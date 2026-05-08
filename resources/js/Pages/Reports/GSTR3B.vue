<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    data: Object,
    month: [String, Number],
    year: [String, Number],
});

const MONTHS = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head :title="`GSTR-3B — ${MONTHS[month]} ${year}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('reports.index')" class="text-gray-500 hover:text-gray-700 text-sm">← Back</Link>
                <h2 class="text-xl font-semibold text-gray-800">GSTR-3B Summary — {{ MONTHS[month] }} {{ year }}</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8 space-y-6">

                <!-- Header Info -->
                <div class="bg-white rounded-lg shadow p-5 flex justify-between items-center">
                    <div>
                        <div class="text-xs text-gray-400 uppercase mb-1">GSTIN</div>
                        <div class="font-mono font-semibold text-gray-800">{{ data.gstin || 'Not set' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 uppercase mb-1">Filing Period</div>
                        <div class="font-semibold text-gray-800">{{ data.period }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 uppercase mb-1">Total Invoices</div>
                        <div class="font-semibold text-gray-800">{{ data.invoice_count }}</div>
                    </div>
                </div>

                <!-- 3.1 Outward Supplies -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-5 py-4 bg-indigo-50 border-b">
                        <h3 class="font-semibold text-indigo-800">3.1 — Details of Outward Supplies</h3>
                        <p class="text-xs text-indigo-500 mt-0.5">Tax on outward and reverse charge inward supplies</p>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium text-gray-500">Nature of Supplies</th>
                                <th class="px-5 py-3 text-right font-medium text-gray-500">Taxable Value</th>
                                <th class="px-5 py-3 text-right font-medium text-gray-500">IGST</th>
                                <th class="px-5 py-3 text-right font-medium text-gray-500">CGST</th>
                                <th class="px-5 py-3 text-right font-medium text-gray-500">SGST</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">
                                    (a) Outward taxable supplies (other than zero rated, nil rated and exempted)
                                </td>
                                <td class="px-5 py-3 text-right font-medium">{{ fmt(data.outward_supplies.taxable_value) }}</td>
                                <td class="px-5 py-3 text-right text-orange-600">{{ fmt(data.outward_supplies.igst) }}</td>
                                <td class="px-5 py-3 text-right text-blue-600">{{ fmt(data.outward_supplies.cgst) }}</td>
                                <td class="px-5 py-3 text-right text-green-600">{{ fmt(data.outward_supplies.sgst) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Total Tax Liability -->
                <div class="bg-white rounded-lg shadow p-5">
                    <h3 class="font-semibold text-gray-700 mb-4">6 — Payment of Tax</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b text-sm">
                            <span class="text-gray-600">IGST</span>
                            <span class="font-medium text-orange-700">{{ fmt(data.outward_supplies.igst) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b text-sm">
                            <span class="text-gray-600">CGST</span>
                            <span class="font-medium text-blue-700">{{ fmt(data.outward_supplies.cgst) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b text-sm">
                            <span class="text-gray-600">SGST/UTGST</span>
                            <span class="font-medium text-green-700">{{ fmt(data.outward_supplies.sgst) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-3 bg-indigo-50 px-4 rounded-lg">
                            <span class="font-bold text-gray-800">Total Tax Liability</span>
                            <span class="text-xl font-bold text-indigo-700">{{ fmt(data.total_tax_liability) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Note -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
                    <strong>Note:</strong> This is a system-generated summary for reference. Please verify all figures before filing on the GST portal. Due date for GSTR-3B is 20th of the following month.
                </div>

                <div class="flex gap-3">
                    <Link :href="route('reports.index')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                        Back to Reports
                    </Link>
                    <a :href="route('reports.gstr1') + `?month=${month}&year=${year}`" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                        View GSTR-1
                    </a>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
