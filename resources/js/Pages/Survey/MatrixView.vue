<template>
    <AppLayout>
        <Head :title="`Matrix - ${period.nama}`" />

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
                            Matrix Tabulasi
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ period.nama }} · Dosen × Pertanyaan
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
                        :href="route('survey.dashboard.chart', period.id)"
                        class="px-4 py-2 rounded-lg bg-green-50 hover:bg-green-100 text-green-600 font-medium text-sm"
                    >
                        Chart
                    </Link>
                    <button
                        @click="printMatrix"
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

            <!-- Matrix Table -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 sticky left-0 bg-gray-50 dark:bg-gray-800 z-10 min-w-[200px]"
                                >
                                    Dosen
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    N
                                </th>
                                <th
                                    v-for="q in questions"
                                    :key="q.id"
                                    class="px-3 py-3 text-center font-semibold text-gray-700 dark:text-gray-300 min-w-[100px]"
                                    :title="q.pertanyaan"
                                >
                                    <div class="text-xs leading-tight">
                                        <span
                                            v-if="q.kategori"
                                            class="block text-primary-600 dark:text-primary-400 mb-0.5"
                                            >{{ q.kategori }}</span
                                        >
                                        <span
                                            class="block truncate max-w-[100px]"
                                            >Q{{
                                                questions.indexOf(q) + 1
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300 bg-primary-50 dark:bg-primary-900/20"
                                >
                                    Avg
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    Kategori
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-800"
                        >
                            <tr
                                v-for="row in matrix"
                                :key="row.dosen"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50"
                            >
                                <td
                                    class="px-4 py-3 font-medium text-gray-900 dark:text-white sticky left-0 bg-white dark:bg-gray-900 z-10"
                                >
                                    {{ row.dosen }}
                                </td>
                                <td class="px-4 py-3 text-center text-gray-500">
                                    {{ row.total_responses }}
                                </td>
                                <td
                                    v-for="q in questions"
                                    :key="q.id"
                                    class="px-3 py-3 text-center"
                                >
                                    <span
                                        class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-bold"
                                        :class="getScoreColor(row.scores[q.id])"
                                    >
                                        {{ row.scores[q.id] || "-" }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 text-center bg-primary-50 dark:bg-primary-900/20"
                                >
                                    <span
                                        class="font-bold text-primary-600 dark:text-primary-400"
                                    >
                                        {{ getRowAvg(row.scores) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-block px-2 py-1 rounded-full text-xs font-medium"
                                        :class="
                                            getScoreColor(getRowAvg(row.scores))
                                        "
                                    >
                                        {{ getCategory(getRowAvg(row.scores)) }}
                                    </span>
                                </td>
                            </tr>
                            <!-- Summary Row -->
                            <tr
                                v-if="matrix.length > 0"
                                class="bg-gray-50 dark:bg-gray-800 font-semibold"
                            >
                                <td
                                    class="px-4 py-3 text-gray-700 dark:text-gray-300 sticky left-0 bg-gray-50 dark:bg-gray-800 z-10"
                                >
                                    Rata-rata
                                </td>
                                <td class="px-4 py-3 text-center text-gray-500">
                                    {{ getTotalN() }}
                                </td>
                                <td
                                    v-for="q in questions"
                                    :key="'sum-' + q.id"
                                    class="px-3 py-3 text-center"
                                >
                                    <span
                                        class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-bold"
                                        :class="
                                            getScoreColor(getColumnAvg(q.id))
                                        "
                                    >
                                        {{ getColumnAvg(q.id) }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 text-center bg-primary-100 dark:bg-primary-900/30"
                                >
                                    <span
                                        class="font-bold text-lg text-primary-700 dark:text-primary-400"
                                    >
                                        {{ getTotalAvg() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-block px-3 py-1.5 rounded-full text-sm font-bold"
                                        :class="getScoreColor(getTotalAvg())"
                                    >
                                        {{ getCategory(getTotalAvg()) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-if="matrix.length === 0"
                    class="p-12 text-center text-gray-500"
                >
                    Tidak ada data matrix
                </div>
            </div>

            <!-- Legend -->
            <div
                class="flex flex-wrap items-center justify-center gap-4 text-sm"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="w-6 h-6 rounded bg-green-100 dark:bg-green-900/30"
                    ></span>
                    <span class="text-gray-600 dark:text-gray-400"
                        >Sangat Baik (≥3.26)</span
                    >
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="w-6 h-6 rounded bg-blue-100 dark:bg-blue-900/30"
                    ></span>
                    <span class="text-gray-600 dark:text-gray-400"
                        >Baik (2.51-3.25)</span
                    >
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="w-6 h-6 rounded bg-yellow-100 dark:bg-yellow-900/30"
                    ></span>
                    <span class="text-gray-600 dark:text-gray-400"
                        >Cukup (1.76-2.50)</span
                    >
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="w-6 h-6 rounded bg-red-100 dark:bg-red-900/30"
                    ></span>
                    <span class="text-gray-600 dark:text-gray-400"
                        >Kurang (&lt;1.76)</span
                    >
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";

const getScoreColor = (score) => {
    if (!score || score === 0 || score === "-")
        return "bg-gray-100 text-gray-400";
    const s = parseFloat(score);
    if (s >= 3.26)
        return "bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400";
    if (s >= 2.51)
        return "bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400";
    if (s >= 1.76)
        return "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400";
    return "bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400";
};

const getCategory = (score) => {
    if (!score || score === 0 || score === "-") return "-";
    const s = parseFloat(score);
    if (s >= 3.26) return "Sangat Baik";
    if (s >= 2.51) return "Baik";
    if (s >= 1.76) return "Cukup";
    return "Kurang";
};

const getRowAvg = (scores) => {
    const values = Object.values(scores).filter((v) => v > 0);
    if (values.length === 0) return "-";
    const avg = values.reduce((a, b) => a + b, 0) / values.length;
    return avg.toFixed(2);
};

const props = defineProps({
    period: Object,
    matrix: Array,
    questions: Array,
    prodiList: Array,
    kelasList: Array,
    filters: Object,
});

const selectedProdi = ref(props.filters?.prodi_id || "");
const selectedKelas = ref(props.filters?.kelas_id || "");

const applyFilters = () => {
    router.get(
        route("survey.dashboard.matrix", props.period.id),
        {
            prodi_id: selectedProdi.value || undefined,
            kelas_id: selectedKelas.value || undefined,
        },
        { preserveState: true }
    );
};

const getTotalN = () => {
    return props.matrix.reduce((sum, row) => sum + row.total_responses, 0);
};

const getColumnAvg = (questionId) => {
    const values = props.matrix
        .map((row) => row.scores[questionId])
        .filter((v) => v > 0);
    if (values.length === 0) return "-";
    const avg = values.reduce((a, b) => a + b, 0) / values.length;
    return avg.toFixed(2);
};

const getTotalAvg = () => {
    let allValues = [];
    props.matrix.forEach((row) => {
        Object.values(row.scores).forEach((v) => {
            if (v > 0) allValues.push(v);
        });
    });
    if (allValues.length === 0) return "-";
    const avg = allValues.reduce((a, b) => a + b, 0) / allValues.length;
    return avg.toFixed(2);
};

const printMatrix = () => {
    window.print();
};
</script>
