<template>
    <AppLayout>
        <Head title="Dashboard Survei EDOM" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Instrument Survei
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">
                    Daftar periode survei EDOM yang tersedia
                </p>
            </div>

            <!-- Period List Table -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    #
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    Nama Periode
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    Tahun Akademik
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    Responden
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-800"
                        >
                            <tr
                                v-for="(period, idx) in periods"
                                :key="period.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50"
                            >
                                <td class="px-6 py-4 text-gray-500">
                                    {{ idx + 1 }}
                                </td>
                                <td class="px-6 py-4">
                                    <p
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {{
                                            period.nama ||
                                            "Periode " + period.id
                                        }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ period.slug }}
                                    </p>
                                </td>
                                <td
                                    class="px-6 py-4 text-gray-600 dark:text-gray-400"
                                >
                                    {{ period.tahun_akademik?.nama || "-" }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400"
                                    >
                                        {{ period.responses_count || 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                        :class="
                                            period.status === 'active'
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                        "
                                    >
                                        {{
                                            period.status === "active"
                                                ? "Aktif"
                                                : "Ditutup"
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'survey.dashboard.data',
                                                    period.id
                                                )
                                            "
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 dark:text-blue-400 text-sm font-medium transition"
                                        >
                                            <svg
                                                class="w-4 h-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                />
                                            </svg>
                                            Data
                                        </Link>
                                        <Link
                                            :href="
                                                route(
                                                    'survey.dashboard.chart',
                                                    period.id
                                                )
                                            "
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-50 hover:bg-green-100 text-green-600 dark:bg-green-900/20 dark:hover:bg-green-900/30 dark:text-green-400 text-sm font-medium transition"
                                        >
                                            <svg
                                                class="w-4 h-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                                />
                                            </svg>
                                            Chart
                                        </Link>
                                        <Link
                                            :href="
                                                route(
                                                    'survey.dashboard.matrix',
                                                    period.id
                                                )
                                            "
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-600 dark:bg-purple-900/20 dark:hover:bg-purple-900/30 dark:text-purple-400 text-sm font-medium transition"
                                        >
                                            <svg
                                                class="w-4 h-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                                                />
                                            </svg>
                                            Matrix
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="!periods || periods.length === 0"
                    class="p-12 text-center"
                >
                    <svg
                        class="w-16 h-16 text-gray-300 mx-auto mb-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">
                        Belum ada periode survei
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";

defineProps({
    periods: Array,
});
</script>
