<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    creditNotes: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

function applySearch() {
    router.get(route('credit-notes.index'), { search: search.value }, { preserveState: true });
}

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function del(id) {
    if (confirm('Delete this credit note?')) {
        router.delete(route('credit-notes.destroy', id));
    }
}
</script>

<template>
    <Head title="Credit Notes" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Credit Notes</h2>
                <Link :href="route('credit-notes.create')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    + New Credit Note
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-4">

                <div class="flex gap-3">
                    <input v-model="search" @keydown.enter="applySearch" type="text"
                           placeholder="Search credit note # or customer..."
                           class="flex-1 bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm shadow-sm" />
                    <button @click="applySearch" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 shadow-sm">Search</button>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-600">Credit Note #</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Customer</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Against Invoice</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Date</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Reason</th>
                                <th class="px-4 py-3 font-medium text-gray-600 text-right">Amount</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="cn in creditNotes.data" :key="cn.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-medium text-indigo-600">
                                    <Link :href="route('credit-notes.show', cn.id)">{{ cn.credit_note_number }}</Link>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ cn.customer?.name }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    <Link v-if="cn.invoice" :href="route('invoices.show', cn.invoice.id)" class="text-indigo-600 hover:underline">
                                        {{ cn.invoice.invoice_number }}
                                    </Link>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ cn.credit_note_date }}</td>
                                <td class="px-4 py-3 text-gray-600 max-w-[200px] truncate">{{ cn.reason }}</td>
                                <td class="px-4 py-3 text-right font-medium text-red-600">{{ fmt(cn.total_amount) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <Link :href="route('credit-notes.show', cn.id)" class="text-indigo-600 hover:underline text-xs">View</Link>
                                        <button @click="del(cn.id)" class="text-red-500 hover:underline text-xs">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="creditNotes.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-gray-400">No credit notes found.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-500">
                        <span>Showing {{ creditNotes.from ?? 0 }}–{{ creditNotes.to ?? 0 }} of {{ creditNotes.total }}</span>
                        <div class="flex gap-2">
                            <Link v-if="creditNotes.prev_page_url" :href="creditNotes.prev_page_url" class="text-indigo-600 hover:underline">← Prev</Link>
                            <Link v-if="creditNotes.next_page_url" :href="creditNotes.next_page_url" class="text-indigo-600 hover:underline">Next →</Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
