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

    /**
     * Um From sem nome nao e' um fornecedor. Enquanto isto devolvia o endereco,
     * o painel encheu-se de facturas do fornecedor "noreply@amen.pt".
     */
    public function test_from_sem_nome_nao_inventa_um_fornecedor(): void
    {
        $semNome = MimeMessage::deBruto("From: noreply@amen.pt\r\nSubject: Aviso\r\n\r\n.");
        $this->assertNull($semNome->nomeDe());
        $this->assertSame('noreply@amen.pt', $semNome->enderecoDe());

        $soAngulos = MimeMessage::deBruto("From: <contas@fornecedor.pt>\r\nSubject: Fatura\r\n\r\n.");
        $this->assertNull($soAngulos->nomeDe());

        $comNome = MimeMessage::deBruto("From: Papelaria Lda <contas@fornecedor.pt>\r\nSubject: Fatura\r\n\r\n.");
        $this->assertSame('Papelaria Lda', $comNome->nomeDe());
    }

    /**
     * O email da Via Verde traz factura, detalhe e CSV, e e' um so' gasto.
     *
     * Repara em qual deles tem o total no caso real: e' o `detalhe_*.pdf`.
     * Qualquer regra que decida pelo nome do ficheiro poe de lado justamente o
     * que interessa — por isso `legivel` diz apenas "isto da' para ler", e quem
     * decide o que e' a factura e' o total que o leitor encontrar.
     */
    public function test_email_com_varios_ficheiros_separa_legiveis_de_dados(): void
    {
        $fatura = '%PDF-fatura'.str_repeat('x', 3000);
        $detalhe = '%PDF-detalhe'.str_repeat('y', 9000);
        $csv = "Data;Portagem;Valor\r\n01/09/2026;A8 Bombarral;2,35\r\n";
        $logo = str_repeat("\x89PNG\r\n", 80);

        $b = 'FRONTEIRA';
        $bruto = implode("\r\n", [
            'Date: Tue, 08 Sep 2026 06:12:00 +0100',
            'From: "Via Verde" <extracto@viaverde.pt>',
            'Subject: Extracto Via Verde',
            "Content-Type: multipart/mixed; boundary=\"{$b}\"",
            '',
            "--{$b}",
            'Content-Type: text/plain; charset=UTF-8',
            '',
            'Segue o seu extracto mensal.',
            "--{$b}",
            'Content-Type: application/pdf',
            'Content-Transfer-Encoding: base64',
            'Content-Disposition: attachment; filename="fatura_2026_09.pdf"',
            '',
            trim(chunk_split(base64_encode($fatura), 76, "\r\n")),
            "--{$b}",
            'Content-Type: application/pdf',
            'Content-Transfer-Encoding: base64',
            'Content-Disposition: attachment; filename="detalhe_20260901.pdf"',
            '',
            trim(chunk_split(base64_encode($detalhe), 76, "\r\n")),
            "--{$b}",
            'Content-Type: text/csv; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'Content-Disposition: attachment; filename="detalhe_20260901.csv"',
            '',
            trim(chunk_split(base64_encode($csv), 76, "\r\n")),
            "--{$b}",
            'Content-Type: image/png',
            'Content-Transfer-Encoding: base64',
            'Content-Disposition: inline',
            'Content-ID: <logo@viaverde>',
            '',
            trim(chunk_split(base64_encode($logo), 76, "\r\n")),
            "--{$b}--",
        ]);

        $anexos = MimeMessage::deBruto($bruto)->anexosDeFatura();

        // O corpo de texto e o logotipo da assinatura ficam de fora.
        $this->assertCount(3, $anexos);

        $this->assertSame(['fatura_2026_09.pdf', true], [$anexos[0]['nome'], $anexos[0]['legivel']]);
        $this->assertSame(['detalhe_20260901.pdf', true], [$anexos[1]['nome'], $anexos[1]['legivel']]);
        $this->assertSame(['detalhe_20260901.csv', false], [$anexos[2]['nome'], $anexos[2]['legivel']]);

        $this->assertSame($csv, $anexos[2]['conteudo']);
        $this->assertSame($detalhe, $anexos[1]['conteudo']);
        $this->assertSame('csv', $anexos[2]['extensao']);
    }

    public function test_xml_e_excel_entram_como_dados(): void
    {
        $xml = '<?xml version="1.0"?><Invoice><Total>123.45</Total></Invoice>';

        $bruto = implode("\r\n", [
            'From: a@b.pt', 'Content-Type: multipart/mixed; boundary="F2"', '',
            '--F2',
            'Content-Type: application/octet-stream',
            'Content-Transfer-Encoding: base64',
            'Content-Disposition: attachment; filename="ft2026.xml"',
            '',
            base64_encode($xml),
            '--F2',
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Transfer-Encoding: base64',
            'Content-Disposition: attachment; filename="mapa.xlsx"',
            '',
            base64_encode('PK-excel'),
            '--F2--',
        ]);

        $anexos = MimeMessage::deBruto($bruto)->anexosDeFatura();

        $this->assertCount(2, $anexos);
        $this->assertFalse($anexos[0]['legivel']);
        $this->assertSame($xml, $anexos[0]['conteudo']);
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
