<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Caixa de correio das facturas
    |--------------------------------------------------------------------------
    |
    | A caixa faturacao@ateneya.com recebe as facturas dos fornecedores. O
    | comando `faturas:importar-email` vai la buscar os anexos PDF e imagem e
    | cria um documento de contabilidade por cada um.
    |
    | Fica em config/ e nao em env() espalhado pelo codigo de proposito: o
    | deploy corre `php artisan config:cache`, e depois disso as chamadas a
    | env() fora de config/ devolvem null sem dar erro nenhum.
    |
    | A password NUNCA fica aqui — fica no .env do servidor.
    |
    */

    'enabled' => filter_var(env('FATURAS_EMAIL_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'host' => env('FATURAS_EMAIL_HOST', 'mail.ateneya.com'),
    'port' => (int) env('FATURAS_EMAIL_PORT', 993),

    // 'ssl' (porta 993), 'tls' (STARTTLS na porta 143) ou 'none'.
    'encryption' => env('FATURAS_EMAIL_ENCRYPTION', 'ssl'),

    // Desligar so' se o servidor tiver certificado auto-assinado.
    'validate_cert' => filter_var(env('FATURAS_EMAIL_VALIDATE_CERT', true), FILTER_VALIDATE_BOOLEAN),

    'username' => env('FATURAS_EMAIL_USERNAME', 'faturacao@ateneya.com'),
    'password' => env('FATURAS_EMAIL_PASSWORD'),

    'folder' => env('FATURAS_EMAIL_FOLDER', 'INBOX'),

    /*
    | Para onde vai a mensagem depois de importada. Vazio = fica na entrada,
    | apenas marcada como lida. Uma pasta ("Importadas") e' mais seguro: se
    | alguem abrir a caixa no telemovel e ler um email por engano, ele deixa
    | de estar por importar e passava despercebido.
    */
    'processed_folder' => env('FATURAS_EMAIL_PROCESSED_FOLDER'),

    // Quantos dias para tras procurar. Trava a primeira corrida numa caixa
    // com anos de historico.
    'days' => (int) env('FATURAS_EMAIL_DAYS', 30),

    // Mensagens processadas por corrida.
    'max_messages' => (int) env('FATURAS_EMAIL_MAX_MESSAGES', 40),

    // Anexos maiores do que isto sao ignorados (MB).
    'max_attachment_mb' => (int) env('FATURAS_EMAIL_MAX_ATTACHMENT_MB', 20),

    /*
    | Logotipos de assinatura vem como imagem no email e nao sao facturas.
    | Imagens abaixo deste tamanho (KB) sao ignoradas; os PDF entram sempre,
    | independentemente do tamanho.
    */
    'min_image_kb' => (int) env('FATURAS_EMAIL_MIN_IMAGE_KB', 30),

    // Marca por defeito dos documentos que entram pelo email. Vazio deixa-os
    // em "Geral" — aparecem na vista do contabilista mas sem marca atribuida.
    'default_brand_id' => env('FATURAS_EMAIL_BRAND_ID'),

    // Segundos de espera em cada leitura do socket.
    'timeout' => (int) env('FATURAS_EMAIL_TIMEOUT', 30),

];
