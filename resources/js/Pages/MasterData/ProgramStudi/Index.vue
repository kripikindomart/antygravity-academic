<template>
    <AppLayout>
        <Head title="Program Studi" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Program Studi</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola data program studi</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="reloadData" class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 text-gray-600 rounded-xl" title="Refresh">
                        <svg class="w-5 h-5" :class="{'animate-spin': isLoading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <button @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 hover:scale-105 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Prodi
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
                        <input v-model="localFilters.search" type="text" placeholder="Cari nama atau kode..." class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl" @input="debouncedSearch"/>
                    </div>
                    <select v-model="localFilters.jenjang" @change="applyFilters" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl">
                        <option value="">Semua Jenjang</option>
                        <option value="S1">S1</option>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
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
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Nama</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Jenjang</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Akreditasi</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Kaprodi</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-white uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="prodi in (programStudis?.data || [])" :key="prodi.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-sm font-semibold rounded-lg">{{ prodi.kode }}</span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ prodi.nama }}</td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full', getJenjangClass(prodi.jenjang)]">{{ prodi.jenjang }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ prodi.akreditasi || '-' }}</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                    <span v-if="prodi.kaprodi?.dosen">{{ prodi.kaprodi.dosen.nama }}</span>
                                    <span v-else class="text-gray-400 italic">Belum diset</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openModal(prodi)" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="confirmDelete(prodi)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Hapus">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!programStudis?.data?.length">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">Belum ada data program studi</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="programStudis?.data?.length" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ programStudis.from || 0 }} - {{ programStudis.to || 0 }} dari {{ programStudis.total || 0 }}</p>
                    <div class="flex gap-1">
                        <template v-for="link in (programStudis.links || [])" :key="link.label">
                            <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 rounded-lg text-sm', link.active ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100']" v-html="link.label"/>
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
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-modal-in">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-white">{{ editingItem ? 'Edit' : 'Tambah' }} Program Studi</h2>
                                        <p class="text-white/70 text-sm">Kaprodi/Sekprodi diatur terpisah</p>
                                    </div>
                                </div>
                                <button @click="showModal = false" class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="submitForm" class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode <span class="text-red-500">*</span></label>
                                    <input v-model="form.kode" type="text" required placeholder="MAN" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jenjang <span class="text-red-500">*</span></label>
                                    <select v-model="form.jenjang" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500">
                                        <option value="S2">S2</option>
                                        <option value="S1">S1</option>
                                        <option value="S3">S3</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama <span class="text-red-500">*</span></label>
                                <input v-model="form.nama" type="text" required placeholder="Magister Manajemen" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Akreditasi</label>
                                <input v-model="form.akreditasi" type="text" placeholder="A / Unggul" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500"/>
                            </div>
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                                <button type="button" @click="showModal = false" class="px-6 py-2.5 text-gray-700 font-medium rounded-xl hover:bg-gray-100">Batal</button>
                                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium rounded-xl disabled:opacity-50">
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
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Hapus Program Studi?</h3>
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

const props = defineProps({
    programStudis: Object,
    filters: Object,
});

const localFilters = ref({
    search: props.filters?.search || '',
    jenjang: props.filters?.jenjang || '',
});

const isLoading = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingItem = ref(null);
const itemToDelete = ref(null);

const form = useForm({
    kode: '',
    nama: '',
    jenjang: 'S2',
    akreditasi: '',
    is_active: true,
});

let searchTimeout = null;

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
};

const applyFilters = () => {
    router.get('/prodi', localFilters.value, { preserveState: true, replace: true });
};

const reloadData = () => {
    isLoading.value = true;
    router.reload({ onFinish: () => { isLoading.value = false; } });
};

const getJenjangClass = (jenjang) => {
    const classes = {
        S1: 'bg-green-100 text-green-700',
        S2: 'bg-blue-100 text-blue-700',
        S3: 'bg-purple-100 text-purple-700',
    };
    return classes[jenjang] || 'bg-gray-100 text-gray-700';
};

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        form.kode = item.kode;
        form.nama = item.nama;
        form.jenjang = item.jenjang;
        form.akreditasi = item.akreditasi || '';
    } else {
        form.reset();
        form.jenjang = 'S2';
    }
    showModal.value = true;
};

const submitForm = () => {
    if (editingItem.value) {
        form.put(`/prodi/${editingItem.value.id}`, { onSuccess: () => { showModal.value = false; form.reset(); } });
    } else {
        form.post('/prodi', { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    router.delete(`/prodi/${itemToDelete.value.id}`, { 
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
