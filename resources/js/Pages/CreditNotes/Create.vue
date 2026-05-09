<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    customers: Array,
    invoice: Object,
    tenant: Object,
});

const form = useForm({
    customer_id:      props.invoice?.customer_id || '',
    invoice_id:       props.invoice?.id || '',
    credit_note_date: new Date().toISOString().slice(0, 10),
    reason:           '',
    supply_type:      props.invoice?.supply_type || 'intrastate',
    notes:            '',
    items:            props.invoice
        ? props.invoice.items.map(i => ({
            description:  i.description,
            hsn_sac_code: i.hsn_sac_code || '',
            unit:         i.unit,
            quantity:     i.quantity,
            price:        i.price,
            gst_rate:     i.gst_rate,
          }))
        : [newItem()],
});

function newItem() {
    return { description: '', hsn_sac_code: '', unit: 'Nos', quantity: 1, price: 0, gst_rate: 18 };
}

function addItem()      { form.items.push(newItem()); }
function removeItem(i)  { if (form.items.length > 1) form.items.splice(i, 1); }

function calcItem(item) {
    const taxable = item.price * item.quantity;
    const tax     = taxable * item.gst_rate / 100;
    return { taxable: taxable.toFixed(2), tax: tax.toFixed(2), total: (taxable + tax).toFixed(2) };
}

const totals = computed(() => {
    let sub = 0, tax = 0;
    form.items.forEach(item => {
        const c = calcItem(item);
        sub += parseFloat(c.taxable);
        tax += parseFloat(c.tax);
    });
    return { subtotal: sub.toFixed(2), tax: tax.toFixed(2), total: (sub + tax).toFixed(2) };
});

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function submit() {
    form.post(route('credit-notes.store'));
}
</script>

<template>
    <Head title="New Credit Note" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">New Credit Note</h2>
                <Link :href="route('credit-notes.index')" class="text-sm text-indigo-600 hover:underline">← Credit Notes</Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Header -->
                    <div class="bg-white rounded-lg shadow p-6 grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Customer *</label>
                            <select v-model="form.customer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                                <option value="">Select Customer</option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <p v-if="form.errors.customer_id" class="text-red-500 text-xs mt-1">{{ form.errors.customer_id }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Against Invoice (optional)</label>
                            <input v-model="form.invoice_id" type="text" placeholder="Invoice ID"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            <p v-if="invoice" class="text-xs text-gray-400 mt-1">Linked to {{ invoice.invoice_number }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Credit Note Date *</label>
                            <input v-model="form.credit_note_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Supply Type *</label>
                            <select v-model="form.supply_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="intrastate">Intrastate (CGST + SGST)</option>
                                <option value="interstate">Interstate (IGST)</option>
                            </select>
                        </div>

                        <div class="col-span-2">
                            <label class="text-sm font-medium text-gray-700 block mb-1">Reason *</label>
                            <input v-model="form.reason" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                   placeholder="e.g. Goods returned, Price correction, Duplicate invoice..." required />
                            <p v-if="form.errors.reason" class="text-red-500 text-xs mt-1">{{ form.errors.reason }}</p>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-gray-800">Items</h3>
                            <button type="button" @click="addItem" class="text-sm text-indigo-600 hover:underline">+ Add Item</button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-gray-600 font-medium">Description</th>
                                        <th class="px-3 py-2 text-left text-gray-600 font-medium w-20">HSN/SAC</th>
                                        <th class="px-3 py-2 text-left text-gray-600 font-medium w-16">Unit</th>
                                        <th class="px-3 py-2 text-right text-gray-600 font-medium w-20">Qty</th>
                                        <th class="px-3 py-2 text-right text-gray-600 font-medium w-24">Price</th>
                                        <th class="px-3 py-2 text-right text-gray-600 font-medium w-20">GST %</th>
                                        <th class="px-3 py-2 text-right text-gray-600 font-medium w-28">Total</th>
                                        <th class="px-2 py-2 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, i) in form.items" :key="i" class="border-t">
                                        <td class="px-3 py-2">
                                            <input v-model="item.description" type="text" class="w-full border border-gray-200 rounded px-2 py-1 text-sm" required />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input v-model="item.hsn_sac_code" type="text" class="w-full border border-gray-200 rounded px-2 py-1 text-sm" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input v-model="item.unit" type="text" class="w-full border border-gray-200 rounded px-2 py-1 text-sm" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input v-model.number="item.quantity" type="number" min="0.001" step="0.001" class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input v-model.number="item.price" type="number" min="0" step="0.01" class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <select v-model.number="item.gst_rate" class="w-full border border-gray-200 rounded px-2 py-1 text-sm">
                                                <option v-for="r in [0,5,12,18,28]" :key="r" :value="r">{{ r }}%</option>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2 text-right font-medium text-red-600">
                                            {{ fmt(calcItem(item).total) }}
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <button type="button" @click="removeItem(i)" class="text-red-400 hover:text-red-600 text-xs" :disabled="form.items.length === 1">✕</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Totals -->
                        <div class="mt-4 flex justify-end">
                            <div class="w-64 space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Subtotal</span>
                                    <span class="font-medium">{{ fmt(totals.subtotal) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">GST</span>
                                    <span class="font-medium">{{ fmt(totals.tax) }}</span>
                                </div>
                                <div class="flex justify-between border-t pt-1 font-bold text-red-600">
                                    <span>Total Credit</span>
                                    <span>{{ fmt(totals.total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <label class="text-sm font-medium text-gray-700 block mb-1">Internal Notes</label>
                        <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <Link :href="route('credit-notes.index')" class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Issuing...' : 'Issue Credit Note' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
