<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dojah_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32);
            $table->string('purpose', 32)->nullable();
            $table->string('reference', 64)->nullable()->index();
            $table->string('staging_token', 64)->nullable()->index();
            $table->boolean('passed')->default(false);
            $table->string('decision', 32)->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('probability', 8, 2)->nullable();
            $table->string('message', 500)->nullable();
            $table->json('request_meta')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });

        $now = now();
        $rows = [
            ['key' => 'dojah_enabled', 'value' => '1', 'group' => 'dojah'],
            ['key' => 'dojah_mode', 'value' => 'sandbox', 'group' => 'dojah'],
            ['key' => 'dojah_liveness_enabled', 'value' => '1', 'group' => 'dojah'],
            ['key' => 'dojah_transaction_monitoring_enabled', 'value' => '1', 'group' => 'dojah'],
            ['key' => 'dojah_block_risky_withdrawals', 'value' => '0', 'group' => 'dojah'],
            ['key' => 'dojah_min_liveness_probability', 'value' => '70', 'group' => 'dojah'],
            ['key' => 'dojah_sandbox_base_url', 'value' => 'https://sandbox.dojah.io', 'group' => 'dojah'],
            ['key' => 'dojah_live_base_url', 'value' => 'https://api.dojah.io', 'group' => 'dojah'],
            ['key' => 'dojah_sandbox_public_key', 'value' => (string) env('DOJAH_SANDBOX_PUBLIC_KEY', ''), 'group' => 'dojah'],
            ['key' => 'dojah_sandbox_secret_key', 'value' => (string) env('DOJAH_SANDBOX_SECRET_KEY', ''), 'group' => 'dojah'],
            ['key' => 'dojah_sandbox_app_id', 'value' => (string) env('DOJAH_SANDBOX_APP_ID', ''), 'group' => 'dojah'],
            ['key' => 'dojah_sandbox_ingest_key', 'value' => (string) env('DOJAH_SANDBOX_INGEST_KEY', ''), 'group' => 'dojah'],
            ['key' => 'dojah_live_public_key', 'value' => (string) env('DOJAH_LIVE_PUBLIC_KEY', ''), 'group' => 'dojah'],
            ['key' => 'dojah_live_secret_key', 'value' => (string) env('DOJAH_LIVE_SECRET_KEY', ''), 'group' => 'dojah'],
            ['key' => 'dojah_live_app_id', 'value' => (string) env('DOJAH_LIVE_APP_ID', ''), 'group' => 'dojah'],
            ['key' => 'dojah_live_ingest_key', 'value' => (string) env('DOJAH_LIVE_INGEST_KEY', ''), 'group' => 'dojah'],
            ['key' => 'dojah_webhook_url', 'value' => '', 'group' => 'dojah'],
        ];

        foreach ($rows as $row) {
            if (DB::table('settings')->where('key', $row['key'])->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $row['key'],
                'value' => $row['value'],
                'group' => $row['group'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dojah_checks');
        DB::table('settings')->where('group', 'dojah')->delete();
    }
};
