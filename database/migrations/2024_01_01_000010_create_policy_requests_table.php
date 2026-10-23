<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Agent idempotency key — the core duplicate-prevention constraint
            $table->string('client_request_id', 100);
            $table->string('agent_id', 50);

            // Customer details
            $table->string('customer_name', 255);
            $table->date('date_of_birth');

            // Trip details
            $table->string('destination', 20); // DOMESTIC | INTERNATIONAL
            $table->date('trip_start');
            $table->date('trip_end');

            // Plan and coverage
            $table->string('plan_code', 20);   // BASIC | PLUS | PREMIUM
            $table->decimal('sum_insured', 12, 2);

            // Calculated values (immutable once set)
            $table->unsignedInteger('travel_days')->nullable();
            $table->decimal('calculated_premium', 12, 2)->nullable();

            // State machine
            $table->string('status', 30)->default('RECEIVED');

            // Policy details (set only when ISSUED)
            $table->string('policy_number', 50)->nullable()->unique();
            $table->string('provider_reference', 100)->nullable();

            $table->timestamps();

            // Composite unique index: prevents duplicate logical requests
            // This is the DATABASE-LEVEL guard against duplicate intake
            $table->unique(['agent_id', 'client_request_id'], 'uq_agent_client_request');

            // Performance indexes
            $table->index('status');
            $table->index('agent_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_requests');
    }
};
