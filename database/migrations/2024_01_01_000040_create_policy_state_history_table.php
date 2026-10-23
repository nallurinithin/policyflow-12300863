<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_state_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('policy_request_id')
                  ->constrained('policy_requests')
                  ->onDelete('cascade');

            // State transition details
            $table->string('from_state', 30)->nullable(); // null for initial RECEIVED state
            $table->string('to_state', 30);

            // What triggered this transition
            $table->string('trigger', 100); // e.g., "api_intake", "submit_request", "provider_callback", "reconciliation"

            // Optional context about the transition
            $table->text('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('policy_request_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_state_history');
    }
};
