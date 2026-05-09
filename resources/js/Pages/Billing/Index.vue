<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    plans: Array,
    currentPlan: Object,
    activeSubscription: Object,
    subscriptions: Array,
    usage: Object,
    razorpayKey: String,
});

const payingPlanId = ref(null);
const payError = ref('');

function usagePct(used, limit) {
    if (limit === -1) return 0;
    return Math.min(100, Math.round((used / limit) * 100));
}

function upgrade(plan) {
    if (plan.price_monthly === 0) return;
    payingPlanId.value = plan.id;
    payError.value = '';

    fetch(route('billing.order'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ plan_id: plan.id }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            payError.value = data.error;
            payingPlanId.value = null;
            return;
        }
        if (!window.Razorpay) {
            payError.value = 'Payment gateway script not loaded. Please refresh the page.';
            payingPlanId.value = null;
            return;
        }
        const options = {
            key: props.razorpayKey,
            amount: data.amount,
            currency: data.currency,
            name: 'GST Billing',
            description: data.plan_name + ' Plan - Monthly',
            order_id: data.order_id,
            handler(response) {
                router.post(route('billing.verify'), {
                    razorpay_order_id:   response.razorpay_order_id,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_signature:  response.razorpay_signature,
                    subscription_id:     data.subscription_id,
                });
            },
            prefill: { name: '', email: '' },
            theme: { color: '#4f46e5' },
            modal: { ondismiss: () => { payingPlanId.value = null; } },
        };
        const rzp = new window.Razorpay(options);
        rzp.open();
    })
    .catch(err => {
        payError.value = 'Something went wrong. Please try again.';
        payingPlanId.value = null;
    });
}

function fmt(n) {
    return '₹' + Number(n).toLocaleString('en-IN');
}
</script>

<template>
    <Head title="Billing & Plans" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Billing & Plans</h2>
        </template>

        <!-- Razorpay Script -->
        <component :is="'script'" src="https://checkout.razorpay.com/v1/checkout.js" />

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Flash -->
                <div v-if="$page.props.flash?.success" class="px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="payError" class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex justify-between">
                    <span>{{ payError }}</span>
                    <button @click="payError = ''" class="text-red-400 hover:text-red-600 font-bold">✕</button>
                </div>

                <!-- Current Plan + Usage -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Current Plan</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-2xl font-bold text-indigo-600">{{ currentPlan.name }}</span>
                                <span v-if="currentPlan.price_monthly > 0" class="text-sm text-gray-500">{{ fmt(currentPlan.price_monthly) }}/month</span>
                                <span v-else class="text-sm text-gray-500">Free</span>
                            </div>
                        </div>
                        <div v-if="activeSubscription" class="text-right text-sm text-gray-500">
                            <div>Status: <span class="font-medium text-green-600 capitalize">{{ activeSubscription.status }}</span></div>
                            <div v-if="activeSubscription.ends_at">Renews: {{ new Date(activeSubscription.ends_at).toLocaleDateString('en-IN') }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mt-4">
                        <!-- Invoices -->
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Invoices this month</span>
                                <span class="font-medium">{{ usage.monthly_invoices }} / {{ currentPlan.invoice_limit === -1 ? '∞' : currentPlan.invoice_limit }}</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full transition-all" :style="{ width: currentPlan.invoice_limit === -1 ? '10%' : usagePct(usage.monthly_invoices, currentPlan.invoice_limit) + '%' }" />
                            </div>
                        </div>
                        <!-- Customers -->
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Customers</span>
                                <span class="font-medium">{{ usage.customers }} / {{ currentPlan.customer_limit === -1 ? '∞' : currentPlan.customer_limit }}</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: currentPlan.customer_limit === -1 ? '10%' : usagePct(usage.customers, currentPlan.customer_limit) + '%' }" />
                            </div>
                        </div>
                        <!-- Products -->
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Products</span>
                                <span class="font-medium">{{ usage.products }} / {{ currentPlan.product_limit === -1 ? '∞' : currentPlan.product_limit }}</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-orange-500 rounded-full transition-all" :style="{ width: currentPlan.product_limit === -1 ? '10%' : usagePct(usage.products, currentPlan.product_limit) + '%' }" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plan Cards -->
                <div class="grid grid-cols-3 gap-6">
                    <div v-for="plan in plans" :key="plan.id"
                         class="bg-white rounded-xl shadow p-6 border-2 transition-all"
                         :class="currentPlan.slug === plan.slug ? 'border-indigo-500' : 'border-transparent hover:border-indigo-200'">

                        <div v-if="plan.slug === 'pro'" class="text-center mb-3">
                            <span class="px-3 py-1 bg-indigo-600 text-white text-xs font-bold rounded-full">MOST POPULAR</span>
                        </div>

                        <h3 class="text-lg font-bold text-gray-800">{{ plan.name }}</h3>
                        <div class="mt-2 mb-4">
                            <span class="text-3xl font-bold text-gray-900">{{ plan.price_monthly === 0 ? 'Free' : fmt(plan.price_monthly) }}</span>
                            <span v-if="plan.price_monthly > 0" class="text-gray-500 text-sm">/month</span>
                        </div>

                        <ul class="space-y-2 mb-6">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2 text-sm text-gray-600">
                                <span class="text-green-500 font-bold mt-0.5">✓</span>
                                {{ feature }}
                            </li>
                        </ul>

                        <button
                            v-if="currentPlan.slug === plan.slug"
                            disabled
                            class="w-full py-2 border-2 border-indigo-500 text-indigo-600 rounded-lg text-sm font-medium opacity-70 cursor-default"
                        >
                            Current Plan
                        </button>
                        <button
                            v-else-if="plan.price_monthly > 0"
                            @click="upgrade(plan)"
                            :disabled="payingPlanId !== null"
                            class="w-full py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ payingPlanId === plan.id ? 'Processing...' : 'Upgrade to ' + plan.name }}
                        </button>
                        <div v-else class="w-full py-2 text-center text-sm text-gray-400">Basic Plan</div>
                    </div>
                </div>

                <!-- Payment History -->
                <div v-if="subscriptions.length > 0" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Payment History</h3>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-600 font-medium">Plan</th>
                                <th class="px-4 py-2 text-left text-gray-600 font-medium">Status</th>
                                <th class="px-4 py-2 text-left text-gray-600 font-medium">Date</th>
                                <th class="px-4 py-2 text-right text-gray-600 font-medium">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="sub in subscriptions" :key="sub.id">
                                <td class="px-4 py-3 font-medium">{{ sub.plan?.name }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                          :class="{ 'bg-green-100 text-green-700': sub.status === 'active', 'bg-gray-100 text-gray-600': sub.status !== 'active' }">
                                        {{ sub.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ sub.created_at ? new Date(sub.created_at).toLocaleDateString('en-IN') : '-' }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ sub.amount_paid > 0 ? fmt(sub.amount_paid) : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
