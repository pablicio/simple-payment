#!/bin/bash
set -e

echo "🚀 Iniciando aplicação Payment..."

# Aguardar MySQL estar pronto
echo "⏳ Aguardando MySQL..."
MAX_TRIES=30
COUNT=0

while ! php artisan db:show &>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "❌ MySQL não respondeu após $MAX_TRIES tentativas"
        exit 1
    fi
    echo "Tentativa $COUNT/$MAX_TRIES..."
    sleep 2
done

echo "✅ MySQL conectado!"

# Executar migrations
echo "🗄️ Executando migrations..."
php artisan migrate --force

# Verificar se precisa executar seeders
if php artisan db:table users --count 2>/dev/null | grep -q "^0$"; then
    echo "📦 Banco vazio, executando seeders..."
    php artisan db:seed --force
else
    echo "📦 Banco já possui dados, pulando seeders..."
fi

# Cache de configuração
echo "⚡ Otimizando aplicação..."
php artisan config:cache
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

echo "✨ Aplicação pronta!"
echo "📍 Acesse: http://localhost:8000"
echo ""

# Executar comando
exec "$@"
