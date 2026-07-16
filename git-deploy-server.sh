#!/usr/bin/env bash
# git-deploy-server.sh — Atualiza produção via git pull + limpeza
# No servidor:  bash /var/www/backup-manager/git-deploy-server.sh --local
# Do Windows:   bash git-deploy-server.sh   (faz SSH para o servidor)

set -euo pipefail

REMOTE_DIR="/var/www/backup-manager"

if [ "${1:-}" != "--local" ]; then
  SERVER="gestao.ateneya.com"
  SSH_KEY="$HOME/.ssh/ateneya_vps_key"
  [ ! -f "$SSH_KEY" ] && SSH_KEY="$HOME/.ssh/id_rsa"
  exec ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "root@$SERVER" \
    "bash $REMOTE_DIR/git-deploy-server.sh --local"
fi

# ---------- corre NO SERVIDOR ----------
cd "$REMOTE_DIR"

echo "=========================================="
echo "  Backup Manager — Deploy  $(date)"
echo "=========================================="
echo "==> Antes: $(git log --oneline -1)"

# 1. Proteger uploads (o commit de limpeza remove-os do git;
#    sem isto o pull apagava os ficheiros reais dos utilizadores)
if [ -d storage/app/public ]; then
  cp -a storage/app/public /tmp/storage-public-safe
fi

# 2. Atualizar código
git fetch origin master
git reset --hard origin/master

# 3. Restaurar uploads que o pull tenha removido
if [ -d /tmp/storage-public-safe ]; then
  cp -rn /tmp/storage-public-safe/. storage/app/public/ 2>/dev/null || true
  rm -rf /tmp/storage-public-safe
fi

# 4. Limpeza de ficheiros desnecessários no servidor
rm -rf bin _local .claude .agents 2>/dev/null || true
rm -f ./*.bat ./*-log.txt ./check-*.txt ./deploy-*.txt \
      DEPLOY_STATUS.txt deploy-status.html "toArray())" 2>/dev/null || true
# logs Laravel com mais de 30 dias
find storage/logs -name "*.log" -mtime +30 -delete 2>/dev/null || true

# 5. Dependências e caches
composer install --no-dev --optimize-autoloader --quiet
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Permissões
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo ""
echo "==> Agora: $(git log --oneline -1)"
echo "==> Espaço na partição:"
df -h "$REMOTE_DIR" | tail -1
echo "Deploy concluído!"
