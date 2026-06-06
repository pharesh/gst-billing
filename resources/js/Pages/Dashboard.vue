<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
    recentInvoices: Array,
    overdueInvoices: Array,
    topCustomers: Array,
    monthlyChart: Array,
    taxThisMonth: Object,
});

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function fmtDate(d) {
    if (!d) return '—';
    const [y, m, day] = d.substring(0, 10).split('-');
    return `${day}-${m}-${y}`;
}

function fmtCompact(n) {
    const v = Number(n || 0);
    if (v >= 10000000) return '₹' + (v / 10000000).toFixed(1) + 'Cr';
    if (v >= 100000)   return '₹' + (v / 100000).toFixed(1) + 'L';
    if (v >= 1000)     return '₹' + (v / 1000).toFixed(1) + 'K';
    return '₹' + v.toFixed(0);
}

function statusClass(s) {
    return { paid: 'text-green-700 bg-green-50', unpaid: 'text-red-700 bg-red-50', partial: 'text-yellow-700 bg-yellow-50' }[s] || '';
}

function daysPastDue(dueDate) {
    return Math.floor((new Date() - new Date(dueDate)) / 86400000);
}

// SVG chart helpers
const chartH = 120;
const chartW = 600;
const barCount = 12;
const barPad = 4;

const maxVal = computed(() => {
    const m = Math.max(...(props.monthlyChart || []).map(d => d.total), 1);
    return m;
});

function barHeight(val) {
    return Math.max(2, (val / maxVal.value) * chartH);
}

function barX(i) {
    const slotW = chartW / barCount;
    return i * slotW + barPad;
}

function barW() {
    return chartW / barCount - barPad * 2;
}

const totalTax = computed(() => {
    const t = props.taxThisMonth || {};
    return (t.cgst || 0) + (t.sgst || 0) + (t.igst || 0);
});

function taxPct(val) {
    return totalTax.value > 0 ? Math.round((val / totalTax.value) * 100) : 0;
}
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Overdue Alert Banner -->
                <div v-if="overdueInvoices?.length" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-red-700 text-sm font-medium">
                        <span class="text-base">⚠️</span>
                        {{ stats.overdue_count }} invoice{{ stats.overdue_count !== 1 ? 's are' : ' is' }} overdue — action needed
                    </div>
                    <Link :href="route('invoices.index', { status: 'unpaid' })" class="text-xs text-red-600 underline hover:text-red-800">View All</Link>
                </div>

                <!-- Stat Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- This Month Billed -->
                    <div class="bg-white rounded-lg shadow p-5">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Billed This Month</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ fmt(stats?.monthly_total) }}</div>
                        <div class="mt-1 text-xs" v-if="stats?.mom_change !== null">
                            <span :class="stats.mom_change >= 0 ? 'text-green-600' : 'text-red-500'">
                                {{ stats.mom_change >= 0 ? '↑' : '↓' }} {{ Math.abs(stats.mom_change) }}%
                            </span>
                            <span class="text-gray-400 ml-1">vs last month</span>
                        </div>
                        <div class="mt-1 text-xs text-gray-400" v-else>First month</div>
                    </div>

                    <!-- Collected -->
                    <div class="bg-white rounded-lg shadow p-5">
                        <div class="text-xs text-green-600 uppercase tracking-wide">Collected</div>
                        <div class="text-2xl font-bold text-green-700 mt-1">{{ fmt(stats?.monthly_paid) }}</div>
                        <div class="mt-1 text-xs text-gray-400">This month</div>
                    </div>

                    <!-- Outstanding -->
                    <div class="bg-white rounded-lg shadow p-5">
                        <div class="text-xs text-orange-500 uppercase tracking-wide">Outstanding</div>
                        <div class="text-2xl font-bold text-orange-600 mt-1">{{ fmt(stats?.total_outstanding) }}</div>
                        <div class="mt-1 text-xs text-gray-400">All unpaid balance</div>
                    </div>

                    <!-- Overdue -->
                    <div class="bg-white rounded-lg shadow p-5" :class="stats?.overdue_count > 0 ? 'border-l-4 border-red-500' : ''">
                        <div class="text-xs text-red-500 uppercase tracking-wide">Overdue</div>
                        <div class="text-2xl font-bold mt-1" :class="stats?.overdue_count > 0 ? 'text-red-600' : 'text-gray-400'">
                            {{ stats?.overdue_count || 0 }}
                        </div>
                        <div class="mt-1 text-xs text-gray-400">Past due date</div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="grid grid-cols-4 gap-4">
                    <Link :href="route('invoices.create')" class="bg-indigo-600 text-white rounded-lg p-4 hover:bg-indigo-700 text-center transition-colors">
                        <div class="text-xl mb-1 font-bold">+</div>
                        <div class="text-sm font-medium">New Invoice</div>
                    </Link>
                    <Link :href="route('customers.index')" class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 text-center shadow-sm transition-colors">
                        <div class="text-xl mb-1">👥</div>
                        <div class="text-sm font-medium text-gray-700">Customers</div>
                    </Link>
                    <Link :href="route('products.index')" class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 text-center shadow-sm transition-colors">
                        <div class="text-xl mb-1">📦</div>
                        <div class="text-sm font-medium text-gray-700">Products</div>
                    </Link>
                    <Link :href="route('reports.index')" class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 text-center shadow-sm transition-colors">
                        <div class="text-xl mb-1">📊</div>
                        <div class="text-sm font-medium text-gray-700">GST Reports</div>
                    </Link>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-3 gap-4">

                    <!-- Monthly Revenue Bar Chart -->
                    <div class="col-span-2 bg-white rounded-lg shadow p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-800">Revenue — Last 12 Months</h3>
                            <span class="text-xs text-gray-400">hover for amount</span>
                        </div>
                        <svg :viewBox="`0 0 ${600} ${chartH + 24}`" class="w-full" style="height:160px;">
                            <g v-for="(d, i) in monthlyChart" :key="i">
                                <rect
                                    :x="barX(i)"
                                    :y="chartH - barHeight(d.total)"
                                    :width="barW()"
                                    :height="barHeight(d.total)"
                                    :fill="i === 11 ? '#4f46e5' : '#c7d2fe'"
                                    rx="2"
                                >
                                    <title>{{ d.label }}: ₹{{ Number(d.total).toLocaleString('en-IN') }}</title>
                                </rect>
                                <text
                                    :x="barX(i) + barW() / 2"
                                    :y="chartH + 14"
                                    text-anchor="middle"
                                    font-size="7"
                                    fill="#9ca3af"
                                >{{ d.label.split(' ')[0] }}</text>
                            </g>
                        </svg>
                        <div class="flex justify-between mt-1 text-xs text-gray-400 px-1">
                            <span>{{ monthlyChart?.[0]?.label }}</span>
                            <span class="text-indigo-600 font-medium">{{ monthlyChart?.[11]?.label }} (current)</span>
                        </div>
                    </div>

                    <!-- Tax Liability This Month -->
                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Tax Liability — This Month</h3>
                        <div class="text-2xl font-bold text-gray-900 mb-4">{{ fmtCompact(totalTax) }}</div>

                        <div class="space-y-3">
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-500">CGST</span>
                                    <span class="font-medium">{{ fmtCompact(taxThisMonth?.cgst || 0) }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full transition-all" :style="`width:${taxPct(taxThisMonth?.cgst || 0)}%`"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-500">SGST</span>
                                    <span class="font-medium">{{ fmtCompact(taxThisMonth?.sgst || 0) }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full transition-all" :style="`width:${taxPct(taxThisMonth?.sgst || 0)}%`"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-500">IGST</span>
                                    <span class="font-medium">{{ fmtCompact(taxThisMonth?.igst || 0) }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="bg-orange-400 h-2 rounded-full transition-all" :style="`width:${taxPct(taxThisMonth?.igst || 0)}%`"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-t text-xs text-gray-400">
                            File GSTR-3B by 20th to pay this liability
                        </div>
                    </div>

                </div>

                <!-- Two-column: Recent Invoices + Top Customers -->
                <div class="grid grid-cols-3 gap-6">

                    <!-- Recent Invoices (wider) -->
                    <div class="col-span-2 bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b flex justify-between items-center">
                            <h3 class="font-semibold text-gray-800">Recent Invoices</h3>
                            <Link :href="route('invoices.index')" class="text-sm text-indigo-600 hover:underline">View All →</Link>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="inv in recentInvoices" :key="inv.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-mono text-indigo-600 whitespace-nowrap">
                                        <Link :href="route('invoices.show', inv.id)">{{ inv.invoice_number }}</Link>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 truncate max-w-[120px]">{{ inv.customer?.name }}</td>
                                    <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">{{ fmtDate(inv.invoice_date) }}</td>
                                    <td class="px-4 py-3 text-right font-medium whitespace-nowrap">{{ fmt(inv.total_amount) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="statusClass(inv.payment_status)">
                                            {{ (inv.payment_status ?? 'unpaid').toUpperCase() }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!recentInvoices?.length">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                        No invoices yet.
                                        <Link :href="route('invoices.create')" class="text-indigo-600 hover:underline">Create your first invoice</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Top Customers -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b">
                            <h3 class="font-semibold text-gray-800">Top Customers</h3>
                            <p class="text-xs text-gray-400 mt-0.5">By total billed</p>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div v-for="(c, i) in topCustomers" :key="c.id" class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs flex items-center justify-center font-bold">{{ i + 1 }}</span>
                                    <div>
                                        <div class="text-sm font-medium text-gray-800 truncate max-w-[120px]">{{ c.name }}</div>
                                        <div class="text-xs text-gray-400">{{ c.invoice_count }} invoice{{ c.invoice_count !== 1 ? 's' : '' }}</div>
                                    </div>
                                </div>
                                <div class="text-sm font-semibold text-gray-700">{{ fmtCompact(c.total_billed) }}</div>
                            </div>
                            <div v-if="!topCustomers?.length" class="px-6 py-8 text-center text-gray-400 text-sm">
                                No data yet
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Overdue Invoices Table -->
                <div v-if="overdueInvoices?.length" class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800 text-red-700">Overdue Invoices</h3>
                        <Link :href="route('invoices.index')" class="text-sm text-indigo-600 hover:underline">View All →</Link>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-red-50 text-xs text-red-700 uppercase tracking-wide">
                            <tr>
                                <th class="px-6 py-2 text-left font-medium">Invoice</th>
                                <th class="px-4 py-2 text-left font-medium">Customer</th>
                                <th class="px-4 py-2 text-left font-medium">Due Date</th>
                                <th class="px-4 py-2 text-left font-medium">Days Overdue</th>
                                <th class="px-4 py-2 text-right font-medium">Balance Due</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-red-50">
                            <tr v-for="inv in overdueInvoices" :key="inv.id" class="hover:bg-red-50">
                                <td class="px-6 py-3 font-mono text-indigo-600">
                                    <Link :href="route('invoices.show', inv.id)">{{ inv.invoice_number }}</Link>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ inv.customer?.name }}</td>
                                <td class="px-4 py-3 text-red-600 font-medium text-xs">{{ inv.due_date }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">
                                        {{ daysPastDue(inv.due_date) }} days
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-red-700">
                                    {{ fmt(inv.total_amount - inv.amount_paid) }}
                                </td>
                                <td class="px-4 py-3">
                                    <Link :href="route('invoices.show', inv.id)" class="text-xs text-indigo-600 hover:underline">Pay →</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
