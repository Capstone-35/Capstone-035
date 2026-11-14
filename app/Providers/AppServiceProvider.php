<?php

namespace App\Providers;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Drivers\MockQrisGateway;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use App\Policies\InvoicePolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, MockQrisGateway::class);
    }

    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip().'|'.strtolower((string) $request->input('email')));
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(15)->by($request->ip().'|'.$request->session()->getId());
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
