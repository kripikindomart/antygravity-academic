<template>
    <AppLayout>
        <Head title="Edit Template Survei" />

        <div class="flex gap-6">
            <!-- Left: Form Builder -->
            <div class="flex-1 space-y-4">
                <!-- Header -->
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('survey.templates.index')"
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
                    <div class="flex-1">
                        <input
                            v-model="form.nama"
                            type="text"
                            placeholder="Nama Template Survei"
                            class="w-full text-2xl font-bold bg-transparent border-0 border-b-2 border-transparent hover:border-gray-300 focus:border-primary-500 focus:ring-0 text-gray-900 dark:text-white px-0 py-1 transition-colors"
                        />
                        <input
                            v-model="form.deskripsi"
                            type="text"
                            placeholder="Deskripsi (opsional)"
                            class="w-full text-sm bg-transparent border-0 text-gray-500 dark:text-gray-400 px-0 py-1 focus:ring-0"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="showPreview = !showPreview"
                            :class="[
                                'p-2.5 rounded-xl transition-all',
                                showPreview
                                    ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-600'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
                            ]"
                            title="Preview"
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
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                />
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg transition-all disabled:opacity-50"
                        >
                            {{ form.processing ? "Menyimpan..." : "Simpan" }}
                        </button>
                    </div>
                </div>

                <!-- Questions -->
                <div class="space-y-4">
                    <TransitionGroup name="list">
                        <div
                            v-for="(question, index) in form.questions"
                            :key="question._key"
                            :class="[
                                'bg-white dark:bg-gray-900 rounded-2xl shadow-sm border-2 transition-all cursor-move',
                                selectedQuestion === index
                                    ? 'border-primary-500 ring-2 ring-primary-500/20'
                                    : 'border-gray-100 dark:border-gray-800',
                            ]"
                            draggable="true"
                            @dragstart="dragStart(index)"
                            @dragover.prevent
                            @drop="drop(index)"
                            @click="selectedQuestion = index"
                        >
                            <div
                                class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3"
                            >
                                <div class="cursor-move text-gray-400">
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
                                            d="M4 8h16M4 16h16"
                                        />
                                    </svg>
                                </div>
                                <span
                                    class="w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 font-bold text-sm"
                                    >{{ index + 1 }}</span
                                >
                                <select
                                    v-model="question.tipe"
                                    @change="onTypeChange(question)"
                                    class="ml-auto px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-lg text-sm"
                                >
                                    <option value="scale">Skala 1-5</option>
                                    <option value="choice">
                                        Pilihan Ganda
                                    </option>
                                    <option value="text">Teks Bebas</option>
                                    <option value="dropdown_dynamic">
                                        Dropdown Dinamis
                                    </option>
                                </select>
                                <!-- Duplicate button -->
                                <button
                                    @click.stop="duplicateQuestion(index)"
                                    class="p-1.5 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                    title="Duplikat"
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
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        />
                                    </svg>
                                </button>
                                <!-- Add below button -->
                                <button
                                    @click.stop="addQuestionAfter(index)"
                                    class="p-1.5 text-gray-400 hover:text-green-500 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                    title="Tambah di bawah"
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
                                            d="M12 4v16m8-8H4"
                                        />
                                    </svg>
                                </button>
                                <button
                                    @click.stop="removeQuestion(index)"
                                    class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg"
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
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-4 space-y-3">
                                <div class="flex gap-3">
                                    <input
                                        v-model="question.kategori"
                                        type="text"
                                        placeholder="Kategori"
                                        class="w-32 px-3 py-2 bg-gray-50 dark:bg-gray-800 border-0 rounded-lg text-sm"
                                    />
                                    <textarea
                                        v-model="question.pertanyaan"
                                        rows="2"
                                        placeholder="Tulis pertanyaan..."
                                        class="flex-1 px-4 py-3 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl resize-none"
                                    ></textarea>
                                </div>

                                <div
                                    v-if="
                                        question.tipe === 'scale' ||
                                        question.tipe === 'choice'
                                    "
                                    class="pl-4 border-l-2 border-primary-200 space-y-2"
                                >
                                    <div
                                        v-for="(
                                            opt, oIndex
                                        ) in question.options"
                                        :key="oIndex"
                                        class="flex items-center gap-2"
                                    >
                                        <span
                                            class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center text-xs font-bold text-primary-600"
                                            >{{ opt.nilai }}</span
                                        >
                                        <input
                                            v-model="opt.label"
                                            type="text"
                                            placeholder="Label"
                                            class="flex-1 px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-lg text-sm"
                                        />
                                        <button
                                            v-if="question.options.length > 2"
                                            @click="removeOption(index, oIndex)"
                                            class="p-1 text-gray-400 hover:text-red-500 rounded"
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
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                    <button
                                        @click="addOption(index)"
                                        class="text-sm text-primary-600 font-medium"
                                    >
                                        + Tambah Opsi
                                    </button>
                                </div>

                                <div
                                    v-if="question.tipe === 'dropdown_dynamic'"
                                    class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl space-y-3"
                                >
                                    <p
                                        class="text-sm font-medium text-blue-700 dark:text-blue-400"
                                    >
                                        Pengaturan Dropdown Dinamis
                                    </p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label
                                                class="block text-xs text-gray-600 mb-1"
                                                >Sumber Data</label
                                            >
                                            <select
                                                v-model="question.data_source"
                                                class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm"
                                            >
                                                <option value="">
                                                    Pilih sumber
                                                </option>
                                                <option value="dosen">
                                                    Dosen
                                                </option>
                                                <option value="mahasiswa">
                                                    Mahasiswa
                                                </option>
                                                <option value="prodi">
                                                    Program Studi
                                                </option>
                                                <option value="kelas">
                                                    Kelas
                                                </option>
                                                <option value="mata_kuliah">
                                                    Mata Kuliah
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs text-gray-600 mb-1"
                                                >Filter Prodi</label
                                            >
                                            <select
                                                v-model="
                                                    question.data_filter
                                                        .prodi_id
                                                "
                                                class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm"
                                            >
                                                <option value="">
                                                    Semua Prodi
                                                </option>
                                                <option
                                                    v-for="p in prodis"
                                                    :key="p.id"
                                                    :value="p.id"
                                                >
                                                    {{ p.label }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div
                                        v-if="question.data_source === 'dosen'"
                                        class="flex items-center gap-2"
                                    >
                                        <input
                                            v-model="
                                                question.data_filter.jadwal_only
                                            "
                                            type="checkbox"
                                            class="w-4 h-4 rounded border-gray-300 text-primary-600"
                                        />
                                        <label
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                            >Hanya dosen di jadwal aktif</label
                                        >
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800"
                                >
                                    <span class="text-sm text-gray-500"
                                        >Wajib</span
                                    >
                                    <button
                                        @click="
                                            question.is_required =
                                                !question.is_required
                                        "
                                        :class="[
                                            'w-10 h-6 rounded-full',
                                            question.is_required
                                                ? 'bg-primary-600'
                                                : 'bg-gray-300',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'block w-4 h-4 bg-white rounded-full shadow',
                                                question.is_required
                                                    ? 'translate-x-5'
                                                    : 'translate-x-1',
                                            ]"
                                        ></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </TransitionGroup>
                </div>

                <div class="sticky bottom-4 flex justify-center">
                    <button
                        @click="addQuestion"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-900 border-2 border-dashed border-gray-300 hover:border-primary-500 text-gray-600 hover:text-primary-600 rounded-xl font-medium shadow-lg transition-all"
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
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Tambah Pertanyaan
                    </button>
                </div>
            </div>

            <!-- Preview -->
            <Transition name="slide-right">
                <div v-if="showPreview" class="w-96 flex-shrink-0">
                    <div
                        class="sticky top-4 bg-white dark:bg-gray-900 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden"
                    >
                        <div
                            class="bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-3"
                        >
                            <h3 class="text-white font-semibold">Preview</h3>
                        </div>
                        <div
                            class="p-4 max-h-[calc(100vh-200px)] overflow-y-auto space-y-4"
                        >
                            <div
                                class="text-center pb-4 border-b border-gray-100"
                            >
                                <h2
                                    class="text-lg font-bold text-gray-900 dark:text-white"
                                >
                                    {{ form.nama || "Nama Template" }}
                                </h2>
                                <p class="text-sm text-gray-500">
                                    {{ form.deskripsi }}
                                </p>
                            </div>
                            <div
                                v-for="(q, i) in form.questions"
                                :key="i"
                                class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl"
                            >
                                <p
                                    class="text-sm font-medium text-gray-900 dark:text-white"
                                >
                                    <span class="text-primary-600"
                                        >{{ i + 1 }}.</span
                                    >
                                    {{ q.pertanyaan || "..." }}
                                </p>
                                <div
                                    v-if="q.tipe === 'scale'"
                                    class="flex gap-1 mt-2"
                                >
                                    <div
                                        v-for="o in q.options"
                                        :key="o.nilai"
                                        class="flex-1 py-1 text-center text-xs bg-gray-200 dark:bg-gray-700 rounded"
                                    >
                                        {{ o.nilai }}
                                    </div>
                                </div>
                                <div
                                    v-else-if="q.tipe === 'dropdown_dynamic'"
                                    class="mt-2 px-3 py-2 bg-gray-200 dark:bg-gray-700 rounded text-xs"
                                >
                                    Dropdown: {{ q.data_source || "?" }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";
import axios from "axios";

const props = defineProps({ template: Object });

const defaultOptions = [
    { label: "Sangat Tidak Setuju", nilai: 1 },
    { label: "Tidak Setuju", nilai: 2 },
    { label: "Netral", nilai: 3 },
    { label: "Setuju", nilai: 4 },
    { label: "Sangat Setuju", nilai: 5 },
];

let keyCounter = 0;
const form = useForm({
    nama: props.template.nama,
    deskripsi: props.template.deskripsi || "",
    is_active: props.template.is_active,
    questions: props.template.questions.map((q) => ({
        _key: ++keyCounter,
        id: q.id,
        kategori: q.kategori || "",
        pertanyaan: q.pertanyaan,
        tipe: q.tipe,
        data_source: q.data_source || "",
        data_filter: q.data_filter || { jadwal_only: true, prodi_id: "" },
        is_required: q.is_required,
        options:
            q.options?.map((o) => ({
                id: o.id,
                label: o.label,
                nilai: o.nilai,
            })) || [],
    })),
});

const showPreview = ref(true);
const selectedQuestion = ref(null);
const draggedIndex = ref(null);
const prodis = ref([]);

onMounted(async () => {
    try {
        const res = await axios.get(route("survey.api.data", "prodi"));
        prodis.value = res.data.data;
    } catch (e) {}
});

const addQuestion = () => {
    form.questions.push({
        _key: ++keyCounter,
        kategori: "",
        pertanyaan: "",
        tipe: "scale",
        data_source: "",
        data_filter: { jadwal_only: true, prodi_id: "" },
        is_required: true,
        options: defaultOptions.map((o) => ({ ...o })),
    });
    selectedQuestion.value = form.questions.length - 1;
};

const addQuestionAfter = (index) => {
    form.questions.splice(index + 1, 0, {
        _key: ++keyCounter,
        kategori: "",
        pertanyaan: "",
        tipe: "scale",
        data_source: "",
        data_filter: { jadwal_only: true, prodi_id: "" },
        is_required: true,
        options: defaultOptions.map((o) => ({ ...o })),
    });
    selectedQuestion.value = index + 1;
};

const duplicateQuestion = (index) => {
    const original = form.questions[index];
    form.questions.splice(index + 1, 0, {
        _key: ++keyCounter,
        kategori: original.kategori,
        pertanyaan: original.pertanyaan + " (salinan)",
        tipe: original.tipe,
        data_source: original.data_source,
        data_filter: { ...original.data_filter },
        is_required: original.is_required,
        options: original.options.map((o) => ({ ...o })),
    });
    selectedQuestion.value = index + 1;
};

const removeQuestion = (i) => {
    form.questions.splice(i, 1);
};
const addOption = (i) => {
    form.questions[i].options.push({
        label: "",
        nilai: form.questions[i].options.length + 1,
    });
};
const removeOption = (qi, oi) => {
    form.questions[qi].options.splice(oi, 1);
};
const onTypeChange = (q) => {
    if (q.tipe === "dropdown_dynamic") {
        q.options = [];
        q.data_source = "";
    } else if (q.tipe !== "text" && (!q.options || !q.options.length)) {
        q.options = defaultOptions.map((o) => ({ ...o }));
    }
};
const dragStart = (i) => {
    draggedIndex.value = i;
};
const drop = (i) => {
    if (draggedIndex.value !== null && draggedIndex.value !== i) {
        const item = form.questions.splice(draggedIndex.value, 1)[0];
        form.questions.splice(i, 0, item);
    }
    draggedIndex.value = null;
};
const submit = () => {
    const data = {
        ...form.data(),
        questions: form.questions.map(({ _key, ...q }) => q),
    };
    form.transform(() => data).put(
        route("survey.templates.update", props.template.id)
    );
};
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}
.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.3s ease;
}
.slide-right-enter-from,
.slide-right-leave-to {
    opacity: 0;
    transform: translateX(20px);
}
</style>
