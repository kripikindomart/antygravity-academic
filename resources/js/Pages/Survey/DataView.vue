<template>
    <AppLayout>
        <Head :title="`Data - ${period.nama}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-4">
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
                            Data Responden
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ period.nama }} ·
                            {{ respondents.length }} responden
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('survey.dashboard.chart', period.id)"
                        class="px-4 py-2 rounded-lg bg-green-50 hover:bg-green-100 text-green-600 font-medium text-sm"
                    >
                        Chart
                    </Link>
                    <Link
                        :href="route('survey.dashboard.matrix', period.id)"
                        class="px-4 py-2 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-600 font-medium text-sm"
                    >
                        Matrix
                    </Link>
                </div>
            </div>

            <!-- Filters & Actions Bar -->
            <div
                class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4"
            >
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Filters -->
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

                    <div class="flex-1"></div>

                    <!-- Bulk Actions -->
                    <div
                        v-if="selectedIds.length > 0"
                        class="flex items-center gap-2"
                    >
                        <span class="text-sm text-gray-500"
                            >{{ selectedIds.length }} dipilih</span
                        >
                        <button
                            @click="bulkDelete"
                            class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium"
                        >
                            Hapus Terpilih
                        </button>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <input
                                        type="checkbox"
                                        :checked="allSelected"
                                        @change="toggleSelectAll"
                                        class="rounded border-gray-300 text-primary-600"
                                    />
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    #
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    Mahasiswa
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    NIM
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    Prodi
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    Dosen
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    Matakuliah
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    Waktu
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    Jawaban
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-800"
                        >
                            <tr
                                v-for="(resp, idx) in respondents"
                                :key="resp.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50"
                            >
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        :value="resp.id"
                                        v-model="selectedIds"
                                        class="rounded border-gray-300 text-primary-600"
                                    />
                                </td>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ idx + 1 }}
                                </td>
                                <td
                                    class="px-4 py-3 font-medium text-gray-900 dark:text-white"
                                >
                                    {{ resp.mahasiswa }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-400"
                                >
                                    {{ resp.nim }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-400"
                                >
                                    {{ resp.prodi }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-400"
                                >
                                    {{ resp.dosen }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-400"
                                >
                                    {{ resp.kelas }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ resp.submitted_at }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                        :class="
                                            resp.answer_count > 0
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700'
                                        "
                                    >
                                        {{ resp.answer_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div
                                        class="flex items-center justify-center gap-1"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'survey.response.show',
                                                    resp.id
                                                )
                                            "
                                            class="p-1.5 rounded hover:bg-blue-50 text-blue-600"
                                            title="Lihat Detail"
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
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="deleteOne(resp.id)"
                                            class="p-1.5 rounded hover:bg-red-50 text-red-600"
                                            title="Hapus"
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-if="respondents.length === 0"
                    class="p-12 text-center text-gray-500"
                >
                    Belum ada responden
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";

const props = defineProps({
    period: Object,
    respondents: Array,
    prodiList: Array,
    kelasList: Array,
    filters: Object,
});

const selectedProdi = ref(props.filters?.prodi_id || "");
const selectedKelas = ref(props.filters?.kelas_id || "");
const selectedIds = ref([]);

const allSelected = computed(() => {
    return (
        props.respondents.length > 0 &&
        selectedIds.value.length === props.respondents.length
    );
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedIds.value = props.respondents.map((r) => r.id);
    } else {
        selectedIds.value = [];
    }
};

const applyFilters = () => {
    router.get(
        route("survey.dashboard.data", props.period.id),
        {
            prodi_id: selectedProdi.value || undefined,
            kelas_id: selectedKelas.value || undefined,
        },
        { preserveState: true }
    );
};

const deleteOne = (id) => {
    if (confirm("Yakin ingin menghapus response ini?")) {
        router.delete(route("survey.response.destroy", id));
    }
};

const bulkDelete = () => {
    if (
        confirm(`Yakin ingin menghapus ${selectedIds.value.length} response?`)
    ) {
        router.post(route("survey.responses.bulk-destroy"), {
            ids: selectedIds.value,
        });
    }
};
</script>
