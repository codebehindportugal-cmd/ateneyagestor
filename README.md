# Backup Manager

Aplicacao web (Laravel + Filament) para gerir clientes, faturacao, tickets de
suporte e o estado dos backups feitos pelo `pi-backup-system` no teu
Raspberry Pi -- tudo num so sitio, como pedido.

## Aviso importante sobre este projeto

Construi esta aplicacao por escrito completo (todos os ficheiros Laravel,
migrations, modelos, paineis Filament, API), mas o ambiente onde estou a
trabalhar **nao tem PHP/Composer instalado e nao tenho permissoes para os
instalar** (sandbox sem root e sem acesso à internet fora de uma lista
branca). Ou seja: consegui escrever e rever o codigo com cuidado, mas
**nao consegui correr `composer install`, `php artisan migrate`, nem abrir
a aplicacao num browser para confirmar que arranca sem erros.**

Isto e diferente do `pi-backup-system` (Python), que testei mesmo a correr
ponta-a-ponta com um servidor falso. Aqui, antes de confiares nisto em
produção, tens de fazer tu o primeiro arranque no teu VPS/Plesk (que já tem
PHP) e seguir a checklist de verificação mais abaixo. Se algo nao arrancar
à primeira, é provavel que seja um erro pequeno e localizado (ex: um nome
de classe, um import) -- diz-me o erro exato do `composer install` /
`php artisan migrate` / log do Laravel e eu corrijo.

## Arquitetura (porque foi feita assim)

- **O site (este projeto) gere tudo**: clientes, servidores a fazer
  backup, faturas, tickets, e o historico de execucoes de backup. Corre
  num VPS ou conta Plesk -- onde tu disseste que preferias.
- **O Pi executa**: continua a usar o `pi-backup-system` (Python) que ja
  esta a funcionar. Em vez de editares `config.yaml` a mao, um novo script
  `agent_sync.py` (no mesmo projeto) **pergunta a este site** quais sao os
  servidores a backup, gera o `config.yaml` automaticamente, corre o
  `backup.py` normal, e devolve o resultado a este site.
- **O Pi liga-se sempre para fora, nunca o contrario.** Isto resolve
  diretamente o problema que apontaste: *o IP do Pi em casa muda*. Como o
  site nunca precisa de "chamar" o Pi -- é sempre o Pi que liga ao
  site -- nao precisas de DNS dinamico, abrir portos no router, nem nada
  parecido. Corre o `agent_sync.py` por cron no Pi (ver
  `pi-backup-system/README.md`), e ele fala com este site sempre que
  corre.
- **Os segredos (chaves SSH, token da API cPanel) ficam SO no Pi**, nunca
  neste site/base de dados. O site so guarda metadados (host, porta,
  utilizador, caminhos) -- nunca uma password ou chave privada. Cada
  servidor tem um campo `agent_secret_ref` que liga ao `secrets.yaml`
  local do Pi. Ver `pi-backup-system/README.md` e
  `pi-backup-system/secrets.example.yaml`. Isto se mantém fiel à decisão
  de segurança já tomada no `pi-backup-system`: o sitio mais exposto à
  internet (este site) nunca guarda os segredos que abrem ligacao aos
  teus servidores.

## Os 3 "utilizadores" deste site

1. **Admin** (`/admin`) -- tu (e a tua equipa, se tiveres). Gere tudo:
   clientes, servidores, faturas, tickets, agentes/tokens.
2. **Cliente** (`/client`) -- os teus clientes. Veem as suas proprias
   faturas (so leitura) e os seus tickets (podem abrir e responder), e o
   estado dos backups dos seus servidores. Um cliente só ganha acesso ao
   portal quando lhe definires uma password no admin -- até lá, o registo
   serve só para faturação/gestão interna.
3. **Agente** (Pi) -- autentica-se via token (Sanctum), só pode chamar
   `/api/agent/*`. Nao é um login -- nao tem password nem acesso a paineis.

## Pre-requisitos no servidor (VPS ou Plesk)

- PHP 8.2 ou superior, com as extensoes habituais do Laravel (mbstring,
  xml, curl, pdo_mysql, zip, bcmath, openssl, tokenizer, ctype, fileinfo).
- Composer 2.
- MySQL 8 (ou MariaDB equivalente) -- ou ajusta `DB_CONNECTION` no `.env`
  para `sqlite` se quiseres testar rapido sem criar uma base de dados.
- Acesso para definir o **document root como a pasta `public/`** deste
  projeto (tanto VPS com Nginx/Apache, como um dominio em Plesk).
- Um cron a correr `php artisan schedule:run` a cada minuto (ver abaixo).

## Instalacao

```bash
cd backup-manager
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edita o `.env`:
- `APP_URL` -- o dominio real onde vais publicar isto (ex:
  `https://gestao.oteudominio.pt`).
- `DB_*` -- dados da base de dados MySQL que criaste no VPS/Plesk.

Depois:

```bash
php artisan migrate
php artisan storage:link
php artisan db:seed   # opcional -- cria dados de exemplo (cliente, servidores, faturas, ticket)
```

Se correres o `db:seed`, ele imprime no terminal:
- O login do admin (`andre.f.mendes92@gmail.com` / `changeme123`) -- **troca a password no primeiro login.**
- O login do cliente de demonstracao -- apaga este registo quando tiveres clientes reais.
- Um **token de agente** para o Pi "Pi de casa" -- copia-o, só aparece uma vez (ou gera um novo a partir do admin: Agentes > criar/editar > "Gerar novo token").

Se preferires nao usar dados de exemplo, cria so o utilizador admin a mao:

```bash
php artisan tinker
>>> \App\Models\User::create(['name' => 'Andre', 'email' => 'andre.f.mendes92@gmail.com', 'password' => 'uma-password-forte']);
```

### Permissoes

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache   # ajusta o utilizador do teu servidor web
```

### Cron (no VPS/Plesk, nao no Pi)

```cron
* * * * * cd /caminho/para/backup-manager && php artisan schedule:run >> /dev/null 2>&1
```

Isto liga o scheduler do Laravel (`routes/console.php`), que so faz duas
coisas por agora: marcar um agente como "offline" se nao aparecer por 3h, e
marcar faturas como "em atraso" quando passam da data de vencimento.

## Checklist de verificacao manual (faz isto antes de confiar em produção)

1. `composer install` correu sem erros.
2. `php artisan migrate` criou todas as tabelas sem erros (`php artisan
   migrate:status` para confirmar).
3. Abres `https://<o-teu-dominio>/admin/login` no browser e consegues
   entrar com o utilizador admin.
4. No admin, consegues ver os menus: Clientes, Servidores, Faturas,
   Tickets, Agentes (Pi), Historico de backups, Equipa.
5. Crias um Cliente de teste, defines-lhe password, e consegues entrar em
   `/client/login` com esse cliente -- e ves so as faturas/tickets dele.
6. Em Agentes, crias um agente, copias o token gerado.
7. No Pi, configuras `agent_config.yaml` com o URL deste site + esse
   token (ver `pi-backup-system/README.md`), e corres:
   `python3 agent_sync.py --dry-run -v`
   Deve mostrar "Fetching config from https://..." com sucesso (nao
   "Connection refused" nem 401/403).
8. Corres `python3 agent_sync.py -v` (sem `--dry-run`) uma vez e confirmas
   que aparece uma linha nova em Historico de backups / no servidor certo.
9. No portal do cliente, o widget de "Estado dos teus backups" mostra essa
   execucao.

Se algum destes passos falhar, o erro exato (mensagem do Laravel, do
browser, ou do `agent_sync.py`) e o ponto de partida mais rapido para eu
corrigir.

## Ligar ao pi-backup-system existente

Resumo (detalhe completo em `pi-backup-system/README.md`):

1. Cria um Agente aqui no site (Admin > Agentes > Criar), copia o token.
2. No Pi: `cp agent_config.example.yaml agent_config.yaml`, preenche
   `api.base_url` (URL deste site) e `api.token` (o token copiado).
3. No Pi: `cp secrets.example.yaml secrets.yaml`, preenche os caminhos das
   chaves SSH / token cPanel reais -- **isto nunca sai do Pi.**
4. Cria os Servidores aqui no site (Admin > Servidores), um por cada VPS /
   conta Plesk / conta cPanel, com o mesmo `agent_secret_ref` (ou nome) que
   usaste no `secrets.yaml`.
5. No crontab do Pi, troca a chamada a `backup.py` por
   `agent_sync.py` (ver README do pi-backup-system para a linha exata).

A partir daqui, adicionar um servidor novo é só: criar o registo aqui no
site + adicionar a entrada correspondente no `secrets.yaml` do Pi. Nao
precisas de tocar em codigo.

## Estrutura de ficheiros (resumo)

```
backup-manager/
├── app/
│   ├── Enums/                     ServerType, BackupStatus, InvoiceStatus, TicketStatus, TicketPriority
│   ├── Models/                    User, Client, Agent, Server, BackupRun, Invoice, Ticket, TicketMessage
│   ├── Http/Controllers/Api/      AgentController (config / runs / heartbeat)
│   ├── Providers/Filament/        AdminPanelProvider, ClientPanelProvider
│   └── Filament/
│       ├── Admin/Resources/       Client, Server, Invoice, Ticket, BackupRun, Agent, User
│       └── Client/Resources/      Invoice (so leitura), Ticket (criar/responder) + widget de estado de backups
├── database/migrations/           todas as tabelas
├── database/seeders/              dados de demonstracao opcionais
├── routes/
│   ├── web.php                    redireciona / -> /admin/login
│   ├── api.php                    /api/agent/* (Sanctum)
│   └── console.php                scheduler (agentes offline, faturas em atraso)
└── README.md                      este ficheiro
```

## Faturacao -- o que isto e e nao e

Como pediste, isto é só **registo de faturado vs. pago vs. em falta**: cada
fatura tem um valor, datas de emissao/vencimento, e um botao "Marcar como
paga". **Nao ha integracao com nenhum gateway de pagamento** (Stripe,
Multibanco, etc.) -- isso teria de ser um pedido aparte se um dia
precisares de cobranca automatica.

## Proximos passos para teres isto a funcionar de verdade

1. Publicar este projeto num VPS ou subdominio Plesk com PHP 8.2+.
2. Seguir "Instalacao" acima (composer, .env, migrate, seed opcional).
3. Trocar a password do admin gerada pelo seed.
4. Criar o(s) cliente(s) reais e definir-lhes password se quiseres dar-lhes
   portal.
5. Criar o Agente (o teu Pi) e copiar o token.
6. Seguir "Ligar ao pi-backup-system existente" acima.
7. Correr a checklist de verificacao manual passo a passo.
8. So depois de tudo verde, pôr o `agent_sync.py` no crontab real do Pi.
