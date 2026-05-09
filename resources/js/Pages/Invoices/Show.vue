<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    invoice: Object,
    gstGroups: Array,
    amountInWords: String,
});

const showPaymentForm = ref(false);

const paymentForm = useForm({
    amount: props.invoice.balance_due,
    payment_date: new Date().toISOString().slice(0, 10),
    payment_method: 'bank_transfer',
    reference_number: '',
    notes: '',
});

function recordPayment() {
    paymentForm.post(route('payments.store', props.invoice.id), {
        onSuccess: () => { showPaymentForm.value = false; paymentForm.reset(); },
    });
}

function deletePayment(id) {
    if (confirm('Remove this payment?')) {
        router.delete(route('payments.destroy', id));
    }
}

function sendWhatsApp() {
    if (confirm('Send invoice to customer via WhatsApp?')) {
        router.post(route('invoices.whatsapp', props.invoice.id));
    }
}

function sendEmail() {
    if (confirm('Send invoice to customer via email?')) {
        router.post(route('invoices.email', props.invoice.id));
    }
}

function sendReminder() {
    if (confirm('Send payment reminder to customer via email & WhatsApp?')) {
        router.post(route('invoices.reminder', props.invoice.id));
    }
}

function statusClass(s) {
    return { paid: 'bg-green-100 text-green-800', unpaid: 'bg-red-100 text-red-800', partial: 'bg-yellow-100 text-yellow-800' }[s] || '';
}

function fmt(n) {
    return '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('invoices.index')" class="text-gray-500 hover:text-gray-700">← Back</Link>
                    <h2 class="text-xl font-semibold text-gray-800">{{ invoice.invoice_number }}</h2>
                    <span class="px-2 py-1 rounded-full text-xs font-bold" :class="statusClass(invoice.payment_status)">
                        {{ invoice.payment_status.toUpperCase() }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <Link v-if="invoice.payment_status !== 'paid'" :href="route('invoices.edit', invoice.id)" class="px-3 py-2 border border-gray-400 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                        Edit
                    </Link>
                    <button @click="sendWhatsApp" class="px-3 py-2 border border-green-500 text-green-700 rounded-lg text-sm hover:bg-green-50">
                        WhatsApp
                    </button>
                    <button @click="sendEmail" class="px-3 py-2 border border-blue-400 text-blue-700 rounded-lg text-sm hover:bg-blue-50">
                        Send Email
                    </button>
                    <button @click="sendReminder" v-if="invoice.payment_status !== 'paid'" class="px-3 py-2 border border-orange-400 text-orange-700 rounded-lg text-sm hover:bg-orange-50">
                        Send Reminder
                    </button>
                    <a :href="route('invoices.download', invoice.id)" class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                        Download PDF
                    </a>
                    <Link :href="route('credit-notes.create', { invoice_id: invoice.id })" class="px-3 py-2 border border-amber-400 text-amber-700 rounded-lg text-sm hover:bg-amber-50">
                        Credit Note
                    </Link>
                    <button @click="showPaymentForm = !showPaymentForm" v-if="invoice.payment_status !== 'paid'" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                        Record Payment
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Flash messages -->
                <div v-if="$page.props.flash?.success" class="px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Record Payment Form -->
                <div v-if="showPaymentForm" class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold">Record Payment</h3>
                        <span class="text-sm text-gray-500">Balance Due: <span class="font-bold text-red-600">{{ fmt(invoice.balance_due) }}</span></span>
                    </div>
                    <form @submit.prevent="recordPayment" class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Amount (₹)</label>
                            <input v-model.number="paymentForm.amount" type="number" step="0.01"
                                   :max="invoice.balance_due" min="0.01"
                                   class="w-full border rounded-lg px-3 py-2 text-sm"
                                   :class="paymentForm.errors.amount ? 'border-red-400 bg-red-50' : 'border-gray-300'" required />
                            <p v-if="paymentForm.errors.amount" class="text-red-500 text-xs mt-1">{{ paymentForm.errors.amount }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Date</label>
                            <input v-model="paymentForm.payment_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required />
                            <p v-if="paymentForm.errors.payment_date" class="text-red-500 text-xs mt-1">{{ paymentForm.errors.payment_date }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Method</label>
                            <select v-model="paymentForm.payment_method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Reference / UTR No.</label>
                            <input v-model="paymentForm.reference_number" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div class="col-span-2 flex items-end gap-2">
                            <button type="submit" :disabled="paymentForm.processing" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 disabled:opacity-50">Save Payment</button>
                            <button type="button" @click="paymentForm.amount = invoice.balance_due" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Full Amount</button>
                            <button type="button" @click="showPaymentForm = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Invoice Header -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <div class="text-lg font-bold text-gray-900">{{ invoice.tenant.name }}</div>
                            <div class="text-sm text-gray-500 mt-1">{{ invoice.tenant.address }}</div>
                            <div v-if="invoice.tenant.gstin" class="text-sm text-gray-500">GSTIN: {{ invoice.tenant.gstin }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Invoice Date: <span class="text-gray-800 font-medium">{{ invoice.invoice_date }}</span></div>
                            <div v-if="invoice.due_date" class="text-sm text-gray-500">Due Date: <span class="text-gray-800 font-medium">{{ invoice.due_date }}</span></div>
                            <div class="text-sm text-gray-500 mt-1">Type: {{ invoice.invoice_type.toUpperCase() }} / {{ invoice.supply_type }}</div>
                        </div>
                    </div>

                    <div class="border-t mt-4 pt-4">
                        <div class="text-xs text-gray-400 uppercase font-medium mb-1">Bill To</div>
                        <div class="font-semibold text-gray-800">{{ invoice.customer.name }}</div>
                        <div v-if="invoice.customer.gstin" class="text-sm text-gray-500">GSTIN: {{ invoice.customer.gstin }}</div>
                        <div class="text-sm text-gray-500">{{ invoice.customer.address }}, {{ invoice.customer.city }}</div>
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
                                <th class="px-4 py-3 text-right font-medium text-gray-600">GST</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(item, i) in invoice.items" :key="item.id">
                                <td class="px-4 py-3 text-gray-500">{{ i + 1 }}</td>
                                <td class="px-4 py-3">{{ item.description }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ item.hsn_sac_code || '—' }}</td>
                                <td class="px-4 py-3 text-center">{{ item.quantity }} {{ item.unit }}</td>
                                <td class="px-4 py-3 text-right">{{ fmt(item.price) }}</td>
                                <td class="px-4 py-3 text-right">{{ fmt(item.taxable_amount) }}</td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ item.gst_rate }}%</td>
                                <td class="px-4 py-3 text-right font-medium">{{ fmt(item.total_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Totals -->
                    <div class="p-4 border-t">
                        <div class="flex justify-end">
                            <div class="w-72 space-y-1 text-sm">
                                <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>{{ fmt(invoice.subtotal) }}</span></div>
                                <div v-if="invoice.cgst_amount > 0" class="flex justify-between text-gray-600"><span>CGST</span><span>{{ fmt(invoice.cgst_amount) }}</span></div>
                                <div v-if="invoice.sgst_amount > 0" class="flex justify-between text-gray-600"><span>SGST</span><span>{{ fmt(invoice.sgst_amount) }}</span></div>
                                <div v-if="invoice.igst_amount > 0" class="flex justify-between text-gray-600"><span>IGST</span><span>{{ fmt(invoice.igst_amount) }}</span></div>
                                <div class="flex justify-between font-bold text-gray-900 border-t pt-1 text-base"><span>Total</span><span>{{ fmt(invoice.total_amount) }}</span></div>
                                <div v-if="invoice.amount_paid > 0" class="flex justify-between text-green-600"><span>Paid</span><span>{{ fmt(invoice.amount_paid) }}</span></div>
                                <div v-if="invoice.balance_due > 0" class="flex justify-between font-bold text-red-600"><span>Balance Due</span><span>{{ fmt(invoice.balance_due) }}</span></div>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-gray-500 italic">{{ amountInWords }}</div>
                    </div>
                </div>

                <!-- Payment History -->
                <div v-if="invoice.payments.length > 0" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-base font-semibold mb-3">Payment History</h3>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Date</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Method</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Reference</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600">Amount</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="p in invoice.payments" :key="p.id">
                                <td class="px-3 py-2">{{ p.payment_date }}</td>
                                <td class="px-3 py-2 capitalize">{{ p.payment_method.replace('_', ' ') }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ p.reference_number || '—' }}</td>
                                <td class="px-3 py-2 text-right font-medium text-green-700">{{ fmt(p.amount) }}</td>
                                <td class="px-3 py-2 text-right">
                                    <button @click="deletePayment(p.id)" class="text-xs text-red-500 hover:underline">Remove</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
