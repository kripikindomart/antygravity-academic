<script setup>
import AppLayout from '../../Components/Layout/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { ArrowLeftIcon, PrinterIcon, ExclamationTriangleIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    kelas: Object,
    defaultGroup: Object, // { dates: [], mks: [] }
    customGroup: Object,  // { dates: [], mks: [] }
    conflicts: Array,
    allMks: Array, // For settings
});

// Settings Modal Logic
const showSettings = ref(false);
// State for Drag & Drop
const settingsGroup1 = ref([]);
const settingsGroup2 = ref([]);
const draggingItem = ref(null);
const draggingFromGroup = ref(null);

const form = useForm({
    settings: []
});

const openSettings = () => {
    // Populate form with all MKs data derived from currently visible groups to get rich data
    // Or rely on props.allMks but we need time/dosen info which might be sparsely available in allMks if not passed
    // We can merge data from defaultGroup/customGroup into allMks
    
    // Helper to find rich data
    const findRichData = (id) => {
        const inDefault = props.defaultGroup.mks.find(m => m.id === id); // Note: mks struct might be different
        // Actually props.defaultGroup.mks items DON'T have 'id' at top level based on controller?
        // Let's check Controller. 
        // Controller: $mkData doesn't have ID explicit? Wait.
        // Controller: $mkData = [...]
        // NO ID in $defaultGroup['mks'] items!
        // We need to fix Controller to pass ID in MKS structure or match by code/name.
        // BUT props.allMks DOES have ID.
        // For visual, we want Waktu/SKS/Dosen. 
        // Let's try to match by 'nama' or 'kode' if ID missing in group data.
        
        // Actually, let's look at what we have in allMks
        const basic = props.allMks.find(m => m.id === id);
        return basic;
    };

    const g1 = [];
    const g2 = [];

    props.allMks.forEach(mk => {
        // Enriched MK object for the card
        // We'll try to find schedule info from the groups if possible, or just use basic info
        
        // Find matching MK in groups to get 'waktu' and 'dosens' string
        let rich = props.defaultGroup.mks.find(m => m.kode === mk.kode) || 
                   props.customGroup.mks.find(m => m.kode === mk.kode);
        
        const card = {
            id: mk.id,
            kode: mk.kode || '-', // allMks needs kode. Controller map: id, nama, matrix_group. MISSING KODE/SKS/DOSEN/WAKTU inside allMks map!
            nama: mk.nama,
            sks: rich ? rich.sks : '-',
            waktu: rich ? rich.waktu : '-',
            dosens: rich ? rich.dosens : '-',
            group: mk.matrix_group || 1
        };

        if (card.group === 2) {
            g2.push(card);
        } else {
            g1.push(card);
        }
    });

    settingsGroup1.value = g1;
    settingsGroup2.value = g2;
    showSettings.value = true;
};

const startDrag = (evt, item, groupNum) => {
    draggingItem.value = item;
    draggingFromGroup.value = groupNum;
    evt.dataTransfer.dropEffect = 'move';
    evt.dataTransfer.effectAllowed = 'move';
    evt.dataTransfer.setData('itemId', item.id);
};

const onDrop = (evt, targetGroupNum) => {
    const itemId = parseInt(evt.dataTransfer.getData('itemId'));
    if (!itemId) return;

    // Remove from source
    if (draggingFromGroup.value === 1) {
        const idx = settingsGroup1.value.findIndex(i => i.id === itemId);
        if (idx > -1) settingsGroup1.value.splice(idx, 1);
    } else {
        const idx = settingsGroup2.value.findIndex(i => i.id === itemId);
        if (idx > -1) settingsGroup2.value.splice(idx, 1);
    }

    // Add to target
    const item = draggingItem.value;
    item.group = targetGroupNum; // Update internal group flag
    
    if (targetGroupNum === 1) {
        settingsGroup1.value.push(item);
    } else {
        settingsGroup2.value.push(item);
    }

    draggingItem.value = null;
    draggingFromGroup.value = null;
    
    // Auto-save for "Live Edit" feel
    saveSettings();
};

// Toggle Edit Mode inside Dropdown/Cards
const toggleEdit = (mk) => {
    // Close others
    [...settingsGroup1.value, ...settingsGroup2.value].forEach(m => {
        if (m.id !== mk.id) m._isEditing = false;
    });
    mk._isEditing = !mk._isEditing;
};

const saveSettings = () => {
    // Reconstruct the flat array for the API
    const merged = [
        ...settingsGroup1.value.map(m => ({ id: m.id, group: 1 })),
        ...settingsGroup2.value.map(m => ({ id: m.id, group: 2 }))
    ];
    
    form.settings = merged; // Update the form payload
    
    // Use proper route name found in web.php
    form.post(route('kelas.update-matrix-settings', props.kelas.id), {
        preserveScroll: true,
        preserveState: true, // Keep modal open so user sees result? Or maybe just let it refresh the props behind scenes?
        // Actually, if we refresh props, the whole matrix updates "Live".
        onSuccess: () => {
             // Optional: Toast notification "Tersimpan"
        },
    });
};


// Split MKs into chunks of 3
const chunkArray = (arr, size) => {
    const chunks = [];
    if (!arr) return [];
    for (let i = 0; i < arr.length; i += size) {
        chunks.push(arr.slice(i, i + size));
    }
    return chunks;
};

const defaultChunks = computed(() => chunkArray(props.defaultGroup.mks, 3));
const customChunks = computed(() => chunkArray(props.customGroup.mks, 3));

// Get unique sorted dates present in a specific chunk of MKs, filling in gaps based on pattern
const getChunkDates = (chunk) => {
    // 1. Collect all actual dates from the chunk
    const actualDates = new Set();
    chunk.forEach(mk => {
        if (mk.dates) Object.keys(mk.dates).forEach(d => actualDates.add(d));
    });
    
    // 2. Identify target weekdays from Default Group (Template)
    const templateDays = new Set();
    if (props.defaultGroup.dates) {
        props.defaultGroup.dates.forEach(d => templateDays.add(new Date(d).getDay()));
    }
    
    // Also add any days present in this chunk
    actualDates.forEach(d => templateDays.add(new Date(d).getDay()));
    
    // 3. Group actual dates by 'Week Starting Date' (Monday)
    const weeksList = new Set();
    // Use actual dates from THIS chunk + default dates to determine full range of weeks? 
    // Actually, user wants "alignment". So we should arguably use week ranges from the group? 
    // But for Custom Group, weeks are different (Dec vs Oct).
    // So we just stick to weeks present in the data for this chunk.
    const datesToConsider = actualDates.size > 0 ? actualDates : new Set();
    
    datesToConsider.forEach(dateStr => {
        const d = new Date(dateStr);
        const day = d.getDay(); 
        const diff = day === 0 ? 6 : day - 1; 
        const monday = new Date(d);
        monday.setDate(d.getDate() - diff); 
        monday.setHours(0,0,0,0);
        weeksList.add(monday.getTime());
    });
    
    // 4. Generate all dates and check for data existence
    const finalDates = [];
    const seenDates = new Set();
    const sortedWeeks = Array.from(weeksList).sort((a,b) => a - b);
    
    sortedWeeks.forEach(mondayTs => {
        const monday = new Date(mondayTs);
        
        templateDays.forEach(dayIdx => {
            const offset = dayIdx === 0 ? 6 : dayIdx - 1;
            const targetDate = new Date(monday);
            targetDate.setDate(monday.getDate() + offset);
            
            const y = targetDate.getFullYear();
            const m = String(targetDate.getMonth() + 1).padStart(2, '0');
            const d = String(targetDate.getDate()).padStart(2, '0');
            const dateStr = `${y}-${m}-${d}`;
            
            if (!seenDates.has(dateStr)) {
                seenDates.add(dateStr);
                // Check if this date actually has data in the current chunk
                const hasData = chunk.some(mk => mk.dates && mk.dates[dateStr]);
                finalDates.push({
                    date: dateStr,
                    hasData: hasData
                });
            }
        });
    });
    
    return finalDates.sort((a,b) => a.date.localeCompare(b.date));
};

// Format date
const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return {
        day: date.getDate(),
        month: date.toLocaleDateString('id-ID', { month: 'short' }),
        year: date.getFullYear()
    };
};

const getDayName = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { weekday: 'short' }).toUpperCase();
};

const getCellData = (mk, dateStr) => mk.dates[dateStr] || null;

const printMatrix = () => window.print();
</script>

<template>
    <Head :title="`Jadwal Matrix - ${kelas.nama}`" />
    <AppLayout>
        <div class="min-h-screen bg-gray-50 py-6 print:bg-white print:py-0">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 print:px-2">
                
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between print:mb-4">
                    <div class="flex items-center gap-4">
                        <Link :href="route('kelas.show', kelas.id)" class="text-gray-500 hover:text-gray-700 print:hidden">
                            <ArrowLeftIcon class="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 print:text-lg">Draft Jadwal Perkuliahan</h1>
                            <p class="text-sm text-gray-500">{{ kelas.prodi?.nama || 'Program Studi' }} • {{ kelas.nama }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="openSettings" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 print:hidden">
                            <Cog6ToothIcon class="w-5 h-5" />
                            Pengaturan
                        </button>
                        <button @click="printMatrix" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 print:hidden">
                            <PrinterIcon class="w-5 h-5" />
                            Cetak
                        </button>
                    </div>
                </div>

                <!-- Conflict Warning -->
                <div v-if="conflicts.length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg print:hidden">
                    <div class="flex items-center gap-2 text-red-700 font-medium">
                        <ExclamationTriangleIcon class="w-5 h-5" />
                        {{ conflicts.length }} Jadwal Bentrok
                    </div>
                </div>

                <!-- Print Header -->
                <div class="hidden print:block text-center mb-4">
                    <h1 class="text-lg font-bold">DRAFT JADWAL PERKULIAHAN SMT. {{ kelas.semester?.nama || '-' }}</h1>
                    <h2 class="text-base font-semibold">{{ kelas.prodi?.nama?.toUpperCase() }}</h2>
                    <p class="text-sm">{{ kelas.semester?.tahunAkademik?.nama }}</p>
                </div>

                <!-- DEFAULT GROUP TABLES (chunked by 3 MK) -->
                <template v-for="(chunk, chunkIdx) in defaultChunks" :key="'default-' + chunkIdx">
                    <div class="bg-white rounded-lg shadow overflow-x-auto print:shadow-none print:rounded-none mb-6">
                        <table class="min-w-full border-collapse border border-gray-300 text-xs">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th rowspan="3" class="border border-gray-300 px-1 py-1 text-center w-8">NO</th>
                                    <th rowspan="3" class="border border-gray-300 px-1 py-1 text-center w-14">KODE MK</th>
                                    <th rowspan="3" class="border border-gray-300 px-1 py-1 text-left min-w-36">MATA KULIAH</th>
                                    <th rowspan="3" class="border border-gray-300 px-1 py-1 text-center w-8">SKS</th>
                                    <th rowspan="3" class="border border-gray-300 px-1 py-1 text-center w-16">WAKTU</th>
                                    <!-- Use filtered dates for this chunk -->
                                    <th :colspan="getChunkDates(chunk).length" class="border border-gray-300 px-1 py-0.5 text-center bg-gray-200 text-[10px]">
                                        HARI DAN TANGGAL PERKULIAHAN
                                    </th>
                                    <th rowspan="3" class="border border-gray-300 px-1 py-1 text-left min-w-24">DOSEN</th>
                                    <th rowspan="3" class="border border-gray-300 px-1 py-1 text-center w-12">JML MHS</th>
                                </tr>
                                <tr class="bg-gray-100">
                                    <th v-for="d in getChunkDates(chunk)" :key="d.date" class="border border-gray-300 px-0 py-0 text-center w-7 text-[8px]">
                                        <span v-if="d.hasData">{{ getDayName(d.date) }}</span>
                                        <span v-else>&nbsp;</span>
                                    </th>
                                </tr>
                                <tr class="bg-gray-100">
                                    <th v-for="d in getChunkDates(chunk)" :key="d.date" class="border border-gray-300 px-0 py-0 text-center text-[7px] w-7">
                                        <div v-if="d.hasData">
                                            <div>{{ formatDate(d.date).day }}</div>
                                            <div>{{ formatDate(d.date).month }}</div>
                                            <div>{{ formatDate(d.date).year }}</div>
                                        </div>
                                        <div v-else>&nbsp;</div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(mk, idx) in chunk" :key="mk.kode">
                                    <td class="border border-gray-300 px-1 py-0.5 text-center text-[10px]">{{ chunkIdx * 3 + idx + 1 }}</td>
                                    <td class="border border-gray-300 px-1 py-0.5 text-center font-mono text-[9px]">{{ mk.kode }}</td>
                                    <td class="border border-gray-300 px-1 py-0.5 text-[9px]">{{ mk.nama }}</td>
                                    <td class="border border-gray-300 px-1 py-0.5 text-center text-[10px]">{{ mk.sks }}</td>
                                    <td class="border border-gray-300 px-1 py-0.5 text-center text-[8px]">{{ mk.waktu }}</td>
                                    <td 
                                        v-for="d in getChunkDates(chunk)" :key="d.date"
                                        class="border border-gray-300 px-0 py-0 text-center w-7"
                                        :class="{
                                            'bg-gray-900 text-white': !getCellData(mk, d.date)?.custom_color && getCellData(mk, d.date)?.mode === 'offline' && !getCellData(mk, d.date)?.has_conflict,
                                            'bg-emerald-500 text-white': !getCellData(mk, d.date)?.custom_color && getCellData(mk, d.date)?.mode === 'online' && !getCellData(mk, d.date)?.has_conflict,
                                            'bg-red-600 text-white': getCellData(mk, d.date)?.has_conflict,
                                        }"
                                        :style="getCellData(mk, d.date)?.custom_color && !getCellData(mk, d.date)?.has_conflict ? { backgroundColor: getCellData(mk, d.date).custom_color, color: '#000' } : {}"
                                        :title="getCellData(mk, d.date) ? `P${getCellData(mk, d.date).pertemuan_ke} - ${getCellData(mk, d.date).dosen} (${getCellData(mk, d.date).tipe || 'KULIAH'})` : ''"
                                    >
                                        <template v-if="getCellData(mk, d.date)">
                                            <span v-if="getCellData(mk, d.date).tipe === 'UTS' || getCellData(mk, d.date).tipe === 'UAS'" class="text-[7px] font-bold text-orange-300">{{ getCellData(mk, d.date).tipe }}</span>
                                            <span v-else class="text-[8px] font-bold">{{ getCellData(mk, d.date).initials }}</span>
                                        </template>
                                    </td>
                                    <td class="border border-gray-300 px-1 py-0.5 text-[8px]">{{ mk.dosens }}</td>
                                    <td class="border border-gray-300 px-1 py-0.5 text-center text-[10px]">{{ kelas.mahasiswas_count || 16 }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <!-- CUSTOM GROUP TABLES (chunked by 3 MK) - tanggal mulai sendiri -->
                <template v-if="customGroup.mks.length > 0">
                    <template v-for="(chunk, chunkIdx) in customChunks" :key="'custom-' + chunkIdx">
                        <div class="bg-white rounded-lg shadow overflow-x-auto print:shadow-none print:rounded-none mb-6">
                            <table class="min-w-full border-collapse border border-gray-300 text-xs">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th rowspan="3" class="border border-gray-300 px-1 py-1 text-center w-8">NO</th>
                                        <th rowspan="3" class="border border-gray-300 px-1 py-1 text-center w-14">KODE MK</th>
                                        <th rowspan="3" class="border border-gray-300 px-1 py-1 text-left min-w-36">MATA KULIAH</th>
                                        <th rowspan="3" class="border border-gray-300 px-1 py-1 text-center w-8">SKS</th>
                                        <th rowspan="3" class="border border-gray-300 px-1 py-1 text-center w-16">WAKTU</th>
                                        <!-- Use filtered dates for this chunk -->
                                        <th :colspan="getChunkDates(chunk).length" class="border border-gray-300 px-1 py-0.5 text-center bg-gray-200 text-[10px]">
                                            HARI DAN TANGGAL PERKULIAHAN
                                        </th>
                                        <th rowspan="3" class="border border-gray-300 px-1 py-1 text-left min-w-24">DOSEN</th>
                                        <th rowspan="3" class="border border-gray-300 px-1 py-1 text-center w-12">JML MHS</th>
                                    </tr>
                                    <tr class="bg-gray-100">
                                        <th v-for="d in getChunkDates(chunk)" :key="d.date" class="border border-gray-300 px-0 py-0 text-center w-7 text-[8px]">
                                            <span v-if="d.hasData">{{ getDayName(d.date) }}</span>
                                            <span v-else>&nbsp;</span>
                                        </th>
                                    </tr>
                                    <tr class="bg-gray-100">
                                        <th v-for="d in getChunkDates(chunk)" :key="d.date" class="border border-gray-300 px-0 py-0 text-center text-[7px] w-7">
                                            <div v-if="d.hasData">
                                                <div>{{ formatDate(d.date).day }}</div>
                                                <div>{{ formatDate(d.date).month }}</div>
                                                <div>{{ formatDate(d.date).year }}</div>
                                            </div>
                                            <div v-else>&nbsp;</div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(mk, idx) in chunk" :key="mk.kode">
                                        <td class="border border-gray-300 px-1 py-0.5 text-center text-[10px]">{{ defaultGroup.mks.length + chunkIdx * 3 + idx + 1 }}</td>
                                        <td class="border border-gray-300 px-1 py-0.5 text-center font-mono text-[9px]">{{ mk.kode }}</td>
                                        <td class="border border-gray-300 px-1 py-0.5 text-[9px]">{{ mk.nama }}</td>
                                        <td class="border border-gray-300 px-1 py-0.5 text-center text-[10px]">{{ mk.sks }}</td>
                                        <td class="border border-gray-300 px-1 py-0.5 text-center text-[8px]">{{ mk.waktu }}</td>
                                        <td 
                                            v-for="d in getChunkDates(chunk)" :key="d.date"
                                            class="border border-gray-300 px-0 py-0 text-center w-7"
                                            :class="{
                                                'bg-gray-900 text-white': getCellData(mk, d.date)?.mode === 'offline' && !getCellData(mk, d.date)?.has_conflict,
                                                'bg-emerald-500 text-white': getCellData(mk, d.date)?.mode === 'online' && !getCellData(mk, d.date)?.has_conflict,
                                                'bg-red-600 text-white': getCellData(mk, d.date)?.has_conflict,
                                            }"
                                            :title="getCellData(mk, d.date) ? `P${getCellData(mk, d.date).pertemuan_ke} - ${getCellData(mk, d.date).dosen} (${getCellData(mk, d.date).tipe || 'KULIAH'})` : ''"
                                        >
                                            <template v-if="getCellData(mk, d.date)">
                                                <span v-if="getCellData(mk, d.date).tipe === 'UTS' || getCellData(mk, d.date).tipe === 'UAS'" class="text-[7px] font-bold text-orange-300">{{ getCellData(mk, d.date).tipe }}</span>
                                                <span v-else class="text-[8px] font-bold">{{ getCellData(mk, d.date).initials }}</span>
                                            </template>
                                        </td>
                                        <td class="border border-gray-300 px-1 py-0.5 text-[8px]">{{ mk.dosens }}</td>
                                        <td class="border border-gray-300 px-1 py-0.5 text-center text-[10px]">{{ kelas.mahasiswas_count || 16 }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </template>

                <!-- Legend -->
                <div class="mt-4 flex flex-wrap gap-4 text-sm print:mt-2">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-3 bg-gray-900 rounded"></div>
                        <span>Offline</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-3 bg-emerald-500 rounded"></div>
                        <span>Online</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-3 bg-red-600 rounded"></div>
                        <span>Bentrok</span>
                    </div>
                </div>

                <!-- Settings Modal (Visual Drag & Drop) -->
                <div v-if="showSettings" class="fixed inset-0 z-[100] overflow-y-auto print:hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen px-4 py-6 text-center sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showSettings = false"></div>

                        <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-4xl sm:w-full mx-auto my-8 flex flex-col max-h-[90vh]">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 flex-shrink-0">
                                <h3 class="text-xl leading-6 font-bold text-gray-900" id="modal-title">Pengaturan Posisi Jadwal</h3>
                                <p class="text-sm text-gray-500 mt-1">Geser (Drag & Drop) kartu mata kuliah ke kolom yang diinginkan.</p>
                            </div>

                            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                                <div class="flex flex-col md:flex-row gap-6 h-full min-h-[400px]">
                                    
                                    <!-- Board 1: Table Default -->
                                    <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200">
                                        <div class="p-3 bg-gray-100 border-b border-gray-200 rounded-t-xl flex justify-between items-center">
                                            <h4 class="font-semibold text-gray-700">Tabel 1 (Utama)</h4>
                                            <span class="bg-gray-200 text-gray-600 text-xs py-1 px-2 rounded-full">{{ settingsGroup1.length }} Item</span>
                                        </div>
                                        <div 
                                            class="flex-1 p-3 space-y-3 overflow-y-auto min-h-[200px]"
                                            @dragover.prevent
                                            @drop="onDrop($event, 1)"
                                        >
                                            <div 
                                                v-for="mk in settingsGroup1" 
                                                :key="mk.id"
                                                draggable="true"
                                                @dragstart="startDrag($event, mk, 1)"
                                                class="bg-white border hover:border-indigo-400 p-3 rounded-lg shadow-sm cursor-move transition-all active:scale-95 group relative"
                                            >
                                                <div class="flex justify-between items-start">
                                                    <div class="flex-1">
                                                        <div class="text-xs font-mono text-gray-500 mb-0.5 flex items-center gap-2">
                                                            {{ mk.kode }} • {{ mk.sks }} SKS
                                                            <span v-if="mk.initials" class="bg-yellow-100 text-yellow-800 px-1 rounded text-[10px] font-bold">{{ mk.initials }}</span>
                                                        </div>
                                                        <div class="font-bold text-gray-800 text-sm leading-tight">{{ mk.nama }}</div>
                                                        <div class="text-xs text-indigo-600 mt-1 font-medium">{{ mk.waktu }}</div>
                                                    </div>
                                                    <div class="flex flex-col items-center gap-2">
                                                        <button @click.stop="toggleEdit(mk)" class="text-gray-400 hover:text-indigo-600 p-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div v-if="mk._isEditing" class="mt-3 pt-2 border-t border-gray-100 bg-gray-50 rounded p-2 text-xs space-y-2" @click.stop>
                                                    <div>
                                                        <label class="block text-gray-500 mb-1">Inisial Custom (Maks. 3 Huruf)</label>
                                                        <input type="text" v-model="mk.initials" maxlength="3" class="w-full border-gray-300 rounded text-xs px-2 py-1 focus:ring-indigo-500 uppercase" placeholder="Contoh: PAK" @blur="saveSettings">
                                                    </div>
                                                    <div>
                                                        <label class="block text-gray-500 mb-1">Warna Keterangan</label>
                                                        <div class="flex gap-2">
                                                            <input type="color" v-model="mk.color" class="h-6 w-8 p-0 border-0 rounded" @change="saveSettings">
                                                            <span class="text-xs text-gray-400 self-center">{{ mk.color || 'Default' }}</span>
                                                            <button v-if="mk.color" @click="mk.color = ''; saveSettings()" class="text-[10px] text-red-500 underline ml-auto">Reset</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- ... similar for group 2 ... -->
                                            
                                            <div v-if="settingsGroup1.length === 0" class="h-full flex items-center justify-center text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-lg">
                                                Kosong
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Board 2: Custom Group -->
                                    <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200">
                                        <div class="p-3 bg-indigo-50 border-b border-indigo-100 rounded-t-xl flex justify-between items-center">
                                            <h4 class="font-semibold text-indigo-900">Tabel 2 (Custom)</h4>
                                            <span class="bg-indigo-100 text-indigo-700 text-xs py-1 px-2 rounded-full">{{ settingsGroup2.length }} Item</span>
                                        </div>
                                        <div 
                                            class="flex-1 p-3 space-y-3 overflow-y-auto min-h-[200px] bg-indigo-50/30"
                                            @dragover.prevent
                                            @drop="onDrop($event, 2)"
                                        >
                                            <div 
                                                v-for="mk in settingsGroup2" 
                                                :key="mk.id"
                                                draggable="true"
                                                @dragstart="startDrag($event, mk, 2)"
                                                class="bg-white border border-indigo-100 hover:border-indigo-400 p-3 rounded-lg shadow-sm cursor-move transition-all active:scale-95 group"
                                            >
                                                <div class="flex justify-between items-start">
                                                    <div class="flex-1">
                                                        <div class="text-xs font-mono text-gray-500 mb-0.5 flex items-center gap-2">
                                                            {{ mk.kode }} • {{ mk.sks }} SKS
                                                            <span v-if="mk.initials" class="bg-yellow-100 text-yellow-800 px-1 rounded text-[10px] font-bold">{{ mk.initials }}</span>
                                                        </div>
                                                        <div class="font-bold text-gray-800 text-sm leading-tight">{{ mk.nama }}</div>
                                                        <div class="text-xs text-indigo-600 mt-1 font-medium">{{ mk.waktu }}</div>
                                                    </div>
                                                    <div class="flex flex-col items-center gap-2">
                                                        <button @click.stop="toggleEdit(mk)" class="text-gray-400 hover:text-indigo-600 p-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div v-if="mk._isEditing" class="mt-3 pt-2 border-t border-gray-100 bg-gray-50 rounded p-2 text-xs space-y-2" @click.stop>
                                                    <div>
                                                        <label class="block text-gray-500 mb-1">Inisial Custom (Maks. 3 Huruf)</label>
                                                        <input type="text" v-model="mk.initials" maxlength="3" class="w-full border-gray-300 rounded text-xs px-2 py-1 focus:ring-indigo-500 uppercase" placeholder="Contoh: PAK" @blur="saveSettings">
                                                    </div>
                                                    <div>
                                                        <label class="block text-gray-500 mb-1">Warna Keterangan</label>
                                                        <div class="flex gap-2">
                                                            <input type="color" v-model="mk.color" class="h-6 w-8 p-0 border-0 rounded" @change="saveSettings">
                                                            <span class="text-xs text-gray-400 self-center">{{ mk.color || 'Default' }}</span>
                                                            <button v-if="mk.color" @click="mk.color = ''; saveSettings()" class="text-[10px] text-red-500 underline ml-auto">Reset</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-if="settingsGroup2.length === 0" class="h-full flex items-center justify-center text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-lg">
                                                Tarik MK ke sini
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>

                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
                                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm" @click="saveSettings" :disabled="form.processing">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                                </button>
                                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" @click="showSettings = false">
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    @page { size: landscape; margin: 8mm; }
    table { font-size: 7px; }
}
</style>
