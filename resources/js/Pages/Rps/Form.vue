<script setup>
import AppLayout from '../../Components/Layout/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue';
import { 
    ChevronUpIcon, PlusIcon, TrashIcon, BookOpenIcon, 
    QueueListIcon, SparklesIcon, Cog6ToothIcon,
    CheckCircleIcon, ExclamationCircleIcon, ArrowPathIcon, AcademicCapIcon,
    ClockIcon, CheckBadgeIcon, PencilSquareIcon, PlusCircleIcon, ArrowLeftIcon,
    DocumentArrowDownIcon, ChevronDownIcon, ArrowDownTrayIcon, EyeIcon, PaperAirplaneIcon, BookmarkSquareIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    rps: Object,
    mataKuliah: Object,
    availableCpmks: { type: Array, default: () => [] },
    availableSubCpmks: { type: Array, default: () => [] },
    can_approve: Boolean,
});

// Form State
const form = useForm({
    id: props.rps?.id,
    mata_kuliah_id: props.mataKuliah?.id,
    semester_id: props.rps?.semester_id || 1,
    deskripsi: props.rps?.deskripsi || '',
    bahan_kajian: props.rps?.bahan_kajian || '',
    pustaka_utama: props.rps?.pustaka_utama || '',
    pustaka_pendukung: props.rps?.pustaka_pendukung || '',
    details: props.rps?.details?.length ? props.rps.details : Array.from({ length: 16 }, (_, i) => ({
        pertemuan: i + 1,
        materi: '',
        metode: '',
        indikator: '',
        sub_cpmk_id: null,
        bobot_nilai: 0
    })),
});

// UI State
const activeTab = ref('magic'); // Start with Magic Generator
const localSubCpmks = ref([...(props.availableSubCpmks || [])]);

// DEBUG: Log props on mount
onMounted(() => {
    console.log('🔍 RPS Form Props Debug:');
    console.log('  props.rps:', props.rps);
    console.log('  props.mataKuliah:', props.mataKuliah);
    console.log('  props.availableCpmks:', props.availableCpmks);
    console.log('  props.availableSubCpmks:', props.availableSubCpmks);
    console.log('  availableCpmks length:', props.availableCpmks?.length || 0);
});

// AI Generator State
const aiTopics = ref('');
const aiModel = ref('gpt-3.5-turbo');
const isGenerating = ref(false);
const generationLogs = ref([]);
const generationSuccess = ref(false);
const showSettings = ref(false);
const showPdfMenu = ref(false);
const apiKey = ref('');

// Toast notification
const toast = ref({ show: false, message: '', type: 'success' });
const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => toast.value.show = false, 4000);
};

// Tab definitions
const tabs = [
    { id: 'magic', name: 'Magic AI Generator', icon: SparklesIcon, color: 'purple' },
    { id: 'info', name: 'Informasi Umum', icon: BookOpenIcon, color: 'blue' },
    { id: 'subcpmk', name: 'Sub-CPMK', icon: AcademicCapIcon, color: 'indigo' },
    { id: 'plan', name: '16 Pertemuan', icon: QueueListIcon, color: 'green' },
];

// Sub-CPMK Form State
const newSubCpmk = ref({ cpmk_id: '', kode: '', deskripsi: '' });
const isSavingSubCpmk = ref(false);

const saveSubCpmk = async () => {
    if (!newSubCpmk.value.deskripsi.trim()) {
        alert('Deskripsi Sub-CPMK harus diisi');
        return;
    }
    
    // Use selected CPMK or first available
    const cpmkId = newSubCpmk.value.cpmk_id || props.availableCpmks?.[0]?.id;
    if (!cpmkId) {
        alert('Tidak ada CPMK. Hubungi Kaprodi.');
        return;
    }
    
    isSavingSubCpmk.value = true;
    try {
        const res = await axios.post(route('sub-cpmk.store'), {
            cpmk_id: cpmkId,
            kode: newSubCpmk.value.kode || `SC-${String(localSubCpmks.value.length + 1).padStart(2, '0')}-${Date.now().toString(36)}`,
            deskripsi: newSubCpmk.value.deskripsi,
            urutan: localSubCpmks.value.length + 1,
        });
        localSubCpmks.value.push(res.data);
        newSubCpmk.value = { cpmk_id: cpmkId, kode: '', deskripsi: '' }; // Keep same CPMK selected
    } catch (e) {
        alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message));
    } finally {
        isSavingSubCpmk.value = false;
    }
};

// Computed
const mkInfo = computed(() => ({
    nama: props.mataKuliah?.nama || 'Mata Kuliah',
    kode: props.mataKuliah?.kode || '-',
    sks: (props.mataKuliah?.sks_teori || 0) + (props.mataKuliah?.sks_praktik || 0),
    prodi: props.mataKuliah?.prodi?.nama || 'Program Studi',
}));

const filledMeetings = computed(() => {
    return form.details.filter(d => d.materi && d.materi.trim() !== '').length;
});

// Methods
const addLog = (message, type = 'info') => {
    generationLogs.value.push({
        time: new Date().toLocaleTimeString('id-ID'),
        message,
        type // info, success, error, wait
    });
};

const resetLogs = () => {
    generationLogs.value = [];
    generationSuccess.value = false;
};

const saveSettings = async () => {
    try {
        await axios.post(route('ai.settings'), { openai_api_key: apiKey.value });
        showSettings.value = false;
        alert('✅ API Key berhasil disimpan!');
    } catch (e) {
        alert('❌ Gagal menyimpan API Key');
    }
};

const generateRps = async () => {
    if (!aiTopics.value.trim()) {
        alert('Masukkan topik/silabus terlebih dahulu.');
        return;
    }

    if (!props.mataKuliah?.id) {
        alert('Data Mata Kuliah tidak tersedia. Silakan refresh halaman.');
        return;
    }

    isGenerating.value = true;
    resetLogs();

    addLog('🚀 Memulai AI RPS Generator...', 'info');
    addLog(`📚 Mata Kuliah: ${mkInfo.value.nama}`, 'info');
    addLog(`🎓 Program Studi: ${mkInfo.value.prodi}`, 'info');
    addLog(`📊 SKS: ${mkInfo.value.sks}`, 'info');

    try {
        addLog('🔍 Menganalisis topik yang diinput...', 'info');
        addLog(`🤖 Mengirim ke ${aiModel.value}...`, 'wait');
        addLog('⏳ Mohon tunggu 30-90 detik...', 'wait');

        const response = await axios.post(route('ai.generate-complete'), {
            mata_kuliah_id: props.mataKuliah.id,
            topics: aiTopics.value,
            model: aiModel.value,
        });

        if (response.data.success) {
            addLog('✅ AI berhasil generate RPS!', 'success');
            addLog(`📝 Deskripsi & pustaka tersimpan`, 'success');
            addLog(`🎯 ${response.data.sub_cpmks_created} Sub-CPMK dibuat`, 'success');
            addLog(`📅 ${response.data.details_created} Pertemuan diisi`, 'success');
            addLog('💾 Semua data tersimpan ke database!', 'success');
            
            generationSuccess.value = true;
            
            // Show toast notification
            showToast(`🎉 RPS berhasil di-generate! ${response.data.sub_cpmks_created} Sub-CPMK, ${response.data.details_created} Pertemuan`, 'success');

            setTimeout(() => {
                addLog('🔄 Memuat ulang halaman...', 'info');
                if (response.data.redirect_url) {
                    window.location.href = response.data.redirect_url;
                } else {
                    window.location.reload();
                }
            }, 2500);
        }

    } catch (e) {
        let msg = e.response?.data?.message || e.message;
        
        // Handle Validation Errors
        if (e.response?.status === 422 && e.response?.data?.errors) {
            msg = 'Validasi Gagal:';
            Object.values(e.response.data.errors).flat().forEach(err => {
                msg += `\n- ${err}`;
                addLog(`⚠️ ${err}`, 'error');
            });
            showToast('Input tidak valid, cek log untuk detail.', 'error');
        } else {
            addLog(`❌ Error: ${msg}`, 'error');
            showToast(msg, 'error');
        }
        
        if (msg.includes('API Key')) {
            addLog('🔑 Silakan set API Key di Pengaturan', 'error');
        }
        isGenerating.value = false;
    }
};

const submit = () => {
    if (form.id) {
        form.put(route('rps.update', form.id), { preserveScroll: true });
    } else {
        form.post(route('rps.store'));
    }
};

// Submit RPS for approval (change status to submitted)
const submitForApproval = async () => {
    if (!confirm('Ajukan RPS ini untuk direview? Status akan berubah menjadi "Submitted".')) {
        return;
    }
    try {
        await axios.post(route('rps.submit', form.id));
        showToast('RPS berhasil diajukan untuk review!', 'success');
        window.location.reload();
    } catch (e) {
        showToast('Gagal mengajukan: ' + (e.response?.data?.message || e.message), 'error');
    }
};

// Approve RPS (Kaprodi/Akademik)
const approveRps = async () => {
    if (!confirm('Setujui RPS ini? Status akan berubah menjadi "Approved".')) {
        return;
    }
    try {
        await axios.post(route('rps.approve', form.id));
        showToast('RPS berhasil DISETUJUI! Dokumen kini final.', 'success');
        window.location.reload();
    } catch (e) {
        showToast('Gagal menyetujui: ' + (e.response?.data?.message || e.message), 'error');
    }
};

// Reject RPS (Return to Draft)
const rejectRps = async () => {
    if (!confirm('Tolak/Kembalikan RPS ini ke Draft untuk revisi?')) {
        return;
    }
    try {
        await axios.post(route('rps.reject', form.id));
        showToast('RPS dikembalikan ke status DRAFT.', 'warning');
        window.location.reload();
    } catch (e) {
        showToast('Gagal menolak: ' + (e.response?.data?.message || e.message), 'error');
    }
};

const getSubCpmkLabel = (id) => {
    const sc = localSubCpmks.value.find(s => s.id === id);
    return sc ? sc.kode : '-';
};

const copyFromPrevious = (index) => {
    if (index > 0) {
        const prev = form.details[index - 1];
        if (prev.sub_cpmk_id) {
            form.details[index].sub_cpmk_id = prev.sub_cpmk_id;
        }
    }
};

// Delete RPS - Direct delete using Inertia
const deleteRps = () => {
    if (!form.id) return;
    
    // Use Inertia's built-in delete with onBefore for confirmation
    router.delete(`/rps/${form.id}`, {
        onBefore: () => {
            // This returns true to proceed, false to cancel
            return true; // Direct delete, no confirmation popup
        },
        onSuccess: () => {
            // Redirect handled by backend
        },
        onError: (errors) => {
            console.error('Delete error:', errors);
            showToast('Gagal menghapus RPS', 'error');
        }
    });
};
</script>

<template>
    <AppLayout :title="`RPS - ${mkInfo.nama}`">
        <template #header>
            <!-- Stunning Header with Gradient Background -->
            <div class="relative rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 p-6 shadow-2xl">
                <!-- Animated Background Pattern (Contained within absolute div with overflow hidden) -->
                <div class="absolute inset-0 overflow-hidden rounded-2xl pointer-events-none">
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 left-0 w-40 h-40 bg-white rounded-full blur-3xl animate-pulse"></div>
                        <div class="absolute bottom-0 right-0 w-60 h-60 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
                    </div>
                </div>
                
                <div class="relative z-10">
                    <!-- Top Row: Title & Status -->
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                        <div class="flex-1">
                            <h1 class="text-2xl lg:text-3xl font-black text-white drop-shadow-lg">
                                {{ mkInfo.nama }}
                            </h1>
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                <span class="px-3 py-1.5 bg-white/20 backdrop-blur-sm text-white rounded-lg text-sm font-bold border border-white/30 flex items-center gap-1.5">
                                    <BookOpenIcon class="w-4 h-4" />
                                    {{ mkInfo.kode }}
                                </span>
                                <span class="px-3 py-1.5 bg-white/20 backdrop-blur-sm text-white rounded-lg text-sm font-bold border border-white/30 flex items-center gap-1.5">
                                    <ClockIcon class="w-4 h-4" />
                                    {{ mkInfo.sks }} SKS
                                </span>
                                <span class="px-3 py-1.5 bg-white/20 backdrop-blur-sm text-white rounded-lg text-sm border border-white/30 flex items-center gap-1.5">
                                    <AcademicCapIcon class="w-4 h-4" />
                                    {{ mkInfo.prodi }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Status Badge (Large) -->
                        <div v-if="form.id" class="flex-shrink-0">
                            <div class="px-4 py-2 rounded-xl font-black text-sm uppercase tracking-wider shadow-lg flex items-center gap-2"
                                :class="{
                                    'bg-yellow-400 text-yellow-900': !props.rps?.status || props.rps?.status === 'draft',
                                    'bg-blue-400 text-blue-900': props.rps?.status === 'submitted',
                                    'bg-green-400 text-green-900': props.rps?.status === 'approved',
                                }">
                                <template v-if="props.rps?.status === 'approved'">
                                    <CheckBadgeIcon class="w-5 h-5" />
                                    DISETUJUI
                                </template>
                                <template v-else-if="props.rps?.status === 'submitted'">
                                    <ClockIcon class="w-5 h-5" />
                                    DALAM REVIEW
                                </template>
                                <template v-else>
                                    <PencilSquareIcon class="w-5 h-5" />
                                    DRAFT
                                </template>
                            </div>
                        </div>
                        <div v-else class="flex-shrink-0">
                            <div class="px-4 py-2 rounded-xl font-black text-sm uppercase tracking-wider shadow-lg bg-gray-800 text-gray-200 flex items-center gap-2">
                                <PlusCircleIcon class="w-5 h-5" />
                                RPS BARU
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons Row -->
                    <div class="flex flex-wrap items-center gap-3 mt-6 pt-4 border-t border-white/20">
                        <!-- Back Button -->
                        <Link :href="route('rps.index')" 
                            class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white rounded-xl font-bold transition-all border border-white/30 flex items-center gap-2">
                            <ArrowLeftIcon class="w-5 h-5" />
                            Kembali
                        </Link>
                        
                        <!-- PDF Export (Always show for saved RPS) -->
                        <div v-if="form.id" class="relative group">
                            <button @click="showPdfMenu = !showPdfMenu" type="button"
                                class="px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl font-bold transition-all shadow-lg flex items-center gap-2">
                                <DocumentArrowDownIcon class="w-5 h-5" />
                                Export
                                <ChevronDownIcon class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': showPdfMenu }" />
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div v-if="showPdfMenu" 
                                class="absolute left-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 z-50 origin-top-left transform transition-all duration-200 ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <div class="px-4 py-2 border-b border-gray-50 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    Format Dokumen
                                </div>
                                <a :href="route('rps.pdf', form.id)" target="_blank"
                                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-red-50 text-gray-700 transition-colors">
                                    <div class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
                                        <ArrowDownTrayIcon class="w-5 h-5" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-sm">Download PDF</span>
                                        <span class="text-xs text-gray-500">Cetak layout landscape</span>
                                    </div>
                                </a>
                                <a :href="route('rps.pdf.preview', form.id) + '?html=true'" target="_blank"
                                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-indigo-50 text-gray-700 transition-colors">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                        <EyeIcon class="w-5 h-5" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-sm">Preview HTML</span>
                                        <span class="text-xs text-gray-500">Cek tampilan di browser</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Submit for Review (Draft only) -->
                        <button v-if="form.id && (!props.rps?.status || props.rps?.status === 'draft')" 
                            @click="submitForApproval" type="button"
                            class="px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-bold transition-all shadow-lg flex items-center gap-2">
                            <PaperAirplaneIcon class="w-5 h-5" />
                            Ajukan
                        </button>
                        
                        <!-- APPROVAL ACTION (Kaprodi/Admin) -->
                        <div v-if="(props.can_approve && props.rps?.status === 'submitted') || (props.can_approve && $page.props.auth.user.roles.includes('akademik'))" class="flex items-center gap-2">
                            <button v-if="props.rps?.status !== 'approved'" @click="approveRps" type="button"
                                class="px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-xl font-bold transition-all shadow-lg flex items-center gap-2">
                                <CheckBadgeIcon class="w-5 h-5" />
                                {{ props.rps?.status === 'draft' ? 'Direct Approve' : 'Setujui' }}
                            </button>
                            
                            <button v-if="props.rps?.status !== 'draft'" @click="rejectRps" type="button"
                                class="px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl font-bold transition-all shadow-lg flex items-center gap-2">
                                <ExclamationCircleIcon class="w-5 h-5" />
                                Revisi
                            </button>
                        </div>

                        <div class="flex-1"></div>

                        <!-- Delete Button (High Contrast Version) -->
                        <button v-if="form.id" @click="deleteRps" type="button"
                             class="px-4 py-2.5 bg-white text-red-600 hover:bg-red-50 border-2 border-red-200 rounded-xl font-bold transition-all shadow-lg flex items-center gap-2 mr-2">
                             <TrashIcon class="w-5 h-5" />
                             Hapus RPS
                        </button>
                        
                        <!-- Save Button (Primary) -->
                        <button @click="submit" :disabled="form.processing" type="button"
                            class="px-6 py-2.5 bg-white text-indigo-700 hover:bg-gray-100 rounded-xl font-black transition-all shadow-lg disabled:opacity-50 flex items-center gap-2">
                            <template v-if="!form.processing">
                                <BookmarkSquareIcon class="w-5 h-5" />
                                Simpan RPS
                            </template>
                            <template v-else>
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Menyimpan...
                            </template>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Tab Navigation -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm mb-6 p-2">
                    <div class="flex flex-wrap gap-2">
                        <button v-for="tab in tabs" :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'flex items-center gap-2 px-6 py-3 rounded-xl font-bold transition-all',
                                activeTab === tab.id 
                                    ? `bg-gradient-to-r from-${tab.color}-500 to-${tab.color}-600 text-white shadow-lg` 
                                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
                            ]"
                        >
                            <component :is="tab.icon" class="w-5 h-5" />
                            {{ tab.name }}
                            <span v-if="tab.id === 'plan'" class="ml-1 px-2 py-0.5 bg-white/20 rounded-full text-xs">
                                {{ filledMeetings }}/16
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden">
                    
                    <!-- MAGIC AI GENERATOR TAB -->
                    <div v-show="activeTab === 'magic'" class="p-8">
                        <div class="max-w-3xl mx-auto">
                            <!-- Header -->
                            <div class="text-center mb-8">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl mb-4">
                                    <SparklesIcon class="w-8 h-8 text-white" />
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Magic AI Generator</h3>
                                <p class="text-gray-500 mt-2">Buat RPS lengkap dalam hitungan detik dengan AI</p>
                            </div>

                            <!-- Not Generating View -->
                            <div v-if="!isGenerating && !generationSuccess" class="space-y-6">
                                <!-- Model Selection -->
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="relative cursor-pointer">
                                        <input type="radio" v-model="aiModel" value="gpt-3.5-turbo" class="peer sr-only">
                                        <div class="p-4 rounded-xl border-2 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 hover:bg-gray-50 transition">
                                            <div class="font-bold text-gray-900">⚡ GPT-3.5 Turbo</div>
                                            <div class="text-sm text-gray-500">Cepat & Hemat (~30 detik)</div>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" v-model="aiModel" value="gpt-4o" class="peer sr-only">
                                        <div class="p-4 rounded-xl border-2 peer-checked:border-purple-500 peer-checked:bg-purple-50 hover:bg-gray-50 transition">
                                            <div class="font-bold text-gray-900">🧠 GPT-4o</div>
                                            <div class="text-sm text-gray-500">Lebih Cerdas (~60 detik)</div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Topics Input -->
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">📝 Daftar Topik / Silabus</label>
                                    <textarea v-model="aiTopics" rows="10"
                                        class="w-full border-2 border-gray-200 rounded-xl p-4 focus:border-purple-500 focus:ring-purple-500 font-mono text-sm"
                                        placeholder="Masukkan topik atau silabus mata kuliah:

1. Pengantar dan Konsep Dasar
2. Teori Fundamental
3. Metode dan Teknik
4. Implementasi Praktis
5. Studi Kasus
6. dst...

Semakin detail, semakin akurat hasil AI!"
                                    ></textarea>
                                </div>

                                <!-- Warning if no CPMK -->
                                <div v-if="!props.availableCpmks?.length" class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                                    <div class="flex gap-3">
                                        <ExclamationCircleIcon class="w-6 h-6 text-amber-500 shrink-0" />
                                        <div>
                                            <div class="font-bold text-amber-800">Perhatian</div>
                                            <div class="text-sm text-amber-600">Belum ada CPMK untuk MK ini. Sub-CPMK tidak akan dibuat. Hubungi Kaprodi.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Generate Button -->
                                <button @click="generateRps" :disabled="!aiTopics.trim()"
                                    class="w-full py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-red-500 text-white rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl hover:scale-[1.02] transition disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-3">
                                    <SparklesIcon class="w-6 h-6" />
                                    Generate RPS Lengkap dengan AI
                                </button>

                                <!-- Settings Link -->
                                <div class="text-center">
                                    <button @click="showSettings = true" class="text-sm text-gray-500 hover:text-purple-600 flex items-center gap-1 mx-auto">
                                        <Cog6ToothIcon class="w-4 h-4" />
                                        Pengaturan API Key
                                    </button>
                                </div>
                            </div>

                            <!-- Generating View -->
                            <div v-else class="space-y-6">
                                <!-- Progress Header -->
                                <div class="text-center py-4">
                                    <div v-if="!generationSuccess" class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full animate-pulse">
                                        <ArrowPathIcon class="w-10 h-10 text-white animate-spin" />
                                    </div>
                                    <div v-else class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full">
                                        <CheckCircleIcon class="w-10 h-10 text-white" />
                                    </div>
                                    <h4 class="font-bold text-xl mt-4" :class="generationSuccess ? 'text-green-600' : 'text-gray-900'">
                                        {{ generationSuccess ? '🎉 Berhasil!' : 'AI Sedang Bekerja...' }}
                                    </h4>
                                </div>

                                <!-- Log Panel -->
                                <div class="bg-gray-900 rounded-xl p-4 h-64 overflow-y-auto font-mono text-sm">
                                    <div v-for="(log, i) in generationLogs" :key="i" class="flex gap-3 py-1 border-b border-gray-800 last:border-0">
                                        <span class="text-gray-500 w-20 shrink-0">{{ log.time }}</span>
                                        <span :class="{
                                            'text-green-400': log.type === 'success',
                                            'text-red-400': log.type === 'error',
                                            'text-yellow-400': log.type === 'wait',
                                            'text-gray-300': log.type === 'info'
                                        }">{{ log.message }}</span>
                                    </div>
                                </div>

                                <!-- What's Being Generated -->
                                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4">
                                    <div class="font-bold text-purple-800 dark:text-purple-300 mb-2">Yang sedang di-generate:</div>
                                    <ul class="space-y-1 text-sm text-purple-600 dark:text-purple-400">
                                        <li>✅ Deskripsi Mata Kuliah</li>
                                        <li>✅ Bahan Kajian</li>
                                        <li>✅ Pustaka Utama & Pendukung</li>
                                        <li>✅ 6-10 Sub-CPMK</li>
                                        <li>✅ 16 Pertemuan Lengkap</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- INFO TAB -->
                    <div v-show="activeTab === 'info'" class="p-8">
                        <div class="max-w-3xl mx-auto space-y-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi Mata Kuliah</label>
                                <textarea v-model="form.deskripsi" rows="4" class="w-full border border-gray-300 rounded-xl p-4 bg-gray-50 focus:bg-white focus:border-indigo-500" placeholder="Deskripsi mata kuliah..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Bahan Kajian</label>
                                <textarea v-model="form.bahan_kajian" rows="3" class="w-full border border-gray-300 rounded-xl p-4 bg-gray-50 focus:bg-white focus:border-indigo-500" placeholder="Bahan kajian..."></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Pustaka Utama</label>
                                    <textarea v-model="form.pustaka_utama" rows="4" class="w-full border border-gray-300 rounded-xl p-4 bg-gray-50 focus:bg-white focus:border-indigo-500" placeholder="1. Buku A&#10;2. Buku B"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Pustaka Pendukung</label>
                                    <textarea v-model="form.pustaka_pendukung" rows="4" class="w-full border border-gray-300 rounded-xl p-4 bg-gray-50 focus:bg-white focus:border-indigo-500" placeholder="1. Jurnal/Artikel&#10;2. Website"></textarea>
                                </div>
                            </div>
                            
                            <!-- Backup Delete Button -->
                            <div v-if="form.id" class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
                                <button @click="deleteRps" type="button" class="px-4 py-2 text-red-500 hover:text-red-700 font-bold text-sm flex items-center gap-2">
                                    <TrashIcon class="w-4 h-4" />
                                    Hapus RPS ini Permanen
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-CPMK TAB -->
                    <div v-show="activeTab === 'subcpmk'" class="p-8">
                        <div class="max-w-4xl mx-auto">
                            <!-- Header -->
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Kelola Sub-CPMK</h3>
                                    <p class="text-gray-500 text-sm">Buat Sub-CPMK terlebih dahulu, lalu assign ke pertemuan</p>
                                </div>
                                <div class="flex gap-2">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-bold">
                                        {{ props.availableCpmks?.length || 0 }} CPMK
                                    </span>
                                    <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-bold">
                                        {{ localSubCpmks.length }} Sub-CPMK
                                    </span>
                                </div>
                            </div>

                            <!-- Available CPMKs Info -->
                            <div v-if="props.availableCpmks?.length" class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                                <div class="font-bold text-blue-900 mb-2">📚 CPMK tersedia untuk Mata Kuliah ini:</div>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="c in props.availableCpmks" :key="c.id" 
                                        class="px-3 py-1 bg-white border border-blue-300 rounded-lg text-sm">
                                        <strong>{{ c.kode }}</strong> - {{ c.sub_cpmks?.length || 0 }} Sub
                                    </span>
                                </div>
                            </div>

                            <!-- Warning if no CPMK -->
                            <div v-else class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                                <div class="flex gap-3">
                                    <ExclamationCircleIcon class="w-6 h-6 text-amber-500 shrink-0" />
                                    <div>
                                        <div class="font-bold text-amber-800">Belum ada CPMK</div>
                                        <div class="text-sm text-amber-600">Hubungi Kaprodi untuk menambahkan CPMK pada Mata Kuliah ini.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add New Sub-CPMK Form -->
                            <div v-if="props.availableCpmks?.length" class="bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-xl p-6 mb-6">
                                <h4 class="font-bold text-indigo-900 mb-4">➕ Tambah Sub-CPMK Baru</h4>
                                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-1">CPMK INDUK *</label>
                                        <select v-model="newSubCpmk.cpmk_id" 
                                            class="w-full border border-gray-300 rounded-lg p-3 bg-white focus:border-indigo-500">
                                            <option value="">- Pilih CPMK -</option>
                                            <option v-for="c in props.availableCpmks" :key="c.id" :value="c.id">
                                                {{ c.kode }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-1">KODE</label>
                                        <input v-model="newSubCpmk.kode" type="text" placeholder="Auto-generate" 
                                            class="w-full border border-gray-300 rounded-lg p-3 bg-white focus:border-indigo-500">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-500 mb-1">DESKRIPSI *</label>
                                        <input v-model="newSubCpmk.deskripsi" type="text" placeholder="Mahasiswa mampu..." 
                                            class="w-full border border-gray-300 rounded-lg p-3 bg-white focus:border-indigo-500">
                                    </div>
                                    <div class="flex items-end">
                                        <button @click="saveSubCpmk" :disabled="isSavingSubCpmk || !newSubCpmk.deskripsi"
                                            class="w-full py-3 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 disabled:opacity-50 flex items-center justify-center gap-2">
                                            <PlusIcon class="w-5 h-5" />
                                            Tambah
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Sub-CPMK List -->
                            <div v-if="localSubCpmks.length > 0" class="space-y-3">
                                <div v-for="(sc, i) in localSubCpmks" :key="sc.id"
                                    class="flex items-center gap-4 p-4 bg-white border border-gray-200 rounded-xl hover:shadow-sm transition">
                                    <span class="w-10 h-10 flex items-center justify-center bg-indigo-100 text-indigo-700 rounded-full font-bold text-sm">
                                        {{ i + 1 }}
                                    </span>
                                    <div class="flex-1">
                                        <div class="font-bold text-gray-900">{{ sc.kode || 'No Kode' }}</div>
                                        <div class="text-sm text-gray-600">{{ sc.deskripsi || 'No Description' }}</div>
                                    </div>
                                    <span class="text-xs text-gray-400">CPMK ID: {{ sc.cpmk_id }}</span>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="text-center py-12 text-gray-500">
                                <AcademicCapIcon class="w-12 h-12 mx-auto mb-4 opacity-50" />
                                <p>Belum ada Sub-CPMK. Tambahkan di atas.</p>
                            </div>
                        </div>
                    </div>

                    <!-- PLAN TAB (16 Pertemuan) -->
                    <div v-show="activeTab === 'plan'" class="p-6">
                        <div class="space-y-4">
                            <Disclosure v-for="(detail, index) in form.details" :key="index" v-slot="{ open }">
                                <div class="border rounded-xl overflow-hidden" :class="detail.materi ? 'border-green-200 bg-green-50/50' : 'border-gray-200'">
                                    <DisclosureButton class="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                                        <div class="flex items-center gap-4">
                                            <span class="w-10 h-10 flex items-center justify-center rounded-full font-bold text-white"
                                                :class="{
                                                    'bg-orange-500': detail.pertemuan === 8,
                                                    'bg-red-500': detail.pertemuan === 16,
                                                    'bg-green-500': detail.materi,
                                                    'bg-gray-300': !detail.materi
                                                }">
                                                {{ detail.pertemuan }}
                                            </span>
                                            <div class="text-left">
                                                <div class="font-bold text-gray-900">
                                                    Pertemuan {{ detail.pertemuan }}
                                                    <span v-if="detail.pertemuan === 8" class="ml-2 text-orange-600">(UTS)</span>
                                                    <span v-if="detail.pertemuan === 16" class="ml-2 text-red-600">(UAS)</span>
                                                </div>
                                                <div class="text-sm text-gray-500 truncate max-w-md">
                                                    {{ detail.materi || 'Belum diisi' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span v-if="detail.sub_cpmk_id" class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold">
                                                {{ getSubCpmkLabel(detail.sub_cpmk_id) }}
                                            </span>
                                            <ChevronUpIcon :class="open ? 'rotate-180' : ''" class="w-5 h-5 text-gray-500 transition-transform" />
                                        </div>
                                    </DisclosureButton>
                                    <DisclosurePanel class="px-6 pb-6 pt-2 border-t bg-white">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="md:col-span-2">
                                                <label class="block text-xs font-bold text-gray-500 mb-1">MATERI</label>
                                                <textarea v-model="detail.materi" rows="2" class="w-full border border-gray-300 rounded-lg text-sm p-3 bg-gray-50 focus:bg-white focus:border-indigo-500"></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-500 mb-1">METODE</label>
                                                <input v-model="detail.metode" type="text" class="w-full border border-gray-300 rounded-lg text-sm p-3 bg-gray-50 focus:bg-white focus:border-indigo-500" placeholder="Ceramah, Diskusi...">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-500 mb-1">BOBOT NILAI (%)</label>
                                                <input v-model.number="detail.bobot_nilai" type="number" min="0" max="100" class="w-full border border-gray-300 rounded-lg text-sm p-3 bg-gray-50 focus:bg-white focus:border-indigo-500">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-xs font-bold text-gray-500 mb-1">INDIKATOR</label>
                                                <textarea v-model="detail.indikator" rows="2" class="w-full border border-gray-300 rounded-lg text-sm p-3 bg-gray-50 focus:bg-white focus:border-indigo-500"></textarea>
                                            </div>
                                            <div class="md:col-span-2">
                                                <div class="flex justify-between items-center mb-1">
                                                    <label class="text-xs font-bold text-gray-500">SUB-CPMK</label>
                                                    <button v-if="index > 0" @click="copyFromPrevious(index)" class="text-xs text-indigo-600 hover:underline">
                                                        ← Sama dengan sebelumnya
                                                    </button>
                                                </div>
                                                <select v-model="detail.sub_cpmk_id" class="w-full border border-gray-300 rounded-lg text-sm p-3 bg-gray-50 focus:bg-white focus:border-indigo-500">
                                                    <option :value="null">- Pilih Sub-CPMK -</option>
                                                    <option v-for="sc in localSubCpmks" :key="sc.id" :value="sc.id">
                                                        [{{ sc.kode }}] {{ sc.deskripsi }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </DisclosurePanel>
                                </div>
                            </Disclosure>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Settings Modal -->
        <Teleport to="body">
            <div v-if="showSettings" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="showSettings = false"></div>
                <div class="relative bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
                    <h3 class="font-bold text-lg mb-4">⚙️ Pengaturan AI</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">OpenAI API Key</label>
                            <input v-model="apiKey" type="password" placeholder="sk-..." class="w-full border-gray-300 rounded-lg px-4 py-2">
                            <p class="text-xs text-gray-500 mt-1">Key disimpan aman & terenkripsi</p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button @click="showSettings = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                            <button @click="saveSettings" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold">Simpan</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Toast Notification -->
        <Teleport to="body">
            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="toast.show" 
                    class="fixed bottom-6 right-6 z-[100] max-w-md w-full bg-white dark:bg-gray-800 shadow-2xl rounded-2xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden flex items-center p-4 gap-4 border-l-4"
                    :class="toast.type === 'error' ? 'border-red-500' : 'border-green-500'">
                    <div class="flex-shrink-0">
                        <svg v-if="toast.type === 'success'" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ toast.message }}</p>
                    </div>
                    <button @click="toast.show = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
/* Custom tab colors */
.from-purple-500 { --tw-gradient-from: #8b5cf6; }
.to-purple-600 { --tw-gradient-to: #7c3aed; }
.from-blue-500 { --tw-gradient-from: #3b82f6; }
.to-blue-600 { --tw-gradient-to: #2563eb; }
.from-green-500 { --tw-gradient-from: #22c55e; }
.to-green-600 { --tw-gradient-to: #16a34a; }
</style>
