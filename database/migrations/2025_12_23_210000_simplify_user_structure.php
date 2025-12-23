<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Adicionar campo type na tabela users (se não existir)
        if (!Schema::hasColumn('users', 'type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('type', ['common', 'merchant'])->default('common')->after('email');
            });
        }

        // 2. Renomear coluna cpf para document (se necessário)
        if (Schema::hasColumn('users', 'cpf') && !Schema::hasColumn('users', 'document')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('cpf', 'document');
            });
        }

        // 3. Migrar dados de shopkeepers para users (se houver)
        if (Schema::hasTable('shopkeepers')) {
            $shopkeepers = DB::table('shopkeepers')->get();
            
            foreach ($shopkeepers as $shopkeeper) {
                $exists = DB::table('users')->where('email', $shopkeeper->email)->exists();
                
                if (!$exists) {
                    DB::table('users')->insert([
                        'name' => $shopkeeper->name,
                        'email' => $shopkeeper->email,
                        'document' => $shopkeeper->cnpj,
                        'password' => $shopkeeper->password,
                        'type' => 'merchant',
                        'balance' => $shopkeeper->balance ?? 0,
                        'created_at' => $shopkeeper->created_at ?? now(),
                        'updated_at' => $shopkeeper->updated_at ?? now(),
                    ]);
                }
            }
        }

        // 4. SQLITE: Não suporta drop column com índices - precisa recriar a tabela
        if (Schema::hasColumn('transactions', 'payee_type')) {
            // Criar tabela temporária
            Schema::create('transactions_temp', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payer_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('payee_id')->constrained('users')->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->string('status')->default('pending');
                $table->text('description')->nullable();
                $table->timestamps();
            });

            // Copiar dados (apenas onde payee_type é User, pois agora todos são users)
            DB::statement('
                INSERT INTO transactions_temp (id, payer_id, payee_id, amount, status, description, created_at, updated_at)
                SELECT id, payer_id, payee_id, amount, status, description, created_at, updated_at
                FROM transactions
            ');

            // Dropar tabela antiga e renomear
            Schema::drop('transactions');
            Schema::rename('transactions_temp', 'transactions');
        }

        // 5. Remover tabelas antigas
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('shopkeepers');
    }

    public function down(): void
    {
        // Reverter mudanças
        Schema::create('shopkeepers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('cnpj')->unique();
            $table->string('password');
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });

        // Recriar transactions com payee_type
        if (!Schema::hasColumn('transactions', 'payee_type')) {
            Schema::create('transactions_temp', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payer_id')->constrained('users')->onDelete('cascade');
                $table->morphs('payee');
                $table->decimal('amount', 15, 2);
                $table->string('status')->default('pending');
                $table->text('description')->nullable();
                $table->timestamps();
            });

            DB::statement('
                INSERT INTO transactions_temp (id, payer_id, payee_id, payee_type, amount, status, description, created_at, updated_at)
                SELECT id, payer_id, payee_id, "App\\\\Models\\\\User", amount, status, description, created_at, updated_at
                FROM transactions
            ');

            Schema::drop('transactions');
            Schema::rename('transactions_temp', 'transactions');
        }

        if (Schema::hasColumn('users', 'document') && !Schema::hasColumn('users', 'cpf')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('document', 'cpf');
            });
        }

        if (Schema::hasColumn('users', 'type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
