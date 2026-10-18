<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('body');
            $table->string('reaction_emoji', 16)->nullable()->after('is_pinned');
            $table->unsignedInteger('reaction_count')->default(0)->after('reaction_emoji');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'reaction_emoji', 'reaction_count']);
        });
    }
};
