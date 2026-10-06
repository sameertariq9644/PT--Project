<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('affiliate_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('campaign')->default('');
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->timestamps();

            $table->unique(['affiliate_id', 'date', 'campaign']);
            $table->index('date');
        });

        Schema::create('affiliate_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->uuid('visitor_id');
            $table->date('date');
            $table->timestamps();

            $table->unique(['affiliate_id', 'visitor_id', 'date']);
            $table->index('date');
        });

        Schema::create('affiliate_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->char('period', 7);
            $table->unsignedInteger('amount');
            $table->timestamp('paid_at');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['affiliate_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_payouts');
        Schema::dropIfExists('affiliate_visits');
        Schema::dropIfExists('affiliate_daily_stats');
    }
};
