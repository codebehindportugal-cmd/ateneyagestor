#!/bin/sh
# Nao deixa o agente arrancar sem rede. O router de casa reinicia de
# madrugada e leva o DNS com ele: sem isto, o agente tenta na mesma,
# falha todos os SSH e conta isso como backups falhados.
i=0
while [ $i -lt 90 ]; do
  if getent hosts gestao.ateneya.com >/dev/null 2>&1; then
    [ $i -gt 0 ] && echo "espera-rede: rede voltou ao fim de $((i * 30))s"
    exit 0
  fi
  sleep 30
  i=$((i + 1))
done
echo "espera-rede: 45 minutos sem DNS, nao arranco" >&2
exit 1
