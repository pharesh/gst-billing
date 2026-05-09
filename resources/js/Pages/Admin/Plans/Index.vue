<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ plans: Array });

const showCreate = ref(false);
const editingPlan = ref(null);

const createForm = useForm({
    name: '', slug: '', price_monthly: '', invoice_limit: 10,
    customer_limit: 10, product_limit: 10,
    whatsapp_enabled: false, gstr_export: false, pdf_download: true, multi_user: false,
    features: '',
});

const editForm = useForm({
    name: '', slug: '', price_monthly: '', invoice_limit: 10,
    customer_limit: 10, product_limit: 10,
    whatsapp_enabled: false, gstr_export: false, pdf_download: true, multi_user: false,
    features: '',
});

function startEdit(plan) {
    editingPlan.value = plan.id;
    editForm.name = plan.name;
    editForm.slug = plan.slug;
    editForm.price_monthly = plan.price_monthly;
    editForm.invoice_limit = plan.invoice_limit;
    editForm.customer_limit = plan.customer_limit;
    editForm.product_limit = plan.product_limit;
    editForm.whatsapp_enabled = plan.whatsapp_enabled;
    editForm.gstr_export = plan.gstr_export;
    editForm.pdf_download = plan.pdf_download;
    editForm.multi_user = plan.multi_user;
    editForm.features = Array.isArray(plan.features) ? plan.features.join('\n') : '';
}

function submitCreate() {
    const data = { ...createForm.data(), features: createForm.features.split('\n').filter(f => f.trim()) };
    createForm.transform(() => data).post(route('admin.plans.store'), {
        onSuccess: () => { showCreate.value = false; createForm.reset(); },
    });
}

function submitEdit(planId) {
    const data = { ...editForm.data(), features: editForm.features.split('\n').filter(f => f.trim()) };
    editForm.transform(() => data).patch(route('admin.plans.update', planId), {
        onSuccess: () => { editingPlan.value = null; },
    });
}

function deletePlan(plan) {
    if (confirm(`Delete plan "${plan.name}"? This cannot be undone.`)) {
        useForm({}).delete(route('admin.plans.destroy', plan.id));
    }
}

function limitLabel(v) { return v === -1 ? '∞' : v; }
</script>

<template>
    <Head title="Admin — Plans" />

    <div class="min-h-screen bg-gray-100">
        <nav class="bg-gray-900 text-white px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-8">
                <span class="font-bold text-lg">GST Billing <span class="text-indigo-400 text-sm">Admin</span></span>
                <div class="flex gap-6 text-sm">
                    <Link :href="route('admin.dashboard')" class="text-gray-300 hover:text-white">Dashboard</Link>
                    <Link :href="route('admin.tenants.index')" class="text-gray-300 hover:text-white">Tenants</Link>
                    <Link :href="route('admin.plans.index')" class="text-indigo-300 font-medium">Plans</Link>
                </div>
            </div>
            <Link :href="route('dashboard')" class="text-xs text-gray-400 hover:text-white">← Back to App</Link>
        </nav>

        <div class="p-6 max-w-6xl mx-auto space-y-4">

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ $page.props.flash.error }}
            </div>

            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Subscription Plans</h2>
                <button @click="showCreate = !showCreate"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">
                    + New Plan
                </button>
            </div>

            <!-- Create Form -->
            <div v-if="showCreate" class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Create New Plan</h3>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Plan Name</label>
                            <input v-model="createForm.name" type="text" required placeholder="e.g. Starter"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Slug</label>
                            <input v-model="createForm.slug" type="text" required placeholder="e.g. starter"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Price/Month (₹)</label>
                            <input v-model="createForm.price_monthly" type="number" min="0" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Invoice Limit (-1 = ∞)</label>
                            <input v-model.number="createForm.invoice_limit" type="number" min="-1"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Customer Limit (-1 = ∞)</label>
                            <input v-model.number="createForm.customer_limit" type="number" min="-1"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Product Limit (-1 = ∞)</label>
                            <input v-model.number="createForm.product_limit" type="number" min="-1"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div class="flex gap-6 flex-wrap">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="createForm.whatsapp_enabled" class="rounded" />
                            WhatsApp Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="createForm.gstr_export" class="rounded" />
                            GSTR Export
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="createForm.pdf_download" class="rounded" />
                            PDF Download
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="createForm.multi_user" class="rounded" />
                            Multi-User
                        </label>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Features (one per line)</label>
                        <textarea v-model="createForm.features" rows="3" placeholder="10 invoices/month&#10;10 customers&#10;PDF downloads"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" :disabled="createForm.processing"
                                class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Create Plan
                        </button>
                        <button type="button" @click="showCreate = false"
                                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Plans Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Plan</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Price</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Invoices</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Customers</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Products</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Features</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template v-for="plan in plans" :key="plan.id">
                            <!-- View Row -->
                            <tr v-if="editingPlan !== plan.id">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">{{ plan.name }}</div>
                                    <div class="text-xs text-gray-400">{{ plan.slug }}</div>
                                </td>
                                <td class="px-4 py-3 font-medium text-indigo-600">
                                    {{ plan.price_monthly === 0 ? 'Free' : '₹' + plan.price_monthly + '/mo' }}
                                </td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ limitLabel(plan.invoice_limit) }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ limitLabel(plan.customer_limit) }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ limitLabel(plan.product_limit) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-2 flex-wrap">
                                        <span v-if="plan.whatsapp_enabled" class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">WhatsApp</span>
                                        <span v-if="plan.gstr_export" class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">GSTR</span>
                                        <span v-if="plan.pdf_download" class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">PDF</span>
                                        <span v-if="plan.multi_user" class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">Multi-User</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex gap-3 justify-end">
                                        <button @click="startEdit(plan)" class="text-xs text-indigo-600 hover:underline">Edit</button>
                                        <button @click="deletePlan(plan)" class="text-xs text-red-500 hover:underline">Delete</button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Row -->
                            <tr v-else>
                                <td colspan="7" class="px-4 py-4 bg-indigo-50">
                                    <form @submit.prevent="submitEdit(plan.id)" class="space-y-3">
                                        <div class="grid grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                                                <input v-model="editForm.name" type="text" required
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Slug</label>
                                                <input v-model="editForm.slug" type="text" required
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Price (₹/mo)</label>
                                                <input v-model="editForm.price_monthly" type="number" min="0"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Invoice Limit</label>
                                                <input v-model.number="editForm.invoice_limit" type="number" min="-1"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Customer Limit</label>
                                                <input v-model.number="editForm.customer_limit" type="number" min="-1"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Product Limit</label>
                                                <input v-model.number="editForm.product_limit" type="number" min="-1"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                                            </div>
                                        </div>
                                        <div class="flex gap-6 flex-wrap">
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" v-model="editForm.whatsapp_enabled" class="rounded" />
                                                WhatsApp
                                            </label>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" v-model="editForm.gstr_export" class="rounded" />
                                                GSTR Export
                                            </label>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" v-model="editForm.pdf_download" class="rounded" />
                                                PDF Download
                                            </label>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" v-model="editForm.multi_user" class="rounded" />
                                                Multi-User
                                            </label>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Features (one per line)</label>
                                            <textarea v-model="editForm.features" rows="2"
                                                      class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                                        </div>
                                        <div class="flex gap-3">
                                            <button type="submit" :disabled="editForm.processing"
                                                    class="px-4 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                                Save
                                            </button>
                                            <button type="button" @click="editingPlan = null"
                                                    class="px-4 py-1.5 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
