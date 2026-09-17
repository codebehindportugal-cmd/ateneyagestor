#!/usr/bin/env bash
#
# rodar-segredos.sh — troca os segredos de producao que estiveram expostos.
#
# Porque existe: a 17/09/2026 descobriu-se que o repositorio no GitHub estava
# PUBLICO e tinha la dentro o .env de producao (um ficheiro "env" sem ponto e
# dois .env.bak de julho) e o agent.yaml com o token de um agente. Tornar o
# repositorio privado nao chega: quem o copiou ja tem os valores.
#
# Corre NO SERVIDOR, como root, na pasta da app:
#   cd /var/www/vhosts/gestao.ateneya.com/httpdocs && bash scripts/rodar-segredos.sh
#
# O que faz, por esta ordem (pergunta antes de cada passo que se pode saltar):
#   1. copia o .env para .env.antes-rotacao-<data> (fica fora do git)
#   2. revoga o token exposto (id 7) — mostra primeiro de que agente e
#   3. novo topico ntfy
#   4. nova APP_KEY, com a antiga em APP_PREVIOUS_KEYS, e recifra a base de dados
#   5. password da base de dados (a nova tem de ja estar mudada no Plesk)
#   6. password da caixa faturacao@ (a nova tem de ja estar mudada no email)
#   7. apaga as copias do .env que estiveram no repositorio
#
# Nenhum valor secreto e escrito no ecra, excepto o topico ntfy novo — esse
# tem de ser copiado para a app do telemovel.

set -euo pipefail

APP_DIR="$(pwd)"
[ -f "$APP_DIR/artisan" ] || { echo "Corre isto na pasta da app (onde esta o artisan)." >&2; exit 1; }
[ "$(id -u)" -eq 0 ] || { echo "Corre como root." >&2; exit 1; }

PHP="/opt/plesk/php/8.3/bin/php"
[ -x "$PHP" ] || PHP="$(command -v php)"
OWNER="$(stat -c '%U' "$APP_DIR")"
ENV_FILE="$APP_DIR/.env"

artisan() { sudo -u "$OWNER" "$PHP" "$APP_DIR/artisan" "$@"; }

# Le um valor do .env sem o mostrar.
env_get() {
    awk -v k="$1" 'index($0, k"=")==1 { v=substr($0, length(k)+2); gsub(/^["\047]|["\047]$/, "", v); print v; exit }' "$ENV_FILE"
}

# Escreve (ou acrescenta) KEY='valor'. Plicas e nao aspas: entre aspas o
# phpdotenv expande ${...}, e uma password com "$" ficava estragada sem aviso.
# O valor vai por variavel de ambiente para nao aparecer na lista de processos.
env_set() {
    case "$2" in
        *"'"*) echo "   O valor tem uma plica ('), que o .env nao aceita aqui. Escolhe outro." >&2; return 1 ;;
    esac
    NOVO_VALOR="$2" awk -v k="$1" -v q="'" '
        BEGIN { v = ENVIRON["NOVO_VALOR"]; feito = 0 }
        index($0, k"=")==1 { print k "=" q v q; feito = 1; next }
        { print }
        END { if (!feito) print k "=" q v q }
    ' "$ENV_FILE" > "$ENV_FILE.tmp"
    cat "$ENV_FILE.tmp" > "$ENV_FILE"   # cat > mantem dono e permissoes do .env
    rm -f "$ENV_FILE.tmp"
}

env_del() {
    awk -v k="$1" 'index($0, k"=")!=1' "$ENV_FILE" > "$ENV_FILE.tmp"
    cat "$ENV_FILE.tmp" > "$ENV_FILE"
    rm -f "$ENV_FILE.tmp"
}

sim() { local r; read -r -p "$1 [s/N] " r; [[ "$r" =~ ^[sS]$ ]]; }

echo "=========================================="
echo "  Rotacao de segredos — $(date)"
echo "=========================================="

# 1. Copia -------------------------------------------------------------------
COPIA="$APP_DIR/.env.antes-rotacao-$(date +%Y%m%d-%H%M%S)"
cp -p "$ENV_FILE" "$COPIA"
chmod 600 "$COPIA"
echo "==> Copia do .env em $COPIA (apagar quando estiver tudo confirmado)"

# 2. Token exposto -----------------------------------------------------------
echo
echo "==> Token 7 (o que estava em agents/backup_agent/agent.yaml)"
artisan tinker --execute='
$t = \Laravel\Sanctum\PersonalAccessToken::find(7);
if (! $t) { echo "   ja nao existe\n"; return; }
$a = $t->tokenable;
echo "   pertence a: " . ($a?->name ?? "?") . " (" . class_basename($t->tokenable_type) . " #" . $t->tokenable_id . ")\n";
echo "   ultimo uso: " . ($t->last_used_at ?? "nunca") . "\n";
'
if sim "   Revogar o token 7?"; then
    artisan tinker --execute='\Laravel\Sanctum\PersonalAccessToken::whereKey(7)->delete(); echo "   revogado\n";'
fi

# 3. ntfy --------------------------------------------------------------------
echo
if sim "==> Gerar um topico ntfy novo?"; then
    TOPICO="ateneya-$(openssl rand -hex 16)"
    env_set NTFY_TOPIC "$TOPICO"
    echo "   Topico novo:  $TOPICO"
    echo "   Na app ntfy do telemovel: apagar a subscricao antiga e subscrever este."
fi

# 4. APP_KEY -----------------------------------------------------------------
echo
if sim "==> Trocar a APP_KEY (as sessoes abertas caem; credenciais e syncs sao recifrados)?"; then
    ANTIGA="$(env_get APP_KEY)"
    NOVA="$(artisan key:generate --show | tail -n 1)"
    [[ "$NOVA" == base64:* ]] || { echo "   key:generate nao devolveu uma chave — nada mudou." >&2; exit 1; }
    env_set APP_PREVIOUS_KEYS "$ANTIGA"
    env_set APP_KEY "$NOVA"
    artisan config:cache >/dev/null
    artisan seguranca:recifrar || true
    if sim "   Gravar a recifragem?"; then
        if artisan seguranca:recifrar --confirmar; then
            env_del APP_PREVIOUS_KEYS
            artisan config:cache >/dev/null
            echo "   APP_KEY trocada e APP_PREVIOUS_KEYS retirada."
        else
            echo "   Ficaram valores por ler: a APP_PREVIOUS_KEYS fica no .env ate se ver porque." >&2
        fi
    else
        echo "   Nao gravei. A chave antiga continua em APP_PREVIOUS_KEYS — voltar a correr este passo."
    fi
fi

# 5. Base de dados -----------------------------------------------------------
echo
echo "==> Password da base de dados"
echo "   Primeiro muda-a no Plesk (Websites & Domains > Databases > User Management)."
if sim "   Ja mudaste e queres escreve-la no .env?"; then
    read -r -s -p "   Nova password (nao aparece): " DBPASS; echo
    env_set DB_PASSWORD "$DBPASS" || exit 1
    unset DBPASS
    artisan config:cache >/dev/null
    if artisan migrate:status >/dev/null 2>&1; then
        echo "   Ligacao a base de dados OK."
    else
        echo "   ⚠️ A app NAO consegue ligar a base de dados com a password nova." >&2
        echo "   Repor:  cp -p $COPIA $ENV_FILE && sudo -u $OWNER $PHP artisan config:cache" >&2
        echo "   (atencao: isso desfaz tambem os passos 3 e 4)" >&2
    fi
fi

# 6. Email -------------------------------------------------------------------
echo
echo "==> Password de $(env_get FATURAS_EMAIL_USERNAME)"
echo "   Primeiro muda-a no servidor de email."
if sim "   Ja mudaste e queres escreve-la no .env?"; then
    read -r -s -p "   Nova password (nao aparece): " MAILPASS; echo
    env_set FATURAS_EMAIL_PASSWORD "$MAILPASS" || exit 1
    unset MAILPASS
    artisan config:cache >/dev/null
    artisan faturas:importar-email --teste || echo "   ⚠️ O teste de ligacao ao email falhou." >&2
fi

# 7. Copias expostas ---------------------------------------------------------
echo
for f in env .env.bak-20260702170904 .env.bak.20260702223808; do
    if [ -f "$APP_DIR/$f" ]; then
        rm -f "$APP_DIR/$f" && echo "==> Apagado $f"
    fi
done

echo
echo "Feito. Falta:"
echo "  - subscrever o topico ntfy novo no telemovel e correr:  sudo -u $OWNER $PHP artisan ntfy:test"
echo "  - entrar outra vez no painel (as sessoes caem com a APP_KEY nova)"
echo "  - apagar $COPIA quando estiver tudo confirmado"
