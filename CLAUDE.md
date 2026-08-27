# backup-manager — CLAUDE.md

## O que é este projeto
Aplicação web (Laravel + Filament) para gerir clientes, faturação, tickets de suporte e backups feitos por um Raspberry Pi (`pi-backup-system`). O Pi comunica sempre para o site — o site nunca "chama" o Pi. Segredos SSH ficam APENAS no Pi.

## Stack
- **Backend:** Laravel 11, PHP 8.2+
- **Admin UI:** Filament 3 (`/admin`)
- **Autenticação:** Laravel + Filament (admin), sessões próprias (cliente)
- **BD:** MySQL (ou SQLite para testes rápidos)
- **API:** Laravel Sanctum (token para o agente Pi)

## Os 3 tipos de utilizador
1. **Admin** (`/admin`) — Filament, gere tudo: clientes, servidores, faturas, tickets, agentes
2. **Client** (`/client`) — portal do cliente: faturas (só leitura), tickets (pode abrir/responder), estado de backups dos seus servidores
3. **Agent (Pi)** — autentica via token Sanctum, só acede a `/api/agent/*`. Não é login.

## Modelos principais
- `Client` — cliente (empresa/pessoa)
- `Server` — servidor a fazer backup (pertence a um `Client`)
- `Agent` — token de acesso do Pi (`agent_secret_ref` liga ao `secrets.yaml` local do Pi)
- `BackupRun` — resultado de uma execução de backup
- `Invoice` — fatura emitida ao cliente
- `Ticket` + `TicketMessage` — suporte
- `SupplierInvoice` + `SupplierInvoiceItem` — faturas de fornecedores
- `ClientDocument` + `AccountingDocument` — documentos do cliente / contabilidade
- `SiteMonitor` + `SiteMonitorCheck` — monitorização de sites
- `SyncProject` + `SyncRun` — projetos de sincronização

## Controllers
- `AccountantViewController` — vista de contabilidade
- `ClientDocumentController` — documentos do cliente
- `SupplierInvoiceController` — faturas de fornecedores
- `Api/` — endpoints do agente Pi

## Segurança — IMPORTANTE
- Segredos SSH e tokens cPanel ficam **APENAS no Pi** (`secrets.yaml` local)
- A BD guarda só metadados (host, porta, utilizador, caminhos) — **nunca passwords ou chaves privadas**
- O campo `agent_secret_ref` liga ao `secrets.yaml` do Pi, não guarda o segredo

## Arquitetura Pi ↔ Site
- O Pi corre `agent_sync.py` por cron
- `agent_sync.py` pede ao site quais servidores fazer backup → gera `config.yaml` → corre `backup.py` → devolve resultado ao site
- O IP do Pi pode mudar — não importa porque o Pi sempre liga para fora

## Deploy (primeira vez)
```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# Editar .env: APP_URL, DB_*
php artisan migrate
php artisan storage:link
php artisan db:seed   # cria admin + cliente demo + token Pi (só aparecem uma vez)
```

Cron: `* * * * * php artisan schedule:run`
