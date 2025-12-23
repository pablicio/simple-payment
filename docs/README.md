# 📋 Índice da Documentação - Payment Simplificado

Bem-vindo à documentação do Payment Simplificado! Esta é uma API RESTful para gerenciar transferências de dinheiro entre usuários e lojistas.

---

## 📚 Documentos Disponíveis

### 1. [API.md](./API.md)
**Documentação Completa da API**

Contém toda a especificação dos endpoints, incluindo:
- Controllers e suas responsabilidades
- Todos os métodos disponíveis
- Request/Response de cada endpoint
- Services e lógica de negócio
- Models e relacionamentos
- Exemplos práticos de uso

**Ideal para:** Desenvolvedores que vão consumir ou manter a API.

---

### 2. [ARQUITETURA.md](./ARQUITETURA.md)
**Arquitetura e Design do Sistema**

Explica a estrutura técnica do projeto:
- Visão geral da arquitetura em camadas
- Padrões de design utilizados
- Fluxo detalhado de uma transferência
- Estrutura do banco de dados
- Práticas de segurança
- Estratégias de escalabilidade
- Decisões arquiteturais

**Ideal para:** Arquitetos, tech leads e desenvolvedores que querem entender o design do sistema.

---

### 3. [INSTALACAO.md](./INSTALACAO.md)
**Guia de Instalação e Uso**

Tutorial completo para configurar o projeto:
- Requisitos do sistema
- Passo a passo da instalação
- Configuração com Docker
- Como usar a API (exemplos práticos)
- Comandos úteis
- Troubleshooting
- Configuração para produção

**Ideal para:** Desenvolvedores configurando o ambiente pela primeira vez.

---

## 🚀 Início Rápido

### Para Desenvolvedores

1. **Configure o ambiente:**
   ```bash
   git clone <repositorio>
   cd simple-payment
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Configure o banco de dados no `.env`**

3. **Execute as migrations:**
   ```bash
   php artisan migrate
   ```

4. **Inicie o servidor:**
   ```bash
   php artisan serve
   ```

5. **Teste a API:**
   ```bash
   curl http://localhost:8000/api/users
   ```

**📖 Mais detalhes:** [INSTALACAO.md](./INSTALACAO.md)

---

### Para Quem Vai Consumir a API

**Endpoints Principais:**

- `POST /api/users` - Criar usuário
- `GET /api/users` - Listar usuários
- `POST /api/transfer` - Realizar transferência

**Exemplo de Transferência:**
```bash
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 100.00,
    "payer": 1,
    "payee": 2
  }'
```

**📖 Documentação completa:** [API.md](./API.md)

---

## 🎯 Funcionalidades Principais

### ✅ Gestão de Usuários
- Criar usuários comuns e lojistas
- Listar todos os usuários
- Consultar usuário específico
- Atualizar dados do usuário
- Deletar usuário

### ✅ Transferências
- Transferência entre usuários
- Validação de saldo
- Autorização externa
- Transações atômicas (rollback automático em caso de erro)
- Notificação ao recebedor

### ✅ Regras de Negócio
- Apenas usuários comuns podem enviar dinheiro
- Lojistas apenas recebem
- CPF/CNPJ e email devem ser únicos
- Validação de saldo antes da transferência
- Integração com serviço autorizador externo
- Sistema de notificações (não bloqueante)

---

## 🏗️ Tecnologias

- **Framework:** Laravel 11.x
- **Linguagem:** PHP 8.2+
- **Banco de Dados:** MySQL 8.0
- **Padrões:** RESTful API, Service Layer, Repository (Eloquent)
- **Segurança:** Database Transactions, Lock Pessimista, Validação em Camadas

---

## 📊 Estrutura do Projeto

```
simple-payment/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── TransferController.php
│   │       └── UserController.php
│   ├── Models/
│   │   ├── User.php
│   │   └── Transaction.php
│   └── Services/
│       └── TransferService.php
├── database/
│   └── migrations/
├── routes/
│   └── api.php
├── tests/
│   ├── Feature/
│   └── Unit/
└── docs/
    ├── README.md (este arquivo)
    ├── API.md
    ├── ARQUITETURA.md
    └── INSTALACAO.md
```

---

## 🔍 Navegação por Perfil

### 👨‍💻 Desenvolvedor Backend
Comece por:
1. [INSTALACAO.md](./INSTALACAO.md) - Configure o ambiente
2. [ARQUITETURA.md](./ARQUITETURA.md) - Entenda a estrutura
3. [API.md](./API.md) - Conheça os endpoints

### 🏛️ Arquiteto de Software
Foque em:
1. [ARQUITETURA.md](./ARQUITETURA.md) - Padrões e design
2. [API.md](./API.md) - Detalhes técnicos dos services

### 📱 Desenvolvedor Frontend/Mobile
Comece por:
1. [API.md](./API.md) - Endpoints e contratos
2. [INSTALACAO.md](./INSTALACAO.md) - Como rodar localmente

### 🧪 QA/Tester
Foque em:
1. [INSTALACAO.md](./INSTALACAO.md) - Como configurar
2. [API.md](./API.md) - Exemplos de uso e casos de erro

---

## 🎓 Conceitos Importantes

### Tipos de Usuário
- **Common (Comum):** Pode enviar e receber dinheiro
- **Merchant (Lojista):** Pode apenas receber dinheiro

### Status de Transação
- **Pending:** Transação iniciada
- **Completed:** Transação concluída com sucesso
- **Failed:** Transação falhou

### Fluxo de Transferência
1. Validação de dados
2. Lock dos usuários (evita race condition)
3. Validação de regras de negócio
4. Consulta ao autorizador externo
5. Criação da transação
6. Atualização de saldos
7. Conclusão da transação
8. Notificação (não bloqueante)

---

## 🛡️ Segurança

### Implementado
- ✅ Transações database (atomicidade)
- ✅ Lock pessimista (concorrência)
- ✅ Validação em múltiplas camadas
- ✅ Senhas com hash (bcrypt)
- ✅ Proteção de dados sensíveis
- ✅ Rate limiting (60 req/min)

### Recomendado para Produção
- 🔲 Autenticação (JWT/Sanctum)
- 🔲 Autorização (Policies)
- 🔲 Logs de auditoria
- 🔲 Monitoramento (New Relic, Datadog)
- 🔲 HTTPS obrigatório
- 🔲 Backup automatizado

---

## 🧪 Testes

### Executar Testes
```bash
# Todos os testes
php artisan test

# Com cobertura
php artisan test --coverage
```

### Tipos de Teste
- **Unit:** Testes de services e models
- **Feature:** Testes de endpoints
- **Integration:** Testes de fluxo completo

**📖 Mais detalhes:** [INSTALACAO.md](./INSTALACAO.md#-executar-testes)

---

## 📈 Melhorias Futuras

### Alta Prioridade
- [ ] Implementar filas (Laravel Queue) para notificações
- [ ] Adicionar autenticação
- [ ] Implementar logs de auditoria
- [ ] Adicionar cache para consultas frequentes

### Média Prioridade
- [ ] Implementar Event Sourcing
- [ ] Adicionar métricas e observabilidade
- [ ] Criar dashboard administrativo
- [ ] Implementar webhooks

### Baixa Prioridade
- [ ] Sistema de reembolso
- [ ] Transferência programada
- [ ] Relatórios avançados
- [ ] Suporte a múltiplas moedas

---

## 📞 Precisa de Ajuda?

### Problemas Comuns
1. **Erro de conexão com banco:** Verifique credenciais no `.env`
2. **Erro 500:** Verifique `storage/logs/laravel.log`
3. **Permissões:** Execute `chmod -R 775 storage bootstrap/cache`

### Recursos
- 📖 [Documentação Laravel](https://laravel.com/docs)
- 💬 [Stack Overflow](https://stackoverflow.com/questions/tagged/laravel)
- 🐛 Abra uma issue no repositório

---

## 📄 Licença

Este projeto foi desenvolvido como desafio técnico para o Payment.

---

## 👥 Contribuindo

1. Fork o projeto
2. Crie uma branch (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

---

**Última atualização:** Dezembro 2024  
**Versão da API:** 1.0.0  
**Framework:** Laravel 11.x
