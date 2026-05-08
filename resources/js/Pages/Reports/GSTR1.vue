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

function downloadJson() {
    window.location.href = route('reports.gstr1.download') + `?month=${props.month}&year=${props.year}`;
}
</script>

<template>
    <Head :title="`GSTR-1 — ${MONTHS[month]} ${year}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('reports.index')" class="text-gray-500 hover:text-gray-700 text-sm">← Back</Link>
                    <h2 class="text-xl font-semibold text-gray-800">GSTR-1 — {{ MONTHS[month] }} {{ year }}</h2>
                </div>
                <button @click="downloadJson" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                    Download JSON
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Summary Cards -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-gray-500 uppercase mb-1">Total Invoices</div>
                        <div class="text-2xl font-bold text-gray-900">{{ data.summary.total_invoices }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-gray-500 uppercase mb-1">Taxable Value</div>
                        <div class="text-xl font-bold text-gray-900">{{ fmt(data.summary.total_taxable_value) }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-gray-500 uppercase mb-1">Total Tax</div>
                        <div class="text-xl font-bold text-indigo-700">
                            {{ fmt((data.summary.total_igst || 0) + (data.summary.total_cgst || 0) + (data.summary.total_sgst || 0)) }}
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-gray-500 uppercase mb-1">Invoice Value</div>
                        <div class="text-xl font-bold text-gray-900">{{ fmt(data.summary.total_invoice_value) }}</div>
                    </div>
                </div>

                <!-- Tax Breakup -->
                <div class="bg-white rounded-lg shadow p-5">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Tax Breakup</h3>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <div class="text-xs text-blue-500 mb-1">CGST</div>
                            <div class="text-lg font-bold text-blue-700">{{ fmt(data.summary.total_cgst) }}</div>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <div class="text-xs text-green-500 mb-1">SGST</div>
                            <div class="text-lg font-bold text-green-700">{{ fmt(data.summary.total_sgst) }}</div>
                        </div>
                        <div class="p-3 bg-orange-50 rounded-lg">
                            <div class="text-xs text-orange-500 mb-1">IGST</div>
                            <div class="text-lg font-bold text-orange-700">{{ fmt(data.summary.total_igst) }}</div>
                        </div>
                    </div>
                </div>

                <!-- B2B Invoices -->
                <div v-if="data.b2b && data.b2b.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-5 py-4 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-700">B2B Invoices (Registered Customers)</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Supplies made to GST-registered businesses</p>
                    </div>
                    <div v-for="entry in data.b2b" :key="entry.ctin" class="border-b last:border-0">
                        <div class="px-5 py-3 bg-gray-50 flex items-center gap-2">
                            <span class="font-mono text-sm font-semibold text-gray-800">{{ entry.ctin }}</span>
                            <span class="text-xs text-gray-400">({{ entry.inv.length }} invoice{{ entry.inv.length > 1 ? 's' : '' }})</span>
                        </div>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs">
                                <tr>
                                    <th class="px-5 py-2 text-left font-medium text-gray-500">Invoice No.</th>
                                    <th class="px-5 py-2 text-left font-medium text-gray-500">Date</th>
                                    <th class="px-5 py-2 text-left font-medium text-gray-500">POS</th>
                                    <th class="px-5 py-2 text-right font-medium text-gray-500">Value</th>
                                    <th class="px-5 py-2 text-right font-medium text-gray-500">Taxable</th>
                                    <th class="px-5 py-2 text-right font-medium text-gray-500">IGST</th>
                                    <th class="px-5 py-2 text-right font-medium text-gray-500">CGST</th>
                                    <th class="px-5 py-2 text-right font-medium text-gray-500">SGST</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="inv in entry.inv" :key="inv.inum" class="hover:bg-gray-50">
                                    <td class="px-5 py-2.5 font-mono text-indigo-600">{{ inv.inum }}</td>
                                    <td class="px-5 py-2.5 text-gray-500">{{ inv.idt }}</td>
                                    <td class="px-5 py-2.5 text-gray-500">{{ inv.pos }}</td>
                                    <td class="px-5 py-2.5 text-right font-medium">{{ fmt(inv.val) }}</td>
                                    <td class="px-5 py-2.5 text-right text-gray-600">
                                        {{ fmt(inv.itms.reduce((s, i) => s + i.itm_det.txval, 0)) }}
                                    </td>
                                    <td class="px-5 py-2.5 text-right text-orange-600">
                                        {{ fmt(inv.itms.reduce((s, i) => s + i.itm_det.iamt, 0)) }}
                                    </td>
                                    <td class="px-5 py-2.5 text-right text-blue-600">
                                        {{ fmt(inv.itms.reduce((s, i) => s + i.itm_det.camt, 0)) }}
                                    </td>
                                    <td class="px-5 py-2.5 text-right text-green-600">
                                        {{ fmt(inv.itms.reduce((s, i) => s + i.itm_det.samt, 0)) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- B2C Invoices -->
                <div v-if="data.b2cs && data.b2cs.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-5 py-4 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-700">B2CS — Unregistered Customers (Summary)</h3>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-5 py-2 text-left font-medium text-gray-500">Supply Type</th>
                                <th class="px-5 py-2 text-left font-medium text-gray-500">POS</th>
                                <th class="px-5 py-2 text-right font-medium text-gray-500">GST Rate</th>
                                <th class="px-5 py-2 text-right font-medium text-gray-500">Taxable Value</th>
                                <th class="px-5 py-2 text-right font-medium text-gray-500">IGST</th>
                                <th class="px-5 py-2 text-right font-medium text-gray-500">CGST</th>
                                <th class="px-5 py-2 text-right font-medium text-gray-500">SGST</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(row, i) in data.b2cs" :key="i" class="hover:bg-gray-50">
                                <td class="px-5 py-2.5">{{ row.sply_ty }}</td>
                                <td class="px-5 py-2.5 text-gray-500">{{ row.pos }}</td>
                                <td class="px-5 py-2.5 text-right">{{ row.rt }}%</td>
                                <td class="px-5 py-2.5 text-right font-medium">{{ fmt(row.txval) }}</td>
                                <td class="px-5 py-2.5 text-right text-orange-600">{{ fmt(row.iamt) }}</td>
                                <td class="px-5 py-2.5 text-right text-blue-600">{{ fmt(row.camt) }}</td>
                                <td class="px-5 py-2.5 text-right text-green-600">{{ fmt(row.samt) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- No Data -->
                <div v-if="(!data.b2b || data.b2b.length === 0) && (!data.b2cs || data.b2cs.length === 0)"
                     class="bg-white rounded-lg shadow p-12 text-center">
                    <div class="text-4xl mb-3">📄</div>
                    <div class="text-gray-500">No invoices found for {{ MONTHS[month] }} {{ year }}</div>
                    <Link :href="route('invoices.create')" class="mt-3 inline-block text-indigo-600 hover:underline text-sm">Create an invoice</Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
