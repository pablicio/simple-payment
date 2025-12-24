#!/bin/bash

# Script para remover modelos não utilizados
# Data: 2025-12-23
# Motivo: Simplificação da arquitetura - uso de User.type

echo "🗑️  Removendo modelos e migrations não utilizados..."

# Verificar se os arquivos existem antes de remover
files_to_remove=(
    "app/Models/Shopkeeper.php"
    "app/Models/Wallet.php"
    "database/migrations/2025_12_23_193713_create_shopkeepers_table.php"
    "database/migrations/2025_12_23_193723_create_wallets_table.php"
)

for file in "${files_to_remove[@]}"; do
    if [ -f "$file" ]; then
        echo "✓ Removendo: $file"
        rm "$file"
    else
        echo "⚠ Arquivo não encontrado: $file"
    fi
done

echo ""
echo "✅ Remoção concluída!"
echo ""
echo "📝 Próximos passos:"
echo "1. Verificar se os testes ainda passam: php artisan test"
echo "2. Commit as mudanças: git add . && git commit -m 'refactor: remove unused Shopkeeper and Wallet models'"
echo ""
echo "💾 Backup dos arquivos removidos está em: docs/removed_models_backup.md"
