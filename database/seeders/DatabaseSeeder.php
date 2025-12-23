<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Iniciando seed do banco de dados...');
        $this->command->newLine();

        // Ordem de execução dos seeders
        $this->call([
            UserSeeder::class,
            TransactionSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Database seeding concluído com sucesso!');
        $this->command->newLine();
        
        // Informações úteis
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📋 CREDENCIAIS DE ACESSO');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();
        
        $this->command->line('👤 <fg=cyan>USUÁRIOS COMUNS:</>');
        $this->command->line('   • joao@example.com     | Senha: password | Saldo: R$ 1.000,00');
        $this->command->line('   • maria@example.com    | Senha: password | Saldo: R$ 1.500,50');
        $this->command->line('   • pedro@example.com    | Senha: password | Saldo: R$ 500,00');
        $this->command->line('   • ana@example.com      | Senha: password | Saldo: R$ 2.500,00');
        $this->command->line('   • carlos@example.com   | Senha: password | Saldo: R$ 750,25');
        $this->command->newLine();
        
        $this->command->line('🏪 <fg=yellow>LOJISTAS (MERCHANTS):</>');
        $this->command->line('   • contato@lojaabc.com          | Senha: password | Saldo: R$ 5.000,00');
        $this->command->line('   • vendas@supercentral.com      | Senha: password | Saldo: R$ 15.000,00');
        $this->command->line('   • contato@techstore.com        | Senha: password | Saldo: R$ 8.500,50');
        $this->command->line('   • pedidos@saborarte.com        | Senha: password | Saldo: R$ 3.200,00');
        $this->command->line('   • atendimento@farmaciasaude.com| Senha: password | Saldo: R$ 6.800,75');
        $this->command->newLine();
        
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🚀 EXEMPLOS DE USO DA API');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();
        
        $this->command->line('<fg=green>Listar todos os usuários:</>');
        $this->command->line('GET http://localhost:8000/api/users');
        $this->command->newLine();
        
        $this->command->line('<fg=green>Ver usuário específico:</>');
        $this->command->line('GET http://localhost:8000/api/users/1');
        $this->command->newLine();
        
        $this->command->line('<fg=green>Fazer transferência:</>');
        $this->command->line('POST http://localhost:8000/api/transfer');
        $this->command->line('Body: {"value": 100, "payer": 1, "payee": 2}');
        $this->command->newLine();
        
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
