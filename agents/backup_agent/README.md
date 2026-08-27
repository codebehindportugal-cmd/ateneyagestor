# Agente de backups — LXC no Proxmox

Puxa os backups das VPS para o NAS local e reporta ao painel `gestao.ateneya.com`.

O agente **liga sempre para fora**. O painel nunca precisa de alcançar esta máquina:
o IP de casa pode mudar, e o NAS nunca fica exposto à internet. As chaves SSH ficam
só aqui, no `secrets.yaml` — a base de dados do painel guarda apenas metadados
(host, porta, utilizador, caminhos).

```
   LXC no Proxmox                     internet                VPS (Plesk / WP / Laravel)
  ┌────────────────┐                                        ┌────────────────────────┐
  │ agent_sync.py  │──── GET /api/agent/config ────────────▶│ gestao.ateneya.com     │
  │                │◀─── servidores + retenção ─────────────│ (painel)               │
  │ backup.py      │──── ssh + stream ────────────────────▶ │ dump + tar (stdout)    │
  │      │         │◀─── dados ──────────────────────────── │                        │
  │      ▼         │──── POST /api/agent/runs ─────────────▶│ painel regista o run   │
  │  /mnt/nas      │                                        └────────────────────────┘
  └────────────────┘
```

---

## 1. Criar o container no Proxmox

No nó `home`, um LXC Debian 12 chega bem:

```bash
pct create 105 local:vztmpl/debian-12-standard_12.7-1_amd64.tar.zst \
  --hostname backup-agent \
  --cores 2 --memory 1024 --swap 512 \
  --rootfs local-lvm:8 \
  --net0 name=eth0,bridge=vmbr0,ip=dhcp \
  --features nesting=1 \
  --unprivileged 1 \
  --onboot 1
pct start 105
pct enter 105
```

8 GB de disco chegam porque **nada é guardado no container** — o stream vai
direto para o NAS.

## 2. Montar o NAS

O `backup_root` tem de ser um ponto de montagem real. O agente recusa-se a correr
se não for: um NAS desmontado encheria o disco do container em silêncio, e depois
descobrias na altura errada que os backups não estavam onde julgavas.

**NFS** (mais simples, e o dono dos ficheiros mantém-se):

```bash
apt install -y nfs-common
mkdir -p /mnt/nas
echo "192.168.1.X:/volume1/backups  /mnt/nas  nfs  defaults,_netdev,soft,timeo=150  0 0" >> /etc/fstab
mount -a && mountpoint /mnt/nas
```

**SMB/CIFS** (se o NAS só falar SMB):

```bash
apt install -y cifs-utils
mkdir -p /mnt/nas
printf 'username=backup\npassword=…\n' > /root/.smbcred && chmod 600 /root/.smbcred
echo "//192.168.1.X/backups  /mnt/nas  cifs  credentials=/root/.smbcred,_netdev,uid=0,gid=0,file_mode=0640,dir_mode=0750  0 0" >> /etc/fstab
mount -a && mountpoint /mnt/nas
```

> Num LXC **unprivileged**, montar dentro do container não funciona.
> Monta no host Proxmox e passa para dentro:
> `pct set 105 -mp0 /mnt/pve/nas-backups,mp=/mnt/nas`

## 3. Instalar o agente

```bash
bash install.sh
```

Instala em `/opt/backup-agent`, cria o venv, gera uma chave SSH ed25519 e regista
um timer systemd diário (03:30 por omissão — `BACKUP_TIME=04:00 bash install.sh`
muda a hora).

## 4. Ligar ao painel

No painel: **Operação → Agentes → Criar agente**, tipo *Agente de backups*.
Preenche o *Caminho do disco* e a retenção. Guarda o token — só aparece uma vez.

```yaml
# /opt/backup-agent/agent.yaml
api_url: https://gestao.ateneya.com
token: "…"
backup_root: /mnt/nas/backups
require_mountpoint: true
```

Depois, em **Servidores**, põe cada servidor com *Agente (Pi) responsável* =
este agente. Servidores sem agente atribuído também são entregues a este —
o endpoint devolve `agent_id IS NULL OR agent_id = <este>`.

## 5. Dar acesso SSH às VPS

```bash
cat /opt/backup-agent/keys/ateneya_vps.pub
```

Essa chave pública vai para o `~/.ssh/authorized_keys` do utilizador de backup de
cada VPS (`root` no caso do Plesk, que precisa de `plesk bin pleskbackup`).

Depois, uma entrada por servidor no `secrets.yaml`, usando o `agent_secret_ref`
do painel (ou o nome do servidor, se estiver vazio):

```yaml
default_key_path: /opt/backup-agent/keys/ateneya_vps
servers:
  gestao-ateneya-com:
    key_path: /opt/backup-agent/keys/ateneya_vps
```

## 6. Testar

```bash
/opt/backup-agent/run.sh --config-only     # vê o config.yaml montado
/opt/backup-agent/run.sh --dry-run         # testa o SSH a todos, não transfere nada
/opt/backup-agent/run.sh --only gestao-ateneya-com   # um servidor a sério
```

O `--dry-run` não é gravado no histórico do painel (o `AgentController` ignora-o
de propósito), por isso podes usá-lo à vontade.

---

## O que é guardado, por tipo

| Tipo | O que puxa |
|---|---|
| `plesk` | `pleskbackup --domains-name <domínio> --output-file=-` → `<domínio>.tar` |
| `wordpress` | `database.sql.gz` (wp-cli, ou mysqldump lido do `wp-config.php`) + `files.tar.gz` |
| `vps_laravel` | `database.sql.gz` (credenciais do `.env` ou do `db_override`) + `app.tar.gz` + `storage-*.tar.gz` |
| `cpanel` | ainda não implementado — dá erro claro |

Tudo é transmitido por stdout: **nada é escrito no disco do servidor de origem**.
É por isso que funciona mesmo em VPS com o disco quase cheio, que é onde o backup
nativo do Plesk costuma morrer.

## Layout no NAS

```
/mnt/nas/backups/
└── gestao-ateneya-com/
    ├── 2026-08-27_0330/
    │   ├── gestao.ateneya.com.tar
    │   └── manifest.json      ← tamanhos + sha256 de cada ficheiro
    ├── 2026-08-26_0330/
    └── latest -> 2026-08-27_0330
```

Um backup a meio fica em `.<stamp>.partial` e só é renomeado no fim. Uma execução
interrompida nunca fica a parecer completa.

## Retenção

`keep_days` e `keep_min_copies`, do painel: primeiro o global do agente, e o
valor por servidor sobrepõe-se. O mínimo de cópias ganha sempre ao número de dias —
se estiveres 3 meses sem correr, as últimas N cópias não são apagadas.

## Verificar um backup

```bash
cd /mnt/nas/backups/gestao-ateneya-com/latest
sha256sum -c <(python3 -c "
import json;[print(a['sha256'],' ',a['file']) for a in json.load(open('manifest.json'))['artifacts']]")
```

## Problemas comuns

| Sintoma | Causa |
|---|---|
| `não é um ponto de montagem` | NAS desmontado — `mount -a` e vê o `dmesg` |
| `SSH falhou: Permission denied` | chave pública não está no `authorized_keys` do servidor |
| `No route to host` | firewall do destino (Plesk Firewall / fail2ban) bloqueia este IP |
| `sem entrada '<ref>' no secrets.yaml` | falta a entrada, ou o `agent_secret_ref` do painel não coincide |
| `database.sql.gz veio vazio` | `wp` ou `mysqldump` não existem no servidor, ou credenciais erradas |
| Agente aparece *offline* no painel | sem heartbeat há 3h — `systemctl status backup-agent.timer` |

## Logs

```bash
tail -f /opt/backup-agent/logs/agent.log
systemctl status backup-agent.timer
systemctl start backup-agent.service   # correr já
```
