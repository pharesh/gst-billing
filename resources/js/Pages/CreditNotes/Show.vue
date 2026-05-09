<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    creditNote: Object,
    gstGroups: Array,
});

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function del() {
    if (confirm('Delete this credit note? This cannot be undone.')) {
        router.delete(route('credit-notes.destroy', props.creditNote.id));
    }
}
</script>

<template>
    <Head :title="`Credit Note ${creditNote.credit_note_number}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">{{ creditNote.credit_note_number }}</h2>
                    <p class="text-sm text-gray-500">Credit Note · {{ creditNote.credit_note_date }}</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('credit-notes.index')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">← Back</Link>
                    <button @click="del" class="px-4 py-2 border border-red-300 text-red-600 rounded-lg text-sm hover:bg-red-50">Delete</button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">

                <!-- Header Info -->
                <div class="bg-white rounded-lg shadow p-6 grid grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Customer</h3>
                        <p class="font-semibold text-gray-800">{{ creditNote.customer?.name }}</p>
                        <p v-if="creditNote.customer?.gstin" class="text-xs text-gray-500 font-mono mt-0.5">{{ creditNote.customer.gstin }}</p>
                    </div>
                    <div class="text-right">
                        <div v-if="creditNote.invoice" class="text-sm text-gray-500 mb-1">
                            Against Invoice:
                            <Link :href="route('invoices.show', creditNote.invoice.id)" class="text-indigo-600 hover:underline font-medium">
                                {{ creditNote.invoice.invoice_number }}
                            </Link>
                        </div>
                        <div class="text-xs text-gray-400">Supply: {{ creditNote.supply_type }}</div>
                    </div>
                </div>

                <!-- Reason -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-5 py-3">
                    <span class="text-xs font-semibold text-amber-700 uppercase">Reason: </span>
                    <span class="text-sm text-amber-800">{{ creditNote.reason }}</span>
                </div>

                <!-- Items -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Description</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">HSN/SAC</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Qty</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Price</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">GST%</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in creditNote.items" :key="item.id">
                                <td class="px-4 py-3">{{ item.description }}</td>
                                <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ item.hsn_sac_code || '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ item.quantity }} {{ item.unit }}</td>
                                <td class="px-4 py-3 text-right">{{ fmt(item.price) }}</td>
                                <td class="px-4 py-3 text-right">{{ item.gst_rate }}%</td>
                                <td class="px-4 py-3 text-right font-medium text-red-600">{{ fmt(item.total_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- GST Breakdown -->
                    <div class="px-6 py-4 border-t bg-gray-50 flex justify-end">
                        <div class="w-64 space-y-1.5 text-sm">
                            <div class="flex justify-between text-gray-500">
                                <span>Subtotal</span>
                                <span>{{ fmt(creditNote.subtotal) }}</span>
                            </div>
                            <template v-for="g in gstGroups" :key="g.gst_rate">
                                <div v-if="g.cgst_amount > 0" class="flex justify-between text-gray-500">
                                    <span>CGST {{ g.gst_rate / 2 }}%</span>
                                    <span>{{ fmt(g.cgst_amount) }}</span>
                                </div>
                                <div v-if="g.sgst_amount > 0" class="flex justify-between text-gray-500">
                                    <span>SGST {{ g.gst_rate / 2 }}%</span>
                                    <span>{{ fmt(g.sgst_amount) }}</span>
                                </div>
                                <div v-if="g.igst_amount > 0" class="flex justify-between text-gray-500">
                                    <span>IGST {{ g.gst_rate }}%</span>
                                    <span>{{ fmt(g.igst_amount) }}</span>
                                </div>
                            </template>
                            <div class="flex justify-between font-bold text-red-600 border-t pt-1.5 text-base">
                                <span>Total Credit</span>
                                <span>{{ fmt(creditNote.total_amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="creditNote.notes" class="bg-white rounded-lg shadow p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase mb-1">Notes</p>
                    <p class="text-sm text-gray-700">{{ creditNote.notes }}</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
