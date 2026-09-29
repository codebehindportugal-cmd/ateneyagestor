<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Moloni — faturas de venda para os Resultados
    |--------------------------------------------------------------------------
    |
    | O painel vai ao Moloni buscar as faturas emitidas (so' leitura) para as
    | pôr ao lado das despesas em Contabilidade > Resultados.
    |
    | As credenciais ficam no .env do servidor, nunca na base de dados. O
    | client_id e o client_secret sao os de programador (moloni.pt/dev); o
    | utilizador e a password sao os de uma conta com acesso a empresa.
    |
    */

    'enabled' => filter_var(env('MOLONI_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'base_url' => rtrim(env('MOLONI_BASE_URL', 'https://api.moloni.pt/v1'), '/'),

    'client_id'     => env('MOLONI_CLIENT_ID'),
    'client_secret' => env('MOLONI_CLIENT_SECRET'),
    'username'      => env('MOLONI_USERNAME'),
    'password'      => env('MOLONI_PASSWORD'),

    // Vazio = a primeira empresa da conta. `php artisan moloni:sincronizar --teste`
    // lista as empresas e o id de cada uma.
    'company_id' => env('MOLONI_COMPANY_ID') ? (int) env('MOLONI_COMPANY_ID') : null,

    'timeout' => (int) env('MOLONI_TIMEOUT', 30),

    /*
    | Que documentos contam como venda, pelo codigo SAF-T. O sinal diz se soma
    | ou subtrai. Os recibos (RC/RG) ficam de fora de proposito: pagam uma
    | fatura que ja foi contada, e conta-los duplicava a venda. Guias,
    | orcamentos e encomendas nao sao vendas.
    */
    'tipos_venda' => [
        'FT' => 1,   // Fatura
        'FR' => 1,   // Fatura-recibo
        'FS' => 1,   // Fatura simplificada
        'ND' => 1,   // Nota de debito
        'NC' => -1,  // Nota de credito
    ],
];
