<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    quotation: Object,
    gstGroups: Array,
    amountInWords: String,
});

const statusForm = useForm({ status: props.quotation.status });

function updateStatus(newStatus) {
    statusForm.status = newStatus;
    statusForm.post(route('quotations.status', props.quotation.id));
}

function convertToInvoice() {
    if (confirm('Convert this quotation to a Tax Invoice? This cannot be undone.')) {
        router.post(route('quotations.convert', props.quotation.id));
    }
}

function deleteQuotation() {
    if (confirm('Delete this quotation?')) {
        router.delete(route('quotations.destroy', props.quotation.id));
    }
}

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function statusClass(s) {
    return {
        draft:     'bg-gray-100 text-gray-600',
        sent:      'bg-blue-100 text-blue-700',
        accepted:  'bg-green-100 text-green-700',
        rejected:  'bg-red-100 text-red-700',
        converted: 'bg-purple-100 text-purple-700',
    }[s] || 'bg-gray-100 text-gray-600';
}
</script>

<template>
    <Head :title="`Quotation ${quotation.quotation_number}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">{{ quotation.quotation_number }}</h2>
                    <span class="mt-1 px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(quotation.status)">
                        {{ quotation.status.toUpperCase() }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <!-- Status actions -->
                    <template v-if="quotation.status === 'draft'">
                        <button @click="updateStatus('sent')" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Mark as Sent</button>
                    </template>
                    <template v-if="quotation.status === 'sent'">
                        <button @click="updateStatus('accepted')" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">Mark Accepted</button>
                        <button @click="updateStatus('rejected')" class="px-3 py-1.5 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600">Mark Rejected</button>
                    </template>

                    <!-- Convert to Invoice -->
                    <button
                        v-if="quotation.status !== 'converted' && quotation.status !== 'rejected'"
                        @click="convertToInvoice"
                        class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 font-medium"
                    >
                        Convert to Invoice
                    </button>

                    <a :href="route('quotations.download', quotation.id)" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 text-gray-700">
                        Download PDF
                    </a>

                    <button v-if="quotation.status !== 'converted'" @click="deleteQuotation" class="px-3 py-1.5 border border-red-200 rounded-lg text-sm text-red-600 hover:bg-red-50">
                        Delete
                    </button>

                    <Link :href="route('quotations.index')" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 text-gray-600">
                        Back
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-4">

                <!-- Converted Banner -->
                <div v-if="quotation.status === 'converted'" class="bg-purple-50 border border-purple-200 rounded-lg p-4 flex items-center justify-between">
                    <div class="text-sm text-purple-800">
                        This quotation has been converted to a Tax Invoice.
                    </div>
                    <Link :href="route('invoices.show', quotation.converted_invoice_id)" class="text-purple-700 font-medium text-sm hover:underline">
                        View Invoice →
                    </Link>
                </div>

                <!-- Details & Customer -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-500 mb-3">QUOTATION DETAILS</h3>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Quote #</span><span class="font-mono font-medium">{{ quotation.quotation_number }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Date</span><span>{{ quotation.quotation_date }}</span></div>
                            <div class="flex justify-between" v-if="quotation.valid_until"><span class="text-gray-500">Valid Until</span><span>{{ quotation.valid_until }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Type</span><span>{{ quotation.invoice_type.toUpperCase() }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Supply</span><span>{{ quotation.supply_type }}</span></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-500 mb-3">CUSTOMER</h3>
                        <div class="font-medium text-gray-800">{{ quotation.customer?.name }}</div>
                        <div v-if="quotation.customer?.gstin" class="text-xs text-gray-500 font-mono mt-1">{{ quotation.customer.gstin }}</div>
                        <div v-if="quotation.customer?.address" class="text-xs text-gray-500 mt-1">{{ quotation.customer.address }}</div>
                        <div v-if="quotation.customer?.city" class="text-xs text-gray-500">{{ quotation.customer.city }}, {{ quotation.customer.state }}</div>
                        <div v-if="quotation.customer?.phone" class="text-xs text-gray-500 mt-1">{{ quotation.customer.phone }}</div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">#</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Description</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">HSN/SAC</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Qty</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Rate</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Taxable</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">GST</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(item, i) in quotation.items" :key="item.id">
                                <td class="px-4 py-3 text-gray-500">{{ i + 1 }}</td>
                                <td class="px-4 py-3">{{ item.description }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ item.hsn_sac_code || '—' }}</td>
                                <td class="px-4 py-3 text-center">{{ item.quantity }} {{ item.unit }}</td>
                                <td class="px-4 py-3 text-right">{{ fmt(item.price) }}</td>
                                <td class="px-4 py-3 text-right">{{ fmt(item.taxable_amount) }}</td>
                                <td class="px-4 py-3 text-right text-xs text-gray-500">{{ item.gst_rate }}%</td>
                                <td class="px-4 py-3 text-right font-medium">{{ fmt(item.total_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals & GST Summary -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-500 mb-3">GST SUMMARY</h3>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500 border-b">
                                    <th class="text-left pb-1">Rate</th>
                                    <th class="text-right pb-1">Taxable</th>
                                    <th class="text-right pb-1">CGST</th>
                                    <th class="text-right pb-1">SGST</th>
                                    <th class="text-right pb-1">IGST</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in gstGroups" :key="g.gst_rate" class="border-b last:border-0">
                                    <td class="py-1">{{ g.gst_rate }}%</td>
                                    <td class="text-right py-1">{{ fmt(g.taxable_amount) }}</td>
                                    <td class="text-right py-1 text-gray-500">{{ fmt(g.cgst_amount) }}</td>
                                    <td class="text-right py-1 text-gray-500">{{ fmt(g.sgst_amount) }}</td>
                                    <td class="text-right py-1 text-gray-500">{{ fmt(g.igst_amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="quotation.notes || quotation.terms" class="mt-4 text-xs text-gray-500 space-y-1">
                            <div v-if="quotation.notes"><span class="font-medium">Notes:</span> {{ quotation.notes }}</div>
                            <div v-if="quotation.terms"><span class="font-medium">Terms:</span> {{ quotation.terms }}</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-500 mb-3">AMOUNT SUMMARY</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>{{ fmt(quotation.subtotal) }}</span></div>
                            <div class="flex justify-between" v-if="quotation.cgst_amount"><span class="text-gray-500">CGST</span><span>{{ fmt(quotation.cgst_amount) }}</span></div>
                            <div class="flex justify-between" v-if="quotation.sgst_amount"><span class="text-gray-500">SGST</span><span>{{ fmt(quotation.sgst_amount) }}</span></div>
                            <div class="flex justify-between" v-if="quotation.igst_amount"><span class="text-gray-500">IGST</span><span>{{ fmt(quotation.igst_amount) }}</span></div>
                            <div class="flex justify-between" v-if="quotation.discount_amount"><span class="text-gray-500">Discount</span><span class="text-red-600">-{{ fmt(quotation.discount_amount) }}</span></div>
                            <div class="flex justify-between font-bold text-base border-t pt-2">
                                <span>Total</span><span>{{ fmt(quotation.total_amount) }}</span>
                            </div>
                            <div class="text-xs text-gray-500 italic mt-1">{{ amountInWords }}</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
