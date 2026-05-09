<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    invoices: Object,
    customers: Array,
    filters: Object,
    summary: Object,
});

const search      = ref(props.filters.search      || '');
const status      = ref(props.filters.status      || '');
const customerId  = ref(props.filters.customer_id || '');
const dateFrom    = ref(props.filters.date_from   || '');
const dateTo      = ref(props.filters.date_to     || '');

function applyFilters() {
    router.get(route('invoices.index'), {
        search:      search.value,
        status:      status.value,
        customer_id: customerId.value,
        date_from:   dateFrom.value,
        date_to:     dateTo.value,
    }, { preserveState: true });
}

function clearFilters() {
    search.value = ''; status.value = ''; customerId.value = '';
    dateFrom.value = ''; dateTo.value = '';
    router.get(route('invoices.index'), {}, { preserveState: false });
}

function statusClass(s) {
    return {
        paid:    'bg-green-100 text-green-800',
        unpaid:  'bg-red-100 text-red-800',
        partial: 'bg-yellow-100 text-yellow-800',
    }[s] || '';
}

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

const hasFilters = () => search.value || status.value || customerId.value || dateFrom.value || dateTo.value;
</script>

<template>
    <Head title="Invoices" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Invoices</h2>
                <Link :href="route('invoices.create')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                    + New Invoice
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-4">

                <!-- Summary Cards -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-gray-500">Total Billed</div>
                        <div class="text-xl font-bold text-gray-800 mt-1">{{ fmt(summary.total) }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-green-600">Collected</div>
                        <div class="text-xl font-bold text-green-700 mt-1">{{ fmt(summary.paid) }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-red-500">Outstanding</div>
                        <div class="text-xl font-bold text-red-600 mt-1">{{ fmt(summary.unpaid) }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-orange-500">Overdue Invoices</div>
                        <div class="text-xl font-bold text-orange-600 mt-1">{{ summary.overdue }}</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-4 space-y-3">
                    <div class="grid grid-cols-5 gap-3">
                        <input v-model="search" @keydown.enter="applyFilters" type="text"
                               placeholder="Search invoice # or customer..."
                               class="col-span-2 border border-gray-300 rounded-lg px-3 py-2 text-sm" />

                        <select v-model="customerId" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">All Customers</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>

                        <select v-model="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">All Status</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>

                        <div class="flex gap-2">
                            <button @click="applyFilters" class="flex-1 px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Filter</button>
                            <button v-if="hasFilters()" @click="clearFilters" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 text-gray-600">✕</button>
                        </div>
                    </div>
                    <div class="flex gap-3 items-center">
                        <span class="text-xs text-gray-500">Date range:</span>
                        <input v-model="dateFrom" type="date" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                        <span class="text-xs text-gray-400">to</span>
                        <input v-model="dateTo" type="date" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                        <button @click="applyFilters" class="px-3 py-1.5 text-xs bg-gray-100 rounded-lg hover:bg-gray-200">Apply</button>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-600">Invoice #</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Customer</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Date</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Due Date</th>
                                <th class="px-4 py-3 font-medium text-gray-600 text-right">Amount</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50"
                                :class="inv.payment_status !== 'paid' && inv.due_date && new Date(inv.due_date) < new Date() ? 'bg-red-50' : ''">
                                <td class="px-4 py-3 font-mono font-medium text-indigo-600">
                                    <Link :href="route('invoices.show', inv.id)">{{ inv.invoice_number }}</Link>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ inv.customer?.name }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ inv.invoice_date }}</td>
                                <td class="px-4 py-3 text-xs"
                                    :class="inv.payment_status !== 'paid' && inv.due_date && new Date(inv.due_date) < new Date() ? 'text-red-600 font-medium' : 'text-gray-500'">
                                    {{ inv.due_date || '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium">{{ fmt(inv.total_amount) }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(inv.payment_status)">
                                        {{ inv.payment_status.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <Link :href="route('invoices.show', inv.id)" class="text-indigo-600 hover:underline text-xs">View</Link>
                                        <a :href="route('invoices.download', inv.id)" class="text-gray-600 hover:underline text-xs">PDF</a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="invoices.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-gray-400">No invoices found.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-500">
                        <span>Showing {{ invoices.from }}–{{ invoices.to }} of {{ invoices.total }}</span>
                        <div class="flex gap-2">
                            <Link v-if="invoices.prev_page_url" :href="invoices.prev_page_url" class="text-indigo-600 hover:underline">← Prev</Link>
                            <Link v-if="invoices.next_page_url" :href="invoices.next_page_url" class="text-indigo-600 hover:underline">Next →</Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
