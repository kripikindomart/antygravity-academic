<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage, Head } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { 
    ClipboardDocumentCheckIcon, UserGroupIcon, CalendarDaysIcon,
    CheckCircleIcon, XCircleIcon, ClockIcon, AcademicCapIcon,
    ChevronRightIcon, MagnifyingGlassIcon, FunnelIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    kelases: Array,
    meetings: Array,
    filters: Object,
});

const page = usePage();

// Date filters
const tanggalAwal = ref(props.filters?.tanggal_awal || '');
const tanggalAkhir = ref(props.filters?.tanggal_akhir || '');

// Search/Filter
const searchQuery = ref('');
const selectedKelas = ref(null);

// Apply date filter
const applyDateFilter = () => {
    router.get(route('absensi.index'), {
        tanggal_awal: tanggalAwal.value,
        tanggal_akhir: tanggalAkhir.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Filtered meetings
const filteredMeetings = computed(() => {
    let meetings = props.meetings || [];
    
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        meetings = meetings.filter(m => 
            m.jadwal?.mata_kuliah?.nama?.toLowerCase().includes(q) ||
            m.jadwal?.mata_kuliah?.kode?.toLowerCase().includes(q) ||
            m.jadwal?.kelas_model?.nama?.toLowerCase().includes(q)
        );
    }
    
    if (selectedKelas.value) {
        meetings = meetings.filter(m => m.jadwal?.kelas_model?.id === selectedKelas.value);
    }
    
    return meetings;
});

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { 
        weekday: 'short', day: 'numeric', month: 'short' 
    });
};

// Navigate to input page
const goToInput = (meetingId) => {
    router.visit(route('absensi.pertemuan', meetingId));
};

// Stats
const stats = computed(() => {
    const meetings = props.meetings || [];
    return {
        total: meetings.length,
        filled: meetings.filter(m => m.has_attendance).length,
        pending: meetings.filter(m => !m.has_attendance).length,
    };
});
</script>

<template>
    <AppLayout>
        <Head title="Absensi" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <ClipboardDocumentCheckIcon class="w-5 h-5 text-white" />
                        </div>
                        Absensi
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola kehadiran mahasiswa per pertemuan</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl p-5 text-white shadow-lg shadow-indigo-500/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-indigo-100 text-sm">Total Pertemuan</p>
                            <p class="text-3xl font-black">{{ stats.total }}</p>
                        </div>
                        <CalendarDaysIcon class="w-10 h-10 text-indigo-200" />
                    </div>
                </div>
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg shadow-emerald-500/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-emerald-100 text-sm">Sudah Diisi</p>
                            <p class="text-3xl font-black">{{ stats.filled }}</p>
                        </div>
                        <CheckCircleIcon class="w-10 h-10 text-emerald-200" />
                    </div>
                </div>
                <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl p-5 text-white shadow-lg shadow-amber-500/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-amber-100 text-sm">Belum Diisi</p>
                            <p class="text-3xl font-black">{{ stats.pending }}</p>
                        </div>
                        <ClockIcon class="w-10 h-10 text-amber-200" />
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Date Range -->
                    <div class="flex items-center gap-2">
                        <CalendarDaysIcon class="w-5 h-5 text-gray-400" />
                        <input v-model="tanggalAwal" type="date" 
                            class="px-3 py-2 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20" />
                        <span class="text-gray-400">-</span>
                        <input v-model="tanggalAkhir" type="date" 
                            class="px-3 py-2 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20" />
                        <button @click="applyDateFilter" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition flex items-center gap-1">
                            <FunnelIcon class="w-4 h-4" />
                            Filter
                        </button>
                    </div>
                    
                    <!-- Search -->
                    <div class="flex-1 relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input v-model="searchQuery" type="text" placeholder="Cari mata kuliah, kelas..."
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-indigo-500/20" />
                    </div>
                    
                    <!-- Kelas Filter -->
                    <select v-model="selectedKelas" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20">
                        <option :value="null">Semua Kelas</option>
                        <option v-for="kelas in kelases" :key="kelas.id" :value="kelas.id">
                            {{ kelas.nama }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Meetings List -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <CalendarDaysIcon class="w-5 h-5 text-indigo-500" />
                        Daftar Pertemuan ({{ formatDate(tanggalAwal) }} - {{ formatDate(tanggalAkhir) }})
                    </h2>
                </div>
                
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="meeting in filteredMeetings" :key="meeting.id"
                        @click="goToInput(meeting.id)"
                        class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition group">
                        
                        <div class="w-14 h-14 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-xl flex flex-col items-center justify-center flex-shrink-0">
                            <span class="text-xl font-black text-indigo-600 dark:text-indigo-400">{{ meeting.pertemuan_ke }}</span>
                            <span class="text-[10px] text-indigo-500 uppercase">Meet</span>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-900 dark:text-white truncate">
                                    {{ meeting.jadwal?.mata_kuliah?.nama || '-' }}
                                </h3>
                                <span :class="[
                                    'px-2 py-0.5 rounded-full text-xs font-bold',
                                    meeting.has_attendance 
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' 
                                        : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400'
                                ]">
                                    {{ meeting.has_attendance ? 'Sudah Diisi' : 'Belum Diisi' }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ meeting.jadwal?.kelas_model?.nama }} • {{ formatDate(meeting.tanggal) }}
                            </p>
                        </div>
                        
                        <ChevronRightIcon class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 transition" />
                    </div>
                    
                    <div v-if="filteredMeetings.length === 0" class="px-6 py-12 text-center">
                        <ClipboardDocumentCheckIcon class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                        <p class="text-gray-500">Tidak ada pertemuan dalam rentang tanggal ini</p>
                        <p class="text-sm text-gray-400 mt-1">Coba ubah filter tanggal</p>
                    </div>
                </div>
            </div>

            <!-- Classes Overview (Collapsible) -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <AcademicCapIcon class="w-5 h-5 text-purple-500" />
                        Kelas Aktif
                    </h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
                    <div v-for="kelas in kelases" :key="kelas.id"
                        class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer"
                        @click="selectedKelas = kelas.id">
                        <h3 class="font-bold text-gray-900 dark:text-white">{{ kelas.nama }}</h3>
                        <p class="text-sm text-gray-500">{{ kelas.prodi?.nama }}</p>
                        <div class="mt-2 flex items-center gap-2 text-xs text-gray-400">
                            <UserGroupIcon class="w-4 h-4" />
                            <span>{{ kelas.mahasiswas_count || 0 }} Mahasiswa</span>
                        </div>
                    </div>
                    
                    <div v-if="kelases.length === 0" class="col-span-full text-center py-8 text-gray-400">
                        Tidak ada kelas aktif
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
