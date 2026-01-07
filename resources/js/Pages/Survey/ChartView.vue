<template>
    <AppLayout>
        <Head :title="`Chart - ${period.nama}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('survey.dashboard')"
                        class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 transition"
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
                    </Link>
                    <div>
                        <h1
                            class="text-2xl font-bold text-gray-900 dark:text-white"
                        >
                            Chart Visualisasi
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ period.nama }} · {{ totalResponses }} responden
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('survey.dashboard.data', period.id)"
                        class="px-4 py-2 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 font-medium text-sm"
                    >
                        Data
                    </Link>
                    <Link
                        :href="route('survey.dashboard.matrix', period.id)"
                        class="px-4 py-2 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-600 font-medium text-sm"
                    >
                        Matrix
                    </Link>
                    <button
                        @click="printChart"
                        class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm flex items-center gap-1.5"
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
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                        Download
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4"
            >
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600 dark:text-gray-400"
                            >Prodi:</label
                        >
                        <select
                            v-model="selectedProdi"
                            @change="applyFilters"
                            class="text-sm rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800"
                        >
                            <option value="">Semua</option>
                            <option
                                v-for="p in prodiList"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.nama }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600 dark:text-gray-400"
                            >Kelas:</label
                        >
                        <select
                            v-model="selectedKelas"
                            @change="applyFilters"
                            class="text-sm rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800"
                        >
                            <option value="">Semua</option>
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
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                            class="font-medium text-gray-900 dark:text-white text-sm leading-tight"
                        >
                            {{ chart.question }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Rata-rata:
                            <strong class="text-primary-600"
                                >{{ chart.avg }}/5</strong
                            >
                            · {{ chart.total }} jawaban
                        </p>
                    </div>

                    <!-- Charts Container -->
                    <div class="flex gap-6">
                        <!-- Bar Chart -->
                        <div class="flex-1 space-y-2">
                            <div
                                v-for="(item, i) in chart.distribution"
                                :key="i"
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="w-24 text-xs text-gray-600 dark:text-gray-400 truncate text-right shrink-0"
                                >
                                    {{ item.label }}
                                </span>
                                <div
                                    class="flex-1 h-6 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden relative"
                                >
                                    <div
                                        class="h-full rounded transition-all duration-500"
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
                                        {{ item.count }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Pie Chart -->
                        <div class="shrink-0 flex flex-col items-center">
                            <div
                                class="w-24 h-24 rounded-full"
                                :style="{
                                    background: getPieGradient(
                                        chart.distribution,
                                        chart.total
                                    ),
                                }"
                            ></div>
                            <div class="mt-2 text-center">
                                <span
                                    class="text-lg font-bold text-primary-600"
                                    >{{ chart.avg }}</span
                                >
                                <span class="text-xs text-gray-500">/5</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="chartData.length === 0"
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
                    Tidak ada data chart
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";

const props = defineProps({
    period: Object,
    chartData: Array,
    totalResponses: Number,
    prodiList: Array,
    kelasList: Array,
    filters: Object,
});

const selectedProdi = ref(props.filters?.prodi_id || "");
const selectedKelas = ref(props.filters?.kelas_id || "");

const applyFilters = () => {
    router.get(
        route("survey.dashboard.chart", props.period.id),
        {
            prodi_id: selectedProdi.value || undefined,
            kelas_id: selectedKelas.value || undefined,
        },
        { preserveState: true }
    );
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

const getPieGradient = (distribution, total) => {
    if (!total || !distribution) return "#e5e7eb";

    const colors = ["#ef4444", "#f97316", "#facc15", "#4ade80", "#22c55e"];
    let cumulative = 0;
    const segments = [];

    distribution.forEach((item, idx) => {
        const percent = (item.count / total) * 100;
        const color = colors[idx] || "#9ca3af";
        segments.push(`${color} ${cumulative}% ${cumulative + percent}%`);
        cumulative += percent;
    });

    return `conic-gradient(${segments.join(", ")})`;
};

const printChart = () => {
    // Create print-friendly version
    const printWindow = window.open("", "_blank");
    const chartContent = document.querySelector(".grid.grid-cols-1").innerHTML;

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Chart - ${props.period.nama}</title>
            <style>
                body { font-family: system-ui, sans-serif; padding: 20px; }
                .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
                .bg-white { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; }
                .text-primary-600 { color: #0066cc; }
                .font-medium { font-weight: 500; }
                .font-bold { font-weight: 700; }
                .text-sm { font-size: 14px; }
                .text-xs { font-size: 12px; }
                .flex { display: flex; }
                .flex-1 { flex: 1; }
                .items-center { align-items: center; }
                .gap-2 { gap: 8px; }
                .gap-6 { gap: 24px; }
                .mb-4 { margin-bottom: 16px; }
                .mt-2 { margin-top: 8px; }
                .space-y-2 > * + * { margin-top: 8px; }
                .h-6 { height: 24px; }
                .rounded { border-radius: 4px; }
                .rounded-full { border-radius: 9999px; }
                .w-24 { width: 96px; }
                .h-24 { height: 96px; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .shrink-0 { flex-shrink: 0; }
                .flex-col { flex-direction: column; }
                .bg-gray-100 { background: #f3f4f6; }
                .from-green-400 { background: linear-gradient(to right, #4ade80, #22c55e); }
                .from-yellow-400 { background: linear-gradient(to right, #facc15, #eab308); }
                .from-red-400 { background: linear-gradient(to right, #f87171, #ef4444); }
                @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
            </style>
        </head>
        <body>
            <h1 style="margin-bottom: 20px;">Chart Survei: ${props.period.nama}</h1>
            <p style="color: #666; margin-bottom: 20px;">${props.totalResponses} responden</p>
            <div class="grid">${chartContent}</div>
            <script>window.print(); window.close();<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
};
</script>
