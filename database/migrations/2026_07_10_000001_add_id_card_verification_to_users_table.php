<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'id_card_image_path')) {
                $table->string('id_card_image_path')->nullable()->after('face_image_path');
            }

            if (! Schema::hasColumn('users', 'id_card_verified_at')) {
                $table->timestamp('id_card_verified_at')->nullable()->after('id_card_image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $drops = [];

            if (Schema::hasColumn('users', 'id_card_image_path')) {
                $drops[] = 'id_card_image_path';
            }

            if (Schema::hasColumn('users', 'id_card_verified_at')) {
                $drops[] = 'id_card_verified_at';
            }

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
