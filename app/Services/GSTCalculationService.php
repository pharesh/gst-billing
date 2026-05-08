<?php

namespace App\Services;

class GSTCalculationService
{
    // Standard GST rates in India
    const GST_RATES = [0, 0.1, 0.25, 1, 1.5, 3, 5, 6, 7.5, 12, 18, 28];

    /**
     * Calculate tax breakdown for a single line item.
     *
     * @param  float  $price         Unit price (exclusive of tax)
     * @param  float  $quantity
     * @param  float  $gstRate       Total GST % (e.g. 18)
     * @param  string $supplyType    'intrastate' = CGST+SGST, 'interstate' = IGST
     * @param  float  $discountPct   Discount % off taxable value
     */
    public function calculateItem(
        float $price,
        float $quantity,
        float $gstRate,
        string $supplyType = 'intrastate',
        float $discountPct = 0
    ): array {
        $grossAmount = round($price * $quantity, 2);
        $discountAmount = round($grossAmount * $discountPct / 100, 2);
        $taxableAmount = round($grossAmount - $discountAmount, 2);

        $totalTax = round($taxableAmount * $gstRate / 100, 2);

        if ($supplyType === 'interstate') {
            $cgstRate = 0;
            $sgstRate = 0;
            $igstRate = $gstRate;
            $cgstAmount = 0;
            $sgstAmount = 0;
            $igstAmount = $totalTax;
        } else {
            $halfRate = $gstRate / 2;
            $cgstRate = $halfRate;
            $sgstRate = $halfRate;
            $igstRate = 0;
            $cgstAmount = round($taxableAmount * $halfRate / 100, 2);
            $sgstAmount = round($taxableAmount * $halfRate / 100, 2);
            $igstAmount = 0;
            // Correct for rounding difference
            $totalTax = $cgstAmount + $sgstAmount;
        }

        return [
            'taxable_amount' => $taxableAmount,
            'gst_rate' => $gstRate,
            'cgst_rate' => $cgstRate,
            'sgst_rate' => $sgstRate,
            'igst_rate' => $igstRate,
            'cgst_amount' => $cgstAmount,
            'sgst_amount' => $sgstAmount,
            'igst_amount' => $igstAmount,
            'total_amount' => round($taxableAmount + $totalTax, 2),
        ];
    }

    /**
     * Calculate totals for an entire invoice from its items array.
     *
     * Each item must have: taxable_amount, cgst_amount, sgst_amount, igst_amount, total_amount
     */
    public function calculateInvoiceTotals(array $items): array
    {
        $subtotal = 0;
        $cgst = 0;
        $sgst = 0;
        $igst = 0;
        $total = 0;

        foreach ($items as $item) {
            $subtotal += $item['taxable_amount'];
            $cgst += $item['cgst_amount'];
            $sgst += $item['sgst_amount'];
            $igst += $item['igst_amount'];
            $total += $item['total_amount'];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'cgst_amount' => round($cgst, 2),
            'sgst_amount' => round($sgst, 2),
            'igst_amount' => round($igst, 2),
            'total_amount' => round($total, 2),
        ];
    }

    /**
     * Group invoice items by GST rate for GSTR summary display.
     */
    public function groupByGSTRate(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {
            $rate = (string) $item['gst_rate'];
            if (!isset($groups[$rate])) {
                $groups[$rate] = [
                    'gst_rate' => $item['gst_rate'],
                    'taxable_amount' => 0,
                    'cgst_amount' => 0,
                    'sgst_amount' => 0,
                    'igst_amount' => 0,
                ];
            }
            $groups[$rate]['taxable_amount'] += $item['taxable_amount'];
            $groups[$rate]['cgst_amount'] += $item['cgst_amount'];
            $groups[$rate]['sgst_amount'] += $item['sgst_amount'];
            $groups[$rate]['igst_amount'] += $item['igst_amount'];
        }

        return array_values($groups);
    }

    /**
     * Convert amount to words (Indian numbering system) for invoice footer.
     */
    public function amountInWords(float $amount): string
    {
        $amount = round($amount, 2);
        [$rupees, $paise] = explode('.', number_format($amount, 2, '.', ''));
        $paise = (int) $paise;

        $words = $this->numberToWords((int) str_replace(',', '', $rupees)) . ' Rupees';

        if ($paise > 0) {
            $words .= ' and ' . $this->numberToWords($paise) . ' Paise';
        }

        return $words . ' Only';
    }

    private function numberToWords(int $n): string
    {
        if ($n === 0) return 'Zero';

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($n < 20) return $ones[$n];
        if ($n < 100) return $tens[(int)($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
        if ($n < 1000) return $ones[(int)($n / 100)] . ' Hundred' . ($n % 100 ? ' ' . $this->numberToWords($n % 100) : '');
        if ($n < 100000) return $this->numberToWords((int)($n / 1000)) . ' Thousand' . ($n % 1000 ? ' ' . $this->numberToWords($n % 1000) : '');
        if ($n < 10000000) return $this->numberToWords((int)($n / 100000)) . ' Lakh' . ($n % 100000 ? ' ' . $this->numberToWords($n % 100000) : '');
        return $this->numberToWords((int)($n / 10000000)) . ' Crore' . ($n % 10000000 ? ' ' . $this->numberToWords($n % 10000000) : '');
    }
}
