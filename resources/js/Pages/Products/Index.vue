<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showForm = ref(false);
const editingProduct = ref(null);

const GST_RATES = [0, 0.25, 1, 3, 5, 12, 18, 28];
const UNITS = ['Nos', 'Kgs', 'Ltr', 'Mtr', 'Box', 'Hrs', 'Days', 'Pcs', 'Set'];

const form = useForm({
    name: '', description: '', hsn_sac_code: '',
    type: 'goods', unit: 'Nos',
    price: 0, gst_rate: 18, is_active: true,
});

function openCreate() {
    editingProduct.value = null;
    form.reset();
    form.unit = 'Nos';
    form.gst_rate = 18;
    form.is_active = true;
    showForm.value = true;
}

function openEdit(product) {
    editingProduct.value = product;
    Object.keys(form.data()).forEach(k => { if (product[k] !== undefined) form[k] = product[k]; });
    showForm.value = true;
}

function submit() {
    if (editingProduct.value) {
        form.patch(route('products.update', editingProduct.value.id), { onSuccess: () => { showForm.value = false; } });
    } else {
        form.post(route('products.store'), { onSuccess: () => { showForm.value = false; form.reset(); } });
    }
}

function deleteProduct(id) {
    if (confirm('Delete this product?')) router.delete(route('products.destroy', id));
}

function applySearch() {
    router.get(route('products.index'), { search: search.value }, { preserveState: true });
}

function fmt(n) {
    return '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head title="Products & Services" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Products & Services</h2>
                <button @click="openCreate" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Product</button>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Form -->
                <div v-if="showForm" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-base font-semibold mb-4">{{ editingProduct ? 'Edit' : 'Add' }} Product / Service</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="text-sm font-medium text-gray-700 block mb-1">Name *</label>
                            <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Type</label>
                            <select v-model="form.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="goods">Goods</option>
                                <option value="service">Service</option>
                            </select>
                        </div>
                        <div class="col-span-3">
                            <label class="text-sm font-medium text-gray-700 block mb-1">Description</label>
                            <input v-model="form.description" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">HSN/SAC Code</label>
                            <input v-model="form.hsn_sac_code" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Unit</label>
                            <select v-model="form.unit" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="u in UNITS" :key="u" :value="u">{{ u }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Price (₹)</label>
                            <input v-model.number="form.price" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">GST Rate</label>
                            <select v-model.number="form.gst_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="r in GST_RATES" :key="r" :value="r">{{ r }}%</option>
                            </select>
                        </div>
                        <div class="col-span-3 flex gap-2 items-center">
                            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50">
                                {{ form.processing ? 'Saving...' : (editingProduct ? 'Update' : 'Add Product') }}
                            </button>
                            <button type="button" @click="showForm = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Search -->
                <div class="flex gap-3">
                    <input v-model="search" @keydown.enter="applySearch" type="text" placeholder="Search products..." class="flex-1 bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm shadow-sm" />
                    <button @click="applySearch" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 shadow-sm">Search</button>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">HSN/SAC</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Type</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Unit</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Price</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">GST%</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="p in products.data" :key="p.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">{{ p.name }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ p.hsn_sac_code || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-xs" :class="p.type === 'goods' ? 'bg-orange-100 text-orange-700' : 'bg-purple-100 text-purple-700'">
                                        {{ p.type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ p.unit }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ fmt(p.price) }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ p.gst_rate }}%</td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="p.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-0.5 rounded text-xs">
                                        {{ p.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button @click="openEdit(p)" class="text-indigo-600 hover:underline text-xs">Edit</button>
                                        <button @click="deleteProduct(p.id)" class="text-red-500 hover:underline text-xs">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">No products yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
