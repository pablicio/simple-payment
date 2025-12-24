# 🐳 Docker - Guia Completo

## 📋 O que foi configurado

- ✅ **PHP 8.3-FPM** - Aplicação Laravel
- ✅ **Nginx** - Servidor web
- ✅ **MySQL 8.0** - Banco de dados
- ✅ **Volumes** - Persistência de dados
- ✅ **Network** - Comunicação entre containers
- ✅ **Auto-setup** - Configuração automática

---

## 🚀 Início Rápido

### 1. Construir e Iniciar
```bash
docker-compose up -d
```

### 2. Acessar a Aplicação
```
http://localhost:8000
```

### 3. Parar os Containers
```bash
docker-compose down
```

---

## 📦 Estrutura dos Containers

```
┌─────────────────────────────────────┐
│         Payment-nginx (8000)         │
│           Servidor Web              │
└─────────────┬───────────────────────┘
              │
┌─────────────▼───────────────────────┐
│         Payment-app (9000)           │
│        PHP 8.3 + Laravel            │
└─────────────┬───────────────────────┘
              │
┌─────────────▼───────────────────────┐
│         Payment-db (3306)            │
│          MySQL 8.0                  │
└─────────────────────────────────────┘
```

---

## 🎯 Comandos Principais

### Iniciar Containers
```bash
# Primeiro build (ou quando mudar Dockerfile)
docker-compose up -d --build

# Iniciar normalmente
docker-compose up -d

# Ver logs
docker-compose logs -f

# Ver logs de um serviço específico
docker-compose logs -f app
```

### Parar/Remover
```bash
# Parar containers
docker-compose stop

# Parar e remover containers
docker-compose down

# Remover tudo (incluindo volumes)
docker-compose down -v
```

### Executar Comandos
```bash
# Entrar no container da aplicação
docker-compose exec app bash

# Executar comandos Artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan test
docker-compose exec app php artisan tinker

# Instalar dependências
docker-compose exec app composer install

# Limpar cache
docker-compose exec app php artisan cache:clear
```

### Verificar Status
```bash
# Ver containers rodando
docker-compose ps

# Ver logs
docker-compose logs

# Ver recursos usados
docker stats
```

---

## 🔧 Configuração

### Variáveis de Ambiente

O arquivo `.env` é criado automaticamente na primeira execução. Principais variáveis:

```env
APP_NAME=Payment-Simplificado
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=ayment_simplificado
DB_USERNAME=root
DB_PASSWORD=secret
```

### Portas

| Serviço | Porta Host | Porta Container |
|---------|------------|-----------------|
| Nginx   | 8000       | 80              |
| MySQL   | 3306       | 3306            |
| PHP-FPM | -          | 9000            |

Para mudar a porta do Nginx, edite `docker-compose.yml`:
```yaml
nginx:
  ports:
    - "8080:80"  # Muda para porta 8080
```

---

## 📁 Estrutura de Arquivos Docker

```
simple-payment/
├── docker/
│   ├── nginx/
│   │   └── default.conf      # Config Nginx
│   ├── php/
│   │   └── local.ini          # Config PHP
│   └── entrypoint.sh          # Script de inicialização
├── docker-compose.yml         # Orquestração
├── Dockerfile                 # Build da aplicação
└── .dockerignore             # Arquivos ignorados
```

---

## 🎓 Uso Diário

### Setup Inicial (Primeira Vez)
```bash
# 1. Construir e iniciar
docker-compose up -d --build

# 2. Verificar logs
docker-compose logs -f app

# 3. Aguardar mensagem "Aplicação iniciada com sucesso!"

# 4. Acessar
curl http://localhost:8000/api/users
```

### Desenvolvimento
```bash
# Fazer alterações no código (o volume sincroniza automaticamente)

# Limpar cache se necessário
docker-compose exec app php artisan cache:clear

# Executar testes
docker-compose exec app php artisan test

# Ver logs em tempo real
docker-compose logs -f
```

### Banco de Dados
```bash
# Executar migrations
docker-compose exec app php artisan migrate

# Rollback
docker-compose exec app php artisan migrate:rollback

# Seed (popular dados)
docker-compose exec app php artisan db:seed

# Fresh (resetar tudo)
docker-compose exec app php artisan migrate:fresh --seed

# Conectar ao MySQL
docker-compose exec db mysql -uroot -psecret ayment_simplificado
```

---

## 🧪 Testes

```bash
# Todos os testes
docker-compose exec app php artisan test

# Com cobertura
docker-compose exec app php artisan test --coverage

# Teste específico
docker-compose exec app php artisan test --filter=TransferTest

# Criar teste
docker-compose exec app php artisan make:test NomeTest
```

---

## 🐛 Troubleshooting

### Container não inicia
```bash
# Ver logs de erro
docker-compose logs app

# Verificar status
docker-compose ps

# Rebuild completo
docker-compose down
docker-compose up -d --build
```

### Erro de permissão
```bash
# Ajustar permissões
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### MySQL não conecta
```bash
# Verificar se MySQL está rodando
docker-compose ps db

# Ver logs do MySQL
docker-compose logs db

# Aguardar alguns segundos (MySQL pode demorar para iniciar)
```

### Cache de configuração
```bash
# Limpar todos os caches
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Porta já em uso
```bash
# Verificar o que está usando a porta
# Windows
netstat -ano | findstr :8000

# Linux/macOS
lsof -i :8000

# Matar processo ou mudar porta no docker-compose.yml
```

### Recomeçar do zero
```bash
# Remover tudo
docker-compose down -v

# Limpar imagens
docker system prune -a

# Reconstruir
docker-compose up -d --build
```

---

## 🚀 Produção

### Build Otimizado
```bash
# Usar flag de produção
docker-compose -f docker-compose.prod.yml up -d
```

Crie `docker-compose.prod.yml`:
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    volumes:
      - ./:/var/www
    command: php artisan optimize && php-fpm

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/production.conf:/etc/nginx/conf.d/default.conf

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
    volumes:
      - dbdata:/var/lib/mysql

volumes:
  dbdata:
```

### Otimizações
```bash
# Dentro do container
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app composer install --optimize-autoloader --no-dev
```

---

## 📊 Monitoramento

### Ver Recursos
```bash
# CPU, Memória, Rede
docker stats

# Uso de disco
docker system df

# Logs em tempo real
docker-compose logs -f --tail=100
```

### Health Check
```bash
# Verificar se está respondendo
curl http://localhost:8000/api/users

# Ver status dos containers
docker-compose ps
```

---

## 🎁 Comandos Úteis

### Composer
```bash
docker-compose exec app composer install
docker-compose exec app composer update
docker-compose exec app composer require nome/pacote
```

### Artisan
```bash
docker-compose exec app php artisan list
docker-compose exec app php artisan make:controller Nome
docker-compose exec app php artisan make:model Nome
docker-compose exec app php artisan make:migration nome
```

### MySQL CLI
```bash
# Conectar
docker-compose exec db mysql -uroot -psecret

# Backup
docker-compose exec db mysqldump -uroot -psecret ayment_simplificado > backup.sql

# Restore
docker-compose exec -T db mysql -uroot -psecret ayment_simplificado < backup.sql
```

### Limpar Tudo
```bash
# Parar e remover containers
docker-compose down

# Remover volumes
docker volume prune

# Remover imagens não usadas
docker image prune -a

# Limpar sistema completo
docker system prune -a --volumes
```

---

## 📝 Aliases Úteis (Opcional)

Adicione ao seu `.bashrc` ou `.zshrc`:

```bash
# Docker Compose
alias dc='docker-compose'
alias dcu='docker-compose up -d'
alias dcd='docker-compose down'
alias dcl='docker-compose logs -f'

# App Container
alias dce='docker-compose exec app'
alias artisan='docker-compose exec app php artisan'
alias composer='docker-compose exec app composer'
alias phpunit='docker-compose exec app php artisan test'

# Database
alias mysql='docker-compose exec db mysql -uroot -psecret ayment_simplificado'
```

Uso:
```bash
artisan migrate
composer install
phpunit --filter=TransferTest
```

---

## 🎯 Checklist de Setup

- [ ] Docker e Docker Compose instalados
- [ ] Executou `docker-compose up -d --build`
- [ ] Aguardou mensagem de sucesso nos logs
- [ ] Testou `http://localhost:8000`
- [ ] Executou migrations
- [ ] Rodou testes
- [ ] Verificou logs sem erros

---

## 🌟 Vantagens do Docker

✅ **Ambiente Idêntico** - Dev, teste e produção iguais  
✅ **Setup Rápido** - Um comando e está rodando  
✅ **Isolamento** - Não conflita com outras apps  
✅ **Portabilidade** - Roda em qualquer lugar  
✅ **Reproduzível** - Sempre o mesmo resultado  
✅ **Fácil Reset** - `docker-compose down -v` e recomeça  

---

**Pronto para usar! 🚀**

Execute: `docker-compose up -d` e acesse `http://localhost:8000`
