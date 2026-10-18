<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'country_id')) {
                $table->string('country_id', 10)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'face_image_path')) {
                $table->string('face_image_path')->nullable()->after('country_id');
            }

            if (! Schema::hasColumn('users', 'face_verified_at')) {
                $table->timestamp('face_verified_at')->nullable()->after('country_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $drops = [];

            if (Schema::hasColumn('users', 'country_id')) {
                $drops[] = 'country_id';
            }

            if (Schema::hasColumn('users', 'face_image_path')) {
                $drops[] = 'face_image_path';
            }

            if (Schema::hasColumn('users', 'face_verified_at')) {
                $drops[] = 'face_verified_at';
            }

            if ($drops) {
                $table->dropColumn($drops);
            }
        });
    }
};
