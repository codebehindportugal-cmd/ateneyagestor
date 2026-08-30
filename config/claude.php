<?php

return [

    /*
    | O painel que o worker vai sondar. Em producao e https://gestao.ateneya.com;
    | para testar contra o laragon aponta-se ao localhost. O token gera-se no
    | painel, em Projectos > Token do worker, e vive so no .env desta maquina.
    |
    | Isto e o que permite ter o painel no servidor e o codigo em casa: o painel
    | nunca chama o PC, e o PC que vem buscar.
    */
    'panel' => [
        'url'   => env('CLAUDE_PANEL_URL'),
        'token' => env('CLAUDE_PANEL_TOKEN'),
    ],

    /*
    | Binario do Claude Code. Em Windows costuma ser preciso o caminho completo
    | do .cmd (ex: C:\Users\andre\AppData\Roaming\npm\claude.cmd), porque o
    | PHP nao resolve .cmd pelo PATH como a linha de comandos resolve.
    */
    'binary' => env('CLAUDE_BINARY', 'claude'),

    /*
    | Alternativa ao binario: o cli.js do Claude Code arrancado pelo node.
    | Em Windows e o caminho fiavel quando a pasta do utilizador tem espacos ou
    | acentos — o executavel passa a ser 'node' (sem nada de especial) e o
    | caminho complicado vai como argumento, que o Symfony escapa bem.
    | Descobre-se com `php artisan claude:check`. Quando esta preenchido,
    | manda sobre o CLAUDE_BINARY.
    */
    'node_script' => env('CLAUDE_NODE_SCRIPT'),

    /* Tempo maximo de uma execucao, em segundos. */
    'timeout' => (int) env('CLAUDE_TIMEOUT', 900),

    /* Modelo a usar. Vazio = o que estiver configurado no Claude Code. */
    'model' => env('CLAUDE_MODEL'),

    /*
    | --bare arranca mais depressa e ignora hooks, MCP, skills e CLAUDE.md do
    | repositorio, mas obriga a ANTHROPIC_API_KEY (nao usa o login da subscricao).
    | Em repositorios nossos compensa deixar false: o CLAUDE.md do projecto e
    | metade do contexto que faz a resposta prestar.
    */
    'bare' => (bool) env('CLAUDE_BARE', false),

    /*
    | dontAsk nega tudo o que nao esteja explicitamente permitido. E o que
    | mantem esta ronda em modo de leitura: sem edicoes, sem deploys.
    */
    'permission_mode' => env('CLAUDE_PERMISSION_MODE', 'dontAsk'),

    /*
    | Ferramentas quando o pedido e de alterar ficheiros (modo apply).
    | Escreve e edita, mas nao corre migrations, nao instala pacotes, nao faz
    | commit e nao faz deploy — a entrega e deixar o trabalho por commitar,
    | para o Andre ver com `git diff` antes de enviar.
    */
    'permission_mode_write' => env('CLAUDE_PERMISSION_MODE_WRITE', 'acceptEdits'),

    'allowed_tools_write' => env(
        'CLAUDE_ALLOWED_TOOLS_WRITE',
        'Read,Edit,Write,Grep,Glob,Bash(git diff *),Bash(git status *),Bash(git log *)'
    ),

    'allowed_tools' => env(
        'CLAUDE_ALLOWED_TOOLS',
        'Read,Grep,Glob,Bash(git log *),Bash(git status *),Bash(git diff *)'
    ),

    /*
    | Chave da API. So e preciso com CLAUDE_BARE=true, ou se o worker correr
    | numa sessao sem login do Claude Code. Fica aqui e nao em env() directo
    | para o comando continuar a funcionar com `php artisan config:cache`.
    */
    'api_key' => env('ANTHROPIC_API_KEY'),

    /*
    | Ficheiros que ele nunca abre, mesmo estando o Read permitido.
    |
    | Importa sobretudo quando o worker corre no proprio servidor: a pasta de
    | trabalho de um projecto "local" pode ser a mesma onde vive o .env de
    | producao, e a resposta fica guardada na base de dados do painel. Isto e
    | mitigacao, nao garantia — a protecao a serio e o worker nao correr onde
    | estao os segredos.
    */
    'disallowed_tools' => env(
        'CLAUDE_DISALLOWED_TOOLS',
        'Read(**/.env),Read(**/.env.*),Read(**/*.pem),Read(**/*.key),Read(**/auth.json),Read(**/id_rsa*)'
    ),

    /* Segundos entre sondagens quando a fila esta vazia. */
    'sleep' => (int) env('CLAUDE_WORKER_SLEEP', 5),

    /* Um run preso em "a correr" mais do que isto foi um worker que morreu. */
    'stale_after_minutes' => (int) env('CLAUDE_STALE_AFTER', 30),

    /*
    | Copia so de leitura dos sites que so existem no servidor (code_source =
    | remote). Traz-se o minimo que serve para perceber o codigo: o tema e os
    | plugins, nunca os uploads. wp-config.php e .env ficam sempre de fora —
    | tem credenciais e nao fazem falta nenhuma para diagnosticar.
    */
    'snapshot' => [

        /* Copia mais nova do que isto reaproveita-se em vez de puxar outra vez. */
        'ttl_minutes' => (int) env('CLAUDE_SNAPSHOT_TTL', 60),

        'timeout' => (int) env('CLAUDE_SNAPSHOT_TIMEOUT', 300),

        /* Travao: uma copia maior do que isto e sinal de que os excludes falharam. */
        'max_mb' => (int) env('CLAUDE_SNAPSHOT_MAX_MB', 200),

        'wordpress' => [
            'include' => [
                'wp-content/themes',
                'wp-content/plugins',
                'wp-content/mu-plugins',
            ],
            'exclude' => [
                'wp-config.php',
                '.env',
                '*/node_modules',
                '*/vendor',
                '*/uploads',
                '*/cache',
                '*/.git',
                '*.zip', '*.gz', '*.sql', '*.log',
                '*.jpg', '*.jpeg', '*.png', '*.gif', '*.webp', '*.svg', '*.mp4', '*.woff*', '*.ttf',
            ],
        ],

        'laravel' => [
            'include' => [
                'app', 'config', 'routes', 'resources', 'database',
                'composer.json', 'package.json', 'CLAUDE.md', 'README.md',
            ],
            'exclude' => [
                '.env',
                '*/node_modules',
                '*/vendor',
                '*/.git',
                '*/storage/framework',
                '*.log', '*.sql',
            ],
        ],

    ],

];
