"""
Testes da retenção e da frequência.

prune() APAGA directórios, por isso é verificada com ficheiros reais e datas
controladas em vez de por inspecção. Correr com:

    cd /opt/backup-agent && ./venv/bin/python test_prune.py
"""
import os, sys, time, shutil, tempfile
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import backup


def cenario(nomes, idades_dias):
    """Cria snapshots com nomes e idades controladas. nomes[0] = mais recente."""
    d = Path(tempfile.mkdtemp())
    for nome, idade in zip(nomes, idades_dias):
        p = d / nome
        p.mkdir()
        t = time.time() - idade * 86400
        os.utime(p, (t, t))
    return d


def restantes(d):
    return sorted((x.name for x in d.iterdir() if x.is_dir()), reverse=True)


falhas = []


def check(desc, obtido, esperado):
    ok = obtido == esperado
    print(("  OK    " if ok else "  FALHA ") + desc)
    if not ok:
        print("        esperado:", esperado)
        print("        obtido:  ", obtido)
        falhas.append(desc)


N = ["2026-08-28_0400", "2026-08-27_0400", "2026-08-26_0400",
     "2026-08-25_0400", "2026-08-24_0400"]

print("--- max_copies=2: guardar apenas 2 ---")
d = cenario(N, [0, 1, 2, 3, 4])
backup.prune(d, None, None, 2)
check("diarios, guarda os 2 mais recentes", restantes(d), N[:2])
shutil.rmtree(d)

d = cenario(N[:3], [0, 31, 62])
backup.prune(d, None, None, 2)
check("mensais, guarda os 2 mais recentes", restantes(d), N[:2])
shutil.rmtree(d)

d = cenario(N, [0, 1, 2, 3, 4])
backup.prune(d, None, 5, 2)
check("maximo manda sobre um minimo contraditorio", restantes(d), N[:2])
shutil.rmtree(d)

print("--- comportamento anterior preservado ---")
d = cenario(N, [0, 1, 2, 3, 4])
backup.prune(d, None, 2, None)
check("sem max_copies nem keep_days nao apaga nada", restantes(d), N)
shutil.rmtree(d)

d = cenario(N, [0, 1, 10, 20, 30])
backup.prune(d, 7, 2, None)
check("keep_days continua a funcionar sozinho", restantes(d), N[:2])
shutil.rmtree(d)

d = cenario(N[:3], [100, 200, 300])
backup.prune(d, 7, 2, None)
check("nunca desce abaixo do minimo", restantes(d), N[:2])
shutil.rmtree(d)

d = cenario(N[:1], [0])
backup.prune(d, None, None, 2)
check("nao apaga o unico snapshot que existe", restantes(d), N[:1])
shutil.rmtree(d)

print("--- frequencia ---")
check("diario corre no dia 15", backup.site_due_today({"frequency": "daily"}, 15), True)
check("mensal NAO corre no dia 15", backup.site_due_today({"frequency": "monthly"}, 15), False)
check("mensal corre no dia 1", backup.site_due_today({"frequency": "monthly"}, 1), True)
check("sem frequencia definida = diario", backup.site_due_today({}, 15), True)
check("maiusculas toleradas", backup.site_due_today({"frequency": "Monthly"}, 15), False)
check("valor desconhecido = diario", backup.site_due_today({"frequency": "xpto"}, 15), True)

print()
print("FALHAS:", len(falhas))
sys.exit(1 if falhas else 0)
