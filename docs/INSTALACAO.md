# 📖 Guia de Instalação e Uso

## 🔧 Requisitos do Sistema

- PHP >= 8.2
- Composer
- MySQL >= 8.0 ou MariaDB >= 10.3
- Node.js >= 18.x (opcional, para assets)
- Git

---

## 🚀 Instalação

### 1. Clonar o Repositório
```bash
git clone <url-do-repositorio>
cd simple-payment
```

### 2. Instalar Dependências
```bash
composer install
```

### 3. Configurar Ambiente
```bash
# Copiar arquivo de configuração
cp .env.example .env

# Gerar chave da aplicação
php artisan key:generate
```

### 4. Configurar Banco de Dados

Edite o arquivo `.env` com suas credenciais:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=payment_simplificado
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

### 5. Criar Banco de Dados
```bash
# MySQL
mysql -u root -p
CREATE DATABASE payment_simplificado;
exit;
```

### 6. Executar Migrations
```bash
php artisan migrate
```

### 7. (Opcional) Popular com Dados de Teste
```bash
php artisan db:seed
```

### 8. Iniciar o Servidor
```bash
php artisan serve
```

A aplicação estará disponível em: `http://localhost:8000`

---

## 🐳 Instalação com Docker (Alternativa)

### 1. Criar arquivo docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: payment-app
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
    environment:
      - DB_HOST=db
      - DB_DATABASE=payment_simplificado
      - DB_USERNAME=root
      - DB_PASSWORD=secret

  db:
    image: mysql:8.0
    container_name: payment-db
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: payment_simplificado
    volumes:
      - dbdata:/var/lib/mysql

volumes:
  dbdata:
```

### 2. Criar Dockerfile
```dockerfile
FROM php:8.2-fpm

# Instalar dependências
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Instalar extensões PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos
COPY . .

# Instalar dependências
RUN composer install

# Permissões
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
```

### 3. Executar
```bash
docker-compose up -d
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

---

## 📚 Uso da API

### Criar Usuário Comum

```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@email.com",
    "document": "12345678900",
    "password": "senha123",
    "type": "common",
    "balance": 1000.00
  }'
```

**Response:**
```json
{
  "message": "User created successfully",
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@email.com",
    "document": "12345678900",
    "type": "common",
    "balance": "1000.00"
  }
}
```

---

### Criar Lojista

```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Loja do José",
    "email": "loja@email.com",
    "document": "12345678000199",
    "password": "senha123",
    "type": "merchant",
    "balance": 0
  }'
```

---

### Listar Todos os Usuários

```bash
curl -X GET http://localhost:8000/api/users
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "João Silva",
      "email": "joao@email.com",
      "document": "12345678900",
      "type": "common",
      "balance": "1000.00"
    },
    {
      "id": 2,
      "name": "Loja do José",
      "email": "loja@email.com",
      "document": "12345678000199",
      "type": "merchant",
      "balance": "0.00"
    }
  ]
}
```

---

### Consultar Usuário Específico

```bash
curl -X GET http://localhost:8000/api/users/1
```

---

### Realizar Transferência

```bash
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 100.00,
    "payer": 1,
    "payee": 2
  }'
```

**Response (Sucesso):**
```json
{
  "message": "Transfer completed successfully",
  "data": {
    "transaction_id": 1,
    "payer": {
      "id": 1,
      "name": "João Silva",
      "balance": "900.00"
    },
    "payee": {
      "id": 2,
      "name": "Loja do José",
      "balance": "100.00"
    },
    "amount": "100.00",
    "status": "completed",
    "created_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**Response (Erro - Saldo Insuficiente):**
```json
{
  "message": "Insufficient balance"
}
```

**Response (Erro - Lojista não pode enviar):**
```json
{
  "message": "Merchants cannot send transfers"
}
```

---

### Atualizar Usuário

```bash
curl -X PUT http://localhost:8000/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva Atualizado",
    "balance": 2000.00
  }'
```

---

### Deletar Usuário

```bash
curl -X DELETE http://localhost:8000/api/users/1
```

---

## 🧪 Executar Testes

### Todos os Testes
```bash
php artisan test
```

### Testes Específicos
```bash
# Testes de Feature
php artisan test --testsuite=Feature

# Testes Unitários
php artisan test --testsuite=Unit

# Teste específico
php artisan test tests/Feature/TransferTest.php
```

### Com Cobertura de Código
```bash
php artisan test --coverage
```

---

## 🛠️ Comandos Úteis

### Limpar Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Ver Rotas
```bash
php artisan route:list
```

### Criar Migration
```bash
php artisan make:migration create_nome_tabela
```

### Criar Model
```bash
php artisan make:model NomeModel -m
```

### Criar Controller
```bash
php artisan make:controller NomeController
```

### Criar Service
```bash
php artisan make:service NomeService
```

### Rollback de Migrations
```bash
# Última migration
php artisan migrate:rollback

# Todas as migrations
php artisan migrate:reset

# Reset + Migrate
php artisan migrate:fresh

# Fresh + Seed
php artisan migrate:fresh --seed
```

---

## 📊 Monitoramento e Logs

### Ver Logs
```bash
# Logs em tempo real
tail -f storage/logs/laravel.log

# Últimas 100 linhas
tail -n 100 storage/logs/laravel.log
```

### Limpar Logs
```bash
> storage/logs/laravel.log
```

---

## 🐛 Troubleshooting

### Erro: "Storage not writable"
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Erro: "Class not found"
```bash
composer dump-autoload
```

### Erro: "SQLSTATE[HY000] [2002] Connection refused"
Verifique se o MySQL está rodando:
```bash
sudo service mysql status
sudo service mysql start
```

### Erro: "419 Page Expired" (CSRF)
Para APIs, desabilite CSRF nas rotas API (já configurado por padrão).

---

## 🔐 Segurança em Produção

### 1. Configurar .env
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seudominio.com
```

### 2. Otimizar
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### 3. HTTPS
Configure SSL/TLS no seu servidor web (Nginx/Apache).

### 4. Rate Limiting
Já configurado nas rotas API (60 requests/minuto).

### 5. Backup Database
```bash
mysqldump -u root -p payment_simplificado > backup.sql
```

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique os logs em `storage/logs/laravel.log`
2. Consulte a documentação do Laravel: https://laravel.com/docs
3. Abra uma issue no repositório

---

## 📝 Notas Adicionais

- A API não requer autenticação (conforme especificação do desafio)
- Serviços externos podem estar indisponíveis (são mocks)
- Em produção, considere usar filas para notificações
- Implemente cache para melhor performance
- Configure monitoramento (New Relic, Datadog, etc)
