<template>
    <AppLayout>
        <Head :title="'Survei - ' + (target.dosen?.nama || 'Dosen')" />

        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('survey.responses.index')"
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
                        Survei Evaluasi Dosen
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        {{ target.kelas_matakuliah?.mata_kuliah?.nama }} -
                        {{ target.dosen?.nama }}
                    </p>
                </div>
            </div>

            <!-- Progress -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4"
            >
                <div class="flex items-center justify-between mb-2">
                    <span
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Progres Pengisian</span
                    >
                    <span class="text-sm font-bold text-primary-600"
                        >{{ answeredCount }}/{{ questions.length }}</span
                    >
                </div>
                <div
                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden"
                >
                    <div
                        class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full transition-all duration-300"
                        :style="{ width: `${progress}%` }"
                    ></div>
                </div>
            </div>

            <!-- Questions -->
            <form @submit.prevent="submit" class="space-y-4">
                <div
                    v-for="(question, index) in questions"
                    :key="question.id"
                    class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden"
                >
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-start gap-4"
                    >
                        <div
                            class="w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-bold text-sm flex-shrink-0"
                        >
                            {{ index + 1 }}
                        </div>
                        <div class="flex-1">
                            <span
                                v-if="question.kategori"
                                class="inline-block px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-medium rounded-full mb-2"
                                >{{ question.kategori }}</span
                            >
                            <p
                                class="font-medium text-gray-900 dark:text-white"
                            >
                                {{ question.pertanyaan }}
                            </p>
                            <span
                                v-if="question.is_required"
                                class="text-xs text-red-500 mt-1"
                                >* Wajib diisi</span
                            >
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <!-- Scale -->
                        <div
                            v-if="question.tipe === 'scale'"
                            class="flex flex-wrap gap-2"
                        >
                            <button
                                v-for="opt in question.options"
                                :key="opt.id"
                                type="button"
                                @click="form.answers[question.id] = opt.id"
                                :class="[
                                    'flex-1 min-w-[80px] py-3 px-4 rounded-xl border-2 text-center transition-all',
                                    form.answers[question.id] === opt.id
                                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700'
                                        : 'border-gray-200 dark:border-gray-700 text-gray-600',
                                ]"
                            >
                                <span class="text-lg font-bold block">{{
                                    opt.nilai
                                }}</span>
                                <span class="text-xs">{{ opt.label }}</span>
                            </button>
                        </div>
                        <!-- Choice -->
                        <div
                            v-else-if="question.tipe === 'choice'"
                            class="space-y-2"
                        >
                            <label
                                v-for="opt in question.options"
                                :key="opt.id"
                                class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                                :class="
                                    form.answers[question.id] === opt.id
                                        ? 'border-primary-500 bg-primary-50'
                                        : 'border-gray-200'
                                "
                            >
                                <input
                                    type="radio"
                                    :name="'q_' + question.id"
                                    :value="opt.id"
                                    v-model="form.answers[question.id]"
                                    class="w-4 h-4 text-primary-600"
                                />
                                <span
                                    class="text-gray-700 dark:text-gray-300"
                                    >{{ opt.label }}</span
                                >
                            </label>
                        </div>
                        <!-- Dynamic Dropdown -->
                        <div
                            v-else-if="question.tipe === 'dropdown_dynamic'"
                            class="space-y-2"
                        >
                            <div
                                v-if="loadingOptions[question.id]"
                                class="py-4 text-center text-gray-500 text-sm"
                            >
                                Memuat data...
                            </div>
                            <select
                                v-else
                                v-model="form.answers[question.id]"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
                            >
                                <option value="">
                                    Pilih
                                    {{
                                        dataSourceLabels[
                                            question.data_source
                                        ] || "opsi"
                                    }}
                                </option>
                                <option
                                    v-for="opt in dynamicOptions[question.id]"
                                    :key="opt.id"
                                    :value="opt.id"
                                >
                                    {{ opt.label }}
                                    <span v-if="opt.info"
                                        >({{ opt.info }})</span
                                    >
                                </option>
                            </select>
                        </div>
                        <!-- Text -->
                        <div v-else-if="question.tipe === 'text'">
                            <textarea
                                v-model="form.answers[question.id]"
                                rows="3"
                                placeholder="Tulis jawaban Anda..."
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-between pt-4">
                    <Link
                        :href="route('survey.responses.index')"
                        class="px-6 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 rounded-xl font-medium"
                        >Kembali</Link
                    >
                    <button
                        type="submit"
                        :disabled="form.processing || !isComplete"
                        :class="[
                            'px-8 py-2.5 rounded-xl font-medium shadow-lg transition-all',
                            isComplete
                                ? 'bg-gradient-to-r from-green-600 to-green-700 text-white'
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
                        ]"
                    >
                        {{ form.processing ? "Mengirim..." : "Kirim Survei" }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";
import axios from "axios";

const props = defineProps({
    target: Object,
    questions: Array,
});

const dataSourceLabels = {
    dosen: "Dosen",
    mahasiswa: "Mahasiswa",
    prodi: "Program Studi",
    kelas: "Kelas",
    mata_kuliah: "Mata Kuliah",
};

const form = useForm({ answers: {} });
const dynamicOptions = ref({});
const loadingOptions = ref({});

onMounted(async () => {
    // Load dynamic options for dropdown_dynamic questions
    for (const q of props.questions) {
        if (q.tipe === "dropdown_dynamic" && q.data_source) {
            loadingOptions.value[q.id] = true;
            try {
                const params = new URLSearchParams();
                if (q.data_filter?.jadwal_only)
                    params.append("jadwal_only", "1");
                if (q.data_filter?.prodi_id)
                    params.append("prodi_id", q.data_filter.prodi_id);
                const res = await axios.get(
                    route("survey.api.data", q.data_source) +
                        "?" +
                        params.toString()
                );
                dynamicOptions.value[q.id] = res.data.data;
            } catch (e) {
                console.error("Failed to load dynamic options", e);
                dynamicOptions.value[q.id] = [];
            }
            loadingOptions.value[q.id] = false;
        }
    }
});

const answeredCount = computed(() => {
    return props.questions.filter((q) => {
        const ans = form.answers[q.id];
        return ans !== undefined && ans !== null && ans !== "";
    }).length;
});

const progress = computed(() => {
    if (!props.questions.length) return 0;
    return Math.round((answeredCount.value / props.questions.length) * 100);
});

const isComplete = computed(() => {
    return props.questions
        .filter((q) => q.is_required)
        .every((q) => {
            const ans = form.answers[q.id];
            return ans !== undefined && ans !== null && ans !== "";
        });
});

const submit = () => {
    form.post(route("survey.responses.store", props.target.id));
};
</script>
