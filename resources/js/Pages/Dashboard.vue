<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    recentInvoices: Array,
});

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function statusClass(s) {
    return { paid: 'text-green-700 bg-green-50', unpaid: 'text-red-700 bg-red-50', partial: 'text-yellow-700 bg-yellow-50' }[s] || '';
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

                <!-- Stats -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg shadow p-5">
                        <div class="text-sm text-gray-500">Total Billed (This Month)</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ fmt(stats?.monthly_total) }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-5">
                        <div class="text-sm text-green-600">Collected</div>
                        <div class="text-2xl font-bold text-green-700 mt-1">{{ fmt(stats?.monthly_paid) }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-5">
                        <div class="text-sm text-red-500">Outstanding</div>
                        <div class="text-2xl font-bold text-red-600 mt-1">{{ fmt(stats?.total_outstanding) }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-5">
                        <div class="text-sm text-gray-500">Total Invoices</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats?.invoice_count || 0 }}</div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="grid grid-cols-4 gap-4">
                    <Link :href="route('invoices.create')" class="bg-indigo-600 text-white rounded-lg p-4 hover:bg-indigo-700 text-center">
                        <div class="text-2xl mb-1">+</div>
                        <div class="text-sm font-medium">New Invoice</div>
                    </Link>
                    <Link :href="route('customers.index')" class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 text-center shadow-sm">
                        <div class="text-2xl mb-1">👥</div>
                        <div class="text-sm font-medium text-gray-700">Customers</div>
                    </Link>
                    <Link :href="route('products.index')" class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 text-center shadow-sm">
                        <div class="text-2xl mb-1">📦</div>
                        <div class="text-sm font-medium text-gray-700">Products</div>
                    </Link>
                    <Link :href="route('reports.index')" class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 text-center shadow-sm">
                        <div class="text-2xl mb-1">📊</div>
                        <div class="text-sm font-medium text-gray-700">GST Reports</div>
                    </Link>
                </div>

                <!-- Recent Invoices -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800">Recent Invoices</h3>
                        <Link :href="route('invoices.index')" class="text-sm text-indigo-600 hover:underline">View All</Link>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="inv in recentInvoices" :key="inv.id" class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-mono text-indigo-600">
                                    <Link :href="route('invoices.show', inv.id)">{{ inv.invoice_number }}</Link>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ inv.customer?.name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ inv.invoice_date }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ fmt(inv.total_amount) }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="statusClass(inv.payment_status)">
                                        {{ inv.payment_status.toUpperCase() }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!recentInvoices?.length">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">No invoices yet. <Link :href="route('invoices.create')" class="text-indigo-600 hover:underline">Create your first invoice</Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
