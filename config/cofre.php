<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Cofre pessoal
    |---------------------------------------------------------------------------
    |
    | Minutos de inactividade até o cofre se trancar sozinho. Conta-se a partir
    | da última vez que se leu ou gravou uma senha, não do desbloqueio.
    |
    */

    'minutos_inactividade' => (int) env('COFRE_MINUTOS_INACTIVIDADE', 15),

    /*
    | Custo do Argon2id ao derivar a chave a partir da master password. Os
    | valores usados ficam gravados em cada cofre, por isso subir isto só afecta
    | cofres novos (e cofres cuja master password se volte a mudar).
    |
    | 3 passagens / 64 MB é o "interactive" do libsodium com uma volta a mais:
    | ~0,3s num VPS pequeno, uma vez por desbloqueio.
    */

    'opslimit' => (int) env('COFRE_OPSLIMIT', 3),
    'memlimit' => (int) env('COFRE_MEMLIMIT', 67108864),

    /*
    | Tamanho por omissão das senhas que o gerador cria.
    */

    'tamanho_senha_gerada' => (int) env('COFRE_TAMANHO_SENHA', 20),

];
