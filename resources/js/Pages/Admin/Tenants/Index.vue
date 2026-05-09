<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ tenants: Object, plans: Array, filters: Object });

const search = ref(props.filters.search ?? '');

function doSearch() {
    router.get(route('admin.tenants.index'), { search: search.value }, { preserveState: true });
}

function toggleSuspend(tenant) {
    if (confirm(`${tenant.is_suspended ? 'Reactivate' : 'Suspend'} ${tenant.name}?`)) {
        router.post(route('admin.tenants.toggle-suspend', tenant.id));
    }
}

function deleteTenant(tenant) {
    if (confirm(`Permanently delete ${tenant.name} and ALL their data? This cannot be undone.`)) {
        router.delete(route('admin.tenants.destroy', tenant.id));
    }
}

function date(d) { return d ? new Date(d).toLocaleDateString('en-IN') : '-'; }
</script>

<template>
    <Head title="Admin — Tenants" />

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
            <Link :href="route('dashboard')" class="text-xs text-gray-400 hover:text-white">← Back to App</Link>
        </nav>

        <div class="p-6 max-w-7xl mx-auto space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">All Tenants ({{ tenants.total }})</h2>
                <input v-model="search" @keyup.enter="doSearch" type="text" placeholder="Search name or email..."
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-64" />
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ $page.props.flash.success }}
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Business</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Plan</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Invoices</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Customers</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Joined</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="t in tenants.data" :key="t.id" :class="t.is_suspended ? 'bg-red-50' : ''">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.tenants.show', t.id)" class="font-medium text-gray-800 hover:text-indigo-600">{{ t.name }}</Link>
                                <div class="text-xs text-gray-400">{{ t.email ?? 'No email' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium"
                                      :class="t.plan ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500'">
                                    {{ t.plan?.name ?? 'Free' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ t.invoices_count }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ t.customers_count }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ date(t.created_at) }}</td>
                            <td class="px-4 py-3">
                                <span :class="t.is_suspended ? 'text-red-600 font-medium' : 'text-green-600'">
                                    {{ t.is_suspended ? 'Suspended' : 'Active' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-2 justify-end">
                                    <Link :href="route('admin.tenants.show', t.id)" class="text-xs text-indigo-600 hover:underline">View</Link>
                                    <button @click="toggleSuspend(t)" class="text-xs hover:underline"
                                            :class="t.is_suspended ? 'text-green-600' : 'text-orange-500'">
                                        {{ t.is_suspended ? 'Reactivate' : 'Suspend' }}
                                    </button>
                                    <button @click="deleteTenant(t)" class="text-xs text-red-500 hover:underline">Delete</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t flex justify-between items-center text-sm text-gray-600">
                    <span>Showing {{ tenants.from }}–{{ tenants.to }} of {{ tenants.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="tenants.prev_page_url" :href="tenants.prev_page_url" class="px-3 py-1 border rounded hover:bg-gray-50">← Prev</Link>
                        <Link v-if="tenants.next_page_url" :href="tenants.next_page_url" class="px-3 py-1 border rounded hover:bg-gray-50">Next →</Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
