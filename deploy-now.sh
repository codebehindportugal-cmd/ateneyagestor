#!/usr/bin/env bash
# deploy-now.sh — Faz deploy do backup-manager do Windows para produção via rsync + SSH.
# Corre no Git Bash: bash deploy-now.sh
# Preenche as 2 variáveis abaixo antes de correr.

set -euo pipefail

# ─── PREENCHE AQUI ────────────────────────────────────────────────────────────
SERVER="gestao.ateneya.com"          # IP ou domínio do servidor de produção
SSH_KEY="$HOME/.ssh/id_rsa"          # Caminho da chave SSH privada (no Windows: C:/Users/André Mendes/.ssh/...)
SSH_USER="root"                       # Utilizador SSH (normalmente root ou www-data)
REMOTE_DIR="/var/www/backup-manager" # Pasta no servidor onde a app fica
# ──────────────────────────────────────────────────────────────────────────────

echo ""
echo "=========================================="
echo "  Backup Manager — Deploy para Produção"
echo "=========================================="
echo "  Servidor : $SERVER"
echo "  Utilizador: $SSH_USER"
echo "  Destino  : $REMOTE_DIR"
echo "  Chave SSH: $SSH_KEY"
echo ""
echo "Continuar? (Ctrl+C para cancelar, Enter para avançar)"
read -r

# ── 1. Rsync ──────────────────────────────────────────────────────────────────
echo ""
echo "==> [1/6] A sincronizar ficheiros..."
rsync -avz --progress \
  --exclude='.git' \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='.env' \
  --exclude='storage/logs' \
  --exclude='storage/framework/cache' \
  --exclude='storage/framework/sessions' \
  --exclude='storage/framework/views' \
  --exclude='bootstrap/cache' \
  -e "ssh -i $SSH_KEY" \
  /c/laragon/www/backup-manager/ \
  "$SSH_USER@$SERVER:$REMOTE_DIR/"

echo ""
echo "==> [2/6] A correr comandos no servidor..."

# ── 2. Comandos pós-deploy via SSH ────────────────────────────────────────────
ssh -i "$SSH_KEY" "$SSH_USER@$SERVER" bash -s <<REMOTE
set -euo pipefail
cd $REMOTE_DIR

# .env: cria a partir do exemplo se ainda não existir
if [ ! -f .env ]; then
    echo "  [env] Criando .env a partir de .env.production.example..."
    cp .env.production.example .env
    php artisan key:generate
    echo ""
    echo "  !! ATENÇÃO: edita $REMOTE_DIR/.env antes de continuar !!"
    echo "     nano $REMOTE_DIR/.env"
    echo "  (preenche APP_URL, DB_PASSWORD, MAIL_*)"
    exit 1
fi

echo "==> [3/6] Composer install..."
composer install --no-dev --optimize-autoloader --quiet

echo "==> [4/6] Node + build assets..."
npm install --silent
npm run build

echo "==> [5/6] Migrations + cache..."
php artisan migrate --force
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> [6/6] Permissões..."
chown -R www-data:www-data $REMOTE_DIR
chmod -R 755 $REMOTE_DIR
chmod -R 775 $REMOTE_DIR/storage
chmod -R 775 $REMOTE_DIR/bootstrap/cache

echo ""
echo "=========================================="
echo "  Deploy concluído com sucesso!"
echo "  Painel: https://\$(grep APP_URL $REMOTE_DIR/.env | cut -d= -f2)/admin"
echo "=========================================="
REMOTE

echo ""
echo "✓ Deploy terminado."
