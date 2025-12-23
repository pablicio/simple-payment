<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seed mínimo para demonstração rápida
     */
    public function run(): void
    {
        $this->command->info('🎯 Criando dados mínimos para demo...');

        // Limpar dados
        User::truncate();
        Transaction::truncate();

        // 2 usuários comuns
        $user1 = User::create([
            'name' => 'Alice Demo',
            'email' => 'alice@demo.com',
            'document' => '11111111111',
            'password' => Hash::make('demo123'),
            'type' => User::TYPE_COMMON,
            'balance' => 1000.00,
        ]);

        $user2 = User::create([
            'name' => 'Bob Demo',
            'email' => 'bob@demo.com',
            'document' => '22222222222',
            'password' => Hash::make('demo123'),
            'type' => User::TYPE_COMMON,
            'balance' => 500.00,
        ]);

        // 1 lojista
        $merchant = User::create([
            'name' => 'Demo Store',
            'email' => 'store@demo.com',
            'document' => '12345678000100',
            'password' => Hash::make('demo123'),
            'type' => User::TYPE_MERCHANT,
            'balance' => 2000.00,
        ]);

        // 2 transações de exemplo
        Transaction::create([
            'payer_id' => $user1->id,
            'payee_id' => $user2->id,
            'amount' => 100.00,
            'status' => Transaction::STATUS_COMPLETED,
            'created_at' => now()->subHour(),
        ]);

        Transaction::create([
            'payer_id' => $user2->id,
            'payee_id' => $merchant->id,
            'amount' => 50.00,
            'status' => Transaction::STATUS_COMPLETED,
            'created_at' => now()->subMinutes(30),
        ]);

        $this->command->newLine();
        $this->command->info('✅ Demo seed concluído!');
        $this->command->newLine();
        $this->command->line('👤 alice@demo.com | Senha: demo123 | Saldo: R$ 1.000,00');
        $this->command->line('👤 bob@demo.com   | Senha: demo123 | Saldo: R$ 500,00');
        $this->command->line('🏪 store@demo.com | Senha: demo123 | Saldo: R$ 2.000,00');
    }
}
