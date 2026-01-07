<template>
    <AppLayout>
        <Head title="Dashboard Survei EDOM" />

        <div class="space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Dashboard Survei EDOM
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Ringkasan hasil evaluasi dosen oleh mahasiswa
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <select
                        v-model="selectedPeriodId"
                        @change="loadData"
                        class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20"
                    >
                        <option value="">Pilih Periode</option>
                        <option v-for="p in periods" :key="p.id" :value="p.id">
                            {{ p.nama || "Periode " + p.id }} -
                            {{ p.tahun_akademik?.nama }}
                        </option>
                    </select>
                    <button
                        @click="exportData"
                        class="p-2.5 bg-green-50 hover:bg-green-100 text-green-600 dark:bg-green-900/20 dark:hover:bg-green-900/30 dark:text-green-400 rounded-xl transition-all"
                        title="Export Excel"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ stats?.total_targets || 0 }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Total Target
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ stats?.total_responses || 0 }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Total Response
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ stats?.avg_score?.toFixed(2) || "0.00" }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Skor Rata-rata
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400"
                        >
                            <svg
                                class="w-6 h-6"
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
                        </div>
                        <div>
                            <p
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ stats?.response_rate?.toFixed(0) || 0 }}%
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Response Rate
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question Results -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden"
            >
                <div
                    class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4"
                >
                    <h2 class="text-lg font-semibold text-white">
                        Hasil Per Pertanyaan
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div
                        v-for="(qs, index) in questionStats"
                        :key="index"
                        class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                    >
                        <div
                            class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4"
                        >
                            <div class="flex-1">
                                <span
                                    v-if="qs.kategori"
                                    class="inline-block px-2 py-0.5 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-medium rounded-full mb-2"
                                    >{{ qs.kategori }}</span
                                >
                                <p
                                    class="font-medium text-gray-900 dark:text-white"
                                >
                                    {{ qs.question }}
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-3xl font-bold text-primary-600">
                                    {{ qs.avg_score?.toFixed(2) || "0.00"
                                    }}<span
                                        class="text-base text-gray-400 font-normal"
                                        >/5</span
                                    >
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ qs.total_responses }} responden
                                </p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="dist in qs.distribution"
                                :key="dist.label"
                                class="flex items-center gap-3"
                            >
                                <span
                                    class="w-36 text-sm text-gray-600 dark:text-gray-400 truncate text-right"
                                    >{{ dist.label }}</span
                                >
                                <div
                                    class="flex-1 h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden"
                                >
                                    <div
                                        class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full transition-all duration-500"
                                        :style="{
                                            width: `${
                                                qs.total_responses
                                                    ? (dist.count /
                                                          qs.total_responses) *
                                                      100
                                                    : 0
                                            }%`,
                                        }"
                                    ></div>
                                </div>
                                <span
                                    class="w-10 text-sm text-gray-600 dark:text-gray-400 text-right"
                                    >{{ dist.count }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="!questionStats || questionStats.length === 0"
                        class="text-center py-12"
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
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                            />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">
                            Pilih periode untuk melihat hasil survei
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";

const props = defineProps({
    periods: Array,
    selectedPeriodId: Number,
    stats: Object,
    questionStats: Array,
});

const selectedPeriodId = ref(props.selectedPeriodId);

const loadData = () => {
    router.get(
        route("survey.dashboard"),
        { period_id: selectedPeriodId.value },
        { preserveState: true }
    );
};

const exportData = () => {
    if (selectedPeriodId.value) {
        window.open(
            route("survey.dashboard") +
                "?period_id=" +
                selectedPeriodId.value +
                "&export=excel",
            "_blank"
        );
    } else {
        alert("Pilih periode terlebih dahulu");
    }
};
</script>
