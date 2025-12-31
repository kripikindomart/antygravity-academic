<script setup>
import AppLayout from '../../Components/Layout/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    Cog6ToothIcon, UserCircleIcon, CheckIcon, ArrowLeftIcon,
    AcademicCapIcon, BuildingOfficeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    prodis: Array,
});

const editingProdiId = ref(null);
const form = useForm({
    prodi_id: null,
    kaprodi_id: null,
    koordinator_rmk_id: null,
});

const startEdit = (prodi) => {
    editingProdiId.value = prodi.id;
    form.prodi_id = prodi.id;
    form.kaprodi_id = prodi.kaprodi_id;
    form.koordinator_rmk_id = prodi.koordinator_rmk_id;
};

const cancelEdit = () => {
    editingProdiId.value = null;
    form.reset();
};

const saveEdit = () => {
    form.post(route('rps-settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            editingProdiId.value = null;
        }
    });
};
</script>

<template>
    <AppLayout title="Pengaturan RPS">
        <template #header>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-indigo-50 rounded-lg text-indigo-600">
                        <Cog6ToothIcon class="w-8 h-8" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Pengaturan Struktural RPS</h1>
                        <p class="text-gray-500 text-sm mt-1">Kelola Kaprodi & Koordinator RMK per Program Studi</p>
                    </div>
                </div>
                <Link :href="route('rps.index')" class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition">
                    <ArrowLeftIcon class="w-4 h-4" />
                    Kembali ke RPS
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-semibold text-xs border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">Program Studi</th>
                                <th class="px-6 py-4">Ketua Prodi</th>
                                <th class="px-6 py-4">Koordinator RMK</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="prodi in prodis" :key="prodi.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
                                            <BuildingOfficeIcon class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900">{{ prodi.nama }}</div>
                                            <div class="text-xs text-gray-500">{{ prodi.kode }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <template v-if="editingProdiId === prodi.id">
                                        <select v-model="form.kaprodi_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option :value="null">-- Pilih Kaprodi --</option>
                                            <option v-for="d in prodi.dosens" :key="d.id" :value="d.id">{{ d.name }}</option>
                                        </select>
                                    </template>
                                    <template v-else>
                                        <div v-if="prodi.kaprodi_name" class="flex items-center gap-2">
                                            <AcademicCapIcon class="w-4 h-4 text-emerald-500" />
                                            <span class="text-gray-700 font-medium">{{ prodi.kaprodi_name }}</span>
                                        </div>
                                        <span v-else class="text-gray-400 italic text-xs">- Belum diset -</span>
                                    </template>
                                </td>
                                <td class="px-6 py-4">
                                    <template v-if="editingProdiId === prodi.id">
                                        <select v-model="form.koordinator_rmk_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option :value="null">-- Pilih Koordinator RMK --</option>
                                            <option v-for="d in prodi.dosens" :key="d.id" :value="d.id">{{ d.name }}</option>
                                        </select>
                                    </template>
                                    <template v-else>
                                        <div v-if="prodi.koordinator_rmk_name" class="flex items-center gap-2">
                                            <UserCircleIcon class="w-4 h-4 text-blue-500" />
                                            <span class="text-gray-700 font-medium">{{ prodi.koordinator_rmk_name }}</span>
                                        </div>
                                        <span v-else class="text-gray-400 italic text-xs">- Belum diset -</span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <template v-if="editingProdiId === prodi.id">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="saveEdit" :disabled="form.processing"
                                                class="px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 transition disabled:opacity-50">
                                                <CheckIcon class="w-4 h-4 inline -mt-0.5 mr-1" /> Simpan
                                            </button>
                                            <button @click="cancelEdit" class="px-3 py-1.5 bg-gray-200 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-300 transition">
                                                Batal
                                            </button>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <button @click="startEdit(prodi)" class="px-3 py-1.5 bg-indigo-50 text-indigo-600 text-xs font-bold rounded-lg hover:bg-indigo-100 transition">
                                            Edit
                                        </button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="prodis.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    Tidak ada data Program Studi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
