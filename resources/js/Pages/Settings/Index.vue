<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ tenant: Object });

const form = useForm({
    name:           props.tenant.name           ?? '',
    gstin:          props.tenant.gstin          ?? '',
    address:        props.tenant.address        ?? '',
    city:           props.tenant.city           ?? '',
    state:          props.tenant.state          ?? '',
    state_code:     props.tenant.state_code     ?? '',
    pincode:        props.tenant.pincode        ?? '',
    phone:          props.tenant.phone          ?? '',
    email:          props.tenant.email          ?? '',
    invoice_prefix: props.tenant.invoice_prefix ?? 'INV',
    bank_details: {
        bank_name:      props.tenant.bank_details?.bank_name      ?? '',
        account_number: props.tenant.bank_details?.account_number ?? '',
        ifsc_code:      props.tenant.bank_details?.ifsc_code      ?? '',
        account_name:   props.tenant.bank_details?.account_name   ?? '',
    },
});

const STATES = [
    { name: 'Andhra Pradesh', code: '37' }, { name: 'Arunachal Pradesh', code: '12' },
    { name: 'Assam', code: '18' }, { name: 'Bihar', code: '10' },
    { name: 'Chhattisgarh', code: '22' }, { name: 'Goa', code: '30' },
    { name: 'Gujarat', code: '24' }, { name: 'Haryana', code: '06' },
    { name: 'Himachal Pradesh', code: '02' }, { name: 'Jharkhand', code: '20' },
    { name: 'Karnataka', code: '29' }, { name: 'Kerala', code: '32' },
    { name: 'Madhya Pradesh', code: '23' }, { name: 'Maharashtra', code: '27' },
    { name: 'Manipur', code: '14' }, { name: 'Meghalaya', code: '17' },
    { name: 'Mizoram', code: '15' }, { name: 'Nagaland', code: '13' },
    { name: 'Odisha', code: '21' }, { name: 'Punjab', code: '03' },
    { name: 'Rajasthan', code: '08' }, { name: 'Sikkim', code: '11' },
    { name: 'Tamil Nadu', code: '33' }, { name: 'Telangana', code: '36' },
    { name: 'Tripura', code: '16' }, { name: 'Uttar Pradesh', code: '09' },
    { name: 'Uttarakhand', code: '05' }, { name: 'West Bengal', code: '19' },
    { name: 'Delhi', code: '07' }, { name: 'Jammu & Kashmir', code: '01' },
    { name: 'Ladakh', code: '38' }, { name: 'Puducherry', code: '34' },
    { name: 'Chandigarh', code: '04' },
];

function onStateChange() {
    const s = STATES.find(s => s.name === form.state);
    if (s) form.state_code = s.code;
}

function save() {
    form.patch(route('settings.update'));
}
</script>

<template>
    <Head title="Business Settings" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Business Settings</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">

                <!-- Flash -->
                <div v-if="$page.props.flash?.success" class="px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <form @submit.prevent="save" class="space-y-6">

                    <!-- Business Details -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Business Details</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="text-sm font-medium text-gray-700 block mb-1">Business Name *</label>
                                <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required />
                                <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">GSTIN</label>
                                <input v-model="form.gstin" type="text" maxlength="15" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono" placeholder="22AAAAA0000A1Z5" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">Invoice Prefix *</label>
                                <input v-model="form.invoice_prefix" type="text" maxlength="10" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono" placeholder="INV" required />
                                <p class="text-xs text-gray-400 mt-1">Used for invoice numbers e.g. {{ form.invoice_prefix }}-0001</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">Phone</label>
                                <input v-model="form.phone" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">Email</label>
                                <input v-model="form.email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Address</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="text-sm font-medium text-gray-700 block mb-1">Street Address</label>
                                <input v-model="form.address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">City</label>
                                <input v-model="form.city" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">Pincode</label>
                                <input v-model="form.pincode" type="text" maxlength="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">State</label>
                                <select v-model="form.state" @change="onStateChange" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">Select State</option>
                                    <option v-for="s in STATES" :key="s.code" :value="s.name">{{ s.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">State Code</label>
                                <input v-model="form.state_code" type="text" maxlength="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50" readonly />
                            </div>
                        </div>
                    </div>

                    <!-- Bank Details -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Bank Details <span class="text-xs text-gray-400 font-normal">(shown on invoice)</span></h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">Bank Name</label>
                                <input v-model="form.bank_details.bank_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="HDFC Bank" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">Account Name</label>
                                <input v-model="form.bank_details.account_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">Account Number</label>
                                <input v-model="form.bank_details.account_number" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">IFSC Code</label>
                                <input v-model="form.bank_details.ifsc_code" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase" placeholder="HDFC0001234" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : 'Save Settings' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
