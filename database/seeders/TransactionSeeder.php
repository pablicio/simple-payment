<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Transaction;
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

        // Buscar usuários
        $joao = User::where('email', 'joao@example.com')->first();
        $maria = User::where('email', 'maria@example.com')->first();
        $pedro = User::where('email', 'pedro@example.com')->first();
        $ana = User::where('email', 'ana@example.com')->first();
        $carlos = User::where('email', 'carlos@example.com')->first();

        $lojaAbc = User::where('email', 'contato@lojaabc.com')->first();
        $superCentral = User::where('email', 'vendas@supercentral.com')->first();
        $techStore = User::where('email', 'contato@techstore.com')->first();
        $restaurante = User::where('email', 'pedidos@saborarte.com')->first();

        // Verificar se os usuários existem
        if (!$joao || !$maria || !$lojaAbc) {
            $this->command->warn('⚠️  Execute primeiro: php artisan db:seed --class=UserSeeder');
            return;
        }

        // Transações de exemplo
        $transactions = [
            // Transferências entre usuários comuns
            [
                'payer_id' => $joao->id,
                'payee_id' => $maria->id,
                'amount' => 100.00,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Pagamento de almoço',
                'created_at' => now()->subDays(5),
            ],
            [
                'payer_id' => $maria->id,
                'payee_id' => $pedro->id,
                'amount' => 50.50,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Divisão de conta',
                'created_at' => now()->subDays(4),
            ],
            [
                'payer_id' => $ana->id,
                'payee_id' => $carlos->id,
                'amount' => 200.00,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Empréstimo',
                'created_at' => now()->subDays(3),
            ],

            // Compras em lojistas
            [
                'payer_id' => $joao->id,
                'payee_id' => $lojaAbc->id,
                'amount' => 250.00,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Compra de produtos',
                'created_at' => now()->subDays(2),
            ],
            [
                'payer_id' => $maria->id,
                'payee_id' => $superCentral->id,
                'amount' => 180.75,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Compras do mês',
                'created_at' => now()->subDays(2),
            ],
            [
                'payer_id' => $pedro->id,
                'payee_id' => $techStore->id,
                'amount' => 1500.00,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Notebook',
                'created_at' => now()->subDay(),
            ],
            [
                'payer_id' => $ana->id,
                'payee_id' => $restaurante->id,
                'amount' => 85.50,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Jantar',
                'created_at' => now()->subDay(),
            ],
            [
                'payer_id' => $carlos->id,
                'payee_id' => $lojaAbc->id,
                'amount' => 120.00,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Vestuário',
                'created_at' => now()->subHours(12),
            ],

            // Transferências mais recentes
            [
                'payer_id' => $joao->id,
                'payee_id' => $ana->id,
                'amount' => 75.00,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Presente',
                'created_at' => now()->subHours(6),
            ],
            [
                'payer_id' => $maria->id,
                'payee_id' => $lojaAbc->id,
                'amount' => 300.00,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Compra online',
                'created_at' => now()->subHours(3),
            ],

            // Algumas transações mais recentes
            [
                'payer_id' => $pedro->id,
                'payee_id' => $superCentral->id,
                'amount' => 95.20,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Compras da semana',
                'created_at' => now()->subHour(),
            ],
            [
                'payer_id' => $carlos->id,
                'payee_id' => $maria->id,
                'amount' => 40.00,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Pagamento de transporte',
                'created_at' => now()->subMinutes(30),
            ],

            // Uma transação pendente (simulação - normalmente não ficaria pendente)
            [
                'payer_id' => $ana->id,
                'payee_id' => $techStore->id,
                'amount' => 450.00,
                'status' => Transaction::STATUS_PENDING,
                'description' => 'Fone de ouvido',
                'created_at' => now()->subMinutes(5),
            ],
        ];

        // Criar transações
        foreach ($transactions as $transactionData) {
            Transaction::create($transactionData);
        }

        $this->command->info('✅ Criadas ' . count($transactions) . ' transações de exemplo');
        $this->command->info('📊 ' . Transaction::where('status', Transaction::STATUS_COMPLETED)->count() . ' completadas');
        $this->command->info('⏳ ' . Transaction::where('status', Transaction::STATUS_PENDING)->count() . ' pendentes');
    }
}
