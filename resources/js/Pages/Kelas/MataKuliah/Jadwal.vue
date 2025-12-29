<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { router, usePage, Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Components/Layout/AppLayout.vue';
import { 
    CalendarDaysIcon, UserIcon, ClockIcon, PencilSquareIcon, 
    TrashIcon, CheckCircleIcon, XMarkIcon, ChevronLeftIcon,
    BookOpenIcon, ExclamationTriangleIcon, WifiIcon, BuildingOfficeIcon,
    CheckIcon, Squares2X2Icon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    kelasMatakuliah: Object,
    jadwals: Array,
    availableDosens: Array,
    availableRuangans: Array,
});

const page = usePage();

// Toast
const toast = reactive({ show: false, message: '', type: 'success' });
const showToast = (message, type = 'success') => {
    toast.message = message;
    toast.type = type;
    toast.show = true;
    setTimeout(() => toast.show = false, 3000);
};

// Flash Messages
if (page.props.flash?.success) {
    showToast(page.props.flash.success, 'success');
}

// Format Date
const formatShortDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

// Status & Mode Badge Colors
const statusColors = {
    'terjadwal': 'bg-blue-100 text-blue-700',
    'selesai': 'bg-green-100 text-green-700',
    'dibatalkan': 'bg-red-100 text-red-700',
};
const modeColors = {
    'online': 'bg-emerald-100 text-emerald-700',
    'offline': 'bg-slate-100 text-slate-600',
    'hybrid': 'bg-purple-100 text-purple-700',
};

// Selection
const selectedIds = ref([]);
const selectAll = computed({
    get: () => props.jadwals?.length > 0 && selectedIds.value.length === props.jadwals.length,
    set: (val) => {
        selectedIds.value = val ? props.jadwals.map(j => j.id) : [];
    }
});

// Bulk Action Modal
const showBulkModal = ref(false);
const bulkAction = ref(''); // 'dosen', 'tipe', 'mode', 'tanggal'
const bulkForm = reactive({
    dosen_id: null,
    tipe: null,
    mode: null,
    tanggal: '',
});
const isBulkSubmitting = ref(false);

const openBulkModal = (action) => {
    if (selectedIds.value.length === 0) {
        showToast('Pilih minimal 1 jadwal', 'error');
        return;
    }
    bulkAction.value = action;
    // Reset form
    bulkForm.dosen_id = null;
    bulkForm.tipe = null;
    bulkForm.mode = null;
    bulkForm.tanggal = '';
    showBulkModal.value = true;
};

const submitBulk = () => {
    isBulkSubmitting.value = true;
    const payload = { ids: selectedIds.value };
    if (bulkAction.value === 'dosen') payload.dosen_id = bulkForm.dosen_id;
    if (bulkAction.value === 'tipe') payload.tipe = bulkForm.tipe;
    if (bulkAction.value === 'mode') payload.mode = bulkForm.mode;
    if (bulkAction.value === 'tanggal') payload.tanggal = bulkForm.tanggal;

    router.put(route('jadwal-pertemuan.bulk-update'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            showBulkModal.value = false;
            selectedIds.value = [];
            showToast(`${payload.ids.length} jadwal berhasil diperbarui`);
        },
        onError: () => showToast('Gagal memperbarui jadwal', 'error'),
        onFinish: () => isBulkSubmitting.value = false,
    });
};

// Edit Modal (Single)
const showEditModal = ref(false);
const editForm = reactive({ 
    id: null, tanggal: '', dosen_id: null, ruangan_id: null,
    status: 'terjadwal', mode: 'offline', tipe: 'kuliah', catatan: '',
    jam_mulai: '', jam_selesai: '' // for conflict checking
});
const isSubmitting = ref(false);

// Conflict State
const conflictState = reactive({
    isChecking: false,
    kelasAvailable: true,
    kelasConflict: null,
    mkConflict: null,
    dosenAvailable: true,
    dosenConflict: null,
    ruanganAvailable: true,
    ruanganConflict: null,
    suggestedDates: [],
    suggestedRuangans: [],
    suggestedTimeSlots: [],
});

const openEditModal = (jadwal) => {
    editForm.id = jadwal.id;
    editForm.tanggal = jadwal.tanggal ? jadwal.tanggal.split('T')[0] : '';
    editForm.dosen_id = jadwal.dosen_id;
    editForm.ruangan_id = jadwal.ruangan_id;
    editForm.status = jadwal.status || 'terjadwal';
    editForm.mode = jadwal.mode || 'offline';
    editForm.tipe = jadwal.tipe || 'kuliah';
    editForm.catatan = jadwal.catatan || '';
    editForm.jam_mulai = jadwal.jadwal?.jam_mulai?.substring(0,5) || '08:00';
    editForm.jam_selesai = jadwal.jadwal?.jam_selesai?.substring(0,5) || '10:00';
    // Reset conflict state
    conflictState.dosenAvailable = true;
    conflictState.dosenConflict = null;
    conflictState.kelasAvailable = true;
    conflictState.kelasConflict = null;
    conflictState.mkConflict = null;
    conflictState.ruanganAvailable = true;
    conflictState.ruanganConflict = null;
    conflictState.suggestedDates = [];
    conflictState.suggestedRuangans = [];
    conflictState.suggestedTimeSlots = [];
    showEditModal.value = true;
    // Immediately check for conflicts
    setTimeout(checkAvailability, 100);
};

// Check availability when date/dosen/ruangan changes
const checkAvailability = async () => {
    if (!editForm.tanggal) return;
    conflictState.isChecking = true;
    try {
        const response = await fetch(route('jadwal-pertemuan.check-availability'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
            body: JSON.stringify({
                tanggal: editForm.tanggal,
                jam_mulai: editForm.jam_mulai,
                jam_selesai: editForm.jam_selesai,
                kelas_id: props.kelasMatakuliah?.kelas_id,
                mata_kuliah_id: props.kelasMatakuliah?.mata_kuliah_id,
                dosen_id: editForm.dosen_id,
                ruangan_id: editForm.ruangan_id,
                exclude_id: editForm.id,
            }),
        });
        const data = await response.json();
        conflictState.kelasAvailable = data.kelas_available;
        conflictState.kelasConflict = data.kelas_conflict;
        conflictState.mkConflict = data.mk_conflict;
        conflictState.dosenAvailable = data.dosen_available;
        conflictState.dosenConflict = data.dosen_conflict;
        conflictState.ruanganAvailable = data.ruangan_available;
        conflictState.ruanganConflict = data.ruangan_conflict;
        conflictState.suggestedDates = data.suggested_dates || [];
        conflictState.suggestedRuangans = data.suggested_ruangans || [];
        conflictState.suggestedTimeSlots = data.suggested_time_slots || [];
    } catch (e) {
        console.error('Check availability error:', e);
    }
    conflictState.isChecking = false;
};

// Debounced watcher for conflicts (including time changes)
let debounceTimer = null;
watch([() => editForm.tanggal, () => editForm.jam_mulai, () => editForm.jam_selesai, () => editForm.dosen_id, () => editForm.ruangan_id], () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(checkAvailability, 300);
});

const hasConflict = computed(() => 
    !conflictState.kelasAvailable || !conflictState.dosenAvailable || !conflictState.ruanganAvailable || conflictState.mkConflict
);

const submitEdit = () => {
    if (hasConflict.value) {
        showToast('Ada konflik jadwal! Silakan pilih tanggal/dosen/ruangan lain.', 'error');
        return;
    }
    isSubmitting.value = true;
    router.put(route('jadwal-pertemuan.update', editForm.id), editForm, {
        preserveScroll: true,
        onSuccess: () => { showEditModal.value = false; showToast('Jadwal berhasil diperbarui'); },
        onError: () => showToast('Gagal memperbarui jadwal', 'error'),
        onFinish: () => isSubmitting.value = false,
    });
};

// Delete
const showDeleteModal = ref(false);
const deleteTargetId = ref(null);
const deleteTargetName = ref('');

const openDeleteModal = (jadwal) => {
    deleteTargetId.value = jadwal.id;
    deleteTargetName.value = `Pertemuan ke-${jadwal.pertemuan_ke}`;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    router.delete(route('jadwal-pertemuan.destroy', deleteTargetId.value), {
        preserveScroll: true,
        onSuccess: () => { showDeleteModal.value = false; showToast('Jadwal berhasil dihapus'); },
        onError: () => showToast('Gagal menghapus jadwal', 'error'),
    });
};

// Computed
const mk = computed(() => props.kelasMatakuliah?.mata_kuliah);
const kelas = computed(() => props.kelasMatakuliah?.kelas);
const totalSks = computed(() => (mk.value?.sks_teori || 0) + (mk.value?.sks_praktik || 0));
const stats = computed(() => {
    const total = props.jadwals?.length || 0;
    const selesai = props.jadwals?.filter(j => j.status === 'selesai').length || 0;
    const online = props.jadwals?.filter(j => j.mode === 'online').length || 0;
    return { total, selesai, online, progress: total > 0 ? Math.round((selesai / total) * 100) : 0 };
});

// Get conflict info for a jadwal in the list (checks if same date as other jadwals in MK)
const getConflictInfo = (jadwal) => {
    if (!jadwal.tanggal) return null;
    const dateStr = jadwal.tanggal.split('T')[0];
    // Find other jadwals on same date (excluding self)
    const conflicting = props.jadwals?.filter(j => 
        j.id !== jadwal.id && 
        j.tanggal?.split('T')[0] === dateStr
    );
    if (conflicting?.length) {
        return {
            type: 'same_date',
            message: `Bentrok dengan Pertemuan ke-${conflicting[0].pertemuan_ke}`,
            count: conflicting.length
        };
    }
    return null;
};
</script>

<template>
    <Head :title="`Jadwal - ${mk?.nama}`" />
    <AppLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 py-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <!-- Back Button -->
                <Link :href="route('kelas.show', kelas?.id)" class="inline-flex items-center gap-2 text-gray-500 hover:text-indigo-600 font-medium mb-6 transition group">
                    <ChevronLeftIcon class="w-5 h-5 group-hover:-translate-x-1 transition-transform" />
                    Kembali ke Detail Kelas
                </Link>

                <!-- Header Card -->
                <div class="bg-white rounded-3xl shadow-xl shadow-indigo-100/50 border border-gray-100 overflow-hidden mb-8">
                    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 p-6 sm:p-8 text-white">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex-shrink-0 w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center">
                                <BookOpenIcon class="w-8 h-8" />
                            </div>
                            <div class="flex-1">
                                <p class="text-indigo-200 text-sm font-medium mb-1">{{ mk?.kode }}</p>
                                <h1 class="text-2xl sm:text-3xl font-black">{{ mk?.nama }}</h1>
                                <p class="text-white/80 mt-1">{{ kelas?.nama }} • {{ kelas?.semester?.nama }}</p>
                            </div>
                            <div class="flex gap-4 text-center">
                                <div class="bg-white/10 backdrop-blur px-4 py-2 rounded-xl">
                                    <div class="text-2xl font-black">{{ totalSks }}</div>
                                    <div class="text-xs text-indigo-200 uppercase">SKS</div>
                                </div>
                                <div class="bg-white/10 backdrop-blur px-4 py-2 rounded-xl">
                                    <div class="text-2xl font-black">{{ stats.total }}</div>
                                    <div class="text-xs text-indigo-200 uppercase">Pertemuan</div>
                                </div>
                                <div class="bg-white/10 backdrop-blur px-4 py-2 rounded-xl">
                                    <div class="text-2xl font-black">{{ stats.online }}</div>
                                    <div class="text-xs text-indigo-200 uppercase">Online</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Progress Bar -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-600">Progress</span>
                            <span class="text-sm font-bold text-indigo-600">{{ stats.selesai }}/{{ stats.total }} ({{ stats.progress }}%)</span>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-green-500 to-emerald-400 rounded-full" :style="{ width: stats.progress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Bulk Action Toolbar -->
                <div v-if="selectedIds.length > 0" class="mb-4 bg-indigo-600 text-white rounded-2xl p-4 flex items-center justify-between shadow-lg">
                    <span class="font-bold">{{ selectedIds.length }} jadwal dipilih</span>
                    <div class="flex gap-2">
                        <button @click="openBulkModal('dosen')" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-semibold flex items-center gap-2">
                            <UserIcon class="w-4 h-4" /> Ubah Dosen
                        </button>
                        <button @click="openBulkModal('tipe')" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-semibold flex items-center gap-2">
                            <Squares2X2Icon class="w-4 h-4" /> Ubah Tipe
                        </button>
                        <button @click="openBulkModal('mode')" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-semibold flex items-center gap-2">
                            <WifiIcon class="w-4 h-4" /> Ubah Mode
                        </button>
                        <button @click="openBulkModal('tanggal')" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-semibold flex items-center gap-2">
                            <CalendarDaysIcon class="w-4 h-4" /> Reschedule
                        </button>
                        <button @click="selectedIds = []" class="px-4 py-2 bg-red-500/50 hover:bg-red-500 rounded-lg text-sm font-semibold">
                            Batal
                        </button>
                    </div>
                </div>

                <!-- Schedule Table -->
                <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <CalendarDaysIcon class="w-6 h-6 text-indigo-500" />
                            Daftar Pertemuan
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-4 text-left w-12">
                                        <input type="checkbox" v-model="selectAll" class="w-4 h-4 rounded text-indigo-600" />
                                    </th>
                                    <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase">Pert</th>
                                    <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                                    <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase">Waktu</th>
                                    <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase">Dosen</th>
                                    <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 uppercase">Tipe</th>
                                    <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 uppercase">Mode</th>
                                    <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 uppercase w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="jadwal in jadwals" :key="jadwal.id" 
                                    class="hover:bg-indigo-50/30 transition group"
                                    :class="{ 
                                        'bg-indigo-50': selectedIds.includes(jadwal.id),
                                        'bg-red-50 hover:bg-red-100 border-l-4 border-l-red-500': getConflictInfo(jadwal)
                                    }">
                                    <td class="px-4 py-4">
                                        <input type="checkbox" :value="jadwal.id" v-model="selectedIds" class="w-4 h-4 rounded text-indigo-600" />
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-black text-lg shadow">
                                            {{ jadwal.pertemuan_ke }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-gray-900">{{ formatShortDate(jadwal.tanggal) }}</div>
                                        <div class="text-xs text-gray-500 capitalize">{{ jadwal.jadwal?.hari }}</div>
                                        <div v-if="getConflictInfo(jadwal)" class="mt-1 text-xs text-red-600 font-semibold flex items-center gap-1">
                                            <ExclamationTriangleIcon class="w-3 h-3" />
                                            {{ getConflictInfo(jadwal)?.message }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-1 text-gray-600 text-sm">
                                            <ClockIcon class="w-4 h-4 text-gray-400" />
                                            {{ jadwal.jadwal?.jam_mulai?.substring(0,5) }} - {{ jadwal.jadwal?.jam_selesai?.substring(0,5) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div v-if="jadwal.dosen" class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-white font-bold text-xs">
                                                {{ jadwal.dosen.nama_gelar?.charAt(0) || '?' }}
                                            </div>
                                            <span class="font-medium text-gray-900 text-sm truncate max-w-[150px]">{{ jadwal.dosen.nama_gelar }}</span>
                                        </div>
                                        <div v-else class="flex items-center gap-1 text-amber-600 text-sm">
                                            <ExclamationTriangleIcon class="w-4 h-4" /> Belum assign
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold"
                                            :class="jadwal.tipe === 'uts' ? 'bg-orange-100 text-orange-700' : jadwal.tipe === 'uas' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'">
                                            {{ (jadwal.tipe || 'kuliah').toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="px-2 py-1 rounded-full text-xs font-bold flex items-center justify-center gap-1"
                                                :class="modeColors[jadwal.mode] || modeColors.offline">
                                                <WifiIcon v-if="jadwal.mode === 'online'" class="w-3 h-3" />
                                                <BuildingOfficeIcon v-else class="w-3 h-3" />
                                                {{ (jadwal.mode || 'offline').charAt(0).toUpperCase() + (jadwal.mode || 'offline').slice(1) }}
                                            </span>
                                            <span v-if="jadwal.mode !== 'online' && jadwal.ruangan" class="text-xs text-gray-500 truncate max-w-[120px]" :title="jadwal.ruangan.nama">
                                                {{ jadwal.ruangan.nama }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold" :class="statusColors[jadwal.status]">
                                            {{ jadwal.status?.charAt(0).toUpperCase() + jadwal.status?.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition">
                                            <button @click="openEditModal(jadwal)" class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-100" title="Edit">
                                                <PencilSquareIcon class="w-4 h-4" />
                                            </button>
                                            <button @click="openDeleteModal(jadwal)" class="p-1.5 rounded-lg text-red-500 hover:bg-red-100" title="Hapus">
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!jadwals || jadwals.length === 0">
                                    <td colspan="9" class="py-16 text-center text-gray-400">
                                        <CalendarDaysIcon class="w-12 h-12 mx-auto mb-3 opacity-50" />
                                        <p class="font-semibold">Belum ada jadwal.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Action Modal -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showBulkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showBulkModal = false">
                    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
                        <div class="px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                            <h3 class="text-xl font-bold">
                                {{ bulkAction === 'dosen' ? 'Ubah Dosen' : bulkAction === 'tipe' ? 'Ubah Tipe' : bulkAction === 'mode' ? 'Ubah Mode' : 'Reschedule' }}
                            </h3>
                            <p class="text-indigo-200 text-sm mt-1">{{ selectedIds.length }} jadwal terpilih</p>
                        </div>
                        <div class="p-6">
                            <!-- Dosen -->
                            <div v-if="bulkAction === 'dosen'">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Dosen</label>
                                <select v-model="bulkForm.dosen_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl">
                                    <option :value="null">-- Pilih Dosen --</option>
                                    <option v-for="d in availableDosens" :key="d.id" :value="d.id">{{ d.nama_gelar }}</option>
                                </select>
                            </div>
                            <!-- Tipe -->
                            <div v-if="bulkAction === 'tipe'">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Tipe</label>
                                <select v-model="bulkForm.tipe" class="w-full px-4 py-3 border border-gray-200 rounded-xl">
                                    <option :value="null">-- Pilih Tipe --</option>
                                    <option value="kuliah">Kuliah</option>
                                    <option value="uts">UTS</option>
                                    <option value="uas">UAS</option>
                                </select>
                            </div>
                            <!-- Mode -->
                            <div v-if="bulkAction === 'mode'">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Mode</label>
                                <select v-model="bulkForm.mode" class="w-full px-4 py-3 border border-gray-200 rounded-xl">
                                    <option :value="null">-- Pilih Mode --</option>
                                    <option value="online">Online</option>
                                    <option value="offline">Offline</option>
                                    <option value="hybrid">Hybrid</option>
                                </select>
                            </div>
                            <!-- Tanggal -->
                            <div v-if="bulkAction === 'tanggal'">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Tanggal Baru</label>
                                <input type="date" v-model="bulkForm.tanggal" class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3">
                            <button @click="showBulkModal = false" class="px-5 py-2.5 border border-gray-200 rounded-xl font-semibold hover:bg-gray-100">Batal</button>
                            <button @click="submitBulk" :disabled="isBulkSubmitting" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold disabled:opacity-50 flex items-center gap-2">
                                <CheckCircleIcon class="w-5 h-5" />
                                {{ isBulkSubmitting ? 'Menyimpan...' : 'Terapkan' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Edit Modal -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showEditModal = false">
                    <div class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl">
                        <div class="px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white flex items-center justify-between">
                            <h3 class="text-xl font-bold">Edit Jadwal</h3>
                            <span v-if="conflictState.isChecking" class="text-xs bg-white/20 px-2 py-1 rounded">Checking...</span>
                        </div>

                        <!-- Conflict Warnings -->
                        <div v-if="hasConflict" class="px-6 py-3 bg-red-50 border-b border-red-200">
                            <!-- MK Conflict (Fatal) -->
                            <div v-if="conflictState.mkConflict" class="flex items-center gap-2 text-red-800 text-sm mb-2 font-bold">
                                <ExclamationTriangleIcon class="w-5 h-5" />
                                <span>🚫 FATAL: MK sama sudah ada di tanggal ini! ({{ conflictState.mkConflict?.jam_mulai?.substring(0,5) }}-{{ conflictState.mkConflict?.jam_selesai?.substring(0,5) }})</span>
                            </div>
                            <!-- Kelas Conflict (different MK, same time) -->
                            <div v-if="!conflictState.kelasAvailable && !conflictState.mkConflict" class="flex items-center gap-2 text-red-700 text-sm mb-1">
                                <ExclamationTriangleIcon class="w-4 h-4" />
                                <span><strong>Kelas bentrok!</strong> {{ conflictState.kelasConflict?.mata_kuliah }} ({{ conflictState.kelasConflict?.jam_mulai?.substring(0,5) }}-{{ conflictState.kelasConflict?.jam_selesai?.substring(0,5) }})</span>
                            </div>
                            <!-- Dosen Conflict -->
                            <div v-if="!conflictState.dosenAvailable" class="flex items-center gap-2 text-red-700 text-sm mb-1">
                                <ExclamationTriangleIcon class="w-4 h-4" />
                                <span><strong>Dosen bentrok!</strong> {{ conflictState.dosenConflict?.mata_kuliah }} ({{ conflictState.dosenConflict?.jam_mulai?.substring(0,5) }}-{{ conflictState.dosenConflict?.jam_selesai?.substring(0,5) }})</span>
                            </div>
                            <!-- Ruangan Conflict -->
                            <div v-if="!conflictState.ruanganAvailable" class="flex items-center gap-2 text-orange-700 text-sm mb-1">
                                <ExclamationTriangleIcon class="w-4 h-4" />
                                <span><strong>Ruangan bentrok!</strong> {{ conflictState.ruanganConflict?.mata_kuliah }} ({{ conflictState.ruanganConflict?.jam_mulai?.substring(0,5) }}-{{ conflictState.ruanganConflict?.jam_selesai?.substring(0,5) }})</span>
                            </div>
                            <!-- Suggested Available Dates -->
                            <div v-if="conflictState.suggestedDates?.length" class="mt-3 pt-3 border-t border-red-200">
                                <div class="text-sm text-gray-700 font-semibold mb-2">📅 Tanggal yang tersedia:</div>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="s in conflictState.suggestedDates" :key="s.date" 
                                        @click="editForm.tanggal = s.date"
                                        class="px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-800 text-xs rounded-lg font-medium transition">
                                        {{ s.day }}
                                    </button>
                                </div>
                            </div>
                            <!-- Suggested Available Ruangans -->
                            <div v-if="conflictState.suggestedRuangans?.length" class="mt-3 pt-3 border-t border-orange-200">
                                <div class="text-sm text-gray-700 font-semibold mb-2">🏫 Ruangan yang tersedia:</div>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="r in conflictState.suggestedRuangans" :key="r.id" 
                                        @click="editForm.ruangan_id = r.id"
                                        class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-800 text-xs rounded-lg font-medium transition">
                                        {{ r.nama }} ({{ r.gedung }})
                                    </button>
                                </div>
                            </div>
                            <!-- Suggested Time Slots -->
                            <div v-if="conflictState.suggestedTimeSlots?.length" class="mt-3 pt-3 border-t border-purple-200">
                                <div class="text-sm text-gray-700 font-semibold mb-2">⏰ Jam yang tersedia (di tanggal ini):</div>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="slot in conflictState.suggestedTimeSlots" :key="slot.label" 
                                        @click="editForm.jam_mulai = slot.jam_mulai; editForm.jam_selesai = slot.jam_selesai"
                                        class="px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-800 text-xs rounded-lg font-medium transition">
                                        {{ slot.label }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal</label>
                                <input type="date" v-model="editForm.tanggal" 
                                    class="w-full px-4 py-3 border rounded-xl" 
                                    :class="{ 'border-red-500 bg-red-50': hasConflict }" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Mulai</label>
                                    <input type="time" v-model="editForm.jam_mulai" 
                                        class="w-full px-4 py-3 border rounded-xl"
                                        :class="{ 'border-red-500 bg-red-50': hasConflict }" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Selesai</label>
                                    <input type="time" v-model="editForm.jam_selesai" 
                                        class="w-full px-4 py-3 border rounded-xl"
                                        :class="{ 'border-red-500 bg-red-50': hasConflict }" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Dosen</label>
                                <select v-model="editForm.dosen_id" 
                                    class="w-full px-4 py-3 border rounded-xl"
                                    :class="{ 'border-red-500 bg-red-50': !conflictState.dosenAvailable }">
                                    <option :value="null">-- Pilih Dosen --</option>
                                    <option v-for="d in availableDosens" :key="d.id" :value="d.id">{{ d.nama_gelar }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Ruangan</label>
                                <select v-model="editForm.ruangan_id" 
                                    class="w-full px-4 py-3 border rounded-xl"
                                    :class="{ 'border-orange-500 bg-orange-50': !conflictState.ruanganAvailable }">
                                    <option :value="null">-- Pilih Ruangan --</option>
                                    <option v-for="r in availableRuangans" :key="r.id" :value="r.id">{{ r.nama }} ({{ r.gedung }})</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipe</label>
                                    <select v-model="editForm.tipe" class="w-full px-4 py-3 border rounded-xl">
                                        <option value="kuliah">Kuliah</option>
                                        <option value="uts">UTS</option>
                                        <option value="uas">UAS</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mode</label>
                                    <select v-model="editForm.mode" class="w-full px-4 py-3 border rounded-xl">
                                        <option value="online">Online</option>
                                        <option value="offline">Offline</option>
                                        <option value="hybrid">Hybrid</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                                <select v-model="editForm.status" class="w-full px-4 py-3 border rounded-xl">
                                    <option value="terjadwal">Terjadwal</option>
                                    <option value="selesai">Selesai</option>
                                    <option value="dibatalkan">Dibatalkan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan</label>
                                <textarea v-model="editForm.catatan" rows="2" class="w-full px-4 py-3 border rounded-xl"></textarea>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3">
                            <button @click="showEditModal = false" class="px-5 py-2.5 border rounded-xl font-semibold hover:bg-gray-100">Batal</button>
                            <button @click="submitEdit" :disabled="isSubmitting || hasConflict" 
                                class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold disabled:opacity-50 flex items-center gap-2"
                                :class="{ 'from-gray-400 to-gray-500 cursor-not-allowed': hasConflict }">
                                <CheckCircleIcon class="w-5 h-5" />
                                {{ isSubmitting ? 'Menyimpan...' : hasConflict ? 'Ada Konflik' : 'Simpan' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showDeleteModal = false">
                    <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl text-center">
                        <div class="p-8">
                            <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                                <TrashIcon class="w-8 h-8 text-red-600" />
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Jadwal?</h3>
                            <p class="text-gray-500">Yakin ingin menghapus <span class="font-bold text-gray-700">{{ deleteTargetName }}</span>?</p>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t flex justify-center gap-3">
                            <button @click="showDeleteModal = false" class="px-5 py-2.5 border rounded-xl font-semibold hover:bg-gray-100">Batal</button>
                            <button @click="confirmDelete" class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl font-bold">Ya, Hapus</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Toast -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0"
                leave-active-class="ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
                <div v-if="toast.show" :class="['fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3 font-semibold z-[100]', toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white']">
                    <CheckCircleIcon v-if="toast.type === 'success'" class="w-5 h-5" />
                    <XMarkIcon v-else class="w-5 h-5" />
                    {{ toast.message }}
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
