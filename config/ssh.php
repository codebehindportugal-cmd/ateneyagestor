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

    /*
    |---------------------------------------------------------------------------
    | IP do painel
    |---------------------------------------------------------------------------
    |
    | Entra no `ignoreip` do fail2ban de cada servidor. Sem isto, o fail2ban
    | acaba por banir o painel — que se liga muitas vezes seguidas durante uma
    | auditoria — e a máquina fica inalcançável.
    |
    */

    'ip_do_painel' => env('SSH_IP_DO_PAINEL', '144.91.100.40'),

];
