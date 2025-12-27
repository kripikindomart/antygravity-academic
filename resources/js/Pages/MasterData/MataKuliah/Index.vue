<template>
    <AppLayout>
        <Head title="Mata Kuliah" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mata Kuliah</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola data mata kuliah dan SKS</p>
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
                        Tambah Mata Kuliah
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input v-model="localFilters.search" type="text" placeholder="Cari nama atau kode MK..." class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500/20" @input="debouncedSearch"/>
                    </div>
                    <select v-model="localFilters.prodi_id" @change="applyFilters" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 max-w-xs">
                        <option value="">Semua Program Studi</option>
                        <option v-for="prodi in prodis" :key="prodi.id" :value="prodi.id">{{ prodi.nama }} ({{ prodi.jenjang }})</option>
                    </select>
                    <select v-model="localFilters.semester" @change="applyFilters" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Semua Semester</option>
                        <option v-for="i in 8" :key="i" :value="i">Semester {{ i }}</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-primary-600 to-primary-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Kode</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Nama Mata Kuliah</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">SKS</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Smt</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Prodi</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Jenis</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-white uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="mk in (mataKuliahs?.data || [])" :key="mk.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-2 py-1 rounded">{{ mk.kode }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ mk.nama }}</div>
                                    <div v-if="mk.nama_en" class="text-xs text-gray-500 italic">{{ mk.nama_en }}</div>
                                    <div v-if="mk.prasyarat" class="mt-1 flex items-center gap-1">
                                        <span class="text-[10px] text-gray-400 uppercase tracking-wider">Syarat:</span>
                                        <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded">{{ mk.prasyarat.kode }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ mk.total_sks }}</span>
                                        <span class="text-xs text-gray-400">(T:{{ mk.sks_teori }} P:{{ mk.sks_praktik }})</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                    {{ mk.semester }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ mk.prodi?.nama }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full capitalize', mk.jenis === 'wajib' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400']">{{ mk.jenis }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openModal(mk)" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="confirmDelete(mk)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!mataKuliahs?.data?.length">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium">Belum ada mata kuliah</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div v-if="mataKuliahs?.data?.length" class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm text-gray-500">Menampilkan {{ mataKuliahs.from || 0 }} - {{ mataKuliahs.to || 0 }} dari {{ mataKuliahs.total || 0 }}</p>
                    <div class="flex gap-1">
                        <template v-for="link in (mataKuliahs.links || [])" :key="link.label">
                            <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', link.active ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 dark:text-gray-400']" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showModal = false">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden animate-modal-in">
                        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-white">{{ editingItem ? 'Edit' : 'Tambah' }} Mata Kuliah</h2>
                                        <p class="text-white/70 text-sm">Masuk sebagai data master kurikulum</p>
                                    </div>
                                </div>
                                <button @click="showModal = false" class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="submitForm" class="p-6 space-y-6">
                            <!-- Prodi Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Program Studi <span class="text-red-500">*</span></label>
                                <select v-model="form.prodi_id" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-gray-900 dark:text-white">
                                    <option value="" disabled>Pilih Program Studi</option>
                                    <option v-for="prodi in prodis" :key="prodi.id" :value="prodi.id">{{ prodi.nama }} ({{ prodi.jenjang }})</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode MK <span class="text-red-500">*</span></label>
                                    <input v-model="form.kode" type="text" required placeholder="MK001" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500"/>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Mata Kuliah (Indonesia) <span class="text-red-500">*</span></label>
                                    <input v-model="form.nama" type="text" required placeholder="Metodologi Penelitian" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500"/>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Inggris (Optional)</label>
                                <input v-model="form.nama_en" type="text" placeholder="Research Methodology" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 italic"/>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">SKS Teori <span class="text-red-500">*</span></label>
                                    <input v-model.number="form.sks_teori" type="number" min="0" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-center"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">SKS Praktik <span class="text-red-500">*</span></label>
                                    <input v-model.number="form.sks_praktik" type="number" min="0" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-center"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Semester <span class="text-red-500">*</span></label>
                                    <select v-model="form.semester" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-center">
                                        <option v-for="i in 8" :key="i" :value="i">{{ i }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jenis <span class="text-red-500">*</span></label>
                                    <select v-model="form.jenis" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-center capitalize">
                                        <option value="wajib">Wajib</option>
                                        <option value="pilihan">Pilihan</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Detailed info or Prerequisite -->
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Mata Kuliah Prasyarat (Optional)</label>
                                <select v-model="form.prasyarat_id" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500">
                                    <option :value="null">Tidak ada prasyarat</option>
                                    <template v-for="mk in availablePrasyarat" :key="mk.id">
                                        <option v-if="mk.id !== form.id && (!editingItem || mk.id !== editingItem.id)" :value="mk.id">
                                            {{ mk.kode }} - {{ mk.nama }}
                                        </option>
                                    </template>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Hanya menampilkan mata kuliah dalam prodi yang sama.</p>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" @click="showModal = false" class="px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Batal</button>
                                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-medium rounded-xl disabled:opacity-50 hover:shadow-lg hover:shadow-primary-500/30 transition-all">
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
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Hapus Mata Kuliah?</h3>
                        <p class="text-gray-500 mb-6">Yakin ingin menghapus <strong>{{ itemToDelete?.nama }}</strong>? Data kurikulum terkait mungkin akan terpengaruh.</p>
                        <div class="flex gap-3 justify-center">
                            <button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700">Batal</button>
                            <button @click="executeDelete" class="px-5 py-2.5 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 shadow-lg shadow-red-500/30">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    mataKuliahs: Object,
    prodis: Array,
    allMataKuliahs: Array,
    filters: Object,
});

const localFilters = ref({
    search: props.filters?.search || '',
    prodi_id: props.filters?.prodi_id || '',
    semester: props.filters?.semester || '',
});

const isLoading = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingItem = ref(null);
const itemToDelete = ref(null);

const form = useForm({
    id: null,
    prodi_id: '',
    kode: '',
    nama: '',
    nama_en: '',
    sks_teori: 2,
    sks_praktik: 0,
    semester: 1,
    jenis: 'wajib',
    prasyarat_id: null,
    deskripsi: '',
    is_active: true,
});

// Computed available prerequisites based on selected prodi
const availablePrasyarat = computed(() => {
    if (!form.prodi_id) return [];
    return props.allMataKuliahs.filter(mk => mk.prodi_id == form.prodi_id);
});

let searchTimeout = null;

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
};

const applyFilters = () => {
    router.get('/master/mata-kuliah', localFilters.value, { preserveState: true, replace: true });
};

const reloadData = () => {
    isLoading.value = true;
    router.reload({ onFinish: () => { isLoading.value = false; } });
};

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        form.id = item.id;
        form.prodi_id = item.prodi_id;
        form.kode = item.kode;
        form.nama = item.nama;
        form.nama_en = item.nama_en || '';
        form.sks_teori = item.sks_teori;
        form.sks_praktik = item.sks_praktik;
        form.semester = item.semester;
        form.jenis = item.jenis;
        form.prasyarat_id = item.prasyarat_id;
        form.is_active = !!item.is_active;
    } else {
        form.reset();
        form.id = null;
        // Preselect prodi if only one or filtered?
        // Default values
        form.sks_teori = 2;
        form.sks_praktik = 0;
        form.semester = 1;
        form.jenis = 'wajib';
    }
    showModal.value = true;
};

const submitForm = () => {
    if (editingItem.value) {
        form.put(`/master/mata-kuliah/${editingItem.value.id}`, { onSuccess: () => { showModal.value = false; form.reset(); } });
    } else {
        form.post('/master/mata-kuliah', { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    router.delete(`/master/mata-kuliah/${itemToDelete.value.id}`, { 
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
