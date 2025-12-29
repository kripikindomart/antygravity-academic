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
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

const props = defineProps({
    kelas: Object,
    availableMks: Array,
    kurikulums: Array,
    allRuangans: Array,
    dosens: Array,
    filterData: Object,
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

// Time options for academic schedule (07:00 - 21:00, 30 min intervals)
const timeOptions = [];
for (let h = 7; h <= 21; h++) {
    for (let m = 0; m < 60; m += 30) {
        const hour24 = h.toString().padStart(2, '0');
        const min = m.toString().padStart(2, '0');
        const value = `${hour24}:${min}`;
        // Format: 07:00 (7 Pagi), 13:00 (1 Siang), etc.
        const hour12 = h > 12 ? h - 12 : h;
        const period = h < 12 ? 'Pagi' : h < 15 ? 'Siang' : h < 18 ? 'Sore' : 'Malam';
        const label = `${hour24}:${min} (${hour12}:${min} ${period})`;
        timeOptions.push({ value, label });
    }
}

// Format 24h to 12h with period
const formatTime12 = (time24) => {
    if (!time24) return '';
    const [h, m] = time24.split(':').map(Number);
    const hour12 = h > 12 ? h - 12 : h === 0 ? 12 : h;
    const period = h < 12 ? 'AM' : 'PM';
    return `${hour12}:${m.toString().padStart(2, '0')} ${period}`;
};

// Spinner time picker helpers
const getTimeHour = (time) => {
    if (!time) return '08';
    return time.split(':')[0] || '08';
};

const getTimeMinute = (time) => {
    if (!time) return '00';
    return time.split(':')[1] || '00';
};

const incrementTime = (field, part, delta) => {
    let currentTime = rowSettings.value[field] || '08:00';
    let [h, m] = currentTime.split(':').map(Number);
    
    if (part === 'hour') {
        h = Math.max(0, Math.min(23, h + delta));
    } else {
        m = m + delta;
        if (m >= 60) { m = 0; h = Math.min(23, h + 1); }
        if (m < 0) { m = 55; h = Math.max(0, h - 1); }
    }
    
    rowSettings.value[field] = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
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

// Time picker refs
const jamMulaiRef = ref(null);
const jamSelesaiRef = ref(null);
let fpMulai = null;
let fpSelesai = null;

// Initialize flatpickr when row expands
const initTimePickers = () => {
    setTimeout(() => {
        if (jamMulaiRef.value && !fpMulai) {
            fpMulai = flatpickr(jamMulaiRef.value, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                defaultDate: rowSettings.value.jam_mulai || "08:00",
                onChange: (selectedDates, dateStr) => {
                    rowSettings.value.jam_mulai = dateStr;
                }
            });
        }
        if (jamSelesaiRef.value && !fpSelesai) {
            fpSelesai = flatpickr(jamSelesaiRef.value, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                defaultDate: rowSettings.value.jam_selesai || "10:00",
                onChange: (selectedDates, dateStr) => {
                    rowSettings.value.jam_selesai = dateStr;
                }
            });
        }
    }, 50);
};

// Destroy flatpickr when row collapses
const destroyTimePickers = () => {
    if (fpMulai) { fpMulai.destroy(); fpMulai = null; }
    if (fpSelesai) { fpSelesai.destroy(); fpSelesai = null; }
};

// Searchable dosen dropdown
const dosenSearchQuery = ref('');
const showDosenDropdown = ref(false);
const filteredDosens = computed(() => {
    if (!dosenSearchQuery.value) return props.dosens?.slice(0, 10) || [];
    const q = dosenSearchQuery.value.toLowerCase();
    return (props.dosens || []).filter(d => 
        d.nama?.toLowerCase().includes(q) || 
        d.nidn?.toLowerCase().includes(q) ||
        d.nip?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const selectDosen = (d) => {
    rowSettings.value.dosen_id = d.id;
    dosenSearchQuery.value = d.nama;
    showDosenDropdown.value = false;
};

// Close dosen dropdown with delay (allows click on items to register)
const closeDosenDropdown = () => {
    setTimeout(() => {
        showDosenDropdown.value = false;
    }, 150);
};

const toggleRowSettings = (km) => {
    if (expandedRowId.value === km.id) {
        destroyTimePickers();
        expandedRowId.value = null;
    } else {
        destroyTimePickers();
        expandedRowId.value = km.id;
        rowSettings.value = {
            hari: km.hari || '',
            jam_mulai: km.jam_mulai || '',
            jam_selesai: km.jam_selesai || '',
            tanggal_mulai: km.tanggal_mulai || '',
            tanggal_selesai: km.tanggal_selesai || '',
            dosen_id: '',
        };
        dosenSearchQuery.value = '';
        showDosenDropdown.value = false;
        initTimePickers();
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
        dosenSearchQuery.value = '';
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

// Ruangan Management
const ruanganSearchQuery = ref('');
const showRuanganDropdown = ref(false);
const filteredRuangans = computed(() => {
    if (!ruanganSearchQuery.value) return [];
    const q = ruanganSearchQuery.value.toLowerCase();
    return (props.allRuangans || []).filter(r => 
        r.nama.toLowerCase().includes(q) || 
        r.kode.toLowerCase().includes(q)
    ).slice(0, 10);
});
const closeRuanganDropdown = () => setTimeout(() => showRuanganDropdown.value = false, 200);
const selectRuangan = (r) => {
    rowSettings.value.ruangan_id = r.id;
    ruanganSearchQuery.value = r.nama;
    showRuanganDropdown.value = false;
};

const addRuanganToMk = async (mkId) => {
    if (!rowSettings.value.ruangan_id) return;
    try {
        await axios.post(route('kelas-mk.add-ruangan', mkId), { 
            ruangan_id: rowSettings.value.ruangan_id 
        });
        showToast('Ruangan berhasil ditambahkan');
        router.reload({ only: ['kelas'] });
        rowSettings.value.ruangan_id = '';
        ruanganSearchQuery.value = '';
    } catch (e) {
        showToast('Gagal menambah ruangan', 'error');
    }
};

const removeRuanganFromMk = async (mkId, ruanganId) => {
    if (!confirm('Hapus ruangan ini?')) return;
    try {
        await axios.delete(route('kelas-mk.remove-ruangan', { kelasMatakuliah: mkId, ruanganId }));
        showToast('Ruangan berhasil dihapus');
        router.reload({ only: ['kelas'] });
    } catch (e) {
        showToast('Gagal menghapus ruangan', 'error');
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
const bulkSettingsType = ref(''); // 'hari', 'jam', 'tanggal', 'dosen', 'ruangan'
const bulkSettingsData = ref({
    hari: '',
    jam_mulai: '',
    jam_selesai: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
    dosen_id: '',
    ruangan_id: '',
});

const openBulkSettings = (type) => {
    bulkSettingsType.value = type;
    bulkSettingsData.value = { hari: '', jam_mulai: '08:00', jam_selesai: '10:00', tanggal_mulai: '', tanggal_selesai: '', dosen_id: '', ruangan_id: '' };
    showBulkSettingsModal.value = true;
    
    // Init flatpickr for jam type
    if (type === 'jam') {
        setTimeout(() => {
            if (bulkJamMulaiRef.value) {
                fpBulkMulai = flatpickr(bulkJamMulaiRef.value, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true,
                    defaultDate: "08:00",
                    onChange: (selectedDates, dateStr) => {
                        bulkSettingsData.value.jam_mulai = dateStr;
                    }
                });
            }
            if (bulkJamSelesaiRef.value) {
                fpBulkSelesai = flatpickr(bulkJamSelesaiRef.value, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true,
                    defaultDate: "10:00",
                    onChange: (selectedDates, dateStr) => {
                        bulkSettingsData.value.jam_selesai = dateStr;
                    }
                });
            }
        }, 100);
    }
};

// Bulk time picker refs
const bulkJamMulaiRef = ref(null);
const bulkJamSelesaiRef = ref(null);
let fpBulkMulai = null;
let fpBulkSelesai = null;

// Increment time for bulk settings modal
const incrementBulkTime = (field, part, delta) => {
    let currentTime = bulkSettingsData.value[field] || '08:00';
    let [h, m] = currentTime.split(':').map(Number);
    
    if (part === 'hour') {
        h = Math.max(0, Math.min(23, h + delta));
    } else {
        m = m + delta;
        if (m >= 60) { m = 0; h = Math.min(23, h + 1); }
        if (m < 0) { m = 55; h = Math.max(0, h - 1); }
    }
    
    bulkSettingsData.value[field] = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
};

const closeBulkSettings = () => {
    if (fpBulkMulai) { fpBulkMulai.destroy(); fpBulkMulai = null; }
    if (fpBulkSelesai) { fpBulkSelesai.destroy(); fpBulkSelesai = null; }
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

// =============== TAB: MAHASISWA ===============
const mhsSearch = ref('');
const mhsPerPage = ref(10);
const mhsCurrentPage = ref(1);
const selectedMhs = ref([]);
const selectAllMhs = ref(false);

const filteredMahasiswas = computed(() => {
    let result = props.kelas.mahasiswas || [];
    if (mhsSearch.value) {
        const q = mhsSearch.value.toLowerCase();
        result = result.filter(m => 
            m.nama.toLowerCase().includes(q) || 
            m.nim.toLowerCase().includes(q)
        );
    }
    return result;
});

const mhsTotalPages = computed(() => Math.ceil(filteredMahasiswas.value.length / mhsPerPage.value));
const paginatedMahasiswas = computed(() => {
    const start = (mhsCurrentPage.value - 1) * mhsPerPage.value;
    return filteredMahasiswas.value.slice(start, start + mhsPerPage.value);
});

// Watchers for Mhs pagination/filter
watch([mhsSearch, mhsPerPage], () => { mhsCurrentPage.value = 1; });
watch(selectAllMhs, (val) => {
    if (val) {
        selectedMhs.value = paginatedMahasiswas.value.map(m => m.id);
    } else {
        selectedMhs.value = [];
    }
});

// Remove Mahasiswa
const removeMahasiswa = async (mhsId) => {
    if (!confirm('Hapus mahasiswa dari kelas ini?')) return;
    try {
        await axios.delete(route('kelas.remove-mahasiswa', [props.kelas.id, mhsId]));
        showToast('Mahasiswa berhasil dihapus');
        router.reload({ only: ['kelas', 'availableMahasiswas'] });
    } catch (e) {
        showToast('Gagal menghapus mahasiswa', 'error');
    }
};

const bulkRemoveMahasiswa = async () => {
    if (selectedMhs.value.length === 0) return;
    if (!confirm(`Hapus ${selectedMhs.value.length} mahasiswa terpilih dari kelas?`)) return;
    
    try {
        await axios.post(route('kelas.bulk-remove-mahasiswa', props.kelas.id), {
            mahasiswa_ids: selectedMhs.value
        });
        showToast('Mahasiswa berhasil dihapus');
        selectedMhs.value = [];
        selectAllMhs.value = false;
        router.reload({ only: ['kelas', 'availableMahasiswas'] });
    } catch (e) {
        showToast('Gagal menghapus mahasiswa', 'error');
    }
};

// Enroll Mahasiswa Modal
const showEnrollMhsModal = ref(false);
const enrollMhsSearch = ref('');
const selectedEnrollMhs = ref([]);
const isEnrolling = ref(false);
const enrollCandidates = ref({ data: [], current_page: 1, last_page: 1, total: 0 });
const isLoadingCandidates = ref(false);
const enrollFilters = ref({
    prodi_id: '',
    angkatan: '',
    status: 'aktif',
});
const selectAllPage = ref(false);
const enrollPerPage = ref(20);

let searchTimeout;
const fetchEnrollCandidates = async (page = 1) => {
    isLoadingCandidates.value = true;
    try {
        const res = await axios.get(route('kelas.enroll-candidates', props.kelas.id), {
            params: {
                page,
                per_page: enrollPerPage.value,
                search: enrollMhsSearch.value,
                ...enrollFilters.value
            }
        });
        enrollCandidates.value = res.data;
        updateSelectAllState();
    } catch (e) {
        console.error(e);
    } finally {
        isLoadingCandidates.value = false;
    }
};

const updateSelectAllState = () => {
    if (enrollCandidates.value.data.length === 0) {
        selectAllPage.value = false;
        return;
    }
    const allSelected = enrollCandidates.value.data.every(m => selectedEnrollMhs.value.includes(m.id));
    selectAllPage.value = allSelected;
};

// Toggle single selection
const toggleEnrollMhs = (id) => {
    if (selectedEnrollMhs.value.includes(id)) {
        selectedEnrollMhs.value = selectedEnrollMhs.value.filter(i => i !== id);
    } else {
        selectedEnrollMhs.value.push(id);
    }
    updateSelectAllState();
};

// Toggle all in current page
const toggleSelectAllPage = () => {
    const pageIds = enrollCandidates.value.data.map(m => m.id);
    if (!selectAllPage.value) {
        // Currently not all selected -> Select All
        const newIds = pageIds.filter(id => !selectedEnrollMhs.value.includes(id));
        selectedEnrollMhs.value = [...selectedEnrollMhs.value, ...newIds];
        selectAllPage.value = true;
    } else {
        // Currently all selected -> Deselect All
        selectedEnrollMhs.value = selectedEnrollMhs.value.filter(id => !pageIds.includes(id));
        selectAllPage.value = false;
    }
};

// Watchers for search and filters
watch(enrollMhsSearch, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchEnrollCandidates(1), 300);
});
watch(enrollFilters, () => { fetchEnrollCandidates(1); }, { deep: true });
watch(enrollPerPage, () => { fetchEnrollCandidates(1); });
watch(showEnrollMhsModal, (val) => {
    if (val) {
        // Set default prodi if empty
        if (!enrollFilters.value.prodi_id && props.kelas.prodi_id) {
            enrollFilters.value.prodi_id = props.kelas.prodi_id;
        }
        fetchEnrollCandidates(1);
    }
});

const submitEnrollMhs = async () => {
    if (selectedEnrollMhs.value.length === 0) return;
    isEnrolling.value = true;
    try {
        await axios.post(route('kelas.enroll-mahasiswa', props.kelas.id), {
            mahasiswa_ids: selectedEnrollMhs.value
        });
        showToast('Mahasiswa berhasil ditambahkan');
        showEnrollMhsModal.value = false;
        selectedEnrollMhs.value = [];
        enrollMhsSearch.value = '';
        router.reload({ only: ['kelas'] }); // No more availableMahasiswas prop reload needed
    } catch (e) {
        showToast('Gagal menambahkan mahasiswa', 'error');
    } finally {
        isEnrolling.value = false;
    }
};

// Stats
const mkCount = computed(() => props.kelas.kelas_matakuliahs?.length || 0);
const mhsCount = computed(() => props.kelas.mahasiswas?.length || 0);
const ruanganCount = computed(() => {
    const uniqueIds = new Set();
    const mks = props.kelas.kelas_matakuliahs || [];
    mks.forEach(mk => {
        if (mk.ruangans && Array.isArray(mk.ruangans)) {
            mk.ruangans.forEach(r => uniqueIds.add(r.id));
        }
    });
    return uniqueIds.size;
});
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
                                <button @click="openBulkSettings('jam')" 
                                    class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm font-semibold hover:bg-gray-50 flex items-center gap-1">
                                    <ClockIcon class="w-4 h-4" /> Set Jam
                                </button>
                                <button @click="openBulkSettings('tanggal')" 
                                    class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm font-semibold hover:bg-gray-50 flex items-center gap-1">
                                    <CalendarIcon class="w-4 h-4" /> Set Tanggal
                                </button>
                                <button @click="openBulkSettings('dosen')" 
                                    class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm font-semibold hover:bg-gray-50 flex items-center gap-1">
                                    <UserIcon class="w-4 h-4" /> Set Dosen
                                </button>
                                <button @click="openBulkSettings('ruangan')" 
                                    class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm font-semibold hover:bg-gray-50 flex items-center gap-1">
                                    <BuildingOfficeIcon class="w-4 h-4" /> Set Ruangan
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
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Ruangan</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-24">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-if="paginatedMks.length === 0">
                                        <td colspan="9" class="py-16 text-center text-gray-500">
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
                                                <!-- Jam info below hari -->
                                                <div v-if="km.jam_mulai && km.jam_selesai" class="mt-1 text-xs text-indigo-600 font-medium">
                                                    {{ km.jam_mulai?.substring(0,5) }} - {{ km.jam_selesai?.substring(0,5) }}
                                                </div>
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
                                            <td class="px-4 py-3 text-center text-sm">
                                                <div v-if="km.ruangans?.length" class="flex flex-wrap gap-1 justify-center">
                                                    <span v-for="r in km.ruangans" :key="r.id" 
                                                        class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-xs">
                                                        {{ r.nama }}
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
                                                            <!-- Time info below Hari -->
                                                            <div v-if="rowSettings.jam_mulai && rowSettings.jam_selesai" class="mt-1.5 text-xs text-indigo-600 font-medium">
                                                                🕐 {{ rowSettings.jam_mulai }} - {{ rowSettings.jam_selesai }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Jam Mulai</label>
                                                            <input ref="jamMulaiRef" type="text" readonly 
                                                                :value="rowSettings.jam_mulai"
                                                                placeholder="Pilih jam..."
                                                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white cursor-pointer">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Jam Selesai</label>
                                                            <input ref="jamSelesaiRef" type="text" readonly 
                                                                :value="rowSettings.jam_selesai"
                                                                placeholder="Pilih jam..."
                                                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white cursor-pointer">
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
                                                        <!-- Add Dosen with Search -->
                                                        <div class="flex gap-2">
                                                            <div class="relative flex-1">
                                                                <input 
                                                                    type="text" 
                                                                    v-model="dosenSearchQuery"
                                                                    @focus="showDosenDropdown = true"
                                                                    @blur="closeDosenDropdown"
                                                                    placeholder="Cari nama dosen..."
                                                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
                                                                />
                                                                <!-- Dropdown -->
                                                                <div v-if="showDosenDropdown && filteredDosens.length > 0" 
                                                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                                                    <button 
                                                                        v-for="d in filteredDosens" 
                                                                        :key="d.id" 
                                                                        @click="selectDosen(d)"
                                                                        class="w-full px-3 py-2 text-left text-sm hover:bg-purple-50 flex items-center gap-2"
                                                                    >
                                                                        <UserIcon class="w-4 h-4 text-gray-400" />
                                                                        <span>{{ d.nama }}</span>
                                                                        <span v-if="d.nidn" class="text-gray-400 text-xs ml-auto">{{ d.nidn }}</span>
                                                                    </button>
                                                                </div>
                                                                <div v-if="showDosenDropdown && filteredDosens.length === 0 && dosenSearchQuery" 
                                                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-3 text-sm text-gray-500 text-center">
                                                                    Tidak ada dosen ditemukan
                                                                </div>
                                                            </div>
                                                            <button @click="addDosenToMk(km.id)" :disabled="!rowSettings.dosen_id"
                                                                class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-bold hover:bg-purple-700 disabled:opacity-50 flex items-center gap-1">
                                                                <PlusIcon class="w-4 h-4" /> Tambah
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Row 3: Pilihan Ruangan -->
                                                    <div class="mt-4 pt-4 border-t border-gray-100">
                                                        <label class="block text-xs font-semibold text-gray-600 mb-2">Pilihan Ruangan (Preferensi)</label>
                                                        <!-- Existing Ruangans -->
                                                        <div v-if="km.ruangans?.length" class="flex flex-wrap gap-2 mb-3">
                                                            <span v-for="r in km.ruangans" :key="r.id" 
                                                                class="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-lg text-sm flex items-center gap-2">
                                                                {{ r.nama }}
                                                                <button @click="removeRuanganFromMk(km.id, r.id)" class="text-emerald-500 hover:text-red-500">
                                                                    <XMarkIcon class="w-4 h-4" />
                                                                </button>
                                                            </span>
                                                        </div>
                                                        <!-- Add Ruangan with Search -->
                                                        <div class="flex gap-2">
                                                            <div class="relative flex-1">
                                                                <input 
                                                                    type="text" 
                                                                    v-model="ruanganSearchQuery"
                                                                    @focus="showRuanganDropdown = true"
                                                                    @blur="closeRuanganDropdown"
                                                                    placeholder="Cari ruangan..."
                                                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                                                />
                                                                <!-- Dropdown -->
                                                                <div v-if="showRuanganDropdown && filteredRuangans.length > 0" 
                                                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                                                    <button 
                                                                        v-for="r in filteredRuangans" 
                                                                        :key="r.id" 
                                                                        @click="selectRuangan(r)"
                                                                        class="w-full px-3 py-2 text-left text-sm hover:bg-emerald-50 flex items-center gap-2"
                                                                    >
                                                                        <BuildingOfficeIcon class="w-4 h-4 text-gray-400" />
                                                                        <span>{{ r.nama }}</span>
                                                                        <span v-if="r.kode" class="text-gray-400 text-xs ml-auto">{{ r.kode }}</span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <button @click="addRuanganToMk(km.id)" :disabled="!rowSettings.ruangan_id"
                                                                class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-bold hover:bg-emerald-700 disabled:opacity-50 flex items-center gap-1">
                                                                <PlusIcon class="w-4 h-4" /> Tambah
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
                        <!-- Toolbar -->
                        <div class="flex flex-col md:flex-row gap-4 mb-6 justify-between items-center">
                            <div class="flex gap-2 w-full md:w-auto">
                                <button @click="showEnrollMhsModal = true" 
                                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 flex items-center gap-2">
                                    <PlusIcon class="w-5 h-5" /> Enroll Mahasiswa
                                </button>
                                <button v-if="selectedMhs.length > 0" @click="bulkRemoveMahasiswa" 
                                    class="px-4 py-2 bg-red-100 text-red-600 rounded-xl font-bold hover:bg-red-200 flex items-center gap-2">
                                    <TrashIcon class="w-5 h-5" /> Hapus ({{ selectedMhs.length }})
                                </button>
                            </div>

                            <div class="relative w-full md:w-64">
                                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input v-model="mhsSearch" type="text" placeholder="Cari Mahasiswa..." 
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredMahasiswas.length === 0" class="text-center py-12 border-2 border-dashed border-gray-200 rounded-2xl">
                            <UsersIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                            <h3 class="text-lg font-bold text-gray-600">Belum ada mahasiswa</h3>
                            <p class="text-gray-400">Enroll mahasiswa ke kelas ini untuk memulai.</p>
                        </div>

                        <!-- Table -->
                        <div v-else class="bg-white border boundary-gray-200 rounded-xl overflow-hidden shadow-sm">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 w-10">
                                            <input type="checkbox" v-model="selectAllMhs"
                                                class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">NIM</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="m in paginatedMahasiswas" :key="m.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" :value="m.id" v-model="selectedMhs" 
                                                class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-6 py-3 font-mono text-sm text-gray-600">{{ m.nim }}</td>
                                        <td class="px-6 py-3 font-semibold text-gray-900">{{ m.nama }}</td>
                                        <td class="px-6 py-3">
                                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-bold">Aktif</span>
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <button @click="removeMahasiswa(m.id)" 
                                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <TrashIcon class="w-5 h-5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="mhsTotalPages > 1" class="flex items-center justify-between mt-4">
                            <span class="text-sm text-gray-600">
                                Halaman {{ mhsCurrentPage }} dari {{ mhsTotalPages }}
                            </span>
                            <div class="flex gap-1">
                                <button @click="mhsCurrentPage = Math.max(1, mhsCurrentPage - 1)" :disabled="mhsCurrentPage === 1"
                                    class="px-3 py-1.5 border rounded-lg text-sm hover:bg-gray-50 disabled:opacity-50">
                                    Prev
                                </button>
                                <button v-for="p in Math.min(5, mhsTotalPages)" :key="p" @click="mhsCurrentPage = p"
                                    :class="['px-3 py-1.5 rounded-lg text-sm font-semibold', mhsCurrentPage === p ? 'bg-indigo-600 text-white' : 'border hover:bg-gray-50']">
                                    {{ p }}
                                </button>
                                <button @click="mhsCurrentPage = Math.min(mhsTotalPages, mhsCurrentPage + 1)" :disabled="mhsCurrentPage === mhsTotalPages"
                                    class="px-3 py-1.5 border rounded-lg text-sm hover:bg-gray-50 disabled:opacity-50">
                                    Next
                                </button>
                            </div>
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
                                   bulkSettingsType === 'jam' ? 'Set Jam' : 
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

                            <!-- Jam -->
                            <div v-if="bulkSettingsType === 'jam'" class="space-y-3">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Mulai</label>
                                    <input ref="bulkJamMulaiRef" type="text" readonly
                                        :value="bulkSettingsData.jam_mulai"
                                        placeholder="Pilih jam..."
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-white cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Selesai</label>
                                    <input ref="bulkJamSelesaiRef" type="text" readonly
                                        :value="bulkSettingsData.jam_selesai"
                                        placeholder="Pilih jam..."
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-white cursor-pointer">
                                </div>
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

                            <!-- Ruangan -->
                            <div v-if="bulkSettingsType === 'ruangan'">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Ruangan</label>
                                <select v-model="bulkSettingsData.ruangan_id" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl">
                                    <option value="">Pilih Ruangan</option>
                                    <option v-for="r in allRuangans" :key="r.id" :value="r.id">{{ r.nama }}</option>
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

        <!-- =============== MODAL: ENROLL MAHASISWA =============== -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showEnrollMhsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showEnrollMhsModal = false">
                    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col overflow-hidden">
                        
                        <!-- Modal Header -->
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5 text-white flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold">Enroll Mahasiswa</h2>
                                <p class="text-white/80 text-sm">Pilih mahasiswa untuk ditambahkan ke kelas ini</p>
                            </div>
                            <button @click="showEnrollMhsModal = false" class="p-2 hover:bg-white/20 rounded-lg">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                        
                        <!-- Filters -->
                        <div class="px-6 py-4 border-b bg-gray-50 flex flex-wrap gap-3 items-center">
                            <div class="relative flex-1 min-w-[200px]">
                                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input v-model="enrollMhsSearch" type="text" placeholder="Cari nama atau NIM..." 
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>
                            
                            <!-- Filter Prodi -->
                            <select v-model="enrollFilters.prodi_id" class="px-4 py-2 border border-gray-200 rounded-xl max-w-xs truncate">
                                <option value="">Semua Prodi</option>
                                <option v-for="p in filterData.prodis" :key="p.id" :value="p.id">{{ p.nama }}</option>
                            </select>

                            <!-- Filter Angkatan -->
                            <select v-model="enrollFilters.angkatan" class="px-4 py-2 border border-gray-200 rounded-xl">
                                <option value="">Semua Angkatan</option>
                                <option v-for="a in filterData.angkatans" :key="a" :value="a">{{ a }}</option>
                            </select>

                            <!-- Filter Status -->
                            <select v-model="enrollFilters.status" class="px-4 py-2 border border-gray-200 rounded-xl">
                                <option value="">Semua Status</option>
                                <option v-for="s in filterData.statuses" :key="s" :value="s">{{ s.toUpperCase() }}</option>
                            </select>

                            <!-- Per Page -->
                            <select v-model.number="enrollPerPage" class="px-4 py-2 border border-gray-200 rounded-xl ml-auto">
                                <option :value="10">10 / Hal</option>
                                <option :value="20">20 / Hal</option>
                                <option :value="50">50 / Hal</option>
                                <option :value="100">100 / Hal</option>
                            </select>
                        </div>

                        <!-- List -->
                        <div class="flex-1 overflow-y-auto p-0 relative">
                            <!-- Loading Overlay -->
                            <div v-if="isLoadingCandidates" class="absolute inset-0 bg-white/80 z-20 flex items-center justify-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                            </div>

                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-6 py-3 text-left w-10 bg-gray-50">
                                            <input type="checkbox" :checked="selectAllPage" @change="toggleSelectAllPage"
                                                :disabled="enrollCandidates.data.length === 0"
                                                class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider bg-gray-50">NIM</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider bg-gray-50">Nama</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider bg-gray-50">Prodi</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider bg-gray-50">Angkatan</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider bg-gray-50">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="m in enrollCandidates.data" :key="m.id" 
                                        @click="toggleEnrollMhs(m.id)"
                                        class="hover:bg-indigo-50 cursor-pointer transition-colors"
                                        :class="{'bg-indigo-50': selectedEnrollMhs.includes(m.id)}">
                                        <td class="px-6 py-3" @click.stop>
                                            <input type="checkbox" :checked="selectedEnrollMhs.includes(m.id)" @change="toggleEnrollMhs(m.id)"
                                                class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-6 py-3 font-mono text-sm text-gray-600">{{ m.nim }}</td>
                                        <td class="px-6 py-3 font-semibold text-gray-900">{{ m.nama }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-500">{{ m.prodi?.nama }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-500">{{ m.angkatan }}</td>
                                        <td class="px-6 py-3 hidden md:table-cell">
                                            <span :class="['px-2 py-1 rounded-lg text-xs font-bold', 
                                                m.status === 'aktif' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600']">
                                                {{ m.status?.toUpperCase() }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="enrollCandidates.data.length === 0 && !isLoadingCandidates">
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            Tidak ada mahasiswa ditemukan
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer w/ Pagination -->
                        <div class="px-6 py-4 border-t bg-gray-50 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="text-sm font-semibold text-gray-600">
                                    {{ selectedEnrollMhs.length }} terpilih
                                </div>
                                <!-- Simple Pagination -->
                                <div v-if="enrollCandidates.last_page > 1" class="flex items-center gap-2">
                                    <button @click="fetchEnrollCandidates(enrollCandidates.current_page - 1)" 
                                        :disabled="enrollCandidates.current_page === 1"
                                        class="px-2 py-1 border rounded hover:bg-white disabled:opacity-50 text-xs">Prev</button>
                                    <span class="text-xs text-gray-500">
                                        {{ enrollCandidates.current_page }} / {{ enrollCandidates.last_page }}
                                    </span>
                                    <button @click="fetchEnrollCandidates(enrollCandidates.current_page + 1)" 
                                        :disabled="enrollCandidates.current_page === enrollCandidates.last_page"
                                        class="px-2 py-1 border rounded hover:bg-white disabled:opacity-50 text-xs">Next</button>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <button @click="showEnrollMhsModal = false" class="px-5 py-2 border border-gray-200 rounded-xl font-semibold hover:bg-gray-100">
                                    Batal
                                </button>
                                <button @click="submitEnrollMhs" :disabled="selectedEnrollMhs.length === 0 || isEnrolling"
                                    class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 flex items-center gap-2">
                                    <CheckCircleIcon class="w-5 h-5" />
                                    {{ isEnrolling ? 'Menambahkan...' : 'Tambahkan' }}
                                </button>
                            </div>
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
