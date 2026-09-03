<?php

namespace Tests\Unit;

use App\Services\Faturacao\ImapException;
use App\Services\Faturacao\ImapMailbox;
use PHPUnit\Framework\TestCase;

class ImapMailboxTest extends TestCase
{
    /** @var resource|null */
    private $processo = null;

    private array $canos = [];

    private int $porta = 0;

    private string $md5DaMensagem = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->porta = random_int(11500, 12500);

        $this->processo = proc_open(
            [PHP_BINARY, __DIR__.'/../Fixtures/servidor-imap-falso.php', (string) $this->porta],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $this->canos
        );

        if (! is_resource($this->processo)) {
            $this->markTestSkipped('Nao consegui lancar o servidor de teste.');
        }

        // O servidor escreve "PRONTO <md5>" quando ja aceita ligacoes. Esperar
        // por essa linha em vez de dormir um tempo fixo: uma maquina lenta
        // fazia o teste falhar sem nada estar partido.
        $anuncio = fgets($this->canos[1]);

        if (! is_string($anuncio) || ! str_starts_with($anuncio, 'PRONTO ')) {
            $this->markTestSkipped('O servidor de teste nao arrancou.');
        }

        $this->md5DaMensagem = trim(substr($anuncio, 7));
    }

    protected function tearDown(): void
    {
        foreach ($this->canos as $cano) {
            if (is_resource($cano)) {
                fclose($cano);
            }
        }

        if (is_resource($this->processo)) {
            proc_terminate($this->processo);
            proc_close($this->processo);
        }

        parent::tearDown();
    }

    private function config(array $extra = []): array
    {
        return array_merge([
            'host' => '127.0.0.1',
            'port' => $this->porta,
            'encryption' => 'none',
            'validate_cert' => false,
            'username' => 'faturacao@ateneya.com',
            'password' => 'segredo com "aspas"',
            'folder' => 'INBOX',
            'timeout' => 10,
        ], $extra);
    }

    public function test_le_a_caixa_e_traz_a_mensagem_intacta(): void
    {
        $caixa = new ImapMailbox($this->config());
        $caixa->ligar();

        $this->assertSame(['INBOX', 'INBOX.Importadas'], $caixa->pastas());
        $this->assertSame(3, $caixa->escolherPasta('INBOX'));
        $this->assertSame([5, 9, 41], $caixa->procurar('UNSEEN'));

        // O corpo tem linhas que imitam respostas do protocolo. Se o literal
        // nao fosse lido byte a byte, chegavam cortadas — e o PDF saia partido.
        $this->assertSame($this->md5DaMensagem, md5($caixa->mensagemEmBruto(9)));

        $caixa->marcarLida(9);
        $caixa->mover(9, 'INBOX.Importadas');

        // Se o literal tivesse desalinhado o fluxo, isto bloqueava ou vinha lixo.
        $this->assertSame([5, 9, 41], $caixa->procurar('ALL'));

        $caixa->fechar();
    }

    public function test_password_errada_da_erro_com_a_causa(): void
    {
        $this->expectException(ImapException::class);
        $this->expectExceptionMessageMatches('/LOGIN/');

        (new ImapMailbox($this->config(['password' => 'errada'])))->ligar();
    }

    public function test_porta_fechada_diz_o_que_se_passa(): void
    {
        $this->expectException(ImapException::class);
        $this->expectExceptionMessageMatches('/Nao consegui ligar/');

        (new ImapMailbox($this->config(['port' => 1])))->ligar();
    }
}
