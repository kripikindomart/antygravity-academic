<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Components/Layout/AppLayout.vue';
import { 
    ArrowLeftIcon, CalendarIcon, UserIcon, ClockIcon, 
    CheckCircleIcon, XCircleIcon, 
    BookOpenIcon, ClipboardDocumentListIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    pertemuan: Object,
    mahasiswas: Array,
    jurnal: Object,
});

// Format helpers
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', { 
        weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' 
    });
};

// Forms
const absensiForm = useForm({
    mahasiswas: props.mahasiswas.map(m => ({
        id: m.id,
        nama: m.nama,
        nim: m.nim,
        status: m.status || 'hadir',
        keterangan: m.keterangan || ''
    }))
});

const jurnalForm = useForm({
    materi: props.jurnal?.materi || '',
    aktivitas: props.jurnal?.aktivitas || '',
    catatan: props.jurnal?.catatan || '',
});

// Actions
const markAll = (status) => {
    absensiForm.mahasiswas.forEach(m => m.status = status);
};

const saveAbsensi = () => {
    absensiForm.post(route('pertemuan.absensi.store', props.pertemuan.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional toast handled by layout if flash message exists
        }
    });
};

const saveJurnal = () => {
    jurnalForm.post(route('pertemuan.jurnal.store', props.pertemuan.id), {
        preserveScroll: true,
        onSuccess: () => {
        }
    });
};

// Summary Logic used for UI feedback (not saved directly here, backend recalculates)
const stats = computed(() => {
    const s = { hadir: 0, izin: 0, sakit: 0, alpha: 0 };
    absensiForm.mahasiswas.forEach(m => {
        if (s[m.status] !== undefined) s[m.status]++;
    });
    return s;
});

const statusClass = (status) => {
    return {
        'hadir': 'bg-green-100 text-green-700 border-green-200',
        'izin': 'bg-blue-100 text-blue-700 border-blue-200',
        'sakit': 'bg-amber-100 text-amber-700 border-amber-200',
        'alpha': 'bg-red-100 text-red-700 border-red-200',
    }[status] || 'bg-gray-100';
}
</script>

<template>
    <AppLayout :title="`Pertemuan ${pertemuan.pertemuan_ke}: ${pertemuan.jadwal?.mata_kuliah?.nama || 'Detail Kelas'}`">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('kelas.show', { kelas: pertemuan.jadwal.kelas_id, tab: 'jadwal' })" 
                    class="p-2.5 rounded-xl bg-white border border-gray-200 hover:bg-gray-50 transition shadow-sm">
                    <ArrowLeftIcon class="w-5 h-5 text-gray-600" />
                </Link>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-black text-gray-900">Pertemuan Ke-{{ pertemuan.pertemuan_ke }}</h1>
                        <span :class="['px-3 py-1 text-xs font-bold rounded-lg', 
                            pertemuan.status === 'Selesai' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700']">
                            {{ pertemuan.status?.toUpperCase() }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 text-gray-500 mt-1 text-sm font-medium">
                        <span class="flex items-center gap-1"><CalendarIcon class="w-4 h-4"/> {{ formatDate(pertemuan.tanggal) }}</span>
                        <span class="flex items-center gap-1"><ClockIcon class="w-4 h-4"/> {{ pertemuan.jadwal?.jam_mulai }} - {{ pertemuan.jadwal?.jam_selesai }}</span>
                        <span class="flex items-center gap-1"><UserIcon class="w-4 h-4"/> {{ pertemuan.dosen?.nama_gelar || 'Belum ada dosen' }}</span>
                    </div>
                </div>
                <!-- Save Buttons (Sticky or Header) -->
                <div class="flex gap-2">
                    <button @click="saveJurnal" :disabled="jurnalForm.processing"
                        class="px-4 py-2 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-50 shadow-sm flex items-center gap-2">
                        <BookOpenIcon class="w-5 h-5" /> Simpan Jurnal
                    </button>
                    <button @click="saveAbsensi" :disabled="absensiForm.processing"
                        class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 flex items-center gap-2">
                        <CheckCircleIcon class="w-5 h-5" /> Simpan Absensi
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- LEFT COLUMN: JURNAL -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2 mb-4">
                            <BookOpenIcon class="w-5 h-5 text-indigo-600"/> Jurnal Perkuliahan
                        </h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Materi / Topik</label>
                                <textarea v-model="jurnalForm.materi" rows="3" 
                                    class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Apa yang dipelajari hari ini?"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Aktivitas Mahasiswa</label>
                                <textarea v-model="jurnalForm.aktivitas" rows="2" 
                                    class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Diskusi, Presentasi, dll."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan Tambahan</label>
                                <textarea v-model="jurnalForm.catatan" rows="2" 
                                    class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Catatan untuk evaluasi..."></textarea>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100">
                             <h4 class="text-sm font-bold text-gray-500 uppercase mb-2">Ringkasan Kehadiran</h4>
                             <div class="grid grid-cols-2 gap-2 text-sm">
                                 <div class="bg-green-50 p-2 rounded-lg text-green-700 flex justify-between">
                                     <span>Hadir</span> <span class="font-bold">{{ stats.hadir }}</span>
                                 </div>
                                 <div class="bg-blue-50 p-2 rounded-lg text-blue-700 flex justify-between">
                                     <span>Izin</span> <span class="font-bold">{{ stats.izin }}</span>
                                 </div>
                                 <div class="bg-amber-50 p-2 rounded-lg text-amber-700 flex justify-between">
                                     <span>Sakit</span> <span class="font-bold">{{ stats.sakit }}</span>
                                 </div>
                                 <div class="bg-red-50 p-2 rounded-lg text-red-700 flex justify-between">
                                     <span>Alpha</span> <span class="font-bold">{{ stats.alpha }}</span>
                                 </div>
                             </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: ABSENSI -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 flex flex-col h-full">
                        <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-gray-50 rounded-t-2xl">
                             <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <ClipboardDocumentListIcon class="w-5 h-5 text-indigo-600"/> Daftar Hadir Mahasiswa
                            </h3>
                            <div class="flex gap-1 text-sm">
                                <button @click="markAll('hadir')" class="px-3 py-1 bg-white border border-gray-200 hover:bg-green-50 text-green-700 rounded-lg">All Hadir</button>
                                <button @click="markAll('alpha')" class="px-3 py-1 bg-white border border-gray-200 hover:bg-red-50 text-red-700 rounded-lg">All Alpha</button>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase w-12">No</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Mahasiswa</th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status Kehadiran</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase w-1/4">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="(m, idx) in absensiForm.mahasiswas" :key="m.id" 
                                        :class="{'bg-red-50': m.status==='alpha'}">
                                        <td class="px-6 py-4 text-gray-500 font-mono">{{ idx + 1 }}</td>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900">{{ m.nama }}</div>
                                            <div class="text-xs text-gray-500">{{ m.nim }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex justify-center gap-1 bg-gray-100 p-1 rounded-xl w-fit mx-auto">
                                                <button type="button" @click="m.status = 'hadir'" 
                                                    :class="['px-3 py-1 rounded-lg text-xs font-bold transition', m.status === 'hadir' ? 'bg-white shadow text-green-600' : 'text-gray-400 hover:text-gray-600']">
                                                    H
                                                </button>
                                                <button type="button" @click="m.status = 'izin'" 
                                                    :class="['px-3 py-1 rounded-lg text-xs font-bold transition', m.status === 'izin' ? 'bg-white shadow text-blue-600' : 'text-gray-400 hover:text-gray-600']">
                                                    I
                                                </button>
                                                <button type="button" @click="m.status = 'sakit'" 
                                                    :class="['px-3 py-1 rounded-lg text-xs font-bold transition', m.status === 'sakit' ? 'bg-white shadow text-amber-600' : 'text-gray-400 hover:text-gray-600']">
                                                    S
                                                </button>
                                                <button type="button" @click="m.status = 'alpha'" 
                                                    :class="['px-3 py-1 rounded-lg text-xs font-bold transition', m.status === 'alpha' ? 'bg-white shadow text-red-600' : 'text-gray-400 hover:text-gray-600']">
                                                    A
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="text" v-model="m.keterangan" 
                                                class="w-full px-2 py-1 text-sm border-gray-200 rounded-lg focus:ring-1 focus:ring-indigo-500 placeholder-gray-300" 
                                                placeholder="Ket...">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
