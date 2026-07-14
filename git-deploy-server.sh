#!/usr/bin/env bash
# git-deploy-server.sh — Corre no SERVIDOR para actualizar via git pull
# Uso local (Windows): bash git-deploy-server.sh
# Ou via SSH: ssh plesk-dev "bash /var/www/backup-manager/git-deploy-server.sh"

set -euo pipefail

SERVER="gestao.ateneya.com"
SSH_KEY="$HOME/.ssh/ateneya_vps_key"
[ ! -f "$SSH_KEY" ] && SSH_KEY="$HOME/.ssh/id_rsa"
REMOTE_DIR="/var/www/backup-manager"

echo "=========================================="
echo "  Backup Manager - Deploy via git pull"
echo "  $(date)"
echo "=========================================="

ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "root@$SERVER" bash -s <<REMOTE
set -euo pipefail
cd $REMOTE_DIR

echo "==> Estado antes:"
git log --oneline -2

echo "==> Git pull..."
git pull origin master

echo "==> Composer..."
composer install --no-dev --optimize-autoloader --quiet

echo "==> Artisan..."
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear

echo ""
echo "==> Commits em producao agora:"
git log --oneline -3
echo ""
echo "Deploy concluido!"
REMOTE
