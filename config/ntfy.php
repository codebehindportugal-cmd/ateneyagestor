<?php

return [

    /*
    | Notificacoes push por ntfy (https://ntfy.sh), lidas na app do telemovel.
    |
    | Sem NTFY_TOPIC preenchido nao se envia nada — o resto do painel funciona
    | na mesma. Nunca deve deitar abaixo uma verificacao por a notificacao ter
    | falhado.
    */

    'enabled' => (bool) env('NTFY_ENABLED', true),

    /* Servidor. O publico chega; um proprio tem de estar acessivel a partir da VPS. */
    'url' => rtrim((string) env('NTFY_URL', 'https://ntfy.sh'), '/'),

    /*
    | O topico e a unica coisa que protege isto no servidor publico: quem souber
    | o nome le as notificacoes. Usa um nome longo e aleatorio, nao "ateneya".
    */
    'topic' => env('NTFY_TOPIC'),

    /* Token de acesso, se o servidor exigir autenticacao. */
    'token' => env('NTFY_TOKEN'),

    'timeout' => (int) env('NTFY_TIMEOUT', 8),

    /* Que avisos enviar. Desliga-se cada um por si. */
    'avisos' => [
        'sites'         => (bool) env('NTFY_AVISA_SITES', true),
        'servidores'    => (bool) env('NTFY_AVISA_SERVIDORES', true),
        'sincronizadores' => (bool) env('NTFY_AVISA_SYNCS', true),
        'backups'       => (bool) env('NTFY_AVISA_BACKUPS', true),
        'agentes'       => (bool) env('NTFY_AVISA_AGENTES', true),
    ],

    /*
    | Minutos sem contacto ate um agente de backups ser dado como parado.
    |
    | O agente de casa fala com o painel ao arrancar a corrida, no fim dela, e
    | — com o actualizador de WordPress instalado — de 30 em 30 segundos. Duas
    | horas chegam para apanhar um container morto sem tocar por uma rede que
    | pisca. Os agentes de produtividade (PCs) ficam com as 3 horas de sempre:
    | um PC desligado a noite nao e uma avaria.
    */
    'agente_offline_minutos' => (int) env('NTFY_AGENTE_OFFLINE_MINUTOS', 120),

];
