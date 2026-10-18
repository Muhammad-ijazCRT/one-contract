<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'id_document_type')) {
                $table->string('id_document_type', 32)->nullable()->after('id_card_back_image_path');
            }
            if (! Schema::hasColumn('users', 'passport_image_path')) {
                $table->string('passport_image_path')->nullable()->after('id_document_type');
            }
            if (! Schema::hasColumn('users', 'license_image_path')) {
                $table->string('license_image_path')->nullable()->after('passport_image_path');
            }
            if (! Schema::hasColumn('users', 'license_back_image_path')) {
                $table->string('license_back_image_path')->nullable()->after('license_image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['id_document_type', 'passport_image_path', 'license_image_path', 'license_back_image_path'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
