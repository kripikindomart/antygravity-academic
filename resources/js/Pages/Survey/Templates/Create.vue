<template>
    <AppLayout>
        <Head title="Buat Template Survei" />

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

                <!-- Questions List -->
                <div class="space-y-4">
                    <TransitionGroup name="list">
                        <div
                            v-for="(question, index) in form.questions"
                            :key="question._key"
                            :class="[
                                'bg-white dark:bg-gray-900 rounded-2xl shadow-sm border-2 transition-all',
                                selectedQuestion === index
                                    ? 'border-primary-500 ring-2 ring-primary-500/20'
                                    : 'border-gray-100 dark:border-gray-800 hover:border-gray-200',
                            ]"
                            draggable="true"
                            @dragstart="dragStart(index)"
                            @dragover.prevent
                            @drop="drop(index)"
                            @click="selectedQuestion = index"
                        >
                            <!-- Header -->
                            <div
                                class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3"
                            >
                                <div
                                    class="cursor-move text-gray-400 hover:text-gray-600"
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
                                            d="M4 8h16M4 16h16"
                                        />
                                    </svg>
                                </div>
                                <span
                                    class="w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-bold text-sm"
                                    >{{ index + 1 }}</span
                                >
                                <select
                                    v-model="question.tipe"
                                    @change="onTypeChange(question)"
                                    class="ml-auto px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-lg text-sm focus:ring-primary-500"
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
                                    class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                    title="Hapus"
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

                            <!-- Content -->
                            <div class="p-4 space-y-3">
                                <div class="flex gap-3">
                                    <input
                                        v-model="question.kategori"
                                        type="text"
                                        placeholder="Kategori"
                                        class="w-32 px-3 py-2 bg-gray-50 dark:bg-gray-800 border-0 rounded-lg text-sm focus:ring-primary-500"
                                    />
                                    <textarea
                                        v-model="question.pertanyaan"
                                        rows="2"
                                        placeholder="Tulis pertanyaan..."
                                        class="flex-1 px-4 py-3 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 resize-none"
                                    ></textarea>
                                </div>

                                <!-- Scale/Choice Options -->
                                <div
                                    v-if="
                                        question.tipe === 'scale' ||
                                        question.tipe === 'choice'
                                    "
                                    class="pl-4 border-l-2 border-primary-200 dark:border-primary-800 space-y-2"
                                >
                                    <div
                                        v-for="(
                                            opt, oIndex
                                        ) in question.options"
                                        :key="oIndex"
                                        class="flex items-center gap-2"
                                    >
                                        <span
                                            class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-xs font-bold text-primary-600"
                                            >{{ opt.nilai }}</span
                                        >
                                        <input
                                            v-model="opt.label"
                                            type="text"
                                            placeholder="Label opsi"
                                            class="flex-1 px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-lg text-sm focus:ring-primary-500"
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
                                        class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                                    >
                                        + Tambah Opsi
                                    </button>
                                </div>

                                <!-- Dynamic Dropdown Settings -->
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
                                                class="block text-xs text-gray-600 dark:text-gray-400 mb-1"
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
                                                class="block text-xs text-gray-600 dark:text-gray-400 mb-1"
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
                                            id="jadwal_only"
                                            class="w-4 h-4 rounded border-gray-300 text-primary-600"
                                        />
                                        <label
                                            for="jadwal_only"
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                            >Hanya dosen yang ada di jadwal
                                            aktif</label
                                        >
                                    </div>
                                </div>

                                <!-- Required toggle -->
                                <div
                                    class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800"
                                >
                                    <span class="text-sm text-gray-500"
                                        >Wajib diisi</span
                                    >
                                    <button
                                        @click="
                                            question.is_required =
                                                !question.is_required
                                        "
                                        :class="[
                                            'w-10 h-6 rounded-full transition-colors',
                                            question.is_required
                                                ? 'bg-primary-600'
                                                : 'bg-gray-300 dark:bg-gray-600',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'block w-4 h-4 bg-white rounded-full shadow transition-transform',
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

                <!-- Add Question Button -->
                <div class="sticky bottom-4 flex justify-center">
                    <button
                        @click="addQuestion"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-700 hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 text-gray-600 dark:text-gray-400 hover:text-primary-600 rounded-xl font-medium transition-all shadow-lg"
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

            <!-- Right: Live Preview -->
            <Transition name="slide-right">
                <div v-if="showPreview" class="w-96 flex-shrink-0">
                    <div
                        class="sticky top-4 bg-white dark:bg-gray-900 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden"
                    >
                        <div
                            class="bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-3"
                        >
                            <h3 class="text-white font-semibold">
                                Preview Survei
                            </h3>
                        </div>
                        <div
                            class="p-4 max-h-[calc(100vh-200px)] overflow-y-auto space-y-4"
                        >
                            <div
                                class="text-center pb-4 border-b border-gray-100 dark:border-gray-800"
                            >
                                <h2
                                    class="text-lg font-bold text-gray-900 dark:text-white"
                                >
                                    {{ form.nama || "Nama Template" }}
                                </h2>
                                <p class="text-sm text-gray-500">
                                    {{ form.deskripsi || "Deskripsi survei" }}
                                </p>
                            </div>

                            <div
                                v-for="(q, i) in form.questions"
                                :key="i"
                                class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl"
                            >
                                <div class="flex items-start gap-2 mb-2">
                                    <span
                                        class="text-xs font-bold text-primary-600"
                                        >{{ i + 1 }}.</span
                                    >
                                    <div>
                                        <p
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {{
                                                q.pertanyaan || "Pertanyaan..."
                                            }}
                                        </p>
                                        <span
                                            v-if="q.is_required"
                                            class="text-xs text-red-500"
                                            >*Wajib</span
                                        >
                                    </div>
                                </div>
                                <!-- Scale -->
                                <div
                                    v-if="q.tipe === 'scale'"
                                    class="flex gap-1 mt-2"
                                >
                                    <div
                                        v-for="opt in q.options"
                                        :key="opt.nilai"
                                        class="flex-1 py-1 text-center text-xs bg-gray-200 dark:bg-gray-700 rounded"
                                    >
                                        {{ opt.nilai }}
                                    </div>
                                </div>
                                <!-- Choice -->
                                <div
                                    v-else-if="q.tipe === 'choice'"
                                    class="space-y-1 mt-2"
                                >
                                    <div
                                        v-for="opt in q.options"
                                        :key="opt.nilai"
                                        class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400"
                                    >
                                        <span
                                            class="w-3 h-3 rounded-full border border-gray-400"
                                        ></span
                                        >{{ opt.label || "Opsi" }}
                                    </div>
                                </div>
                                <!-- Dropdown Dynamic -->
                                <div
                                    v-else-if="q.tipe === 'dropdown_dynamic'"
                                    class="mt-2"
                                >
                                    <div
                                        class="px-3 py-2 bg-gray-200 dark:bg-gray-700 rounded text-xs text-gray-500 flex items-center justify-between"
                                    >
                                        <span
                                            >Dropdown:
                                            {{
                                                dataSourceLabels[
                                                    q.data_source
                                                ] || "Pilih sumber"
                                            }}</span
                                        >
                                        <span
                                            v-if="q.data_filter?.jadwal_only"
                                            class="text-primary-600"
                                            >📅</span
                                        >
                                    </div>
                                </div>
                                <!-- Text -->
                                <div
                                    v-else
                                    class="mt-2 h-12 bg-gray-200 dark:bg-gray-700 rounded text-xs text-gray-400 flex items-center justify-center"
                                >
                                    Kotak teks
                                </div>
                            </div>

                            <div
                                v-if="form.questions.length === 0"
                                class="text-center py-8 text-gray-400 text-sm"
                            >
                                Belum ada pertanyaan
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

const defaultOptions = [
    { label: "Sangat Tidak Setuju", nilai: 1 },
    { label: "Tidak Setuju", nilai: 2 },
    { label: "Netral", nilai: 3 },
    { label: "Setuju", nilai: 4 },
    { label: "Sangat Setuju", nilai: 5 },
];

const dataSourceLabels = {
    dosen: "Dosen",
    mahasiswa: "Mahasiswa",
    prodi: "Program Studi",
    kelas: "Kelas",
    mata_kuliah: "Mata Kuliah",
};

const form = useForm({
    nama: "",
    deskripsi: "",
    is_active: true,
    questions: [],
});

const showPreview = ref(true);
const selectedQuestion = ref(null);
const draggedIndex = ref(null);
const prodis = ref([]);
let keyCounter = 0;

onMounted(async () => {
    try {
        const res = await axios.get(route("survey.api.data", "prodi"));
        prodis.value = res.data.data;
    } catch (e) {
        console.error(e);
    }
});

const createNewQuestion = () => ({
    _key: ++keyCounter,
    kategori: "",
    pertanyaan: "",
    tipe: "scale",
    data_source: "",
    data_filter: { jadwal_only: true, prodi_id: "" },
    is_required: true,
    options: defaultOptions.map((o) => ({ ...o })),
});

const addQuestion = () => {
    form.questions.push(createNewQuestion());
    selectedQuestion.value = form.questions.length - 1;
};

const addQuestionAfter = (index) => {
    form.questions.splice(index + 1, 0, createNewQuestion());
    selectedQuestion.value = index + 1;
};

const duplicateQuestion = (index) => {
    const original = form.questions[index];
    const duplicate = {
        _key: ++keyCounter,
        kategori: original.kategori,
        pertanyaan: original.pertanyaan + " (salinan)",
        tipe: original.tipe,
        data_source: original.data_source,
        data_filter: { ...original.data_filter },
        is_required: original.is_required,
        options: original.options.map((o) => ({ ...o })),
    };
    form.questions.splice(index + 1, 0, duplicate);
    selectedQuestion.value = index + 1;
};

const removeQuestion = (index) => {
    form.questions.splice(index, 1);
    if (selectedQuestion.value >= form.questions.length)
        selectedQuestion.value = form.questions.length - 1;
};

const addOption = (qIndex) => {
    const nextVal = form.questions[qIndex].options.length + 1;
    form.questions[qIndex].options.push({ label: "", nilai: nextVal });
};

const removeOption = (qIndex, oIndex) => {
    form.questions[qIndex].options.splice(oIndex, 1);
};

const onTypeChange = (question) => {
    if (question.tipe === "dropdown_dynamic") {
        question.options = [];
        question.data_source = "";
        question.data_filter = { jadwal_only: true, prodi_id: "" };
    } else if (
        question.tipe !== "text" &&
        (!question.options || question.options.length === 0)
    ) {
        question.options = defaultOptions.map((o) => ({ ...o }));
    }
};

const dragStart = (index) => {
    draggedIndex.value = index;
};
const drop = (index) => {
    if (draggedIndex.value !== null && draggedIndex.value !== index) {
        const item = form.questions.splice(draggedIndex.value, 1)[0];
        form.questions.splice(index, 0, item);
    }
    draggedIndex.value = null;
};

const submit = () => {
    const data = {
        ...form.data(),
        questions: form.questions.map(({ _key, ...q }) => q),
    };
    form.transform(() => data).post(route("survey.templates.store"));
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
.list-move {
    transition: transform 0.3s ease;
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
