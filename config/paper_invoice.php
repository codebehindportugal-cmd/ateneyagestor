<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Caminhos dos binários externos
    |--------------------------------------------------------------------------
    |
    | Deixar vazio faz o extractor procurar com `which`. Preencher salta essa
    | procura, o que interessa em servidores onde o PHP da web corre com um
    | PATH reduzido.
    |
    | Isto vive num ficheiro de config e não em env() dentro do serviço de
    | propósito: com `php artisan config:cache` — que o deploy corre — as
    | chamadas a env() fora de config/ devolvem null, e as definições
    | pareceriam ignoradas sem qualquer erro.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Os nossos NIF
    |--------------------------------------------------------------------------
    |
    | Nunca sao aceites como NIF do fornecedor. As facturas trazem o NIF do
    | cliente tantas vezes como o do emitente — o extracto da Via Verde poe o
    | nosso no cabecalho, em "CONTRIBUINTE:" — e sem esta lista era esse que
    | ficava no campo do fornecedor.
    |
    | Varios separados por virgula, no .env: NIFS_EMPRESA=515313700,500000000
    |
    */

    'nifs_proprios' => env('NIFS_EMPRESA', ''),

    'binaries' => [
        'pdftotext' => env('PDFTOTEXT_BINARY'),
        'pdftoppm'  => env('PDFTOPPM_BINARY'),
        'zbarimg'   => env('ZBARIMG_BINARY'),
        'tesseract' => env('TESSERACT_BINARY'),
    ],

];
