<script setup>
import AppLayout from '../../Components/Layout/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    PlusIcon, MagnifyingGlassIcon, AcademicCapIcon, 
    BookOpenIcon, UsersIcon, BuildingOfficeIcon, CalendarIcon,
    GlobeAltIcon, ComputerDesktopIcon, EyeIcon, PencilIcon, 
    TrashIcon, XMarkIcon, CheckCircleIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    kelasList: Object,
    filters: Object,
    semesters: Array,
    prodis: Array,
    activeSemester: Object,
});

// Filters
const search = ref(props.filters?.search || '');
const semesterId = ref(props.filters?.semester_id || '');
const prodiId = ref(props.filters?.prodi_id || '');
const status = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get(route('kelas.index'), {
        search: search.value || undefined,
        semester_id: semesterId.value || undefined,
        prodi_id: prodiId.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true });
};

// Status colors
const statusColor = (s) => ({
    'draft': 'bg-amber-100 text-amber-700 border-amber-200',
    'ready': 'bg-blue-100 text-blue-700 border-blue-200',
    'generated': 'bg-emerald-100 text-emerald-700 border-emerald-200',
}[s] || 'bg-gray-100 text-gray-700');

// Delete single
const deleteKelas = (kelas) => {
    if (confirm(`Hapus kelas "${kelas.nama}"?`)) {
        router.delete(route('kelas.destroy', kelas.id));
    }
};

// Bulk selection
const selectedKelas = ref([]);
const selectAll = ref(false);

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedKelas.value = props.kelasList.data.map(k => k.id);
    } else {
        selectedKelas.value = [];
    }
};

// Bulk delete
const bulkDelete = async () => {
    if (selectedKelas.value.length === 0) return;
    if (!confirm(`Hapus ${selectedKelas.value.length} kelas yang dipilih?`)) return;
    
    try {
        await axios.post(route('kelas.bulk-destroy'), { ids: selectedKelas.value });
        selectedKelas.value = [];
        selectAll.value = false;
        router.reload();
    } catch (e) {
        alert('Gagal menghapus beberapa kelas');
    }
};

// Update kelas status
const updateKelasStatus = (kelas, newStatus) => {
    router.put(route('kelas.update-status', kelas.id), {
        status: newStatus,
    }, {
        preserveScroll: true,
    });
};

// =============== CREATE MODAL ===============
const showModal = ref(false);
const form = useForm({
    nama: '',
    kode: '',
    prodi_id: '',
    semester_id: props.activeSemester?.id || '',
    persen_online: 30,
    platform_online: 'zoom',
    link_online: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
});

const openModal = () => {
    form.reset();
    form.semester_id = props.activeSemester?.id || '';
    form.persen_online = 30;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitForm = () => {
    form.post(route('kelas.store'), {
        preserveScroll: true,
        onSuccess: (page) => {
            closeModal();
            const kelasId = page.props.flash?.kelas_id;
            if (kelasId) {
                router.visit(route('kelas.show', kelasId));
            }
        },
    });
};

const persenOffline = computed(() => 100 - form.persen_online);
</script>

<template>
    <AppLayout title="Manajemen Kelas">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-black bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                        Manajemen Kelas
                    </h1>
                    <p class="text-gray-500 mt-1">Kelola kelas per semester dengan pengaturan jadwal otomatis</p>
                </div>
                <button @click="openModal" 
                    class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold 
                           hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-200 
                           flex items-center gap-2">
                    <PlusIcon class="w-5 h-5" />
                    Buat Kelas
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Filters Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
                    <div class="flex flex-wrap gap-3 items-center">
                        <div class="relative flex-1 min-w-[200px]">
                            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input v-model="search" @keyup.enter="applyFilters" type="text" 
                                placeholder="Cari kelas..." 
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <select v-model="semesterId" @change="applyFilters" 
                            class="px-4 py-2.5 bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-indigo-500">
                            <option value="">Semua Semester</option>
                            <option v-for="s in semesters" :key="s.id" :value="s.id">
                                {{ s.tahun_akademik?.nama }} - {{ s.nama }}
                            </option>
                        </select>
                        <select v-model="prodiId" @change="applyFilters" 
                            class="px-4 py-2.5 bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-indigo-500">
                            <option value="">Semua Prodi</option>
                            <option v-for="p in prodis" :key="p.id" :value="p.id">{{ p.nama }}</option>
                        </select>
                        <select v-model="status" @change="applyFilters" 
                            class="px-4 py-2.5 bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-indigo-500">
                            <option value="">Semua Status</option>
                            <option value="draft">Draft</option>
                            <option value="ready">Ready</option>
                            <option value="generated">Generated</option>
                        </select>
                    </div>
                </div>

                <!-- Bulk Action Bar -->
                <div v-if="selectedKelas.length > 0" 
                    class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" 
                            class="w-5 h-5 rounded text-red-600 focus:ring-red-500">
                        <span class="font-semibold text-red-700">{{ selectedKelas.length }} kelas dipilih</span>
                    </div>
                    <button @click="bulkDelete" 
                        class="px-4 py-2 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 flex items-center gap-2">
                        <TrashIcon class="w-4 h-4" /> Hapus Terpilih
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="kelasList.data.length === 0" 
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full flex items-center justify-center mb-4">
                        <AcademicCapIcon class="w-10 h-10 text-indigo-600" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Belum ada kelas</h3>
                    <p class="text-gray-500 mb-6">Mulai dengan membuat kelas baru untuk semester ini</p>
                    <button @click="openModal" 
                        class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold">
                        <PlusIcon class="w-5 h-5 inline mr-2" /> Buat Kelas Pertama
                    </button>
                </div>

                <!-- Kelas Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div v-for="kelas in kelasList.data" :key="kelas.id" 
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg hover:border-indigo-200 transition group">
                        
                        <!-- Card Header -->
                        <div class="p-5 border-b border-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" :value="kelas.id" v-model="selectedKelas"
                                        class="w-5 h-5 rounded text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                        @click.stop>
                                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                                        <AcademicCapIcon class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900 group-hover:text-indigo-600 transition">{{ kelas.nama }}</h3>
                                        <p class="text-sm text-gray-500">{{ kelas.kode || '-' }}</p>
                                    </div>
                                </div>
                                <span :class="['px-2.5 py-1 text-xs font-bold rounded-lg border', statusColor(kelas.status)]">
                                    {{ kelas.status?.toUpperCase() }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 space-y-4">
                            <!-- Prodi & Semester -->
                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                <span class="flex items-center gap-1.5">
                                    <BuildingOfficeIcon class="w-4 h-4 text-gray-400" />
                                    {{ kelas.prodi?.nama || '-' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 text-sm text-gray-600">
                                <CalendarIcon class="w-4 h-4 text-gray-400" />
                                {{ kelas.semester?.tahun_akademik?.nama }} - {{ kelas.semester?.nama }}
                            </div>

                            <!-- Stats -->
                            <div class="grid grid-cols-3 gap-3 pt-2">
                                <div class="text-center p-2 bg-blue-50 rounded-xl">
                                    <div class="flex items-center justify-center gap-1">
                                        <GlobeAltIcon class="w-4 h-4 text-blue-500" />
                                        <span class="font-bold text-blue-600">{{ kelas.persen_online }}%</span>
                                    </div>
                                    <div class="text-xs text-blue-600 mt-0.5">Online</div>
                                </div>
                                <div class="text-center p-2 bg-purple-50 rounded-xl">
                                    <div class="flex items-center justify-center gap-1">
                                        <BookOpenIcon class="w-4 h-4 text-purple-500" />
                                        <span class="font-bold text-purple-600">{{ kelas.mata_kuliahs_count || 0 }}</span>
                                    </div>
                                    <div class="text-xs text-purple-600 mt-0.5">MK</div>
                                </div>
                                <div class="text-center p-2 bg-amber-50 rounded-xl">
                                    <div class="flex items-center justify-center gap-1">
                                        <UsersIcon class="w-4 h-4 text-amber-500" />
                                        <span class="font-bold text-amber-600">{{ kelas.mahasiswas_count || 0 }}</span>
                                    </div>
                                    <div class="text-xs text-amber-600 mt-0.5">Mhs</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                            <Link :href="route('kelas.show', kelas.id)" 
                                class="flex items-center gap-1.5 text-indigo-600 hover:text-indigo-700 font-semibold text-sm">
                                <EyeIcon class="w-4 h-4" /> Detail
                            </Link>
                            <div class="flex items-center gap-2">
                                <!-- Status Dropdown -->
                                <select :value="kelas.status" 
                                    @change="updateKelasStatus(kelas, $event.target.value)"
                                    :class="['text-xs font-bold py-1 px-2 rounded-lg border cursor-pointer', statusColor(kelas.status)]">
                                    <option value="draft">DRAFT</option>
                                    <option value="ready">READY</option>
                                    <option value="generated">GENERATED</option>
                                </select>
                                <Link :href="route('kelas.edit', kelas.id)" 
                                    class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                                    <PencilIcon class="w-4 h-4" />
                                </Link>
                                <button @click="deleteKelas(kelas)" 
                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="kelasList.last_page > 1" class="mt-6 flex justify-center gap-1">
                    <Link v-for="link in kelasList.links" :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-4 py-2 rounded-xl text-sm font-semibold transition',
                            link.active ? 'bg-indigo-600 text-white' : 'bg-white border text-gray-700 hover:bg-gray-50',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label">
                    </Link>
                </div>
            </div>
        </div>

        <!-- =============== CREATE MODAL =============== -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" 
                    @click.self="closeModal">
                    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden">
                        
                        <!-- Modal Header -->
                        <div class="relative bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5 text-white">
                            <h2 class="text-xl font-bold">Buat Kelas Baru</h2>
                            <p class="text-white/80 text-sm mt-0.5">Isi informasi dasar kelas</p>
                            <button @click="closeModal" class="absolute right-4 top-4 p-2 hover:bg-white/20 rounded-lg">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="p-6 space-y-5 max-h-[60vh] overflow-y-auto">
                            <!-- Nama & Kode -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Kelas *</label>
                                    <input v-model="form.nama" type="text" 
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Kelas A">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kode</label>
                                    <input v-model="form.kode" type="text" 
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="KLS-A">
                                </div>
                            </div>

                            <!-- Prodi & Semester -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Program Studi *</label>
                                    <select v-model="form.prodi_id" 
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Pilih Prodi</option>
                                        <option v-for="p in prodis" :key="p.id" :value="p.id">{{ p.nama }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Semester *</label>
                                    <select v-model="form.semester_id" 
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Pilih Semester</option>
                                        <option v-for="s in semesters" :key="s.id" :value="s.id">
                                            {{ s.tahun_akademik?.nama }} - {{ s.nama }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Tanggal -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tanggal Mulai</label>
                                    <input v-model="form.tanggal_mulai" type="date" 
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tanggal Selesai</label>
                                    <input v-model="form.tanggal_selesai" type="date" 
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>

                            <!-- Online/Offline Slider -->
                            <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-2xl p-5">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Mode Perkuliahan</label>
                                <div class="flex items-center gap-4">
                                    <div class="text-center">
                                        <GlobeAltIcon class="w-6 h-6 text-blue-500 mx-auto" />
                                        <div class="text-lg font-bold text-blue-600">{{ form.persen_online }}%</div>
                                        <div class="text-xs text-blue-500">Online</div>
                                    </div>
                                    <input type="range" v-model.number="form.persen_online" min="0" max="100" step="10"
                                        class="flex-1 h-3 bg-gradient-to-r from-blue-200 to-green-200 rounded-full appearance-none cursor-pointer">
                                    <div class="text-center">
                                        <ComputerDesktopIcon class="w-6 h-6 text-green-500 mx-auto" />
                                        <div class="text-lg font-bold text-green-600">{{ persenOffline }}%</div>
                                        <div class="text-xs text-green-500">Offline</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3">
                            <button @click="closeModal" class="px-5 py-2.5 border border-gray-200 rounded-xl font-semibold hover:bg-gray-100">
                                Batal
                            </button>
                            <button @click="submitForm" :disabled="form.processing"
                                class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 flex items-center gap-2">
                                <CheckCircleIcon class="w-5 h-5" />
                                {{ form.processing ? 'Menyimpan...' : 'Simpan & Lanjut' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
