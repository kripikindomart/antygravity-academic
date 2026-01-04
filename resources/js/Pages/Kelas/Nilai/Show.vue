<template>
    <AppLayout>
        <Head title="Input Nilai" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('dosen.nilai.index')" class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Input Nilai</h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ kelasMatakuliah.kelas?.nama }} - {{ kelasMatakuliah.mata_kuliah?.nama }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="submitForm('save')" :disabled="isSubmitting" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Simpan Draft
                    </button>
                    <button @click="submitForm('submit')" :disabled="isSubmitting" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium rounded-xl shadow-lg shadow-green-500/30 transition-all hover:scale-105">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Submit Final
                    </button>
                </div>
            </div>

            <!-- Component Info Tags -->
            <div class="flex flex-wrap gap-2">
                <span v-for="comp in komponens" :key="comp.id" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ comp.nama }}</span>
                    <span class="px-2 py-0.5 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-sm font-bold rounded">{{ comp.bobot }}%</span>
                </span>
            </div>

            <!-- Spreadsheet Card -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-blue-600 to-cyan-500 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider w-12">No</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider min-w-[200px]">Mahasiswa</th>
                                <th v-for="comp in komponens" :key="comp.id" class="px-4 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider min-w-[120px]">
                                    <div>{{ comp.nama }}</div>
                                    <div class="text-[10px] opacity-80 font-normal">{{ comp.bobot }}%</div>
                                </th>
                                <th class="px-4 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider min-w-[120px] bg-blue-700/50">
                                    <div>Nilai Akhir</div>
                                    <div class="text-[10px] opacity-80 font-normal">Preview</div>
                                </th>
                                <th class="px-4 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider min-w-[100px]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="(mhs, idx) in mahasiswas" :key="mhs.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-4 py-3 text-center text-sm text-gray-500">{{ idx + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                            <span class="text-sm font-bold text-gray-600 dark:text-gray-400">{{ getInitials(mhs.nama) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ mhs.nama }}</p>
                                            <p class="text-xs text-gray-500">{{ mhs.nim }}</p>
                                        </div>
                                    </div>
                                </td>
                                
                                <td v-for="comp in komponens" :key="comp.id" class="px-2 py-2">
                                    <input 
                                        v-if="grades[mhs.id]"
                                        v-model="grades[mhs.id][comp.id]" 
                                        type="number" 
                                        min="0" 
                                        max="100" 
                                        step="0.01"
                                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-lg focus:border-primary-500 focus:ring-0 text-center font-medium transition-colors"
                                        placeholder="0"
                                    >
                                </td>

                                <td class="px-4 py-3 text-center bg-blue-50/50 dark:bg-blue-900/10">
                                    <div class="flex flex-col items-center">
                                        <span class="text-xl font-black text-gray-900 dark:text-white">{{ calcFinal(mhs.id).score }}</span>
                                        <span class="px-2 py-0.5 mt-1 rounded text-sm font-bold" :class="getGradeClass(calcFinal(mhs.id).letter)">
                                            {{ calcFinal(mhs.id).letter }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span v-if="rekaps[mhs.id]" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold capitalize"
                                        :class="{
                                            'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': rekaps[mhs.id].status === 'published',
                                            'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400': rekaps[mhs.id].status === 'submitted',
                                            'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400': rekaps[mhs.id].status === 'draft'
                                        }">
                                        <span :class="{
                                            'bg-green-500': rekaps[mhs.id].status === 'published',
                                            'bg-blue-500': rekaps[mhs.id].status === 'submitted',
                                            'bg-gray-400': rekaps[mhs.id].status === 'draft'
                                        }" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ rekaps[mhs.id].status }}
                                    </span>
                                    <span v-else class="text-gray-400 text-xs">-</span>
                                </td>
                            </tr>
                            <tr v-if="mahasiswas.length === 0">
                                <td :colspan="4 + komponens.length" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400 font-medium">Tidak ada mahasiswa terdaftar</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    kelasMatakuliah: Object,
    mahasiswas: Array,
    komponens: Array,
    scores: Object,
    rekaps: Object,
    skalaNilais: Array,
});

const grades = ref({});
const isSubmitting = ref(false);

onMounted(() => {
    props.mahasiswas.forEach(mhs => {
        grades.value[mhs.id] = {};
        props.komponens.forEach(comp => {
            let val = 0;
            if (props.scores[mhs.id]) {
                const found = props.scores[mhs.id].find(s => s.komponen_nilai_id === comp.id);
                if (found) val = found.nilai;
            }
            grades.value[mhs.id][comp.id] = val;
        });
    });
});

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const calcFinal = (mhsId) => {
    let total = 0;
    if (!grades.value[mhsId]) return { score: 0, letter: 'E' };

    props.komponens.forEach(comp => {
        const val = parseFloat(grades.value[mhsId][comp.id]) || 0;
        total += (val * parseFloat(comp.bobot) / 100);
    });

    let letter = 'E';
    for (const skala of props.skalaNilais) {
        if (total >= parseFloat(skala.min_nilai) && total <= parseFloat(skala.max_nilai)) {
            letter = skala.huruf;
            break;
        }
    }
    return { score: total.toFixed(2), letter };
};

const getGradeClass = (letter) => {
    const map = {
        'A': 'bg-green-100 text-green-700',
        'A-': 'bg-green-100 text-green-600',
        'B+': 'bg-blue-100 text-blue-700',
        'B': 'bg-blue-100 text-blue-600',
        'B-': 'bg-blue-100 text-blue-500',
        'C+': 'bg-yellow-100 text-yellow-700',
        'C': 'bg-yellow-100 text-yellow-600',
        'D': 'bg-orange-100 text-orange-600',
        'E': 'bg-red-100 text-red-600',
    };
    return map[letter] || 'bg-gray-100 text-gray-600';
};

const submitForm = (action) => {
    const gradesArray = [];
    for (const mhsId in grades.value) {
        for (const compId in grades.value[mhsId]) {
            gradesArray.push({
                mahasiswa_id: mhsId,
                komponen_nilai_id: compId,
                nilai: grades.value[mhsId][compId]
            });
        }
    }

    isSubmitting.value = true;
    router.post(route('dosen.nilai.store', props.kelasMatakuliah.id), {
        grades: gradesArray,
        action: action,
    }, {
        preserveScroll: true,
        onFinish: () => { isSubmitting.value = false; }
    });
};
</script>
