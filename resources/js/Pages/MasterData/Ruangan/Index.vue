<template>
    <AppLayout>
        <Head title="Ruangan" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ruangan</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola data ruangan dan kapasitas</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="reloadData" class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 text-gray-600 rounded-xl" title="Refresh">
                        <svg class="w-5 h-5" :class="{'animate-spin': isLoading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <button @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-green-600 to-green-700 text-white font-medium rounded-xl shadow-lg shadow-green-500/30 hover:scale-105 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Ruangan
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
                    <select v-model="localFilters.tipe" @change="applyFilters" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl">
                        <option value="">Semua Tipe</option>
                        <option value="kelas">Kelas</option>
                        <option value="lab">Lab</option>
                        <option value="aula">Aula</option>
                        <option value="ruang_rapat">Ruang Rapat</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
            </div>

            <!-- Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="room in (ruangans?.data || [])" :key="room.id" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', getTipeClass(room.tipe)]">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ room.nama }}</h3>
                                <p class="text-sm text-gray-500">{{ room.kode }}</p>
                            </div>
                        </div>
                        <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full capitalize', getTipeBadge(room.tipe)]">{{ room.tipe }}</span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ room.kapasitas }}</p>
                            <p class="text-xs text-gray-500">Kapasitas</p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ room.gedung || '-' }}</p>
                            <p class="text-xs text-gray-500">Gedung / Lt. {{ room.lantai || 0 }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button @click="openModal(room)" class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            Edit
                        </button>
                        <button @click="confirmDelete(room)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Hapus">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>

                <div v-if="!ruangans?.data?.length" class="col-span-full text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 font-medium">Belum ada data ruangan</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="ruangans?.data?.length" class="flex items-center justify-between">
                <p class="text-sm text-gray-500">{{ ruangans.from || 0 }} - {{ ruangans.to || 0 }} dari {{ ruangans.total || 0 }}</p>
                <div class="flex gap-1">
                    <template v-for="link in (ruangans.links || [])" :key="link.label">
                        <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 rounded-lg text-sm', link.active ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100']" v-html="link.label"/>
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
                        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                    </div>
                                    <h2 class="text-xl font-bold text-white">{{ editingItem ? 'Edit' : 'Tambah' }} Ruangan</h2>
                                </div>
                                <button @click="showModal = false" class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="submitForm" class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Kode <span class="text-red-500">*</span></label>
                                    <input v-model="form.kode" type="text" required placeholder="R-101" class="block w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-primary-500"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tipe <span class="text-red-500">*</span></label>
                                    <select v-model="form.tipe" required class="block w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-primary-500">
                                        <option value="kelas">Kelas</option>
                                        <option value="lab">Lab</option>
                                        <option value="aula">Aula</option>
                                        <option value="ruang_rapat">Ruang Rapat</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama <span class="text-red-500">*</span></label>
                                <input v-model="form.nama" type="text" required placeholder="Ruang 101" class="block w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-primary-500"/>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Kapasitas <span class="text-red-500">*</span></label>
                                    <input v-model.number="form.kapasitas" type="number" required min="1" placeholder="30" class="block w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-primary-500"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Gedung</label>
                                    <input v-model="form.gedung" type="text" placeholder="A" class="block w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-primary-500"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Lantai</label>
                                    <input v-model.number="form.lantai" type="number" min="0" placeholder="1" class="block w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-primary-500"/>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                                <button type="button" @click="showModal = false" class="px-6 py-2.5 text-gray-700 font-medium rounded-xl hover:bg-gray-100">Batal</button>
                                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-gradient-to-r from-green-600 to-green-700 text-white font-medium rounded-xl disabled:opacity-50">
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
                <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 animate-modal-in">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Ruangan?</h3>
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
    ruangans: Object,
    filters: Object,
});

const localFilters = ref({
    search: props.filters?.search || '',
    tipe: props.filters?.tipe || '',
});

const isLoading = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingItem = ref(null);
const itemToDelete = ref(null);

const form = useForm({
    kode: '',
    nama: '',
    tipe: 'teori',
    kapasitas: 30,
    gedung: '',
    lantai: 1,
    is_active: true,
});

let searchTimeout = null;

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
};

const applyFilters = () => {
    router.get('/ruangan', localFilters.value, { preserveState: true, replace: true });
};

const reloadData = () => {
    isLoading.value = true;
    router.reload({ onFinish: () => { isLoading.value = false; } });
};

const getTipeClass = (tipe) => {
    const classes = {
        kelas: 'bg-blue-100 text-blue-600',
        lab: 'bg-green-100 text-green-600',
        aula: 'bg-purple-100 text-purple-600',
        ruang_rapat: 'bg-amber-100 text-amber-600',
        lainnya: 'bg-gray-100 text-gray-600',
    };
    return classes[tipe] || 'bg-gray-100 text-gray-600';
};

const getTipeBadge = (tipe) => {
    const classes = {
        kelas: 'bg-blue-100 text-blue-700',
        lab: 'bg-green-100 text-green-700',
        aula: 'bg-purple-100 text-purple-700',
        ruang_rapat: 'bg-amber-100 text-amber-700',
        lainnya: 'bg-gray-100 text-gray-700',
    };
    return classes[tipe] || 'bg-gray-100 text-gray-700';
};

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        form.kode = item.kode;
        form.nama = item.nama;
        form.tipe = item.tipe;
        form.kapasitas = item.kapasitas;
        form.gedung = item.gedung || '';
        form.lantai = item.lantai || 1;
    } else {
        form.reset();
        form.tipe = 'kelas';
        form.kapasitas = 30;
        form.lantai = 1;
    }
    showModal.value = true;
};

const submitForm = () => {
    if (editingItem.value) {
        form.put(`/ruangan/${editingItem.value.id}`, { onSuccess: () => { showModal.value = false; form.reset(); } });
    } else {
        form.post('/ruangan', { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    router.delete(`/ruangan/${itemToDelete.value.id}`, { 
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
