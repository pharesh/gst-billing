<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'             => 'Free',
                'slug'             => 'free',
                'price_monthly'    => 0,
                'invoice_limit'    => 10,
                'customer_limit'   => 10,
                'product_limit'    => 10,
                'whatsapp_enabled' => false,
                'gstr_export'      => false,
                'pdf_download'     => true,
                'multi_user'       => false,
                'features'         => ['10 invoices/month', '10 customers', 'PDF download', 'Email support'],
                'sort_order'       => 1,
            ],
            [
                'name'             => 'Starter',
                'slug'             => 'starter',
                'price_monthly'    => 499,
                'invoice_limit'    => 100,
                'customer_limit'   => 100,
                'product_limit'    => 100,
                'whatsapp_enabled' => true,
                'gstr_export'      => true,
                'pdf_download'     => true,
                'multi_user'       => false,
                'features'         => ['100 invoices/month', '100 customers', 'WhatsApp notifications', 'GSTR-1 & 3B export', 'Payment reminders', 'Email support'],
                'sort_order'       => 2,
            ],
            [
                'name'             => 'Pro',
                'slug'             => 'pro',
                'price_monthly'    => 999,
                'invoice_limit'    => -1,
                'customer_limit'   => -1,
                'product_limit'    => -1,
                'whatsapp_enabled' => true,
                'gstr_export'      => true,
                'pdf_download'     => true,
                'multi_user'       => true,
                'features'         => ['Unlimited invoices', 'Unlimited customers', 'WhatsApp notifications', 'GSTR export', 'Multi-user access', 'Priority support'],
                'sort_order'       => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
