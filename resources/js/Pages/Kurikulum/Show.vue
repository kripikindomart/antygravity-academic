<template>
    <Head :title="`Kurikulum: ${kurikulum.nama}`" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-3xl p-8 text-white">
                <div class="absolute inset-0 bg-grid-white/10"></div>
                <div class="relative">
                    <Link href="/kurikulum" class="inline-flex items-center gap-2 text-white/80 hover:text-white mb-4 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Kembali ke Daftar
                    </Link>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">{{ kurikulum.kode }}</span>
                                <span :class="['px-3 py-1 rounded-full text-sm font-medium', kurikulum.is_active ? 'bg-green-500' : 'bg-gray-500']">
                                    {{ kurikulum.is_active ? 'Aktif' : 'Non-aktif' }}
                                </span>
                            </div>
                            <h1 class="text-3xl font-bold mt-2">{{ kurikulum.nama }}</h1>
                            <p class="text-indigo-100 mt-1">{{ kurikulum.prodi?.nama }} • Tahun {{ kurikulum.tahun }}</p>
                        </div>
                        <div class="flex gap-3">
                            <div class="text-center px-6 py-3 bg-white/10 backdrop-blur-sm rounded-xl">
                                <p class="text-3xl font-bold">{{ kurikulum.cpls?.length || 0 }}</p>
                                <p class="text-sm text-indigo-100">CPL</p>
                            </div>
                            <div class="text-center px-6 py-3 bg-white/10 backdrop-blur-sm rounded-xl">
                                <p class="text-3xl font-bold">{{ kurikulum.mata_kuliahs?.length || 0 }}</p>
                                <p class="text-sm text-indigo-100">Mata Kuliah</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex gap-2 bg-white dark:bg-gray-900 p-2 rounded-2xl shadow-sm">
                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="['flex-1 py-3 px-4 rounded-xl text-sm font-medium transition-all', activeTab === tab.id ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800']">
                    {{ tab.label }}
                </button>
            </div>

            <!-- CPL Tab -->
            <div v-if="activeTab === 'cpl'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Capaian Pembelajaran Lulusan (CPL)</h2>
                    <button @click="openCplModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah CPL
                    </button>
                </div>

                <!-- CPL List grouped by kategori -->
                <div v-for="kategori in categories" :key="kategori.value" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r" :class="kategori.gradient">
                        <h3 class="font-bold text-white">{{ kategori.label }}</h3>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <div v-for="cpl in getCplsByKategori(kategori.value)" :key="cpl.id" class="p-6 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="flex items-start gap-4">
                                <div class="w-16 h-16 bg-gradient-to-br rounded-xl flex items-center justify-center text-white font-bold" :class="kategori.gradient">
                                    {{ cpl.kode }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-gray-900 dark:text-white">{{ cpl.deskripsi }}</p>
                                    <div class="mt-2 flex items-center gap-4 text-sm text-gray-500">
                                        <span>{{ cpl.cpmks?.length || 0 }} CPMK terkait</span>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button @click="openCplModal(cpl)" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click="confirmDeleteCpl(cpl)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-if="getCplsByKategori(kategori.value).length === 0" class="p-6 text-center text-gray-400">
                            Belum ada CPL untuk kategori ini
                        </div>
                    </div>
                </div>
            </div>

            <!-- Matrix CPL-MK Tab -->
            <div v-if="activeTab === 'matrix'" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Pemetaan CPL → Mata Kuliah</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Semester</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode MK</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mata Kuliah</th>
                                <th v-for="cpl in sortedCpls" :key="cpl.id" class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase min-w-[60px]">
                                    {{ cpl.kode }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="mk in kurikulum.mata_kuliahs" :key="mk.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 text-sm text-gray-500">{{ mk.semester }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ mk.kode }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ mk.nama }}</td>
                                <td v-for="cpl in sortedCpls" :key="cpl.id" class="px-3 py-3 text-center">
                                    <span class="text-green-500">✓</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!kurikulum.mata_kuliahs?.length" class="p-12 text-center text-gray-400">
                    Belum ada Mata Kuliah yang ditambahkan ke kurikulum ini
                </div>
            </div>

            <!-- MK Tab -->
            <div v-if="activeTab === 'mk'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Mata Kuliah dalam Kurikulum</h2>
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah MK
                    </button>
                </div>

                <!-- MK By Semester -->
                <div v-for="sem in [1,2,3,4]" :key="sem" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white">Semester {{ sem }}</h3>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <div v-for="mk in getMkBySemester(sem)" :key="mk.id" class="p-4 flex items-center gap-4">
                            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">{{ mk.sks_teori + mk.sks_praktik }}</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ mk.nama }}</p>
                                <p class="text-sm text-gray-500">{{ mk.kode }} • {{ mk.jenis }}</p>
                            </div>
                        </div>
                        <div v-if="getMkBySemester(sem).length === 0" class="p-6 text-center text-gray-400">
                            Belum ada MK di semester ini
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CPL Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showCplModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="showCplModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full animate-modal-in overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6 text-white">
                            <h3 class="text-xl font-bold">{{ editingCpl ? 'Edit CPL' : 'Tambah CPL' }}</h3>
                        </div>
                        <form @submit.prevent="submitCplForm" class="p-8 space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Kode CPL <span class="text-red-500">*</span></label>
                                    <input v-model="cplForm.kode" type="text" required placeholder="CPL01" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-indigo-500"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Kategori <span class="text-red-500">*</span></label>
                                    <select v-model="cplForm.kategori" required class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-indigo-500">
                                        <option value="">Pilih Kategori</option>
                                        <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Deskripsi CPL <span class="text-red-500">*</span></label>
                                <textarea v-model="cplForm.deskripsi" rows="4" required placeholder="Deskripsi capaian pembelajaran..." class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-indigo-500"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Urutan</label>
                                <input v-model="cplForm.urutan" type="number" min="0" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-indigo-500"/>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="button" @click="showCplModal = false" class="flex-1 py-3 text-gray-600 dark:text-gray-400 font-medium hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl">Batal</button>
                                <button type="submit" :disabled="cplForm.processing" class="flex-1 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50">
                                    {{ cplForm.processing ? 'Menyimpan...' : 'Simpan' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    kurikulum: Object,
});

const activeTab = ref('cpl');
const showCplModal = ref(false);
const editingCpl = ref(null);

const tabs = [
    { id: 'cpl', label: 'Manajemen CPL' },
    { id: 'matrix', label: 'Pemetaan CPL → MK' },
    { id: 'mk', label: 'Mata Kuliah' },
];

const categories = [
    { value: 'sikap', label: 'Sikap', gradient: 'from-blue-500 to-blue-600' },
    { value: 'pengetahuan', label: 'Pengetahuan', gradient: 'from-green-500 to-green-600' },
    { value: 'keterampilan_umum', label: 'Keterampilan Umum', gradient: 'from-orange-500 to-orange-600' },
    { value: 'keterampilan_khusus', label: 'Keterampilan Khusus', gradient: 'from-purple-500 to-purple-600' },
];

const cplForm = useForm({
    kode: '',
    deskripsi: '',
    kategori: '',
    urutan: 0,
});

const sortedCpls = computed(() => {
    return [...(props.kurikulum.cpls || [])].sort((a, b) => a.urutan - b.urutan);
});

const getCplsByKategori = (kategori) => {
    return (props.kurikulum.cpls || []).filter(cpl => cpl.kategori === kategori).sort((a, b) => a.urutan - b.urutan);
};

const getMkBySemester = (semester) => {
    return (props.kurikulum.mata_kuliahs || []).filter(mk => mk.semester === semester);
};

const openCplModal = (cpl = null) => {
    editingCpl.value = cpl;
    if (cpl) {
        cplForm.kode = cpl.kode;
        cplForm.deskripsi = cpl.deskripsi;
        cplForm.kategori = cpl.kategori;
        cplForm.urutan = cpl.urutan || 0;
    } else {
        cplForm.reset();
        cplForm.urutan = (props.kurikulum.cpls?.length || 0) + 1;
    }
    showCplModal.value = true;
};

const submitCplForm = () => {
    if (editingCpl.value) {
        cplForm.put(`/kurikulum/cpl/${editingCpl.value.id}`, { preserveScroll: true, onSuccess: () => { showCplModal.value = false; } });
    } else {
        cplForm.post(`/kurikulum/${props.kurikulum.id}/cpl`, { preserveScroll: true, onSuccess: () => { showCplModal.value = false; } });
    }
};

const confirmDeleteCpl = (cpl) => {
    if (confirm(`Hapus CPL "${cpl.kode}"?`)) {
        router.delete(`/kurikulum/cpl/${cpl.id}`, { preserveScroll: true });
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
