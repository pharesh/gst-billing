<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    suppliers: Array,
    tenant: Object,
});

const form = useForm({
    supplier_id: '',
    bill_number: '',
    bill_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    supply_type: 'intrastate',
    itc_eligible: true,
    notes: '',
    items: [newItem()],
});

function newItem() {
    return { description: '', hsn_sac_code: '', unit: 'Nos', quantity: 1, price: 0, gst_rate: 18 };
}

function addItem() { form.items.push(newItem()); }
function removeItem(i) { if (form.items.length > 1) form.items.splice(i, 1); }

function calcItem(item) {
    const taxable = item.price * item.quantity;
    const tax = taxable * item.gst_rate / 100;
    return { taxable: taxable.toFixed(2), tax: tax.toFixed(2), total: (taxable + tax).toFixed(2) };
}

const totals = computed(() => {
    let subtotal = 0, tax = 0;
    form.items.forEach(item => {
        const c = calcItem(item);
        subtotal += parseFloat(c.taxable);
        tax += parseFloat(c.tax);
    });
    return { subtotal: subtotal.toFixed(2), tax: tax.toFixed(2), total: (subtotal + tax).toFixed(2) };
});

function fmt(n) {
    return '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function submit() { form.post(route('purchases.store')); }
</script>

<template>
    <Head title="Record Purchase Bill" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Record Purchase Bill</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Bill Details -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Bill Details</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Supplier *</label>
                                <select v-model="form.supplier_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                                    <option value="">Select supplier</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}{{ s.gstin ? ` (${s.gstin})` : '' }}</option>
                                </select>
                                <p v-if="form.errors.supplier_id" class="text-red-500 text-xs mt-1">{{ form.errors.supplier_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Supplier Bill Number</label>
                                <input v-model="form.bill_number" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Auto-generated if blank" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bill Date *</label>
                                <input v-model="form.bill_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                <input v-model="form.due_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Supply Type</label>
                                <select v-model="form.supply_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="intrastate">Intrastate (CGST + SGST)</option>
                                    <option value="interstate">Interstate (IGST)</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <input v-model="form.itc_eligible" type="checkbox" id="itc_eligible" class="rounded border-gray-300" />
                                <label for="itc_eligible" class="text-sm font-medium text-gray-700">ITC Eligible</label>
                                <span class="text-xs text-gray-400">(Claim input tax credit)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-base font-semibold text-gray-800">Items / Services</h3>
                            <button type="button" @click="addItem" class="text-sm text-indigo-600 hover:underline">+ Add Item</button>
                        </div>

                        <div v-for="(item, i) in form.items" :key="i" class="border border-gray-200 rounded-lg p-4 mb-3">
                            <div class="grid grid-cols-7 gap-3">
                                <div class="col-span-2">
                                    <label class="text-xs text-gray-500 mb-1 block">Description *</label>
                                    <input v-model="item.description" type="text" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" required />
                                </div>
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
                            </div>
                            <div class="mt-2 flex justify-between items-center">
                                <div class="text-xs text-gray-500">Total: <span class="font-medium text-gray-800">{{ fmt(calcItem(item).total) }}</span></div>
                                <button v-if="form.items.length > 1" type="button" @click="removeItem(i)" class="text-xs text-red-500 hover:underline">Remove</button>
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="flex justify-end mt-4">
                            <div class="w-72 space-y-1 text-sm">
                                <div class="flex justify-between text-gray-600"><span>Subtotal (Taxable)</span><span>{{ fmt(totals.subtotal) }}</span></div>
                                <div class="flex justify-between text-green-600"><span>Input Tax (ITC)</span><span>{{ fmt(totals.tax) }}</span></div>
                                <div class="flex justify-between font-bold text-gray-900 border-t pt-1"><span>Total</span><span>{{ fmt(totals.total) }}</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Internal notes..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a :href="route('purchases.index')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : 'Record Bill' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
