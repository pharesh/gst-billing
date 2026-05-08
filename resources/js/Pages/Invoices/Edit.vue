<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    invoice:   Object,
    customers: Array,
    products:  Array,
    tenant:    Object,
});

const form = useForm({
    customer_id:  props.invoice.customer_id,
    invoice_date: props.invoice.invoice_date,
    due_date:     props.invoice.due_date ?? '',
    invoice_type: props.invoice.invoice_type,
    supply_type:  props.invoice.supply_type,
    notes:        props.invoice.notes ?? '',
    terms:        props.invoice.terms ?? '',
    items: props.invoice.items.map(item => ({
        product_id:       item.product_id ?? '',
        description:      item.description,
        hsn_sac_code:     item.hsn_sac_code ?? '',
        unit:             item.unit,
        quantity:         item.quantity,
        price:            item.price,
        gst_rate:         item.gst_rate,
        discount_percent: item.discount_percent ?? 0,
    })),
});

function newItem() {
    return { product_id: '', description: '', hsn_sac_code: '', unit: 'Nos', quantity: 1, price: 0, gst_rate: 18, discount_percent: 0 };
}

function addItem() { form.items.push(newItem()); }
function removeItem(i) { if (form.items.length > 1) form.items.splice(i, 1); }

function onProductSelect(i) {
    const product = props.products.find(p => p.id == form.items[i].product_id);
    if (product) {
        form.items[i].description  = product.name;
        form.items[i].price        = product.price;
        form.items[i].gst_rate     = product.gst_rate;
        form.items[i].unit         = product.unit;
        form.items[i].hsn_sac_code = product.hsn_sac_code || '';
    }
}

function calcItem(item) {
    const gross    = item.price * item.quantity;
    const discount = gross * (item.discount_percent || 0) / 100;
    const taxable  = gross - discount;
    const tax      = taxable * item.gst_rate / 100;
    return { taxable: taxable.toFixed(2), tax: tax.toFixed(2), total: (taxable + tax).toFixed(2) };
}

const totals = computed(() => {
    let subtotal = 0, tax = 0;
    form.items.forEach(item => {
        const c = calcItem(item);
        subtotal += parseFloat(c.taxable);
        tax      += parseFloat(c.tax);
    });
    return { subtotal: subtotal.toFixed(2), tax: tax.toFixed(2), total: (subtotal + tax).toFixed(2) };
});

function fmt(n) {
    return '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function submit() {
    form.put(route('invoices.update', props.invoice.id));
}
</script>

<template>
    <Head :title="`Edit ${invoice.invoice_number}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('invoices.show', invoice.id)" class="text-gray-500 hover:text-gray-700">← Back</Link>
                <h2 class="text-xl font-semibold text-gray-800">Edit {{ invoice.invoice_number }}</h2>
                <span v-if="invoice.payment_status === 'paid'" class="text-xs text-red-500 font-medium">(Paid invoices cannot be edited)</span>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Invoice Details -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Invoice Details</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Customer *</label>
                                <select v-model="form.customer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                                    <option value="">Select customer</option>
                                    <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }} {{ c.gstin ? `(${c.gstin})` : '' }}</option>
                                </select>
                                <p v-if="form.errors.customer_id" class="text-red-500 text-xs mt-1">{{ form.errors.customer_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Date *</label>
                                <input v-model="form.invoice_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                <input v-model="form.due_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Type</label>
                                <select v-model="form.invoice_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="b2b">B2B</option>
                                    <option value="b2c">B2C</option>
                                    <option value="export">Export</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Supply Type</label>
                                <select v-model="form.supply_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="intrastate">Intrastate (CGST + SGST)</option>
                                    <option value="interstate">Interstate (IGST)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-base font-semibold text-gray-800">Line Items</h3>
                            <button type="button" @click="addItem" class="text-sm text-indigo-600 hover:underline">+ Add Item</button>
                        </div>

                        <div v-for="(item, i) in form.items" :key="i" class="border border-gray-200 rounded-lg p-4 mb-3">
                            <div class="grid grid-cols-6 gap-3 mb-2">
                                <div class="col-span-2">
                                    <label class="text-xs text-gray-500 mb-1 block">Product (optional)</label>
                                    <select v-model="item.product_id" @change="onProductSelect(i)" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                                        <option value="">-- Select --</option>
                                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                                    </select>
                                </div>
                                <div class="col-span-4">
                                    <label class="text-xs text-gray-500 mb-1 block">Description *</label>
                                    <input v-model="item.description" type="text" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" required />
                                </div>
                            </div>
                            <div class="grid grid-cols-7 gap-3">
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">HSN/SAC</label>
                                    <input v-model="item.hsn_sac_code" type="text" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Unit</label>
                                    <input v-model="item.unit" type="text" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Qty *</label>
                                    <input v-model.number="item.quantity" type="number" step="0.001" min="0.001" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" required />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Rate (₹) *</label>
                                    <input v-model.number="item.price" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" required />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">GST %</label>
                                    <select v-model.number="item.gst_rate" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                                        <option v-for="r in [0,0.25,1,3,5,12,18,28]" :key="r" :value="r">{{ r }}%</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Discount %</label>
                                    <input v-model.number="item.discount_percent" type="number" step="0.01" min="0" max="100" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Total</label>
                                    <div class="text-sm font-medium text-gray-800 pt-2">{{ fmt(calcItem(item).total) }}</div>
                                </div>
                            </div>
                            <div v-if="form.items.length > 1" class="mt-2 text-right">
                                <button type="button" @click="removeItem(i)" class="text-xs text-red-500 hover:underline">Remove</button>
                            </div>
                        </div>

                        <div class="flex justify-end mt-4">
                            <div class="w-72 space-y-1 text-sm">
                                <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>{{ fmt(totals.subtotal) }}</span></div>
                                <div class="flex justify-between text-gray-600"><span>GST ({{ form.supply_type === 'intrastate' ? 'CGST+SGST' : 'IGST' }})</span><span>{{ fmt(totals.tax) }}</span></div>
                                <div class="flex justify-between font-bold text-gray-900 border-t pt-1"><span>Total</span><span>{{ fmt(totals.total) }}</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea v-model="form.notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Terms & Conditions</label>
                                <textarea v-model="form.terms" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <Link :href="route('invoices.show', invoice.id)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</Link>
                        <button type="submit" :disabled="form.processing || invoice.payment_status === 'paid'" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : 'Update Invoice' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
