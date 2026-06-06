<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    purchase: Object,
    gstGroups: Array,
    amountInWords: String,
});

const showPayForm = ref(false);
const payForm = useForm({
    amount: props.purchase.balance_due,
    payment_date: new Date().toISOString().slice(0, 10),
    payment_method: 'bank_transfer',
    reference: '',
});

function recordPayment() {
    payForm.post(route('purchases.mark-paid', props.purchase.id), {
        onSuccess: () => { showPayForm.value = false; },
    });
}

function deleteBill() {
    if (confirm('Delete this purchase bill?')) {
        router.delete(route('purchases.destroy', props.purchase.id));
    }
}

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function statusClass(s) {
    return { paid: 'bg-green-100 text-green-800', unpaid: 'bg-red-100 text-red-800', partial: 'bg-yellow-100 text-yellow-800' }[s] || '';
}
</script>

<template>
    <Head :title="`Purchase ${purchase.bill_number}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('purchases.index')" class="text-gray-500 hover:text-gray-700">← Back</Link>
                    <h2 class="text-xl font-semibold text-gray-800">{{ purchase.bill_number }}</h2>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="statusClass(purchase.payment_status)">
                        {{ purchase.payment_status.toUpperCase() }}
                    </span>
                    <span v-if="purchase.itc_eligible" class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">ITC Eligible</span>
                </div>
                <div class="flex gap-2">
                    <button v-if="purchase.payment_status !== 'paid'" @click="showPayForm = !showPayForm"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                        Record Payment
                    </button>
                    <Link :href="route('debit-notes.create', { purchase_invoice_id: purchase.id })"
                          class="px-3 py-2 border border-orange-300 text-orange-700 rounded-lg text-sm hover:bg-orange-50">
                        Debit Note
                    </Link>
                    <button @click="deleteBill" class="px-3 py-2 border border-red-200 text-red-600 rounded-lg text-sm hover:bg-red-50">Delete</button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-4">

                <!-- Flash -->
                <div v-if="$page.props.flash?.success" class="px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Pay Form -->
                <div v-if="showPayForm" class="bg-white rounded-lg shadow p-5">
                    <h3 class="text-base font-semibold mb-4">Record Payment to Supplier</h3>
                    <form @submit.prevent="recordPayment" class="grid grid-cols-4 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Amount *</label>
                            <input v-model.number="payForm.amount" type="number" step="0.01" min="0.01"
                                   :max="purchase.balance_due"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Date *</label>
                            <input v-model="payForm.payment_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Method</label>
                            <select v-model="payForm.payment_method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Reference</label>
                            <input v-model="payForm.reference" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div class="col-span-4 flex gap-2">
                            <button type="submit" :disabled="payForm.processing" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 disabled:opacity-50">Save</button>
                            <button type="button" @click="showPayForm = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Bill Info -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-500 mb-3">BILL DETAILS</h3>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Bill #</span><span class="font-mono font-medium">{{ purchase.bill_number }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Date</span><span>{{ purchase.bill_date }}</span></div>
                            <div class="flex justify-between" v-if="purchase.due_date"><span class="text-gray-500">Due</span><span>{{ purchase.due_date }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Supply Type</span><span>{{ purchase.supply_type }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">ITC Eligible</span><span :class="purchase.itc_eligible ? 'text-green-600 font-medium' : 'text-gray-400'">{{ purchase.itc_eligible ? 'Yes' : 'No' }}</span></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-500 mb-3">SUPPLIER</h3>
                        <div class="font-medium text-gray-800">{{ purchase.supplier?.name }}</div>
                        <div v-if="purchase.supplier?.gstin" class="text-xs text-gray-500 font-mono mt-1">{{ purchase.supplier.gstin }}</div>
                        <div v-if="purchase.supplier?.address" class="text-xs text-gray-500 mt-1">{{ purchase.supplier.address }}</div>
                        <div v-if="purchase.supplier?.city" class="text-xs text-gray-500">{{ purchase.supplier.city }}, {{ purchase.supplier.state }}</div>
                    </div>
                </div>

                <!-- Items -->
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
                                <th class="px-4 py-3 text-right font-medium text-green-600">ITC (Tax)</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(item, i) in purchase.items" :key="item.id">
                                <td class="px-4 py-3 text-gray-500">{{ i + 1 }}</td>
                                <td class="px-4 py-3">{{ item.description }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ item.hsn_sac_code || '—' }}</td>
                                <td class="px-4 py-3 text-center">{{ item.quantity }} {{ item.unit }}</td>
                                <td class="px-4 py-3 text-right">{{ fmt(item.price) }}</td>
                                <td class="px-4 py-3 text-right">{{ fmt(item.taxable_amount) }}</td>
                                <td class="px-4 py-3 text-right text-green-600 font-medium">
                                    {{ fmt((item.cgst_amount || 0) + (item.sgst_amount || 0) + (item.igst_amount || 0)) }}
                                    <span class="text-xs text-gray-400">({{ item.gst_rate }}%)</span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium">{{ fmt(item.total_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-green-50 rounded-lg shadow p-5 border border-green-100">
                        <h3 class="text-sm font-semibold text-green-700 mb-3">INPUT TAX CREDIT (ITC)</h3>
                        <div v-if="purchase.itc_eligible" class="space-y-2 text-sm">
                            <div class="flex justify-between" v-if="purchase.cgst_amount"><span class="text-gray-500">CGST Credit</span><span class="font-medium text-green-700">{{ fmt(purchase.cgst_amount) }}</span></div>
                            <div class="flex justify-between" v-if="purchase.sgst_amount"><span class="text-gray-500">SGST Credit</span><span class="font-medium text-green-700">{{ fmt(purchase.sgst_amount) }}</span></div>
                            <div class="flex justify-between" v-if="purchase.igst_amount"><span class="text-gray-500">IGST Credit</span><span class="font-medium text-green-700">{{ fmt(purchase.igst_amount) }}</span></div>
                            <div class="flex justify-between font-bold text-green-800 border-t pt-2">
                                <span>Total ITC</span>
                                <span>{{ fmt((purchase.cgst_amount || 0) + (purchase.sgst_amount || 0) + (purchase.igst_amount || 0)) }}</span>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-400">This bill is not marked as ITC eligible.</div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-500 mb-3">AMOUNT SUMMARY</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>{{ fmt(purchase.subtotal) }}</span></div>
                            <div v-if="purchase.cgst_amount" class="flex justify-between"><span class="text-gray-500">CGST</span><span>{{ fmt(purchase.cgst_amount) }}</span></div>
                            <div v-if="purchase.sgst_amount" class="flex justify-between"><span class="text-gray-500">SGST</span><span>{{ fmt(purchase.sgst_amount) }}</span></div>
                            <div v-if="purchase.igst_amount" class="flex justify-between"><span class="text-gray-500">IGST</span><span>{{ fmt(purchase.igst_amount) }}</span></div>
                            <div class="flex justify-between font-bold border-t pt-2"><span>Total</span><span>{{ fmt(purchase.total_amount) }}</span></div>
                            <div v-if="purchase.amount_paid > 0" class="flex justify-between text-green-600"><span>Paid</span><span>{{ fmt(purchase.amount_paid) }}</span></div>
                            <div v-if="purchase.balance_due > 0" class="flex justify-between font-bold text-red-600"><span>Balance Due</span><span>{{ fmt(purchase.balance_due) }}</span></div>
                        </div>
                        <div class="mt-3 text-xs text-gray-400 italic">{{ amountInWords }}</div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
