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

## Resolver tarefas com o Claude

Cada tarefa de projecto tem um botao **Resolver com o Claude**. O painel so cria um
registo em `claude_runs` com estado `queued` — nunca chama nada para fora. Quem
executa e um worker que **vem buscar por HTTP**, com um token Sanctum:

```
GET  /api/claude/next               -> entrega o pedido mais antigo e marca-o a correr
POST /api/claude/runs/{id}/finish   -> devolve a resposta (ou o erro)
```

E o mesmo sentido de conversa dos sincronizadores: o painel esta em producao e o
codigo esta noutro sitio, por isso a ligacao e sempre de fora para dentro. Uma
consequencia util: **o worker corre onde quiseres** — o PC com o laragon, um LXC no
Proxmox, ou a propria VPS. Muda-se o `.env` da maquina, nao o painel.

```bash
php artisan claude:work          # fica a correr (claude-worker.bat / claude-worker.sh)
php artisan claude:work --once   # apanha um pedido e sai, para cron
```

No `.env` **da maquina do worker** (nunca no do servidor):

```
CLAUDE_PANEL_URL=https://gestao.ateneya.com
CLAUDE_PANEL_TOKEN=...     # Projectos > Token do worker
CLAUDE_BINARY=claude       # em Windows, o caminho completo do .cmd
```

### De onde vem o codigo (`projects.code_source`)

| Fonte | Onde ele trabalha | Para que projectos |
|---|---|---|
| `local` | a pasta em `projects.code_path` | codigo que ja esta no disco da maquina do worker |
| `remote` | copia so de leitura em `storage/app/claude/snapshots/<slug>` | sites que so existem no servidor |
| `none` | pasta vazia — planeia sem ler codigo | tarefas de manutencao: passwords, backups, actualizacoes |

No caso `remote` o caminho nao se repete no projecto: sai do `Site` associado
(`wp_root` ou `app_path`) e a ligacao sai do `Server` ao lado. O worker traz por
SSH o tema, os plugins e os mu-plugins num `tar`, e **nunca** `wp-config.php`,
`.env`, uploads ou binarios. A copia dura `CLAUDE_SNAPSHOT_TTL` minutos antes de
ser puxada outra vez. Producao nunca e escrita.

`none` e o valor por defeito de proposito: um projecto novo funciona sem
configuracao nenhuma, so com respostas mais fracas.

O worker leva no `code` da resposta da API tudo o que precisa (host, porta,
utilizador, caminho). Segredos nao passam: a chave SSH e a que a maquina do
worker ja tem no `~/.ssh`.

### Continuar a conversa

O `claude -p` e um tiro unico: responde e sai. Para avancar ha o botao **Continuar**
na linha da tarefa, com uma caixa de texto. Escreve-se a instrucao, o worker retoma a
sessao anterior com `--resume` e manda so o que e novo — o contexto ja la esta.

Como o painel e web, isto funciona do telemovel: escreve-se a instrucao de qualquer
lado, fica na fila, e o worker executa quando estiver a correr.

O interruptor **Pode alterar ficheiros** decide o modo:

| | Permissoes | O que faz |
|---|---|---|
| Desligado (`continue`) | `dontAsk` + leitura | Responde. Nao toca em nada. |
| Ligado (`apply`) | `acceptEdits` + Read/Edit/Write | Altera os ficheiros e **para ai**. |

Em modo `apply` ele nao faz commit, nao cria ramos, nao faz push, nao corre migrations
e nao instala pacotes — a entrega e o trabalho por commitar, para se ver com `git diff`
e enviar com o `enviar-producao.bat` como sempre. O bloqueio a `.env` e chaves mantem-se
nos dois modos.

Tambem se pode retomar a conversa no terminal: o modal mostra o `claude --resume <id>`
ja com a pasta do projecto.

### O resto

- Modo actual: **so diagnostico**. `CLAUDE_PERMISSION_MODE=dontAsk` com uma lista
  curta de ferramentas de leitura — nao altera ficheiros nem toca em servidores.
- A resposta, o custo, a duracao e o prompt exacto ficam no `ClaudeRun` e aparecem
  na linha da tarefa, em **Ver resposta**.
- Um segundo pedido na mesma tarefa continua a conversa anterior (`--resume`).
- Um pedido preso em "a correr" e limpo pelo agendador
  (`claude:reclaim-stale-runs`, de 15 em 15 minutos).
- Texto escrito por terceiros (notas, e um dia tickets) vai delimitado no prompt e
  marcado como dados. Ver `App\Support\ClaudeTaskPrompt`.
