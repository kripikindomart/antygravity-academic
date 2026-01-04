<template>
    <AppLayout>
        <Head :title="isGlobal ? 'Edit Komponen Global' : 'Edit Komponen Nilai'" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('komponen-nilai.index')" class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ isGlobal ? 'Edit Komponen Global' : 'Edit Komponen Nilai' }}
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-1" v-if="isGlobal">
                            <span class="font-medium text-blue-600 dark:text-blue-400">🌐 Berlaku untuk semua Prodi (Default)</span>
                        </p>
                        <p class="text-gray-500 dark:text-gray-400 mt-1" v-else>
                            <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ prodi.jenjang }}</span> {{ prodi.nama }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div :class="isGlobal ? 'bg-gradient-to-r from-blue-600 to-indigo-600' : 'bg-gradient-to-r from-amber-500 to-orange-500'" class="rounded-2xl p-5 text-white shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                        <svg v-if="isGlobal" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg v-else class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-white/80 text-sm">Penting</p>
                        <p class="font-medium" v-if="isGlobal">Total bobot wajib <strong>100%</strong>. Komponen ini berlaku sebagai <strong>default untuk semua Prodi</strong> yang belum punya setting khusus.</p>
                        <p class="font-medium" v-else>Total bobot wajib <strong>100%</strong>. Komponen ini akan <strong>meng-override setting Global</strong> untuk Program Studi ini.</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <form @submit.prevent="submitForm">
                    <!-- Component List -->
                    <div class="p-6 space-y-4">
                        <div v-for="(comp, index) in form.components" :key="index" class="flex gap-4 items-start p-5 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-700 transition-colors">
                            <div class="flex items-center justify-center w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg text-primary-600 dark:text-primary-400 font-bold text-lg">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Komponen</label>
                                <input v-model="comp.nama" type="text" required placeholder="Contoh: Tugas, Quiz, UTS, UAS" class="block w-full px-4 py-3 bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500"/>
                            </div>
                            <div class="w-28">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bobot (%)</label>
                                <input v-model="comp.bobot" type="number" step="0.01" min="0" max="100" required placeholder="0" class="block w-full px-4 py-3 bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-center font-bold"/>
                            </div>
                            <div class="w-40">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Sumber Data</label>
                                <select v-model="comp.source_type" class="block w-full px-3 py-3 bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-sm">
                                    <option value="manual">Manual</option>
                                    <option value="kehadiran">Kehadiran</option>
                                </select>
                            </div>
                            <div class="pt-8">
                                <button type="button" @click="removeComponent(index)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Add Button -->
                        <button type="button" @click="addComponent" class="w-full flex items-center justify-center gap-2 px-4 py-4 border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-primary-400 dark:hover:border-primary-600 text-gray-500 hover:text-primary-600 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Komponen
                        </button>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-5 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <span class="text-lg font-bold text-gray-700 dark:text-gray-300">Total Bobot:</span>
                            <span class="text-3xl font-black" :class="Math.abs(totalBobot - 100) < 0.1 ? 'text-green-600' : 'text-red-600'">
                                {{ totalBobot }}%
                            </span>
                            <span v-if="Math.abs(totalBobot - 100) < 0.1" class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Valid
                            </span>
                            <span v-else class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-semibold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Harus 100%
                            </span>
                        </div>
                        <div class="flex gap-3">
                            <Link :href="route('komponen-nilai.index')" class="px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                Batal
                            </Link>
                            <button type="submit" :disabled="!isValid || form.processing" class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-medium rounded-xl disabled:opacity-50 disabled:cursor-not-allowed shadow-lg transition-all hover:scale-105">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    prodi: Object, // null for global
    komponens: Array,
});

const isGlobal = computed(() => !props.prodi);

const form = useForm({
    components: [],
});

onMounted(() => {
    if (props.komponens && props.komponens.length > 0) {
        form.components = props.komponens.map(c => ({
            nama: c.nama,
            bobot: parseFloat(c.bobot),
            source_type: c.source_type || 'manual',
        }));
    } else {
        // Default template
        form.components = [
            { nama: 'Kehadiran', bobot: 10, source_type: 'kehadiran' },
            { nama: 'Tugas', bobot: 20, source_type: 'manual' },
            { nama: 'UTS', bobot: 30, source_type: 'manual' },
            { nama: 'UAS', bobot: 40, source_type: 'manual' },
        ];
    }
});

const addComponent = () => {
    form.components.push({ nama: '', bobot: 0, source_type: 'manual' });
};

const removeComponent = (index) => {
    form.components.splice(index, 1);
};

const totalBobot = computed(() => {
    return form.components.reduce((sum, item) => sum + (parseFloat(item.bobot) || 0), 0);
});

const isValid = computed(() => {
    return Math.abs(totalBobot.value - 100) < 0.1 && form.components.length > 0;
});

const submitForm = () => {
    if (!isValid.value) return;
    
    if (isGlobal.value) {
        form.put(route('komponen-nilai.updateGlobal'));
    } else {
        form.put(route('komponen-nilai.update', props.prodi.id));
    }
};
</script>
