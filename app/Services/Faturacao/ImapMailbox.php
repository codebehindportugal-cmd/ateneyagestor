<?php

namespace App\Services\Faturacao;

/**
 * Cliente IMAP minimo, em PHP puro, sobre sockets.
 *
 * Porque nao uma biblioteca: o composer.lock so' se pode actualizar numa
 * maquina com PHP, e a maquina onde este codigo se escreve nao tem nenhum. Um
 * lock desalinhado faz o `composer install` do deploy morrer — e este deploy ja
 * falhou em silencio antes. A extensao ext-imap tambem nao serve: esta
 * depreciada no PHP 8.4 e nao ha garantia de estar instalada no Plesk.
 *
 * O que faz falta e' pouco: ligar, entrar, procurar, trazer a mensagem inteira
 * em bruto e arruma-la. O MIME e' desmontado a seguir pelo MimeMessage.
 */
class ImapMailbox
{
    /** @var resource|null */
    private $socket = null;

    private int $contador = 0;

    private array $capacidades = [];

    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? (array) config('faturas_email', []);
    }

    public function __destruct()
    {
        $this->fechar();
    }

    // ── Ligacao ──────────────────────────────────────────────────────────────

    public function ligar(): void
    {
        if ($this->socket !== null) {
            return;
        }

        $host = (string) ($this->config['host'] ?? '');
        $porta = (int) ($this->config['port'] ?? 993);
        $cifra = strtolower((string) ($this->config['encryption'] ?? 'ssl'));
        $espera = max(5, (int) ($this->config['timeout'] ?? 30));
        $validar = (bool) ($this->config['validate_cert'] ?? true);

        if ($host === '') {
            throw new ImapException('Falta o servidor de email (FATURAS_EMAIL_HOST).');
        }

        $contexto = stream_context_create([
            'ssl' => [
                'verify_peer' => $validar,
                'verify_peer_name' => $validar,
                'allow_self_signed' => ! $validar,
                'SNI_enabled' => true,
            ],
        ]);

        $destino = ($cifra === 'ssl' ? 'ssl://' : 'tcp://').$host.':'.$porta;

        $erroNumero = 0;
        $erroTexto = '';
        $socket = @stream_socket_client(
            $destino,
            $erroNumero,
            $erroTexto,
            $espera,
            STREAM_CLIENT_CONNECT,
            $contexto
        );

        if ($socket === false) {
            throw new ImapException(
                "Nao consegui ligar a {$host}:{$porta} — ".($erroTexto ?: 'sem resposta')
                .'. Confirma o servidor, a porta e que a firewall deixa sair.'
            );
        }

        $this->socket = $socket;
        stream_set_timeout($this->socket, $espera);

        $saudacao = $this->lerLinha();

        if (! preg_match('/^\*\s+(OK|PREAUTH)/i', $saudacao)) {
            $this->fechar();
            throw new ImapException('O servidor respondeu de forma inesperada: '.trim($saudacao));
        }

        $this->guardarCapacidades($saudacao);

        if ($cifra === 'tls') {
            $this->comando('STARTTLS');

            $activou = @stream_socket_enable_crypto(
                $this->socket,
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            );

            if ($activou !== true) {
                $this->fechar();
                throw new ImapException('O STARTTLS falhou. Tenta encryption=ssl na porta 993.');
            }

            $this->capacidades = [];
        }

        $this->autenticar();
    }

    public function fechar(): void
    {
        if ($this->socket === null) {
            return;
        }

        try {
            $this->escrever('ZZZZ LOGOUT'."\r\n");
        } catch (\Throwable) {
            // A ligacao ja pode estar morta; fechar na mesma.
        }

        @fclose($this->socket);
        $this->socket = null;
    }

    private function autenticar(): void
    {
        $utilizador = (string) ($this->config['username'] ?? '');
        $password = (string) ($this->config['password'] ?? '');

        if ($utilizador === '' || $password === '') {
            throw new ImapException(
                'Faltam as credenciais do email. Define FATURAS_EMAIL_USERNAME e '
                .'FATURAS_EMAIL_PASSWORD no .env do servidor.'
            );
        }

        // O nome do comando vai para as mensagens de erro; a password nunca.
        $this->comando(
            'LOGIN '.$this->citar($utilizador).' '.$this->citar($password),
            'LOGIN'
        );

        $this->capacidades = [];
    }

    // ── Operacoes ────────────────────────────────────────────────────────────

    /** @return list<string> */
    public function pastas(): array
    {
        $blocos = $this->comando('LIST "" "*"');
        $nomes = [];

        foreach ($blocos as $bloco) {
            if (! str_starts_with($bloco['texto'], '* LIST')) {
                continue;
            }

            if ($bloco['literais'] !== []) {
                $nomes[] = $bloco['literais'][0];

                continue;
            }

            // * LIST (\HasNoChildren) "." "INBOX.Importadas"
            if (preg_match('/"([^"]*)"\s*$/', trim($bloco['texto']), $m)) {
                $nomes[] = $m[1];
            } elseif (preg_match('/\s(\S+)\s*$/', trim($bloco['texto']), $m)) {
                $nomes[] = $m[1];
            }
        }

        return array_values(array_unique($nomes));
    }

    /** Devolve quantas mensagens a pasta tem. */
    public function escolherPasta(string $pasta, bool $soLeitura = false): int
    {
        $blocos = $this->comando(
            ($soLeitura ? 'EXAMINE ' : 'SELECT ').$this->citar($pasta)
        );

        foreach ($blocos as $bloco) {
            if (preg_match('/^\*\s+(\d+)\s+EXISTS/i', $bloco['texto'], $m)) {
                return (int) $m[1];
            }
        }

        return 0;
    }

    public function criarPasta(string $pasta): void
    {
        try {
            $this->comando('CREATE '.$this->citar($pasta));
        } catch (ImapException) {
            // Ja existe — que e' o que interessa.
        }
    }

    /**
     * @return list<int> UIDs, dos mais antigos para os mais recentes
     */
    public function procurar(string $criterio): array
    {
        $blocos = $this->comando('UID SEARCH '.$criterio);
        $uids = [];

        foreach ($blocos as $bloco) {
            if (! preg_match('/^\*\s+SEARCH(.*)$/is', trim($bloco['texto']), $m)) {
                continue;
            }

            foreach (preg_split('/\s+/', trim($m[1])) ?: [] as $pedaco) {
                if ($pedaco !== '' && ctype_digit($pedaco)) {
                    $uids[] = (int) $pedaco;
                }
            }
        }

        sort($uids);

        return $uids;
    }

    /**
     * Procura por data sem depender do locale: o IMAP exige os meses em ingles
     * abreviado (SINCE 03-Sep-2026) e o Carbon traduzido devolveria "Set".
     */
    public function procurarDesde(\DateTimeInterface $desde, bool $incluirLidas = false): array
    {
        $meses = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $data = sprintf(
            '%02d-%s-%04d',
            (int) $desde->format('j'),
            $meses[((int) $desde->format('n')) - 1],
            (int) $desde->format('Y')
        );

        return $this->procurar(($incluirLidas ? '' : 'UNSEEN ')."SINCE {$data}");
    }

    /** A mensagem inteira, cabecalhos incluidos, sem a marcar como lida. */
    public function mensagemEmBruto(int $uid): string
    {
        $blocos = $this->comando("UID FETCH {$uid} BODY.PEEK[]");

        foreach ($blocos as $bloco) {
            if ($bloco['literais'] !== [] && stripos($bloco['texto'], 'FETCH') !== false) {
                return $bloco['literais'][0];
            }
        }

        throw new ImapException("O servidor nao devolveu o conteudo da mensagem {$uid}.");
    }

    public function marcarLida(int $uid): void
    {
        $this->comando("UID STORE {$uid} +FLAGS (".'\Seen'.')');
    }

    /**
     * Move a mensagem. Usa UID MOVE quando o servidor o anuncia e, quando nao,
     * cai no copiar + marcar apagada + expunge, que e' o que os servidores
     * antigos percebem.
     */
    public function mover(int $uid, string $pasta): void
    {
        if ($this->suporta('MOVE')) {
            $this->comando("UID MOVE {$uid} ".$this->citar($pasta));

            return;
        }

        $this->comando("UID COPY {$uid} ".$this->citar($pasta));
        $this->comando("UID STORE {$uid} +FLAGS (".'\Deleted'.')');
        $this->comando('EXPUNGE');
    }

    public function suporta(string $capacidade): bool
    {
        if ($this->capacidades === []) {
            $blocos = $this->comando('CAPABILITY');

            foreach ($blocos as $bloco) {
                $this->guardarCapacidades($bloco['texto']);
            }
        }

        return in_array(strtoupper($capacidade), $this->capacidades, true);
    }

    private function guardarCapacidades(string $linha): void
    {
        if (! preg_match('/CAPABILITY([^\]]*)/i', $linha, $m)) {
            return;
        }

        foreach (preg_split('/\s+/', trim($m[1])) ?: [] as $pedaco) {
            if ($pedaco !== '') {
                $this->capacidades[] = strtoupper($pedaco);
            }
        }

        $this->capacidades = array_values(array_unique($this->capacidades));
    }

    // ── Protocolo ────────────────────────────────────────────────────────────

    /**
     * @return list<array{texto: string, literais: list<string>}>
     */
    private function comando(string $comando, ?string $rotulo = null): array
    {
        if ($this->socket === null) {
            throw new ImapException('Nao ha ligacao ao servidor de email.');
        }

        $etiqueta = sprintf('A%04d', ++$this->contador);
        $this->escrever($etiqueta.' '.$comando."\r\n");

        $blocos = [];

        while (true) {
            $bloco = $this->lerBloco();

            if (str_starts_with($bloco['texto'], $etiqueta.' ')) {
                $resto = trim(substr($bloco['texto'], strlen($etiqueta) + 1));
                $estado = strtoupper(strtok($resto, " \r\n") ?: '');

                if ($estado !== 'OK') {
                    throw new ImapException(
                        'O servidor recusou '.($rotulo ?? $comando).': '.$resto
                    );
                }

                $this->guardarCapacidades($resto);

                return $blocos;
            }

            $blocos[] = $bloco;

            if (count($blocos) > 20000) {
                throw new ImapException('Resposta do servidor grande demais em '.($rotulo ?? $comando).'.');
            }
        }
    }

    /**
     * Le uma resposta completa, incluindo os literais ({1234} seguido dos bytes
     * todos). Sem isto, um PDF com \r\n la' dentro parecia varias linhas de
     * protocolo e desalinhava tudo o que viesse a seguir.
     *
     * @return array{texto: string, literais: list<string>}
     */
    private function lerBloco(): array
    {
        $texto = $this->lerLinha();
        $literais = [];

        while (preg_match('/\{(\d+)\}\r?\n$/', $texto, $m)) {
            $literais[] = $this->lerBytes((int) $m[1]);
            $texto .= $this->lerLinha();
        }

        return ['texto' => $texto, 'literais' => $literais];
    }

    private function lerLinha(): string
    {
        if ($this->socket === null) {
            throw new ImapException('Nao ha ligacao ao servidor de email.');
        }

        $linha = fgets($this->socket, 8192);

        if ($linha === false) {
            $estado = stream_get_meta_data($this->socket);

            throw new ImapException(
                ! empty($estado['timed_out'])
                    ? 'O servidor de email deixou de responder (timeout).'
                    : 'A ligacao ao servidor de email caiu.'
            );
        }

        // Uma linha maior do que o buffer chega partida: juntar o resto.
        while (! str_ends_with($linha, "\n")) {
            $seguinte = fgets($this->socket, 8192);

            if ($seguinte === false) {
                break;
            }

            $linha .= $seguinte;
        }

        return $linha;
    }

    private function lerBytes(int $quantidade): string
    {
        if ($this->socket === null) {
            throw new ImapException('Nao ha ligacao ao servidor de email.');
        }

        $dados = '';

        while (strlen($dados) < $quantidade) {
            $pedaco = fread($this->socket, min(65536, $quantidade - strlen($dados)));

            if ($pedaco === false || $pedaco === '') {
                $estado = stream_get_meta_data($this->socket);

                if (! empty($estado['timed_out'])) {
                    throw new ImapException('O servidor parou a meio do envio da mensagem (timeout).');
                }

                break;
            }

            $dados .= $pedaco;
        }

        if (strlen($dados) < $quantidade) {
            throw new ImapException('A mensagem chegou incompleta do servidor.');
        }

        return $dados;
    }

    private function escrever(string $linha): void
    {
        if ($this->socket === null) {
            throw new ImapException('Nao ha ligacao ao servidor de email.');
        }

        if (@fwrite($this->socket, $linha) === false) {
            throw new ImapException('Nao consegui escrever para o servidor de email.');
        }
    }

    private function citar(string $valor): string
    {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $valor).'"';
    }
}
