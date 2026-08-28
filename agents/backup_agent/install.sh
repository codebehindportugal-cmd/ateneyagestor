#!/usr/bin/env bash
#
# install.sh — instala o agente de backups num container Debian/Ubuntu do Proxmox.
#
#   bash install.sh
#
# Depois: editar /opt/backup-agent/agent.yaml e secrets.yaml, montar o NAS,
# e testar com  /opt/backup-agent/run.sh --dry-run
#
set -euo pipefail

INSTALL_DIR="${INSTALL_DIR:-/opt/backup-agent}"
SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_TIME="${BACKUP_TIME:-03:30}"

if [[ $EUID -ne 0 ]]; then
  echo "Corre como root (é um container dedicado)." >&2
  exit 1
fi

echo "==> Dependências do sistema"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq python3 python3-venv python3-pip openssh-client gzip tar curl ca-certificates

echo "==> $INSTALL_DIR"
mkdir -p "$INSTALL_DIR"/{keys,logs}
for f in agent_sync.py backup.py requirements.txt agent.example.yaml secrets.example.yaml README.md; do
  [[ -f "$SRC_DIR/$f" ]] && install -m 0644 "$SRC_DIR/$f" "$INSTALL_DIR/$f"
done
chmod 0755 "$INSTALL_DIR/agent_sync.py" "$INSTALL_DIR/backup.py"
chmod 0700 "$INSTALL_DIR/keys"

echo "==> Ambiente Python"
python3 -m venv "$INSTALL_DIR/venv"
"$INSTALL_DIR/venv/bin/pip" install --quiet --upgrade pip
"$INSTALL_DIR/venv/bin/pip" install --quiet -r "$INSTALL_DIR/requirements.txt"

# Ficheiros de configuração — nunca sobrepostos numa reinstalação
for f in agent secrets; do
  if [[ ! -f "$INSTALL_DIR/$f.yaml" ]]; then
    cp "$INSTALL_DIR/$f.example.yaml" "$INSTALL_DIR/$f.yaml"
    echo "    criado $INSTALL_DIR/$f.yaml (por preencher)"
  fi
  chmod 0600 "$INSTALL_DIR/$f.yaml"
done

cat > "$INSTALL_DIR/run.sh" <<EOF
#!/usr/bin/env bash
# Atalho: corre o ciclo do agente com o venv certo.
exec "$INSTALL_DIR/venv/bin/python" "$INSTALL_DIR/agent_sync.py" "\$@"
EOF
chmod 0755 "$INSTALL_DIR/run.sh"

echo "==> Chave SSH do agente"
if [[ ! -f "$INSTALL_DIR/keys/ateneya_vps" ]]; then
  ssh-keygen -t ed25519 -N "" -C "backup-agent@proxmox" -f "$INSTALL_DIR/keys/ateneya_vps" >/dev/null
  echo "    chave nova gerada"
fi
chmod 0600 "$INSTALL_DIR/keys/ateneya_vps"

echo "==> systemd"
cat > /etc/systemd/system/backup-agent.service <<EOF
[Unit]
Description=Agente de backups Ateneya (puxa das VPS para o NAS)
After=network-online.target remote-fs.target
Wants=network-online.target

[Service]
Type=oneshot
WorkingDirectory=$INSTALL_DIR
ExecStart=$INSTALL_DIR/venv/bin/python $INSTALL_DIR/agent_sync.py
StandardOutput=append:$INSTALL_DIR/logs/agent.log
StandardError=append:$INSTALL_DIR/logs/agent.log
# 20h. Nao e generosidade: quando este limite dispara, o systemd manda
# SIGTERM a meio de uma transferencia e perde-se a corrida inteira.
TimeoutStartSec=72000
EOF

cat > /etc/systemd/system/backup-agent.timer <<EOF
[Unit]
Description=Corre o agente de backups todos os dias às $BACKUP_TIME

[Timer]
OnCalendar=*-*-* $BACKUP_TIME:00
Persistent=true
RandomizedDelaySec=300

[Install]
WantedBy=timers.target
EOF

cat > /etc/logrotate.d/backup-agent <<EOF
$INSTALL_DIR/logs/*.log {
    weekly
    rotate 8
    compress
    missingok
    notifempty
    copytruncate
}
EOF

systemctl daemon-reload
systemctl enable --now backup-agent.timer >/dev/null

echo
echo "Instalado. Falta:"
echo "  1. Montar o NAS (ver README) e confirmar:  mountpoint /mnt/nas"
echo "  2. Preencher $INSTALL_DIR/agent.yaml (api_url + token do painel)"
echo "  3. Preencher $INSTALL_DIR/secrets.yaml (um agent_secret_ref por servidor)"
echo "  4. Copiar a chave pública para cada VPS:"
echo "       cat $INSTALL_DIR/keys/ateneya_vps.pub"
echo "  5. Testar:  $INSTALL_DIR/run.sh --dry-run"
echo
echo "Próxima execução automática:"
systemctl list-timers backup-agent.timer --no-pager | sed -n '2p'
