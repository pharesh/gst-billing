<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    recurringInvoices: Object,
});

function toggle(id) {
    router.post(route('recurring-invoices.toggle', id));
}

function del(id) {
    if (confirm('Delete this recurring schedule? Future invoices will not be generated.')) {
        router.delete(route('recurring-invoices.destroy', id));
    }
}

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function itemTotal(items) {
    return items?.reduce((sum, i) => {
        const taxable = i.price * i.quantity * (1 - (i.discount_percent || 0) / 100);
        return sum + taxable * (1 + i.gst_rate / 100);
    }, 0) || 0;
}
</script>

<template>
    <Head title="Recurring Invoices" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Recurring Invoices</h2>
                <Link :href="route('recurring-invoices.create')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    + New Schedule
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-4">

                <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-700">
                    Recurring invoices are auto-generated daily at 6:00 AM when they're due.
                    Run <code class="font-mono bg-blue-100 px-1 rounded">php artisan invoices:process-recurring</code> to trigger manually.
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-600">Title</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Customer</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Frequency</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Next Run</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Last Run</th>
                                <th class="px-4 py-3 font-medium text-gray-600 text-right">Est. Amount</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="ri in recurringInvoices.data" :key="ri.id" class="hover:bg-gray-50"
                                :class="!ri.is_active ? 'opacity-60' : ''">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ ri.title }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ ri.customer?.name }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs capitalize font-medium">
                                        {{ ri.frequency }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ ri.next_run_date }}</td>
                                <td class="px-4 py-3 text-gray-400 text-xs">{{ ri.last_run_date || '—' }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ fmt(itemTotal(ri.items)) }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                          :class="ri.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                                        {{ ri.is_active ? 'Active' : 'Paused' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button @click="toggle(ri.id)" class="text-xs hover:underline"
                                                :class="ri.is_active ? 'text-orange-500' : 'text-green-600'">
                                            {{ ri.is_active ? 'Pause' : 'Resume' }}
                                        </button>
                                        <button @click="del(ri.id)" class="text-red-500 hover:underline text-xs">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="recurringInvoices.data.length === 0">
                                <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                                    No recurring schedules yet.
                                    <Link :href="route('recurring-invoices.create')" class="text-indigo-600 hover:underline ml-1">Create one</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-500">
                        <span>{{ recurringInvoices.total }} schedule{{ recurringInvoices.total !== 1 ? 's' : '' }}</span>
                        <div class="flex gap-2">
                            <Link v-if="recurringInvoices.prev_page_url" :href="recurringInvoices.prev_page_url" class="text-indigo-600 hover:underline">← Prev</Link>
                            <Link v-if="recurringInvoices.next_page_url" :href="recurringInvoices.next_page_url" class="text-indigo-600 hover:underline">Next →</Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
