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

## Avisos no telemovel (ntfy)

A app **ntfy** recebe um push sempre que alguma coisa cai. Serve-se de
`App\Support\Ntfy` e configura-se em `config/ntfy.php`.

Duas regras que fazem isto ser util em vez de irritante:

1. **So se avisa em transicoes** — quando cai e quando volta. Um site verificado
   de 5 em 5 minutos daria 288 avisos por dia; assim da dois.
2. **Nunca deita nada abaixo.** Se o ntfy nao responder, fica um `Log::warning` e
   a verificacao segue. Um site em baixo e um problema; nao conseguir avisar
   disso nao pode ser um segundo problema.

Onde esta ligado:

| Aviso | Onde | Dispara quando |
|---|---|---|
| `sites` | `CheckSiteMonitors` | o monitor passa a `down`, e quando volta a `up` |
| `servidores` | `CheckServers` | o ping deixa de responder, e quando volta |
| `sincronizadores` | `SyncController` (`storeRun`, `finishRun`) | o projecto passa a `error`, e quando volta a `ok` |
| `backups` | `AgentController::storeRunResults` | o agente passa a ter falhas, e quando deixa de ter |

No `.env` **do servidor de producao** (e quem corre as verificacoes):

```
NTFY_TOPIC=<nome longo e aleatorio>
```

O topico e a unica coisa que protege isto no `ntfy.sh` publico — quem souber o
nome le tudo o que la passa, por isso nao pode ser "ateneya". Na app do
telemovel: *Subscribe to topic* com exactamente o mesmo nome.

```bash
php artisan ntfy:test           # confirma que chega ao telemovel
```

## Actualizar WordPress com reposicao automatica

Cada site WordPress tem na linha um botao **Actualizar**. O painel poe o pedido
em fila (`site_updates`); quem executa e o **agente dos backups**, que ja tem SSH
a todas as VPS e vem buscar o trabalho por HTTP — o mesmo sentido de conversa do
resto do painel.

```
GET  /api/agent/updates/next               -> entrega o pedido e marca-o a correr
POST /api/agent/updates/{id}/progress      -> linhas de log enquanto corre
POST /api/agent/updates/{id}/finish        -> resultado final
```

No agente: `wp_update.py`, com o servico `wp-updater` a sondar de 30 em 30
segundos (`actualizar.sh --once -v` para correr a mao).

### O que ele faz, por esta ordem

1. **Nao comeca se o site ja estiver mal.** Mede a homepage e as paginas extra
   antes de tocar em nada. Um site que ja dava erro fica como estava — nao se
   actualiza por cima de um problema, e depois nao se sabe qual e qual.
2. **Copia no proprio servidor** — ficheiros e base de dados, em
   `ATUALIZACOES_SNAPSHOT_DIR`. No servidor e nao no NAS de proposito: a graca
   de ter copia e voltar atras em segundos. Os uploads ficam de fora — sao a
   maior parte do disco e nenhuma actualizacao lhes toca.
3. **Um item de cada vez**, core primeiro, depois plugins, depois temas. A
   seguir a cada um testa o site.
4. **O que partir o site e reposto na hora** — so esse item, dos ficheiros da
   copia. O resto continua actualizado e fica registado qual foi o culpado.

Actualizar tudo de uma vez e depois descobrir que o site esta em baixo obriga a
repor tudo e a nao saber qual dos vinte plugins foi. Um de cada vez custa uns
minutos e da uma resposta.

### Como se decide que "partiu"

Nao chega HTTP 200 — o WordPress esconde os fatais atras de uma pagina simpatica.
Sao quatro sinais, sempre comparados com o estado de ANTES:

| Sinal | Apanha |
|---|---|
| Codigo HTTP piorou | o classico 500 |
| Marcas no HTML (`critical error`, `Fatal error`, `wp-recovery-mode`, ...) | o fatal disfarcado |
| A pagina encolheu mais do que `ATUALIZACOES_ENCOLHIMENTO` | o plugin que morre em silencio e deixa meia pagina |
| `wp option get siteurl` deixou de responder | o fatal que a cache do site ainda esconde |

Compara-se sempre com antes, nunca com um ideal: uma pagina que ja dava 404 nao
passa a ser culpa da actualizacao.

### A janela da noite

O botao pergunta **esta noite** (a partir das `ATUALIZACOES_JANELA_INICIO`, por
omissao as 02:00) ou **agora**. Por omissao e a noite: mesmo com reposicao
automatica, ninguem quer descobrir que um plugin partiu a loja as tres da tarde.

Nao ha agendador nenhum a fazer isto. Um pedido da noite nasce com
`agendado_para`; um pedido "agora" nasce sem hora nenhuma. O
`/api/agent/updates/next` da sempre os que nao tem hora, e da os outros so
**dentro** da janela — se o agente estiver em baixo as 2h, o trabalho espera
pela noite seguinte em vez de arrancar as 9 da manha com o site cheio de gente.
Uma simulacao nao espera pela noite: nao toca em nada.

### Escada de reposicao

Correr de madrugada e o que torna o ultimo degrau aceitavel.

| Degrau | O que repoe | Custo |
|---|---|---|
| 1 | So a pasta do item que partiu | nenhum |
| 2 | Todos os ficheiros (`wp-admin`, `wp-includes`, `wp-content`) | o resto das actualizacoes dessa noite |
| 3 | A base de dados, do dump da copia | so avanca se nao custar nada — ver abaixo |

O degrau 3 so entra depois de o 1 e o 2 falharem, e **nao e uma opcao, e uma
contagem**. Antes de repor, o agente conta o que entrou no site desde a copia:
encomendas (`wc_orders` e `shop_order`), comentarios, utilizadores e conteudos.

- **nada entrou** -> repoe. Nao custa nada a ninguem.
- **entrou alguma coisa** -> nao mexe, e a mensagem diz o que era
  ("entraram 2 encomendas desde a copia"). O site fica em baixo e o Andre e
  avisado. Uma encomenda das 3 da manha nao existe em mais lado nenhum: repor
  a base por cima dela apagava-a sem deixar rasto, e ninguem daria por isso.

`ATUALIZACOES_REPOR_BD`: `auto` (o acima, por omissao), `nunca`, `sempre`.

Antes de repor a base, guarda sempre um dump do estado actual em
`database-antes-de-repor.sql.gz`, dentro da mesma pasta da copia. Nenhuma
reposicao pode ser um caminho sem volta.

Depois de qualquer reposicao ele **para ali** e avisa no ntfy. O resto fica por
actualizar de proposito: um site que acabou de partir nao e sitio para continuar
a mexer.

### O que NAO faz

- Nao actualiza sozinho: e sempre por botao, mesmo quando corre a noite.

### Paginas a testar

Em cada site, na seccao **Actualizacoes**, pode-se juntar paginas alem da
homepage. Numa loja vale mesmo a pena por o carrinho e uma ficha de produto — e
onde os plugins partem, nao na entrada.

### Simulacao

O botao tem um interruptor **So ver o que ha para actualizar**: lista o que esta
por actualizar sem tocar em nada.

## Facturas que chegam por email

A caixa `faturacao@ateneya.com` recebe as facturas dos fornecedores. O painel vai
la busca-las sozinho e cria um `AccountingDocument` por cada anexo.

```bash
php artisan faturas:importar-email --teste     # so testa a ligacao
php artisan faturas:importar-email             # corre agora
php artisan faturas:importar-email --dias=90 --todas --limite=200   # primeira carga
```

Corre de 30 em 30 minutos pelo agendador (`cron.faturas_email.cron`), e ha dois
botoes no `/admin/accounting-documents` — **Importar do email** e **Testar
ligacao ao email** — visiveis so ao administrador.

**So entram mensagens com anexo PDF ou imagem.** O resto fica na caixa. Imagens
com menos de `FATURAS_EMAIL_MIN_IMAGE_KB` que venham embutidas no corpo sao
logotipos de assinatura e sao ignoradas; os PDF entram sempre.

Nao ha duplicados: o `ficheiro_hash` e' o sha256 do anexo e tem indice unico. O
mesmo PDF reenviado nao volta a entrar, mesmo com outro assunto ou outro UID.

Depois de importada, a mensagem e' marcada como lida e — se
`FATURAS_EMAIL_PROCESSED_FOLDER` estiver definido — movida para essa pasta. Nada
e' apagado: o original fica sempre no email, que e' onde tem valor legal.

### Porque nao ha biblioteca de IMAP

`app/Services/Faturacao/ImapMailbox.php` e' um cliente IMAP em PHP puro e o
`MimeMessage.php` desmonta o MIME. Duas razoes: o `composer.lock` so' se
actualiza numa maquina com PHP (a copia local nao tem), e um lock desalinhado
faz o `composer install` do deploy morrer; e a `ext-imap` esta depreciada no PHP
8.4 e nao ha garantia de estar no Plesk.

Os dois tem testes: `tests/Unit/MimeMessageTest.php` e
`tests/Unit/ImapMailboxTest.php` (este lanca `tests/Fixtures/servidor-imap-falso.php`).
O que la se testa nao e' decorativo — um anexo lido por linhas em vez de byte a
byte chega corrompido e so' se percebe quando o contabilista abre o PDF.

### O campo do contabilista

`accounting_documents.importado_contabilidade` diz se o contabilista ja lancou o
documento no software dele. Quem marca e' ele, no portal `/contabilista/{token}`:
ha uma caixa por documento que grava sozinha, e um contador no topo com quantos
faltam. No `/admin` isso aparece como coluna, filtro e separador **Por importar
pelo contabilista** — de leitura, para nao haver duas versoes da verdade.
