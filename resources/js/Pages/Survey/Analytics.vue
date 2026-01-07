<template>
    <AppLayout>
        <Head title="Analytics Survei" />

        <div class="space-y-6">
            <!-- Header & Filters -->
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Analytics Survei
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Visualisasi dan tabulasi data survei
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <select
                        v-model="selectedPeriodId"
                        @change="applyFilters"
                        class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl"
                    >
                        <option value="">Pilih Periode</option>
                        <option v-for="p in periods" :key="p.id" :value="p.id">
                            {{ p.nama || "Periode " + p.id }} -
                            {{ p.tahun_akademik?.nama }}
                        </option>
                    </select>
                    <select
                        v-model="selectedDosenId"
                        @change="applyFilters"
                        class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl"
                    >
                        <option value="">Semua Dosen</option>
                        <option
                            v-for="d in dosenList"
                            :key="d.id"
                            :value="d.id"
                        >
                            {{ d.nama }}
                        </option>
                    </select>
                    <select
                        v-model="selectedKelasId"
                        @change="applyFilters"
                        class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl"
                    >
                        <option value="">Semua Matakuliah</option>
                        <option
                            v-for="k in kelasList"
                            :key="k.id"
                            :value="k.id"
                        >
                            {{ k.nama }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Charts Grid -->
            <div
                v-if="chartData.length > 0"
                class="grid grid-cols-1 lg:grid-cols-2 gap-6"
            >
                <div
                    v-for="(chart, idx) in chartData"
                    :key="idx"
                    class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6"
                >
                    <div class="mb-4">
                        <span
                            v-if="chart.kategori"
                            class="inline-block px-2 py-0.5 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-medium rounded-full mb-2"
                        >
                            {{ chart.kategori }}
                        </span>
                        <h3
                            class="font-medium text-gray-900 dark:text-white text-sm"
                        >
                            {{ chart.question }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Rata-rata:
                            <strong class="text-primary-600"
                                >{{ chart.avg }}/5</strong
                            >
                            · {{ chart.total }} responden
                        </p>
                    </div>

                    <!-- CSS Bar Chart -->
                    <div class="space-y-2">
                        <div
                            v-for="(item, i) in chart.distribution"
                            :key="i"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="w-28 text-xs text-gray-600 dark:text-gray-400 truncate text-right"
                            >
                                {{ item.label }}
                            </span>
                            <div
                                class="flex-1 h-6 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden relative"
                            >
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="getBarColor(item.nilai)"
                                    :style="{
                                        width: getBarWidth(
                                            item.count,
                                            chart.total
                                        ),
                                    }"
                                ></div>
                                <span
                                    class="absolute inset-0 flex items-center justify-center text-xs font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {{ item.count }} ({{
                                        getPercent(item.count, chart.total)
                                    }}%)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-12 text-center"
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
                    Pilih periode untuk melihat analytics
                </p>
            </div>

            <!-- Back to Dashboard -->
            <div class="flex justify-center">
                <Link
                    :href="route('survey.dashboard')"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition"
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
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                    Kembali ke Dashboard
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";

const props = defineProps({
    periods: Array,
    selectedPeriodId: Number,
    chartData: Array,
    dosenList: Array,
    kelasList: Array,
    filters: Object,
});

const selectedPeriodId = ref(props.selectedPeriodId);
const selectedDosenId = ref(props.filters?.dosen_id || "");
const selectedKelasId = ref(props.filters?.kelas_id || "");

const applyFilters = () => {
    const params = { period_id: selectedPeriodId.value };
    if (selectedDosenId.value) params.dosen_id = selectedDosenId.value;
    if (selectedKelasId.value) params.kelas_id = selectedKelasId.value;
    router.get(route("survey.analytics"), params, { preserveState: true });
};

const getBarWidth = (count, total) => {
    if (!total) return "0%";
    return `${Math.max((count / total) * 100, 2)}%`;
};

const getPercent = (count, total) => {
    if (!total) return 0;
    return Math.round((count / total) * 100);
};

const getBarColor = (nilai) => {
    if (nilai >= 4) return "bg-gradient-to-r from-green-400 to-green-500";
    if (nilai === 3) return "bg-gradient-to-r from-yellow-400 to-yellow-500";
    return "bg-gradient-to-r from-red-400 to-red-500";
};
</script>
