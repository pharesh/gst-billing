<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    buckets: Object,
    summary: Object,
    total_outstanding: Number,
});

const activeBucket = ref(null);

const BUCKET_META = {
    current:  { label: 'Not Yet Due',    color: 'bg-green-100 text-green-800',  border: 'border-green-200',  dot: 'bg-green-500' },
    '1_30':   { label: '1–30 Days',      color: 'bg-yellow-100 text-yellow-800', border: 'border-yellow-200', dot: 'bg-yellow-500' },
    '31_60':  { label: '31–60 Days',     color: 'bg-orange-100 text-orange-800', border: 'border-orange-200', dot: 'bg-orange-500' },
    '61_90':  { label: '61–90 Days',     color: 'bg-red-100 text-red-700',       border: 'border-red-200',    dot: 'bg-red-500' },
    over_90:  { label: '90+ Days',       color: 'bg-red-200 text-red-900',       border: 'border-red-300',    dot: 'bg-red-800' },
};

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function selectedRows() {
    if (!activeBucket.value) return [];
    return props.buckets[activeBucket.value] || [];
}
</script>

<template>
    <Head title="Aging Report" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('reports.index')" class="text-gray-500 hover:text-gray-700">← Reports</Link>
                    <h2 class="text-xl font-semibold text-gray-800">Aging Report</h2>
                </div>
                <div class="text-sm text-gray-500">
                    Total Outstanding: <span class="font-bold text-red-600 text-base">{{ fmt(total_outstanding) }}</span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Bucket Summary Cards -->
                <div class="grid grid-cols-5 gap-4">
                    <div
                        v-for="(key, _) in BUCKET_META"
                        :key="key"
                        @click="activeBucket = activeBucket === key ? null : key"
                        class="bg-white rounded-lg shadow p-4 cursor-pointer transition-all"
                        :class="[BUCKET_META[key].border, activeBucket === key ? 'ring-2 ring-indigo-400' : 'border']"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :class="BUCKET_META[key].dot"></span>
                            <span class="text-xs font-semibold text-gray-600">{{ BUCKET_META[key].label }}</span>
                        </div>
                        <div class="text-xl font-bold text-gray-900">{{ fmt(summary[key]?.total) }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ summary[key]?.count || 0 }} invoice{{ summary[key]?.count !== 1 ? 's' : '' }}</div>
                    </div>
                </div>

                <!-- Visual Bar -->
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 mb-2 font-medium">Distribution of Outstanding Amount</div>
                    <div class="flex h-5 rounded-full overflow-hidden gap-0.5" v-if="total_outstanding > 0">
                        <template v-for="(key, _) in BUCKET_META" :key="key">
                            <div
                                v-if="summary[key]?.total > 0"
                                :style="`width: ${(summary[key].total / total_outstanding * 100).toFixed(1)}%`"
                                :class="BUCKET_META[key].dot"
                                class="transition-all"
                                :title="`${BUCKET_META[key].label}: ${fmt(summary[key].total)}`"
                            ></div>
                        </template>
                    </div>
                    <div v-else class="h-5 bg-gray-100 rounded-full flex items-center justify-center text-xs text-gray-400">No outstanding invoices</div>
                    <div class="flex gap-4 mt-2">
                        <div v-for="(key, _) in BUCKET_META" :key="key" class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full" :class="BUCKET_META[key].dot"></span>
                            <span class="text-xs text-gray-500">{{ BUCKET_META[key].label }}</span>
                        </div>
                    </div>
                </div>

                <!-- Detail Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-4 py-3 border-b flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-gray-700">
                                <template v-if="activeBucket">
                                    <span class="px-2 py-0.5 rounded-full text-xs" :class="BUCKET_META[activeBucket].color">{{ BUCKET_META[activeBucket].label }}</span>
                                    — {{ summary[activeBucket]?.count }} invoices
                                </template>
                                <template v-else>All Overdue Invoices (click a card to filter)</template>
                            </h3>
                        </div>
                        <button v-if="activeBucket" @click="activeBucket = null" class="text-xs text-gray-400 hover:text-gray-600">Clear filter</button>
                    </div>

                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Invoice #</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Customer</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Due Date</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Days Overdue</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Balance Due</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Bucket</th>
                                <th class="px-4 py-3 font-medium text-gray-600"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template v-if="activeBucket">
                                <tr v-for="row in selectedRows()" :key="row.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono font-medium text-indigo-600">{{ row.invoice_number }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ row.customer_name }}</td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ row.due_date }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span v-if="row.days_overdue === 0" class="text-green-600 font-medium">Not yet due</span>
                                        <span v-else class="font-bold text-red-600">{{ row.days_overdue }}d</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-red-600">{{ fmt(row.balance_due) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="BUCKET_META[activeBucket].color">
                                            {{ BUCKET_META[activeBucket].label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link :href="route('invoices.show', row.id)" class="text-indigo-600 hover:underline text-xs">View</Link>
                                    </td>
                                </tr>
                                <tr v-if="selectedRows().length === 0">
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">No invoices in this bucket.</td>
                                </tr>
                            </template>
                            <template v-else>
                                <template v-for="(bkey, _) in BUCKET_META" :key="bkey">
                                    <tr v-for="row in buckets[bkey]" :key="row.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-mono font-medium text-indigo-600">{{ row.invoice_number }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ row.customer_name }}</td>
                                        <td class="px-4 py-3 text-gray-500 text-xs">{{ row.due_date }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span v-if="row.days_overdue === 0" class="text-green-600 font-medium">Not yet due</span>
                                            <span v-else class="font-bold text-red-600">{{ row.days_overdue }}d</span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-red-600">{{ fmt(row.balance_due) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="BUCKET_META[bkey].color">
                                                {{ BUCKET_META[bkey].label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <Link :href="route('invoices.show', row.id)" class="text-indigo-600 hover:underline text-xs">View</Link>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="total_outstanding === 0">
                                    <td colspan="7" class="px-4 py-10 text-center text-gray-400">No outstanding invoices. All paid up!</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
