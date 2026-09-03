#!/usr/bin/env bash
#
# Cria (ou apaga) uma conta de WordPress em TODOS os sites de um servidor.
#
#   ./conta-estagio.sh --listar                # o que encontrou, sem tocar em nada
#   ./conta-estagio.sh --criar --confirmar     # cria em todos
#   ./conta-estagio.sh --password --confirmar  # troca a password em todos
#   ./conta-estagio.sh --revogar --confirmar   # apaga de todos
#
# Copia-se para o servidor e corre-se lá:
#   scp conta-estagio.sh root@<vps>:/root/ && ssh root@<vps> 'bash /root/conta-estagio.sh --listar'
#
# Nao instala nada de forma permanente: se faltar o wp-cli, deixa um .phar em
# /tmp. Serve tanto os Plesk como as maquinas onde os sites vivem em
# /var/www/<dominio>/public_html.
#
# A PASSWORD NAO ESTA AQUI. Vem da variavel WP_USER_PASSWORD ou e pedida no
# arranque sem aparecer no ecra, e segue por stdin do wp-cli — nao fica no
# historico da shell nem na lista de processos do servidor.

set -uo pipefail

UTILIZADOR="estagio2026"
EMAIL="estagio@codebehind.pt"
NOME="Estagio Codebehind"
PAPEL="administrator"
ACCAO=""
CONFIRMAR=0

# Onde procurar. Cobre Plesk (/var/www/vhosts/<dominio>/httpdocs), o feitio
# solto (/var/www/<dominio>/public_html) e cPanel (/home/<user>/public_html).
RAIZES=(/var/www/vhosts /var/www /home /srv/www /usr/share/nginx)

# Pastas que tem um wp-config.php mas nao sao um site a serio — copias de
# seguranca de plugins, restos de migracoes. Criar uma conta la dentro nao
# faz mal nenhum, mas suja o relatorio e faz perder tempo.
IGNORAR='/(wp-content/(uploads|cache|upgrade|ai1wm-backups|updraft|backups?|wpvivid[^/]*)|\.wp-toolkit-[^/]*|backup[^/]*|old|_old|copia)/'

vermelho() { printf '\033[31m%s\033[0m\n' "$*"; }
verde()    { printf '\033[32m%s\033[0m\n' "$*"; }

uso() {
    sed -n '2,20p' "$0" | sed 's/^# \?//'
    exit 1
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --criar)       ACCAO="criar" ;;
        --revogar)     ACCAO="revogar" ;;
        --password)    ACCAO="password" ;;
        --listar)      ACCAO="listar" ;;
        --confirmar)   CONFIRMAR=1 ;;
        --utilizador)  UTILIZADOR="$2"; shift ;;
        --email)       EMAIL="$2"; shift ;;
        --papel)       PAPEL="$2"; shift ;;
        --nome)        NOME="$2"; shift ;;
        -h|--help)     uso ;;
        *) vermelho "opcao desconhecida: $1"; uso ;;
    esac
    shift
done

[[ -z "$ACCAO" ]] && uso

# --------------------------------------------------------------------------
# PHP e wp-cli
# --------------------------------------------------------------------------
# Nos Plesk o php nao esta no PATH de uma sessao SSH nao interactiva: vive em
# /opt/plesk/php/<versao>/bin. Procura-se so quando o `php` falta mesmo, para
# nao trocar a versao aos servidores onde ja funciona.

encontrar_php() {
    if command -v php >/dev/null 2>&1; then echo "php"; return; fi

    local candidato
    for candidato in $(ls -1d /opt/plesk/php/*/bin/php 2>/dev/null | sort -V -r) \
                     $(ls -1d /usr/local/bin/ea-php*/root/usr/bin/php 2>/dev/null | sort -V -r); do
        [[ -x "$candidato" ]] && { echo "$candidato"; return; }
    done

    echo ""
}

PHP="$(encontrar_php)"
if [[ -z "$PHP" ]]; then
    vermelho "ERRO: nao encontrei nenhum PHP neste servidor."
    exit 1
fi

if command -v wp >/dev/null 2>&1 && wp --info >/dev/null 2>&1; then
    WP="wp"
else
    echo "==> sem wp-cli no PATH; a deixar um wp-cli.phar em /tmp"
    if ! curl -fsSL -o /tmp/wp-cli.phar \
         https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; then
        vermelho "ERRO: nao consegui descarregar o wp-cli."
        exit 1
    fi
    chmod +x /tmp/wp-cli.phar
    WP="$PHP /tmp/wp-cli.phar"
fi

wpc() { $WP --path="$1" --allow-root --skip-plugins --skip-themes "${@:2}" 2>&1; }

# --------------------------------------------------------------------------
# Descobrir os sites
# --------------------------------------------------------------------------

echo "==> a procurar instalacoes de WordPress..."
SITES=()
while IFS= read -r ficheiro; do
    [[ -z "$ficheiro" ]] && continue
    echo "$ficheiro" | grep -Eq "$IGNORAR" && continue
    pasta="$(dirname "$ficheiro")"
    # Um wp-config.php pode estar um nivel acima da raiz do site; so conta
    # como instalacao se o wp-includes estiver ao lado dele.
    [[ -d "$pasta/wp-includes" ]] || continue
    SITES+=("$pasta")
done < <(
    for raiz in "${RAIZES[@]}"; do
        [[ -d "$raiz" ]] || continue
        find "$raiz" -maxdepth 5 -name wp-config.php -type f 2>/dev/null
    done | sort -u
)

if [[ ${#SITES[@]} -eq 0 ]]; then
    vermelho "Nao encontrei nenhum WordPress em: ${RAIZES[*]}"
    exit 1
fi

echo
echo "Servidor:    $(hostname)"
echo "Conta:       $UTILIZADOR <$EMAIL>   papel: $PAPEL"
echo "Encontrados: ${#SITES[@]} sites"
for pasta in "${SITES[@]}"; do
    url="$(wpc "$pasta" option get siteurl | head -1)"
    [[ "$url" == *Error* || -z "$url" ]] && url="(nao respondeu — ver a mao)"
    printf '  · %-58s %s\n' "$pasta" "$url"
done

if [[ "$ACCAO" == "listar" ]]; then
    echo
    echo "(--listar: nao se tocou em nada)"
    exit 0
fi

if [[ "$CONFIRMAR" -ne 1 ]]; then
    echo
    vermelho "Falta --confirmar. Nada foi alterado."
    exit 1
fi

# --------------------------------------------------------------------------
# Password
# --------------------------------------------------------------------------

PASSWORD="${WP_USER_PASSWORD:-}"
if [[ ( "$ACCAO" == "criar" || "$ACCAO" == "password" ) && -z "$PASSWORD" ]]; then
    echo
    read -rsp "Password da conta (nao aparece no ecra): " PASSWORD
    echo
fi

if [[ ( "$ACCAO" == "criar" || "$ACCAO" == "password" ) && ${#PASSWORD} -lt 12 ]]; then
    vermelho "Password demasiado curta — no minimo 12 caracteres."
    exit 1
fi

# --------------------------------------------------------------------------
# Fazer
# --------------------------------------------------------------------------

OK=0
FALHOU=0
FALHAS=()

echo
for pasta in "${SITES[@]}"; do
    nome="$(basename "$(dirname "$pasta")")/$(basename "$pasta")"

    if [[ "$ACCAO" == "criar" ]]; then
        # Procura-se pelo email e pelo login: o mesmo site pode ja ter um
        # `estagio` de outra altura, com outro endereco.
        existente=""
        for chave in "$EMAIL" "$UTILIZADOR"; do
            id="$(wpc "$pasta" user get "$chave" --field=ID | head -1)"
            [[ "$id" =~ ^[0-9]+$ ]] && { existente="$chave"; break; }
        done

        if [[ -n "$existente" ]]; then
            saida="$(wpc "$pasta" user set-role "$existente" "$PAPEL")"
            if [[ $? -eq 0 ]]; then
                verde "  ok  $nome — ja existia, papel confirmado como $PAPEL"
                OK=$((OK + 1))
            else
                vermelho "  !!  $nome — $saida"
                FALHOU=$((FALHOU + 1)); FALHAS+=("$nome")
            fi
            continue
        fi

        saida="$(printf '%s\n' "$PASSWORD" | $WP --path="$pasta" --allow-root \
                 --skip-plugins --skip-themes user create \
                 "$UTILIZADOR" "$EMAIL" --role="$PAPEL" \
                 --display_name="$NOME" --prompt=user_pass 2>&1)"

        if [[ $? -eq 0 ]]; then
            verde "  ok  $nome — criada com papel $PAPEL"
            OK=$((OK + 1))
        else
            vermelho "  !!  $nome — $(echo "$saida" | tail -2 | tr '\n' ' ')"
            FALHOU=$((FALHOU + 1)); FALHAS+=("$nome")
        fi

    elif [[ "$ACCAO" == "password" ]]; then
        alvo=""
        for chave in "$UTILIZADOR" "$EMAIL"; do
            id="$(wpc "$pasta" user get "$chave" --field=ID | head -1)"
            [[ "$id" =~ ^[0-9]+$ ]] && { alvo="$chave"; break; }
        done

        if [[ -z "$alvo" ]]; then
            echo "  --  $nome — a conta nao existe aqui"
            OK=$((OK + 1))
            continue
        fi

        saida="$(printf '%s\n' "$PASSWORD" | $WP --path="$pasta" --allow-root \
                 --skip-plugins --skip-themes user update "$alvo" --prompt=user_pass 2>&1)"

        if [[ $? -eq 0 ]]; then
            verde "  ok  $nome — password trocada"
            OK=$((OK + 1))
        else
            vermelho "  !!  $nome — $(echo "$saida" | tail -2 | tr '\n' ' ')"
            FALHOU=$((FALHOU + 1)); FALHAS+=("$nome")
        fi

    else
        alvo=""
        for chave in "$EMAIL" "$UTILIZADOR"; do
            id="$(wpc "$pasta" user get "$chave" --field=ID | head -1)"
            [[ "$id" =~ ^[0-9]+$ ]] && { alvo="$chave"; break; }
        done

        if [[ -z "$alvo" ]]; then
            echo "  --  $nome — nao existia"
            OK=$((OK + 1))
            continue
        fi

        # O --reassign diz para quem passa o que a conta publicou. Sem isso o
        # wp-cli apaga os conteudos dela, e o trabalho dos estagiarios nao se
        # deita fora com a conta.
        destino="$(wpc "$pasta" user list --role=administrator --field=ID --number=5 \
                   | grep -E '^[0-9]+$' | grep -v "^$(wpc "$pasta" user get "$alvo" --field=ID | head -1)$" | head -1)"

        if [[ -z "$destino" ]]; then
            vermelho "  !!  $nome — nao ha outro administrador para receber o conteudo; NAO apagada"
            FALHOU=$((FALHOU + 1)); FALHAS+=("$nome")
            continue
        fi

        saida="$(wpc "$pasta" user delete "$alvo" --reassign="$destino" --yes)"
        if [[ $? -eq 0 ]]; then
            verde "  ok  $nome — apagada (conteudo passou para o utilizador $destino)"
            OK=$((OK + 1))
        else
            vermelho "  !!  $nome — $saida"
            FALHOU=$((FALHOU + 1)); FALHAS+=("$nome")
        fi
    fi
done

echo
echo "$OK de ${#SITES[@]} sites tratados."
if [[ "$FALHOU" -gt 0 ]]; then
    vermelho "Falharam ${FALHOU}: ${FALHAS[*]}"
    exit 1
fi

verde "Feito."
