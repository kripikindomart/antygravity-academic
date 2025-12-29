<script setup>
import AppLayout from '../../Components/Layout/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    ArrowLeftIcon, BookOpenIcon, UsersIcon, Cog6ToothIcon, SparklesIcon,
    PlusIcon, TrashIcon, CheckIcon, AcademicCapIcon, PencilIcon,
    GlobeAltIcon, ComputerDesktopIcon, CalendarIcon, ClockIcon,
    ExclamationTriangleIcon, BuildingOfficeIcon, ChartBarIcon,
    ArrowPathIcon, CheckCircleIcon, XMarkIcon, MagnifyingGlassIcon,
    Squares2X2Icon, UserIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    kelas: Object,
    availableMks: Array,
    kurikulums: Array,
    allRuangans: Array,
    dosens: Array,
});

// Tabs
const activeTab = ref('overview');
const tabs = [
    { id: 'overview', name: 'Overview', icon: ChartBarIcon },
    { id: 'mk', name: 'Mata Kuliah', icon: BookOpenIcon },
    { id: 'mahasiswa', name: 'Mahasiswa', icon: UsersIcon },
    { id: 'generate', name: 'Generate Jadwal', icon: SparklesIcon },
];

// Toast
const toast = ref({ show: false, message: '', type: 'success' });
const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => toast.value.show = false, 3000);
};

// Status colors
const statusColor = (s) => ({
    'draft': 'bg-amber-100 text-amber-700',
    'ready': 'bg-blue-100 text-blue-700',
    'generated': 'bg-emerald-100 text-emerald-700',
}[s] || 'bg-gray-100 text-gray-700');

const hariOptions = [
    { value: 'senin', label: 'Senin' },
    { value: 'selasa', label: 'Selasa' },
    { value: 'rabu', label: 'Rabu' },
    { value: 'kamis', label: 'Kamis' },
    { value: 'jumat', label: 'Jumat' },
    { value: 'sabtu', label: 'Sabtu' },
];

// Format date in Indonesian format
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric',
        timeZone: 'Asia/Jakarta'
    });
};

// =============== TAB: MATA KULIAH ===============

// Modal state for adding MK
const showAddModal = ref(false);
const modalFilter = ref('all');
const modalKurikulum = ref('');
const modalProdi = ref('');
const modalSemester = ref('');
const modalSearch = ref('');
const modalSelectedMks = ref([]);
const modalSelectAll = ref(false);

const modalFilteredMks = computed(() => {
    let mks = props.availableMks || [];
    
    // Auto-filter by kelas's prodi
    if (props.kelas?.prodi_id) {
        mks = mks.filter(mk => mk.prodi_id === props.kelas.prodi_id);
    }
    
    // Filter by semester
    if (modalSemester.value) {
        mks = mks.filter(mk => mk.semester === parseInt(modalSemester.value));
    }
    
    // Additional filter by kurikulum
    if (modalFilter.value === 'kurikulum' && modalKurikulum.value) {
        mks = mks.filter(mk => 
            mk.kurikulums?.some(k => k.id === parseInt(modalKurikulum.value))
        );
    }
    
    // Filter by search
    if (modalSearch.value) {
        const s = modalSearch.value.toLowerCase();
        mks = mks.filter(mk => 
            mk.nama?.toLowerCase().includes(s) || mk.kode?.toLowerCase().includes(s)
        );
    }
    
    return mks;
});

// Modal Pagination
const modalPerPage = ref(25);
const modalCurrentPage = ref(1);

const modalPaginatedMks = computed(() => {
    const start = (modalCurrentPage.value - 1) * modalPerPage.value;
    return modalFilteredMks.value.slice(start, start + modalPerPage.value);
});

const modalTotalPages = computed(() => Math.ceil(modalFilteredMks.value.length / modalPerPage.value) || 1);

// Reset page when filter changes
watch(modalSearch, () => modalCurrentPage.value = 1);
watch(modalFilter, () => modalCurrentPage.value = 1);
watch(modalKurikulum, () => modalCurrentPage.value = 1);
watch(modalSemester, () => modalCurrentPage.value = 1);
watch(modalPerPage, () => modalCurrentPage.value = 1);

// Toggle select all in modal - only for current page
watch(modalSelectAll, (val) => {
    if (val) {
        modalSelectedMks.value = [...new Set([...modalSelectedMks.value, ...modalPaginatedMks.value.map(mk => mk.id)])];
    } else {
        const pageIds = modalPaginatedMks.value.map(mk => mk.id);
        modalSelectedMks.value = modalSelectedMks.value.filter(id => !pageIds.includes(id));
    }
});


const openAddModal = () => {
    modalSelectedMks.value = [];
    modalSelectAll.value = false;
    modalSearch.value = '';
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
};

// Bulk add MKs from modal
const isAddingBulk = ref(false);
const bulkAddMks = async () => {
    if (modalSelectedMks.value.length === 0) return;
    if (!props.kelas?.id) {
        console.error('Kelas ID is undefined:', props.kelas);
        showToast('Error: Kelas tidak valid', 'error');
        return;
    }
    
    isAddingBulk.value = true;
    const kelasId = props.kelas.id;
    
    let count = 0;
    let errors = [];
    for (const mkId of modalSelectedMks.value) {
        try {
            await axios.post(route('kelas.assign-mk', kelasId), {
                mata_kuliah_id: mkId,
            });
            count++;
        } catch (e) {
            console.error('Error adding MK:', mkId, e.response?.data || e.message);
            errors.push(e.response?.data?.message || e.message);
        }
    }
    
    if (count > 0) {
        showToast(`${count} Mata Kuliah berhasil ditambahkan`);
    } else if (errors.length > 0) {
        showToast(`Gagal: ${errors[0]}`, 'error');
    }
    router.reload({ only: ['kelas', 'availableMks'] });
    closeAddModal();
    isAddingBulk.value = false;
};

// ========== Assigned MKs Management ==========
const selectedAssignedMks = ref([]);
const selectAllAssigned = ref(false);

// MK Table Filter & Pagination
const mkSearch = ref('');
const mkPerPage = ref(10);
const mkCurrentPage = ref(1);

const filteredAssignedMks = computed(() => {
    let mks = props.kelas?.kelas_matakuliahs || [];
    if (mkSearch.value) {
        const s = mkSearch.value.toLowerCase();
        mks = mks.filter(km => 
            km.mata_kuliah?.nama?.toLowerCase().includes(s) || 
            km.mata_kuliah?.kode?.toLowerCase().includes(s)
        );
    }
    return mks;
});

const paginatedMks = computed(() => {
    const start = (mkCurrentPage.value - 1) * mkPerPage.value;
    return filteredAssignedMks.value.slice(start, start + mkPerPage.value);
});

const totalPages = computed(() => Math.ceil(filteredAssignedMks.value.length / mkPerPage.value) || 1);

// Reset page when filter changes
watch(mkSearch, () => mkCurrentPage.value = 1);
watch(mkPerPage, () => mkCurrentPage.value = 1);

// Expandable row settings
const expandedRowId = ref(null);
const rowSettings = ref({ 
    hari: '', 
    jam_mulai: '',
    jam_selesai: '',
    tanggal_mulai: '', 
    tanggal_selesai: '', 
    dosen_id: '' 
});

const toggleRowSettings = (km) => {
    if (expandedRowId.value === km.id) {
        expandedRowId.value = null;
    } else {
        expandedRowId.value = km.id;
        rowSettings.value = {
            hari: km.hari || '',
            jam_mulai: km.jam_mulai || '',
            jam_selesai: km.jam_selesai || '',
            tanggal_mulai: km.tanggal_mulai || '',
            tanggal_selesai: km.tanggal_selesai || '',
            dosen_id: '',
        };
    }
};

const isSavingRow = ref(false);
const saveRowSettings = async (kmId) => {
    isSavingRow.value = true;
    try {
        // Only send non-empty values
        const payload = { ids: [kmId] };
        if (rowSettings.value.hari) payload.hari = rowSettings.value.hari;
        if (rowSettings.value.jam_mulai) payload.jam_mulai = rowSettings.value.jam_mulai;
        if (rowSettings.value.jam_selesai) payload.jam_selesai = rowSettings.value.jam_selesai;
        if (rowSettings.value.tanggal_mulai) payload.tanggal_mulai = rowSettings.value.tanggal_mulai;
        if (rowSettings.value.tanggal_selesai) payload.tanggal_selesai = rowSettings.value.tanggal_selesai;
        
        await axios.post(route('kelas.bulk-update-mk', props.kelas.id), payload);
        showToast('Settings berhasil disimpan');
        expandedRowId.value = null;
        router.reload({ only: ['kelas'] });
    } catch (e) {
        showToast('Gagal menyimpan settings', 'error');
    } finally {
        isSavingRow.value = false;
    }
};

// Add dosen to MK (team teaching)
const addDosenToMk = async (kmId) => {
    if (!rowSettings.value.dosen_id) return;
    try {
        await axios.post(route('kelas-mk.assign-dosen', kmId), {
            dosen_id: rowSettings.value.dosen_id,
        });
        showToast('Dosen berhasil ditambahkan');
        rowSettings.value.dosen_id = '';
        router.reload({ only: ['kelas'] });
    } catch (e) {
        showToast('Gagal menambahkan dosen', 'error');
    }
};

// Remove dosen from MK
const removeDosenFromMk = async (kmId, dosenId) => {
    try {
        await axios.delete(route('kelas-mk.remove-dosen', { kelasMatakuliah: kmId, dosen: dosenId }));
        showToast('Dosen berhasil dihapus');
        router.reload({ only: ['kelas'] });
    } catch (e) {
        showToast('Gagal menghapus dosen', 'error');
    }
};

watch(selectAllAssigned, (val) => {
    if (val) {
        selectedAssignedMks.value = paginatedMks.value.map(km => km.id);
    } else {
        selectedAssignedMks.value = [];
    }
});


// Bulk settings modal
const showBulkSettingsModal = ref(false);
const bulkSettingsType = ref(''); // 'hari', 'tanggal', 'dosen', 'ruangan'
const bulkSettingsData = ref({
    hari: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
    dosen_id: '',
    ruangan_id: '',
});

const openBulkSettings = (type) => {
    bulkSettingsType.value = type;
    bulkSettingsData.value = { hari: '', tanggal_mulai: '', tanggal_selesai: '', dosen_id: '', ruangan_id: '' };
    showBulkSettingsModal.value = true;
};

const closeBulkSettings = () => {
    showBulkSettingsModal.value = false;
};

const isSavingBulk = ref(false);
const saveBulkSettings = async () => {
    if (selectedAssignedMks.value.length === 0) return;
    isSavingBulk.value = true;
    
    try {
        await axios.post(route('kelas.bulk-update-mk', props.kelas.id), {
            ids: selectedAssignedMks.value,
            ...bulkSettingsData.value,
        });
        showToast('Settings berhasil diupdate');
        router.reload({ only: ['kelas'] });
        closeBulkSettings();
        selectedAssignedMks.value = [];
        selectAllAssigned.value = false;
    } catch (e) {
        showToast(e.response?.data?.message || 'Gagal update settings', 'error');
    } finally {
        isSavingBulk.value = false;
    }
};

// Bulk delete
const bulkDeleteMks = async () => {
    if (selectedAssignedMks.value.length === 0) return;
    if (!confirm(`Hapus ${selectedAssignedMks.value.length} MK yang dipilih?`)) return;
    
    try {
        await axios.post(route('kelas.bulk-remove-mk', props.kelas.id), {
            ids: selectedAssignedMks.value,
        });
        showToast('MK berhasil dihapus');
        router.reload({ only: ['kelas', 'availableMks'] });
        selectedAssignedMks.value = [];
        selectAllAssigned.value = false;
    } catch (e) {
        showToast('Gagal menghapus MK', 'error');
    }
};

// Single MK remove
const removeMk = async (mkId) => {
    if (!confirm('Hapus MK dari kelas?')) return;
    try {
        await axios.delete(route('kelas.remove-mk', [props.kelas.id, mkId]));
        showToast('MK berhasil dihapus');
        router.reload({ only: ['kelas', 'availableMks'] });
    } catch (e) {
        showToast('Gagal menghapus MK', 'error');
    }
};

// =============== TAB: GENERATE ===============
const isGenerating = ref(false);
const generateJadwal = async () => {
    if (!confirm('Generate jadwal untuk semua MK di kelas ini?')) return;
    isGenerating.value = true;
    try {
        await axios.post(route('kelas.generate-jadwal', props.kelas.id));
        showToast('Jadwal berhasil di-generate!');
        router.reload();
    } catch (e) {
        showToast(e.response?.data?.message || 'Gagal generate jadwal', 'error');
    } finally {
        isGenerating.value = false;
    }
};

// Stats
const mkCount = computed(() => props.kelas.kelas_matakuliahs?.length || 0);
const mhsCount = computed(() => props.kelas.mahasiswas?.length || 0);
const ruanganCount = computed(() => props.kelas.ruangans?.length || 0);
</script>

<template>
    <AppLayout :title="`Detail: ${kelas.nama}`">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('kelas.index')" 
                    class="p-2.5 rounded-xl bg-white border border-gray-200 hover:bg-gray-50 transition shadow-sm">
                    <ArrowLeftIcon class="w-5 h-5 text-gray-600" />
                </Link>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-black text-gray-900">{{ kelas.nama }}</h1>
                        <span :class="['px-3 py-1 text-xs font-bold rounded-lg', statusColor(kelas.status)]">
                            {{ kelas.status?.toUpperCase() }}
                        </span>
                    </div>
                    <p class="text-gray-500 mt-0.5">{{ kelas.prodi?.nama }} · {{ kelas.semester?.tahun_akademik?.nama }} - {{ kelas.semester?.nama }}</p>
                </div>
                <div class="flex items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-2 shadow-sm">
                    <div class="flex items-center gap-2">
                        <GlobeAltIcon class="w-5 h-5 text-blue-500" />
                        <span class="font-bold text-blue-600">{{ kelas.persen_online }}%</span>
                    </div>
                    <div class="w-px h-6 bg-gray-200"></div>
                    <div class="flex items-center gap-2">
                        <ComputerDesktopIcon class="w-5 h-5 text-green-500" />
                        <span class="font-bold text-green-600">{{ kelas.persen_offline }}%</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Tabs -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-1.5 mb-6">
                    <div class="flex gap-1">
                        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                            :class="['flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold transition',
                                activeTab === tab.id 
                                    ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-200' 
                                    : 'text-gray-600 hover:bg-gray-100']">
                            <component :is="tab.icon" class="w-5 h-5" />
                            {{ tab.name }}
                            <span v-if="tab.id === 'mk'" class="ml-1 px-2 py-0.5 bg-white/20 rounded-full text-xs">{{ mkCount }}</span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                    
                    <!-- =============== TAB: OVERVIEW =============== -->
                    <div v-show="activeTab === 'overview'" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white">
                                <GlobeAltIcon class="w-8 h-8 opacity-80" />
                                <div class="text-3xl font-black mt-2">{{ kelas.persen_online }}%</div>
                                <div class="text-blue-100">Online</div>
                            </div>
                            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-5 text-white">
                                <BookOpenIcon class="w-8 h-8 opacity-80" />
                                <div class="text-3xl font-black mt-2">{{ mkCount }}</div>
                                <div class="text-purple-100">Mata Kuliah</div>
                            </div>
                            <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-5 text-white">
                                <UsersIcon class="w-8 h-8 opacity-80" />
                                <div class="text-3xl font-black mt-2">{{ mhsCount }}</div>
                                <div class="text-amber-100">Mahasiswa</div>
                            </div>
                            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white">
                                <BuildingOfficeIcon class="w-8 h-8 opacity-80" />
                                <div class="text-3xl font-black mt-2">{{ ruanganCount }}</div>
                                <div class="text-emerald-100">Ruangan</div>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button @click="activeTab = 'mk'" class="p-4 border border-gray-200 rounded-xl hover:border-indigo-300 hover:bg-indigo-50 transition flex items-center gap-3 text-left">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <BookOpenIcon class="w-5 h-5 text-purple-600" />
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">Kelola Mata Kuliah</div>
                                    <div class="text-sm text-gray-500">Tambah atau hapus MK</div>
                                </div>
                            </button>
                            <button @click="activeTab = 'mahasiswa'" class="p-4 border border-gray-200 rounded-xl hover:border-indigo-300 hover:bg-indigo-50 transition flex items-center gap-3 text-left">
                                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                    <UsersIcon class="w-5 h-5 text-amber-600" />
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">Enroll Mahasiswa</div>
                                    <div class="text-sm text-gray-500">Import atau pilih mahasiswa</div>
                                </div>
                            </button>
                            <button @click="activeTab = 'generate'" class="p-4 border border-gray-200 rounded-xl hover:border-indigo-300 hover:bg-indigo-50 transition flex items-center gap-3 text-left">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <SparklesIcon class="w-5 h-5 text-indigo-600" />
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">Generate Jadwal</div>
                                    <div class="text-sm text-gray-500">Buat jadwal otomatis</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- =============== TAB: MATA KULIAH (NEW DESIGN) =============== -->
                    <div v-show="activeTab === 'mk'" class="p-6 space-y-4">
                        
                        <!-- Header -->
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900">Mata Kuliah Terdaftar</h3>
                            <button @click="openAddModal" 
                                class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:from-indigo-700 hover:to-purple-700 flex items-center gap-2 shadow-lg shadow-indigo-200">
                                <PlusIcon class="w-5 h-5" /> Tambah MK
                            </button>
                        </div>

                        <!-- Bulk Actions Bar -->
                        <div v-if="selectedAssignedMks.length > 0" 
                            class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 flex items-center gap-4">
                            <span class="font-semibold text-indigo-700">{{ selectedAssignedMks.length }} MK dipilih</span>
                            <div class="flex-1 flex items-center gap-2">
                                <button @click="openBulkSettings('hari')" 
                                    class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm font-semibold hover:bg-gray-50 flex items-center gap-1">
                                    <CalendarIcon class="w-4 h-4" /> Set Hari
                                </button>
                                <button @click="openBulkSettings('tanggal')" 
                                    class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm font-semibold hover:bg-gray-50 flex items-center gap-1">
                                    <ClockIcon class="w-4 h-4" /> Set Tanggal
                                </button>
                                <button @click="openBulkSettings('dosen')" 
                                    class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm font-semibold hover:bg-gray-50 flex items-center gap-1">
                                    <UserIcon class="w-4 h-4" /> Set Dosen
                                </button>
                            </div>
                            <button @click="bulkDeleteMks" 
                                class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 flex items-center gap-1">
                                <TrashIcon class="w-4 h-4" /> Hapus
                            </button>
                        </div>

                        <!-- Filter Bar -->
                        <div class="flex items-center gap-4 bg-gray-50 rounded-xl p-3">
                            <div class="relative flex-1">
                                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input v-model="mkSearch" type="text" placeholder="Cari MK..." 
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <span>Tampilkan</span>
                                <select v-model.number="mkPerPage" class="px-3 py-2 border border-gray-200 rounded-lg">
                                    <option :value="5">5</option>
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                </select>
                                <span>dari {{ filteredAssignedMks.length }} MK</span>
                            </div>
                        </div>

                        <!-- MK Table -->
                        <div class="border border-gray-200 rounded-2xl overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-12">
                                            <input type="checkbox" v-model="selectAllAssigned" 
                                                class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kode</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama MK</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">SKS</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Hari</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Dosen</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-24">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-if="paginatedMks.length === 0">
                                        <td colspan="8" class="py-16 text-center text-gray-500">
                                            <BookOpenIcon class="w-12 h-12 mx-auto opacity-30 mb-3" />
                                            <p class="font-semibold">{{ mkSearch ? 'Tidak ada hasil' : 'Belum ada Mata Kuliah' }}</p>
                                            <p class="text-sm mb-4">{{ mkSearch ? 'Coba kata kunci lain' : 'Klik tombol "Tambah MK" untuk menambahkan' }}</p>
                                            <button v-if="!mkSearch" @click="openAddModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold">
                                                <PlusIcon class="w-4 h-4 inline mr-1" /> Tambah MK
                                            </button>
                                        </td>
                                    </tr>
                                    <template v-for="km in paginatedMks" :key="km.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <input type="checkbox" :value="km.id" v-model="selectedAssignedMks" 
                                                    class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                            </td>
                                            <td class="px-4 py-3 font-mono text-sm text-gray-600">{{ km.mata_kuliah?.kode }}</td>
                                            <td class="px-4 py-3 font-semibold text-gray-900">{{ km.mata_kuliah?.nama }}</td>
                                            <td class="px-4 py-3 text-center">{{ (km.mata_kuliah?.sks_teori || 0) + (km.mata_kuliah?.sks_praktik || 0) }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span v-if="km.hari" class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold">
                                                    {{ km.hari?.charAt(0).toUpperCase() + km.hari?.slice(1) }}
                                                </span>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                            <td class="px-4 py-3 text-center text-xs">
                                                <template v-if="km.tanggal_mulai || km.tanggal_selesai">
                                                    <div>{{ formatDate(km.tanggal_mulai) }}</div>
                                                    <div class="text-gray-400">s/d</div>
                                                    <div>{{ formatDate(km.tanggal_selesai) }}</div>
                                                </template>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm">
                                                <div v-if="km.dosens?.length" class="flex flex-wrap gap-1 justify-center">
                                                    <span v-for="dd in km.dosens" :key="dd.id" 
                                                        class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs">
                                                        {{ dd.dosen?.nama }}
                                                    </span>
                                                </div>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button @click="toggleRowSettings(km)" 
                                                        :class="['p-1.5 rounded-lg transition', expandedRowId === km.id ? 'bg-indigo-100 text-indigo-600' : 'text-gray-400 hover:text-indigo-600 hover:bg-indigo-50']">
                                                        <Cog6ToothIcon class="w-4 h-4" />
                                                    </button>
                                                    <button @click="removeMk(km.mata_kuliah_id)" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                                                        <TrashIcon class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Expanded Settings Row -->
                                        <tr v-if="expandedRowId === km.id" class="bg-indigo-50">
                                            <td colspan="8" class="px-4 py-4">
                                                <div class="space-y-4">
                                                    <!-- Row 1: Hari, Jam, Tanggal -->
                                                    <div class="grid grid-cols-5 gap-4">
                                                        <div>
                                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Hari</label>
                                                            <select v-model="rowSettings.hari" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                                                <option value="">Pilih Hari</option>
                                                                <option v-for="h in hariOptions" :key="h.value" :value="h.value">{{ h.label }}</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Jam Mulai</label>
                                                            <input type="time" v-model="rowSettings.jam_mulai" 
                                                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Jam Selesai</label>
                                                            <input type="time" v-model="rowSettings.jam_selesai" 
                                                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal Mulai</label>
                                                            <input type="date" v-model="rowSettings.tanggal_mulai" 
                                                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal Selesai</label>
                                                            <input type="date" v-model="rowSettings.tanggal_selesai" 
                                                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Row 2: Dosen Team Teaching -->
                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 mb-2">Dosen Team Teaching</label>
                                                        <!-- Existing Dosens -->
                                                        <div v-if="km.dosens?.length" class="flex flex-wrap gap-2 mb-3">
                                                            <span v-for="dd in km.dosens" :key="dd.id" 
                                                                class="px-3 py-1.5 bg-purple-100 text-purple-700 rounded-lg text-sm flex items-center gap-2">
                                                                {{ dd.dosen?.nama }}
                                                                <button @click="removeDosenFromMk(km.id, dd.dosen_id)" class="text-purple-400 hover:text-red-500">
                                                                    <XMarkIcon class="w-4 h-4" />
                                                                </button>
                                                            </span>
                                                        </div>
                                                        <!-- Add Dosen -->
                                                        <div class="flex gap-2">
                                                            <select v-model="rowSettings.dosen_id" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                                                <option value="">Pilih Dosen untuk ditambahkan...</option>
                                                                <option v-for="d in dosens" :key="d.id" :value="d.id">{{ d.nama }}</option>
                                                            </select>
                                                            <button @click="addDosenToMk(km.id)" :disabled="!rowSettings.dosen_id"
                                                                class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-bold hover:bg-purple-700 disabled:opacity-50 flex items-center gap-1">
                                                                <PlusIcon class="w-4 h-4" /> Tambah Dosen
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Actions -->
                                                    <div class="flex gap-2 pt-2 border-t border-indigo-100">
                                                        <button @click="saveRowSettings(km.id)" :disabled="isSavingRow"
                                                            class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 disabled:opacity-50">
                                                            {{ isSavingRow ? 'Menyimpan...' : 'Simpan Perubahan' }}
                                                        </button>
                                                        <button @click="expandedRowId = null" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">
                                                            Tutup
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">
                                Halaman {{ mkCurrentPage }} dari {{ totalPages }}
                            </span>
                            <div class="flex gap-1">
                                <button @click="mkCurrentPage = Math.max(1, mkCurrentPage - 1)" :disabled="mkCurrentPage === 1"
                                    class="px-3 py-1.5 border rounded-lg text-sm hover:bg-gray-50 disabled:opacity-50">
                                    Prev
                                </button>
                                <button v-for="p in Math.min(5, totalPages)" :key="p" @click="mkCurrentPage = p"
                                    :class="['px-3 py-1.5 rounded-lg text-sm font-semibold', mkCurrentPage === p ? 'bg-indigo-600 text-white' : 'border hover:bg-gray-50']">
                                    {{ p }}
                                </button>
                                <button @click="mkCurrentPage = Math.min(totalPages, mkCurrentPage + 1)" :disabled="mkCurrentPage === totalPages"
                                    class="px-3 py-1.5 border rounded-lg text-sm hover:bg-gray-50 disabled:opacity-50">
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- =============== TAB: MAHASISWA =============== -->
                    <div v-show="activeTab === 'mahasiswa'" class="p-6">
                        <div class="text-center py-16 text-gray-500">
                            <div class="w-20 h-20 mx-auto bg-amber-100 rounded-full flex items-center justify-center mb-4">
                                <UsersIcon class="w-10 h-10 text-amber-600" />
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Enroll Mahasiswa</h3>
                            <p class="text-gray-500 mb-6">Fitur ini akan segera tersedia</p>
                        </div>
                    </div>

                    <!-- =============== TAB: GENERATE =============== -->
                    <div v-show="activeTab === 'generate'" class="p-6 space-y-6">
                        <div class="grid grid-cols-3 gap-5">
                            <div class="bg-purple-50 rounded-2xl p-5 border border-purple-100 text-center">
                                <div class="text-4xl font-black text-purple-600">{{ mkCount }}</div>
                                <div class="text-purple-700 font-semibold">Mata Kuliah</div>
                            </div>
                            <div class="bg-amber-50 rounded-2xl p-5 border border-amber-100 text-center">
                                <div class="text-4xl font-black text-amber-600">{{ mhsCount }}</div>
                                <div class="text-amber-700 font-semibold">Mahasiswa</div>
                            </div>
                            <div class="bg-emerald-50 rounded-2xl p-5 border border-emerald-100 text-center">
                                <div class="text-4xl font-black text-emerald-600">{{ ruanganCount }}</div>
                                <div class="text-emerald-700 font-semibold">Ruangan</div>
                            </div>
                        </div>

                        <div v-if="mkCount === 0" class="bg-amber-50 border border-amber-200 rounded-2xl p-5 flex items-center gap-4">
                            <ExclamationTriangleIcon class="w-8 h-8 text-amber-500" />
                            <div>
                                <div class="font-bold text-amber-800">Mata Kuliah kosong</div>
                                <div class="text-amber-700">Tambahkan Mata Kuliah terlebih dahulu</div>
                            </div>
                        </div>

                        <button @click="generateJadwal" :disabled="isGenerating || mkCount === 0"
                            class="w-full py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl font-bold text-lg 
                                   hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 shadow-lg shadow-indigo-200 flex items-center justify-center gap-3">
                            <SparklesIcon class="w-7 h-7" />
                            {{ isGenerating ? 'Generating...' : 'Generate Jadwal Otomatis' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- =============== MODAL: ADD MK =============== -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeAddModal">
                    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col overflow-hidden">
                        
                        <!-- Modal Header -->
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5 text-white flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold">Tambah Mata Kuliah</h2>
                                <p class="text-white/80 text-sm">Pilih MK yang akan ditambahkan ke kelas</p>
                            </div>
                            <button @click="closeAddModal" class="p-2 hover:bg-white/20 rounded-lg">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                        
                        <!-- Filters -->
                        <div class="px-6 py-4 border-b bg-gray-50 flex flex-wrap items-center gap-3">
                            <div class="relative flex-1 min-w-[200px]">
                                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input v-model="modalSearch" type="text" placeholder="Cari MK..." 
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <select v-model="modalSemester" class="px-4 py-2 border border-gray-200 rounded-xl">
                                <option value="">Semua Smt</option>
                                <option v-for="s in 8" :key="s" :value="s">Semester {{ s }}</option>
                            </select>
                            <select v-model="modalFilter" class="px-4 py-2 border border-gray-200 rounded-xl">
                                <option value="all">Semua</option>
                                <option value="kurikulum">By Kurikulum</option>
                            </select>
                            <select v-if="modalFilter === 'kurikulum'" v-model="modalKurikulum" 
                                class="px-4 py-2 border border-gray-200 rounded-xl">
                                <option value="">Pilih Kurikulum</option>
                                <option v-for="k in kurikulums" :key="k.id" :value="k.id">{{ k.nama }}</option>
                            </select>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500">Tampilkan</span>
                                <select v-model.number="modalPerPage" class="px-3 py-2 border border-gray-200 rounded-lg">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                </select>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="flex-1 overflow-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-12">
                                            <input type="checkbox" v-model="modalSelectAll" 
                                                class="w-4 h-4 rounded text-indigo-600">
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kode</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama MK</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">SKS</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Smt</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Prodi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-if="modalPaginatedMks.length === 0">
                                        <td colspan="6" class="py-12 text-center text-gray-500">Tidak ada MK tersedia</td>
                                    </tr>
                                    <tr v-for="mk in modalPaginatedMks" :key="mk.id" class="hover:bg-indigo-50 cursor-pointer"
                                        @click="modalSelectedMks.includes(mk.id) ? modalSelectedMks = modalSelectedMks.filter(id => id !== mk.id) : modalSelectedMks.push(mk.id)">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" :value="mk.id" v-model="modalSelectedMks" 
                                                class="w-4 h-4 rounded text-indigo-600" @click.stop>
                                        </td>
                                        <td class="px-4 py-3 font-mono text-sm">{{ mk.kode }}</td>
                                        <td class="px-4 py-3 font-semibold">{{ mk.nama }}</td>
                                        <td class="px-4 py-3 text-center">{{ (mk.sks_teori || 0) + (mk.sks_praktik || 0) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-bold">
                                                {{ mk.semester || '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ mk.prodi?.nama }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="modalTotalPages > 1" class="px-6 py-3 border-t bg-gray-50 flex items-center justify-between">
                            <span class="text-sm text-gray-600">
                                {{ modalFilteredMks.length }} total · Hal {{ modalCurrentPage }}/{{ modalTotalPages }}
                            </span>
                            <div class="flex gap-1">
                                <button @click="modalCurrentPage = Math.max(1, modalCurrentPage - 1)" :disabled="modalCurrentPage === 1"
                                    class="px-3 py-1.5 border rounded-lg text-sm hover:bg-gray-100 disabled:opacity-50">Prev</button>
                                <button v-for="p in Math.min(5, modalTotalPages)" :key="p" @click="modalCurrentPage = p"
                                    :class="['px-3 py-1.5 rounded-lg text-sm', modalCurrentPage === p ? 'bg-indigo-600 text-white' : 'border hover:bg-gray-100']">{{ p }}</button>
                                <button @click="modalCurrentPage = Math.min(modalTotalPages, modalCurrentPage + 1)" :disabled="modalCurrentPage === modalTotalPages"
                                    class="px-3 py-1.5 border rounded-lg text-sm hover:bg-gray-100 disabled:opacity-50">Next</button>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 border-t bg-gray-50 flex items-center justify-between">
                            <span class="text-gray-600 font-semibold">{{ modalSelectedMks.length }} MK dipilih</span>
                            <div class="flex gap-3">
                                <button @click="closeAddModal" class="px-5 py-2 border border-gray-200 rounded-xl font-semibold hover:bg-gray-100">
                                    Batal
                                </button>
                                <button @click="bulkAddMks" :disabled="modalSelectedMks.length === 0 || isAddingBulk"
                                    class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 flex items-center gap-2">
                                    <CheckCircleIcon class="w-5 h-5" />
                                    {{ isAddingBulk ? 'Menambahkan...' : `Tambah ${modalSelectedMks.length} MK` }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- =============== MODAL: BULK SETTINGS =============== -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showBulkSettingsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeBulkSettings">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                        
                        <div class="bg-indigo-600 px-6 py-4 text-white">
                            <h2 class="text-lg font-bold">
                                {{ bulkSettingsType === 'hari' ? 'Set Hari' : 
                                   bulkSettingsType === 'tanggal' ? 'Set Tanggal' : 
                                   bulkSettingsType === 'dosen' ? 'Set Dosen' : 'Set Ruangan' }}
                            </h2>
                            <p class="text-white/80 text-sm">Untuk {{ selectedAssignedMks.length }} MK terpilih</p>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <!-- Hari -->
                            <div v-if="bulkSettingsType === 'hari'">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Hari</label>
                                <select v-model="bulkSettingsData.hari" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl">
                                    <option value="">Pilih Hari</option>
                                    <option v-for="h in hariOptions" :key="h.value" :value="h.value">{{ h.label }}</option>
                                </select>
                            </div>

                            <!-- Tanggal -->
                            <div v-if="bulkSettingsType === 'tanggal'" class="space-y-3">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Mulai</label>
                                    <input type="date" v-model="bulkSettingsData.tanggal_mulai" 
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Selesai</label>
                                    <input type="date" v-model="bulkSettingsData.tanggal_selesai" 
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl">
                                </div>
                            </div>

                            <!-- Dosen -->
                            <div v-if="bulkSettingsType === 'dosen'">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Dosen</label>
                                <select v-model="bulkSettingsData.dosen_id" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl">
                                    <option value="">Pilih Dosen</option>
                                    <option v-for="d in dosens" :key="d.id" :value="d.id">{{ d.nama }}</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3">
                            <button @click="closeBulkSettings" class="px-5 py-2 border border-gray-200 rounded-xl font-semibold hover:bg-gray-100">
                                Batal
                            </button>
                            <button @click="saveBulkSettings" :disabled="isSavingBulk"
                                class="px-6 py-2 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 disabled:opacity-50">
                                {{ isSavingBulk ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Toast -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0"
                leave-active-class="ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
                <div v-if="toast.show" 
                    :class="['fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3 font-semibold',
                        toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white']">
                    <CheckIcon v-if="toast.type === 'success'" class="w-5 h-5" />
                    <XMarkIcon v-else class="w-5 h-5" />
                    {{ toast.message }}
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
