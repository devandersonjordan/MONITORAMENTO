# SolarSaaS - Monitoramento de Usinas Fotovoltaicas

Sistema multi-tenant SaaS para empresas de energia solar monitorarem usinas fotovoltaicas, com integração de inversores, OCR de faturas, assistente IA e alertas automáticos.

## Stack

- **Backend:** Laravel 12 + PHP 8.4 + PostgreSQL 16 + Redis 7
- **Frontend:** React 18 + TypeScript + Vite + Tailwind CSS + ShadCN UI
- **Infra:** Docker Compose + Nginx + GitHub Actions CI/CD

## Setup Rápido (Desenvolvimento)

```bash
# 1. Clone o repositório
git clone https://github.com/devandersonjordan/MONITORAMENTO.git
cd MONITORAMENTO

# 2. Rode o setup (requer Docker)
chmod +x setup.sh
./setup.sh
```

Acesse:
- **Frontend:** http://localhost:3000
- **API:** http://localhost:8000/api
- **Swagger:** http://localhost:8000/api/documentation
- **Mailpit:** http://localhost:8025

### Credenciais Demo

| Role | Email | Senha |
|------|-------|-------|
| Admin | admin@solartechalagoas.com | password |
| Funcionário | funcionario1@solartechalagoas.com | password |
| Cliente | cliente1@solartechalagoas.com | password |

## Deploy em Produção

### 1. Provisionar Servidor (Ubuntu 22.04+)

```bash
# No servidor, como root:
curl -sL https://raw.githubusercontent.com/devandersonjordan/MONITORAMENTO/main/provision.sh | bash -s deploy app.seudominio.com.br
```

Ou manualmente:
```bash
git clone https://github.com/devandersonjordan/MONITORAMENTO.git /opt/solar-saas
cd /opt/solar-saas
chmod +x provision.sh deploy.sh setup.sh
sudo ./provision.sh deploy app.seudominio.com.br
```

### 2. Configurar Credenciais

```bash
cd /opt/solar-saas
cp backend/.env.example backend/.env.production
nano backend/.env.production
```

Preencha obrigatoriamente:
- `APP_URL` — URL do sistema (https://app.seudominio.com.br)
- `DB_PASSWORD` — Senha forte para PostgreSQL
- `REDIS_PASSWORD` — Senha forte para Redis
- `ANTHROPIC_API_KEY` — Chave da API Claude (sk-ant-api03-...)
- `MAIL_*` — Credenciais SMTP
- `WHATSAPP_*` — Evolution API (opcional)
- Credenciais dos inversores conforme marcas utilizadas

### 3. Deploy

```bash
./deploy.sh
```

### 4. SSL (primeira vez)

```bash
# Atualizar domínio no nginx
sed -i 's/app.seudominio.com.br/SEU_DOMINIO/g' nginx/production.conf

# Gerar certificado
docker compose -f docker-compose.production.yml run --rm certbot \
  certonly --webroot --webroot-path=/var/www/certbot -d SEU_DOMINIO
```

### 5. GitHub Secrets (CI/CD)

Configure no repositório Settings > Secrets:

| Secret | Descrição |
|--------|-----------|
| `DEPLOY_HOST` | IP ou hostname do servidor |
| `DEPLOY_USER` | Usuário SSH (ex: deploy) |
| `DEPLOY_SSH_KEY` | Chave privada SSH (conteúdo do id_rsa) |

## Arquitetura

```
┌─────────────┐     ┌──────────┐     ┌───────────────┐
│   Frontend   │────▶│  Nginx   │────▶│  Laravel API  │
│  React SPA   │     │ (proxy)  │     │  PHP-FPM 8.4  │
└─────────────┘     └──────────┘     └───────┬───────┘
                                              │
                    ┌─────────────────────────┼──────────────┐
                    │                         │              │
              ┌─────▼─────┐          ┌───────▼──────┐  ┌───▼────┐
              │ PostgreSQL │          │    Redis     │  │ Queue  │
              │     16     │          │   (cache)    │  │ Worker │
              └────────────┘          └──────────────┘  └────────┘
```

### Multi-Tenant

Cada modelo com `company_id` usa o trait `BelongsToCompany`, que aplica um global scope filtrando automaticamente pelo tenant do usuário autenticado.

### Módulos

| Módulo | Descrição |
|--------|-----------|
| **Empresas** | Gestão multi-tenant de empresas |
| **Clientes** | Cadastro com dados Equatorial (UC, login) |
| **Usinas** | Plantas fotovoltaicas com geolocalização |
| **Inversores** | 4 marcas (Elekeeper, GoodWe, Sungrow, Deye) via adapter pattern |
| **Faturas** | Download Equatorial + OCR Tesseract |
| **Dashboard** | 5 gráficos ApexCharts (tempo real, diário, mensal, produção vs consumo, economia) |
| **Relatórios** | PDF profissional com métricas de performance e balanço energético |
| **Assistente IA** | Chat com Claude API + análise de usinas e faturas |
| **Alertas** | Detecção automática de anomalias (6 tipos) |
| **Notificações** | Email + database + WhatsApp (alertas críticos) |

### Jobs Agendados

| Job | Frequência |
|-----|-----------|
| `SyncInverterDataJob` | A cada 5 min |
| `DownloadEquatorialInvoicesJob` | Diário às 06:00 |
| `ProcessInvoiceOCRJob` | A cada hora |
| `GenerateMonthlyReportsJob` | Dia 1 às 08:00 |
| `DetectAnomaliesJob` | A cada 30 min |
| Backup DB | Diário às 02:00 |

## Testes

```bash
# Todos os testes
docker compose exec app php artisan test

# Testes em paralelo
docker compose exec app php artisan test --parallel

# Apenas unit tests
docker compose exec app php artisan test --testsuite=Unit
```

## API

Documentação Swagger disponível em `/api/documentation` após rodar:
```bash
docker compose exec app php artisan l5-swagger:generate
```

### Endpoints Principais

```
POST   /api/auth/login
POST   /api/auth/register
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/dashboard/stats

CRUD   /api/companies
CRUD   /api/clients
CRUD   /api/plants
CRUD   /api/inverters

GET    /api/invoices
GET    /api/invoices/{id}/pdf

GET    /api/reports
POST   /api/reports
GET    /api/reports/{id}/pdf

GET    /api/charts/daily-generation
GET    /api/charts/monthly-generation
GET    /api/charts/yearly-generation
GET    /api/charts/production-vs-consumption
GET    /api/charts/savings-history
GET    /api/charts/realtime-power

POST   /api/ai/chat
GET    /api/ai/analyze/plant/{plant}
GET    /api/ai/analyze/invoice/{invoice}

GET    /api/alerts
GET    /api/alerts/stats
PATCH  /api/alerts/{id}/resolve

GET    /api/notifications
GET    /api/notifications/unread-count
PATCH  /api/notifications/{id}/read
POST   /api/notifications/mark-all-read
```

## Segurança

- Rate limiting: 60/min API, 10/min auth, 10/min IA
- Security headers (HSTS, XSS, CSRF)
- Audit log automático (POST/PUT/PATCH/DELETE)
- Senhas Equatorial encriptadas (AES)
- LGPD: anonimização, exportação e exclusão de dados

## Licença

Proprietário - Todos os direitos reservados.
