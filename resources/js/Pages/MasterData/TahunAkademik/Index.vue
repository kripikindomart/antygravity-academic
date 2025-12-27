<template>
    <AppLayout>
        <Head title="Tahun Akademik" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tahun Akademik</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola data tahun akademik dan semester</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="reloadData" class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl transition-all" title="Refresh">
                        <svg class="w-5 h-5" :class="{'animate-spin': isLoading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <button @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all hover:scale-105">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Tahun Akademik
                    </button>
                </div>
            </div>

            <!-- Active Info Card -->
            <div v-if="activeTahunAkademik" class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-2xl p-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white/70 text-sm">Tahun Akademik Aktif</p>
                            <p class="text-2xl font-bold">{{ activeTahunAkademik.nama }}</p>
                            <p class="text-white/70 text-sm">{{ activeTahunAkademik.kode }}</p>
                        </div>
                    </div>
                    <div class="px-4 py-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <p class="text-sm">{{ activeTahunAkademik.semesters_count || 0 }} Semester</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input v-model="localFilters.search" type="text" placeholder="Cari tahun akademik..." class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500/20" @input="debouncedSearch"/>
                        </div>
                    </div>
                    <select v-model="localFilters.status" @change="applyFilters" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Non-aktif</option>
                    </select>
                </div>
            </div>

            <!-- Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="ta in (tahunAkademiks?.data || [])" :key="ta.id" :class="['bg-white dark:bg-gray-900 rounded-2xl border-2 p-5 transition-all hover:shadow-lg', ta.is_active ? 'border-primary-500' : 'border-gray-100 dark:border-gray-800']">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ ta.nama }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ ta.kode }}</p>
                        </div>
                        <span v-if="ta.is_active" class="px-2.5 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-semibold rounded-full">Aktif</span>
                    </div>
                    
                    <div class="mb-4 space-y-2">
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Periode:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(ta.tanggal_mulai) }} - {{ formatDate(ta.tanggal_selesai) }}</span>
                            </div>
                        </div>
                        <!-- Semester List -->
                        <div v-if="ta.semesters?.length" class="grid grid-cols-2 gap-2">
                            <div v-for="sem in ta.semesters" :key="sem.id" :class="['p-2.5 rounded-lg border-2', sem.tipe === 'ganjil' ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800' : 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800']">
                                <p :class="['text-xs font-bold uppercase', sem.tipe === 'ganjil' ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400']">{{ sem.nama }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ formatDate(sem.tanggal_mulai) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button v-if="!ta.is_active" @click="setActive(ta)" class="flex-1 px-3 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-lg hover:bg-primary-100 transition-colors">
                            Set Aktif
                        </button>
                        <button @click="openModal(ta)" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors" title="Edit">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button v-if="!ta.is_active && (ta.semesters_count || 0) === 0" @click="confirmDelete(ta)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!tahunAkademiks?.data?.length" class="col-span-full text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Belum ada tahun akademik</p>
                    <p class="text-sm text-gray-400 mt-1">Klik tombol di atas untuk menambah</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="tahunAkademiks?.data?.length" class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Menampilkan {{ tahunAkademiks.from || 0 }} - {{ tahunAkademiks.to || 0 }} dari {{ tahunAkademiks.total || 0 }}</p>
                <div class="flex gap-1">
                    <template v-for="link in (tahunAkademiks.links || [])" :key="link.label">
                        <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', link.active ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100']" v-html="link.label"/>
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showModal = false">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-modal-in">
                        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-white">{{ editingItem ? 'Edit' : 'Tambah' }} Tahun Akademik</h2>
                                        <p class="text-white/70 text-sm">Semester Ganjil & Genap otomatis dibuat</p>
                                    </div>
                                </div>
                                <button @click="showModal = false" class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="submitForm" class="p-6 space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode <span class="text-red-500">*</span></label>
                                    <input v-model="form.kode" type="text" required placeholder="2024/2025" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama <span class="text-red-500">*</span></label>
                                    <input v-model="form.nama" type="text" required placeholder="TA 2024/2025" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500"/>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tanggal Mulai <span class="text-red-500">*</span></label>
                                    <DatePicker v-model="form.tanggal_mulai" placeholder="Pilih tanggal mulai..." />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tanggal Selesai <span class="text-red-500">*</span></label>
                                    <DatePicker v-model="form.tanggal_selesai" placeholder="Pilih tanggal selesai..." :min-date="form.tanggal_mulai" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div>
                                    <p class="font-medium text-gray-700 dark:text-gray-300">Status Aktif</p>
                                    <p class="text-sm text-gray-500">Jadikan tahun akademik aktif</p>
                                </div>
                                <label class="relative cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only peer"/>
                                    <div class="w-14 h-7 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-primary-600"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" @click="showModal = false" class="px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800">Batal</button>
                                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-medium rounded-xl disabled:opacity-50">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 animate-modal-in">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Hapus Tahun Akademik?</h3>
                        <p class="text-gray-500 mb-6">Yakin ingin menghapus <strong>{{ itemToDelete?.nama }}</strong>?</p>
                        <div class="flex gap-3 justify-center">
                            <button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200">Batal</button>
                            <button @click="executeDelete" class="px-5 py-2.5 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';

const props = defineProps({
    tahunAkademiks: Object,
    activeTahunAkademik: Object,
    filters: Object,
});

const localFilters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
});

const isLoading = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingItem = ref(null);
const itemToDelete = ref(null);

const currentYear = new Date().getFullYear();

const form = useForm({
    kode: '',
    nama: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
    is_active: false,
});

let searchTimeout = null;

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
};

const applyFilters = () => {
    router.get('/master/tahun-akademik', localFilters.value, { preserveState: true, replace: true });
};

const reloadData = () => {
    isLoading.value = true;
    router.reload({ onFinish: () => { isLoading.value = false; } });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        form.kode = item.kode;
        form.nama = item.nama;
        form.tanggal_mulai = item.tanggal_mulai?.split('T')[0] || '';
        form.tanggal_selesai = item.tanggal_selesai?.split('T')[0] || '';
        form.is_active = item.is_active;
    } else {
        form.reset();
        form.kode = `${currentYear}/${currentYear + 1}`;
        form.nama = `TA ${currentYear}/${currentYear + 1}`;
        form.tanggal_mulai = `${currentYear}-09-01`;
        form.tanggal_selesai = `${currentYear + 1}-08-31`;
    }
    showModal.value = true;
};

const submitForm = () => {
    if (editingItem.value) {
        form.put(`/master/tahun-akademik/${editingItem.value.id}`, { onSuccess: () => { showModal.value = false; form.reset(); } });
    } else {
        form.post('/master/tahun-akademik', { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const setActive = (item) => {
    router.post(`/master/tahun-akademik/${item.id}/set-active`, {}, { preserveScroll: true });
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    router.delete(`/master/tahun-akademik/${itemToDelete.value.id}`, { 
        onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; } 
    });
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.animate-modal-in { animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes modalIn { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
</style>
