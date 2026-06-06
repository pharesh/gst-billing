<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    notes: Object,
    suppliers: Array,
    filters: Object,
    summary: Object,
});

const search     = ref(props.filters.search      || '');
const supplierId = ref(props.filters.supplier_id || '');

function applyFilters() {
    router.get(route('debit-notes.index'), { search: search.value, supplier_id: supplierId.value }, { preserveState: true });
}

function clearFilters() {
    search.value = ''; supplierId.value = '';
    router.get(route('debit-notes.index'), {}, { preserveState: false });
}

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head title="Debit Notes" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Debit Notes</h2>
                <Link :href="route('debit-notes.create')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                    + New Debit Note
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-4">

                <!-- Summary Cards -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-gray-500">Total Debit Notes</div>
                        <div class="text-xl font-bold text-gray-800 mt-1">{{ summary.count }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-red-500">Total Returned Value</div>
                        <div class="text-xl font-bold text-red-600 mt-1">{{ fmt(summary.total) }}</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex gap-3">
                        <input v-model="search" @keydown.enter="applyFilters" type="text"
                               placeholder="Search debit note #..."
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <select v-model="supplierId" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-48">
                            <option value="">All Suppliers</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <button @click="applyFilters" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Filter</button>
                        <button @click="clearFilters" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 text-gray-600">Clear</button>
                    </div>
                </div>

                <!-- Flash -->
                <div v-if="$page.props.flash?.success" class="px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-600">Debit Note #</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Supplier</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Against Bill</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Date</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Reason</th>
                                <th class="px-4 py-3 font-medium text-gray-600 text-right">Amount</th>
                                <th class="px-4 py-3 font-medium text-gray-600"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="n in notes.data" :key="n.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-medium text-indigo-600">
                                    <Link :href="route('debit-notes.show', n.id)">{{ n.debit_note_number }}</Link>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ n.supplier?.name }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ n.purchase_invoice?.bill_number || '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ n.debit_note_date }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ n.reason || '—' }}</td>
                                <td class="px-4 py-3 text-right font-medium text-red-600">{{ fmt(n.total_amount) }}</td>
                                <td class="px-4 py-3">
                                    <Link :href="route('debit-notes.show', n.id)" class="text-indigo-600 hover:underline text-xs">View</Link>
                                </td>
                            </tr>
                            <tr v-if="notes.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-gray-400">No debit notes found.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-500">
                        <span>Showing {{ notes.from }}–{{ notes.to }} of {{ notes.total }}</span>
                        <div class="flex gap-2">
                            <Link v-if="notes.prev_page_url" :href="notes.prev_page_url" class="text-indigo-600 hover:underline">← Prev</Link>
                            <Link v-if="notes.next_page_url" :href="notes.next_page_url" class="text-indigo-600 hover:underline">Next →</Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
