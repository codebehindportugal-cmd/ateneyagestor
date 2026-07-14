#!/usr/bin/env bash
# deploy-claude.sh — Deploy automático gerado pelo Claude
# Corre em Git Bash no Windows

set -uo pipefail

SERVER="gestao.ateneya.com"
SSH_KEY="$HOME/.ssh/ateneya_vps_key"
SSH_USER="root"
REMOTE_DIR="/var/www/backup-manager"
LOCAL_DIR="/c/laragon/www/backup-manager"
LOG="$LOCAL_DIR/deploy-log.txt"

echo "==========================================" | tee "$LOG"
echo "  Backup Manager — Deploy para Produção"   | tee -a "$LOG"
echo "  $(date)"                                  | tee -a "$LOG"
echo "==========================================" | tee -a "$LOG"
echo "" | tee -a "$LOG"

# ── Verificar chave SSH ──────────────────────────────────────────────────────
if [ ! -f "$SSH_KEY" ]; then
    echo "ERRO: Chave SSH não encontrada em $SSH_KEY" | tee -a "$LOG"
    echo "A tentar com ~/.ssh/id_rsa..." | tee -a "$LOG"
    SSH_KEY="$HOME/.ssh/id_rsa"
fi

echo "Servidor : $SERVER" | tee -a "$LOG"
echo "SSH Key  : $SSH_KEY" | tee -a "$LOG"
echo "Destino  : $REMOTE_DIR" | tee -a "$LOG"
echo "" | tee -a "$LOG"

# ── Testar conectividade ─────────────────────────────────────────────────────
echo "==> [0/6] A testar conectividade SSH..." | tee -a "$LOG"
if ! ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$SSH_USER@$SERVER" echo "SSH OK" >> "$LOG" 2>&1; then
    echo "ERRO: Não foi possível conectar ao servidor!" | tee -a "$LOG"
    echo "Verificar: chave SSH, servidor, firewall" | tee -a "$LOG"
    exit 1
fi
echo "  [OK] Conexão SSH estabelecida." | tee -a "$LOG"
echo "" | tee -a "$LOG"

# ── Verificar estado remoto antes do deploy ──────────────────────────────────
echo "==> [PRE] Estado atual em produção:" | tee -a "$LOG"
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_USER@$SERVER" bash -s 2>&1 | tee -a "$LOG" <<'CHECK'
cd /var/www/backup-manager 2>/dev/null || { echo "AVISO: pasta /var/www/backup-manager não existe em produção!"; exit 0; }
echo "  Git log (últimos 3 commits em produção):"
git log --oneline -3 2>/dev/null || echo "  (sem git)"
echo "  PHP: $(php -r 'echo phpversion();' 2>/dev/null)"
echo "  .env APP_URL: $(grep APP_URL .env 2>/dev/null | head -1)"
CHECK

echo "" | tee -a "$LOG"

# ── Rsync ────────────────────────────────────────────────────────────────────
echo "==> [1/6] A sincronizar ficheiros via rsync..." | tee -a "$LOG"
rsync -avz \
  --exclude='.git' \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='.env' \
  --exclude='storage/logs' \
  --exclude='storage/framework/cache' \
  --exclude='storage/framework/sessions' \
  --exclude='storage/framework/views' \
  --exclude='bootstrap/cache' \
  --exclude='deploy-claude.sh' \
  --exclude='deploy-log.txt' \
  -e "ssh -i $SSH_KEY -o StrictHostKeyChecking=no" \
  "$LOCAL_DIR/" \
  "$SSH_USER@$SERVER:$REMOTE_DIR/" 2>&1 | tee -a "$LOG"

if [ $? -ne 0 ]; then
    echo "ERRO: rsync falhou!" | tee -a "$LOG"
    exit 1
fi
echo "  [OK] Ficheiros sincronizados." | tee -a "$LOG"
echo "" | tee -a "$LOG"

# ── Comandos pós-deploy ──────────────────────────────────────────────────────
echo "==> [2/6] A correr comandos no servidor..." | tee -a "$LOG"
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_USER@$SERVER" bash -s 2>&1 | tee -a "$LOG" <<'REMOTE'
set -euo pipefail
cd /var/www/backup-manager

echo "==> [3/6] Composer install (no-dev)..."
composer install --no-dev --optimize-autoloader --quiet

echo "==> [4/6] npm install + build assets..."
npm install --silent
npm run build

echo "==> [5/6] Migrations + artisan cache..."
php artisan migrate --force
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> [6/6] Permissões..."
chown -R www-data:www-data /var/www/backup-manager
chmod -R 755 /var/www/backup-manager
chmod -R 775 /var/www/backup-manager/storage
chmod -R 775 /var/www/backup-manager/bootstrap/cache

echo ""
echo "=========================================="
echo "  DEPLOY CONCLUÍDO COM SUCESSO!"
echo "  $(date)"
echo "=========================================="
echo ""
echo "  Últimos 3 commits agora em produção:"
git log --oneline -3 2>/dev/null || echo "  (sem git)"
REMOTE

echo "" | tee -a "$LOG"
echo "DEPLOY_STATUS: CONCLUÍDO" | tee -a "$LOG"
echo "FIM: $(date)" | tee -a "$LOG"
