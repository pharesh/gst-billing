<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    customers: Array,
});

const form = useForm({
    customer_id:   '',
    title:         '',
    frequency:     'monthly',
    start_date:    new Date().toISOString().slice(0, 10),
    end_date:      '',
    invoice_type:  'b2b',
    supply_type:   'intrastate',
    due_days:      15,
    notes:         '',
    terms:         '',
    items:         [newItem()],
});

function newItem() {
    return { description: '', hsn_sac_code: '', unit: 'Nos', quantity: 1, price: 0, gst_rate: 18, discount_percent: 0 };
}

function addItem()     { form.items.push(newItem()); }
function removeItem(i) { if (form.items.length > 1) form.items.splice(i, 1); }

function calcItem(item) {
    const gross   = item.price * item.quantity;
    const disc    = gross * (item.discount_percent || 0) / 100;
    const taxable = gross - disc;
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
    form.post(route('recurring-invoices.store'));
}
</script>

<template>
    <Head title="New Recurring Schedule" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">New Recurring Schedule</h2>
                <Link :href="route('recurring-invoices.index')" class="text-sm text-indigo-600 hover:underline">← Recurring Invoices</Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Schedule Config -->
                    <div class="bg-white rounded-lg shadow p-6 grid grid-cols-3 gap-4">
                        <div class="col-span-3">
                            <label class="text-sm font-medium text-gray-700 block mb-1">Schedule Title *</label>
                            <input v-model="form.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                   placeholder="e.g. Monthly Retainer – ABC Corp" required />
                            <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Customer *</label>
                            <select v-model="form.customer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                                <option value="">Select Customer</option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <p v-if="form.errors.customer_id" class="text-red-500 text-xs mt-1">{{ form.errors.customer_id }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Frequency *</label>
                            <select v-model="form.frequency" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Due Days After Invoice</label>
                            <input v-model.number="form.due_days" type="number" min="0" max="365" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Start Date *</label>
                            <input v-model="form.start_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">End Date (optional)</label>
                            <input v-model="form.end_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Supply Type *</label>
                            <select v-model="form.supply_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="intrastate">Intrastate (CGST + SGST)</option>
                                <option value="interstate">Interstate (IGST)</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Invoice Type</label>
                            <select v-model="form.invoice_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="b2b">B2B</option>
                                <option value="b2c">B2C</option>
                                <option value="export">Export</option>
                            </select>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-gray-800">Line Items</h3>
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
                                        <th class="px-3 py-2 text-right text-gray-600 font-medium w-24">Disc %</th>
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
                                        <td class="px-3 py-2">
                                            <input v-model.number="item.discount_percent" type="number" min="0" max="100" step="0.01" class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right" />
                                        </td>
                                        <td class="px-3 py-2 text-right font-medium">{{ fmt(calcItem(item).total) }}</td>
                                        <td class="px-2 py-2 text-center">
                                            <button type="button" @click="removeItem(i)" class="text-red-400 hover:text-red-600 text-xs" :disabled="form.items.length === 1">✕</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

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
                                <div class="flex justify-between border-t pt-1 font-bold text-gray-800">
                                    <span>Total (per invoice)</span>
                                    <span>{{ fmt(totals.total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white rounded-lg shadow p-6 grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Notes</label>
                            <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Terms</label>
                            <textarea v-model="form.terms" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <Link :href="route('recurring-invoices.index')" class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Creating...' : 'Create Schedule' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
