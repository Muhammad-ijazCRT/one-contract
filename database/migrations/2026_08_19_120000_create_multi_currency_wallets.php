<?php

use App\Enums\WalletCurrency;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 10);
            $table->decimal('balance', 24, 8)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'currency']);
            $table->index('currency');
        });

        Schema::create('wallet_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('from_currency', 10);
            $table->string('to_currency', 10);
            $table->decimal('from_amount', 24, 8);
            $table->decimal('to_amount', 24, 8);
            $table->decimal('rate', 24, 12);
            $table->decimal('fee_amount', 24, 8)->default(0);
            $table->decimal('fee_percent', 8, 4)->default(0);
            $table->string('status', 20)->default('completed');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        if (Schema::hasTable('withdrawal_requests') && ! Schema::hasColumn('withdrawal_requests', 'currency')) {
            Schema::table('withdrawal_requests', function (Blueprint $table) {
                $table->string('currency', 10)->default('USDT')->after('type');
            });
        }

        if (Schema::hasTable('deposits') && ! Schema::hasColumn('deposits', 'credit_currency')) {
            Schema::table('deposits', function (Blueprint $table) {
                $table->string('credit_currency', 10)->default('USDT')->after('amount');
            });
        }

        // Seed wallets + migrate legacy balances
        User::query()->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $user) {
                foreach (WalletCurrency::all() as $currency) {
                    $balance = 0.0;
                    if ($currency === WalletCurrency::Usdt) {
                        $balance = (float) $user->main_wallet_balance;
                    } elseif ($currency === WalletCurrency::Ngn) {
                        $balance = (float) ($user->ngn_wallet_balance ?? 0);
                    }

                    DB::table('user_wallets')->updateOrInsert(
                        [
                            'user_id' => $user->id,
                            'currency' => $currency->value,
                        ],
                        [
                            'balance' => $balance,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('deposits', 'credit_currency')) {
            Schema::table('deposits', function (Blueprint $table) {
                $table->dropColumn('credit_currency');
            });
        }

        if (Schema::hasColumn('withdrawal_requests', 'currency')) {
            Schema::table('withdrawal_requests', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }

        Schema::dropIfExists('wallet_conversions');
        Schema::dropIfExists('user_wallets');
    }
};
