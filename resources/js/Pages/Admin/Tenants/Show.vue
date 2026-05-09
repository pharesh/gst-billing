<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ tenant: Object, plans: Array, stats: Object });

const planForm = useForm({ plan_id: props.tenant.plan_id ?? '' });

function assignPlan() {
    planForm.post(route('admin.tenants.assign-plan', props.tenant.id));
}

function toggleSuspend() {
    const action = props.tenant.is_suspended ? 'reactivate' : 'suspend';
    if (confirm(`Are you sure you want to ${action} ${props.tenant.name}?`)) {
        router.post(route('admin.tenants.toggle-suspend', props.tenant.id));
    }
}

function fmt(n) { return '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2 }); }
function date(d) { return d ? new Date(d).toLocaleDateString('en-IN') : '-'; }
</script>

<template>
    <Head :title="`Admin — ${tenant.name}`" />

    <div class="min-h-screen bg-gray-100">
        <nav class="bg-gray-900 text-white px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-8">
                <span class="font-bold text-lg">GST Billing <span class="text-indigo-400 text-sm">Admin</span></span>
                <div class="flex gap-6 text-sm">
                    <Link :href="route('admin.dashboard')" class="text-gray-300 hover:text-white">Dashboard</Link>
                    <Link :href="route('admin.tenants.index')" class="text-indigo-300 font-medium">Tenants</Link>
                    <Link :href="route('admin.plans.index')" class="text-gray-300 hover:text-white">Plans</Link>
                </div>
            </div>
            <Link :href="route('admin.tenants.index')" class="text-xs text-gray-400 hover:text-white">← All Tenants</Link>
        </nav>

        <div class="p-6 max-w-5xl mx-auto space-y-6">

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ $page.props.flash.success }}
            </div>

            <!-- Header -->
            <div class="bg-white rounded-lg shadow p-6 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">{{ tenant.name }}</h2>
                    <div class="text-sm text-gray-500 mt-1">{{ tenant.email }} • GSTIN: {{ tenant.gstin ?? 'N/A' }}</div>
                    <div class="text-sm text-gray-500">{{ tenant.city }}, {{ tenant.state }}</div>
                </div>
                <div class="flex gap-3">
                    <span :class="tenant.is_suspended ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                          class="px-3 py-1 rounded-full text-sm font-medium">
                        {{ tenant.is_suspended ? 'Suspended' : 'Active' }}
                    </span>
                    <button @click="toggleSuspend"
                            :class="tenant.is_suspended ? 'bg-green-600 hover:bg-green-700' : 'bg-orange-500 hover:bg-orange-600'"
                            class="px-4 py-2 text-white text-sm rounded-lg">
                        {{ tenant.is_suspended ? 'Reactivate' : 'Suspend' }}
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-indigo-600">{{ stats.invoice_count }}</div>
                    <div class="text-xs text-gray-500 mt-1">Total Invoices</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">{{ stats.customer_count }}</div>
                    <div class="text-xs text-gray-500 mt-1">Customers</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-orange-500">{{ stats.monthly_invoices }}</div>
                    <div class="text-xs text-gray-500 mt-1">Invoices This Month</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-gray-800">{{ fmt(stats.total_revenue) }}</div>
                    <div class="text-xs text-gray-500 mt-1">Total Revenue Processed</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <!-- Assign Plan -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Assign Plan</h3>
                    <div class="mb-3">
                        <span class="text-sm text-gray-500">Current plan:</span>
                        <span class="ml-2 font-medium text-indigo-600">{{ tenant.plan?.name ?? 'Free' }}</span>
                    </div>
                    <form @submit.prevent="assignPlan" class="flex gap-3">
                        <select v-model="planForm.plan_id" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Select plan...</option>
                            <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }} — ₹{{ p.price_monthly }}/mo</option>
                        </select>
                        <button type="submit" :disabled="planForm.processing"
                                class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Assign
                        </button>
                    </form>
                </div>

                <!-- Users -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Users</h3>
                    <div class="space-y-2">
                        <div v-for="user in tenant.users" :key="user.id" class="flex justify-between items-center text-sm">
                            <div>
                                <span class="font-medium text-gray-800">{{ user.name }}</span>
                                <span class="text-gray-400 ml-2">{{ user.email }}</span>
                            </div>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs capitalize">{{ user.role }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription History -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Subscription History</h3>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Plan</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Status</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Started</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Ends</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-600">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="sub in tenant.subscriptions" :key="sub.id">
                            <td class="px-3 py-2 font-medium">{{ sub.plan?.name }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                      :class="sub.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                                    {{ sub.status }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-gray-500 text-xs">{{ date(sub.starts_at) }}</td>
                            <td class="px-3 py-2 text-gray-500 text-xs">{{ date(sub.ends_at) }}</td>
                            <td class="px-3 py-2 text-right font-medium text-indigo-600">{{ sub.amount_paid > 0 ? fmt(sub.amount_paid) : '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</template>
