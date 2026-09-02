#!/usr/bin/env bash
# git-deploy-server.sh — Atualiza produção via git pull + limpeza
# No servidor:  bash /var/www/backup-manager/git-deploy-server.sh --local
# Do Windows:   bash git-deploy-server.sh   (faz SSH para o servidor)

set -euo pipefail

# O servidor é um Plesk: a app vive em /var/www/vhosts/<dominio>/httpdocs,
# não em /var/www/backup-manager. Detecta o primeiro caminho que exista para
# o script não morrer no "cd" (set -e) e abortar o deploy em silêncio.
REMOTE_DIR="${REMOTE_DIR:-}"
if [ -z "$REMOTE_DIR" ]; then
  for candidate in \
    /var/www/vhosts/gestao.ateneya.com/httpdocs \
    /var/www/backup-manager \
    /var/www/html/backup-manager
  do
    if [ -f "$candidate/artisan" ]; then
      REMOTE_DIR="$candidate"
      break
    fi
  done
fi

if [ -z "$REMOTE_DIR" ]; then
  echo "ERRO: nao encontrei a app (nenhum candidato tem artisan)." >&2
  echo "Define REMOTE_DIR=/caminho/da/app antes de correr o script." >&2
  exit 1
fi

if [ "${1:-}" != "--local" ]; then
  SERVER="gestao.ateneya.com"
  SSH_KEY="$HOME/.ssh/ateneya_vps_key"
  [ ! -f "$SSH_KEY" ] && SSH_KEY="$HOME/.ssh/id_rsa"
  # bash -s envia este mesmo ficheiro por stdin: nao depende do caminho remoto.
  exec ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "root@$SERVER" \
    "bash -s -- --local" < "$0"
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
#    O repositorio e privado. Se o servidor nao se conseguir autenticar no
#    GitHub, o git morre aqui e o set -e aborta tudo — sem esta mensagem
#    ficava so o "fatal: could not read Username", que nao diz o que fazer.
if ! git fetch origin master; then
  echo "" >&2
  echo "ERRO: o servidor nao se consegue autenticar no GitHub." >&2
  echo "      remote actual: $(git remote get-url origin)" >&2
  echo "      Producao ficou como estava: $(git log --oneline -1)" >&2
  echo "" >&2
  echo "Resolver com uma deploy key (nao expira), no servidor:" >&2
  echo "  ssh-keygen -t ed25519 -f ~/.ssh/github_deploy -N ''" >&2
  echo "  cat ~/.ssh/github_deploy.pub   # colar em GitHub > repo > Settings > Deploy keys (read-only)" >&2
  echo "  printf 'Host github.com\\n  IdentityFile ~/.ssh/github_deploy\\n  IdentitiesOnly yes\\n' >> ~/.ssh/config" >&2
  echo "  git remote set-url origin git@github.com:codebehindportugal-cmd/ateneyagestor.git" >&2
  exit 1
fi
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
# Em Plesk o PHP corre como o utilizador da subscricao (ex.: gestao_ateneya),
# NAO como www-data. Fazer chown para www-data partia as permissoes da app,
# por isso herda-se o dono/grupo do proprio directorio da app.
APP_OWNER="$(stat -c '%U:%G' "$REMOTE_DIR")"
echo "==> Dono da app: $APP_OWNER"
chown -R "$APP_OWNER" storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

echo ""
echo "==> Agora: $(git log --oneline -1)"
echo "==> Espaço na partição:"
df -h "$REMOTE_DIR" | tail -1
echo "Deploy concluído!"
