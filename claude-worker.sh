#!/usr/bin/env bash
# ============================================================
#  claude-worker.sh - apanha os pedidos feitos no painel
#  (botao "Resolver com o Claude" nas tarefas dos projectos)
#
#  Para correr sempre, num LXC do Proxmox ou na VPS, em vez de
#  ficar com uma janela aberta:
#
#    cp claude-worker.sh /usr/local/bin/
#    cat > /etc/systemd/system/claude-worker.service <<'UNIT'
#    [Unit]
#    Description=Worker do Claude do gestao.ateneya.com
#    After=network-online.target
#
#    [Service]
#    ExecStart=/usr/local/bin/claude-worker.sh /opt/backup-manager
#    Restart=always
#    RestartSec=10
#
#    [Install]
#    WantedBy=multi-user.target
#    UNIT
#    systemctl enable --now claude-worker
# ============================================================
set -u
cd "${1:-$(dirname "$0")}" || exit 1

while true; do
    php artisan claude:work
    echo "O worker parou. A reiniciar dentro de 10 segundos..."
    sleep 10
done
