<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `placed_on` and `confirmed_on` hold the Asia/Karachi calendar day of
     * `created_at` and `confirmed_at`, so reports can group by day without
     * database-specific timezone functions.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('plan_name');
            $table->unsignedInteger('amount');
            $table->string('name');
            $table->string('email')->index();
            $table->string('whatsapp');
            $table->string('status')->default('awaiting_payment')->index();
            $table->string('transaction_id')->nullable();
            $table->date('transferred_on')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('lead_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('affiliate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('utm_campaign')->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->unsignedInteger('commission_amount')->default(0);
            $table->date('placed_on');
            $table->date('confirmed_on')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('plan_id');
            $table->index(['affiliate_id', 'confirmed_at']);
            $table->index(['affiliate_id', 'confirmed_on']);
            $table->index(['affiliate_id', 'placed_on']);
            $table->index(['status', 'confirmed_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
