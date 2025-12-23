# 🔄 GitHub Actions - CI/CD

## 📋 Visão Geral

Este projeto utiliza GitHub Actions para automação de testes, análise de código e deploy. Todos os workflows estão na pasta `.github/workflows/`.

---

## 🚀 Workflows Disponíveis

### 1. **CI - Tests and Code Quality** (`ci.yml`)

**Trigger:**
- Push nas branches `main` e `develop`
- Pull Requests para `main` e `develop`

**Jobs:**

#### 📊 Tests
- **Matriz de PHP:** 8.2 e 8.3
- **Banco de Dados:** MySQL 8.0 (service container)
- **Executa:**
  - Instalação de dependências
  - Migrations
  - Testes com cobertura mínima de 80%
  - Upload de cobertura para Codecov

#### 🔍 Code Quality
- **PHP CS Fixer:** Verifica estilo de código
- **PHPStan:** Análise estática (nível 5)
- **PHP_CodeSniffer:** Verifica conformidade PSR-12

#### 🛡️ Security
- **Composer Audit:** Verifica vulnerabilidades em dependências
- **Security Checker:** Busca vulnerabilidades conhecidas

---

### 2. **Lint Pull Request** (`lint.yml`)

**Trigger:**
- Pull Requests (opened, synchronize, reopened)

**Executa:**
- PHP CS Fixer (dry-run)
- PHP_CodeSniffer (PSR-12)
- PHPStan (análise estática)
- Adiciona comentário no PR com resultado

**Objetivo:** Garantir qualidade do código antes do merge.

---

### 3. **Deploy to Production** (`deploy.yml`)

**Trigger:**
- Push na branch `main`
- Tags `v*` (ex: v1.0.0)

**Executa:**
- Testes completos
- Build otimizado (sem dev dependencies)
- Criação de artifact
- Deploy para servidor (configurável)
- Notificações

---

## 🛠️ Arquivos de Configuração

### `phpcs.xml`
Configuração do PHP_CodeSniffer:
- Padrão PSR-12
- Regras customizadas
- Limite de 120 caracteres por linha
- Complexidade ciclomática máxima: 10

### `.php-cs-fixer.php`
Configuração do PHP CS Fixer:
- Padrão PSR-12
- Array syntax curta
- Imports ordenados alfabeticamente
- Remoção de imports não usados

### `phpstan.neon`
Configuração do PHPStan:
- Nível 5 de análise
- Ignora arquivos gerados
- Configurações específicas do Laravel

---

## 📦 Badges para README

Adicione ao seu README.md:

```markdown
![CI](https://github.com/seu-usuario/simple-payment/workflows/CI%20-%20Tests%20and%20Code%20Quality/badge.svg)
![Lint](https://github.com/seu-usuario/simple-payment/workflows/Lint%20Pull%20Request/badge.svg)
[![codecov](https://codecov.io/gh/seu-usuario/simple-payment/branch/main/graph/badge.svg)](https://codecov.io/gh/seu-usuario/simple-payment)
```

---

## 🔧 Configuração Inicial

### 1. Secrets do GitHub

Configure os seguintes secrets no repositório:

**Para Deploy (opcional):**
```
SSH_PRIVATE_KEY
SERVER_HOST
SERVER_USER
DEPLOY_PATH
```

**Para Codecov (opcional):**
```
CODECOV_TOKEN
```

**Para Notificações (opcional):**
```
SLACK_WEBHOOK
DISCORD_WEBHOOK
```

---

### 2. Instalar Ferramentas Localmente

Adicione ao `composer.json`:

```json
{
  "require-dev": {
    "friendsofphp/php-cs-fixer": "^3.40",
    "phpstan/phpstan": "^1.10",
    "squizlabs/php_codesniffer": "^3.7",
    "enlightn/security-checker": "^1.10"
  }
}
```

Instale:
```bash
composer install --dev
```

---

## 🖥️ Executar Localmente

### Testes
```bash
php artisan test
php artisan test --coverage
php artisan test --coverage --min=80
```

### Linter (PHP CS Fixer)
```bash
# Verificar (dry-run)
vendor/bin/php-cs-fixer fix --dry-run --diff

# Corrigir automaticamente
vendor/bin/php-cs-fixer fix
```

### Code Style (PHP_CodeSniffer)
```bash
# Verificar
vendor/bin/phpcs

# Com mais detalhes
vendor/bin/phpcs app --standard=PSR12 --colors -p

# Corrigir automaticamente
vendor/bin/phpcbf
```

### Análise Estática (PHPStan)
```bash
vendor/bin/phpstan analyse

# Com nível específico
vendor/bin/phpstan analyse --level=5

# Com formato
vendor/bin/phpstan analyse --error-format=table
```

### Security Check
```bash
# Composer audit
composer audit

# Security checker
vendor/bin/security-checker security:check
```

---

## 📊 Interpretando Resultados

### ✅ Sucesso
```
✓ All tests passed
✓ No style issues found
✓ No security vulnerabilities
```

### ❌ Falhas Comuns

**Testes falhando:**
```bash
# Verifique o erro específico
php artisan test --stop-on-failure
```

**Estilo de código:**
```bash
# Corrigir automaticamente
vendor/bin/php-cs-fixer fix
vendor/bin/phpcbf
```

**PHPStan:**
```bash
# Adicionar exceções no phpstan.neon se necessário
# Ou corrigir os tipos/documentação
```

---

## 🎯 Melhores Práticas

### Antes de Fazer Push
```bash
# Executar localmente
composer test       # Testes
composer lint       # Linter
composer analyze    # Análise estática
```

Adicione ao `composer.json`:
```json
{
  "scripts": {
    "test": "php artisan test --coverage --min=80",
    "lint": "php-cs-fixer fix --dry-run --diff",
    "lint:fix": "php-cs-fixer fix",
    "analyze": "phpstan analyse --level=5",
    "cs": "phpcs app --standard=PSR12",
    "cs:fix": "phpcbf app --standard=PSR12",
    "security": "composer audit",
    "quality": [
      "@test",
      "@lint",
      "@analyze",
      "@cs",
      "@security"
    ]
  }
}
```

Agora você pode executar:
```bash
composer quality  # Executa todos os checks
```

---

## 🔄 Fluxo de Trabalho Recomendado

### 1. Criar Feature Branch
```bash
git checkout -b feature/nova-funcionalidade
```

### 2. Desenvolver e Testar Localmente
```bash
# Durante desenvolvimento
php artisan test

# Antes de commit
composer quality
```

### 3. Commit e Push
```bash
git add .
git commit -m "feat: adiciona nova funcionalidade"
git push origin feature/nova-funcionalidade
```

### 4. Criar Pull Request
- GitHub Actions executará automaticamente
- Aguarde todos os checks passarem ✅
- Solicite review

### 5. Merge
- Após aprovação e checks OK
- Merge para `develop` ou `main`
- Deploy automático será executado (se configurado)

---

## 🐛 Troubleshooting

### Workflow não executou
- Verifique se o arquivo YAML está correto
- Confirme que está na pasta `.github/workflows/`
- Verifique as branches configuradas no trigger

### Testes passam local mas falham no CI
- Diferenças de versão PHP
- Banco de dados não configurado corretamente
- Variáveis de ambiente faltando

### Cache não funcionando
```bash
# Limpar cache do GitHub
# Settings > Actions > Caches > Delete all caches
```

### PHPStan muito rigoroso
```yaml
# Ajustar nível no phpstan.neon
parameters:
    level: 3  # Diminuir de 5 para 3
```

---

## 📈 Métricas e Monitoramento

### Codecov
- Cobertura de código em tempo real
- Visualização de arquivos não cobertos
- Histórico de cobertura

### GitHub Actions Dashboard
- Tempo de execução dos workflows
- Taxa de sucesso/falha
- Logs detalhados

### Recomendações
- **Cobertura mínima:** 80%
- **Tempo de CI:** < 5 minutos
- **Zero vulnerabilidades** em produção

---

## 🚀 Próximos Passos

### Melhorias Futuras
- [ ] Adicionar testes de mutação (Infection PHP)
- [ ] Integrar SonarQube
- [ ] Adicionar testes E2E
- [ ] Deploy automático para staging
- [ ] Notificações Slack/Discord
- [ ] Análise de performance

### Ferramentas Adicionais
- **Rector:** Refatoração automática
- **PHPMetrics:** Métricas de código
- **PHPMD:** Mess Detector
- **Deptrac:** Análise de dependências

---

## 📚 Recursos

- [GitHub Actions Docs](https://docs.github.com/en/actions)
- [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)
- [PHPStan](https://phpstan.org/user-guide/getting-started)
- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [Codecov](https://docs.codecov.com/)

---

**Última atualização:** Dezembro 2024  
**Versão:** 1.0.0
