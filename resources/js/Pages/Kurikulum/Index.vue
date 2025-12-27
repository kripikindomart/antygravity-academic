<template>
    <Head title="Kurikulum OBE" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-3xl p-8 text-white">
                <div class="absolute inset-0 bg-grid-white/10"></div>
                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold">Kurikulum OBE</h1>
                        <p class="text-indigo-100 mt-1">Kelola Kurikulum berbasis Outcome Based Education</p>
                    </div>
                    <button @click="openModal()" class="px-5 py-2.5 bg-white text-indigo-700 font-semibold rounded-xl hover:bg-indigo-50 transition-all flex items-center gap-2 shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Kurikulum
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-4 items-center">
                <div class="relative flex-1 max-w-md">
                    <input v-model="localFilters.search" @input="debouncedFilter" type="text" placeholder="Cari kurikulum..." class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-900 border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/20"/>
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <select v-model="localFilters.prodi" @change="applyFilters" class="px-4 py-2.5 bg-white dark:bg-gray-900 border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Semua Prodi</option>
                    <option v-for="prodi in prodis" :key="prodi.id" :value="prodi.id">{{ prodi.nama }}</option>
                </select>
                <select v-model="localFilters.status" @change="applyFilters" class="px-4 py-2.5 bg-white dark:bg-gray-900 border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Non-aktif</option>
                </select>
            </div>

            <!-- Kurikulum Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="kurikulum in kurikulums.data" :key="kurikulum.id" class="group bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden hover:shadow-lg transition-all">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="px-2 py-1 text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg">{{ kurikulum.kode }}</span>
                                <h3 class="mt-2 font-bold text-lg text-gray-900 dark:text-white">{{ kurikulum.nama }}</h3>
                                <p class="text-sm text-gray-500">{{ kurikulum.prodi?.nama }}</p>
                            </div>
                            <span :class="['px-2 py-1 text-xs font-medium rounded-full', kurikulum.is_active ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-500']">
                                {{ kurikulum.is_active ? 'Aktif' : 'Non-aktif' }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <p class="text-2xl font-bold text-indigo-600">{{ kurikulum.tahun }}</p>
                                <p class="text-xs text-gray-500">Tahun</p>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <p class="text-2xl font-bold text-purple-600">{{ kurikulum.cpls?.length || 0 }}</p>
                                <p class="text-xs text-gray-500">CPL</p>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <p class="text-2xl font-bold text-pink-600">{{ kurikulum.mata_kuliahs?.length || 0 }}</p>
                                <p class="text-xs text-gray-500">MK</p>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Link :href="`/kurikulum/${kurikulum.id}`" class="flex-1 py-2 text-center text-sm font-medium text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl hover:bg-indigo-100 transition-colors">
                                Kelola CPL
                            </Link>
                            <button @click="openModal(kurikulum)" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button @click="confirmDelete(kurikulum)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="kurikulums.data.length === 0" class="text-center py-16 bg-white dark:bg-gray-900 rounded-2xl">
                <div class="w-20 h-20 mx-auto bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Belum Ada Kurikulum</h3>
                <p class="text-gray-500 mb-4">Mulai dengan menambahkan kurikulum baru</p>
                <button @click="openModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700">Tambah Kurikulum</button>
            </div>

            <!-- Pagination -->
            <div v-if="kurikulums.data.length > 0" class="flex justify-center">
                <nav class="flex gap-1">
                    <Link v-for="link in kurikulums.links" :key="link.label" :href="link.url || '#'" :class="['px-4 py-2 rounded-lg text-sm', link.active ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-600 hover:bg-gray-100']" v-html="link.label" />
                </nav>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="showModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full animate-modal-in overflow-hidden max-h-[90vh] flex flex-col">
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6 text-white flex-shrink-0">
                            <h3 class="text-xl font-bold">{{ editingKurikulum ? 'Edit Kurikulum' : 'Tambah Kurikulum' }}</h3>
                        </div>
                        <form @submit.prevent="submitForm" class="p-6 space-y-4 overflow-y-auto flex-1">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Program Studi <span class="text-red-500">*</span></label>
                                <select v-model="form.prodi_id" required :disabled="isProdiLocked && !editingKurikulum" :class="['w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-indigo-500', isProdiLocked && !editingKurikulum ? 'cursor-not-allowed opacity-70' : '']">
                                    <option value="">Pilih Prodi</option>
                                    <option v-for="prodi in prodis" :key="prodi.id" :value="prodi.id">{{ prodi.nama }} ({{ prodi.jenjang?.toUpperCase() }})</option>
                                </select>
                                <p v-if="isProdiLocked && !editingKurikulum" class="text-xs text-gray-500 mt-1">* Prodi otomatis sesuai akun Anda</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Kode <span class="text-red-500">*</span></label>
                                    <input v-model="form.kode" type="text" required placeholder="K-2024-S2TI" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-indigo-500"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tahun <span class="text-red-500">*</span></label>
                                    <input v-model="form.tahun" type="number" required min="2000" max="2100" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-indigo-500"/>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Kurikulum <span class="text-red-500">*</span></label>
                                <input v-model="form.nama" type="text" required placeholder="Kurikulum 2024 S2 Teknik Informatika" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-indigo-500"/>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">SKS Wajib</label>
                                    <input v-model="form.total_sks_wajib" type="number" min="0" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-indigo-500"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">SKS Pilihan</label>
                                    <input v-model="form.total_sks_pilihan" type="number" min="0" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-indigo-500"/>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
                                <textarea v-model="form.deskripsi" rows="2" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-indigo-500"></textarea>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Status Aktif</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input v-model="form.is_active" type="checkbox" class="sr-only peer"/>
                                    <div class="w-11 h-6 bg-gray-200 peer-checked:bg-indigo-600 rounded-full peer after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                                </label>
                            </div>
                        </form>
                        <div class="flex gap-3 p-6 pt-0 flex-shrink-0">
                            <button type="button" @click="showModal = false" class="flex-1 py-3 text-gray-600 dark:text-gray-400 font-medium hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl">Batal</button>
                            <button @click="submitForm" :disabled="form.processing" class="flex-1 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    kurikulums: Object,
    prodis: Array,
    filters: Object,
    userProdiId: [Number, String],
});

// Check if user is bound to a single prodi
const isProdiLocked = !!props.userProdiId;

const showModal = ref(false);
const editingKurikulum = ref(null);
const localFilters = ref({
    search: props.filters?.search || '',
    prodi: props.filters?.prodi || '',
    status: props.filters?.status || '',
});

const form = useForm({
    prodi_id: '',
    kode: '',
    nama: '',
    tahun: new Date().getFullYear(),
    total_sks_wajib: 0,
    total_sks_pilihan: 0,
    deskripsi: '',
    is_active: true,
});

let debounceTimer;
const debouncedFilter = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
};

const applyFilters = () => {
    router.get('/kurikulum', localFilters.value, { preserveState: true, preserveScroll: true });
};

const openModal = (kurikulum = null) => {
    editingKurikulum.value = kurikulum;
    if (kurikulum) {
        form.prodi_id = kurikulum.prodi_id;
        form.kode = kurikulum.kode;
        form.nama = kurikulum.nama;
        form.tahun = kurikulum.tahun;
        form.total_sks_wajib = kurikulum.total_sks_wajib || 0;
        form.total_sks_pilihan = kurikulum.total_sks_pilihan || 0;
        form.deskripsi = kurikulum.deskripsi || '';
        form.is_active = kurikulum.is_active;
    } else {
        form.reset();
        form.tahun = new Date().getFullYear();
        form.is_active = true;
        // Auto-set prodi for bound users
        if (isProdiLocked) {
            form.prodi_id = props.userProdiId;
        }
    }
    showModal.value = true;
};

const submitForm = () => {
    if (editingKurikulum.value) {
        form.put(`/kurikulum/${editingKurikulum.value.id}`, { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    } else {
        form.post('/kurikulum', { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    }
};

const confirmDelete = (kurikulum) => {
    if (confirm(`Hapus kurikulum "${kurikulum.nama}"?`)) {
        router.delete(`/kurikulum/${kurikulum.id}`, { preserveScroll: true });
    }
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.animate-modal-in { animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes modalIn { from { transform: scale(0.9) translateY(20px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
.bg-grid-white\/10 { background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
</style>
