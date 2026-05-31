<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\RecurringInvoice;
use App\Services\GSTCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessRecurringInvoices extends Command
{
    protected $signature   = 'invoices:process-recurring';
    protected $description = 'Generate invoices from due recurring invoice schedules';

    public function handle(GSTCalculationService $gst): int
    {
        // MongoDB stores dates as strings (Y-m-d); use direct where comparison
        // instead of whereDate which requires BSON Date objects
        $today = today()->toDateString();

        $due = RecurringInvoice::with('tenant')
            ->where('is_active', true)
            ->where('next_run_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->get();

        $this->info("Processing {$due->count()} recurring invoice(s)...");

        foreach ($due as $recurring) {
            try {
                DB::transaction(function () use ($recurring, $gst) {
                    $tenant   = $recurring->tenant;
                    $number   = $tenant->nextInvoiceNumber();
                    $dueDate  = today()->addDays($recurring->due_days);

                    $calculatedItems = collect($recurring->items)->map(function ($item, $index) use ($recurring, $gst) {
                        $calc = $gst->calculateItem(
                            $item['price'],
                            $item['quantity'],
                            $item['gst_rate'],
                            $recurring->supply_type,
                            $item['discount_percent'] ?? 0
                        );
                        return array_merge($item, $calc, ['sort_order' => $index]);
                    });

                    $totals = $gst->calculateInvoiceTotals($calculatedItems->toArray());

                    $invoice = Invoice::create([
                        'tenant_id'      => $tenant->id,
                        'customer_id'    => $recurring->customer_id,
                        'invoice_number' => $number,
                        'invoice_date'   => today(),
                        'due_date'       => $dueDate,
                        'invoice_type'   => $recurring->invoice_type,
                        'supply_type'    => $recurring->supply_type,
                        'notes'          => $recurring->notes,
                        'terms'          => $recurring->terms,
                        ...$totals,
                    ]);

                    foreach ($calculatedItems as $item) {
                        InvoiceItem::create(array_merge(['invoice_id' => $invoice->id], $item));
                    }

                    $recurring->update([
                        'last_run_date' => today(),
                        'next_run_date' => $recurring->nextRunAfter(),
                    ]);

                    $tenant->incrementMonthlyInvoiceCount();
                });

                $this->info("  ✓ Created invoice for recurring #{$recurring->id} ({$recurring->title})");
            } catch (\Throwable $e) {
                $this->error("  ✗ Failed for recurring #{$recurring->id}: {$e->getMessage()}");
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
