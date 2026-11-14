<?php

namespace App\Http\Controllers\Member;

use App\Domain\Loyalty\LoyaltyService;
use App\Domain\Promotion\PromotionEngine;
use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Reward;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MemberPortalController extends Controller
{
    public function __construct(
        protected LoyaltyService $loyaltyService,
        protected PromotionEngine $promotionEngine
    ) {}

    /**
     * C2 Member Dashboard: /member
     */
    public function dashboard(Request $request): View
    {
        $user = Auth::user();
        $member = $user->member()->with(['user', 'latestRfmResult.segment'])->firstOrFail();

        $activePromos = $this->promotionEngine->eligiblePromotions($member, 0, now());
        $activeVouchers = $member->rewardRedemptions()
            ->with('reward')
            ->where('status', 'active')
            ->latest('redeemed_at')
            ->get()
            ->filter->isUsable();

        $recentOrders = $member->orders()
            ->with(['items.product', 'table', 'invoice'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $nextReward = Reward::query()
            ->where('is_active', true)
            ->where('points_cost', '>', $member->points_balance)
            ->orderBy('points_cost')
            ->first()
            ?? Reward::query()->where('is_active', true)->orderByDesc('points_cost')->first();

        $repeatThreshold = (int) Setting::getValue('promo.repeat_order_count', 5);
        $repeatWindowDays = (int) Setting::getValue('promo.repeat_order_days', 30);
        $recentSuccessCount = $member->orders()
            ->successful()
            ->where('created_at', '>=', now()->subDays($repeatWindowDays))
            ->count();

        return view('member.dashboard', [
            'member' => $member,
            'activePromos' => $activePromos,
            'activeVouchers' => $activeVouchers,
            'recentOrders' => $recentOrders,
            'nextReward' => $nextReward,
            'repeatThreshold' => $repeatThreshold,
            'repeatWindowDays' => $repeatWindowDays,
            'recentSuccessCount' => $recentSuccessCount,
        ]);
    }

    /**
     * C3 Profile & Consent Page: /member/profile (UU PDP No. 27/2022)
     */
    public function profile(): View
    {
        $user = Auth::user();
        $member = $user->member()->with('user')->firstOrFail();

        return view('member.profile', [
            'user' => $user,
            'member' => $member,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $user->member()->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'phone.required' => 'Nomor telepon/WhatsApp wajib diisi.',
            'phone.unique' => 'Nomor telepon ini sudah digunakan oleh akun lain.',
        ]);

        $user->update([
            'name' => trim(strip_tags($validated['name'])),
            'email' => strtolower(trim($validated['email'])),
            'phone' => trim($validated['phone']),
        ]);

        return redirect()->route('member.profile')->with('toast_success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('member.profile')->with('toast_success', 'Kata sandi akun Anda berhasil diperbarui.');
    }

    /**
     * C4 Transaction History: /member/orders
     */
    public function orders(Request $request): View
    {
        $member = Auth::user()->member()->with('user')->firstOrFail();
        $statusFilter = $request->query('status');

        $query = $member->orders()
            ->with(['items.product', 'table', 'invoice', 'payments'])
            ->latest('created_at');

        if (! empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $orders = $query->paginate(12)->withQueryString();

        return view('member.orders', [
            'member' => $member,
            'orders' => $orders,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * C5 Transaction Detail: /member/orders/{order:order_number}
     */
    public function orderDetail(Order $order): View
    {
        $member = Auth::user()->member()->with('user')->firstOrFail();

        // Ownership check: return 404 if another member's order is requested (Section 20)
        if ((int) $order->member_id !== (int) $member->id) {
            abort(404);
        }

        $order->load([
            'items.product',
            'table',
            'payments',
            'invoice.deliveries',
            'statusHistories.actor',
            'promotion',
            'rewardRedemption.reward',
            'pointLedgers',
        ]);

        return view('member.order-detail', [
            'member' => $member,
            'order' => $order,
        ]);
    }

    /**
     * Repeat Order ("Pesan Lagi"): loads previous order's available items into session cart.
     */
    public function repeatOrder(Request $request, Order $order): RedirectResponse
    {
        $member = Auth::user()->member()->firstOrFail();

        if ((int) $order->member_id !== (int) $member->id) {
            abort(404);
        }

        $order->load('items.product');

        $cart = (array) $request->session()->get('customer_cart', []);
        $addedCount = 0;
        $skippedNames = [];

        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product || ! $product->is_active || ! $product->is_available) {
                $skippedNames[] = $item->product_name;

                continue;
            }

            $note = $item->note ? trim(strip_tags($item->note)) : '';
            $lineKey = $product->id.'_'.md5(strtolower($note));

            if (isset($cart[$lineKey])) {
                $cart[$lineKey]['quantity'] = min(99, (int) $cart[$lineKey]['quantity'] + (int) $item->quantity);
            } else {
                $cart[$lineKey] = [
                    'line_key' => $lineKey,
                    'product_id' => $product->id,
                    'quantity' => (int) $item->quantity,
                    'note' => $note ?: null,
                ];
            }

            $addedCount++;
        }

        $request->session()->put('customer_cart', $cart);

        // Ensure a table is in session so the customer can checkout right away
        if (! $request->session()->has('qr_table_id')) {
            $table = $order->table ?? DiningTable::query()->where('is_active', true)->orderBy('number')->first();
            if ($table) {
                $request->session()->put('qr_table_id', $table->id);
                $request->session()->put('qr_table_number', $table->number);
                $request->session()->put('qr_table_label', $table->label);
                $request->session()->put('qr_token', $table->qr_token);
            }
        }

        if ($addedCount === 0) {
            return redirect()->route('menu.index')->with(
                'toast_error',
                'Maaf, seluruh item pada pesanan tersebut sedang tidak tersedia saat ini.'
            );
        }

        $msg = "{$addedCount} item dari pesanan {$order->order_number} berhasil dimasukkan ke keranjang.";
        if (! empty($skippedNames)) {
            $msg .= ' Item dilewati karena habis: '.implode(', ', $skippedNames).'.';
        }

        return redirect()->route('cart.index')->with('toast_success', $msg);
    }

    /**
     * C6 Loyalty Points & Rewards Page: /member/loyalty (FR-09, UC-08)
     */
    public function loyalty(): View
    {
        $member = Auth::user()->member()->with('user')->firstOrFail();

        $rewards = Reward::query()
            ->where('is_active', true)
            ->orderBy('points_cost')
            ->get();

        $redemptions = $member->rewardRedemptions()
            ->with(['reward', 'usedOrder'])
            ->latest('redeemed_at')
            ->get();

        $ledgers = $member->pointLedger()
            ->with('order')
            ->latest('created_at')
            ->paginate(15);

        return view('member.loyalty', [
            'member' => $member,
            'rewards' => $rewards,
            'redemptions' => $redemptions,
            'ledgers' => $ledgers,
            'earnUnitAmount' => (int) Setting::getValue('loyalty.points_per_amount', 10000),
            'earnOnStatus' => (string) Setting::getValue('loyalty.earn_on_status', 'Selesai'),
        ]);
    }

    public function redeemReward(Request $request, Reward $reward): RedirectResponse
    {
        $member = Auth::user()->member()->firstOrFail();

        try {
            $redemption = $this->loyaltyService->redeemReward($member, $reward);

            return redirect()->route('member.loyalty')->with(
                'toast_success',
                "Berhasil menukar {$reward->name}! Kode voucher Anda: {$redemption->voucher_code}"
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('toast_error', collect($e->errors())->flatten()->first());
        }
    }
}
