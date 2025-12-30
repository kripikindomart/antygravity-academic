<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="font-bold text-lg dark:text-white">Kelola CPMK - {{ mataKuliah.nama }}</h3>
                <p class="text-sm text-gray-500">{{ mataKuliah.kode }} • Semester {{ mataKuliah.semester }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="generateAI" :disabled="isGeneratingAI" 
                    class="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-xs font-bold rounded-lg hover:from-purple-700 hover:to-indigo-700 transition shadow disabled:opacity-50">
                    <svg v-if="isGeneratingAI" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    {{ isGeneratingAI ? 'Generating...' : '✨ Generate AI' }}
                </button>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="py-8 text-center">
            <svg class="animate-spin h-8 w-8 text-indigo-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500">Memuat data CPMK...</p>
        </div>

        <div v-else class="space-y-6">
            <!-- CPMK Table (Matrix Input) -->
            <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                <table class="w-full min-w-max text-sm text-left">
                    <thead class="bg-indigo-900 text-white uppercase font-bold text-xs">
                        <tr>
                            <th rowspan="2" class="px-4 py-3 border-r border-indigo-800 w-32">Kode CPMK</th>
                            <th rowspan="2" class="px-4 py-3 border-r border-indigo-800 min-w-[300px]">Deskripsi CPMK</th>
                            <th :colspan="availableCpls.length" class="px-4 py-2 text-center border-b border-indigo-800">
                                Pemetaan CPMK ke CPL
                            </th>
                            <th rowspan="2" class="px-4 py-3 border-l border-indigo-800 w-24 text-center">Bobot (%)</th>
                            <th rowspan="2" class="px-4 py-3 w-20 text-center">Aksi</th>
                        </tr>
                        <tr>
                            <th v-for="cpl in availableCpls" :key="cpl.id" class="px-2 py-2 text-center border-r border-indigo-800 last:border-r-0 min-w-[60px]" :title="cpl.deskripsi">
                                {{ cpl.kode }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        <!-- Existing Data Rows -->
                        <tr v-for="(cpmk, idx) in cpmks" :key="cpmk.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <!-- View/Edit Mode -->
                            <td class="p-2 border-r border-gray-100 dark:border-gray-700 align-top">
                                <input v-if="cpmk.isEditing" v-model="cpmk.editData.kode" type="text" class="w-full px-2 py-1 text-sm border-gray-300 rounded focus:ring-indigo-500">
                                <span v-else class="font-bold block px-2">{{ cpmk.kode }}</span>
                            </td>
                            <td class="p-2 border-r border-gray-100 dark:border-gray-700 align-top">
                                <textarea v-if="cpmk.isEditing" v-model="cpmk.editData.deskripsi" rows="2" class="w-full px-2 py-1 text-sm border-gray-300 rounded focus:ring-indigo-500 resize-none"></textarea>
                                <p v-else class="px-2">{{ cpmk.deskripsi }}</p>
                                <!-- Sub CPMK Display -->
                                <div v-if="!cpmk.isEditing && cpmk.sub_cpmks && cpmk.sub_cpmks.length > 0" class="mt-2 mx-2 text-xs text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 p-2 rounded border border-slate-100 dark:border-slate-800">
                                    <p class="font-bold mb-1 text-slate-700 dark:text-slate-300">Sub-CPMK:</p>
                                    <ul class="space-y-1">
                                        <li v-for="sub in cpmk.sub_cpmks" :key="sub.id" class="flex gap-2">
                                            <span class="font-mono font-semibold bg-white dark:bg-slate-800 px-1 rounded border border-slate-200 dark:border-slate-700 text-[10px] h-fit">{{ sub.kode }}</span>
                                            <span>{{ sub.deskripsi }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </td>

                            <!-- CPL Checkbox Matrix (Single Choice Behavior) -->
                            <td v-for="cpl in availableCpls" :key="cpl.id" class="p-2 text-center border-r border-gray-100 dark:border-gray-700 align-top bg-gray-50/50">
                                <template v-if="cpmk.isEditing">
                                    <input type="radio" :name="`cpl_select_${cpmk.id || 'new'}`" :value="cpl.id" v-model="cpmk.editData.cpl_id" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                </template>
                                <template v-else>
                                    <span v-if="cpmk.cpl_id === cpl.id" class="text-indigo-600">
                                        <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                </template>
                            </td>

                             <td class="p-2 border-l border-gray-100 dark:border-gray-700 align-top text-center">
                                <input v-if="cpmk.isEditing" v-model="cpmk.editData.bobot" type="number" class="w-full px-2 py-1 text-sm border-gray-300 rounded focus:ring-indigo-500 text-center">
                                <span v-else>{{ cpmk.bobot }}</span>
                            </td>

                             <td class="p-2 align-top text-center">
                                <div class="flex justify-center gap-1">
                                    <template v-if="cpmk.isEditing">
                                        <button @click="saveLine(cpmk)" :disabled="isSaving" class="p-1.5 bg-green-500 text-white rounded hover:bg-green-600 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                        <button @click="cancelLine(cpmk)" class="p-1.5 bg-gray-500 text-white rounded hover:bg-gray-600 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button @click="editLine(cpmk)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded transition">
                                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        <button @click="deleteCpmk(cpmk)" class="p-1.5 text-red-600 hover:bg-red-50 rounded transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Empty State -->
                        <tr v-if="cpmks.length === 0 && !isAddingNew">
                            <td :colspan="4 + availableCpls.length" class="p-8 text-center text-gray-400">
                                Belum ada CPMK. Klik tombol di bawah untuk menambah.
                            </td>
                        </tr>

                        <!-- New Row -->
                        <tr v-if="isAddingNew" class="bg-indigo-50/20">
                            <td class="p-2 border-r border-gray-100 dark:border-gray-700 align-top">
                                <input v-model="newForm.kode" ref="focusInput" type="text" placeholder="Kode" class="w-full px-2 py-1 text-sm border-gray-300 rounded focus:ring-indigo-500">
                            </td>
                            <td class="p-2 border-r border-gray-100 dark:border-gray-700 align-top">
                                <textarea v-model="newForm.deskripsi" rows="2" placeholder="Deskripsi CPMK" class="w-full px-2 py-1 text-sm border-gray-300 rounded focus:ring-indigo-500 resize-none"></textarea>
                            </td>
                             <td v-for="cpl in availableCpls" :key="cpl.id" class="p-2 text-center border-r border-gray-100 dark:border-gray-700 align-top bg-white/50">
                                <input type="radio" name="new_cpl_select" :value="cpl.id" v-model="newForm.cpl_id" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 cursor-pointer">
                            </td>
                            <td class="p-2 border-l border-gray-100 dark:border-gray-700 align-top text-center">
                                <input v-model="newForm.bobot" type="number" placeholder="0" class="w-full px-2 py-1 text-sm border-gray-300 rounded focus:ring-indigo-500 text-center">
                            </td>
                            <td class="p-2 align-top text-center">
                                <div class="flex justify-center gap-1">
                                    <button @click="saveNew" :disabled="isSaving" class="p-1.5 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition shadow">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                     <button @click="isAddingNew = false" class="p-1.5 text-gray-500 hover:bg-gray-100 rounded transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button @click="startAdd" class="flex items-center gap-2 text-indigo-600 font-bold text-sm hover:underline px-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Baris CPMK
            </button>

            <!-- CPL Reference Table (Gambar 2) -->
             <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-800">
                <h4 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Referensi CPL Terpilih
                </h4>
                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-indigo-900 text-white text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 w-16 text-center">No</th>
                                <th class="px-4 py-3 w-24">Kode CPL</th>
                                <th class="px-4 py-3">Capaian Pembelajaran Lulusan (CPL)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            <tr v-if="availableCpls.length === 0">
                                <td colspan="3" class="p-4 text-center text-gray-500 italic">Belum ada CPL yang dipetakan ke mata kuliah ini. Silakan atur di tab Matrix dashboard.</td>
                            </tr>
                            <tr v-for="(cpl, index) in availableCpls" :key="cpl.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-center text-gray-500 font-medium">{{ index + 1 }}</td>
                                <td class="px-4 py-3 font-bold text-gray-700 dark:text-gray-300">{{ cpl.kode }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 leading-relaxed">{{ cpl.deskripsi }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    kurikulumId: [String, Number],
    mataKuliah: Object,
    availableCpls: Array,
});

const emit = defineEmits(['close', 'toast', 'updated']);

const cpmks = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);
const isAddingNew = ref(false);
const isGeneratingAI = ref(false);
const pendingNewRows = ref([]); // For multiple new rows
const focusInput = ref(null);

const newForm = ref({ kode: '', deskripsi: '', cpl_id: null, bobot: 0 });

const fetchCpmks = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/kurikulum/${props.kurikulumId}/mk/${props.mataKuliah.id}/cpmk`);
        cpmks.value = response.data.map(item => ({
            ...item,
            isEditing: false,
            editData: {}
        }));
    } catch (error) {
        emit('toast', 'Gagal memuat CPMK', 'error');
    } finally {
        isLoading.value = false;
    }
};

const startAdd = async () => {
    isAddingNew.value = true;
    newForm.value = { kode: '', deskripsi: '', cpl_id: null, bobot: 0 };
    await nextTick();
    focusInput.value?.focus();
};

const saveNew = async () => {
    if (!newForm.value.kode || !newForm.value.deskripsi || !newForm.value.cpl_id) {
        emit('toast', 'Lengkapi Kode, Deskripsi, dan pilih CPL.', 'error');
        return;
    }
    isSaving.value = true;
    try {
        const payload = { ...newForm.value, mata_kuliah_id: props.mataKuliah.id };
        const response = await axios.post('/kurikulum/cpmk', payload);
        cpmks.value.push({ ...response.data.cpmk, isEditing: false, editData: {} });
        // Reset form for next entry (keep form open for multi-row add)
        newForm.value = { kode: '', deskripsi: '', cpl_id: newForm.value.cpl_id, bobot: 0 };
        emit('toast', 'CPMK ditambahkan', 'success');
        emit('updated');
    } catch (error) {
        emit('toast', error.response?.data?.message || 'Gagal simpan', 'error');
    } finally {
        isSaving.value = false;
    }
};

// AI Generate CPMK
const generateAI = async () => {
    if (cpmks.value.length > 0 && !confirm('Sudah ada CPMK. Generate AI akan menambahkan CPMK baru. Lanjutkan?')) return;
    if (props.availableCpls.length === 0) {
        emit('toast', 'Tidak ada CPL yang terpetakan ke MK ini!', 'error');
        return;
    }
    isGeneratingAI.value = true;
    try {
        const response = await axios.post(`/kurikulum/${props.kurikulumId}/mk/${props.mataKuliah.id}/cpmk/generate-ai`);
        if (response.data.cpmks?.length) {
            response.data.cpmks.forEach(c => {
                cpmks.value.push({ ...c, isEditing: false, editData: {} });
            });
            emit('toast', `✨ ${response.data.cpmks.length} CPMK di-generate!`, 'success');
            emit('updated');
        } else {
            emit('toast', 'AI tidak menghasilkan CPMK.', 'warning');
        }
    } catch (error) {
        emit('toast', error.response?.data?.message || 'Gagal generate AI', 'error');
    } finally {
        isGeneratingAI.value = false;
    }
};

const editLine = (cpmk) => {
    cpmk.editData = { 
        kode: cpmk.kode, 
        deskripsi: cpmk.deskripsi, 
        cpl_id: cpmk.cpl_id, 
        bobot: cpmk.bobot 
    };
    cpmk.isEditing = true;
};

const cancelLine = (cpmk) => {
    cpmk.isEditing = false;
};

const saveLine = async (cpmk) => {
     if (!cpmk.editData.kode || !cpmk.editData.deskripsi || !cpmk.editData.cpl_id) {
        emit('toast', 'Data tidak lengkap.', 'error');
        return;
    }
    isSaving.value = true;
    try {
        const response = await axios.put(`/kurikulum/cpmk/${cpmk.id}`, cpmk.editData);
        Object.assign(cpmk, response.data.cpmk);
        cpmk.isEditing = false;
        emit('toast', 'CPMK diperbarui success', 'success');
        emit('updated');
    } catch (error) {
        emit('toast', 'Gagal update', 'error');
    } finally {
        isSaving.value = false;
    }
};

const deleteCpmk = async (cpmk) => {
    if (!confirm('Hapus CPMK ini?')) return;
    try {
        await axios.delete(`/kurikulum/cpmk/${cpmk.id}`);
        cpmks.value = cpmks.value.filter(c => c.id !== cpmk.id);
        emit('toast', 'CPMK dihapus', 'success');
        emit('updated');
    } catch (error) {
        emit('toast', 'Gagal hapus', 'error');
    }
};

onMounted(() => {
    fetchCpmks();
});
</script>
