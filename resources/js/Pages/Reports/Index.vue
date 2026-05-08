<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

const month = ref(new Date().getMonth() + 1);
const year = ref(new Date().getFullYear());

function viewGSTR1() {
    router.get(route('reports.gstr1'), { month: month.value, year: year.value });
}

function viewGSTR3B() {
    router.get(route('reports.gstr3b'), { month: month.value, year: year.value });
}

function downloadGSTR1() {
    window.location.href = route('reports.gstr1.download') + `?month=${month.value}&year=${year.value}`;
}
</script>

<template>
    <Head title="GST Reports" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">GST Reports</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8 space-y-6">

                <!-- Period Selector -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-base font-semibold mb-4">Select Filing Period</h3>
                    <div class="flex gap-4 items-end">
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Month</label>
                            <select v-model="month" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40">
                                <option v-for="(m, i) in MONTHS" :key="i" :value="i + 1">{{ m }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">Year</label>
                            <select v-model="year" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-28">
                                <option v-for="y in [2023,2024,2025,2026]" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Report Cards -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-base font-bold text-gray-800 mb-1">GSTR-1</div>
                        <div class="text-sm text-gray-500 mb-4">Outward supply details. File on GST portal by 11th of next month.</div>
                        <div class="flex gap-2">
                            <button @click="viewGSTR1" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">View Report</button>
                            <button @click="downloadGSTR1" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Download JSON</button>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-base font-bold text-gray-800 mb-1">GSTR-3B</div>
                        <div class="text-sm text-gray-500 mb-4">Summary return with tax liability. File by 20th of next month.</div>
                        <div class="flex gap-2">
                            <button @click="viewGSTR3B" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">View Summary</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
