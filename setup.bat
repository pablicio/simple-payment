@echo off
echo ========================================
echo   PAYMENT - Setup Completo
echo ========================================
echo.

echo [1/5] Parando containers antigos...
docker-compose down -v

echo.
echo [2/5] Subindo containers...
docker-compose up -d --build

echo.
echo [3/5] Aguardando MySQL (30 segundos)...
timeout /t 30 /nobreak

echo.
echo [4/5] Executando migrations...
docker-compose exec app php artisan migrate --force

echo.
echo [5/5] Executando seeders (se existirem)...
docker-compose exec app php artisan db:seed --force

echo.
echo ========================================
echo   Status dos Containers:
echo ========================================
docker-compose ps

echo.
echo ========================================
echo   Tabelas no Banco:
echo ========================================
docker-compose exec app php artisan db:show

echo.
echo ✅ Setup completo!
echo 📍 Acesse: http://localhost:8000
echo.
pause
