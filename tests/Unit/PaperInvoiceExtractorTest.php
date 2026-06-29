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

    public function test_it_accepts_missing_qr_code(): void
    {
        $result = (new PaperInvoiceExtractor())->parseText('Fornecedor XPTO Total 1,00', null);

        $this->assertNull($result['qrData']);
        $this->assertSame('paper_invoice_photo', $result['source']);
    }
}
