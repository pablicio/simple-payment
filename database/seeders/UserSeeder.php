<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpar usuários existentes (apenas em desenvolvimento)
        User::truncate();

        // Usuários comuns (com saldo)
        $commonUsers = [
            [
                'name' => 'João Silva',
                'email' => 'joao@example.com',
                'document' => '12345678901',
                'password' => Hash::make('password'),
                'type' => User::TYPE_COMMON,
                'balance' => 1000.00,
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria@example.com',
                'document' => '98765432109',
                'password' => Hash::make('password'),
                'type' => User::TYPE_COMMON,
                'balance' => 1500.50,
            ],
            [
                'name' => 'Pedro Oliveira',
                'email' => 'pedro@example.com',
                'document' => '45678912345',
                'password' => Hash::make('password'),
                'type' => User::TYPE_COMMON,
                'balance' => 500.00,
            ],
            [
                'name' => 'Ana Costa',
                'email' => 'ana@example.com',
                'document' => '78945612378',
                'password' => Hash::make('password'),
                'type' => User::TYPE_COMMON,
                'balance' => 2500.00,
            ],
            [
                'name' => 'Carlos Ferreira',
                'email' => 'carlos@example.com',
                'document' => '32165498732',
                'password' => Hash::make('password'),
                'type' => User::TYPE_COMMON,
                'balance' => 750.25,
            ],
        ];

        // Lojistas (merchants)
        $merchants = [
            [
                'name' => 'Loja ABC Ltda',
                'email' => 'contato@lojaabc.com',
                'document' => '12345678000199',
                'password' => Hash::make('password'),
                'type' => User::TYPE_MERCHANT,
                'balance' => 5000.00,
            ],
            [
                'name' => 'Supermercado Central',
                'email' => 'vendas@supercentral.com',
                'document' => '98765432000188',
                'password' => Hash::make('password'),
                'type' => User::TYPE_MERCHANT,
                'balance' => 15000.00,
            ],
            [
                'name' => 'Tech Store',
                'email' => 'contato@techstore.com',
                'document' => '45678912000177',
                'password' => Hash::make('password'),
                'type' => User::TYPE_MERCHANT,
                'balance' => 8500.50,
            ],
            [
                'name' => 'Restaurante Sabor & Arte',
                'email' => 'pedidos@saborarte.com',
                'document' => '78945612000166',
                'password' => Hash::make('password'),
                'type' => User::TYPE_MERCHANT,
                'balance' => 3200.00,
            ],
            [
                'name' => 'Farmácia Saúde',
                'email' => 'atendimento@farmaciasaude.com',
                'document' => '32165498000155',
                'password' => Hash::make('password'),
                'type' => User::TYPE_MERCHANT,
                'balance' => 6800.75,
            ],
        ];

        // Criar usuários comuns
        foreach ($commonUsers as $userData) {
            User::create($userData);
        }

        // Criar lojistas
        foreach ($merchants as $merchantData) {
            User::create($merchantData);
        }

        $this->command->info('✅ Criados 5 usuários comuns e 5 lojistas');
        $this->command->info('📧 Email: joao@example.com | Senha: password');
        $this->command->info('📧 Email: contato@lojaabc.com | Senha: password');
    }
}
