<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_settings', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('phone');
            $table->string('support_hours')->default('24/7 — Always Available');
            $table->string('office_address')->default('Republic of Panama');
            $table->string('social_telegram')->nullable();
            $table->string('social_twitter')->nullable();
            $table->string('social_facebook')->nullable();
            $table->string('social_youtube')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('contact_settings')->insert([
            'email' => 'support@one-contract.com',
            'phone' => '+1 (234) 567-890',
            'support_hours' => '24/7 — Always Available',
            'office_address' => 'Republic of Panama',
            'social_telegram' => null,
            'social_twitter' => null,
            'social_facebook' => null,
            'social_youtube' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_settings');
    }
};
