<?php

/**
 * Servidor IMAP de mentira, so para os testes do ImapMailbox.
 *
 * Fala o suficiente do protocolo para provar o que interessa: que um literal
 * ({n} seguido dos bytes) e lido byte a byte mesmo quando o conteudo imita
 * linhas de protocolo, e que a ligacao continua alinhada a seguir.
 *
 * Uso: php servidor-imap-falso.php <porta>
 */

$porta = (int) ($argv[1] ?? 11430);

$mensagem = "From: contas@fornecedor.pt\r\nSubject: Fatura\r\n"
    ."Content-Type: application/pdf; name=\"f.pdf\"\r\n"
    ."Content-Transfer-Encoding: base64\r\n\r\n"
    // Linhas que enganariam um leitor ingenuo do protocolo:
    ."A0001 OK isto nao e uma resposta\r\n)\r\n* SEARCH 1 2 3\r\n"
    .str_repeat('X', 5000)."\r\n";

$servidor = @stream_socket_server("tcp://127.0.0.1:{$porta}", $erro, $mensagemErro);

if ($servidor === false) {
    fwrite(STDERR, "nao consegui abrir a porta {$porta}: {$mensagemErro}\n");
    exit(1);
}

// A quem lanca o processo: ja pode ligar-se.
fwrite(STDOUT, 'PRONTO '.md5($mensagem)."\n");
fflush(STDOUT);

$cliente = @stream_socket_accept($servidor, 10);

if ($cliente === false) {
    exit(1);
}

fwrite($cliente, "* OK [CAPABILITY IMAP4rev1 MOVE UIDPLUS] Servidor de teste\r\n");

while (($linha = fgets($cliente, 8192)) !== false) {
    $texto = trim($linha);
    [$etiqueta, $resto] = array_pad(explode(' ', $texto, 2), 2, '');
    $comando = strtoupper($resto);

    if (str_starts_with($comando, 'LOGIN')) {
        $certo = str_contains($resto, '"faturacao@ateneya.com"')
            && str_contains($resto, '"segredo com \\"aspas\\""');
        fwrite($cliente, $etiqueta.($certo ? " OK entrou\r\n" : " NO credenciais invalidas\r\n"));
    } elseif (str_starts_with($comando, 'CAPABILITY')) {
        fwrite($cliente, "* CAPABILITY IMAP4rev1 MOVE UIDPLUS\r\n".$etiqueta." OK\r\n");
    } elseif (str_starts_with($comando, 'LIST')) {
        fwrite($cliente, '* LIST (\HasNoChildren) "." "INBOX"'."\r\n");
        fwrite($cliente, '* LIST (\HasNoChildren) "." "INBOX.Importadas"'."\r\n");
        fwrite($cliente, $etiqueta." OK\r\n");
    } elseif (str_starts_with($comando, 'SELECT') || str_starts_with($comando, 'EXAMINE')) {
        fwrite($cliente, "* 3 EXISTS\r\n* 0 RECENT\r\n".$etiqueta." OK [READ-WRITE]\r\n");
    } elseif (str_starts_with($comando, 'UID SEARCH')) {
        fwrite($cliente, "* SEARCH 5 9 41\r\n".$etiqueta." OK\r\n");
    } elseif (str_starts_with($comando, 'UID FETCH')) {
        fwrite($cliente, '* 2 FETCH (UID 9 BODY[] {'.strlen($mensagem)."}\r\n");
        fwrite($cliente, $mensagem);
        fwrite($cliente, ")\r\n".$etiqueta." OK\r\n");
    } elseif (str_starts_with($comando, 'LOGOUT')) {
        fwrite($cliente, "* BYE\r\n".$etiqueta." OK\r\n");
        break;
    } elseif (
        str_starts_with($comando, 'UID STORE')
        || str_starts_with($comando, 'UID MOVE')
        || str_starts_with($comando, 'UID COPY')
        || str_starts_with($comando, 'CREATE')
        || str_starts_with($comando, 'EXPUNGE')
    ) {
        fwrite($cliente, $etiqueta." OK feito\r\n");
    } else {
        fwrite($cliente, $etiqueta." BAD comando desconhecido\r\n");
    }
}

fclose($cliente);
fclose($servidor);
