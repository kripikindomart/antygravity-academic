<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { usePage, router, Head } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
    period: Object,
    questions: Array,
    programStudis: Array,
    isLoggedIn: Boolean,
    mahasiswa: Object,
});

const page = usePage();
const successMessage = ref(page.props.flash?.success);
const errorMessage = ref(page.props.flash?.error);

// Form & Selection State
const form = ref({
    prodi_id: "",
    kelas_id: "",
    kelas_matakuliah_id: "",
    mahasiswa_id: props.isLoggedIn ? props.mahasiswa?.id : "",
    answers: {}, // Structure: { dosen_id: { question_id: value } }
});

// Data Lists
const kelasList = ref([]);
const matakuliahList = ref([]);
const mahasiswaList = ref([]);
const dosenList = ref([]);

// Loading States
const loadingKelasList = ref(false);
const loadingMatakuliah = ref(false);
const loadingMahasiswa = ref(false);
const loadingDosen = ref(false);
const submitting = ref(false);

// Active Tab State (Dosen ID)
const activeDosenId = ref(null);

// Searchable Mahasiswa Logic
const mhsSearch = ref("");
const showMhsDropdown = ref(false);
const selectedMhs = ref(null);

const filteredMahasiswa = computed(() => {
    if (!mhsSearch.value) return mahasiswaList.value;
    const search = mhsSearch.value.toLowerCase();
    return mahasiswaList.value.filter(
        (m) =>
            m.nama.toLowerCase().includes(search) ||
            m.nim.toLowerCase().includes(search)
    );
});

const selectMhs = (item) => {
    selectedMhs.value = item;
    form.value.mahasiswa_id = item.id;
    mhsSearch.value = `${item.nama} (${item.nim})`;
    showMhsDropdown.value = false;
    loadDosenAndStatus();
};

const clearMhsSelection = () => {
    selectedMhs.value = null;
    form.value.mahasiswa_id = "";
    mhsSearch.value = "";
    dosenList.value = []; // Reset dosen list as it depends on mahasiswa
};

// Get current active Dosen object
const activeDosen = computed(() => {
    return dosenList.value.find((d) => d.id === activeDosenId.value);
});

// Navigation Helpers
const currentIndex = computed(() =>
    dosenList.value.findIndex((d) => d.id === activeDosenId.value)
);
const hasNextDosen = computed(
    () => currentIndex.value < dosenList.value.length - 1
);
const hasPrevDosen = computed(() => currentIndex.value > 0);

const nextDosen = () => {
    if (hasNextDosen.value) {
        activeDosenId.value = dosenList.value[currentIndex.value + 1].id;
        window.scrollTo({ top: 300, behavior: "smooth" });
    }
};

const prevDosen = () => {
    if (hasPrevDosen.value) {
        activeDosenId.value = dosenList.value[currentIndex.value - 1].id;
        window.scrollTo({ top: 300, behavior: "smooth" });
    }
};

// Selection Handlers

// 1. Prodi -> Get Kelas
const onProdiChange = async () => {
    // Reset downward
    form.value.kelas_id = "";
    form.value.kelas_matakuliah_id = "";
    form.value.dosen_id = ""; // Not used but keep for clean
    if (!props.isLoggedIn) form.value.mahasiswa_id = "";

    // Reset Searchable States
    mhsSearch.value = "";
    selectedMhs.value = null;
    showMhsDropdown.value = false;

    kelasList.value = [];
    matakuliahList.value = [];
    mahasiswaList.value = [];
    dosenList.value = [];

    if (!form.value.prodi_id) return;

    loadingKelasList.value = true;
    try {
        const res = await axios.get(`/survey/s/${props.period.slug}/kelas`, {
            params: { prodi_id: form.value.prodi_id },
        });
        kelasList.value = res.data.data;
    } catch (e) {
        console.error(e);
    }
    loadingKelasList.value = false;
};

// 2. Kelas -> Get Matakuliah & Mahasiswa
const onKelasChange = async () => {
    form.value.kelas_matakuliah_id = "";
    if (!props.isLoggedIn) form.value.mahasiswa_id = "";

    // Reset Searchable States
    mhsSearch.value = "";
    selectedMhs.value = null;
    showMhsDropdown.value = false;

    matakuliahList.value = [];
    mahasiswaList.value = [];
    dosenList.value = [];

    if (!form.value.kelas_id) return;

    loadingMatakuliah.value = true;
    loadingMahasiswa.value = true;

    try {
        const [mkRes, mhsRes] = await Promise.all([
            axios.get(`/survey/s/${props.period.slug}/matakuliah-by-kelas`, {
                params: { kelas_id: form.value.kelas_id },
            }),
            axios.get(`/survey/s/${props.period.slug}/mahasiswa`, {
                params: { kelas_id: form.value.kelas_id },
            }),
        ]);

        matakuliahList.value = mkRes.data.data;
        mahasiswaList.value = mhsRes.data.data;
    } catch (e) {
        console.error(e);
    }

    loadingMatakuliah.value = false;
    loadingMahasiswa.value = false;
};

// 3. Matakuliah + Mahasiswa -> Get Dosen & Status
const loadDosenAndStatus = async () => {
    dosenList.value = [];
    form.value.answers = {}; // Reset answers

    if (!form.value.kelas_matakuliah_id || !form.value.mahasiswa_id) return;

    loadingDosen.value = true;
    try {
        const res = await axios.get(`/survey/s/${props.period.slug}/dosen`, {
            params: {
                kelas_matakuliah_id: form.value.kelas_matakuliah_id,
                mahasiswa_id: form.value.mahasiswa_id,
            },
        });
        dosenList.value = res.data.data;

        // Auto select first dosen
        if (dosenList.value.length > 0) {
            activeDosenId.value = dosenList.value[0].id;

            // Initialize answer objects for all dosens
            dosenList.value.forEach((d) => {
                if (!form.value.answers[d.id]) {
                    form.value.answers[d.id] = {};
                }
            });
        }
    } catch (e) {
        console.error(e);
    }
    loadingDosen.value = false;
};

// Trigger loadDosen when MK or Mhs changes
const onMkChange = () => {
    loadDosenAndStatus();
};
const onMahasiswaChange = () => {
    loadDosenAndStatus();
};

// Answer Handling
const getAnswer = (dosenId, questionId) => {
    return form.value.answers[dosenId]?.[questionId] || "";
};

const setAnswer = (dosenId, questionId, value) => {
    if (!form.value.answers[dosenId]) form.value.answers[dosenId] = {};
    form.value.answers[dosenId][questionId] = value;
};

// Check if a specific dosen's survey is completed
const isDosenCompleted = (dosenId) => {
    // If already filled in database, it's complete
    const dosen = dosenList.value.find((d) => d.id === dosenId);
    if (dosen?.is_filled) return true;

    // Check form answers locally
    const answers = form.value.answers[dosenId] || {};
    return props.questions
        .filter((q) => q.is_required)
        .every((q) => {
            const val = answers[q.id];
            return val !== undefined && val !== null && val !== "";
        });
};

// Status Text Helper
const getCompletionStatus = (dosenId) => {
    const dosen = dosenList.value.find((d) => d.id === dosenId);
    if (dosen?.is_filled) return "✅ Sudah Diisi Sebelumnya";
    if (isDosenCompleted(dosenId)) return "✨ Siap Dikirim";
    return "📝 Belum Lengkap";
};

// Count how many new surveys are ready to submit
const completedDosenCount = computed(() => {
    return dosenList.value.filter((d) => !d.is_filled && isDosenCompleted(d.id))
        .length;
});

const totalProgressCount = computed(() => {
    return dosenList.value.filter((d) => isDosenCompleted(d.id)).length;
});

const readyToFill = computed(() => {
    return form.value.kelas_matakuliah_id && form.value.mahasiswa_id;
});

const validDosenList = computed(() => {
    // Filter dosens that are not filled yet OR are filled (to show status)
    return dosenList.value;
});

// Submit
const submitAll = async (e) => {
    if (e) e.preventDefault();
    if (completedDosenCount.value === 0) return;

    submitting.value = true;

    // Build payload: Only send data for completed and NOT already filled dosens
    const responses = dosenList.value
        .filter((d) => !d.is_filled && isDosenCompleted(d.id))
        .map((d) => ({
            dosen_id: d.id,
            answers: form.value.answers[d.id],
        }));

    try {
        const res = await axios.post(`/survey/s/${props.period.slug}`, {
            mahasiswa_id: form.value.mahasiswa_id,
            kelas_matakuliah_id: form.value.kelas_matakuliah_id,
            responses: responses,
        });

        if (res.status === 200 || res.status === 201) {
            successMessage.value = "Survei berhasil dikirim!";
            setTimeout(() => (successMessage.value = ""), 5000);

            // Reload data to update status without full page reload
            await loadDosenAndStatus();
        }
    } catch (e) {
        console.error(e);
        errorMessage.value =
            e.response?.data?.message || "Gagal mengirim survei.";
        setTimeout(() => (errorMessage.value = ""), 5000);
    }
    submitting.value = false;
};

onMounted(() => {
    // If logged in, set initial data
    if (props.isLoggedIn && props.mahasiswa) {
        form.value.mahasiswa_id = props.mahasiswa.id;
        form.value.prodi_id = props.mahasiswa.program_studi_id;
        onProdiChange();
    }
});
</script>

<template>
    <Head :title="period.nama" />

    <div
        class="min-h-screen bg-gray-50 dark:bg-gray-900 font-sans text-gray-900 dark:text-gray-100 transition-colors duration-300"
    >
        <!-- Background Pattern -->
        <div class="fixed inset-0 z-0 opacity-10 pointer-events-none">
            <div
                class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20"
            ></div>
            <div
                class="absolute top-0 right-0 w-[500px] h-[500px] bg-primary-400 rounded-full mix-blend-multiply filter blur-[128px] animate-blob"
            ></div>
            <div
                class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-blue-400 rounded-full mix-blend-multiply filter blur-[128px] animate-blob animation-delay-2000"
            ></div>
        </div>

        <div class="relative z-10 max-w-4xl mx-auto px-4 py-8 md:py-12 pb-64">
            <!-- Header -->
            <div class="text-center mb-10 animate-fade-in-down">
                <div
                    class="inline-block p-3 rounded-2xl bg-gradient-to-br from-primary-500 to-blue-600 text-white shadow-lg mb-6"
                >
                    <svg
                        class="w-8 h-8"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        ></path>
                    </svg>
                </div>
                <h1
                    class="text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-white mb-2 tracking-tight"
                >
                    {{ period.nama }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-lg">
                    Semester {{ period.semester }}
                </p>
            </div>

            <!-- Messages -->
            <div
                v-if="successMessage"
                class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-xl border border-green-200 dark:border-green-800 flex items-center shadow-sm animate-fade-in"
            >
                <svg
                    class="w-6 h-6 mr-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
                <div class="font-medium">{{ successMessage }}</div>
            </div>

            <div
                v-if="errorMessage"
                class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-xl border border-red-200 dark:border-red-800 flex items-center shadow-sm animate-fade-in"
            >
                <svg
                    class="w-6 h-6 mr-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <div class="font-medium">{{ errorMessage }}</div>
            </div>

            <!-- Step 1: Identifikasi & Seleksi -->
            <div class="space-y-6 mb-8">
                <div
                    class="bg-white dark:bg-gray-800 p-6 md:p-8 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700"
                >
                    <h3
                        class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center"
                    >
                        <span
                            class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-300 text-sm mr-3"
                            >1</span
                        >
                        Identifikasi Pengisi
                    </h3>

                    <!-- Logged In User Info -->
                    <div
                        v-if="isLoggedIn && mahasiswa"
                        class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-600"
                    >
                        <div>
                            <label
                                class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1"
                                >Nama & NIM</label
                            >
                            <div
                                class="font-bold text-gray-900 dark:text-white"
                            >
                                {{ mahasiswa.nama }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ mahasiswa.nim }}
                            </div>
                        </div>
                        <div>
                            <label
                                class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1"
                                >Program Studi</label
                            >
                            <div
                                class="font-bold text-gray-900 dark:text-white"
                            >
                                {{ mahasiswa.program_studi?.nama }}
                            </div>
                        </div>
                    </div>

                    <!-- Guest Selection -->
                    <div
                        v-if="!isLoggedIn"
                        class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6"
                    >
                        <!-- Prodi -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >Program Studi</label
                            >
                            <select
                                v-model="form.prodi_id"
                                @change="onProdiChange"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500"
                            >
                                <option value="">Pilih Program Studi</option>
                                <option
                                    v-for="p in programStudis"
                                    :key="p.id"
                                    :value="p.id"
                                >
                                    {{ p.nama }}
                                </option>
                            </select>
                        </div>
                        <!-- Kelas -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >Kelas</label
                            >
                            <select
                                v-model="form.kelas_id"
                                @change="onKelasChange"
                                :disabled="!form.prodi_id || loadingKelasList"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 disabled:opacity-50"
                            >
                                <option value="">
                                    {{
                                        loadingKelasList
                                            ? "Memuat..."
                                            : "Pilih Kelas"
                                    }}
                                </option>
                                <option
                                    v-for="k in kelasList"
                                    :key="k.id"
                                    :value="k.id"
                                >
                                    {{ k.nama }} ({{ k.semester }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Common Selection (Guest & Auth) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Mata Kuliah -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >Mata Kuliah</label
                            >
                            <select
                                v-model="form.kelas_matakuliah_id"
                                @change="onMkChange"
                                :disabled="
                                    (!isLoggedIn && !form.kelas_id) ||
                                    loadingMatakuliah
                                "
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 disabled:opacity-50"
                            >
                                <option value="">
                                    {{
                                        loadingMatakuliah
                                            ? "Memuat..."
                                            : "Pilih Mata Kuliah"
                                    }}
                                </option>
                                <option
                                    v-for="mk in matakuliahList"
                                    :key="mk.id"
                                    :value="mk.id"
                                >
                                    {{ mk.nama }} - {{ mk.kode }}
                                </option>
                            </select>
                        </div>

                        <!-- Mahasiswa (Guest Only) SEARCHABLE -->
                        <div v-if="!isLoggedIn" class="relative">
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >Nama Anda</label
                            >

                            <div class="relative">
                                <input
                                    type="text"
                                    v-model="mhsSearch"
                                    @focus="showMhsDropdown = true"
                                    @input="showMhsDropdown = true"
                                    :disabled="
                                        !form.kelas_id || loadingMahasiswa
                                    "
                                    :placeholder="
                                        loadingMahasiswa
                                            ? 'Memuat...'
                                            : 'Cari Nama atau NIM...'
                                    "
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:border-primary-500 focus:ring-0 disabled:opacity-50"
                                />
                                <div
                                    v-if="selectedMhs"
                                    class="absolute right-3 top-1/2 -translate-y-1/2"
                                >
                                    <button
                                        @click.prevent="clearMhsSelection"
                                        class="text-gray-400 hover:text-gray-600"
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
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Dropdown List -->
                            <div
                                v-if="
                                    showMhsDropdown &&
                                    filteredMahasiswa.length > 0
                                "
                                class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-64 overflow-y-auto"
                            >
                                <button
                                    v-for="m in filteredMahasiswa"
                                    :key="m.id"
                                    @click.prevent="selectMhs(m)"
                                    class="w-full text-left px-4 py-2 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-0"
                                >
                                    <span
                                        class="block text-gray-900 dark:text-white font-medium"
                                        >{{ m.nama }}</span
                                    >
                                    <span class="block text-gray-500 text-xs">{{
                                        m.nim
                                    }}</span>
                                </button>
                            </div>

                            <div
                                v-if="
                                    showMhsDropdown &&
                                    filteredMahasiswa.length === 0 &&
                                    mahasiswaList.length > 0
                                "
                                class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 text-center text-gray-500"
                            >
                                Tidak ada data "{{ mhsSearch }}"
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Dosen Tabs & Kuesioner -->
            <!-- Step 2: Dosen Tabs & Kuesioner -->
            <form
                v-if="readyToFill && dosenList.length > 0"
                @submit.prevent="submitAll"
                class="space-y-6 animate-fade-in-up"
            >
                <h3
                    class="text-xl font-bold text-gray-900 dark:text-white px-2 flex items-center"
                >
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-300 text-sm mr-3"
                        >2</span
                    >
                    Isi Evaluasi
                </h3>

                <!-- Tabs Dosen -->
                <div class="flex overflow-x-auto space-x-2 pb-2 pl-2">
                    <button
                        v-for="dosen in dosenList"
                        :key="dosen.id"
                        type="button"
                        @click="activeDosenId = dosen.id"
                        class="px-5 py-3 rounded-xl font-medium text-sm transition-all whitespace-nowrap flex items-center space-x-2 shadow-sm border"
                        :class="
                            activeDosenId === dosen.id
                                ? 'bg-primary-600 text-white border-primary-600 shadow-md ring-2 ring-primary-200 dark:ring-primary-900'
                                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                        "
                    >
                        <span>{{ dosen.nama }}</span>
                        <!-- Status Dot -->
                        <span
                            v-if="dosen.is_filled"
                            class="flex h-3 w-3 rounded-full bg-green-400 border-2 border-white dark:border-gray-800 ml-1"
                            title="Sudah Diisi Sebelumnya"
                        ></span>
                        <span
                            v-else-if="isDosenCompleted(dosen.id)"
                            class="flex h-3 w-3 rounded-full bg-blue-400 border-2 border-white dark:border-gray-800 ml-1"
                            title="Siap Dikirim"
                        ></span>
                    </button>
                </div>

                <!-- Content Area -->
                <div>
                    <!-- Alert Info Status -->
                    <div
                        v-if="activeDosen?.is_filled"
                        class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 flex flex-col items-center justify-center text-center space-y-3 mb-6"
                    >
                        <div
                            class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-600 dark:text-green-400"
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
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        </div>
                        <h4
                            class="font-bold text-lg text-green-800 dark:text-green-300"
                        >
                            Terima Kasih!
                        </h4>
                        <p class="text-green-700 dark:text-green-400">
                            Anda sudah mengisi survei untuk dosen
                            <strong>{{ activeDosen?.nama }}</strong> pada mata
                            kuliah ini.
                        </p>
                    </div>

                    <!-- Kuesioner Wrapper per Dosen -->
                    <div
                        v-else
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                    >
                        <!-- Header for active dosen -->
                        <div
                            class="bg-gray-50 dark:bg-gray-700/30 p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-wrap gap-4"
                        >
                            <div>
                                <div
                                    class="text-sm font-medium text-gray-500 uppercase tracking-widest"
                                >
                                    Penilaian Dosen
                                </div>
                                <h4
                                    class="text-xl font-bold text-gray-900 dark:text-white mt-1"
                                >
                                    {{ activeDosen?.nama }}
                                </h4>
                            </div>
                            <div
                                class="px-4 py-1.5 rounded-full text-sm font-medium"
                                :class="
                                    isDosenCompleted(activeDosenId)
                                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                        : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                "
                            >
                                {{
                                    isDosenCompleted(activeDosenId)
                                        ? "Siap Dikirim"
                                        : "Belum Lengkap"
                                }}
                            </div>
                        </div>

                        <!-- Questions Loop -->
                        <div class="p-6 md:p-8 space-y-10">
                            <div
                                v-for="(question, index) in questions"
                                :key="question.id"
                                class="group"
                            >
                                <label
                                    class="block text-lg font-medium text-gray-800 dark:text-gray-200 mb-6 group-hover:text-primary-600 transition-colors"
                                >
                                    <span
                                        class="inline-block w-8 text-primary-500 font-bold opacity-50"
                                        >{{ index + 1 }}.</span
                                    >
                                    {{ question.pertanyaan }}
                                    <span
                                        v-if="question.is_required"
                                        class="text-red-500 ml-1 font-bold"
                                        >*</span
                                    >
                                </label>

                                <!-- Jawaban (Menggunakan answers[dosen_id][question_id]) -->
                                <!-- Scale -->
                                <div
                                    v-if="question.tipe === 'scale'"
                                    class="pl-8"
                                >
                                    <div
                                        class="flex items-center space-x-2 sm:space-x-6 overflow-x-auto pb-4 custom-scrollbar"
                                    >
                                        <div
                                            class="text-xs font-bold text-gray-400 uppercase tracking-wider w-20 text-right hidden sm:block"
                                        >
                                            Sangat Kurang
                                        </div>
                                        <div
                                            v-for="n in 5"
                                            :key="n"
                                            class="flex flex-col items-center cursor-pointer min-w-[3rem]"
                                            @click="
                                                setAnswer(
                                                    activeDosenId,
                                                    question.id,
                                                    n
                                                )
                                            "
                                        >
                                            <div
                                                class="w-12 h-12 md:w-14 md:h-14 rounded-2xl flex items-center justify-center text-xl font-bold border-2 transition-all duration-300 transform"
                                                :class="
                                                    getAnswer(
                                                        activeDosenId,
                                                        question.id
                                                    ) === n
                                                        ? 'border-primary-500 bg-primary-500 text-white shadow-lg shadow-primary-500/30 scale-110 rotate-3'
                                                        : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-400 hover:border-primary-300 hover:bg-primary-50 dark:hover:bg-gray-600'
                                                "
                                            >
                                                {{ n }}
                                            </div>
                                            <div
                                                class="mt-2 text-xs font-medium transition-colors"
                                                :class="
                                                    getAnswer(
                                                        activeDosenId,
                                                        question.id
                                                    ) === n
                                                        ? 'text-primary-600'
                                                        : 'text-gray-400'
                                                "
                                            >
                                                {{ n }}
                                            </div>
                                        </div>
                                        <div
                                            class="text-xs font-bold text-gray-400 uppercase tracking-wider w-20 hidden sm:block"
                                        >
                                            Sangat Baik
                                        </div>
                                    </div>
                                </div>

                                <!-- Essay -->
                                <div
                                    v-else-if="question.tipe === 'text'"
                                    class="pl-8 mt-2"
                                >
                                    <textarea
                                        :value="
                                            getAnswer(
                                                activeDosenId,
                                                question.id
                                            )
                                        "
                                        @input="
                                            (e) =>
                                                setAnswer(
                                                    activeDosenId,
                                                    question.id,
                                                    e.target.value
                                                )
                                        "
                                        rows="4"
                                        class="w-full rounded-2xl border-2 border-gray-200 dark:bg-gray-700/50 dark:border-gray-600 focus:ring-0 focus:border-primary-500 transition-colors bg-gray-50 dark:text-white p-4"
                                        placeholder="Tuliskan pendapat Anda di sini..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Footer -->
                        <div
                            class="bg-gray-50 dark:bg-gray-700/50 p-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center"
                        >
                            <button
                                v-if="hasPrevDosen"
                                type="button"
                                @click="prevDosen"
                                class="flex items-center px-5 py-2.5 rounded-xl text-gray-600 hover:bg-white hover:shadow-sm font-medium transition-all"
                            >
                                <svg
                                    class="w-5 h-5 mr-2"
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
                                Dosen Sebelumnya
                            </button>
                            <div class="flex-1"></div>
                            <button
                                v-if="hasNextDosen"
                                type="button"
                                @click="nextDosen"
                                class="flex items-center px-5 py-2.5 rounded-xl bg-white border border-gray-200 text-primary-600 hover:bg-primary-50 hover:border-primary-200 shadow-sm font-bold transition-all"
                            >
                                Dosen Selanjutnya
                                <svg
                                    class="w-5 h-5 ml-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Spacer for Sticky Footer -->
            <div class="h-48"></div>

            <!-- Empty State if no results -->
            <div
                v-if="
                    !readyToFill &&
                    form.kelas_id &&
                    !loadingMatakuliah &&
                    !matakuliahList.length
                "
                class="text-center py-12 text-gray-500"
            >
                Data mata kuliah atau mahasiswa tidak ditemukan untuk kelas ini.
            </div>
        </div>
        <!-- End Container -->

        <!-- Sticky Bottom Action Bar -->
        <div
            class="fixed bottom-0 left-0 w-full bg-white/90 dark:bg-gray-900/95 backdrop-blur-xl border-t dark:border-gray-700 p-4 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] z-50 transition-transform duration-500"
            :class="
                readyToFill && dosenList.length > 0
                    ? 'translate-y-0'
                    : 'translate-y-full'
            "
        >
            <div
                class="max-w-4xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4"
            >
                <div class="flex items-center space-x-4 w-full sm:w-auto">
                    <div
                        class="hidden sm:flex h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900/50 items-center justify-center text-primary-600 dark:text-primary-400"
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
                        <div
                            class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                        >
                            Status Pengisian
                        </div>
                        <div
                            class="font-bold text-gray-900 dark:text-white text-lg flex items-center"
                        >
                            <span class="text-primary-600 mr-1">{{
                                completedDosenCount
                            }}</span>
                            dari {{ validDosenList.length }} Dosen
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    @click="submitAll"
                    :disabled="submitting || completedDosenCount === 0"
                    class="w-full sm:w-auto px-8 py-4 rounded-xl font-bold text-white shadow-lg shadow-primary-500/30 transition-all transform active:scale-95 flex items-center justify-center space-x-2"
                    :class="
                        completedDosenCount > 0
                            ? 'bg-gradient-to-r from-primary-600 to-primary-700 hover:brightness-110'
                            : 'bg-gray-400 cursor-not-allowed grayscale'
                    "
                >
                    <span v-if="submitting" class="animate-spin mr-2">
                        <svg
                            class="w-5 h-5 text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                    </span>
                    <span>{{
                        submitting
                            ? "Sedang Mengirim..."
                            : `Kirim Survei (${completedDosenCount})`
                    }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
</style>
