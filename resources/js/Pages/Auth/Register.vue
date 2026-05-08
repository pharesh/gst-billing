<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

// Indian states with GST state codes
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
    { name: 'Delhi', code: '07' }, { name: 'Jammu and Kashmir', code: '01' },
    { name: 'Ladakh', code: '38' }, { name: 'Chandigarh', code: '04' },
    { name: 'Dadra and Nagar Haveli', code: '26' }, { name: 'Daman and Diu', code: '25' },
    { name: 'Lakshadweep', code: '31' }, { name: 'Puducherry', code: '34' },
    { name: 'Andaman and Nicobar Islands', code: '35' },
];

const form = useForm({
    // Account
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    // Business
    company_name: '',
    gstin: '',
    state: '',
    state_code: '',
    invoice_prefix: 'INV',
});

function onStateChange() {
    const selected = STATES.find(s => s.name === form.state);
    form.state_code = selected?.code || '';
}

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Create Account" />

        <form @submit.prevent="submit" class="space-y-0">

            <!-- Account Details Section -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 pb-2 border-b">Your Account</h3>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Your Full Name" />
                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus autocomplete="name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email Address" />
                        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autocomplete="username" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Password" />
                        <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required autocomplete="new-password" />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password" />
                        <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" required autocomplete="new-password" />
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>
                </div>
            </div>

            <!-- Business Details Section -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 pb-2 border-b">Your Business</h3>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="company_name" value="Business / Company Name *" />
                        <TextInput id="company_name" type="text" class="mt-1 block w-full" v-model="form.company_name" required placeholder="e.g. Sharma Traders" />
                        <InputError class="mt-2" :message="form.errors.company_name" />
                    </div>

                    <div>
                        <InputLabel for="gstin" value="GSTIN (optional)" />
                        <TextInput
                            id="gstin"
                            type="text"
                            class="mt-1 block w-full font-mono uppercase"
                            v-model="form.gstin"
                            maxlength="15"
                            placeholder="22AAAAA0000A1Z5"
                        />
                        <p class="text-xs text-gray-400 mt-1">15-digit GST Identification Number. You can add this later.</p>
                        <InputError class="mt-2" :message="form.errors.gstin" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="state" value="State" />
                            <select
                                id="state"
                                v-model="form.state"
                                @change="onStateChange"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="">Select state</option>
                                <option v-for="s in STATES" :key="s.code" :value="s.name">{{ s.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.state" />
                        </div>
                        <div>
                            <InputLabel for="invoice_prefix" value="Invoice Prefix" />
                            <TextInput
                                id="invoice_prefix"
                                type="text"
                                class="mt-1 block w-full font-mono uppercase"
                                v-model="form.invoice_prefix"
                                maxlength="10"
                                placeholder="INV"
                            />
                            <p class="text-xs text-gray-400 mt-1">e.g. INV → INV-0001</p>
                            <InputError class="mt-2" :message="form.errors.invoice_prefix" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Create Account
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
