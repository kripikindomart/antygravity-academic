<template>
    <AppLayout>
        <Head :title="`Edit ${programStudi.nama}`" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Link :href="route('prodi.index')" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 mb-2">
                        <ArrowLeftIcon class="w-4 h-4" />
                        Kembali ke Daftar
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Program Studi</h1>
                    <p class="text-gray-500 mt-1">{{ programStudi.kode }} - {{ programStudi.nama }}</p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Tab Navigation -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="flex -mb-px overflow-x-auto">
                            <button type="button" v-for="tab in tabs" :key="tab.key" 
                                @click="activeTab = tab.key"
                                :class="['flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition',
                                    activeTab === tab.key 
                                        ? 'border-primary-500 text-primary-600' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']"
                            >
                                <component :is="tab.icon" class="w-5 h-5" />
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- Tab: Info Dasar -->
                        <div v-show="activeTab === 'basic'" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Kode <span class="text-red-500">*</span></label>
                                    <input v-model="form.kode" type="text" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" placeholder="MPI" />
                                    <p v-if="form.errors.kode" class="text-red-500 text-sm mt-1">{{ form.errors.kode }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Jenjang <span class="text-red-500">*</span></label>
                                    <select v-model="form.jenjang" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition">
                                        <option value="S1">S1</option>
                                        <option value="S2">S2</option>
                                        <option value="S3">S3</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                                    <select v-model="form.is_active" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition">
                                        <option :value="true">Aktif</option>
                                        <option :value="false">Non-Aktif</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Program Studi <span class="text-red-500">*</span></label>
                                <input v-model="form.nama" type="text" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" placeholder="Magister Pendidikan Islam" />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                                    <input v-model="form.email" type="email" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" placeholder="prodi@example.ac.id" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Telepon</label>
                                    <input v-model="form.telepon" type="text" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" placeholder="0251-xxxxx" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Website</label>
                                <input v-model="form.website" type="url" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" placeholder="https://..." />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Alamat</label>
                                <textarea v-model="form.alamat" rows="2" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" placeholder="Alamat lengkap..."></textarea>
                            </div>
                        </div>

                        <!-- Tab: Visi Misi -->
                        <div v-show="activeTab === 'visimisi'" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Visi</label>
                                <textarea v-model="form.visi" rows="4" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" placeholder="Visi Program Studi..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Misi</label>
                                <textarea v-model="form.misi" rows="6" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" placeholder="Misi Program Studi..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Tujuan</label>
                                <textarea v-model="form.tujuan" rows="4" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" placeholder="Tujuan Program Studi..."></textarea>
                            </div>
                        </div>

                        <!-- Tab: Akreditasi -->
                        <div v-show="activeTab === 'akreditasi'" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Peringkat Akreditasi</label>
                                    <select v-model="form.akreditasi" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition">
                                        <option value="">-- Pilih --</option>
                                        <option value="Unggul">Unggul</option>
                                        <option value="Baik Sekali">Baik Sekali</option>
                                        <option value="Baik">Baik</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">No. SK Akreditasi</label>
                                    <input v-model="form.no_sk_akreditasi" type="text" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" placeholder="123/SK/BAN-PT/2024" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tanggal Akreditasi</label>
                                    <input v-model="form.tanggal_akreditasi" type="date" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Masa Berlaku</label>
                                    <input v-model="form.masa_berlaku_akreditasi" type="date" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition" />
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Struktural -->
                        <div v-show="activeTab === 'struktural'" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Kaprodi -->
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <AcademicCapIcon class="w-4 h-4 inline text-emerald-500 mr-1" />
                                        Ketua Program Studi
                                    </label>
                                    <SearchableSelect 
                                        v-model="form.kaprodi_id"
                                        :options="dosens"
                                        :label-formatter="formatDosenName"
                                        placeholder="-- Pilih Kaprodi --"
                                        search-placeholder="Cari dosen..."
                                    />
                                    <label class="flex items-center gap-2 mt-3 cursor-pointer">
                                        <input type="checkbox" v-model="form.is_kaprodi_plt" 
                                            class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" />
                                        <span class="text-sm text-gray-600">
                                            Sebagai <strong class="text-amber-600">PLT</strong> (Pelaksana Tugas)
                                        </span>
                                    </label>
                                </div>

                                <!-- Sekprodi -->
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <UserIcon class="w-4 h-4 inline text-blue-500 mr-1" />
                                        Sekretaris Prodi
                                    </label>
                                    <SearchableSelect 
                                        v-model="form.sekretaris_id"
                                        :options="dosens"
                                        :label-formatter="formatDosenName"
                                        placeholder="-- Pilih Sekprodi --"
                                        search-placeholder="Cari dosen..."
                                    />
                                    <label class="flex items-center gap-2 mt-3 cursor-pointer">
                                        <input type="checkbox" v-model="form.is_sekretaris_plt" 
                                            class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" />
                                        <span class="text-sm text-gray-600">
                                            Sebagai <strong class="text-amber-600">PLT</strong> (Pelaksana Tugas)
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- GKM -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    <ShieldCheckIcon class="w-4 h-4 inline text-purple-500 mr-1" />
                                    Gugus Kendali Mutu (GKM)
                                </label>
                                <SearchableSelect 
                                    v-model="form.gkm_id"
                                    :options="dosens"
                                    :label-formatter="formatDosenName"
                                    placeholder="-- Pilih GKM --"
                                    search-placeholder="Cari dosen..."
                                />
                            </div>

                            <!-- Staf Prodi (Dynamic List) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    <UsersIcon class="w-4 h-4 inline text-gray-500 mr-1" />
                                    Staf Program Studi
                                </label>
                                <div class="space-y-2">
                                    <div v-for="(staf, idx) in form.staf_prodi" :key="idx" class="flex items-center gap-2">
                                        <input v-model="form.staf_prodi[idx]" type="text" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition flex-1" placeholder="Nama Staf..." />
                                        <button type="button" @click="removeStaf(idx)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                            <XMarkIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                    <button type="button" @click="addStaf" class="inline-flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700">
                                        <PlusIcon class="w-4 h-4" />
                                        Tambah Staf
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('prodi.index')" class="px-6 py-2.5 text-gray-700 font-medium rounded-xl hover:bg-gray-100">
                        Batal
                    </Link>
                    <button type="submit" :disabled="form.processing" 
                        class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 hover:scale-105 transition-all disabled:opacity-50">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import { 
    ArrowLeftIcon, BuildingOfficeIcon, DocumentTextIcon, 
    AcademicCapIcon, ShieldCheckIcon, UserIcon, UsersIcon,
    PlusIcon, XMarkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    programStudi: Object,
    dosens: Array,
});

// Format dosen name with gelar and prodi info
const formatDosenName = (dosen) => {
    let name = '';
    if (dosen.gelar_depan) name += dosen.gelar_depan + ' ';
    name += dosen.nama;
    if (dosen.gelar_belakang) name += ', ' + dosen.gelar_belakang;
    // Add prodi info if available
    if (dosen.prodi?.kode) {
        name += ` [${dosen.prodi.kode}]`;
    }
    return name;
};

const tabs = [
    { key: 'basic', label: 'Info Dasar', icon: BuildingOfficeIcon },
    { key: 'visimisi', label: 'Visi & Misi', icon: DocumentTextIcon },
    { key: 'akreditasi', label: 'Akreditasi', icon: AcademicCapIcon },
    { key: 'struktural', label: 'Pejabat Struktural', icon: UsersIcon },
];

const activeTab = ref('basic');

// Helper to format date for input[type=date]
const formatDate = (date) => {
    if (!date) return '';
    // If it's already a string in YYYY-MM-DD format
    if (typeof date === 'string' && date.match(/^\d{4}-\d{2}-\d{2}/)) {
        return date.split('T')[0]; // Remove time part if exists
    }
    // If it's a Date object or ISO string
    const d = new Date(date);
    if (isNaN(d.getTime())) return '';
    return d.toISOString().split('T')[0];
};

const form = useForm({
    kode: props.programStudi.kode || '',
    nama: props.programStudi.nama || '',
    jenjang: props.programStudi.jenjang || 'S2',
    visi: props.programStudi.visi || '',
    misi: props.programStudi.misi || '',
    tujuan: props.programStudi.tujuan || '',
    akreditasi: props.programStudi.akreditasi || '',
    no_sk_akreditasi: props.programStudi.no_sk_akreditasi || '',
    tanggal_akreditasi: formatDate(props.programStudi.tanggal_akreditasi),
    masa_berlaku_akreditasi: formatDate(props.programStudi.masa_berlaku_akreditasi),
    email: props.programStudi.email || '',
    telepon: props.programStudi.telepon || '',
    alamat: props.programStudi.alamat || '',
    website: props.programStudi.website || '',
    kaprodi_id: props.programStudi.kaprodi_id || null,
    is_kaprodi_plt: props.programStudi.is_kaprodi_plt || false,
    sekretaris_id: props.programStudi.sekretaris_id || null,
    is_sekretaris_plt: props.programStudi.is_sekretaris_plt || false,
    gkm_id: props.programStudi.gkm_id || null,
    staf_prodi: props.programStudi.staf_prodi || [],
    is_active: props.programStudi.is_active ?? true,
});

const addStaf = () => {
    form.staf_prodi.push('');
};

const removeStaf = (idx) => {
    form.staf_prodi.splice(idx, 1);
};

const submitForm = () => {
    form.put(route('prodi.update', props.programStudi.id));
};
</script>
