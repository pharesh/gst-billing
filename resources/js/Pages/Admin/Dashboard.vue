<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({ stats: Object, recentTenants: Array, recentSubscriptions: Array });

function fmt(n) { return '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2 }); }
function date(d) { return d ? new Date(d).toLocaleDateString('en-IN') : '-'; }
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="min-h-screen bg-gray-100">
        <!-- Admin Nav -->
        <nav class="bg-gray-900 text-white px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-8">
                <span class="font-bold text-lg">GST Billing <span class="text-indigo-400 text-sm">Admin</span></span>
                <div class="flex gap-6 text-sm">
                    <Link :href="route('admin.dashboard')" class="text-indigo-300 font-medium">Dashboard</Link>
                    <Link :href="route('admin.tenants.index')" class="text-gray-300 hover:text-white">Tenants</Link>
                    <Link :href="route('admin.plans.index')" class="text-gray-300 hover:text-white">Plans</Link>
                </div>
            </div>
            <Link :href="route('dashboard')" class="text-xs text-gray-400 hover:text-white">← Back to App</Link>
        </nav>

        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Stats -->
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="text-sm text-gray-500">Total Tenants</div>
                    <div class="text-3xl font-bold text-gray-900 mt-1">{{ stats.total_tenants }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="text-sm text-gray-500">Active Subscriptions</div>
                    <div class="text-3xl font-bold text-green-600 mt-1">{{ stats.active_subs }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="text-sm text-gray-500">Monthly Revenue (MRR)</div>
                    <div class="text-3xl font-bold text-indigo-600 mt-1">{{ fmt(stats.mrr) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="text-sm text-gray-500">New This Month</div>
                    <div class="text-3xl font-bold text-orange-500 mt-1">{{ stats.new_this_month }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <!-- Recent Tenants -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-800">Recent Tenants</h3>
                        <Link :href="route('admin.tenants.index')" class="text-xs text-indigo-600 hover:underline">View all</Link>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="t in recentTenants" :key="t.id">
                                <td class="py-2">
                                    <Link :href="route('admin.tenants.show', t.id)" class="font-medium text-gray-800 hover:text-indigo-600">{{ t.name }}</Link>
                                    <div class="text-xs text-gray-400">{{ t.email }}</div>
                                </td>
                                <td class="py-2 text-right">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                          :class="t.plan ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600'">
                                        {{ t.plan?.name ?? 'Free' }}
                                    </span>
                                </td>
                                <td class="py-2 text-right text-xs text-gray-400">{{ date(t.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Subscriptions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Recent Payments</h3>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="sub in recentSubscriptions" :key="sub.id">
                                <td class="py-2">
                                    <div class="font-medium text-gray-800">{{ sub.tenant?.name }}</div>
                                    <div class="text-xs text-gray-400">{{ sub.plan?.name }}</div>
                                </td>
                                <td class="py-2 text-right">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                          :class="sub.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">
                                        {{ sub.status }}
                                    </span>
                                </td>
                                <td class="py-2 text-right font-medium text-indigo-600">
                                    {{ sub.amount_paid > 0 ? fmt(sub.amount_paid) : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</template>
