<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issuance_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('policy_request_id')
                  ->constrained('policy_requests')
                  ->onDelete('cascade');

            $table->unsignedInteger('attempt_number');

            // What scenario was used (for mock insurer determinism)
            $table->string('scenario', 50)->nullable();

            // Provider response tracking
            $table->string('provider_reference', 100)->nullable();
            $table->string('response_status', 30)->nullable(); // SUCCESS, FAILURE, TIMEOUT, PENDING
            $table->text('response_payload')->nullable();
            $table->text('error_details')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Prevent duplicate attempt numbers per request
            $table->unique(
                ['policy_request_id', 'attempt_number'],
                'uq_request_attempt'
            );

            $table->index('policy_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issuance_attempts');
    }
};
