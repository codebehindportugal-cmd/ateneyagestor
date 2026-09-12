<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Chave SSH do painel
    |---------------------------------------------------------------------------
    |
    | Usada quando a ficha do servidor não tem caminho de chave preenchido.
    | Evita ter de repetir o mesmo caminho em nove servidores.
    |
    | Atenção a quem lê o ficheiro: o painel corre como utilizador do vhost,
    | não como root. Uma chave em /root/.ssh/ não serve — o painel não a
    | consegue abrir. Ver o README do cofre ou as notas de instalação.
    |
    */

    'chave_por_omissao' => env('SSH_CHAVE_POR_OMISSAO'),

];
