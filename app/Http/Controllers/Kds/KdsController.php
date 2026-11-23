<?php

namespace App\Http\Controllers\Kds;

use App\Domain\Ordering\OrderService;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KdsController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * F1 KDS Main Board: /kds (FR-07, UC-06, NFR-03)
     * 3 columns: Pesanan Baru (Dibayar), Sedang Diproses (Diproses), Siap Diserahkan (Siap).
     * Never exposes prices or revenue totals.
     */
    public function index(): View
    {
        $paidOrders = Order::query()
            ->select([
                'id', 'order_number', 'order_code', 'source', 'table_id',
                'customer_name', 'status', 'paid_at', 'processing_at', 'ready_at', 'created_at',
            ])
            ->with([
                'table:id,number,label',
                'items:id,order_id,product_id,product_name,quantity,note',
            ])
            ->where('status', OrderStatus::Dibayar->value)
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get();

        $processingOrders = Order::query()
            ->select([
                'id', 'order_number', 'order_code', 'source', 'table_id',
                'customer_name', 'status', 'paid_at', 'processing_at', 'ready_at', 'created_at',
            ])
            ->with([
                'table:id,number,label',
                'items:id,order_id,product_id,product_name,quantity,note',
            ])
            ->where('status', OrderStatus::Diproses->value)
            ->orderBy('processing_at')
            ->orderBy('id')
            ->get();

        $readyOrders = Order::query()
            ->select([
                'id', 'order_number', 'order_code', 'source', 'table_id',
                'customer_name', 'status', 'paid_at', 'processing_at', 'ready_at', 'created_at',
            ])
            ->with([
                'table:id,number,label',
                'items:id,order_id,product_id,product_name,quantity,note',
            ])
            ->where('status', OrderStatus::Siap->value)
            ->orderBy('ready_at')
            ->orderBy('id')
            ->get();

        return view('kds.board', [
            'paidOrders' => $paidOrders,
            'processingOrders' => $processingOrders,
            'readyOrders' => $readyOrders,
        ]);
    }

    /**
     * F5 Completed Orders History & Quick Menu Availability Toggle: /kds/history
     */
    public function history(): View
    {
        $completedOrders = Order::query()
            ->select([
                'id', 'order_number', 'order_code', 'source', 'table_id',
                'customer_name', 'status', 'paid_at', 'processing_at', 'ready_at', 'completed_at', 'handed_over_by', 'created_at',
            ])
            ->with([
                'table:id,number,label',
                'items:id,order_id,product_id,product_name,quantity,note',
                'handedOverBy:id,name',
            ])
            ->where('status', OrderStatus::Selesai->value)
            ->latest('completed_at')
            ->limit(30)
            ->get();

        $products = Product::query()
            ->select(['id', 'category_id', 'name', 'slug', 'is_available', 'is_active'])
            ->with('category:id,name')
            ->where('is_active', true)
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        return view('kds.history', [
            'completedOrders' => $completedOrders,
            'products' => $products,
        ]);
    }

    /**
     * JSON feed for live KDS polling (every 3s, NFR-03 <= 5s latency).
     * Strictly operational data only — zero price/revenue fields.
     */
    public function feed(): JsonResponse
    {
        $orders = Order::query()
            ->select([
                'id', 'order_number', 'order_code', 'source', 'table_id',
                'customer_name', 'status', 'paid_at', 'processing_at', 'ready_at', 'created_at',
            ])
            ->with([
                'table:id,number,label',
                'items:id,order_id,product_id,product_name,quantity,note',
            ])
            ->whereIn('status', [
                OrderStatus::Dibayar->value,
                OrderStatus::Diproses->value,
                OrderStatus::Siap->value,
            ])
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get()
            ->map(function (Order $o) {
                $elapsedMinutes = $o->paid_at ? max(0, (int) $o->paid_at->diffInMinutes(now())) : 0;

                return [
                    'order_number' => $o->order_number,
                    'short_number' => $o->shortNumber(),
                    'order_code' => $o->order_code,
                    'source' => strtoupper($o->source),
                    'table_label' => $o->table?->label ?? 'Takeaway/POS',
                    'customer_name' => $o->customer_name,
                    'status' => $o->status->value,
                    'paid_at' => $o->paid_at?->timezone('Asia/Jakarta')->format('H:i:s'),
                    'processing_at' => $o->processing_at?->timezone('Asia/Jakarta')->format('H:i:s'),
                    'ready_at' => $o->ready_at?->timezone('Asia/Jakarta')->format('H:i:s'),
                    'elapsed_minutes' => $elapsedMinutes,
                    'items' => $o->items->map(fn ($i) => [
                        'product_name' => $i->product_name,
                        'quantity' => $i->quantity,
                        'note' => $i->note,
                    ])->values(),
                ];
            });

        return response()->json([
            'counts' => [
                'dibayar' => $orders->where('status', OrderStatus::Dibayar->value)->count(),
                'diproses' => $orders->where('status', OrderStatus::Diproses->value)->count(),
                'siap' => $orders->where('status', OrderStatus::Siap->value)->count(),
            ],
            'orders' => $orders->values(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * F3 Start Processing: Dibayar -> Diproses
     */
    public function startOrder(Order $order): RedirectResponse
    {
        try {
            $this->orderService->startProcessing($order, Auth::user());

            return back()->with('toast_success', "Pesanan {$order->order_number} mulai diproses.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('toast_error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * F3 & F4 Mark Ready: Diproses -> Siap with D11 quality confirmation ("Pesanan sudah sesuai?")
     */
    public function readyOrder(Request $request, Order $order): RedirectResponse
    {
        $qualityConfirmed = $request->boolean('quality_confirmed', false);

        try {
            $this->orderService->markReady($order, Auth::user(), $qualityConfirmed);

            return back()->with('toast_success', "Pesanan {$order->order_number} ditandai Siap diserahkan.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('toast_error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * F4 Handover from KDS ("Serahkan ke Pelanggan", D3): Siap -> Selesai
     */
    public function handoverOrder(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'order_code' => ['required', 'string', 'max:8'],
        ], [
            'order_code.required' => 'Masukkan 4 karakter kode pesanan pelanggan untuk verifikasi penyerahan.',
        ]);

        try {
            $this->orderService->handover($order, Auth::user(), $validated['order_code']);

            return back()->with(
                'toast_success',
                "Pesanan {$order->order_number} (Kode {$order->order_code}) telah diserahkan ke pelanggan dan selesai."
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('toast_error', collect($e->errors())->flatten()->first());
        }
    }
}
