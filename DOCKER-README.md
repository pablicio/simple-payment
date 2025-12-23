# 🚀 Payment System - Docker Setup

Sistema de pagamentos simplificado com Docker.

## 📋 Pré-requisitos

- Docker Desktop instalado e rodando
- Portas 8000 (web) e 3306 (mysql) disponíveis

## ⚡ Instalação Rápida

```batch
# Instalação completa (primeira vez)
install.bat
```

Isso vai:
1. Limpar containers antigos
2. Construir as imagens
3. Subir os containers
4. Aguardar o MySQL
5. Executar migrations e seeders
6. Verificar instalação

## 🎮 Gerenciamento

Use o arquivo `manage.bat` para controlar o sistema:

```batch
# Iniciar
manage.bat start

# Parar
manage.bat stop

# Ver logs
manage.bat logs

# Ver status
manage.bat status

# Recriar banco de dados
manage.bat fresh

# Abrir shell
manage.bat shell
```

## 🔧 Comandos Úteis

### Acessar o container:
```batch
docker-compose exec app bash
```

### Ver logs:
```batch
docker-compose logs -f app
```

### Executar Artisan:
```batch
docker-compose exec app php artisan [comando]
```

### Recriar tudo do zero:
```batch
manage.bat clean
install.bat
```

## 📊 Estrutura

- **App (PHP-FPM)**: Aplicação Laravel na porta 9000
- **Nginx**: Servidor web na porta 8000
- **MySQL**: Banco de dados na porta 3306

## 🌐 Acessos

- **Aplicação**: http://localhost:8000
- **MySQL**: 
  - Host: localhost:3306
  - Database: payment_simplificado
  - User: root
  - Password: secret

## 🐛 Problemas Comuns

### Migrations não executam
```batch
docker-compose exec app php artisan migrate:fresh --seed --force
```

### Erro de permissão
```batch
docker-compose exec app chmod -R 777 storage bootstrap/cache
```

### Container não inicia
```batch
docker-compose logs app
docker-compose down -v
install.bat
```

### Porta 8000 em uso
Mude no `docker-compose.yml`:
```yaml
ports:
  - "8080:80"  # Usa porta 8080
```

## 📁 Estrutura de Arquivos

```
simple-payment/
├── docker/
│   ├── nginx/
│   │   └── default.conf    # Config Nginx
│   └── entrypoint.sh        # Script de inicialização
├── Dockerfile               # Build do container PHP
├── docker-compose.yml       # Orquestração
├── install.bat             # Instalação completa
└── manage.bat              # Gerenciamento
```

## 🔄 Workflow de Desenvolvimento

1. **Iniciar**: `manage.bat start`
2. **Desenvolver**: Edite os arquivos normalmente
3. **Ver logs**: `manage.bat logs`
4. **Testar migrations**: `manage.bat migrate`
5. **Resetar dados**: `manage.bat fresh`

## ✅ Verificação

Após instalação, verifique:

```batch
# Status dos containers
docker-compose ps

# Tabelas criadas
docker-compose exec app php artisan db:show

# Acesse no navegador
start http://localhost:8000
```

## 🆘 Suporte

Se algo der errado:

1. Veja os logs: `manage.bat logs`
2. Limpe tudo: `manage.bat clean`
3. Reinstale: `install.bat`
