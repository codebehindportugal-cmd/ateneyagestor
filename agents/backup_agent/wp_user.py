#!/usr/bin/env python3
"""
Cria (ou revoga) uma conta de WordPress em todos os sites de uma vez.

    python3 wp_user.py --listar                 # o que vai fazer, sem tocar em nada
    python3 wp_user.py --criar --confirmar      # cria a conta em todos os sites
    python3 wp_user.py --revogar --confirmar    # apaga a conta de todos os sites

Corre na mesma maquina que o agent_sync.py e usa o mesmo agent.yaml e o mesmo
secrets.yaml — as chaves SSH nunca passam pelo painel. A lista de sites vem do
painel (GET /api/agent/config), por isso nao ha aqui nenhuma lista a envelhecer.

A PASSWORD NAO VIVE NESTE FICHEIRO. E pedida no arranque (sem aparecer no ecra)
ou lida da variavel de ambiente WP_USER_PASSWORD. Nao e escrita em disco, nao
vai para o log e nao entra na linha de comandos do servidor de forma visivel:
segue por stdin do wp-cli, para nao ficar no `ps` de quem estiver na maquina.

AVISO, para quem vier a este ficheiro daqui a uns meses: uma conta partilhada
por varias pessoas faz com que o WordPress atribua tudo o que for feito ao
mesmo utilizador. Nao ha maneira de saber depois quem alterou o que. Se o
objectivo for saber quem fez o que, criam-se contas nominais — este mesmo
script faz isso, e o --utilizador aceita qualquer email.
"""

from __future__ import annotations

import argparse
import getpass
import logging
import os
import shlex
import sys
from pathlib import Path

from wp_update import (
    AGENT_FILE,
    SECRETS_FILE,
    ErroDeActualizacao,
    Servidor,
    WordPress,
    api,
    chave_ssh,
    load_yaml,
)

log = logging.getLogger("wp_user")

PAPEIS = ["subscriber", "contributor", "author", "editor", "administrator"]


def sites_wordpress(config: dict) -> list[tuple[dict, dict]]:
    """
    Os sites onde da para correr wp-cli: os de tipo `wordpress`, que sao os
    unicos que o painel manda com `wp_root`.

    Os de tipo `plesk` sao dominios geridos pelo Plesk e nao expoem aqui o
    caminho do WordPress — ficam de fora e sao listados no fim, para nao dar a
    ideia de que a conta ficou criada em todo o lado quando nao ficou.
    """
    pares = []

    for servidor in config.get("servers", []):
        for site in servidor.get("sites", []):
            if site.get("type") == "wordpress" and site.get("wp_root"):
                pares.append((servidor, site))

    return pares


def sites_de_fora(config: dict) -> list[str]:
    fora = []

    for servidor in config.get("servers", []):
        for site in servidor.get("sites", []):
            if site.get("type") != "wordpress" or not site.get("wp_root"):
                fora.append(f"{site.get('name')} ({site.get('type')})")

    return fora


def existe(wp: WordPress, utilizador: str) -> bool:
    proc = wp.correr(f"user get {shlex.quote(utilizador)} --field=ID", timeout=120)
    return proc.returncode == 0


def criar(wp: WordPress, utilizador: str, papel: str, password: str, nome: str) -> str:
    """
    Devolve uma frase curta com o que aconteceu. A password vai por stdin
    (`--prompt=user_pass`) para nao ficar visivel na lista de processos do
    servidor — que qualquer utilizador da maquina consegue ler.
    """
    if existe(wp, utilizador):
        proc = wp.correr(
            f"user set-role {shlex.quote(utilizador)} {shlex.quote(papel)}",
            timeout=120,
        )
        if proc.returncode != 0:
            raise ErroDeActualizacao((proc.stderr or proc.stdout or "").strip()[:300])
        return f"ja existia — papel confirmado como {papel}"

    comando = wp.cmd(
        f"user create {shlex.quote(utilizador)} {shlex.quote(utilizador)} "
        f"--role={shlex.quote(papel)} "
        f"--display_name={shlex.quote(nome)} "
        f"--prompt=user_pass"
    )

    import subprocess

    proc = subprocess.run(
        wp.servidor.comando_ssh() + ["--", comando],
        input=password + "\n",
        capture_output=True,
        text=True,
        timeout=180,
    )

    if proc.returncode != 0:
        raise ErroDeActualizacao((proc.stderr or proc.stdout or "").strip()[:300])

    return f"criada com papel {papel}"


def revogar(wp: WordPress, utilizador: str) -> str:
    if not existe(wp, utilizador):
        return "nao existia"

    # `--reassign` obriga a dizer para quem passa o conteudo. Sem isso o wp-cli
    # apaga os posts do utilizador, e o que os estagiarios escreveram nao se
    # deita fora com a conta deles.
    admin = wp.correr("user list --role=administrator --field=ID --number=1", timeout=120)
    destino = (admin.stdout or "").strip().splitlines()
    destino = destino[0].strip() if destino else ""

    if not destino:
        return "NAO APAGADA — nao encontrei outro administrador para receber o conteudo"

    proc = wp.correr(
        f"user delete {shlex.quote(utilizador)} --reassign={shlex.quote(destino)} --yes",
        timeout=180,
    )

    if proc.returncode != 0:
        raise ErroDeActualizacao((proc.stderr or proc.stdout or "").strip()[:300])

    return f"apagada (conteudo passou para o utilizador {destino})"


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__,
                                     formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--utilizador", default="estagio@codebehind.pt",
                        help="email/login da conta (por omissao: estagio@codebehind.pt)")
    parser.add_argument("--nome", default="Estagio Codebehind",
                        help="nome a mostrar no WordPress")
    parser.add_argument("--papel", default="administrator", choices=PAPEIS,
                        help="papel no WordPress (por omissao: administrator)")
    parser.add_argument("--criar", action="store_true", help="criar a conta em todos os sites")
    parser.add_argument("--revogar", action="store_true", help="apagar a conta de todos os sites")
    parser.add_argument("--listar", action="store_true",
                        help="so mostrar em que sites e que ia mexer")
    parser.add_argument("--so", default=None,
                        help="limitar a sites cujo nome contenha este texto")
    parser.add_argument("--confirmar", action="store_true",
                        help="sem isto nao se escreve nada — e de propósito")
    parser.add_argument("-v", "--verbose", action="store_true")
    args = parser.parse_args()

    logging.basicConfig(
        level=logging.DEBUG if args.verbose else logging.INFO,
        format="%(levelname)s %(message)s",
    )

    if sum([args.criar, args.revogar, args.listar]) != 1:
        parser.error("escolhe uma coisa: --listar, --criar ou --revogar")

    agent_cfg = load_yaml(AGENT_FILE, "agent.yaml")
    secrets = load_yaml(SECRETS_FILE, "secrets.yaml")

    resposta = api(agent_cfg, "GET", "/api/agent/config")
    if resposta.status_code != 200:
        raise SystemExit(f"o painel respondeu {resposta.status_code}: {resposta.text[:200]}")
    config = resposta.json()

    pares = sites_wordpress(config)
    if args.so:
        pares = [(s, w) for s, w in pares if args.so.lower() in (w.get("name") or "").lower()]

    fora = sites_de_fora(config)

    print(f"\nConta: {args.utilizador}   papel: {args.papel}")
    print(f"Sites WordPress alcançáveis: {len(pares)}")
    for servidor, site in pares:
        print(f"  · {site['name']:<42} {servidor['name']} ({servidor['host']})")

    if fora:
        print(f"\nFora do alcance deste script ({len(fora)}) — tratar a mão:")
        for nome in fora:
            print(f"  · {nome}")

    if args.listar:
        print("\n(--listar: nao se tocou em nada)")
        return 0

    if not args.confirmar:
        print("\nFalta --confirmar. Nada foi alterado.")
        return 1

    password = ""
    if args.criar:
        password = os.environ.get("WP_USER_PASSWORD") or getpass.getpass(
            "\nPassword da conta (nao aparece no ecra, tira-a do gestor de passwords): "
        )
        if len(password) < 12:
            raise SystemExit("password demasiado curta — no minimo 12 caracteres")

    resultados: list[tuple[str, str, str]] = []

    for servidor, site in pares:
        nome = site["name"]
        try:
            segredo = chave_ssh(secrets, servidor.get("agent_secret_ref") or servidor["name"])
            ligacao = Servidor(servidor, segredo)
            wp = WordPress(ligacao, site["wp_root"])
            wp.preparar()

            if args.criar:
                estado = criar(wp, args.utilizador, args.papel, password, args.nome)
            else:
                estado = revogar(wp, args.utilizador)

            resultados.append((nome, "ok", estado))
            print(f"  ✔ {nome}: {estado}")

        except Exception as erro:  # noqa: BLE001 — um site que falha nao pode parar os outros
            resultados.append((nome, "erro", str(erro)[:200]))
            print(f"  ✘ {nome}: {erro}")

    ok = sum(1 for _, e, _ in resultados if e == "ok")
    print(f"\n{ok} de {len(resultados)} sites tratados.")

    falhados = [n for n, e, _ in resultados if e != "ok"]
    if falhados:
        print("Falharam: " + ", ".join(falhados))
        return 1

    return 0


if __name__ == "__main__":
    sys.exit(main())
