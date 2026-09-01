#!/usr/bin/env python3
"""
Actualiza WordPress a partir da fila do painel, com reposicao automatica.

    1. GET  /api/agent/updates/next            -> apanha um pedido
    2. copia de reposicao no PROPRIO servidor  -> ficheiros + base de dados
    3. actualiza UM item de cada vez           -> core, depois plugins, temas
    4. testa o site a seguir a cada um         -> HTTP + wp-cli
    5. o que partir o site e reposto na hora   -> so esse item
    6. POST /api/agent/updates/<id>/finish     -> devolve tudo ao painel

A ideia toda esta no passo 3. Actualizar tudo de uma vez e depois descobrir que
o site esta em baixo obriga a repor tudo, e a nao saber qual dos vinte plugins
foi o culpado. Um de cada vez custa mais uns minutos e da uma resposta em vez
de um encolher de ombros.

A copia fica no proprio servidor, nao no NAS: a graca de ter uma copia e poder
voltar atras em segundos, e puxar do NAS demorava mais do que o estrago.

Corre na mesma maquina que o agent_sync.py e usa o mesmo agent.yaml e o mesmo
secrets.yaml — as chaves SSH nunca passam pelo painel.
"""

from __future__ import annotations

import argparse
import json
import logging
import re
import shlex
import subprocess
import sys
import time
from datetime import datetime, timezone
from pathlib import Path

import requests
import yaml

from backup import SSH_BASE_OPTS

BASE_DIR = Path(__file__).resolve().parent
AGENT_FILE = BASE_DIR / "agent.yaml"
SECRETS_FILE = BASE_DIR / "secrets.yaml"

log = logging.getLogger("wp_update")

# O que, aparecendo no HTML, significa que o site esta partido mesmo que o
# servidor devolva 200. O WordPress moderno esconde o fatal atras de uma
# pagina simpatica — e por isso que "HTTP 200" nao chega como teste.
MARCAS_DE_ERRO = (
    "There has been a critical error",
    "Ocorreu um erro cr",           # "crítico" — sem acento para nao depender do encoding
    "Error establishing a database connection",
    "Fatal error",
    "Parse error:",
    "Cannot redeclare",
    "wp-recovery-mode",
)

UA = "Mozilla/5.0 (compatible; AteneyaUpdater/1.0)"

# O que NAO entra na copia. Sao pastas que nenhuma actualizacao toca e que
# valem gigabytes: os uploads de uma loja, as caches, e os backups que plugins
# como o All-in-One WP Migration ou o UpdraftPlus deixam dentro do wp-content.
#
# Esta lista tem de ser usada TANTO na medicao do espaco COMO no tar. Estavam
# diferentes, e o resultado foi medir 11 GB para copiar uma fraccao disso — e
# recusar comecar por falta de espaco que existia de sobra.
# O que entra na copia: o codigo do WordPress e nada mais. Os ficheiros .php
# da raiz vao a parte, no proprio comando do tar.
PASTAS_A_COPIAR = ["wp-admin", "wp-includes", "wp-content"]

NAO_COPIAR = [
    "wp-content/uploads",
    "wp-content/cache",
    "wp-content/upgrade",
    "wp-content/ai1wm-backups",
    "wp-content/updraft",
    "wp-content/backup",
    "wp-content/backups",
]


class ErroDeActualizacao(Exception):
    """Erro que impede continuar, mas que deixa o site como estava."""


# --------------------------------------------------------------------------
# Configuracao local
# --------------------------------------------------------------------------

def load_yaml(path: Path, what: str) -> dict:
    if not path.is_file():
        raise SystemExit(f"Falta o {what}: {path}")
    return yaml.safe_load(path.read_text(encoding="utf-8")) or {}


def api(agent_cfg: dict, method: str, path: str, **kwargs):
    url = agent_cfg["api_url"].rstrip("/") + path
    headers = {
        "Authorization": f"Bearer {agent_cfg['token']}",
        "Accept": "application/json",
    }
    return requests.request(method, url, headers=headers,
                            timeout=kwargs.pop("timeout", 60), **kwargs)


def chave_ssh(secrets: dict, ref: str) -> dict:
    """
    Traduz o `agent_secret_ref` que veio do painel na chave local. Igual ao que
    o agent_sync.py faz — o painel manda o nome do segredo, nunca o segredo.
    """
    servers = secrets.get("servers") or {}

    if ref not in servers:
        raise ErroDeActualizacao(
            f"sem entrada '{ref}' no secrets.yaml — o servidor nao esta configurado neste agente"
        )

    entry = servers.get(ref) or {}
    key = entry.get("key_path") or secrets.get("default_key_path")

    if not key:
        raise ErroDeActualizacao(f"'{ref}' nao define key_path e nao ha default_key_path")

    if not Path(key).is_file():
        raise ErroDeActualizacao(f"chave SSH nao encontrada em {key}")

    return {"ssh_key": key, **{k: v for k, v in entry.items() if k in ("user", "port", "known_hosts")}}


# --------------------------------------------------------------------------
# SSH
# --------------------------------------------------------------------------

class Servidor:
    def __init__(self, dados: dict, segredo: dict):
        self.host = dados["host"]
        self.port = segredo.get("port") or dados.get("port") or 22
        self.user = segredo.get("user") or dados.get("user") or "root"
        self.key = segredo["ssh_key"]
        self.known_hosts = segredo.get("known_hosts")
        self.nome = dados.get("name") or self.host

    def comando_ssh(self) -> list[str]:
        cmd = ["ssh", "-i", self.key, *SSH_BASE_OPTS]
        if self.port:
            cmd += ["-p", str(self.port)]
        if self.known_hosts:
            cmd += ["-o", f"UserKnownHostsFile={self.known_hosts}"]
        cmd.append(f"{self.user}@{self.host}")
        return cmd

    def correr(self, comando: str, timeout: int = 300) -> subprocess.CompletedProcess:
        log.debug("ssh %s: %s", self.host, comando)
        return subprocess.run(
            self.comando_ssh() + ["--", comando],
            capture_output=True, text=True, timeout=timeout,
        )

    def exigir(self, comando: str, oquee: str, timeout: int = 300) -> str:
        proc = self.correr(comando, timeout=timeout)
        if proc.returncode != 0:
            erro = (proc.stderr or proc.stdout or "").strip()[:400]
            raise ErroDeActualizacao(f"{oquee}: {erro}")
        return proc.stdout


# --------------------------------------------------------------------------
# wp-cli
# --------------------------------------------------------------------------

class WordPress:
    """
    O wp-cli do lado de la, com o caminho ja preso ao site.

    Nem todos os servidores tem o wp instalado — os Plesk trazem o do WP
    Toolkit, os outros podem nao ter nada. Quando falta, deixa-se um wp-cli.phar
    em /tmp: nao se instala nada no servidor de um cliente sem ele saber.
    """

    def __init__(self, servidor: Servidor, wp_root: str):
        self.servidor = servidor
        self.root = wp_root
        self.binario: str | None = None

    def preparar(self) -> str:
        candidatos = ["wp", "/usr/local/bin/wp", "/usr/bin/wp"]

        for candidato in candidatos:
            proc = self.servidor.correr(
                f"command -v {shlex.quote(candidato)} >/dev/null 2>&1 && {shlex.quote(candidato)} --info >/dev/null 2>&1",
                timeout=60,
            )
            if proc.returncode == 0:
                self.binario = candidato
                break

        if not self.binario:
            log.info("sem wp-cli no servidor; a deixar um wp-cli.phar em /tmp")
            proc = self.servidor.correr(
                "command -v php >/dev/null 2>&1 && "
                "curl -fsSL -o /tmp/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar "
                "&& chmod +x /tmp/wp-cli.phar && php /tmp/wp-cli.phar --info >/dev/null 2>&1",
                timeout=180,
            )
            if proc.returncode != 0:
                raise ErroDeActualizacao(
                    "sem wp-cli e nao foi possivel instalar um temporario "
                    f"({(proc.stderr or '').strip()[:200]})"
                )
            self.binario = "php /tmp/wp-cli.phar"

        return self.binario

    def cmd(self, argumentos: str) -> str:
        return f"{self.binario} --path={shlex.quote(self.root)} --allow-root {argumentos}"

    def correr(self, argumentos: str, timeout: int = 600) -> subprocess.CompletedProcess:
        return self.servidor.correr(self.cmd(argumentos), timeout=timeout)

    def json(self, argumentos: str, timeout: int = 300):
        proc = self.correr(f"{argumentos} --format=json", timeout=timeout)
        if proc.returncode != 0:
            raise ErroDeActualizacao(
                f"wp {argumentos}: {(proc.stderr or proc.stdout or '').strip()[:300]}"
            )
        saida = (proc.stdout or "").strip()
        # O wp-cli as vezes cospe avisos antes do JSON.
        inicio = saida.find("[")
        if inicio == -1:
            inicio = saida.find("{")
        if inicio == -1:
            return []
        try:
            return json.loads(saida[inicio:])
        except json.JSONDecodeError:
            return []

    def vivo(self) -> tuple[bool, str]:
        """
        O teste que apanha o que o HTTP nao apanha: se um plugin morre com um
        fatal, isto falha mesmo que a homepage venha em cache.
        """
        proc = self.correr("option get siteurl", timeout=120)
        if proc.returncode != 0:
            return False, (proc.stderr or proc.stdout or "").strip()[:300]
        return True, ""


# --------------------------------------------------------------------------
# Saude do site
# --------------------------------------------------------------------------

def medir_pagina(url: str, timeout: int = 25) -> dict:
    """
    Uma leitura da pagina. Guarda-se o tamanho porque e o sinal mais honesto:
    um site partido devolve muitas vezes 200 com um corpo minusculo.
    """
    separador = "&" if "?" in url else "?"
    pedido = f"{url}{separador}_ateneya={int(time.time())}"  # fura a cache

    try:
        resposta = requests.get(pedido, timeout=timeout, allow_redirects=True,
                                headers={"User-Agent": UA}, verify=False)
    except requests.RequestException as e:
        return {"url": url, "codigo": 0, "tamanho": 0, "erro": str(e)[:200]}

    corpo = resposta.text or ""
    marcas = [m for m in MARCAS_DE_ERRO if m.lower() in corpo.lower()]

    return {
        "url": url,
        "codigo": resposta.status_code,
        "tamanho": len(corpo),
        "marcas": marcas,
    }


def medir_site(urls: list[str]) -> dict:
    return {"paginas": [medir_pagina(u) for u in urls]}


def comparar(antes: dict, agora: dict, encolhimento_maximo: float) -> tuple[bool, str]:
    """
    Decide se o site continua de pe. Compara-se sempre com o estado de ANTES,
    nunca com um ideal: um site que ja dava 404 numa pagina antes da
    actualizacao nao passa a ser culpa da actualizacao.
    """
    antes_por_url = {p["url"]: p for p in antes.get("paginas", [])}

    for pagina in agora.get("paginas", []):
        anterior = antes_por_url.get(pagina["url"])
        if not anterior:
            continue

        if pagina.get("marcas"):
            return False, f"{pagina['url']}: {pagina['marcas'][0]}"

        # So conta como queda se ANTES estava bem. Uma pagina que ja estava
        # em baixo continua em baixo — nao e novidade nem motivo de reposicao.
        estava_bem = anterior["codigo"] < 400 and anterior["codigo"] != 0
        esta_bem = pagina["codigo"] < 400 and pagina["codigo"] != 0

        if estava_bem and not esta_bem:
            return False, f"{pagina['url']}: HTTP {anterior['codigo']} passou a {pagina['codigo'] or 'sem resposta'}"

        if estava_bem and anterior["tamanho"] > 500:
            minimo = anterior["tamanho"] * (1 - encolhimento_maximo)
            if pagina["tamanho"] < minimo:
                return False, (
                    f"{pagina['url']}: a pagina encolheu de {anterior['tamanho']} "
                    f"para {pagina['tamanho']} bytes"
                )

    return True, ""


# --------------------------------------------------------------------------
# Copia de reposicao
# --------------------------------------------------------------------------

def espaco_suficiente(servidor: Servidor, wp_root: str, destino: str, factor: float) -> tuple[bool, str]:
    """
    Um disco cheio a meio do tar deixa o site sem copia E prestes a ser
    actualizado. Mede-se antes, e desiste-se antes.
    """
    excluir = " ".join(f"--exclude={shlex.quote(c)}" for c in NAO_COPIAR)

    # Mede-se EXACTAMENTE as mesmas pastas que o tar leva — nem o wp_root
    # inteiro, nem uma lista de exclusoes diferente. Medir uma coisa e copiar
    # outra da numeros que nao querem dizer nada: foi assim que um site de 1 GB
    # apareceu como 11 GB e a actualizacao se recusou a comecar.
    proc = servidor.correr(
        f"cd {shlex.quote(wp_root)} && "
        f"du -ck {excluir} {' '.join(PASTAS_A_COPIAR)} 2>/dev/null | tail -1 | cut -f1; "
        f"mkdir -p {shlex.quote(destino)} 2>/dev/null; "
        f"df -Pk {shlex.quote(destino)} | tail -1 | awk '{{print $4}}'",
        timeout=300,
    )

    linhas = [l.strip() for l in (proc.stdout or "").splitlines() if l.strip()]
    if len(linhas) < 2:
        return True, "nao foi possivel medir o espaco; a continuar"

    try:
        tamanho_kb = int(linhas[0])
        livre_kb = int(linhas[-1])
    except ValueError:
        return True, "nao foi possivel medir o espaco; a continuar"

    preciso = tamanho_kb * factor
    if livre_kb < preciso:
        return False, (
            f"sem espaco: e preciso cerca de {int(preciso) // 1024} MB "
            f"(o que se copia ocupa {tamanho_kb // 1024} MB) e so ha "
            f"{livre_kb // 1024} MB livres em {destino}"
        )

    return True, f"espaco ok: {tamanho_kb // 1024} MB a copiar, {livre_kb // 1024} MB livres"


def tirar_copia(servidor: Servidor, wp: WordPress, destino: str) -> str:
    """
    Ficheiros e base de dados, no proprio servidor.

    Os uploads ficam de fora de proposito: sao a maior parte do disco e nenhuma
    actualizacao lhes toca. Sem isso, copiar uma loja com 8 GB de fotografias
    demorava mais do que a actualizacao inteira.
    """
    carimbo = datetime.now(timezone.utc).strftime("%Y-%m-%d_%H%M%S")
    pasta = f"{destino.rstrip('/')}/{carimbo}"

    servidor.exigir(f"mkdir -p {shlex.quote(pasta)}", "criar a pasta da copia", timeout=60)

    proc = wp.correr(f"db export - --single-transaction --quick | gzip -c > {shlex.quote(pasta + '/database.sql.gz')}",
                     timeout=1800)
    if proc.returncode != 0:
        raise ErroDeActualizacao(
            f"nao foi possivel copiar a base de dados: {(proc.stderr or '').strip()[:300]}"
        )

    servidor.exigir(
        f"cd {shlex.quote(wp.root)} && tar czf {shlex.quote(pasta + '/files.tar.gz')} "
        + " ".join(f"--exclude={shlex.quote(c)}" for c in NAO_COPIAR) + " "
        + " ".join(PASTAS_A_COPIAR) + " "
        f"$(ls -1 *.php 2>/dev/null | tr '\\n' ' ')",
        "copiar os ficheiros", timeout=1800,
    )

    # Um tar de 0 bytes e a maneira classica de descobrir tarde de mais que a
    # copia nunca existiu.
    proc = servidor.correr(f"stat -c %s {shlex.quote(pasta + '/files.tar.gz')}", timeout=60)
    try:
        if int((proc.stdout or "0").strip()) < 10240:
            raise ValueError
    except ValueError:
        raise ErroDeActualizacao("a copia dos ficheiros saiu vazia — nao se avanca sem copia")

    return pasta


def limpar_copias_antigas(servidor: Servidor, destino: str, dias: int) -> None:
    servidor.correr(
        f"find {shlex.quote(destino)} -maxdepth 1 -type d -mtime +{int(dias)} "
        f"-name '20*' -exec rm -rf {{}} + 2>/dev/null || true",
        timeout=300,
    )


def contar_novidades(wp: WordPress, desde: str) -> tuple[int, list[str]]:
    """
    O que entrou no site desde a copia.

    E isto que decide se repor a base de dados custa alguma coisa. Uma
    encomenda entrada as 3 da manha nao aparece em lado nenhum se a base for
    reposta por cima — e nao ha como saber que existiu. Por isso conta-se
    antes, e o numero manda.

    `desde` em UTC, no formato do MySQL. As tabelas do WooCommerce podem nao
    existir (loja sem HPOS, ou site sem loja): uma consulta que falha conta
    zero, nunca rebenta.
    """
    prefixo = (wp.correr("config get table_prefix", timeout=120).stdout or "wp_").strip() or "wp_"

    consultas = [
        ("encomendas", f"SELECT COUNT(*) FROM {prefixo}wc_orders WHERE date_created_gmt > '{desde}'"),
        ("encomendas", f"SELECT COUNT(*) FROM {prefixo}posts WHERE post_type LIKE 'shop_order%' AND post_date_gmt > '{desde}'"),
        ("comentarios", f"SELECT COUNT(*) FROM {prefixo}comments WHERE comment_date_gmt > '{desde}'"),
        ("utilizadores", f"SELECT COUNT(*) FROM {prefixo}users WHERE user_registered > '{desde}'"),
        ("conteudos", f"SELECT COUNT(*) FROM {prefixo}posts WHERE post_date_gmt > '{desde}' "
                      f"AND post_status NOT IN ('auto-draft','inherit','trash') AND post_type NOT LIKE 'shop_order%'"),
    ]

    total = 0
    detalhe: dict[str, int] = {}

    for etiqueta, sql in consultas:
        proc = wp.correr(f"db query {shlex.quote(sql)} --skip-column-names", timeout=180)
        if proc.returncode != 0:
            continue  # tabela inexistente — nao e erro, e um site sem loja

        try:
            quantos = int((proc.stdout or "0").strip().splitlines()[-1])
        except (ValueError, IndexError):
            continue

        if quantos > 0:
            detalhe[etiqueta] = detalhe.get(etiqueta, 0) + quantos
            total += quantos

    return total, [f"{n} {etiqueta}" for etiqueta, n in detalhe.items()]


def repor_base_de_dados(servidor: Servidor, wp: WordPress, copia: str) -> tuple[bool, str]:
    """
    Ultimo recurso, e so isso.

    Repor a base de dados desfaz as migracoes que os plugins correm ao
    actualizar — que e exactamente o que e preciso quando repor os ficheiros
    nao chegou.

    Antes de repor guarda o estado actual noutro dump. Sem isso, isto era um
    caminho sem volta: a versao partida da base tem na mesma as encomendas que
    entretanto entraram, e essas nao existem em mais lado nenhum.
    """
    dump = f"{copia}/database.sql.gz"

    proc = servidor.correr(f"test -s {shlex.quote(dump)}", timeout=60)
    if proc.returncode != 0:
        return False, "a copia da base de dados nao existe ou esta vazia"

    guardado = f"{copia}/database-antes-de-repor.sql.gz"
    proc = wp.correr(f"db export - --single-transaction --quick | gzip -c > {shlex.quote(guardado)}", timeout=1800)
    if proc.returncode != 0:
        return False, ("nao foi possivel guardar a base actual antes de repor, "
                       "por isso nao se repos: " + (proc.stderr or "").strip()[:200])

    proc = servidor.correr(
        f"gunzip -c {shlex.quote(dump)} | {wp.cmd('db import -')}",
        timeout=1800,
    )

    if proc.returncode != 0:
        return False, (proc.stderr or proc.stdout or "").strip()[:400]

    wp.correr("cache flush", timeout=120)

    return True, ""


def repor(servidor: Servidor, wp_root: str, copia: str, caminhos: list[str]) -> None:
    """
    Repoe caminhos concretos a partir do tar. Apaga primeiro: extrair por cima
    deixava ficheiros novos da versao partida no meio dos antigos.
    """
    for caminho in caminhos:
        alvo = f"{wp_root.rstrip('/')}/{caminho}"
        servidor.correr(f"rm -rf {shlex.quote(alvo)}", timeout=120)
        servidor.correr(
            f"cd {shlex.quote(wp_root)} && tar xzf {shlex.quote(copia + '/files.tar.gz')} "
            f"{shlex.quote(caminho)} 2>/dev/null || true",
            timeout=600,
        )


# --------------------------------------------------------------------------
# O trabalho
# --------------------------------------------------------------------------

class Relatorio:
    """Log que vai sendo enviado ao painel enquanto corre."""

    def __init__(self, agent_cfg: dict, update_id: int):
        self.agent_cfg = agent_cfg
        self.update_id = update_id
        self.linhas: list[str] = []

    def __call__(self, texto: str) -> None:
        carimbo = datetime.now().strftime("%H:%M:%S")
        linha = f"[{carimbo}] {texto}"
        self.linhas.append(linha)
        log.info(texto)

        try:
            api(self.agent_cfg, "POST", f"/api/agent/updates/{self.update_id}/progress",
                json={"log": linha}, timeout=20)
        except requests.RequestException:
            # O painel pode estar em baixo; nao e motivo para parar uma
            # actualizacao a meio, que e o pior sitio para parar.
            pass

    def texto(self) -> str:
        return "\n".join(self.linhas)


def itens_a_actualizar(wp: WordPress, diz) -> list[dict]:
    """
    Core primeiro, depois plugins, depois temas.

    O core vem a frente porque um plugin novo pode exigir um core novo: se
    fosse ao contrario e o core partisse, ficavamos com plugins a frente de um
    core reposto — pior do que o problema que se queria evitar.
    """
    itens: list[dict] = []

    try:
        core = wp.json("core check-update")
        for actualizacao in core or []:
            if actualizacao.get("update_type") in (None, "major", "minor"):
                itens.append({
                    "tipo": "core",
                    "slug": "wordpress",
                    "de": None,
                    "para": actualizacao.get("version"),
                })
                break
    except ErroDeActualizacao as e:
        diz(f"nao foi possivel ver actualizacoes do core: {e}")

    for tipo, comando in (("plugin", "plugin list"), ("tema", "theme list")):
        alvo = "plugin" if tipo == "plugin" else "theme"
        try:
            lista = wp.json(f"{alvo} list --update=available --fields=name,version,update_version")
        except ErroDeActualizacao as e:
            diz(f"nao foi possivel listar {tipo}s: {e}")
            continue

        for item in lista or []:
            itens.append({
                "tipo": tipo,
                "slug": item.get("name"),
                "de": item.get("version"),
                "para": item.get("update_version"),
            })

    return itens


def caminhos_do_item(item: dict) -> list[str]:
    """Que pastas repor quando este item parte o site."""
    if item["tipo"] == "core":
        return ["wp-admin", "wp-includes"]

    pasta = "plugins" if item["tipo"] == "plugin" else "themes"
    return [f"wp-content/{pasta}/{item['slug']}"]


def actualizar_item(wp: WordPress, item: dict) -> tuple[bool, str]:
    alvo = {"core": "core update", "plugin": "plugin update", "tema": "theme update"}[item["tipo"]]
    argumentos = alvo if item["tipo"] == "core" else f"{alvo} {shlex.quote(item['slug'])}"

    proc = wp.correr(argumentos, timeout=900)

    if proc.returncode != 0:
        return False, (proc.stderr or proc.stdout or "").strip()[:400]

    if item["tipo"] == "core":
        wp.correr("core update-db", timeout=600)

    return True, ""


def processar(pedido: dict, secrets: dict, agent_cfg: dict) -> dict:
    site = pedido["site"]
    opcoes = pedido["opcoes"]
    diz = Relatorio(agent_cfg, pedido["id"])

    segredo = chave_ssh(secrets, pedido["server"]["agent_secret_ref"])
    servidor = Servidor(pedido["server"], segredo)
    wp = WordPress(servidor, site["wp_root"])

    resultado = {
        "status": "aborted",
        "itens": [],
        "log": "",
        "snapshot_path": None,
        "antes": None,
        "depois": None,
        "error": None,
    }

    diz(f"{site['name']} em {servidor.nome} ({servidor.host})")

    # ---- Antes de tocar em nada -------------------------------------------
    servidor.exigir("true", "SSH falhou", timeout=60)

    binario = wp.preparar()
    diz(f"wp-cli: {binario}")

    proc = wp.correr("core is-installed", timeout=120)
    if proc.returncode != 0:
        raise ErroDeActualizacao(f"nao parece um WordPress em {site['wp_root']}")

    urls = [f"https://{site['domain']}"] if site.get("domain") else []
    urls += [u for u in (site.get("check_urls") or []) if u]
    if not urls:
        raise ErroDeActualizacao("o site nao tem dominio nem paginas para testar — sem isso nao ha como saber se partiu")

    antes = medir_site(urls)
    resultado["antes"] = {
        "versao_wp": (wp.correr("core version", timeout=120).stdout or "").strip(),
        **{p["url"]: f"HTTP {p['codigo']} · {p['tamanho']} bytes" for p in antes["paginas"]},
    }

    entrada = antes["paginas"][0]
    if entrada["codigo"] >= 400 or entrada["codigo"] == 0 or entrada.get("marcas"):
        raise ErroDeActualizacao(
            f"o site ja estava com problemas antes de comecar "
            f"(HTTP {entrada['codigo']}{', ' + entrada['marcas'][0] if entrada.get('marcas') else ''}) — nao se actualiza por cima disso"
        )

    resumo_inicial = "; ".join(
        "{} HTTP {} ({} bytes)".format(p["url"], p["codigo"], p["tamanho"])
        for p in antes["paginas"]
    )
    diz(f"estado inicial: {resumo_inicial}")

    # ---- O que ha para fazer ----------------------------------------------
    itens = itens_a_actualizar(wp, diz)

    if not itens:
        diz("nao ha nada por actualizar")
        resultado["status"] = "success"
        resultado["depois"] = resultado["antes"]
        resultado["log"] = diz.texto()
        return resultado

    diz(f"{len(itens)} por actualizar: " + ", ".join(f"{i['slug']} ({i['tipo']})" for i in itens))

    if pedido["mode"] == "dry_run":
        resultado["status"] = "success"
        resultado["itens"] = [{**i, "resultado": "por actualizar"} for i in itens]
        resultado["depois"] = resultado["antes"]
        resultado["log"] = diz.texto()
        diz("simulacao — nao se mexeu em nada")
        return resultado

    # ---- Copia de reposicao ------------------------------------------------
    destino = f"{opcoes['snapshot_dir'].rstrip('/')}/{re.sub(r'[^a-zA-Z0-9._-]', '_', site['name'])}"

    ok, mensagem = espaco_suficiente(servidor, site["wp_root"], destino,
                                     float(opcoes["espaco_minimo_factor"]))
    diz(mensagem)
    if not ok:
        raise ErroDeActualizacao(mensagem)

    diz("a tirar copia (ficheiros e base de dados)...")
    # Guarda-se o instante da copia: e a partir daqui que se conta o que entrou
    # no site, e e essa contagem que decide se a base pode ser reposta.
    momento_copia = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")
    copia = tirar_copia(servidor, wp, destino)
    resultado["snapshot_path"] = copia
    diz(f"copia em {copia}")

    # ---- Um de cada vez ----------------------------------------------------
    encolhimento = float(opcoes["encolhimento_maximo"])
    feitos: list[dict] = []
    repostos = 0

    for item in itens:
        etiqueta = f"{item['slug']} ({item['de'] or '?'} -> {item['para'] or '?'})"
        diz(f"a actualizar {etiqueta}")

        ok, erro = actualizar_item(wp, item)
        if not ok:
            diz(f"  falhou a actualizar: {erro}")
            feitos.append({**item, "resultado": "falhou", "motivo": erro})
            continue

        vivo, erro_wp = wp.vivo()
        agora = medir_site(urls)
        saudavel, motivo = comparar(antes, agora, encolhimento)

        if vivo and saudavel:
            diz("  ok")
            feitos.append({**item, "resultado": "actualizado"})
            continue

        motivo = motivo or f"o wp-cli deixou de responder: {erro_wp}"
        diz(f"  PARTIU O SITE: {motivo}")
        diz(f"  a repor {item['slug']}...")

        repor(servidor, site["wp_root"], copia, caminhos_do_item(item))

        vivo, erro_wp = wp.vivo()
        depois_de_repor = medir_site(urls)
        recuperou, motivo_ainda = comparar(antes, depois_de_repor, encolhimento)

        if vivo and recuperou:
            diz(f"  reposto — {item['slug']} fica na versao antiga")
            feitos.append({**item, "resultado": "reposto", "motivo": motivo})
            repostos += 1
            continue

        # Repor o item nao chegou. Aqui ja nao se tenta ser esperto: repoe-se
        # tudo o que estava no tar e para-se. O que falta e a base de dados,
        # que fica na copia para ser reposta a mao — automatizar isso apagava
        # encomendas.
        diz(f"  repor {item['slug']} nao chegou ({motivo_ainda or erro_wp}) — a repor todos os ficheiros")
        repor(servidor, site["wp_root"], copia, ["wp-admin", "wp-includes", "wp-content"])

        vivo, _ = wp.vivo()
        final = medir_site(urls)
        voltou, ainda = comparar(antes, final, encolhimento)

        # Ainda em baixo com os ficheiros todos repostos: o estrago esta na
        # base de dados — tipicamente uma migracao que o plugin correu ao
        # actualizar. E o unico caso em que se mexe nela.
        politica = str(opcoes.get("repor_bd") or "auto")

        if not (vivo and voltou) and politica != "nunca":
            novidades, detalhe = contar_novidades(wp, momento_copia)

            if novidades and politica == "auto":
                # A decisao nao e minha nem de uma opcao: e do numero. Uma
                # encomenda entrada as 3 da manha nao existe em mais lado
                # nenhum — repor a base por cima dela apagava-a sem deixar
                # rasto. Mais vale o site continuar em baixo e o Andre saber.
                diz(f"  NAO se repos a base de dados: entraram {', '.join(detalhe)} desde a copia")
                resultado["error"] = (
                    f"{item['slug']} partiu o site. Nao se repos a base de dados porque "
                    f"entretanto entraram {', '.join(detalhe)} — repor apagava-os. "
                    f"Copia em {copia}"
                )
            else:
                if novidades:
                    diz(f"  a repor a base de dados apesar de terem entrado {', '.join(detalhe)} (repor_bd=sempre)")
                else:
                    diz("  nada entrou no site desde a copia — a repor tambem a base de dados")

                ok_bd, erro_bd = repor_base_de_dados(servidor, wp, copia)

                if ok_bd:
                    vivo, _ = wp.vivo()
                    final = medir_site(urls)
                    voltou, ainda = comparar(antes, final, encolhimento)
                    diz("  base de dados reposta" + ("" if voltou else " — e o site continua em baixo"))
                else:
                    diz(f"  nao foi possivel repor a base de dados: {erro_bd}")

        feitos.append({**item, "resultado": "reposto" if voltou else "falhou", "motivo": motivo})
        repostos += 1

        resultado["itens"] = feitos
        resultado["depois"] = {p["url"]: f"HTTP {p['codigo']} · {p['tamanho']} bytes" for p in final["paginas"]}
        resultado["log"] = diz.texto()

        if voltou:
            diz("o site voltou com a reposicao completa. Parou-se aqui — o resto fica por actualizar.")
            resultado["status"] = "partial"
        else:
            diz("O SITE CONTINUA EM BAIXO depois de repor tudo o que havia para repor. "
                f"A copia esta em {copia}")
            resultado["status"] = "failed"
            resultado["error"] = resultado.get("error") or (
                f"{item['slug']} partiu o site e a reposicao nao chegou. "
                f"Copia completa (ficheiros e base de dados) em {copia}"
            )

        resultado["log"] = diz.texto()
        return resultado

    # ---- Fim ---------------------------------------------------------------
    final = medir_site(urls)
    resultado["depois"] = {
        "versao_wp": (wp.correr("core version", timeout=120).stdout or "").strip(),
        **{p["url"]: f"HTTP {p['codigo']} · {p['tamanho']} bytes" for p in final["paginas"]},
    }
    resultado["itens"] = feitos
    resultado["status"] = "partial" if repostos else "success"

    actualizados = sum(1 for f in feitos if f["resultado"] == "actualizado")
    diz(f"terminado: {actualizados} actualizados, {repostos} repostos")

    limpar_copias_antigas(servidor, destino, int(opcoes["snapshot_dias"]))

    resultado["log"] = diz.texto()
    return resultado


# --------------------------------------------------------------------------
# Ciclo
# --------------------------------------------------------------------------

def uma_volta(agent_cfg: dict, secrets: dict) -> bool:
    """Devolve True se apanhou trabalho."""
    try:
        resposta = api(agent_cfg, "GET", "/api/agent/updates/next", timeout=30)
    except requests.RequestException as e:
        log.warning("painel inacessivel: %s", e)
        return False

    if resposta.status_code != 200:
        log.warning("painel devolveu HTTP %s", resposta.status_code)
        return False

    pedido = (resposta.json() or {}).get("update")
    if not pedido:
        return False

    log.info("pedido #%s: %s", pedido["id"], pedido["site"]["name"])

    try:
        resultado = processar(pedido, secrets, agent_cfg)
    except ErroDeActualizacao as e:
        resultado = {"status": "aborted", "error": str(e), "log": str(e)}
        log.error("pedido #%s abortado: %s", pedido["id"], e)
    except subprocess.TimeoutExpired as e:
        resultado = {
            "status": "failed",
            "error": f"o comando demorou demasiado e foi cortado: {str(e)[:300]}. "
                     f"Confirmar o estado do site a mao.",
        }
        log.error("pedido #%s: timeout", pedido["id"])
    except Exception as e:  # noqa: BLE001 — o painel tem de saber sempre como acabou
        resultado = {"status": "failed", "error": f"{type(e).__name__}: {e}"[:500]}
        log.exception("pedido #%s rebentou", pedido["id"])

    try:
        api(agent_cfg, "POST", f"/api/agent/updates/{pedido['id']}/finish",
            json=resultado, timeout=60)
    except requests.RequestException as e:
        log.error("nao foi possivel devolver o resultado do #%s: %s", pedido["id"], e)

    return True


def main() -> int:
    parser = argparse.ArgumentParser(description="Actualiza WordPress a partir da fila do painel.")
    parser.add_argument("--once", action="store_true",
                        help="apanha um pedido (se houver) e sai — para cron")
    parser.add_argument("--sleep", type=int, default=30,
                        help="segundos entre sondagens quando fica a correr")
    parser.add_argument("-v", "--verbose", action="store_true")
    args = parser.parse_args()

    logging.basicConfig(
        level=logging.DEBUG if args.verbose else logging.INFO,
        format="%(asctime)s %(levelname)s %(message)s",
    )

    # Os sites com certificado proprio ou expirado nao devem impedir a
    # verificacao — o que se esta a testar e se o site esta de pe.
    requests.packages.urllib3.disable_warnings()  # type: ignore[attr-defined]

    agent_cfg = load_yaml(AGENT_FILE, "agent.yaml")
    secrets = load_yaml(SECRETS_FILE, "secrets.yaml")

    if args.once:
        uma_volta(agent_cfg, secrets)
        return 0

    log.info("a sondar %s de %ss em %ss", agent_cfg["api_url"], args.sleep, args.sleep)
    while True:
        try:
            if not uma_volta(agent_cfg, secrets):
                time.sleep(args.sleep)
        except KeyboardInterrupt:
            return 0
        except Exception:  # noqa: BLE001
            log.exception("erro no ciclo; a continuar")
            time.sleep(args.sleep)


if __name__ == "__main__":
    sys.exit(main())
