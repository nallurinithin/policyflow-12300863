<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mock_insurer_policies', function (Blueprint $table) {
            $table->id();
            $table->string('provider_reference')->unique();
            $table->string('client_request_id');
            $table->string('policy_number')->nullable()->unique();
            $table->string('status'); // ISSUED, FAILED, PENDING
            $table->string('scenario')->default('SUCCESS');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('client_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mock_insurer_policies');
    }
};
