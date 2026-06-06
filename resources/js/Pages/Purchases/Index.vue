<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    bills: Object,
    itcSummary: Object,
    filters: Object,
});

const search   = ref(props.filters.search   || '');
const status   = ref(props.filters.status   || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo   = ref(props.filters.date_to   || '');

function applyFilters() {
    router.get(route('purchases.index'), {
        search: search.value, status: status.value,
        date_from: dateFrom.value, date_to: dateTo.value,
    }, { preserveState: true });
}

function clearFilters() {
    search.value = ''; status.value = ''; dateFrom.value = ''; dateTo.value = '';
    router.get(route('purchases.index'), {}, { preserveState: false });
}

function statusClass(s) {
    return { paid: 'bg-green-100 text-green-800', unpaid: 'bg-red-100 text-red-800', partial: 'bg-yellow-100 text-yellow-800' }[s] || '';
}

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function fmtCompact(n) {
    const v = Number(n || 0);
    if (v >= 100000) return '₹' + (v / 100000).toFixed(1) + 'L';
    if (v >= 1000)   return '₹' + (v / 1000).toFixed(1) + 'K';
    return '₹' + v.toFixed(2);
}
</script>

<template>
    <Head title="Purchases & ITC" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Purchases / Bills</h2>
                <Link :href="route('purchases.create')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                    + Record Purchase Bill
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-4">

                <!-- ITC Summary Cards -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-gray-500">Total Purchases</div>
                        <div class="text-xl font-bold text-gray-800 mt-1">{{ fmt(itcSummary.total_purchases) }}</div>
                    </div>
                    <div class="bg-green-50 rounded-lg shadow p-4 border border-green-100">
                        <div class="text-xs text-green-600 font-semibold">ITC Available (Total)</div>
                        <div class="text-xl font-bold text-green-700 mt-1">{{ fmt(itcSummary.itc_total) }}</div>
                        <div class="text-xs text-green-500 mt-0.5">CGST+SGST+IGST from eligible bills</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-gray-500">ITC Breakdown</div>
                        <div class="text-xs mt-2 space-y-1">
                            <div class="flex justify-between"><span class="text-gray-400">CGST</span><span class="font-medium">{{ fmtCompact(itcSummary.itc_cgst) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-400">SGST</span><span class="font-medium">{{ fmtCompact(itcSummary.itc_sgst) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-400">IGST</span><span class="font-medium">{{ fmtCompact(itcSummary.itc_igst) }}</span></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-red-500">Outstanding Payable</div>
                        <div class="text-xl font-bold text-red-600 mt-1">{{ fmt(itcSummary.outstanding) }}</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-4 space-y-3">
                    <div class="grid grid-cols-5 gap-3">
                        <input v-model="search" @keydown.enter="applyFilters" type="text"
                               placeholder="Search bill # or supplier..."
                               class="col-span-2 border border-gray-300 rounded-lg px-3 py-2 text-sm" />

                        <select v-model="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">All Status</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>

                        <div class="flex gap-2 col-span-2">
                            <button @click="applyFilters" class="flex-1 px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Filter</button>
                            <button @click="clearFilters" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 text-gray-600">Clear</button>
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
                                <th class="px-4 py-3 font-medium text-gray-600">Bill #</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Supplier</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Date</th>
                                <th class="px-4 py-3 font-medium text-gray-600 text-right">Amount</th>
                                <th class="px-4 py-3 font-medium text-green-600 text-right">ITC</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="b in bills.data" :key="b.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-medium text-indigo-600">
                                    <Link :href="route('purchases.show', b.id)">{{ b.bill_number }}</Link>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ b.supplier?.name }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ b.bill_date }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ fmt(b.total_amount) }}</td>
                                <td class="px-4 py-3 text-right text-xs font-medium" :class="b.itc_eligible ? 'text-green-600' : 'text-gray-400'">
                                    {{ b.itc_eligible ? fmt((b.cgst_amount || 0) + (b.sgst_amount || 0) + (b.igst_amount || 0)) : 'Not eligible' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(b.payment_status)">
                                        {{ b.payment_status.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <Link :href="route('purchases.show', b.id)" class="text-indigo-600 hover:underline text-xs">View</Link>
                                </td>
                            </tr>
                            <tr v-if="bills.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-gray-400">No purchase bills found.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-500">
                        <span>Showing {{ bills.from }}–{{ bills.to }} of {{ bills.total }}</span>
                        <div class="flex gap-2">
                            <Link v-if="bills.prev_page_url" :href="bills.prev_page_url" class="text-indigo-600 hover:underline">← Prev</Link>
                            <Link v-if="bills.next_page_url" :href="bills.next_page_url" class="text-indigo-600 hover:underline">Next →</Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
