<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    suppliers: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showForm = ref(false);
const editingSupplier = ref(null);

const form = useForm({
    name: '', gstin: '', supplier_type: 'registered',
    address: '', city: '', state: '', state_code: '',
    pincode: '', phone: '', email: '',
    payment_terms: '', is_active: true,
});

function openCreate() {
    editingSupplier.value = null;
    form.reset();
    showForm.value = true;
}

function openEdit(supplier) {
    editingSupplier.value = supplier;
    form.name          = supplier.name;
    form.gstin         = supplier.gstin || '';
    form.supplier_type = supplier.supplier_type;
    form.address       = supplier.address || '';
    form.city          = supplier.city || '';
    form.state         = supplier.state || '';
    form.state_code    = supplier.state_code || '';
    form.pincode       = supplier.pincode || '';
    form.phone         = supplier.phone || '';
    form.email         = supplier.email || '';
    form.payment_terms = supplier.payment_terms || '';
    form.is_active     = supplier.is_active;
    showForm.value = true;
}

function submit() {
    if (editingSupplier.value) {
        form.patch(route('suppliers.update', editingSupplier.value.id), {
            onSuccess: () => { showForm.value = false; },
        });
    } else {
        form.post(route('suppliers.store'), {
            onSuccess: () => { showForm.value = false; form.reset(); },
        });
    }
}

function deleteSupplier(id) {
    if (confirm('Delete this supplier?')) {
        router.delete(route('suppliers.destroy', id));
    }
}

function applySearch() {
    router.get(route('suppliers.index'), { search: search.value }, { preserveState: true });
}
</script>

<template>
    <Head title="Suppliers" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Suppliers</h2>
                <button @click="openCreate" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Supplier</button>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Supplier Form -->
                <div v-if="showForm" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-base font-semibold mb-4">{{ editingSupplier ? 'Edit' : 'Add' }} Supplier</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Name *</label>
                            <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">GSTIN</label>
                            <input v-model="form.gstin" type="text" maxlength="15" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono" placeholder="22AAAAA0000A1Z5" />
                            <p v-if="form.errors.gstin" class="text-red-500 text-xs mt-1">{{ form.errors.gstin }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Type</label>
                            <select v-model="form.supplier_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="registered">Registered</option>
                                <option value="unregistered">Unregistered</option>
                            </select>
                        </div>
                        <div class="col-span-3">
                            <label class="text-sm font-medium text-gray-700 block mb-1">Address</label>
                            <input v-model="form.address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">City</label>
                            <input v-model="form.city" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">State</label>
                            <input v-model="form.state" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">State Code</label>
                            <input v-model="form.state_code" type="text" maxlength="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="27" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Pincode</label>
                            <input v-model="form.pincode" type="text" maxlength="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Phone</label>
                            <input v-model="form.phone" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Email</label>
                            <input v-model="form.email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Payment Terms</label>
                            <input v-model="form.payment_terms" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g. Net 30" />
                        </div>
                        <div class="flex items-center gap-2 mt-5">
                            <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded" />
                            <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
                        </div>
                        <div class="col-span-3 flex gap-2">
                            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50">
                                {{ form.processing ? 'Saving...' : (editingSupplier ? 'Update' : 'Add Supplier') }}
                            </button>
                            <button type="button" @click="showForm = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Search -->
                <div class="flex gap-3">
                    <input v-model="search" @keydown.enter="applySearch" type="text" placeholder="Search by name or GSTIN..." class="flex-1 bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm shadow-sm" />
                    <button @click="applySearch" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 shadow-sm">Search</button>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">GSTIN</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Type</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">State</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Phone</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Payment Terms</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="s in suppliers.data" :key="s.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">{{ s.name }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ s.gstin || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium" :class="s.supplier_type === 'registered' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'">
                                        {{ s.supplier_type === 'registered' ? 'Registered' : 'Unregistered' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ s.state || '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ s.phone || '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ s.payment_terms || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium" :class="s.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'">
                                        {{ s.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button @click="openEdit(s)" class="text-indigo-600 hover:underline text-xs">Edit</button>
                                        <button @click="deleteSupplier(s.id)" class="text-red-500 hover:underline text-xs">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="suppliers.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">No suppliers yet. Add your first supplier.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
