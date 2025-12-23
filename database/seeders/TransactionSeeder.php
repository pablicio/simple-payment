<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpar transações existentes
        Transaction::truncate();

        // Buscar usuários para criar transações válidas
        $commonUsers = User::where('type', User::TYPE_COMMON)->get();
        $merchants = User::where('type', User::TYPE_MERCHANT)->get();

        if ($commonUsers->isEmpty() || $merchants->isEmpty()) {
            $this->command->warn('⚠️  Não há usuários suficientes para criar transações');
            return;
        }

        $transactions = [];

        // Transações de usuário comum para lojista (pagamentos)
        $transactions[] = [
            'payer_id' => $commonUsers[0]->id, // João
            'payee_id' => $merchants[0]->id,   // Loja ABC
            'value' => 150.00,
            'status' => Transaction::STATUS_COMPLETED,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ];

        $transactions[] = [
            'payer_id' => $commonUsers[1]->id, // Maria
            'payee_id' => $merchants[1]->id,   // Supermercado Central
            'value' => 85.50,
            'status' => Transaction::STATUS_COMPLETED,
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ];

        $transactions[] = [
            'payer_id' => $commonUsers[2]->id, // Pedro
            'payee_id' => $merchants[2]->id,   // Tech Store
            'value' => 300.00,
            'status' => Transaction::STATUS_COMPLETED,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ];

        // Transações entre usuários comuns (P2P)
        $transactions[] = [
            'payer_id' => $commonUsers[0]->id, // João
            'payee_id' => $commonUsers[1]->id, // Maria
            'value' => 50.00,
            'status' => Transaction::STATUS_COMPLETED,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ];

        $transactions[] = [
            'payer_id' => $commonUsers[3]->id, // Ana
            'payee_id' => $commonUsers[4]->id, // Carlos
            'value' => 100.00,
            'status' => Transaction::STATUS_COMPLETED,
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ];

        // Transações pendentes
        $transactions[] = [
            'payer_id' => $commonUsers[4]->id, // Carlos
            'payee_id' => $merchants[3]->id,   // Restaurante
            'value' => 45.00,
            'status' => Transaction::STATUS_PENDING,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ];

        // Transação falhada
        $transactions[] = [
            'payer_id' => $commonUsers[2]->id, // Pedro
            'payee_id' => $merchants[4]->id,   // Farmácia
            'value' => 200.00,
            'status' => Transaction::STATUS_FAILED,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ];

        // Mais transações recentes
        $transactions[] = [
            'payer_id' => $commonUsers[1]->id, // Maria
            'payee_id' => $merchants[0]->id,   // Loja ABC
            'value' => 75.30,
            'status' => Transaction::STATUS_COMPLETED,
            'created_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(30),
        ];

        $transactions[] = [
            'payer_id' => $commonUsers[3]->id, // Ana
            'payee_id' => $merchants[1]->id,   // Supermercado
            'value' => 120.00,
            'status' => Transaction::STATUS_COMPLETED,
            'created_at' => now()->subMinutes(15),
            'updated_at' => now()->subMinutes(15),
        ];

        $transactions[] = [
            'payer_id' => $commonUsers[0]->id, // João
            'payee_id' => $commonUsers[2]->id, // Pedro
            'value' => 25.00,
            'status' => Transaction::STATUS_COMPLETED,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ];

        // Inserir todas as transações
        foreach ($transactions as $transaction) {
            Transaction::create($transaction);
        }

        $this->command->info('✅ Criadas ' . count($transactions) . ' transações de exemplo');
        $this->command->line('   • ' . collect($transactions)->where('status', Transaction::STATUS_COMPLETED)->count() . ' completadas');
        $this->command->line('   • ' . collect($transactions)->where('status', Transaction::STATUS_PENDING)->count() . ' pendentes');
        $this->command->line('   • ' . collect($transactions)->where('status', Transaction::STATUS_FAILED)->count() . ' falhas');
    }
}
