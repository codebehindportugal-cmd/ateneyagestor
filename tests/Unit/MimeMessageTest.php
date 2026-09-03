<?php

namespace Tests\Unit;

use App\Services\Faturacao\MimeMessage;
use PHPUnit\Framework\TestCase;

class MimeMessageTest extends TestCase
{
    private function mensagemDeExemplo(string $pdf, string $foto, string $logo): string
    {
        $fronteira = 'FRONTEIRA-1234';
        $alternativa = 'ALTERNATIVA-9';

        return implode("\r\n", [
            'Date: Tue, 02 Sep 2026 14:31:07 +0100',
            'Message-ID: <abc123@fornecedor.pt>',
            'From: =?UTF-8?Q?Papelaria_Ac=C3=A7=C3=A3o_Lda?= <contas@fornecedor.pt>',
            'Subject: =?UTF-8?B?RmF0dXJhIEZUIDIwMjYvMTIzIC0gU2V0ZW1icm8=?=',
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed;',
            "\tboundary=\"{$fronteira}\"",
            '',
            'Preambulo que deve ser ignorado.',
            "--{$fronteira}",
            "Content-Type: multipart/alternative; boundary=\"{$alternativa}\"",
            '',
            "--{$alternativa}",
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            'Segue a factura do m=C3=AAs.',
            "--{$alternativa}--",
            "--{$fronteira}",
            'Content-Type: application/pdf',
            'Content-Transfer-Encoding: base64',
            'Content-Disposition: attachment; filename="=?UTF-8?Q?Fatura_n=C2=BA123.pdf?="',
            '',
            trim(chunk_split(base64_encode($pdf), 76, "\r\n")),
            "--{$fronteira}",
            'Content-Type: image/png',
            'Content-Transfer-Encoding: base64',
            'Content-Disposition: inline',
            'Content-ID: <logotipo@assinatura>',
            '',
            trim(chunk_split(base64_encode($logo), 76, "\r\n")),
            "--{$fronteira}",
            'Content-Type: image/jpeg',
            'Content-Transfer-Encoding: base64',
            "Content-Disposition: attachment; filename*0*=UTF-8''Fotografia%20da%20; filename*1*=factura.jpg",
            '',
            trim(chunk_split(base64_encode($foto), 76, "\r\n")),
            "--{$fronteira}--",
            'Epilogo ignorado.',
        ]);
    }

    public function test_le_cabecalhos_codificados(): void
    {
        $mensagem = MimeMessage::deBruto($this->mensagemDeExemplo('%PDF-1', str_repeat('f', 40000), 'logo'));

        $this->assertSame('Fatura FT 2026/123 - Setembro', $mensagem->assunto());
        $this->assertSame('Papelaria Acção Lda', $mensagem->nomeDe());
        $this->assertSame('contas@fornecedor.pt', $mensagem->enderecoDe());
        $this->assertSame('<abc123@fornecedor.pt>', $mensagem->messageId());
    }

    /**
     * Ha servidores que poem o dia da semana errado no cabecalho Date. O PHP,
     * em vez de o ignorar, salta para o proximo dia com esse nome — e a factura
     * ficava com data de uma semana depois.
     */
    public function test_dia_da_semana_errado_nao_desloca_a_data(): void
    {
        // 02/09/2026 foi uma quarta-feira, nao uma terca.
        $mensagem = MimeMessage::deBruto("From: a@b.pt\r\nDate: Tue, 02 Sep 2026 14:31:07 +0100\r\n\r\n.");

        $this->assertSame('2026-09-02', $mensagem->data()?->format('Y-m-d'));
    }

    public function test_traz_os_anexos_sem_alterar_um_byte(): void
    {
        // Bytes que um leitor por linhas estragaria: nulos e CRLF la dentro.
        $pdf = "%PDF-1.4\r\n".str_repeat("binario\x00\x01\r\n", 200).'%%EOF';
        $foto = str_repeat("\xFF\xD8\xFF\xE0dados", 6000);
        $logo = str_repeat("\x89PNG\r\n", 100);

        $anexos = MimeMessage::deBruto($this->mensagemDeExemplo($pdf, $foto, $logo))->anexosDeFatura();

        $this->assertCount(2, $anexos, 'O logotipo embutido nao devia entrar.');
        $this->assertSame('Fatura nº123.pdf', $anexos[0]['nome']);
        $this->assertSame($pdf, $anexos[0]['conteudo']);
        $this->assertSame('Fotografia da factura.jpg', $anexos[1]['nome']);
        $this->assertSame($foto, $anexos[1]['conteudo']);
    }

    public function test_email_sem_anexos_nao_produz_documentos(): void
    {
        $boletim = "From: a@b.pt\r\nSubject: Boletim\r\nContent-Type: text/plain\r\n\r\nOla.";

        $this->assertSame([], MimeMessage::deBruto($boletim)->anexosDeFatura());
    }

    public function test_factura_enviada_sem_multipart(): void
    {
        $pdf = '%PDF-mini';
        $bruto = "From: x@y.pt\r\nSubject: Fatura\r\nContent-Type: application/pdf; name=\"f.pdf\"\r\n"
            ."Content-Transfer-Encoding: base64\r\n\r\n".base64_encode($pdf);

        $anexos = MimeMessage::deBruto($bruto)->anexosDeFatura();

        $this->assertCount(1, $anexos);
        $this->assertSame($pdf, $anexos[0]['conteudo']);
    }

    public function test_pdf_declarado_como_octet_stream_entra_pela_extensao(): void
    {
        $bruto = implode("\r\n", [
            'From: x@y.pt', 'Content-Type: multipart/mixed; boundary="F2"', '',
            '--F2',
            'Content-Type: application/octet-stream; name="recibo.pdf"',
            'Content-Transfer-Encoding: base64',
            'Content-Disposition: attachment; filename="recibo.pdf"',
            '',
            base64_encode('%PDF-recibo'),
            '--F2--',
        ]);

        $anexos = MimeMessage::deBruto($bruto)->anexosDeFatura();

        $this->assertCount(1, $anexos);
        $this->assertSame('pdf', $anexos[0]['extensao']);
    }
}
