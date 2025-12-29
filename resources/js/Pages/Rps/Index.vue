<script setup>
import AppLayout from '../../Components/Layout/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, onMounted, computed } from 'vue';
import { debounce } from 'lodash';
import { 
    MagnifyingGlassIcon, PencilSquareIcon, DocumentPlusIcon, CheckBadgeIcon, 
    ClockIcon, SparklesIcon, BookOpenIcon, ExclamationTriangleIcon, InboxIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    mataKuliahs: Object,
    filters: Object,
});

// DEBUG
onMounted(() => {
    console.log('🚀 RPS Index v3.0 with Filters - ' + new Date().toISOString());
});

const search = ref(props.filters?.search || '');
const activeFilter = ref('all');

watch(search, debounce((value) => {
    router.get(route('rps.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

// Filtered data based on activeFilter
const filteredMataKuliahs = computed(() => {
    let data = props.mataKuliahs.data;
    
    switch (activeFilter.value) {
        case 'approved':
            return data.filter(m => m.rps?.status === 'approved');
        case 'submitted':
            return data.filter(m => m.rps?.status === 'submitted');
        case 'draft':
            return data.filter(m => m.rps?.status === 'draft');
        case 'none':
            return data.filter(m => !m.rps);
        default:
            return data;
    }
});

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved':
            return { class: 'bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow-lg', text: 'Disetujui', icon: CheckBadgeIcon };
        case 'submitted':
            return { class: 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white shadow-lg', text: 'Diajukan', icon: ClockIcon };
        case 'draft':
            return { class: 'bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow-lg', text: 'Draft', icon: PencilSquareIcon };
        default:
            return { class: 'bg-gradient-to-r from-gray-300 to-gray-400 text-gray-700 shadow-md', text: 'Belum Ada', icon: DocumentPlusIcon };
    }
};
</script>

<template>
    <AppLayout title="Rencana Pembelajaran Semester">
        <template #header>
            <!-- STUNNING GRADIENT HEADER -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 p-6 shadow-2xl">
                <!-- Animated Background -->
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute top-0 right-0 w-72 h-72 bg-white rounded-full blur-3xl animate-pulse"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-pink-300 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
                </div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-white drop-shadow-lg flex items-center gap-3">
                            <SparklesIcon class="w-8 h-8" />
                            Rencana Pembelajaran Semester
                        </h1>
                        <p class="text-white/80 mt-2 text-sm">Kelola RPS untuk setiap mata kuliah aktif dengan mudah</p>
                    </div>
                    <div class="flex items-center gap-3 text-white/90 text-sm bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2 border border-white/20">
                        <span class="font-bold">{{ mataKuliahs.data.length }}</span>
                        <span>Mata Kuliah</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white">
                                <BookOpenIcon class="w-7 h-7" />
                            </div>
                            <div>
                                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ mataKuliahs.data.length }}</p>
                                <p class="text-xs text-gray-500">Total MK</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white">
                                <CheckBadgeIcon class="w-7 h-7" />
                            </div>
                            <div>
                                <p class="text-2xl font-black text-green-600">{{ mataKuliahs.data.filter(m => m.rps?.status === 'approved').length }}</p>
                                <p class="text-xs text-gray-500">Disetujui</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white">
                                <PencilSquareIcon class="w-7 h-7" />
                            </div>
                            <div>
                                <p class="text-2xl font-black text-amber-600">{{ mataKuliahs.data.filter(m => m.rps?.status === 'draft').length }}</p>
                                <p class="text-xs text-gray-500">Draft</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center text-white">
                                <ExclamationTriangleIcon class="w-7 h-7" />
                            </div>
                            <div>
                                <p class="text-2xl font-black text-gray-600">{{ mataKuliahs.data.filter(m => !m.rps).length }}</p>
                                <p class="text-xs text-gray-500">Belum Ada</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    
                    <!-- Toolbar with Search and Filter -->
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <!-- Search -->
                            <div class="relative w-full md:w-96">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <input v-model="search" type="text" placeholder="Cari Kode atau Nama Mata Kuliah..." 
                                    class="pl-12 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition"
                                >
                            </div>
                            
                            <!-- Filter Buttons -->
                            <div class="flex flex-wrap gap-2">
                                <button @click="activeFilter = 'all'" 
                                    :class="['px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2', activeFilter === 'all' ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-50']">
                                    Semua
                                </button>
                                <button @click="activeFilter = 'approved'" 
                                    :class="['px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2', activeFilter === 'approved' ? 'bg-green-600 text-white shadow-lg' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-50']">
                                    <CheckBadgeIcon class="w-4 h-4" /> Disetujui
                                </button>
                                <button @click="activeFilter = 'draft'" 
                                    :class="['px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2', activeFilter === 'draft' ? 'bg-amber-500 text-white shadow-lg' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-50']">
                                    <PencilSquareIcon class="w-4 h-4" /> Draft
                                </button>
                                <button @click="activeFilter = 'none'" 
                                    :class="['px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2', activeFilter === 'none' ? 'bg-gray-600 text-white shadow-lg' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-50']">
                                    <ExclamationTriangleIcon class="w-4 h-4" /> Belum Ada
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- List Content -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/80 dark:bg-gray-700/50 text-xs text-gray-500 uppercase tracking-wider font-bold border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-6 py-4">Mata Kuliah</th>
                                    <th class="px-6 py-4">SKS / Semester</th>
                                    <th class="px-6 py-4">Status RPS</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                <tr v-for="mk in filteredMataKuliahs" :key="mk.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shadow-sm">
                                                {{ mk.kode.substring(0, 3) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900 dark:text-gray-100">{{ mk.nama }}</div>
                                                <div class="text-xs text-gray-500 font-mono">{{ mk.kode }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ mk.sks_teori + mk.sks_praktik }} SKS • Sem {{ mk.semester }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border', getStatusBadge(mk.rps?.status).class]">
                                            <component :is="getStatusBadge(mk.rps?.status).icon" class="w-3.5 h-3.5 mr-1" />
                                            {{ getStatusBadge(mk.rps?.status).text }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Link :href="route('rps.create', { mata_kuliah: mk.id })" 
                                            class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50 hover:text-indigo-600 transition group-hover:border-indigo-300"
                                        >
                                            <PencilSquareIcon class="w-4 h-4 mr-1.5" />
                                            {{ mk.rps ? 'Edit RPS' : 'Buat RPS' }}
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="filteredMataKuliahs.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center gap-3">
                                            <InboxIcon class="w-16 h-16 text-gray-300" />
                                            <p>Tidak ada mata kuliah yang ditemukan.</p>
                                            <button v-if="activeFilter !== 'all'" @click="activeFilter = 'all'" 
                                                class="text-indigo-600 hover:underline font-bold">
                                                Tampilkan Semua
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
