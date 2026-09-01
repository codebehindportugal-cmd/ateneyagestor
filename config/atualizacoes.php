<?php

return [

    /*
    | Actualizacoes de WordPress feitas pelo agente dos backups.
    |
    | O painel poe o pedido em fila; o agente vem busca-lo, tira uma copia no
    | proprio servidor, actualiza um item de cada vez e testa o site a seguir
    | a cada um. O que partir o site e reposto na hora.
    */

    /* Quantos dias ficam as copias de reposicao no servidor. */
    'snapshot_dias' => (int) env('ATUALIZACOES_SNAPSHOT_DIAS', 7),

    /* Onde ficam, no servidor de cada site. */
    'snapshot_dir' => env('ATUALIZACOES_SNAPSHOT_DIR', '/var/backups/ateneya-updates'),

    /*
    | Espaco livre minimo, em multiplos do tamanho do que se vai copiar. Abaixo
    | disto nem se comeca: um disco cheio a meio de um tar deixa o site sem
    | copia E sem actualizacao.
    */
    'espaco_minimo_factor' => (float) env('ATUALIZACOES_ESPACO_FACTOR', 2.5),

    /*
    | Quanto a pagina pode encolher antes de contar como partida. Um site que
    | responde 200 mas devolve 12% do HTML de antes esta partido, mesmo sem
    | erro nenhum visivel — e o caso classico do plugin que morre em silencio.
    */
    'encolhimento_maximo' => (float) env('ATUALIZACOES_ENCOLHIMENTO', 0.5),

    /*
    | A janela da noite. O botao poe o pedido a espera desta hora em vez de
    | correr logo: mesmo com reposicao automatica, ninguem quer descobrir que
    | um plugin partiu a loja as tres da tarde.
    |
    | Hora local (Europe/Lisbon), nao UTC.
    */
    'janela_inicio' => env('ATUALIZACOES_JANELA_INICIO', '02:00'),
    'janela_fim'    => env('ATUALIZACOES_JANELA_FIM', '06:00'),
    'fuso'          => env('ATUALIZACOES_FUSO', 'Europe/Lisbon'),

    /*
    | Repor tambem a base de dados, em ultimo recurso — depois de repor o item
    | e depois de repor todos os ficheiros.
    |
    | 'auto'  repoe SO se nao tiver entrado nada no site desde a copia. Antes
    |         de repor, conta as encomendas, comentarios, utilizadores e
    |         conteudos criados desde entao; se houver um que seja, nao mexe e
    |         avisa com o numero. Uma encomenda vale mais do que um site de pe.
    | 'nunca' nunca toca na base de dados.
    | 'sempre' repoe na mesma. So faz sentido em sites sem nada a entrar.
    |
    | Em qualquer dos casos, antes de repor guarda um dump do estado actual —
    | reposicao nenhuma pode ser um caminho sem volta.
    */
    'repor_bd' => env('ATUALIZACOES_REPOR_BD', 'auto'),

    /* Um trabalho em "a actualizar" mais tempo do que isto ficou preso. */
    'stale_after_minutes' => (int) env('ATUALIZACOES_STALE_MINUTOS', 60),

    /* Segundos entre sondagens do agente. */
    'sleep' => (int) env('ATUALIZACOES_SLEEP', 30),

];
