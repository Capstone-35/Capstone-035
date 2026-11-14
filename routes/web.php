<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\CustomerOrderingController;
use App\Http\Controllers\Kds\KdsController;
use App\Http\Controllers\Manager\ManagerController;
use App\Http\Controllers\Member\MemberPortalController;
use App\Http\Controllers\Owner\OwnerController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Styleguide\StyleguideController;
use App\Http\Controllers\Webhook\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. Public & Customer Ordering Routes (FR-01 .. FR-05, FR-08, NFR-12)
|--------------------------------------------------------------------------
*/
Route::get('/', [CustomerOrderingController::class, 'home'])->name('home');
Route::get('/t/{qr_token}', [CustomerOrderingController::class, 'scanTable'])->name('table.scan');
Route::post('/t/{qr_token}/guest', [CustomerOrderingController::class, 'continueAsGuest'])->name('table.continue_guest');

Route::get('/menu', [CustomerOrderingController::class, 'menu'])->name('menu.index');
Route::get('/menu/{product:slug}', [CustomerOrderingController::class, 'productDetail'])->name('menu.show');

Route::get('/cart', [CustomerOrderingController::class, 'cart'])->name('cart.index');
Route::post('/cart/items', [CustomerOrderingController::class, 'addToCart'])->name('cart.items.store');
Route::match(['put', 'patch'], '/cart/items/{lineKey}', [CustomerOrderingController::class, 'updateCartItem'])->name('cart.items.update');
Route::delete('/cart/items/{lineKey}', [CustomerOrderingController::class, 'removeCartItem'])->name('cart.items.destroy');

Route::get('/checkout', [CustomerOrderingController::class, 'checkout'])->name('checkout.index');
Route::post('/checkout', [CustomerOrderingController::class, 'placeOrder'])
    ->middleware('throttle:checkout')
    ->name('checkout.store');

Route::get('/order/{public_token}/payment', [CustomerOrderingController::class, 'paymentPage'])->name('order.payment');
Route::post('/order/{public_token}/payment', [CustomerOrderingController::class, 'selectPaymentMethod'])->name('order.payment.select');
Route::post('/order/{public_token}/payment/simulate-qris', [PaymentWebhookController::class, 'simulateCustomerQris'])->name('order.payment.simulate_qris');

Route::get('/order/{public_token}', [CustomerOrderingController::class, 'trackOrder'])->name('order.track');
Route::get('/order/{public_token}/status', [CustomerOrderingController::class, 'orderStatusJson'])->name('order.status.json');
Route::post('/order/{public_token}/cancel', [CustomerOrderingController::class, 'cancelOwnOrder'])->name('order.cancel.customer');
Route::get('/order/{public_token}/invoice', [CustomerOrderingController::class, 'showInvoice'])->name('order.invoice.show');
Route::get('/order/{public_token}/invoice.pdf', [CustomerOrderingController::class, 'downloadInvoicePdf'])->name('order.invoice.pdf');

Route::get('/promotions', [CustomerOrderingController::class, 'promotions'])->name('promotions.public');
Route::view('/offline-notice', 'offline')->name('offline');

/*
|--------------------------------------------------------------------------
| 2. Authentication & Shared Routes (FR-02, FR-11, NFR-08, NFR-09)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showMemberLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'memberLogin'])->middleware('throttle:login')->name('login.store');

    Route::get('/register', [AuthController::class, 'showMemberRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'memberRegister'])->middleware('throttle:register')->name('register.store');

    Route::get('/staff/login', [AuthController::class, 'showStaffLogin'])->name('staff.login');
    Route::post('/staff/login', [AuthController::class, 'staffLogin'])->middleware('throttle:login')->name('staff.login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/styleguide', [StyleguideController::class, 'index'])
    ->middleware(['auth', 'role:manager|owner'])
    ->name('styleguide');

/*
|--------------------------------------------------------------------------
| 3. Member Portal Routes (auth + role:member)
|--------------------------------------------------------------------------
*/
Route::prefix('member')
    ->name('member.')
    ->middleware(['auth', 'role:member'])
    ->group(function () {
        Route::get('/', [MemberPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [MemberPortalController::class, 'profile'])->name('profile');
        Route::match(['put', 'patch'], '/profile', [MemberPortalController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [MemberPortalController::class, 'updatePassword'])->name('profile.password');
        Route::get('/orders', [MemberPortalController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{order:order_number}', [MemberPortalController::class, 'orderDetail'])->name('orders.show');
        Route::post('/orders/{order:order_number}/repeat', [MemberPortalController::class, 'repeatOrder'])->name('orders.repeat');
        Route::get('/loyalty', [MemberPortalController::class, 'loyalty'])->name('loyalty');
        Route::post('/rewards/{reward}/redeem', [MemberPortalController::class, 'redeemReward'])->name('rewards.redeem');
    });

/*
|--------------------------------------------------------------------------
| 4. Kasir Point of Sale (POS) Routes (auth + permission:pos.access)
|--------------------------------------------------------------------------
*/
Route::prefix('pos')
    ->name('pos.')
    ->middleware(['auth', 'permission:pos.access'])
    ->group(function () {
        Route::get('/', [PosController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [PosController::class, 'orders'])->middleware('permission:orders.view')->name('orders.index');
        Route::get('/orders/{order:order_number}', [PosController::class, 'showOrder'])->middleware('permission:orders.view')->name('orders.show');
        Route::match(['put', 'patch'], '/orders/{order:order_number}', [PosController::class, 'updateOrderItems'])->middleware('permission:orders.edit')->name('orders.update');

        Route::get('/new', [PosController::class, 'createManualOrder'])->middleware('permission:orders.create')->name('orders.create');
        Route::post('/orders', [PosController::class, 'storeManualOrder'])->middleware('permission:orders.create')->name('orders.store');

        Route::get('/payments', [PosController::class, 'payments'])->middleware('permission:orders.confirm_cash')->name('payments.index');
        Route::post('/orders/{order:order_number}/confirm-cash', [PosController::class, 'confirmCash'])->middleware('permission:orders.confirm_cash')->name('orders.confirm_cash');
        Route::post('/orders/{order:order_number}/cancel', [PosController::class, 'cancelOrder'])->middleware('permission:orders.cancel_unpaid')->name('orders.cancel');
        Route::post('/orders/{order:order_number}/handover', [PosController::class, 'handover'])->middleware('permission:orders.handover')->name('orders.handover');

        Route::get('/transactions', [PosController::class, 'transactions'])->middleware('permission:orders.view')->name('transactions.index');
        Route::get('/invoices', [PosController::class, 'invoices'])->middleware('permission:invoices.view')->name('invoices.index');
        Route::post('/invoices/{invoice}/resend', [PosController::class, 'resendInvoice'])->middleware('permission:invoices.resend')->name('invoices.resend');

        Route::post('/products/{product}/toggle-availability', [PosController::class, 'toggleProductAvailability'])
            ->middleware('permission:products.toggle_availability')
            ->name('products.toggle');

        Route::get('/feed', [PosController::class, 'feed'])->middleware('permission:orders.view')->name('feed');
    });

/*
|--------------------------------------------------------------------------
| 5. Kitchen Display System (KDS) Routes (auth + role:kitchen)
|--------------------------------------------------------------------------
*/
Route::prefix('kds')
    ->name('kds.')
    ->middleware(['auth', 'role:kitchen'])
    ->group(function () {
        Route::get('/', [KdsController::class, 'index'])->middleware('permission:kds.view')->name('index');
        Route::get('/history', [KdsController::class, 'history'])->middleware('permission:kds.view')->name('history');
        Route::get('/feed', [KdsController::class, 'feed'])->middleware('permission:kds.view')->name('feed');

        Route::post('/orders/{order:order_number}/start', [KdsController::class, 'startOrder'])
            ->middleware('permission:kds.update_status')
            ->name('orders.start');
        Route::post('/orders/{order:order_number}/ready', [KdsController::class, 'readyOrder'])
            ->middleware('permission:kds.update_status')
            ->name('orders.ready');
        Route::post('/orders/{order:order_number}/handover', [KdsController::class, 'handoverOrder'])
            ->middleware('permission:orders.handover')
            ->name('orders.handover');

        Route::post('/products/{product}/toggle-availability', [PosController::class, 'toggleProductAvailability'])
            ->middleware('permission:products.toggle_availability')
            ->name('products.toggle');
    });

/*
|--------------------------------------------------------------------------
| 6. Manager Routes (auth + role:manager)
|--------------------------------------------------------------------------
*/
Route::prefix('manager')
    ->name('manager.')
    ->middleware(['auth', 'role:manager'])
    ->group(function () {
        Route::get('/', [ManagerController::class, 'dashboard'])->name('dashboard');
        Route::get('/transactions', [ManagerController::class, 'transactions'])->name('transactions.index');
        Route::get('/transactions/{order:order_number}', [ManagerController::class, 'showTransaction'])->name('transactions.show');
        Route::post('/orders/{order:order_number}/cancel', [ManagerController::class, 'cancelOrder'])->name('orders.cancel');
        Route::post('/invoices/{invoice}/resend', [PosController::class, 'resendInvoice'])->name('invoices.resend');

        Route::get('/orders', [ManagerController::class, 'orders'])->name('orders.index');

        Route::get('/members', [ManagerController::class, 'members'])->name('members.index');
        Route::get('/members/{member}', [ManagerController::class, 'showMember'])->name('members.show');

        Route::get('/rfm', [ManagerController::class, 'rfmIndex'])->name('rfm.index');
        Route::post('/rfm/recalculate', [ManagerController::class, 'rfmRecalculate'])->name('rfm.recalculate');
        Route::get('/rfm/segments/{segment:slug}', [ManagerController::class, 'rfmSegment'])->name('rfm.segment');
        Route::get('/rfm/parameters', [ManagerController::class, 'rfmParameters'])->name('rfm.parameters');
        Route::match(['put', 'patch'], '/rfm/parameters', [ManagerController::class, 'rfmUpdateParameters'])->name('rfm.parameters.update');

        Route::get('/promotions', [ManagerController::class, 'promotionsIndex'])->name('promotions.index');
        Route::get('/promotions/create', [ManagerController::class, 'promotionsCreate'])->name('promotions.create');
        Route::post('/promotions', [ManagerController::class, 'promotionsStore'])->name('promotions.store');
        Route::get('/promotions/performance', [ManagerController::class, 'promotionsPerformance'])->name('promotions.performance');
        Route::get('/promotions/{promotion}', [ManagerController::class, 'promotionsShow'])->name('promotions.show');
        Route::get('/promotions/{promotion}/edit', [ManagerController::class, 'promotionsEdit'])->name('promotions.edit');
        Route::match(['put', 'patch'], '/promotions/{promotion}', [ManagerController::class, 'promotionsUpdate'])->name('promotions.update');
        Route::post('/promotions/{promotion}/toggle', [ManagerController::class, 'promotionsToggle'])->name('promotions.toggle');

        Route::get('/loyalty', [ManagerController::class, 'loyaltyIndex'])->name('loyalty.index');
        Route::match(['put', 'patch'], '/loyalty', [ManagerController::class, 'loyaltyUpdate'])->name('loyalty.update');
        Route::get('/rewards', [ManagerController::class, 'rewardsIndex'])->name('rewards.index');
        Route::post('/rewards', [ManagerController::class, 'rewardsStore'])->name('rewards.store');
        Route::match(['put', 'patch'], '/rewards/{reward}', [ManagerController::class, 'rewardsUpdate'])->name('rewards.update');

        Route::get('/products', [ManagerController::class, 'productsIndex'])->name('products.index');
        Route::get('/products/create', [ManagerController::class, 'productsCreate'])->name('products.create');
        Route::post('/products', [ManagerController::class, 'productsStore'])->name('products.store');
        Route::get('/products/{product}/edit', [ManagerController::class, 'productsEdit'])->name('products.edit');
        Route::match(['put', 'patch'], '/products/{product}', [ManagerController::class, 'productsUpdate'])->name('products.update');
        Route::delete('/products/{product}', [ManagerController::class, 'productsDestroy'])->name('products.destroy');
        Route::post('/products/{product}/toggle-availability', [PosController::class, 'toggleProductAvailability'])->name('products.toggle');

        Route::get('/categories', [ManagerController::class, 'categoriesIndex'])->name('categories.index');
        Route::post('/categories', [ManagerController::class, 'categoriesStore'])->name('categories.store');
        Route::match(['put', 'patch'], '/categories/{category}', [ManagerController::class, 'categoriesUpdate'])->name('categories.update');
        Route::delete('/categories/{category}', [ManagerController::class, 'categoriesDestroy'])->name('categories.destroy');

        Route::get('/tables', [ManagerController::class, 'tablesIndex'])->name('tables.index');
        Route::post('/tables', [ManagerController::class, 'tablesStore'])->name('tables.store');
        Route::post('/tables/{table}/regenerate', [ManagerController::class, 'tablesRegenerate'])->name('tables.regenerate');
        Route::get('/tables/{table}/qr.svg', [ManagerController::class, 'tablesQrSvg'])->name('tables.qr');

        Route::get('/reports', [ManagerController::class, 'reportsIndex'])->name('reports.index');

        Route::get('/users', [ManagerController::class, 'usersIndex'])->name('users.index');
        Route::post('/users', [ManagerController::class, 'usersStore'])->name('users.store');
        Route::match(['put', 'patch'], '/users/{user}', [ManagerController::class, 'usersUpdate'])->name('users.update');
    });

/*
|--------------------------------------------------------------------------
| 7. Owner Routes (auth + role:owner)
|--------------------------------------------------------------------------
*/
Route::prefix('owner')
    ->name('owner.')
    ->middleware(['auth', 'role:owner'])
    ->group(function () {
        Route::get('/', [OwnerController::class, 'dashboard'])->name('dashboard');
        Route::get('/reports/sales', [OwnerController::class, 'salesReport'])->name('reports.sales');
        Route::get('/reports/revenue', [OwnerController::class, 'revenueReport'])->name('reports.revenue');
        Route::get('/customers', [OwnerController::class, 'customers'])->name('customers.index');
        Route::get('/promotions', [OwnerController::class, 'promotions'])->name('promotions.index');

        Route::get('/users', [OwnerController::class, 'usersIndex'])->name('users.index');
        Route::post('/users', [OwnerController::class, 'usersStore'])->name('users.store');
        Route::match(['put', 'patch'], '/users/{user}', [OwnerController::class, 'usersUpdate'])->name('users.update');
    });

/*
|--------------------------------------------------------------------------
| 8. Payment Webhooks & Non-Production Simulator
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/payments/{provider}', [PaymentWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->name('webhooks.payments');

Route::prefix('sandbox/payments')
    ->name('sandbox.payments.')
    ->middleware(['auth', 'role:manager'])
    ->group(function () {
        Route::get('/', [PaymentWebhookController::class, 'sandboxIndex'])->name('index');
        Route::post('/trigger', [PaymentWebhookController::class, 'sandboxTrigger'])->name('trigger');
    });
