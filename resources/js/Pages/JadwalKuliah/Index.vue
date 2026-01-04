<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage, Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { 
    CalendarDaysIcon, ClockIcon, MapPinIcon, UserIcon,
    ChevronLeftIcon, ChevronRightIcon, CheckCircleIcon,
    AcademicCapIcon, BookOpenIcon, PrinterIcon, ArrowDownTrayIcon,
    XMarkIcon, PencilIcon, UserGroupIcon
} from '@heroicons/vue/24/outline';
import { PlusIcon } from '@heroicons/vue/24/solid';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
    todaySchedule: Array,
    monthlySchedule: Object,
    currentMonth: String,
    isDosen: Boolean,
    dosenName: String,
    canAddSchedule: Boolean,
    availableProdi: Array,
    availableDosens: Array,
    ruangans: Array,
    filters: Object,
});

const page = usePage();

// Current month for calendar navigation
const calendarMonth = ref(props.currentMonth);
const selectedDate = ref(new Date().toISOString().slice(0, 10)); // Default today
const isLoading = ref(false);

// Date range filter
const filterStartDate = ref(props.filters?.start_date || '');
const filterEndDate = ref(props.filters?.end_date || '');

const applyDateFilter = () => {
    if (!filterStartDate.value || !filterEndDate.value) return;
    
    router.get(route('jadwal.index'), {
        start_date: filterStartDate.value,
        end_date: filterEndDate.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => isLoading.value = true,
        onFinish: () => isLoading.value = false,
    });
};

const clearDateFilter = () => {
    filterStartDate.value = '';
    filterEndDate.value = '';
    router.get(route('jadwal.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(() => props.currentMonth, (newVal) => {
    calendarMonth.value = newVal;
});

// Navigate calendar
const navigateMonth = (direction) => {
    const current = new Date(calendarMonth.value + '-01');
    if (direction === 'prev') {
        current.setMonth(current.getMonth() - 1);
    } else {
        current.setMonth(current.getMonth() + 1);
    }
    const newMonth = current.toISOString().slice(0, 7);
    
    router.get(route('jadwal.index'), { month: newMonth }, { 
        preserveState: true,
        preserveScroll: true,
        only: ['monthlySchedule', 'currentMonth'],
        onStart: () => isLoading.value = true,
        onFinish: () => isLoading.value = false
    });
};

// Format month name
const formatMonthName = (monthStr) => {
    const date = new Date(monthStr + '-01');
    return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
};

// Calendar days
const calendarDays = computed(() => {
    const monthDate = new Date(calendarMonth.value + '-01');
    const year = monthDate.getFullYear();
    const month = monthDate.getMonth();
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    
    const days = [];
    
    // Add empty cells for days before the 1st
    const startDay = firstDay.getDay(); // 0 = Sunday
    for (let i = 0; i < startDay; i++) {
        days.push({ date: null, day: null, events: [] });
    }
    
    // Add actual days
    for (let d = 1; d <= lastDay.getDate(); d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const events = props.monthlySchedule?.[dateStr] || [];
        days.push({
            date: dateStr,
            day: d,
            events: events,
            isToday: dateStr === new Date().toISOString().slice(0, 10),
            isSelected: dateStr === selectedDate.value,
            hasEvents: events.length > 0,
            loadLevel: Math.min(events.length, 3) // 0-3 for dot intensity
        });
    }
    
    return days;
});

// Selected Day Schedule
const selectedDaySchedule = computed(() => {
    // If date filter is active, show ALL filtered items (flat list)
    if (props.filters?.start_date && props.filters?.end_date) {
        // Flatten all monthlySchedule entries into a single array
        const allItems = [];
        for (const [date, items] of Object.entries(props.monthlySchedule || {})) {
            for (const item of items) {
                allItems.push({ ...item, _displayDate: date });
            }
        }
        // Sort by date
        return allItems.sort((a, b) => new Date(a._displayDate) - new Date(b._displayDate));
    }
    
    // Normal mode: show only selected date
    if (!selectedDate.value) return [];
    return props.monthlySchedule?.[selectedDate.value] || [];
});

const isFilterActive = computed(() => props.filters?.start_date && props.filters?.end_date);

const selectDate = (date) => {
    if (date) selectedDate.value = date;
};

// Format time
const formatTime = (time) => {
    if (!time) return '-';
    return time.slice(0, 5);
};

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { 
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });
};

const getRelativeDateLabel = (date) => {
    const d = new Date(date);
    const today = new Date();
    today.setHours(0,0,0,0);
    d.setHours(0,0,0,0);
    
    const diff = (d - today) / (1000 * 60 * 60 * 24);
    if (diff === 0) return 'Hari Ini';
    if (diff === 1) return 'Besok';
    if (diff === -1) return 'Kemarin';
    return formatDate(date);
}

// ========== DETAIL MODAL (Inline View) ==========
const showDetailModal = ref(false);
const selectedMeeting = ref(null);
const detailTab = ref('dosen'); // 'dosen' or 'mahasiswa'
const mahasiswaAbsensis = ref([]);
const loadingAbsensis = ref(false);

// Form for dosen attendance (for custom modal)
const dosenAttendForm = useForm({
    dosen_jam_masuk: '',
    dosen_jam_keluar: '',
});

const showCustomTimeModal = ref(false);

const openDetailModal = async (meeting) => {
    selectedMeeting.value = meeting;
    detailTab.value = 'dosen';
    dosenAttendForm.dosen_jam_masuk = meeting.dosen_jam_masuk || meeting.jam_mulai || '';
    dosenAttendForm.dosen_jam_keluar = meeting.dosen_jam_keluar || meeting.jam_selesai || '';
    showDetailModal.value = true;
    
    // Fetch mahasiswa absensis for this meeting
    loadingAbsensis.value = true;
    try {
        const response = await fetch(`/api/pertemuan/${meeting.id}/absensis`);
        if (response.ok) {
            mahasiswaAbsensis.value = await response.json();
        }
    } catch (e) {
        console.log('Could not load absensis');
    }
    loadingAbsensis.value = false;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedMeeting.value = null;
};

// Get current time in HH:MM format
const getCurrentTime = () => {
    const now = new Date();
    return now.toTimeString().slice(0, 5);
};

const getWaktuSesi = (jamMulai) => {
    if (!jamMulai) return '';
    const hour = parseInt(jamMulai.split(':')[0]);
    if (hour < 12) return 'Pagi';
    if (hour < 15) return 'Siang';
    if (hour < 18) return 'Sore';
    return 'Malam';
};

// Quick Absen Masuk - set to current time
const absenMasuk = () => {
    dosenAttendForm.dosen_jam_masuk = getCurrentTime();
    dosenAttendForm.dosen_jam_keluar = selectedMeeting.value.dosen_jam_keluar || '';
    saveDosenAttendance();
};

// Quick Absen Selesai - set to current time
const absenSelesai = () => {
    dosenAttendForm.dosen_jam_masuk = selectedMeeting.value.dosen_jam_masuk || '';
    dosenAttendForm.dosen_jam_keluar = getCurrentTime();
    saveDosenAttendance();
};

// Set attendance according to scheduled class time
const absenSesuaiJadwal = () => {
    dosenAttendForm.dosen_jam_masuk = selectedMeeting.value.jam_mulai || '';
    dosenAttendForm.dosen_jam_keluar = selectedMeeting.value.jam_selesai || '';
    saveDosenAttendance();
};

// Toast State
const toastMessage = ref('');
const showToast = ref(false);
const showNotification = (message) => {
    toastMessage.value = message;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 3000);
};

// Save dosen attendance via API
const saveDosenAttendance = () => {
    dosenAttendForm.post(route('absensi.dosen-attendance.store', selectedMeeting.value.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selectedMeeting.value.dosen_hadir = true;
            // Update local state if needed (though preserveState handles it, we update reactive ref for instant UI feedback)
            if (dosenAttendForm.dosen_jam_masuk) selectedMeeting.value.dosen_jam_masuk = dosenAttendForm.dosen_jam_masuk;
            if (dosenAttendForm.dosen_jam_keluar) selectedMeeting.value.dosen_jam_keluar = dosenAttendForm.dosen_jam_keluar;
            
            showCustomTimeModal.value = false;
            showNotification('Kehadiran dosen berhasil disimpan!');
        },
    });
};

// Reset dosen attendance
const resetDosenAbsensi = async () => {
    if (!confirm('Yakin ingin mereset absensi dosen?')) return;
    
    try {
        const response = await fetch(`/api/pertemuan/${selectedMeeting.value.id}/reset-dosen-attendance`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });
        if (response.ok) {
            selectedMeeting.value.dosen_hadir = false;
            selectedMeeting.value.dosen_jam_masuk = null;
            selectedMeeting.value.dosen_jam_keluar = null;
            dosenAttendForm.dosen_jam_masuk = '';
            dosenAttendForm.dosen_jam_keluar = '';
            showNotification('Absensi dosen berhasil direset!');
        }
    } catch (e) {
        showNotification('Gagal mereset absensi');
    }
};

// Update status mahasiswa single
const updateMahasiswaStatus = async (mhsId, status) => {
    // Optimistic update
    const mhs = mahasiswaAbsensis.value.find(m => m.id === mhsId);
    const oldStatus = mhs.status;
    if (mhs) mhs.status = status === 'null' ? null : status;

    try {
        const response = await fetch(`/api/pertemuan/${selectedMeeting.value.id}/update-mahasiswa-status`, {
            method: 'POST',
            body: JSON.stringify({ mahasiswa_id: mhsId, status }),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });
        
        if (!response.ok) {
            if (mhs) mhs.status = oldStatus; // Revert
            showNotification('Gagal menyimpan kehadiran');
        } else {
            // Optional: showNotification('Tersimpan'); // User asked for auto-save without distinct click, usually implies silent save or subtle indicator
        }
    } catch (e) {
        if (mhs) mhs.status = oldStatus; // Revert
        showNotification('Gagal menghubungi server');
    }
};

// Bulk update status mahasiswa
const bulkUpdateMahasiswaStatus = async (status) => {
    // Optimistic update all
    const oldStatuses = mahasiswaAbsensis.value.map(m => ({ id: m.id, status: m.status }));
    
    mahasiswaAbsensis.value.forEach(m => {
        m.status = status === 'null' ? null : status;
    });

    try {
        const response = await fetch(`/api/pertemuan/${selectedMeeting.value.id}/bulk-update-mahasiswa-status`, {
            method: 'POST',
            body: JSON.stringify({ status }),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });

        if (response.ok) {
             showNotification('Status mahasiswa diperbarui untuk semua');
        } else {
            // Revert all
            mahasiswaAbsensis.value.forEach(m => {
                 const old = oldStatuses.find(o => o.id === m.id);
                 if (old) m.status = old.status;
            });
            showNotification('Gagal update massal');
        }
    } catch (e) {
        // Revert all
        mahasiswaAbsensis.value.forEach(m => {
                const old = oldStatuses.find(o => o.id === m.id);
                if (old) m.status = old.status;
        });
        showNotification('Gagal update massal');
    }
};

// Navigate to full absensi page (for mahasiswa attendance)
const goToAbsensi = (meetingId) => {
    router.visit(route('absensi.pertemuan', meetingId));
};

// Create Schedule Logic
// Create Schedule Logic

const showCreateModal = ref(false);
const createForm = useForm({
    prodi_id: '',
    kelas_id: '',
    mata_kuliah_id: '',
    dosen_ids: [],
    tanggal: new Date().toISOString().slice(0, 10),
    jam_mulai: '08:00',
    jam_selesai: '10:00',
    ruangan_id: '',
    pertemuan_ke: 1,
    materi: '',
    metode_pembelajaran: 'offline',
});

// Search states
const dosenSearch = ref('');
const ruanganSearch = ref('');

// Filter Kelas based on selected Prodi
const availableKelas = computed(() => {
    if (!createForm.prodi_id) return [];
    const selectedProdi = props.availableProdi?.find(p => p.id === createForm.prodi_id);
    return selectedProdi?.kelas || [];
});

// Filter Subjects based on selected Class
const availableSubjects = computed(() => {
    if (!createForm.kelas_id) return [];
    const selectedClass = availableKelas.value.find(k => k.id === createForm.kelas_id);
    return selectedClass?.mata_kuliahs || [];
});

// Filter Dosen by search
const filteredDosens = computed(() => {
    if (!dosenSearch.value) return props.availableDosens || [];
    const search = dosenSearch.value.toLowerCase();
    return (props.availableDosens || []).filter(d => 
        d.nama.toLowerCase().includes(search) || 
        (d.nidn && d.nidn.toLowerCase().includes(search))
    );
});

// Filter Ruangan by search
const filteredRuangans = computed(() => {
    if (!ruanganSearch.value) return props.ruangans || [];
    const search = ruanganSearch.value.toLowerCase();
    return (props.ruangans || []).filter(r => r.nama.toLowerCase().includes(search));
});

// Toggle dosen selection
const toggleDosen = (dosenId) => {
    const idx = createForm.dosen_ids.indexOf(dosenId);
    if (idx === -1) {
        createForm.dosen_ids.push(dosenId);
    } else {
        createForm.dosen_ids.splice(idx, 1);
    }
};

const isDosenSelected = (dosenId) => createForm.dosen_ids.includes(dosenId);

const openCreateModal = () => {
    createForm.reset();
    createForm.tanggal = selectedDate.value || new Date().toISOString().slice(0, 10);
    dosenSearch.value = '';
    ruanganSearch.value = '';
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post(route('jadwal.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            alert('Jadwal berhasil ditambahkan!');
        },
        onError: (errors) => {
            console.log('Form errors:', errors);
        }
    });
};

// Edit Schedule Logic
const showEditModal = ref(false);
const dosenSearchEdit = ref('');

const editForm = useForm({
    tanggal: '',
    jam_mulai: '',
    jam_selesai: '',
    mode: 'offline',
    ruangan_id: '',
    catatan: '',
    dosen_ids: [], // Changed to array for UI consistency
    pertemuan_ke: '',
});

// Computed for Dosen in Edit Modal
const filteredDosensEdit = computed(() => {
    if (!dosenSearchEdit.value) return props.availableDosens || [];
    const search = dosenSearchEdit.value.toLowerCase();
    return (props.availableDosens || []).filter(d => 
        d.nama.toLowerCase().includes(search) || 
        (d.nidn && d.nidn.toLowerCase().includes(search))
    );
});

// Toggle Dosen Selection (Multi)
const toggleDosenEdit = (dosen) => {
    const index = editForm.dosen_ids.indexOf(dosen.id);
    if (index === -1) {
        editForm.dosen_ids.push(dosen.id);
    } else {
        editForm.dosen_ids.splice(index, 1);
    }
};

const openEditModal = (item = null) => {
    if (item && item.id) {
        selectedMeeting.value = item;
    }
    const m = selectedMeeting.value;
    if (!m) return;

    // Fix Date Parsing (handle various formats)
    let dateVal = m.tanggal;
    if (dateVal && dateVal.includes('T')) {
        dateVal = dateVal.split('T')[0];
    }
    editForm.tanggal = dateVal;

    editForm.jam_mulai = m.jam_mulai ? m.jam_mulai.slice(0, 5) : (m.jadwal?.jam_mulai?.slice(0, 5) || '');
    editForm.jam_selesai = m.jam_selesai ? m.jam_selesai.slice(0, 5) : (m.jadwal?.jam_selesai?.slice(0, 5) || '');
    editForm.mode = m.mode;
    editForm.ruangan_id = m.ruangan_id || m.jadwal?.ruangan_id || '';
    editForm.catatan = m.catatan || '';
    
    // Populate dosen_ids (if single existing, put in array)
    editForm.dosen_ids = m.dosen_id ? [m.dosen_id] : [];
    
    editForm.pertemuan_ke = m.pertemuan_ke;
    dosenSearchEdit.value = ''; // Reset search
    
    // Close detail modal if open
    if (showDetailModal.value) showDetailModal.value = false;
    
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!selectedMeeting.value) return;
    
    editForm.transform((data) => ({
        ...data,
        dosen_id: data.dosen_ids.length > 0 ? data.dosen_ids[0] : null
    })).put(route('jadwal.update', selectedMeeting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            showNotification('Jadwal berhasil diperbarui');
        },
        onError: (errors) => {
            console.error(errors);
            showNotification('Gagal memperbarui jadwal');
        }
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Jadwal Kuliah" />

        <div class="space-y-6">
            <!-- Header with Quick Actions -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                     <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                        Jadwal & Agenda
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1 font-medium">
                        {{ isDosen ? `Halo ${dosenName}, ini jadwal mengajar Anda.` : 'Jadwal perkuliahan semester aktif.' }}
                    </p>
                </div>
                <!-- Quick Actions -->
                <div class="flex gap-2">
                    <button v-if="canAddSchedule" @click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 flex items-center gap-2 font-bold transition shadow-lg shadow-indigo-500/20">
                        <PlusIcon class="w-5 h-5" />
                        Tambah Jadwal
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Left: Calendar Widget (4 cols) -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl shadow-gray-100/50 dark:shadow-none border border-gray-100 dark:border-gray-800 overflow-hidden">
                        <!-- Calendar Header -->
                        <div class="px-6 py-6 bg-gradient-to-br from-indigo-600 to-purple-700 text-white relative">
                            <!-- Loading Overlay -->
                            <div v-if="isLoading" class="absolute inset-0 bg-indigo-600/50 backdrop-blur-sm z-20 flex items-center justify-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
                            </div>

                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl font-bold flex items-center gap-2">
                                    <CalendarDaysIcon class="w-6 h-6 text-indigo-200" />
                                    {{ formatMonthName(calendarMonth).split(' ')[0] }}
                                    <span class="text-indigo-200">{{ formatMonthName(calendarMonth).split(' ')[1] }}</span>
                                </h2>
                                <div class="flex gap-1">
                                    <button @click="navigateMonth('prev')" class="p-2 hover:bg-white/10 rounded-lg transition">
                                        <ChevronLeftIcon class="w-5 h-5" />
                                    </button>
                                    <button @click="navigateMonth('next')" class="p-2 hover:bg-white/10 rounded-lg transition">
                                        <ChevronRightIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Day names -->
                            <div class="grid grid-cols-7 text-center">
                                <span v-for="d in ['M','S','S','R','K','J','S']" :key="d" 
                                    class="text-xs font-bold text-indigo-200">
                                    {{ d }}
                                </span>
                            </div>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="p-4 bg-white dark:bg-gray-900">
                            <div class="grid grid-cols-7 gap-y-2 gap-x-1">
                                <div v-for="(cell, idx) in calendarDays" :key="idx" 
                                     class="aspect-square flex flex-col items-center justify-center relative">
                                    
                                    <button v-if="cell.date"
                                        @click="selectDate(cell.date)"
                                        :class="[
                                            'w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all relative z-10',
                                            cell.isSelected 
                                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/40 scale-110' 
                                                : cell.isToday
                                                    ? 'bg-indigo-50 text-indigo-700 border-2 border-indigo-200'
                                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800',
                                            cell.hasEvents && !cell.isSelected && !cell.isToday ? 'font-black' : ''
                                        ]">
                                        {{ cell.day }}
                                        
                                        <!-- Load indicators (dots) -->
                                        <div v-if="cell.hasEvents && !cell.isSelected" class="absolute -bottom-1 flex gap-0.5">
                                            <div v-for="n in Math.min(cell.events.length, 3)" :key="n" 
                                                class="w-1 h-1 rounded-full bg-indigo-400"></div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Legend -->
                        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                            <div class="flex items-center justify-center gap-4 text-xs text-gray-500">
                                <span class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-full bg-indigo-400"></div> Ada Jadwal</span>
                                <span class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-full border-2 border-indigo-200 bg-indigo-50"></div> Hari Ini</span>
                            </div>
                        </div>
                        
                        <!-- Date Range Filter -->
                        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <CalendarDaysIcon class="w-4 h-4" />
                                Filter Rentang Tanggal
                            </h4>
                            <div class="space-y-3">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-gray-400 mb-1 block">Dari</label>
                                        <input type="date" v-model="filterStartDate" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:border-indigo-500 focus:ring-0">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-400 mb-1 block">Sampai</label>
                                        <input type="date" v-model="filterEndDate" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:border-indigo-500 focus:ring-0">
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="applyDateFilter" :disabled="!filterStartDate || !filterEndDate" :class="['flex-1 px-3 py-2 rounded-lg text-sm font-semibold transition', (!filterStartDate || !filterEndDate) ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-indigo-600 text-white hover:bg-indigo-700']">
                                        Terapkan
                                    </button>
                                    <button v-if="filters?.start_date" @click="clearDateFilter" class="px-3 py-2 rounded-lg text-sm font-medium border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                        Reset
                                    </button>
                                </div>
                                <p v-if="filters?.start_date && filters?.end_date" class="text-xs text-indigo-600 font-medium">
                                    Menampilkan: {{ filters.start_date }} s/d {{ filters.end_date }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Detailed Agenda (8 cols) -->
                <div class="lg:col-span-8">
                    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 min-h-[600px] flex flex-col">
                        <!-- Agenda Header -->
                        <div class="px-8 py-8 border-b border-gray-100 dark:border-gray-800">
                            <!-- Filter Mode Header -->
                            <template v-if="isFilterActive">
                                <h2 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                                    <span class="text-indigo-600">Hasil Filter</span>
                                </h2>
                                <p class="text-gray-400 mt-2 font-medium">
                                    {{ selectedDaySchedule.length }} Jadwal dari {{ filters.start_date }} s/d {{ filters.end_date }}
                                </p>
                            </template>
                            <!-- Normal Mode Header -->
                            <template v-else>
                                <h2 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                                    <span class="text-indigo-600">{{ getRelativeDateLabel(selectedDate) }}</span>
                                    <span class="text-gray-300 font-light">|</span>
                                    <span class="text-gray-500 font-medium text-lg">{{ formatDate(selectedDate) }}</span>
                                </h2>
                                <p class="text-gray-400 mt-2 font-medium" v-if="selectedDaySchedule.length > 0">
                                    {{ selectedDaySchedule.length }} Mata Kuliah dijadwalkan
                                </p>
                            </template>
                        </div>

                        <!-- Agenda List (Timeline) -->
                        <div class="p-8 flex-1">
                            <div v-if="selectedDaySchedule.length > 0" class="space-y-8 relative before:absolute before:left-[19px] before:top-2 before:bottom-0 before:w-0.5 before:bg-gray-100 dark:before:bg-gray-800">
                                <div v-for="(item, index) in selectedDaySchedule" :key="item.id" 
                                     class="relative pl-12 group transition cursor-pointer"
                                     @click="openDetailModal(item)">
                                    
                                    <!-- Timeline Dot -->
                                    <div :class="[
                                        'absolute left-0 top-1.5 w-10 h-10 rounded-full border-4 border-white dark:border-gray-900 flex items-center justify-center z-10 box-content transition-transform group-hover:scale-110',
                                        item.has_attendance 
                                            ? 'bg-emerald-500 shadow-lg shadow-emerald-500/30' 
                                            : 'bg-indigo-500 shadow-lg shadow-indigo-500/30'
                                    ]">
                                        <CheckCircleIcon v-if="item.has_attendance" class="w-6 h-6 text-white" />
                                        <ClockIcon v-else class="w-6 h-6 text-white" />
                                    </div>

                                    <!-- Content Card -->
                                    <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-6 hover:bg-white hover:shadow-xl hover:shadow-gray-200/50 dark:hover:bg-gray-800/80 dark:hover:shadow-black/50 transition border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900/50 group-active:scale-[0.99]">
                                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                                            <div>
                                                <div class="flex items-center gap-3 mb-2">
                                                    <span class="px-3 py-1 bg-white dark:bg-gray-900 rounded-lg text-xs font-bold text-gray-500 uppercase tracking-wider border border-gray-100 dark:border-gray-700">
                                                        {{ item.jadwal?.mata_kuliah?.kode }}
                                                    </span>
                                                    <div class="flex flex-col sm:flex-row gap-1">
                                                        <span :class="[
                                                            'px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider border',
                                                            item.dosen_hadir 
                                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                                                : 'bg-rose-50 text-rose-700 border-rose-200'
                                                        ]">
                                                            Dosen: {{ item.dosen_hadir ? 'Hadir' : 'Belum' }}
                                                        </span>
                                                        <span :class="[
                                                            'px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider border',
                                                            (item.attendance_count || 0) >= (item.student_count || 0) && (item.attendance_count || 0) > 0
                                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                                                : (item.attendance_count || 0) > 0 
                                                                    ? 'bg-blue-50 text-blue-700 border-blue-200' 
                                                                    : 'bg-gray-50 text-gray-500 border-gray-200'
                                                        ]">
                                                            Mhs: {{ item.attendance_count || 0 }}/{{ item.student_count || 0 }}
                                                        </span>
                                                    </div>
                                                    <button @click.stop="openEditModal(item)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition ml-auto" title="Edit Jadwal">
                                                        <PencilIcon class="w-4 h-4" />
                                                    </button>
                                                </div>
                                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 group-hover:text-indigo-600 transition">
                                                    {{ item.jadwal?.mata_kuliah?.nama }}
                                                </h3>
                                                
                                                <!-- Date & Time Info -->
                                                <div class="flex flex-wrap items-center gap-x-6 text-sm text-gray-500">
                                                    <div v-if="isFilterActive" class="flex items-center gap-2 text-indigo-600 font-semibold">
                                                        <CalendarDaysIcon class="w-4 h-4" />
                                                        <span>{{ formatDate(item._displayDate || item.tanggal) }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <ClockIcon class="w-4 h-4 text-gray-400" />
                                                        <span>{{ formatTime(item.jam_mulai || item.jadwal?.jam_mulai) }} - {{ formatTime(item.jam_selesai || item.jadwal?.jam_selesai) }}</span>
                                                    </div>
                                                    
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <UserIcon class="w-4 h-4 text-gray-400" />
                                                        <span>{{ item.dosen?.nama }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <AcademicCapIcon class="w-4 h-4 text-gray-400" />
                                                        <span>{{ item.jadwal?.kelas_model?.nama }}</span>
                                                        <span v-if="item.jadwal?.kelas_model?.prodi?.kode" class="px-1.5 py-0.5 text-[10px] font-bold bg-gray-100 dark:bg-gray-800 rounded">
                                                            {{ item.jadwal?.kelas_model?.prodi?.kode }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <MapPinIcon class="w-4 h-4 text-gray-400" />
                                                        <span :class="{'text-blue-600 font-medium': item.mode === 'online'}">
                                                            {{ item.mode === 'online' ? 'Online' : (item.ruangan?.nama || item.jadwal?.ruangan?.nama || 'Ruang Belum Diset') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex flex-col items-center justify-center p-3 bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700 min-w-[80px]">
                                                <span class="text-2xl font-black text-indigo-600">{{ item.pertemuan_ke }}</span>
                                                <span class="text-[10px] text-gray-400 font-bold uppercase">Pertemuan</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="h-full flex flex-col items-center justify-center py-20 text-center text-gray-400">
                                <div class="w-24 h-24 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-6">
                                    <BookOpenIcon class="w-10 h-10 text-gray-300" />
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Tidak ada jadwal kuliah</h3>
                                <p class="max-w-xs mx-auto">Pada tanggal <strong>{{ formatDate(selectedDate) }}</strong> tidak ada kegiatan perkuliahan yang dijadwalkan.</p>
                                
                                <button v-if="selectedDate !== new Date().toISOString().slice(0, 10)" 
                                    @click="selectDate(new Date().toISOString().slice(0, 10))"
                                    class="mt-6 text-indigo-600 font-bold hover:underline">
                                    Kembali ke Hari Ini
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Schedule Slide-Over Panel -->
        <TransitionRoot as="template" :show="showCreateModal">
            <Dialog as="div" class="relative z-50" @close="showCreateModal = false">
                <TransitionChild as="template" enter="ease-in-out duration-500" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-500" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-hidden">
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                            <TransitionChild as="template" enter="transform transition ease-in-out duration-500" enter-from="translate-x-full" enter-to="translate-x-0" leave="transform transition ease-in-out duration-500" leave-from="translate-x-0" leave-to="translate-x-full">
                                <DialogPanel class="pointer-events-auto w-screen max-w-md">
                                    <div class="flex h-full flex-col bg-white dark:bg-gray-900 shadow-2xl">
                                        
                                        <!-- Header -->
                                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-6">
                                            <div class="flex items-center justify-between">
                                                <DialogTitle class="text-xl font-bold text-white flex items-center gap-3">
                                                    <div class="p-2 bg-white/20 rounded-xl">
                                                        <PlusIcon class="h-6 w-6 text-white" />
                                                    </div>
                                                    Tambah Jadwal
                                                </DialogTitle>
                                                <button @click="showCreateModal = false" class="rounded-lg p-2 text-white/80 hover:text-white hover:bg-white/10 transition">
                                                    <XMarkIcon class="h-6 w-6" />
                                                </button>
                                            </div>
                                            <p class="mt-2 text-indigo-100 text-sm">Buat jadwal pertemuan baru secara manual.</p>
                                        </div>

                                        <!-- Form Body -->
                                        <div class="flex-1 overflow-y-auto px-6 py-6">
                                            <div class="space-y-6">
                                                
                                                <!-- Validation Errors Alert -->
                                                <div v-if="Object.keys(createForm.errors).length > 0" class="bg-red-50 border border-red-200 rounded-xl p-4">
                                                    <h4 class="text-sm font-bold text-red-700 mb-2">Terjadi kesalahan:</h4>
                                                    <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                                                        <li v-for="(error, field) in createForm.errors" :key="field">{{ error }}</li>
                                                    </ul>
                                                </div>
                                                <!-- Section: Program Studi, Kelas & Mata Kuliah -->
                                                <div class="space-y-4">
                                                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Program Studi & Kelas</h3>
                                                    
                                                    <!-- Prodi -->
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Program Studi</label>
                                                        <select v-model="createForm.prodi_id" @change="createForm.kelas_id = ''; createForm.mata_kuliah_id = ''" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium">
                                                            <option value="" disabled>— Pilih Program Studi —</option>
                                                            <option v-for="p in availableProdi" :key="p.id" :value="p.id">
                                                                {{ p.nama }} ({{ p.kode }})
                                                            </option>
                                                        </select>
                                                    </div>
                                                    
                                                    <!-- Kelas (appears after prodi is selected) -->
                                                    <div v-if="createForm.prodi_id">
                                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Kelas</label>
                                                        <select v-model="createForm.kelas_id" @change="createForm.mata_kuliah_id = ''" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium">
                                                            <option value="" disabled>— Pilih Kelas —</option>
                                                            <option v-for="k in availableKelas" :key="k.id" :value="k.id">
                                                                {{ k.nama }} ({{ k.kode }})
                                                            </option>
                                                        </select>
                                                        <p v-if="availableKelas.length === 0" class="text-xs text-amber-600 mt-2 flex items-center gap-1">
                                                            <span class="inline-block w-2 h-2 bg-amber-500 rounded-full"></span>
                                                            Tidak ada kelas di prodi ini untuk semester aktif.
                                                        </p>
                                                    </div>

                                                    <!-- Mata Kuliah (appears after kelas is selected) -->
                                                    <div v-if="createForm.kelas_id">
                                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Mata Kuliah</label>
                                                        <select v-model="createForm.mata_kuliah_id" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium">
                                                            <option value="" disabled>— Pilih Mata Kuliah —</option>
                                                            <option v-for="mk in availableSubjects" :key="mk.id" :value="mk.id">
                                                                {{ mk.nama }} ({{ mk.kode }})
                                                            </option>
                                                        </select>
                                                        <p v-if="availableSubjects.length === 0" class="text-xs text-amber-600 mt-2 flex items-center gap-1">
                                                            <span class="inline-block w-2 h-2 bg-amber-500 rounded-full"></span>
                                                            Tidak ada mata kuliah di kelas ini.
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Divider -->
                                                <div class="border-t border-gray-100 dark:border-gray-800"></div>

                                                <!-- Section: Waktu -->
                                                <div class="space-y-4">
                                                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Waktu Pertemuan</h3>
                                                    
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <!-- Tanggal -->
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Tanggal</label>
                                                            <input type="date" v-model="createForm.tanggal" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium">
                                                        </div>
                                                        <!-- Pertemuan Ke -->
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Pertemuan Ke</label>
                                                            <input type="number" v-model="createForm.pertemuan_ke" min="1" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium text-center">
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-4">
                                                        <!-- Jam Mulai -->
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Jam Mulai</label>
                                                            <input type="time" v-model="createForm.jam_mulai" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium text-center">
                                                        </div>
                                                        <!-- Jam Selesai -->
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Jam Selesai</label>
                                                            <input type="time" v-model="createForm.jam_selesai" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium text-center">
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Divider -->
                                                <div class="border-t border-gray-100 dark:border-gray-800"></div>

                                                <!-- Section: Dosen -->
                                                <div class="space-y-4">
                                                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Dosen Pengampu</h3>
                                                    
                                                    <!-- Selected Dosen Tags -->
                                                    <div v-if="createForm.dosen_ids.length > 0" class="flex flex-wrap gap-2">
                                                        <span v-for="dosenId in createForm.dosen_ids" :key="dosenId" class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm font-medium">
                                                            {{ availableDosens?.find(d => d.id === dosenId)?.nama }}
                                                            <button type="button" @click="toggleDosen(dosenId)" class="ml-1 text-indigo-500 hover:text-indigo-700">
                                                                <XMarkIcon class="w-4 h-4" />
                                                            </button>
                                                        </span>
                                                    </div>
                                                    
                                                    <!-- Search Input -->
                                                    <div>
                                                        <input type="text" v-model="dosenSearch" placeholder="Cari dosen..." class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm">
                                                    </div>
                                                    
                                                    <!-- Dosen List -->
                                                    <div class="max-h-40 overflow-y-auto rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                                        <div v-for="d in filteredDosens" :key="d.id" 
                                                             @click="toggleDosen(d.id)"
                                                             :class="['px-4 py-2 cursor-pointer transition flex items-center justify-between text-sm', isDosenSelected(d.id) ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300']">
                                                            <span>{{ d.nama }} <span class="text-gray-400 text-xs">({{ d.nidn }})</span></span>
                                                            <CheckCircleIcon v-if="isDosenSelected(d.id)" class="w-5 h-5 text-indigo-600" />
                                                        </div>
                                                        <div v-if="filteredDosens.length === 0" class="px-4 py-3 text-gray-400 text-sm text-center">
                                                            Tidak ada dosen ditemukan
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Divider -->
                                                <div class="border-t border-gray-100 dark:border-gray-800"></div>

                                                <!-- Section: Detail -->
                                                <div class="space-y-4">
                                                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Detail Pertemuan</h3>
                                                    
                                                    <!-- Metode Pembelajaran (moved before Ruangan) -->
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Metode Pembelajaran</label>
                                                        <div class="grid grid-cols-3 gap-2">
                                                            <label :class="['flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 cursor-pointer transition font-medium text-sm', createForm.metode_pembelajaran === 'offline' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-700 text-gray-500 hover:border-gray-300']">
                                                                <input type="radio" v-model="createForm.metode_pembelajaran" value="offline" class="sr-only">
                                                                <span>Offline</span>
                                                            </label>
                                                            <label :class="['flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 cursor-pointer transition font-medium text-sm', createForm.metode_pembelajaran === 'online' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-700 text-gray-500 hover:border-gray-300']">
                                                                <input type="radio" v-model="createForm.metode_pembelajaran" value="online" class="sr-only">
                                                                <span>Online</span>
                                                            </label>
                                                            <label :class="['flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 cursor-pointer transition font-medium text-sm', createForm.metode_pembelajaran === 'hybrid' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-700 text-gray-500 hover:border-gray-300']">
                                                                <input type="radio" v-model="createForm.metode_pembelajaran" value="hybrid" class="sr-only">
                                                                <span>Hybrid</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Ruangan with Search (only for offline/hybrid) -->
                                                    <div v-if="createForm.metode_pembelajaran !== 'online'">
                                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Ruangan</label>
                                                        <input type="text" v-model="ruanganSearch" placeholder="Cari ruangan..." class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm mb-2">
                                                        <select v-model="createForm.ruangan_id" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium">
                                                            <option value="">— Pilih Ruangan —</option>
                                                            <option v-for="r in filteredRuangans" :key="r.id" :value="r.id">
                                                                {{ r.nama }}
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <!-- Materi -->
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Materi / Topik</label>
                                                        <textarea v-model="createForm.materi" rows="3" placeholder="Contoh: Pengenalan Algoritma dan Struktur Data" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm resize-none"></textarea>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                        <!-- Footer -->
                                        <div class="border-t border-gray-100 dark:border-gray-800 px-6 py-4 bg-gray-50 dark:bg-gray-800/50">
                                            <div class="flex gap-3">
                                                <button @click="showCreateModal = false" class="flex-1 px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                                    Batal
                                                </button>
                                                <button @click="submitCreate" :disabled="createForm.processing || !createForm.kelas_id || !createForm.mata_kuliah_id" :class="['flex-1 px-4 py-3 rounded-xl font-bold transition flex items-center justify-center gap-2', (createForm.processing || !createForm.kelas_id || !createForm.mata_kuliah_id) ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-500/30']">
                                                    <span v-if="createForm.processing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
                                                    {{ createForm.processing ? 'Menyimpan...' : 'Simpan Jadwal' }}
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </DialogPanel>
                            </TransitionChild>
                        </div>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- ============ DETAIL MODAL (Inline View) ============ -->
        <TransitionRoot appear :show="showDetailModal" as="template">
            <Dialog as="div" class="relative z-50" @close="closeDetailModal">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                    leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-hidden">
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                            <TransitionChild
                                as="template"
                                enter="transform transition ease-in-out duration-300"
                                enter-from="translate-x-full" enter-to="translate-x-0"
                                leave="transform transition ease-in-out duration-200"
                                leave-from="translate-x-0" leave-to="translate-x-full">
                                <DialogPanel class="pointer-events-auto w-screen max-w-lg">
                                    <div class="flex h-full flex-col bg-white dark:bg-gray-900 shadow-2xl">
                                        
                                        <!-- Header -->
                                        <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 px-6 py-5">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <DialogTitle class="text-xl font-bold text-white">Detail Pertemuan</DialogTitle>
                                                    <p class="mt-1 text-indigo-100 text-sm">Lihat dan kelola jadwal tanpa pindah halaman.</p>
                                                </div>
                                                <button @click="closeDetailModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg transition">
                                                    <XMarkIcon class="w-6 h-6" />
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Body -->
                                        <div v-if="selectedMeeting" class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                                            
                                            <!-- Meeting Info -->
                                            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-5 border border-indigo-100 dark:border-indigo-800">
                                                <div class="flex items-start justify-between mb-3">
                                                    <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ selectedMeeting.jadwal?.mata_kuliah?.nama }}</h3>
                                                    <span class="px-2 py-1 bg-white/50 dark:bg-black/20 rounded-lg text-xs font-bold text-indigo-600 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700">
                                                        {{ getWaktuSesi(selectedMeeting.jam_mulai) }}
                                                    </span>
                                                </div>
                                                <div class="grid grid-cols-2 gap-4 text-sm">
                                                    <div>
                                                        <p class="text-gray-500">Kelas</p>
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ selectedMeeting.jadwal?.kelas_model?.nama }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-gray-500">Pertemuan ke-</p>
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ selectedMeeting.pertemuan_ke }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-gray-500">Tanggal</p>
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ formatDate(selectedMeeting.tanggal) }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-gray-500">Jam</p>
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ formatTime(selectedMeeting.jam_mulai) }} - {{ formatTime(selectedMeeting.jam_selesai) }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-gray-500">Dosen</p>
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ selectedMeeting.dosen?.nama || '-' }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-gray-500">Ruangan</p>
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ selectedMeeting.ruangan?.nama || '-' }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status Badges -->
                                            <div class="flex gap-3">
                                                <span :class="['px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-2', selectedMeeting.has_attendance ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                                                    <CheckCircleIcon v-if="selectedMeeting.has_attendance" class="w-4 h-4" />
                                                    <ClockIcon v-else class="w-4 h-4" />
                                                    {{ selectedMeeting.has_attendance ? 'Absensi Mahasiswa ✓' : 'Belum Absensi Mahasiswa' }}
                                                </span>
                                                <span :class="['px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-2', selectedMeeting.dosen_hadir ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600']">
                                                    <CheckCircleIcon v-if="selectedMeeting.dosen_hadir" class="w-4 h-4" />
                                                    <ClockIcon v-else class="w-4 h-4" />
                                                    {{ selectedMeeting.dosen_hadir ? 'Dosen Hadir ✓' : 'Dosen Belum Absen' }}
                                                </span>
                                            </div>

                                            <!-- Tabs -->
                                            <div class="flex border-b border-gray-200 dark:border-gray-700">
                                                <button @click="detailTab = 'dosen'" :class="['px-4 py-2 text-sm font-semibold border-b-2 transition', detailTab === 'dosen' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                                                    <AcademicCapIcon class="w-4 h-4 inline mr-1" />
                                                    Kehadiran Dosen
                                                </button>
                                                <button @click="detailTab = 'mahasiswa'" :class="['px-4 py-2 text-sm font-semibold border-b-2 transition', detailTab === 'mahasiswa' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                                                    <UserGroupIcon class="w-4 h-4 inline mr-1" />
                                                    Absensi Mahasiswa
                                                </button>
                                            </div>
                                            <!-- DOSEN TAB CONTENT -->
                                            <div v-show="detailTab === 'dosen'">
                                                <!-- Dosen Attendance Quick Actions -->
                                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5">
                                                    <h4 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                                                        <AcademicCapIcon class="w-5 h-5 text-indigo-500" />
                                                        Kehadiran Dosen Mengajar
                                                    </h4>
                                                    
                                                    <!-- Current Status -->
                                                    <div v-if="selectedMeeting.dosen_hadir" class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                                                        <p class="text-emerald-700 dark:text-emerald-300 font-semibold text-sm">✓ Sudah Absen</p>
                                                        <p class="text-emerald-600 dark:text-emerald-400 text-xs mt-1">
                                                            Jam Masuk: {{ formatTime(selectedMeeting.dosen_jam_masuk) }} — 
                                                            Jam Keluar: {{ formatTime(selectedMeeting.dosen_jam_keluar) }}
                                                        </p>
                                                        <button @click="resetDosenAbsensi" class="mt-2 text-xs text-red-600 hover:text-red-700 underline">
                                                            Reset Absensi
                                                        </button>
                                                    </div>

                                                    <!-- Quick Action Buttons -->
                                                    <div class="grid grid-cols-2 gap-3 mb-4">
                                                        <button @click="absenMasuk" :disabled="dosenAttendForm.processing"
                                                            class="px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-bold disabled:opacity-50 flex items-center justify-center gap-2 hover:from-blue-700 hover:to-indigo-700 transition shadow-lg shadow-blue-500/20 text-sm">
                                                            <ClockIcon class="w-5 h-5" />
                                                            Absen Masuk
                                                            <span class="text-xs opacity-75">(sekarang)</span>
                                                        </button>
                                                        <button @click="absenSelesai" :disabled="dosenAttendForm.processing"
                                                            class="px-4 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-bold disabled:opacity-50 flex items-center justify-center gap-2 hover:from-emerald-700 hover:to-teal-700 transition shadow-lg shadow-emerald-500/20 text-sm">
                                                            <CheckCircleIcon class="w-5 h-5" />
                                                            Absen Selesai
                                                            <span class="text-xs opacity-75">(sekarang)</span>
                                                        </button>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-3">
                                                        <button @click="absenSesuaiJadwal" :disabled="dosenAttendForm.processing"
                                                            class="px-4 py-3 bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold disabled:opacity-50 flex items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition text-sm">
                                                            <CalendarDaysIcon class="w-5 h-5" />
                                                            Sesuai Jadwal
                                                        </button>
                                                        <button @click="showCustomTimeModal = !showCustomTimeModal" 
                                                            class="px-4 py-3 bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold flex items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition text-sm">
                                                            <PencilIcon class="w-5 h-5" />
                                                            Custom Jam
                                                        </button>
                                                    </div>

                                                    <!-- Custom Time Input (Collapsible) -->
                                                    <div v-if="showCustomTimeModal" class="mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                                                        <div class="grid grid-cols-2 gap-4 mb-3">
                                                            <div>
                                                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Jam Masuk</label>
                                                                <input type="time" v-model="dosenAttendForm.dosen_jam_masuk" 
                                                                    class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Jam Keluar</label>
                                                                <input type="time" v-model="dosenAttendForm.dosen_jam_keluar" 
                                                                    class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                                                            </div>
                                                        </div>
                                                        <button @click="saveDosenAttendance" :disabled="dosenAttendForm.processing"
                                                            class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold text-sm hover:bg-indigo-700 transition">
                                                            {{ dosenAttendForm.processing ? 'Menyimpan...' : 'Simpan Custom Jam' }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- MAHASISWA TAB CONTENT -->
                                            <div v-show="detailTab === 'mahasiswa'">
                                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5">
                                                    <h4 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                                                        <UserGroupIcon class="w-5 h-5 text-indigo-500" />
                                                        Daftar Absensi Mahasiswa
                                                    </h4>
                                                    
                                                    <!-- Loading State -->
                                                    <div v-if="loadingAbsensis" class="text-center py-8 text-gray-400">
                                                        <div class="animate-spin w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mx-auto mb-2"></div>
                                                        <p>Memuat data...</p>
                                                    </div>

                                                    <!-- Empty State -->
                                                    <div v-else-if="mahasiswaAbsensis.length === 0" class="text-center py-8 text-gray-400">
                                                        <UserGroupIcon class="w-12 h-12 mx-auto mb-2 opacity-30" />
                                                        <p>Belum ada data mahasiswa</p>
                                                    </div>

                                                    <!-- Data Loaded & Exists -->
                                                    <div v-else>
                                                        <!-- Bulk Actions -->
                                                        <div class="flex flex-wrap gap-2 mb-4">
                                                            <button @click="bulkUpdateMahasiswaStatus('hadir')" class="px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold rounded-lg hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition">
                                                                Hadir Semua
                                                            </button>
                                                            <button @click="bulkUpdateMahasiswaStatus('alpha')" class="px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                                                Alpha Semua
                                                            </button>
                                                            <button @click="bulkUpdateMahasiswaStatus('null')" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                                                Reset Semua
                                                            </button>
                                                        </div>

                                                        <!-- Mahasiswa List -->
                                                        <div class="space-y-2 max-h-80 overflow-y-auto">
                                                            <div v-for="mhs in mahasiswaAbsensis" :key="mhs.id" 
                                                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-xl">
                                                                <div>
                                                                    <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ mhs.nama }}</p>
                                                                    <p class="text-xs text-gray-500">{{ mhs.nim }}</p>
                                                                </div>
                                                                
                                                                <!-- Status Buttons -->
                                                                <div class="flex items-center gap-1">
                                                                    <button @click="updateMahasiswaStatus(mhs.id, 'hadir')" 
                                                                        :class="['w-8 h-8 rounded-lg text-xs font-bold transition flex items-center justify-center', mhs.status === 'hadir' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 hover:border-emerald-500 hover:text-emerald-500']"
                                                                        title="Hadir">H</button>
                                                                    
                                                                    <button @click="updateMahasiswaStatus(mhs.id, 'izin')" 
                                                                        :class="['w-8 h-8 rounded-lg text-xs font-bold transition flex items-center justify-center', mhs.status === 'izin' ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/30' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 hover:border-blue-500 hover:text-blue-500']"
                                                                        title="Izin">I</button>
                                                                    
                                                                    <button @click="updateMahasiswaStatus(mhs.id, 'sakit')" 
                                                                        :class="['w-8 h-8 rounded-lg text-xs font-bold transition flex items-center justify-center', mhs.status === 'sakit' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 hover:border-amber-500 hover:text-amber-500']"
                                                                        title="Sakit">S</button>
                                                                    
                                                                    <button @click="updateMahasiswaStatus(mhs.id, 'alpha')" 
                                                                        :class="['w-8 h-8 rounded-lg text-xs font-bold transition flex items-center justify-center', mhs.status === 'alpha' ? 'bg-red-500 text-white shadow-lg shadow-red-500/30' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 hover:border-red-500 hover:text-red-500']"
                                                                        title="Alpha">A</button>

                                                                    <button v-if="mhs.status" @click="updateMahasiswaStatus(mhs.id, 'null')" 
                                                                        class="w-6 h-8 flex items-center justify-center text-gray-400 hover:text-red-500 transition ml-1"
                                                                        title="Reset">
                                                                        <XMarkIcon class="w-4 h-4" />
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Footer -->
                                        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                                            <div class="flex justify-between items-center">
                                                <div class="flex gap-2">
                                                    <button @click="openEditModal" class="px-4 py-2.5 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl font-semibold hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition flex items-center gap-2">
                                                        <PencilIcon class="w-4 h-4" />
                                                        Edit Jadwal
                                                    </button>
                                                    <button @click="closeDetailModal" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                                        Tutup
                                                    </button>
                                                </div>
                                                <button @click="goToAbsensi(selectedMeeting.id)" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 flex items-center gap-2 transition">
                                                    <UserGroupIcon class="w-5 h-5" />
                                                    Buka Halaman Absensi Lengkap
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </DialogPanel>
                            </TransitionChild>
                        </div>
                    </div>
                </div>

            </Dialog>
        </TransitionRoot>

        <!-- Edit Schedule Slide-Over Panel -->
        <TransitionRoot as="template" :show="showEditModal">
            <Dialog as="div" class="relative z-50" @close="showEditModal = false">
                <TransitionChild as="template" enter="ease-in-out duration-500" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-500" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-hidden">
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                            <TransitionChild as="template" enter="transform transition ease-in-out duration-500 sm:duration-700" enter-from="translate-x-full" enter-to="translate-x-0" leave="transform transition ease-in-out duration-500 sm:duration-700" leave-from="translate-x-0" leave-to="translate-x-full">
                                <DialogPanel class="pointer-events-auto w-screen max-w-md">
                                    <form @submit.prevent="submitEdit" class="flex h-full flex-col bg-white dark:bg-gray-900 shadow-2xl">
                                        
                                        <!-- Header -->
                                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-6 shrink-0 z-10">
                                            <div class="flex items-center justify-between">
                                                <DialogTitle class="text-xl font-bold text-white flex items-center gap-3">
                                                    <div class="p-2 bg-white/20 rounded-xl">
                                                        <PencilIcon class="h-6 w-6 text-white" />
                                                    </div>
                                                    Edit Jadwal
                                                </DialogTitle>
                                                <button type="button" @click="showEditModal = false" class="rounded-lg p-2 text-white/80 hover:text-white hover:bg-white/10 transition">
                                                    <XMarkIcon class="h-6 w-6" />
                                                </button>
                                            </div>
                                            
                                            <!-- Info Matkul (Read-only) -->
                                            <div class="bg-black/20 rounded-xl p-4 mt-4 backdrop-blur-sm border border-white/10" v-if="selectedMeeting">
                                                <div class="text-indigo-100 text-xs font-bold uppercase tracking-wider mb-1">Mata Kuliah</div>
                                                <div class="text-white font-bold text-lg leading-tight">{{ selectedMeeting?.jadwal?.mata_kuliah?.nama }}</div>
                                                <div class="flex items-center gap-2 mt-2 text-indigo-200 text-sm">
                                                    <span class="bg-white/10 px-2 py-0.5 rounded text-xs font-mono">{{ selectedMeeting?.jadwal?.mata_kuliah?.kode }}</span>
                                                    <span>•</span>
                                                    <span>{{ selectedMeeting?.jadwal?.kelas_model?.nama }}</span>
                                                    <span>•</span>
                                                    <span>{{ selectedMeeting?.jadwal?.kelas_model?.prodi?.kode }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Form Body -->
                                        <div class="flex-1 overflow-y-auto px-6 py-6">
                                            <div class="space-y-6">
                                                
                                                <!-- Waktu -->
                                                <div class="space-y-4">
                                                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Waktu Pertemuan</h3>
                                                    
                                                    <div class="grid grid-cols-3 gap-4">
                                                        <div class="col-span-2">
                                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Tanggal</label>
                                                            <input type="date" v-model="editForm.tanggal" 
                                                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium">
                                                            <div v-if="editForm.errors.tanggal" class="text-red-500 text-xs mt-1 font-bold">{{ editForm.errors.tanggal }}</div>
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Ke-</label>
                                                            <input type="number" v-model="editForm.pertemuan_ke" min="1"
                                                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium text-center">
                                                            <div v-if="editForm.errors.pertemuan_ke" class="text-red-500 text-xs mt-1 font-bold">{{ editForm.errors.pertemuan_ke }}</div>
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Jam Mulai</label>
                                                            <input type="time" v-model="editForm.jam_mulai" 
                                                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium text-center">
                                                            <div v-if="editForm.errors.jam_mulai" class="text-red-500 text-xs mt-1 font-bold">{{ editForm.errors.jam_mulai }}</div>
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Jam Selesai</label>
                                                            <input type="time" v-model="editForm.jam_selesai" 
                                                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium text-center">
                                                            <div v-if="editForm.errors.jam_selesai" class="text-red-500 text-xs mt-1 font-bold">{{ editForm.errors.jam_selesai }}</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Divider -->
                                                <div class="border-t border-gray-100 dark:border-gray-800"></div>

                                                <!-- Detail -->
                                                <div class="space-y-4">
                                                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Detail Pertemuan</h3>

                                                    <!-- Mode -->
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Metode Pembelajaran</label>
                                                        <div class="grid grid-cols-3 gap-2">
                                                            <label :class="['flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 cursor-pointer transition font-medium text-sm', editForm.mode === 'offline' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-700 text-gray-500 hover:border-gray-300']">
                                                                <input type="radio" v-model="editForm.mode" value="offline" class="sr-only">
                                                                <span>Offline</span>
                                                            </label>
                                                            <label :class="['flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 cursor-pointer transition font-medium text-sm', editForm.mode === 'online' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-700 text-gray-500 hover:border-gray-300']">
                                                                <input type="radio" v-model="editForm.mode" value="online" class="sr-only">
                                                                <span>Online</span>
                                                            </label>
                                                            <label :class="['flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 cursor-pointer transition font-medium text-sm', editForm.mode === 'hybrid' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-700 text-gray-500 hover:border-gray-300']">
                                                                <input type="radio" v-model="editForm.mode" value="hybrid" class="sr-only">
                                                                <span>Hybrid</span>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <!-- Ruangan -->
                                                    <div v-show="editForm.mode !== 'online'">
                                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Ruangan</label>
                                                        <select v-model="editForm.ruangan_id" 
                                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm font-medium">
                                                            <option value="">Pilih Ruangan</option>
                                                            <option v-for="r in filteredRuangans" :key="r.id" :value="r.id">{{ r.nama }}</option>
                                                        </select>
                                                    </div>

                                                    <!-- Dosen Search UI (Multi Select) -->
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Dosen Pengganti (Opsional)</label>
                                                        
                                                        <!-- Selected Dosen Tags -->
                                                        <div v-if="editForm.dosen_ids.length > 0" class="flex flex-wrap gap-2 mb-3">
                                                            <span v-for="id in editForm.dosen_ids" :key="id" 
                                                                class="inline-flex items-center gap-1 pl-3 pr-2 py-1.5 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-lg text-sm font-bold border border-indigo-200 dark:border-indigo-700">
                                                                {{ availableDosens?.find(d => d.id === id)?.nama }}
                                                                <button type="button" @click="toggleDosenEdit({id})" class="p-0.5 rounded-full hover:bg-white/20 text-indigo-500 hover:text-indigo-700 transition">
                                                                    <XMarkIcon class="w-3.5 h-3.5" />
                                                                </button>
                                                            </span>
                                                        </div>

                                                        <!-- Search Input -->
                                                        <div class="relative">
                                                            <input type="text" v-model="dosenSearchEdit" placeholder="Cari & pilih dosen..." 
                                                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm pl-10">
                                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 1.549l6.826 6.826a1 1 0 001.414-1.414l-6.826-6.826A7 7 0 012 9z" clip-rule="evenodd" />
                                                                    </svg>
                                                                </div>
                                                        </div>

                                                        <!-- Dosen List -->
                                                        <div v-if="dosenSearchEdit || editForm.dosen_ids.length === 0" class="mt-2 max-h-48 overflow-y-auto rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 shadow-lg scrollbar-thin">
                                                            <div v-for="d in filteredDosensEdit" :key="d.id" 
                                                                @click="toggleDosenEdit(d)"
                                                                class="px-4 py-2 cursor-pointer transition flex items-center justify-between text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 group">
                                                                <div class="flex items-center gap-3">
                                                                    <div :class="['w-5 h-5 rounded border flex items-center justify-center transition', editForm.dosen_ids.includes(d.id) ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700']">
                                                                        <svg v-if="editForm.dosen_ids.includes(d.id)" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                                        </svg>
                                                                    </div>
                                                                    <div class="flex flex-col">
                                                                        <span :class="['font-medium', editForm.dosen_ids.includes(d.id) ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200']">{{ d.nama }}</span>
                                                                        <span class="text-xs text-gray-400">{{ d.nidn }}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div v-if="filteredDosensEdit.length === 0" class="px-4 py-3 text-gray-400 text-sm text-center">
                                                                Tidak ada dosen ditemukan
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Catatan -->
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Topik / Catatan</label>
                                                        <textarea v-model="editForm.catatan" rows="3" 
                                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-0 transition text-sm resize-none"
                                                            placeholder="Contoh: Pengantar Teknologi Pendidikan..."></textarea>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                        <!-- Footer Actions -->
                                        <div class="border-t border-gray-100 dark:border-gray-800 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 shrink-0">
                                            <div class="flex gap-3">
                                                <button type="button" @click="showEditModal = false" class="flex-1 px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                                    Batal
                                                </button>
                                                <button type="submit" :disabled="editForm.processing" :class="['flex-1 px-4 py-3 rounded-xl font-bold transition flex items-center justify-center gap-2', editForm.processing ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-500/30']">
                                                    <span v-if="editForm.processing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
                                                    {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </DialogPanel>
                            </TransitionChild>
                        </div>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Toast Notification -->
        <TransitionRoot appear :show="showToast" as="template">
            <TransitionChild
                as="template"
                enter="transform transition ease-in-out duration-300"
                enter-from="translate-y-5 opacity-0"
                enter-to="translate-y-0 opacity-100"
                leave="transition ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0">
                <div class="fixed bottom-5 right-5 z-[60] bg-gray-900 text-white dark:bg-white dark:text-gray-900 px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3">
                    <CheckCircleIcon class="w-6 h-6 text-emerald-500" />
                    <span class="font-semibold">{{ toastMessage }}</span>
                </div>
            </TransitionChild>
        </TransitionRoot>
    </AppLayout>
</template>
