#!/usr/bin/env bash
# ============================================================
#  Instala o worker do Claude num LXC (Debian/Ubuntu).
#
#  Corre isto DENTRO do container, como root:
#    bash instalar-worker-lxc.sh
#
#  No fim ficam por fazer duas coisas que so tu podes fazer:
#  autenticar o Claude Code, e escrever o token do painel no .env.
# ============================================================
set -euo pipefail

BASE=/opt/ateneya
REPOS=$BASE/repos
PAINEL=$BASE/backup-manager
GIT_BASE=${GIT_BASE:-git@github.com:codebehindportugal-cmd}

echo "==> [1/6] Pacotes"
apt-get update -qq
apt-get install -y -qq git curl unzip \
    php-cli php-mbstring php-xml php-curl php-zip php-sqlite3 \
    ca-certificates

echo "==> [2/6] Node.js e Claude Code"
if ! command -v node >/dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y -qq nodejs
fi
npm install -g @anthropic-ai/claude-code

echo "==> [3/6] Composer"
if ! command -v composer >/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "==> [4/6] Painel (so para ter o artisan do worker)"
mkdir -p "$BASE"
[ -d "$PAINEL/.git" ] || git clone "$GIT_BASE/backup-manager.git" "$PAINEL"
cd "$PAINEL" && git pull --ff-only || true
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> [5/6] Repositorios dos projectos"
mkdir -p "$REPOS"
# Acrescenta aqui os que quiseres que o Claude possa ler.
for repo in entregas associacaosantana mordfocas TaxiDiscretoVF gestao-agricola trelamarela; do
    if [ ! -d "$REPOS/$repo/.git" ]; then
        git clone "$GIT_BASE/$repo.git" "$REPOS/$repo" || echo "   (saltei $repo)"
    else
        git -C "$REPOS/$repo" pull --ff-only || true
    fi
done

echo "==> [6/6] Servico"
cp "$PAINEL/claude-worker.sh" /usr/local/bin/claude-worker.sh
chmod +x /usr/local/bin/claude-worker.sh

cat > /etc/systemd/system/claude-worker.service <<UNIT
[Unit]
Description=Worker do Claude do gestao.ateneya.com
After=network-online.target

[Service]
ExecStart=/usr/local/bin/claude-worker.sh $PAINEL
Restart=always
RestartSec=10
Environment=HOME=/root

[Install]
WantedBy=multi-user.target
UNIT

systemctl daemon-reload

cat <<FIM

============================================================
 Instalado. Faltam duas coisas que so tu podes fazer:

 1. Autenticar o Claude Code com a tua subscricao:
      claude
    Segue o link que ele mostra e faz login. Depois /exit.

 2. Escrever o .env do worker em $PAINEL/.env:
      CLAUDE_PANEL_URL=https://gestao.ateneya.com
      CLAUDE_PANEL_TOKEN=<gera em Projectos > Token do worker>
      CLAUDE_REPOS_BASE=$REPOS
      APP_KEY=base64:...   (php artisan key:generate resolve)

    O CLAUDE_REPOS_BASE e o que faz os caminhos do painel
    (C:\laragon\www\entregas) resolverem aqui para $REPOS/entregas.

 Depois:
      systemctl enable --now claude-worker
      systemctl status claude-worker
      php artisan claude:check     # confirma que encontra o Claude

 Para o modo "copia do servidor" funcionar, esta maquina tem de
 conseguir fazer ssh para as VPS dos clientes sem password.
============================================================
FIM
