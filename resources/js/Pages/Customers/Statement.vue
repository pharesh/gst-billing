<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    customer: Object,
    invoices: Array,
    summary: Object,
});

function fmt(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function statusClass(s) {
    return {
        paid:    'bg-green-100 text-green-800',
        unpaid:  'bg-red-100 text-red-800',
        partial: 'bg-yellow-100 text-yellow-800',
    }[s] || '';
}

function balanceDue(inv) {
    return Number(inv.total_amount) - Number(inv.amount_paid);
}
</script>

<template>
    <Head :title="`Statement – ${customer.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Account Statement</h2>
                    <p class="text-sm text-gray-500 mt-0.5">{{ customer.name }}</p>
                </div>
                <Link :href="route('customers.index')" class="text-sm text-indigo-600 hover:underline">← Customers</Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8 space-y-6">

                <!-- Customer Info -->
                <div class="bg-white rounded-lg shadow p-6 grid grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-800 text-base mb-3">Customer Details</h3>
                        <dl class="space-y-1 text-sm">
                            <div class="flex gap-2">
                                <dt class="text-gray-500 w-24">Name</dt>
                                <dd class="font-medium text-gray-800">{{ customer.name }}</dd>
                            </div>
                            <div v-if="customer.gstin" class="flex gap-2">
                                <dt class="text-gray-500 w-24">GSTIN</dt>
                                <dd class="font-mono text-gray-700">{{ customer.gstin }}</dd>
                            </div>
                            <div v-if="customer.phone" class="flex gap-2">
                                <dt class="text-gray-500 w-24">Phone</dt>
                                <dd class="text-gray-700">{{ customer.phone }}</dd>
                            </div>
                            <div v-if="customer.email" class="flex gap-2">
                                <dt class="text-gray-500 w-24">Email</dt>
                                <dd class="text-gray-700">{{ customer.email }}</dd>
                            </div>
                            <div v-if="customer.address" class="flex gap-2">
                                <dt class="text-gray-500 w-24">Address</dt>
                                <dd class="text-gray-700">{{ customer.address }}, {{ customer.city }}, {{ customer.state }} {{ customer.pincode }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-800 text-base mb-3">Account Summary</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Total Billed</span>
                                <span class="font-semibold text-gray-800">{{ fmt(summary.total_billed) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-green-600">Total Paid</span>
                                <span class="font-semibold text-green-700">{{ fmt(summary.total_paid) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm font-medium" :class="summary.total_balance > 0 ? 'text-red-600' : 'text-gray-600'">Balance Due</span>
                                <span class="font-bold text-lg" :class="summary.total_balance > 0 ? 'text-red-600' : 'text-gray-400'">
                                    {{ fmt(summary.total_balance) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice + Payment History -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h3 class="font-semibold text-gray-800">Transaction History</h3>
                    </div>

                    <div v-if="invoices.length === 0" class="px-6 py-10 text-center text-gray-400">
                        No invoices found for this customer.
                    </div>

                    <div v-else>
                        <div v-for="inv in invoices" :key="inv.id" class="border-b last:border-0">
                            <!-- Invoice Row -->
                            <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                                <div class="flex items-center gap-4">
                                    <div>
                                        <Link :href="route('invoices.show', inv.id)" class="font-mono text-indigo-600 font-medium text-sm hover:underline">
                                            {{ inv.invoice_number }}
                                        </Link>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ inv.invoice_date }}{{ inv.due_date ? ' · Due: ' + inv.due_date : '' }}</div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(inv.payment_status)">
                                        {{ inv.payment_status.toUpperCase() }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-gray-800">{{ fmt(inv.total_amount) }}</div>
                                    <div v-if="balanceDue(inv) > 0" class="text-xs text-red-500">Balance: {{ fmt(balanceDue(inv)) }}</div>
                                    <div v-else class="text-xs text-green-600">Fully paid</div>
                                </div>
                            </div>

                            <!-- Payment sub-rows -->
                            <div v-for="pay in inv.payments" :key="pay.id"
                                 class="px-6 py-2 bg-green-50 flex items-center justify-between text-xs border-t border-green-100">
                                <div class="flex items-center gap-3 text-green-700">
                                    <span class="text-green-500">✓ Payment</span>
                                    <span class="text-gray-500">{{ pay.payment_date }}</span>
                                    <span v-if="pay.payment_mode" class="text-gray-400 capitalize">via {{ pay.payment_mode }}</span>
                                    <span v-if="pay.reference" class="text-gray-400">Ref: {{ pay.reference }}</span>
                                </div>
                                <span class="font-semibold text-green-700">{{ fmt(pay.amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer totals -->
                    <div v-if="invoices.length > 0" class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-8 text-sm">
                        <div class="text-gray-500">Total Billed: <span class="font-semibold text-gray-800">{{ fmt(summary.total_billed) }}</span></div>
                        <div class="text-green-600">Paid: <span class="font-semibold text-green-700">{{ fmt(summary.total_paid) }}</span></div>
                        <div :class="summary.total_balance > 0 ? 'text-red-600' : 'text-gray-500'">
                            Balance: <span class="font-bold">{{ fmt(summary.total_balance) }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
