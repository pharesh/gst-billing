<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    debitNote: Object,
    gstGroups: Array,
    amountInWords: String,
});

function deleteNote() {
    if (confirm('Delete this debit note?')) {
        router.delete(route('debit-notes.destroy', props.debitNote.id));
    }
}

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head :title="`Debit Note ${debitNote.debit_note_number}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('debit-notes.index')" class="text-gray-500 hover:text-gray-700">← Back</Link>
                    <h2 class="text-xl font-semibold text-gray-800">{{ debitNote.debit_note_number }}</h2>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">PURCHASE RETURN</span>
                </div>
                <button @click="deleteNote" class="px-3 py-2 border border-red-200 text-red-600 rounded-lg text-sm hover:bg-red-50">Delete</button>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-4">

                <!-- Flash -->
                <div v-if="$page.props.flash?.success" class="px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Info Cards -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-500 mb-3">DEBIT NOTE DETAILS</h3>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Note #</span><span class="font-mono font-medium">{{ debitNote.debit_note_number }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Date</span><span>{{ debitNote.debit_note_date }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Supply Type</span><span>{{ debitNote.supply_type }}</span></div>
                            <div v-if="debitNote.reason" class="flex justify-between"><span class="text-gray-500">Reason</span><span class="text-right text-xs max-w-48">{{ debitNote.reason }}</span></div>
                            <div v-if="debitNote.purchase_invoice" class="flex justify-between">
                                <span class="text-gray-500">Against Bill</span>
                                <Link :href="route('purchases.show', debitNote.purchase_invoice.id)" class="text-indigo-600 hover:underline font-mono text-xs">
                                    {{ debitNote.purchase_invoice.bill_number }}
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-500 mb-3">SUPPLIER</h3>
                        <div class="font-medium text-gray-800">{{ debitNote.supplier?.name }}</div>
                        <div v-if="debitNote.supplier?.gstin" class="text-xs text-gray-500 font-mono mt-1">{{ debitNote.supplier.gstin }}</div>
                        <div v-if="debitNote.supplier?.address" class="text-xs text-gray-500 mt-1">{{ debitNote.supplier.address }}</div>
                        <div v-if="debitNote.supplier?.city" class="text-xs text-gray-500">{{ debitNote.supplier.city }}, {{ debitNote.supplier.state }}</div>
                    </div>
                </div>

                <!-- Items Table -->
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
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Tax</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(item, i) in debitNote.items" :key="item.id">
                                <td class="px-4 py-3 text-gray-500">{{ i + 1 }}</td>
                                <td class="px-4 py-3">{{ item.description }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ item.hsn_sac_code || '—' }}</td>
                                <td class="px-4 py-3 text-center">{{ item.quantity }} {{ item.unit }}</td>
                                <td class="px-4 py-3 text-right">{{ fmt(item.price) }}</td>
                                <td class="px-4 py-3 text-right">{{ fmt(item.taxable_amount) }}</td>
                                <td class="px-4 py-3 text-right text-red-600 font-medium">
                                    {{ fmt((item.cgst_amount || 0) + (item.sgst_amount || 0) + (item.igst_amount || 0)) }}
                                    <span class="text-xs text-gray-400">({{ item.gst_rate }}%)</span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium">{{ fmt(item.total_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="bg-white rounded-lg shadow p-5">
                    <h3 class="text-sm font-semibold text-gray-500 mb-3">AMOUNT SUMMARY</h3>
                    <div class="max-w-sm ml-auto space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Subtotal (Taxable)</span><span>{{ fmt(debitNote.subtotal) }}</span></div>
                        <div v-if="debitNote.cgst_amount" class="flex justify-between"><span class="text-gray-500">CGST</span><span>{{ fmt(debitNote.cgst_amount) }}</span></div>
                        <div v-if="debitNote.sgst_amount" class="flex justify-between"><span class="text-gray-500">SGST</span><span>{{ fmt(debitNote.sgst_amount) }}</span></div>
                        <div v-if="debitNote.igst_amount" class="flex justify-between"><span class="text-gray-500">IGST</span><span>{{ fmt(debitNote.igst_amount) }}</span></div>
                        <div class="flex justify-between font-bold text-red-600 border-t pt-2 text-base">
                            <span>Total Return Value</span>
                            <span>{{ fmt(debitNote.total_amount) }}</span>
                        </div>
                        <div class="text-xs text-gray-400 italic">{{ amountInWords }}</div>
                    </div>
                </div>

                <!-- GST Breakdown -->
                <div v-if="gstGroups?.length" class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">GST Rate</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Taxable Amount</th>
                                <th v-if="debitNote.supply_type === 'intrastate'" class="px-4 py-3 text-right font-medium text-gray-600">CGST</th>
                                <th v-if="debitNote.supply_type === 'intrastate'" class="px-4 py-3 text-right font-medium text-gray-600">SGST</th>
                                <th v-else class="px-4 py-3 text-right font-medium text-gray-600">IGST</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="g in gstGroups" :key="g.gst_rate">
                                <td class="px-4 py-3">{{ g.gst_rate }}%</td>
                                <td class="px-4 py-3 text-right">{{ fmt(g.taxable_amount) }}</td>
                                <td v-if="debitNote.supply_type === 'intrastate'" class="px-4 py-3 text-right">{{ fmt(g.cgst_amount) }}</td>
                                <td v-if="debitNote.supply_type === 'intrastate'" class="px-4 py-3 text-right">{{ fmt(g.sgst_amount) }}</td>
                                <td v-else class="px-4 py-3 text-right">{{ fmt(g.igst_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Notes -->
                <div v-if="debitNote.notes" class="bg-white rounded-lg shadow p-5">
                    <div class="text-sm font-semibold text-gray-500 mb-1">Notes</div>
                    <div class="text-sm text-gray-700">{{ debitNote.notes }}</div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
