<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_events', function (Blueprint $table) {
            $table->id();

            // Idempotency key — the DATABASE-LEVEL guard against duplicate callbacks
            $table->string('event_id', 100)->unique();

            $table->foreignId('policy_request_id')
                  ->constrained('policy_requests')
                  ->onDelete('cascade');

            // Event details
            $table->string('event_type', 50);        // POLICY_ISSUED, POLICY_FAILED, STATUS_UPDATE
            $table->string('provider_reference', 100)->nullable();
            $table->string('policy_number', 50)->nullable();
            $table->text('payload')->nullable();

            // Processing flag — ensures each event is applied exactly once
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('policy_request_id');
            $table->index(['processed', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_events');
    }
};
