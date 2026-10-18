<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_verification_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose', 32);
            $table->string('status', 32)->default('pending');
            $table->string('session_token', 64)->unique();
            $table->json('challenges');
            $table->json('challenges_completed')->nullable();
            $table->boolean('liveness_passed')->default(false);
            $table->decimal('liveness_confidence', 5, 2)->nullable();
            $table->json('liveness_metrics')->nullable();
            $table->boolean('face_match_passed')->nullable();
            $table->decimal('face_match_confidence', 5, 2)->nullable();
            $table->unsignedSmallInteger('face_match_distance')->nullable();
            $table->string('capture_image_path')->nullable();
            $table->string('reference_image_path')->nullable();
            $table->string('failure_reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purpose', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_verification_sessions');
    }
};
