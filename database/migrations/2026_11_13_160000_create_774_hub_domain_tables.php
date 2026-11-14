<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, int, float, bool, json
            $table->string('description', 255)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('member_code', 30)->unique();
            $table->unsignedBigInteger('points_balance')->default(0);
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name', 80);
            $table->string('slug', 110)->unique();
            $table->string('description', 300)->nullable();
            $table->unsignedBigInteger('price'); // integer Rupiah
            $table->string('image_path')->nullable();
            $table->boolean('is_available')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['category_id', 'name']);
        });

        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('number', 10)->unique(); // e.g., 01..12
            $table->string('label', 50); // e.g., Meja 07
            $table->string('qr_token', 64)->unique(); // >= 32 random chars
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('rfm_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->json('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rfm_segments', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            $table->json('rule'); // ordered conditions on r_score, f_score, m_score
            $table->integer('priority')->default(10)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rfm_analyses', function (Blueprint $table) {
            $table->id();
            $table->date('analysis_date')->index();
            $table->unsignedInteger('period_days')->default(90);
            $table->json('parameters_snapshot');
            $table->string('triggered_by', 60)->default('system');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('rfm_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfm_analysis_id')->constrained('rfm_analyses')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->unsignedInteger('recency_days')->nullable();
            $table->unsignedInteger('frequency')->default(0);
            $table->unsignedBigInteger('monetary')->default(0);
            $table->unsignedTinyInteger('r_score')->default(1);
            $table->unsignedTinyInteger('f_score')->default(1);
            $table->unsignedTinyInteger('m_score')->default(1);
            $table->foreignId('segment_id')->nullable()->constrained('rfm_segments')->nullOnDelete();
            $table->string('segment_name', 80)->default('Belum Aktif');
            $table->timestamps();

            $table->unique(['rfm_analysis_id', 'member_id']);
            $table->index(['member_id', 'segment_name']);
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('points_cost');
            $table->string('benefit_type', 30)->default('nominal'); // nominal, percent
            $table->unsignedBigInteger('benefit_value');
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->unsignedInteger('valid_days')->default(30);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 100);
            $table->string('rule_type', 40)->index(); // comeback, repeat_order, hari_besar, welcome_next_visit, slow_hour, return_tracking
            $table->string('description', 300)->nullable();
            $table->string('benefit_type', 30)->default('percent'); // percent, nominal, bonus_points
            $table->unsignedBigInteger('benefit_value')->default(0);
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->json('target_segments')->nullable(); // array of RFM segment slugs or ['all']
            $table->unsignedBigInteger('min_transaction')->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_member_limit')->default(1);
            $table->json('conditions')->nullable(); // rule-specific parameters
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique(); // 774-YYYYMMDD-####
            $table->string('order_code', 8)->index(); // 4-char handover code
            $table->string('public_token', 64)->unique(); // Guest tracking token
            $table->string('client_request_id', 64)->unique(); // Idempotency UUID
            $table->string('source', 10)->default('qr')->index(); // qr, pos
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('customer_name', 80);
            $table->string('customer_email', 120)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->string('status', 30)->default('Menunggu Pembayaran')->index();
            $table->string('payment_method', 20)->nullable()->index(); // tunai, qris
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->unsignedBigInteger('reward_redemption_id')->nullable()->index();
            $table->boolean('is_repeat_order')->default(false)->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['member_id', 'status']);
        });

        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('rewards')->restrictOnDelete();
            $table->string('voucher_code', 32)->unique();
            $table->unsignedBigInteger('points_spent');
            $table->string('status', 20)->default('active')->index(); // active, used, expired, cancelled
            $table->timestamp('redeemed_at');
            $table->foreignId('used_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('reward_redemption_id')
                ->references('id')
                ->on('reward_redemptions')
                ->nullOnDelete();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name', 100);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->string('note', 200)->nullable();
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20)->default('system'); // customer, pos, kds, system, webhook
            $table->string('reason', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('method', 20); // tunai, qris
            $table->string('status', 30)->default('pending')->index(); // pending, paid, failed, expired, cancelled, amount_mismatch
            $table->unsignedBigInteger('amount');
            $table->string('provider', 30)->default('cash'); // cash, mock_qris
            $table->string('provider_reference', 80)->nullable();
            $table->text('qris_payload')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('cash_received')->nullable();
            $table->unsignedBigInteger('change_amount')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_reference']);
            $table->index(['order_id', 'status']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('event_id', 100);
            $table->json('payload');
            $table->boolean('signature_valid')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->string('result', 80)->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('invoice_number', 40)->unique(); // INV-774-YYYYMMDD-####
            $table->timestamp('issued_at');
            $table->string('pdf_path')->nullable();
            $table->json('snapshot');
            $table->timestamps();
        });

        Schema::create('invoice_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('channel', 20)->default('email'); // email, whatsapp
            $table->string('recipient', 120);
            $table->string('status', 20)->default('pending')->index(); // pending, sent, failed
            $table->unsignedTinyInteger('attempt_no')->default(1);
            $table->string('provider_message_id', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('triggered_by', 60)->default('system');
            $table->timestamps();
        });

        Schema::create('point_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('reward_redemption_id')->nullable()->constrained('reward_redemptions')->nullOnDelete();
            $table->string('type', 20)->index(); // earn, redeem, reversal, adjust
            $table->bigInteger('points'); // signed
            $table->unsignedBigInteger('balance_after');
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['order_id', 'type']);
        });

        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->unsignedBigInteger('discount_amount');
            $table->unsignedBigInteger('bonus_points')->default(0);
            $table->timestamp('used_at');
            $table->timestamps();

            $table->index(['promotion_id', 'member_id']);
        });

        Schema::create('promotion_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_usage_id')->unique()->constrained('promotion_usages')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('returned_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedInteger('days_to_return')->nullable();
            $table->unsignedBigInteger('return_order_value')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_returns');
        Schema::dropIfExists('promotion_usages');
        Schema::dropIfExists('point_ledger');
        Schema::dropIfExists('invoice_deliveries');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['reward_redemption_id']);
        });
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('rfm_results');
        Schema::dropIfExists('rfm_analyses');
        Schema::dropIfExists('rfm_segments');
        Schema::dropIfExists('rfm_parameters');
        Schema::dropIfExists('tables');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('members');
        Schema::dropIfExists('settings');
    }
};
