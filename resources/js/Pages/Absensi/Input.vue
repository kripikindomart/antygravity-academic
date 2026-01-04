<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage, Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { 
    UserGroupIcon, CheckCircleIcon, XCircleIcon, ClockIcon,
    ArrowLeftIcon, DocumentCheckIcon, DocumentTextIcon, 
    BookOpenIcon, AcademicCapIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    jadwalPertemuan: Object,
    mataKuliah: Object,
    kelas: Object,
    mahasiswas: Array,
    jurnal: Object,
});

const page = usePage();

// Tabs
const activeTab = ref('absensi');
const tabs = [
    { key: 'absensi', label: 'Absensi Mahasiswa', icon: UserGroupIcon },
    { key: 'dosen', label: 'Kehadiran Dosen', icon: AcademicCapIcon },
    { key: 'jurnal', label: 'Jurnal Perkuliahan', icon: BookOpenIcon },
];

// Status indicators
const absensiSaved = computed(() => props.mahasiswas.some(m => m.absensi_id));
const jurnalSaved = computed(() => !!props.jurnal?.id);
const dosenSaved = computed(() => props.jadwalPertemuan?.dosen_hadir);

// =====================
// DOSEN ATTENDANCE TAB
// =====================
const dosenForm = useForm({
    dosen_jam_masuk: props.jadwalPertemuan?.dosen_jam_masuk || props.jadwalPertemuan?.jam_mulai || '',
    dosen_jam_keluar: props.jadwalPertemuan?.dosen_jam_keluar || props.jadwalPertemuan?.jam_selesai || '',
});

const submitDosenAttendance = () => {
    dosenForm.post(route('absensi.dosen-attendance.store', props.jadwalPertemuan.id), {
        preserveScroll: true,
        preserveState: true,
    });
};

// =====================
// ABSENSI TAB
// =====================
const attendances = ref(props.mahasiswas.map(mhs => ({
    mahasiswa_id: mhs.id,
    nim: mhs.nim,
    nama: mhs.nama,
    status: mhs.status || 'hadir',
    keterangan: mhs.keterangan || '',
    jam_masuk: mhs.jam_masuk || null,
    jam_keluar: mhs.jam_keluar || null,
})));

const isSavingAbsensi = ref(false);

const statusOptions = [
    { value: 'hadir', label: 'Hadir', color: 'bg-emerald-500', icon: CheckCircleIcon },
    { value: 'izin', label: 'Izin', color: 'bg-blue-500', icon: DocumentCheckIcon },
    { value: 'sakit', label: 'Sakit', color: 'bg-amber-500', icon: ClockIcon },
    { value: 'alpha', label: 'Alpha', color: 'bg-red-500', icon: XCircleIcon },
];

const summary = computed(() => {
    return {
        hadir: attendances.value.filter(a => a.status === 'hadir').length,
        izin: attendances.value.filter(a => a.status === 'izin').length,
        sakit: attendances.value.filter(a => a.status === 'sakit').length,
        alpha: attendances.value.filter(a => a.status === 'alpha').length,
        total: attendances.value.length,
    };
});

const setAllStatus = (status) => {
    attendances.value.forEach(a => a.status = status);
};

const submitAttendance = () => {
    isSavingAbsensi.value = true;
    router.post(route('absensi.store', props.jadwalPertemuan.id), {
        attendances: attendances.value.map(a => ({
            mahasiswa_id: a.mahasiswa_id,
            status: a.status,
            keterangan: a.keterangan || null,
            jam_masuk: a.jam_masuk || null,
            jam_keluar: a.jam_keluar || null,
        }))
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toastMessage.value = 'Absensi berhasil disimpan!';
            showToast.value = true;
            setTimeout(() => showToast.value = false, 3000);
        },
        onError: (errors) => {
            console.error('Error saving absensi:', errors);
            alert('Gagal menyimpan absensi: ' + (errors.message || Object.values(errors).join(', ')));
        },
        onFinish: () => {
            isSavingAbsensi.value = false;
        }
    });
};

// =====================
// JURNAL TAB
// =====================
const jurnalForm = useForm({
    materi: props.jurnal?.materi || '',
    aktivitas: props.jurnal?.aktivitas || '',
    capaian: props.jurnal?.capaian || '',
    catatan: props.jurnal?.catatan || '',
    file_materi: [],
    deleted_files: [],
});

// Existing files from database
const existingFiles = ref(props.jurnal?.file_materi || []);
// New files to upload
const newFiles = ref([]);
// Drag state
const isDragging = ref(false);

// Handle file selection
const handleFileSelect = (e) => {
    addFiles(e.target.files);
};

// Add files (from input or drop)
const addFiles = (fileList) => {
    for (const file of fileList) {
        if (['application/pdf', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'].includes(file.type) ||
            file.name.match(/\.(pdf|ppt|pptx)$/i)) {
            newFiles.value.push(file);
        }
    }
    jurnalForm.file_materi = newFiles.value;
};

// Remove new file (not yet uploaded)
const removeNewFile = (index) => {
    newFiles.value.splice(index, 1);
    jurnalForm.file_materi = newFiles.value;
};

// Remove existing file (from database)
const removeExistingFile = (filePath) => {
    existingFiles.value = existingFiles.value.filter(f => f !== filePath);
    jurnalForm.deleted_files.push(filePath);
};

// Drag handlers
const handleDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const handleDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;
    addFiles(e.dataTransfer.files);
};

// Get file name from path
const getFileName = (path) => {
    return path.split('/').pop();
};

const submitJurnal = () => {
    jurnalForm.post(route('absensi.jurnal.store', props.jadwalPertemuan.id), {
        forceFormData: true,
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Move new files to existing files display
            existingFiles.value = [...existingFiles.value, ...newFiles.value.map(f => f.name)];
            newFiles.value = [];
            jurnalForm.deleted_files = [];
        }
    });
};

// Toast
const showToast = ref(false);
const toastMessage = ref('');
watch(() => page.props.flash?.success, (newVal) => {
    if (newVal) {
        toastMessage.value = newVal;
        showToast.value = true;
        setTimeout(() => showToast.value = false, 3000);
    }
}, { immediate: true });

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { 
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' 
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="`Absensi & Jurnal - Pertemuan ${jadwalPertemuan.pertemuan_ke}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-3xl p-6 text-white shadow-xl">
                <div class="flex items-center gap-4 mb-4">
                    <button @click="router.visit(route('absensi.index'))" class="p-2 bg-white/20 rounded-xl hover:bg-white/30 transition">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold">Absensi & Jurnal</h1>
                        <p class="text-white/80">{{ mataKuliah?.nama }} • {{ kelas?.nama }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-4">
                    <div class="bg-white/10 backdrop-blur px-4 py-2 rounded-xl">
                        <div class="text-2xl font-black">{{ jadwalPertemuan.pertemuan_ke }}</div>
                        <div class="text-xs text-white/70 uppercase">Pertemuan</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur px-4 py-2 rounded-xl">
                        <div class="text-sm font-bold">{{ formatDate(jadwalPertemuan.tanggal) }}</div>
                        <div class="text-xs text-white/70">Tanggal</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur px-4 py-2 rounded-xl">
                        <div class="text-sm font-bold">{{ summary.total }} Mahasiswa</div>
                        <div class="text-xs text-white/70">Total</div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex border-b border-gray-100">
                    <button v-for="tab in tabs" :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'flex-1 px-6 py-4 text-sm font-bold flex items-center justify-center gap-2 transition-all',
                            activeTab === tab.key 
                                ? 'text-indigo-600 border-b-2 border-indigo-600 bg-indigo-50/50' 
                                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                        ]">
                        <component :is="tab.icon" class="w-5 h-5" />
                        {{ tab.label }}
                        <!-- Saved indicator -->
                        <span v-if="(tab.key === 'absensi' && absensiSaved) || (tab.key === 'jurnal' && jurnalSaved)" 
                            class="ml-1 px-2 py-0.5 text-xs bg-emerald-100 text-emerald-700 rounded-full font-bold">
                            ✓ Tersimpan
                        </span>
                    </button>
                </div>

                <!-- Absensi Tab Content -->
                <div v-show="activeTab === 'absensi'" class="p-6 space-y-6">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-emerald-50 rounded-2xl p-4">
                            <p class="text-sm text-emerald-600">Hadir</p>
                            <p class="text-3xl font-black text-emerald-700">{{ summary.hadir }}</p>
                        </div>
                        <div class="bg-blue-50 rounded-2xl p-4">
                            <p class="text-sm text-blue-600">Izin</p>
                            <p class="text-3xl font-black text-blue-700">{{ summary.izin }}</p>
                        </div>
                        <div class="bg-amber-50 rounded-2xl p-4">
                            <p class="text-sm text-amber-600">Sakit</p>
                            <p class="text-3xl font-black text-amber-700">{{ summary.sakit }}</p>
                        </div>
                        <div class="bg-red-50 rounded-2xl p-4">
                            <p class="text-sm text-red-600">Alpha</p>
                            <p class="text-3xl font-black text-red-700">{{ summary.alpha }}</p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm font-medium text-gray-600">Quick Actions:</span>
                        <button @click="setAllStatus('hadir')" class="px-4 py-2 bg-emerald-100 text-emerald-700 rounded-xl text-sm font-bold hover:bg-emerald-200 transition">
                            Semua Hadir
                        </button>
                        <button @click="setAllStatus('alpha')" class="px-4 py-2 bg-red-100 text-red-700 rounded-xl text-sm font-bold hover:bg-red-200 transition">
                            Semua Alpha
                        </button>
                    </div>

                    <!-- Attendance Table -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">No</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">NIM</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(mhs, index) in attendances" :key="mhs.mahasiswa_id" class="hover:bg-gray-50/50 transition">
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ index + 1 }}</td>
                                    <td class="px-4 py-3 font-mono text-sm text-gray-700">{{ mhs.nim }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ mhs.nama }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button v-for="opt in statusOptions" :key="opt.value"
                                                @click="mhs.status = opt.value"
                                                :class="[
                                                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                                    mhs.status === opt.value 
                                                        ? `${opt.color} text-white shadow-lg scale-105` 
                                                        : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                                                ]">
                                                {{ opt.label[0] }}
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input v-model="mhs.keterangan" type="text" 
                                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                            placeholder="Keterangan">
                                    </td>
                                </tr>
                                <tr v-if="attendances.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <UserGroupIcon class="w-12 h-12 mx-auto mb-3 opacity-30" />
                                        <p>Belum ada mahasiswa terdaftar</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button @click="submitAttendance" :disabled="isSavingAbsensi" 
                            class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold disabled:opacity-50 flex items-center gap-2 shadow-lg shadow-indigo-500/25">
                            <DocumentCheckIcon class="w-5 h-5" />
                            {{ isSavingAbsensi ? 'Menyimpan...' : 'Simpan Absensi' }}
                        </button>
                    </div>
                </div>

                <!-- Dosen Kehadiran Tab Content -->
                <div v-show="activeTab === 'dosen'" class="p-6 space-y-6">
                    <!-- Info Card -->
                    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl p-6 border border-emerald-100">
                        <h3 class="font-bold text-emerald-800 mb-4 flex items-center gap-2">
                            <AcademicCapIcon class="w-5 h-5" />
                            Kehadiran Dosen Pengampu
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Dosen</p>
                                <p class="font-semibold text-gray-900">{{ jadwalPertemuan.dosen?.nama || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Mata Kuliah</p>
                                <p class="font-semibold text-gray-900">{{ mataKuliah.nama }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Tanggal</p>
                                <p class="font-semibold text-gray-900">{{ new Date(jadwalPertemuan.tanggal).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Status</p>
                                <span v-if="dosenSaved" class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold">
                                    <CheckCircleIcon class="w-4 h-4" /> Hadir
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold">
                                    <ClockIcon class="w-4 h-4" /> Belum Absen
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Jam Mengajar Form -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6">
                        <h4 class="font-bold text-gray-800 mb-4">Jam Mengajar</h4>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Masuk</label>
                                <input type="time" v-model="dosenForm.dosen_jam_masuk" 
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Keluar</label>
                                <input type="time" v-model="dosenForm.dosen_jam_keluar" 
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mt-4">
                            <strong>Catatan:</strong> Jika dosen sudah mengisi jurnal perkuliahan, kehadiran akan otomatis tercatat dengan jam sesuai jadwal.
                        </p>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button @click="submitDosenAttendance" :disabled="dosenForm.processing" 
                            class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-bold disabled:opacity-50 flex items-center gap-2 shadow-lg shadow-emerald-500/25">
                            <CheckCircleIcon class="w-5 h-5" />
                            {{ dosenForm.processing ? 'Menyimpan...' : 'Simpan Kehadiran Dosen' }}
                        </button>
                    </div>
                </div>

                <!-- Jurnal Tab Content -->
                <div v-show="activeTab === 'jurnal'" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Materi -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Materi yang Diajarkan</label>
                            <textarea v-model="jurnalForm.materi" rows="4" 
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                placeholder="Tuliskan materi yang diajarkan pada pertemuan ini..."></textarea>
                        </div>

                        <!-- Aktivitas -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Aktivitas Pembelajaran</label>
                            <textarea v-model="jurnalForm.aktivitas" rows="4" 
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                placeholder="Tuliskan aktivitas pembelajaran yang dilakukan..."></textarea>
                        </div>

                        <!-- Capaian -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Capaian Pembelajaran</label>
                            <textarea v-model="jurnalForm.capaian" rows="4" 
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                placeholder="Tuliskan capaian pembelajaran yang dicapai..."></textarea>
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Catatan Tambahan</label>
                            <textarea v-model="jurnalForm.catatan" rows="4" 
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                placeholder="Catatan tambahan (opsional)..."></textarea>
                        </div>
                    </div>

                    <!-- File Materi (PPT/PDF) - Drag & Drop -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">File Materi (PPT/PDF)</label>
                        
                        <!-- Drop Zone -->
                        <div 
                            @dragover="handleDragOver"
                            @dragleave="handleDragLeave"
                            @drop="handleDrop"
                            :class="[
                                'border-2 border-dashed rounded-xl p-6 text-center transition-all cursor-pointer',
                                isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-gray-400'
                            ]"
                            @click="$refs.fileInput.click()">
                            <DocumentTextIcon class="w-10 h-10 mx-auto text-gray-400 mb-2" />
                            <p class="text-sm text-gray-600">
                                <span class="font-bold text-indigo-600">Klik untuk pilih</span> atau drag & drop file di sini
                            </p>
                            <p class="text-xs text-gray-400 mt-1">PDF, PPT, PPTX (Max 20MB per file)</p>
                            <input ref="fileInput" type="file" accept=".pdf,.ppt,.pptx" multiple @change="handleFileSelect" class="hidden">
                        </div>

                        <!-- Existing Files -->
                        <div v-if="existingFiles.length > 0" class="mt-4 space-y-2">
                            <p class="text-sm font-medium text-gray-600">File Tersimpan:</p>
                            <div v-for="file in existingFiles" :key="file" 
                                class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-2">
                                <a :href="`/storage/${file}`" target="_blank" class="flex items-center gap-2 text-sm text-indigo-600 hover:underline">
                                    <DocumentTextIcon class="w-4 h-4" />
                                    {{ getFileName(file) }}
                                </a>
                                <button @click="removeExistingFile(file)" type="button" class="text-red-500 hover:text-red-700">
                                    <XCircleIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <!-- New Files to Upload -->
                        <div v-if="newFiles.length > 0" class="mt-4 space-y-2">
                            <p class="text-sm font-medium text-gray-600">File Baru (akan diupload):</p>
                            <div v-for="(file, index) in newFiles" :key="index" 
                                class="flex items-center justify-between bg-emerald-50 rounded-lg px-4 py-2">
                                <span class="flex items-center gap-2 text-sm text-emerald-700">
                                    <DocumentTextIcon class="w-4 h-4" />
                                    {{ file.name }}
                                </span>
                                <button @click="removeNewFile(index)" type="button" class="text-red-500 hover:text-red-700">
                                    <XCircleIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Kehadiran (from absensi) -->
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h3 class="text-sm font-bold text-gray-700 mb-3">Ringkasan Kehadiran</h3>
                        <div class="flex gap-4 text-sm">
                            <span class="text-emerald-600"><strong>{{ summary.hadir }}</strong> Hadir</span>
                            <span class="text-blue-600"><strong>{{ summary.izin }}</strong> Izin</span>
                            <span class="text-amber-600"><strong>{{ summary.sakit }}</strong> Sakit</span>
                            <span class="text-red-600"><strong>{{ summary.alpha }}</strong> Alpha</span>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button @click="submitJurnal" :disabled="jurnalForm.processing" 
                            class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-bold disabled:opacity-50 flex items-center gap-2 shadow-lg shadow-purple-500/25">
                            <BookOpenIcon class="w-5 h-5" />
                            {{ jurnalForm.processing ? 'Menyimpan...' : 'Simpan Jurnal' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0"
                leave-active-class="ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
                <div v-if="showToast" class="fixed bottom-6 right-6 z-50 px-6 py-4 bg-emerald-600 text-white rounded-2xl shadow-xl flex items-center gap-3">
                    <CheckCircleIcon class="w-6 h-6" />
                    <span class="font-medium">{{ toastMessage }}</span>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
