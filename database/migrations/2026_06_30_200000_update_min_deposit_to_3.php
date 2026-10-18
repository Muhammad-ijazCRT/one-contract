<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'min_deposit')
            ->where('value', '50')
            ->update(['value' => '3', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'min_deposit')
            ->where('value', '3')
            ->update(['value' => '50', 'updated_at' => now()]);
    }
};
