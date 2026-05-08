<?php

namespace Tests\Unit;

use App\Services\GSTCalculationService;
use PHPUnit\Framework\TestCase;

class GSTCalculationServiceTest extends TestCase
{
    private GSTCalculationService $gst;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gst = new GSTCalculationService();
    }

    // ─── calculateItem ────────────────────────────────────────────────────

    public function test_intrastate_splits_gst_into_cgst_and_sgst(): void
    {
        $result = $this->gst->calculateItem(1000, 1, 18, 'intrastate');

        $this->assertEquals(9, $result['cgst_rate']);
        $this->assertEquals(9, $result['sgst_rate']);
        $this->assertEquals(0, $result['igst_rate']);
        $this->assertEquals(90, $result['cgst_amount']);
        $this->assertEquals(90, $result['sgst_amount']);
        $this->assertEquals(0, $result['igst_amount']);
        $this->assertEquals(1180, $result['total_amount']);
    }

    public function test_interstate_uses_igst_only(): void
    {
        $result = $this->gst->calculateItem(1000, 1, 18, 'interstate');

        $this->assertEquals(0, $result['cgst_amount']);
        $this->assertEquals(0, $result['sgst_amount']);
        $this->assertEquals(180, $result['igst_amount']);
        $this->assertEquals(1180, $result['total_amount']);
    }

    public function test_zero_gst_rate_produces_no_tax(): void
    {
        $result = $this->gst->calculateItem(500, 2, 0, 'intrastate');

        $this->assertEquals(0, $result['cgst_amount']);
        $this->assertEquals(0, $result['sgst_amount']);
        $this->assertEquals(1000, $result['total_amount']);
    }

    public function test_discount_reduces_taxable_amount(): void
    {
        // 10% discount on ₹1000 → taxable = ₹900, 18% GST on 900 = 162
        $result = $this->gst->calculateItem(1000, 1, 18, 'intrastate', 10);

        $this->assertEquals(900, $result['taxable_amount']);
        $this->assertEquals(1062, $result['total_amount']);
    }

    public function test_quantity_multiplies_correctly(): void
    {
        $result = $this->gst->calculateItem(100, 5, 12, 'intrastate');

        $this->assertEquals(500, $result['taxable_amount']);
        $this->assertEquals(560, $result['total_amount']);
    }

    public function test_five_percent_gst_intrastate(): void
    {
        $result = $this->gst->calculateItem(200, 1, 5, 'intrastate');

        $this->assertEquals(2.5, $result['cgst_rate']);
        $this->assertEquals(2.5, $result['sgst_rate']);
        // 5% of ₹200 = ₹10 total tax (₹5 CGST + ₹5 SGST)
        $this->assertEquals(5, $result['cgst_amount']);
        $this->assertEquals(5, $result['sgst_amount']);
        $this->assertEquals(10, $result['cgst_amount'] + $result['sgst_amount']);
        $this->assertEquals(210, $result['total_amount']);
    }

    // ─── calculateInvoiceTotals ───────────────────────────────────────────

    public function test_invoice_totals_sum_multiple_items(): void
    {
        $item1 = $this->gst->calculateItem(1000, 1, 18, 'intrastate');
        $item2 = $this->gst->calculateItem(500, 2, 12, 'intrastate');

        $totals = $this->gst->calculateInvoiceTotals([$item1, $item2]);

        $this->assertEquals(2000, $totals['subtotal']);
        $this->assertEquals(
            round($item1['cgst_amount'] + $item2['cgst_amount'], 2),
            $totals['cgst_amount']
        );
        $this->assertEquals(
            round($item1['total_amount'] + $item2['total_amount'], 2),
            $totals['total_amount']
        );
    }

    public function test_invoice_totals_with_single_item(): void
    {
        $item = $this->gst->calculateItem(500, 1, 18, 'interstate');
        $totals = $this->gst->calculateInvoiceTotals([$item]);

        $this->assertEquals(500, $totals['subtotal']);
        $this->assertEquals(90, $totals['igst_amount']);
        $this->assertEquals(590, $totals['total_amount']);
    }

    // ─── groupByGSTRate ───────────────────────────────────────────────────

    public function test_groups_items_by_gst_rate(): void
    {
        $items = [
            array_merge($this->gst->calculateItem(1000, 1, 18, 'intrastate'), ['gst_rate' => 18]),
            array_merge($this->gst->calculateItem(500, 1, 18, 'intrastate'), ['gst_rate' => 18]),
            array_merge($this->gst->calculateItem(200, 1, 12, 'intrastate'), ['gst_rate' => 12]),
        ];

        $groups = $this->gst->groupByGSTRate($items);

        $this->assertCount(2, $groups);
        $rates = array_column($groups, 'gst_rate');
        $this->assertContains(18, $rates);
        $this->assertContains(12, $rates);
    }

    // ─── amountInWords ────────────────────────────────────────────────────

    public function test_amount_in_words_simple(): void
    {
        $this->assertEquals('One Thousand One Hundred Eighty Rupees Only', $this->gst->amountInWords(1180));
    }

    public function test_amount_in_words_with_paise(): void
    {
        $result = $this->gst->amountInWords(1180.50);
        $this->assertStringContainsString('Paise', $result);
        $this->assertStringContainsString('Fifty', $result);
    }

    public function test_amount_in_words_lakh(): void
    {
        $result = $this->gst->amountInWords(100000);
        $this->assertStringContainsString('Lakh', $result);
    }

    public function test_amount_in_words_crore(): void
    {
        $result = $this->gst->amountInWords(10000000);
        $this->assertStringContainsString('Crore', $result);
    }

    public function test_amount_in_words_zero_paise(): void
    {
        $result = $this->gst->amountInWords(500.00);
        $this->assertStringNotContainsString('Paise', $result);
        $this->assertStringContainsString('Five Hundred Rupees Only', $result);
    }
}
