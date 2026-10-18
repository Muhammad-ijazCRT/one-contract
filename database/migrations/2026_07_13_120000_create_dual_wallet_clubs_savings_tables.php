<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'main_wallet_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_wallet_balance', 20, 8)->default(0)->after('status');
            });
        }

        if (! Schema::hasTable('investment_clubs')) {
            Schema::create('investment_clubs', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->decimal('reward_percent', 8, 4);
                $table->unsignedSmallInteger('cycle_days');
                $table->decimal('min_investment', 20, 8)->default(10);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('club_investments')) {
            Schema::create('club_investments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('investment_club_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 20, 8)->default(0);
                $table->string('status')->default('active');
                $table->dateTime('started_at')->nullable();
                $table->dateTime('last_reward_at')->nullable();
                $table->dateTime('next_reward_at')->nullable();
                $table->decimal('total_rewards_earned', 20, 8)->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'investment_club_id']);
                $table->index(['status', 'next_reward_at']);
            });
        }

        if (! Schema::hasTable('club_capital_requests')) {
            Schema::create('club_capital_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('club_investment_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 20, 8);
                $table->string('status')->default('pending');
                $table->dateTime('requested_at');
                $table->dateTime('available_at');
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'available_at']);
            });
        }

        if (! Schema::hasTable('savings_plans')) {
            Schema::create('savings_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->decimal('daily_amount', 20, 8);
                $table->unsignedSmallInteger('target_days')->default(365);
                $table->unsignedSmallInteger('successful_days')->default(0);
                $table->decimal('total_saved', 20, 8)->default(0);
                $table->string('status')->default('active');
                $table->dateTime('started_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->dateTime('estimated_completion_at')->nullable();
                $table->dateTime('early_withdrawal_requested_at')->nullable();
                $table->dateTime('early_withdrawal_available_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('savings_deductions')) {
            Schema::create('savings_deductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('savings_plan_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 20, 8)->default(0);
                $table->string('status');
                $table->date('deduction_date');
                $table->dateTime('processed_at')->nullable();
                $table->timestamps();

                $table->unique(['savings_plan_id', 'deduction_date']);
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('terms_acceptances')) {
            Schema::create('terms_acceptances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('document_key');
                $table->dateTime('accepted_at');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'document_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_acceptances');
        Schema::dropIfExists('savings_deductions');
        Schema::dropIfExists('savings_plans');
        Schema::dropIfExists('club_capital_requests');
        Schema::dropIfExists('club_investments');
        Schema::dropIfExists('investment_clubs');

        if (Schema::hasColumn('users', 'main_wallet_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('main_wallet_balance');
            });
        }
    }
};
