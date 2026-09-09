<?php

namespace Tests\Unit;

use App\Services\PaperInvoice\PaperInvoiceExtractor;
use PHPUnit\Framework\TestCase;

class PaperInvoiceExtractorTest extends TestCase
{
    public function test_it_extracts_invoice_lines_and_money_values(): void
    {
        $result = (new PaperInvoiceExtractor())->parseText(
            "Fornecedor XPTO\nNIF 509999999\nFatura FT 2026/12\nData 27/06/2026\nProduto A 2 10,00 20,00 23\nServico B 1 5,50 5,50 23\nIVA 4,79\nTotal 25,50"
        );

        $this->assertSame('Fornecedor XPTO', $result['supplier']['name']);
        $this->assertSame('509999999', $result['supplier']['taxNumber']);
        $this->assertSame(25.50, $result['invoice']['total']);
        $this->assertSame(4.79, $result['invoice']['vatTotal']);
        $this->assertCount(2, $result['products']);
        $this->assertSame('Produto A', $result['products'][0]['description']);
        $this->assertSame(2.0, $result['products'][0]['quantity']);
        $this->assertSame(10.0, $result['products'][0]['unitPrice']);
        $this->assertSame(20.0, $result['products'][0]['lineTotal']);
    }

    public function test_it_warns_when_totals_do_not_match(): void
    {
        $result = (new PaperInvoiceExtractor())->parseText(
            "Fornecedor XPTO\nProduto A 1 10,00 10,00 23\nTotal 20,00"
        );

        $this->assertContains('A soma das linhas nao coincide com o total da fatura.', $result['warnings']);
        $this->assertTrue($result['needsManualReview']);
    }

    public function test_it_handles_ocr_without_text(): void
    {
        $result = (new PaperInvoiceExtractor())->parseText('');

        $this->assertSame([], $result['products']);
        $this->assertContains('OCR nao devolveu texto legivel.', $result['warnings']);
        $this->assertContains('Nao foram encontradas linhas de produtos.', $result['warnings']);
        $this->assertTrue($result['needsManualReview']);
    }

    /**
     * O caso real da factura da Brisa que chega no email da Via Verde.
     *
     * O numero de documento e' "019.025.874/08/2026". A expressao que procurava
     * datas agarrava "74/08/2026" la dentro — um dia 74 — e a data verdadeira,
     * escrita "31 agosto 2026", nao era lida de todo. O documento entrava com
     * data errada e ninguem reparava, porque uma data errada nao da erro.
     */
    public function test_numero_de_documento_nao_e_confundido_com_data(): void
    {
        $resultado = (new PaperInvoiceExtractor())->parseText(
            "DATA DE EMISSAO: 31 agosto 2026\nN DE DOCUMENTO: 019.025.874/08/2026"
        );

        $this->assertSame('31/08/2026', $resultado['invoice']['date']);
    }

    public function test_datas_impossiveis_sao_descartadas(): void
    {
        $extractor = new PaperInvoiceExtractor();

        // Sem data nenhuma legivel e' melhor do que uma data inventada: quem
        // importa sabe que tem de a preencher.
        $this->assertSame('', $extractor->parseText('Ref 99/99/2026')['invoice']['date']);
        $this->assertSame('', $extractor->parseText('Ref 10/13/2026')['invoice']['date']);

        // E uma data valida mais a frente no texto ainda e' encontrada.
        $this->assertSame(
            '04/07/2026',
            $extractor->parseText('Ref 99/99/2026 e a data 04/07/2026')['invoice']['date']
        );
    }

    /** Muitas facturas portuguesas escrevem o mes por extenso. */
    public function test_le_datas_por_extenso(): void
    {
        $extractor = new PaperInvoiceExtractor();

        $this->assertSame('01/12/2026', $extractor->parseText('Lisboa, 1 de dezembro de 2026')['invoice']['date']);
        $this->assertSame('31/08/2026', $extractor->parseText('31 AGOSTO 2026')['invoice']['date']);
        // O pdftotext nem sempre traz o cedilha.
        $this->assertSame('05/03/2026', $extractor->parseText('5 marco 2026')['invoice']['date']);
    }

    public function test_datas_numericas_continuam_a_funcionar(): void
    {
        $extractor = new PaperInvoiceExtractor();

        $this->assertSame('27/06/2026', $extractor->parseText('Data 27/06/2026')['invoice']['date']);
        $this->assertSame('15/03/2026', $extractor->parseText('Emitido 2026-03-15')['invoice']['date']);
    }

    /**
     * O extracto mensal da Via Verde, com a disposicao real do documento de
     * Agosto de 2026 (as colunas sao mesmo assim largas — vao 124 espacos
     * entre o rotulo e o valor).
     *
     * Tres armadilhas de uma vez:
     *  - "Total em Portagens" e' o subtotal de **cada** concessionaria, e ha
     *    oito. Nao pode ser confundido com o total do documento.
     *  - "Total em Euros" aparece duas vezes: 596,53 (o documento) e 1,08 (o
     *    bloco das anuidades, mais abaixo). Vale o maior, nao o ultimo.
     *  - O IVA vem separado por taxa, em duas linhas logo abaixo do total, e o
     *    IVA do documento e' a soma das duas. Somar as do documento todo dava
     *    mais do dobro, porque o par repete-se em cada bloco.
     */
    public function test_extracto_via_verde(): void
    {
        $coluna = str_repeat(' ', 124);

        $texto = implode("\n", [
            'DATA DE EMISSAO:'.$coluna.'31 agosto 2026',
            'N DE DOCUMENTO:'.$coluna.'019.025.874/08/2026',
            'CONTRIBUINTE:'.$coluna.'515313700',
            'EXTRATO/RECIBO',
            'Total em Euros'.$coluna.'596,53',
            'IVA incluido a taxa reduzida em vigor'.$coluna.'3,57',
            'IVA incluido a taxa normal em vigor'.$coluna.'99,77',
            'Brisa Concessao Rodoviaria (BR)',
            'Total em Portagens'.$coluna.'171,75',
            'IVA incluido a taxa normal em vigor'.$coluna.'32,12',
            'Autoestradas do Atlantico (AA)',
            'Total em Portagens'.$coluna.'182,40',
            'IVA incluido a taxa normal em vigor'.$coluna.'34,11',
            'Via Verde Portugal (VV)',
            'Total em Euros'.$coluna.'1,08',
            'IVA incluido a taxa normal em vigor'.$coluna.'0,20',
        ]);

        $resultado = (new PaperInvoiceExtractor())->parseText($texto);

        $this->assertSame(596.53, $resultado['invoice']['total']);
        $this->assertSame(103.34, $resultado['invoice']['vatTotal']);
        $this->assertSame('31/08/2026', $resultado['invoice']['date']);
    }

    /** Uma factura simples que escreve so' "Total" continua a ser lida. */
    public function test_rotulo_generico_de_total(): void
    {
        $resultado = (new PaperInvoiceExtractor())->parseText("Fornecedor XPTO\nTotal 25,50");

        $this->assertSame(25.50, $resultado['invoice']['total']);
    }

    /**
     * Os padroes do numero de documento sao case-insensitive e a classe
     * `[A-Z0-9._\/-]` passa a aceitar qualquer letra: colhiam a palavra
     * seguinte, e chegavam a inventar numeros a partir de frases.
     *
     * Casos reais: "FT 2026/12 Total", "FT BR2026/012201461 Data", o numero de
     * documento "dever" tirado de "documento devera ser apresentada", e o "15"
     * tirado de "no prazo de 15 dias. Documento valido para efeitos fiscais".
     */
    public function test_numero_de_documento_nao_apanha_palavras(): void
    {
        $extractor = new PaperInvoiceExtractor();

        $this->assertSame(
            'FT 2026/12',
            $extractor->parseText("Fatura FT 2026/12\nTotal 25,50")['invoice']['number'],
        );

        // Sem numero e' melhor do que um numero inventado.
        $this->assertSame(
            '',
            $extractor->parseText('Qualquer reclamacao devera ser apresentada no prazo de 15 dias. Documento valido para efeitos fiscais.')['invoice']['number'],
        );
    }

    /** "Nº DE DOCUMENTO: X" — a ordem invertida nao era reconhecida. */
    public function test_le_numero_com_a_ordem_invertida(): void
    {
        $resultado = (new PaperInvoiceExtractor())->parseText(
            "N DE DOCUMENTO:                   019.025.874/08/2026"
        );

        $this->assertSame('019.025.874/08/2026', $resultado['invoice']['number']);
    }

    public function test_it_accepts_missing_qr_code(): void
    {
        $result = (new PaperInvoiceExtractor())->parseText('Fornecedor XPTO Total 1,00', null);

        $this->assertNull($result['qrData']);
        $this->assertSame('paper_invoice_photo', $result['source']);
    }
}
