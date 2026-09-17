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

    // Varias pastas separadas por virgula ("INBOX,INBOX.Fornecedores").
    // Vazio = so' a de cima.
    'folders' => env('FATURAS_EMAIL_FOLDERS'),

    // Ler tambem o spam (descoberto pelo atributo \Junk ou pelo nome). O que
    // de la' vier entra sempre POR REVER.
    'include_junk' => filter_var(env('FATURAS_EMAIL_INCLUDE_JUNK', true), FILTER_VALIDATE_BOOLEAN),

    /*
    | O que acontece a mensagem depois de TODOS os seus ficheiros estarem no
    | painel (17/09/2026: a caixa passou a ser uma fila — o que la' fica e' o
    | que falta):
    |   lixo   — vai para o lixo do email (recuperavel). Por omissao.
    |   apagar — apagada de vez.
    |   mover  — vai para FATURAS_EMAIL_PROCESSED_FOLDER.
    |   manter — fica, marcada como lida.
    | As mensagens sem factura (newsletters, avisos) ficam sempre.
    */
    'after_import' => env('FATURAS_EMAIL_AFTER_IMPORT', 'lixo'),

    // Vazio = descobre-se sozinha (atributo \Trash, ou Trash/Lixo/...).
    'trash_folder' => env('FATURAS_EMAIL_TRASH_FOLDER'),

    // So' usada com after_import=mover.
    'processed_folder' => env('FATURAS_EMAIL_PROCESSED_FOLDER'),

    // Por omissao le-se desde o dia 1 do mes anterior (o mes todo mais a
    // virada do mes). Um numero de dias maior do que isso alarga a janela.
    'days' => env('FATURAS_EMAIL_DAYS') !== null ? (int) env('FATURAS_EMAIL_DAYS') : null,

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
