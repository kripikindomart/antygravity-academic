<template>
    <AppLayout>
        <Head title="Edit Periode Survei" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('survey.periods.index')"
                    class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl transition-all"
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
                        Edit Periode Survei
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        {{ period.nama || "Periode " + period.id }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info Card -->
                <div
                    class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden"
                >
                    <div
                        class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4"
                    >
                        <h2 class="text-lg font-semibold text-white">
                            Informasi Periode
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                                    >Nama Periode</label
                                >
                                <input
                                    v-model="form.nama"
                                    type="text"
                                    placeholder="Contoh: EDOM Semester Ganjil 2025/2026"
                                    class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                                    >Tahun Akademik</label
                                >
                                <select
                                    v-model="form.tahun_akademik_id"
                                    class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"
                                >
                                    <option
                                        v-for="ta in tahunAkademiks"
                                        :key="ta.id"
                                        :value="ta.id"
                                    >
                                        {{ ta.nama }} ({{ ta.semester }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                                    >Template Survei</label
                                >
                                <select
                                    v-model="form.survey_template_id"
                                    class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"
                                >
                                    <option
                                        v-for="t in templates"
                                        :key="t.id"
                                        :value="t.id"
                                    >
                                        {{ t.nama }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                                    >Tanggal Mulai</label
                                >
                                <input
                                    v-model="form.tanggal_mulai"
                                    type="date"
                                    class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                                    >Tanggal Selesai</label
                                >
                                <input
                                    v-model="form.tanggal_selesai"
                                    type="date"
                                    class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"
                                />
                            </div>
                        </div>
                        <div class="flex items-center gap-6 pt-2">
                            <div class="flex items-center gap-3">
                                <input
                                    v-model="form.status"
                                    type="radio"
                                    value="draft"
                                    id="status_draft"
                                    class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                                />
                                <label
                                    for="status_draft"
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Draft</label
                                >
                            </div>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model="form.status"
                                    type="radio"
                                    value="active"
                                    id="status_active"
                                    class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                                />
                                <label
                                    for="status_active"
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Aktif</label
                                >
                            </div>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model="form.status"
                                    type="radio"
                                    value="closed"
                                    id="status_closed"
                                    class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                                />
                                <label
                                    for="status_closed"
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Ditutup</label
                                >
                            </div>
                            <div class="flex items-center gap-3 ml-auto">
                                <input
                                    v-model="form.is_mandatory"
                                    type="checkbox"
                                    id="is_mandatory"
                                    class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                                <label
                                    for="is_mandatory"
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Wajib Isi</label
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Target Selection Card -->
                <div
                    class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden"
                >
                    <div
                        class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4 flex items-center justify-between"
                    >
                        <h2 class="text-lg font-semibold text-white">
                            Target Kelas & Dosen ({{
                                form.targets.length
                            }}
                            dipilih)
                        </h2>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="selectAll"
                                class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm font-medium transition-colors backdrop-blur-sm"
                            >
                                Pilih Semua
                            </button>
                            <button
                                type="button"
                                @click="deselectAll"
                                class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm font-medium transition-colors backdrop-blur-sm"
                            >
                                Hapus Semua
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div
                            class="max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl"
                        >
                            <table class="w-full text-left text-sm">
                                <thead
                                    class="bg-gray-50 dark:bg-gray-800 text-xs uppercase text-gray-600 dark:text-gray-400 sticky top-0"
                                >
                                    <tr>
                                        <th class="px-4 py-3 w-10">
                                            <input
                                                type="checkbox"
                                                :checked="isAllSelected"
                                                @change="toggleAll"
                                                class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                            />
                                        </th>
                                        <th class="px-4 py-3">Mata Kuliah</th>
                                        <th class="px-4 py-3">Kelas</th>
                                        <th class="px-4 py-3">Dosen</th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-100 dark:divide-gray-700"
                                >
                                    <template
                                        v-for="kelas in kelasMatakuliahs"
                                        :key="kelas.id"
                                    >
                                        <tr
                                            v-for="dosen in kelas.dosens"
                                            :key="`${kelas.id}-${dosen.id}`"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                        >
                                            <td class="px-4 py-3">
                                                <input
                                                    type="checkbox"
                                                    :checked="
                                                        isTargetSelected(
                                                            kelas.id,
                                                            dosen.id
                                                        )
                                                    "
                                                    @change="
                                                        toggleTarget(
                                                            kelas.id,
                                                            dosen.id
                                                        )
                                                    "
                                                    class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                />
                                            </td>
                                            <td
                                                class="px-4 py-3 font-medium text-gray-900 dark:text-white"
                                            >
                                                {{ kelas.mata_kuliah?.nama }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-gray-600 dark:text-gray-300"
                                            >
                                                {{ kelas.nama }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-gray-600 dark:text-gray-300"
                                            >
                                                {{ dosen.nama }}
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('survey.periods.index')"
                        class="px-6 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 font-medium transition-colors"
                        >Batal</Link
                    >
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white rounded-xl font-medium shadow-lg shadow-primary-500/30 transition-all disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? "Menyimpan..."
                                : "Simpan Perubahan"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";

const props = defineProps({
    period: Object,
    templates: Array,
    tahunAkademiks: Array,
    kelasMatakuliahs: Array,
});

const form = useForm({
    nama: props.period.nama || "",
    tahun_akademik_id: props.period.tahun_akademik_id,
    survey_template_id: props.period.survey_template_id,
    tanggal_mulai: props.period.tanggal_mulai,
    tanggal_selesai: props.period.tanggal_selesai,
    status: props.period.status,
    is_mandatory: props.period.is_mandatory,
    targets:
        props.period.targets?.map((t) => ({
            kelas_matakuliah_id: t.kelas_matakuliah_id,
            dosen_id: t.dosen_id,
        })) || [],
});

const allTargets = computed(() => {
    const targets = [];
    props.kelasMatakuliahs.forEach((k) => {
        k.dosens.forEach((d) => {
            targets.push({ kelas_matakuliah_id: k.id, dosen_id: d.id });
        });
    });
    return targets;
});

const isAllSelected = computed(
    () =>
        allTargets.value.length > 0 &&
        form.targets.length === allTargets.value.length
);

const isTargetSelected = (kelasId, dosenId) =>
    form.targets.some(
        (t) => t.kelas_matakuliah_id === kelasId && t.dosen_id === dosenId
    );

const toggleTarget = (kelasId, dosenId) => {
    const idx = form.targets.findIndex(
        (t) => t.kelas_matakuliah_id === kelasId && t.dosen_id === dosenId
    );
    if (idx === -1)
        form.targets.push({ kelas_matakuliah_id: kelasId, dosen_id: dosenId });
    else form.targets.splice(idx, 1);
};

const selectAll = () => {
    form.targets = [...allTargets.value];
};
const deselectAll = () => {
    form.targets = [];
};
const toggleAll = () => {
    isAllSelected.value ? deselectAll() : selectAll();
};

const submit = () => {
    form.put(route("survey.periods.update", props.period.id));
};
</script>
