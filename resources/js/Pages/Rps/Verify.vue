<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden">
            <!-- Header -->
            <div :class="[
                'px-8 py-6 text-white',
                found && rps?.is_approved 
                    ? 'bg-gradient-to-r from-emerald-500 to-emerald-600' 
                    : 'bg-gradient-to-r from-slate-600 to-slate-700'
            ]">
                <div class="flex items-center gap-4">
                    <div :class="[
                        'w-16 h-16 rounded-2xl flex items-center justify-center',
                        found && rps?.is_approved ? 'bg-white/20' : 'bg-white/10'
                    ]">
                        <CheckBadgeIcon v-if="found && rps?.is_approved" class="w-10 h-10" />
                        <XCircleIcon v-else class="w-10 h-10" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">
                            {{ found && rps?.is_approved ? 'Dokumen Terverifikasi' : 'Dokumen Tidak Valid' }}
                        </h1>
                        <p class="text-white/80 text-sm">Verifikasi RPS Digital</p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8">
                <!-- Verification Code -->
                <div class="mb-6 p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="text-sm text-slate-500 mb-1">Kode Verifikasi</p>
                    <p class="font-mono text-lg font-bold text-slate-800">{{ code }}</p>
                </div>

                <!-- Document Found -->
                <template v-if="found && rps">
                    <!-- Course Info -->
                    <div class="space-y-4 mb-6">
                        <div class="flex items-start gap-3">
                            <BookOpenIcon class="w-5 h-5 text-blue-500 mt-0.5" />
                            <div>
                                <p class="text-sm text-slate-500">Mata Kuliah</p>
                                <p class="font-semibold text-slate-800">{{ rps.mata_kuliah }}</p>
                                <p class="text-sm text-slate-600">{{ rps.kode_mk }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <BuildingOfficeIcon class="w-5 h-5 text-purple-500 mt-0.5" />
                            <div>
                                <p class="text-sm text-slate-500">Program Studi</p>
                                <p class="font-semibold text-slate-800">{{ rps.program_studi }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <CalendarIcon class="w-5 h-5 text-amber-500 mt-0.5" />
                            <div>
                                <p class="text-sm text-slate-500">Semester</p>
                                <p class="font-semibold text-slate-800">{{ rps.semester }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Info -->
                    <div class="border-t border-slate-200 pt-6">
                        <h3 class="text-sm font-semibold text-slate-600 uppercase tracking-wide mb-4">Pengesahan</h3>
                        
                        <div class="space-y-4">
                            <!-- Dosen Pengembang -->
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <UserIcon class="w-4 h-4 text-blue-600" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-slate-500">Dosen Pengembang</p>
                                    <p class="font-medium text-slate-800">{{ rps.dosen_pengembang }}</p>
                                </div>
                            </div>

                            <!-- GKM -->
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                                    <ShieldCheckIcon class="w-4 h-4 text-purple-600" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-slate-500">Koordinator RMK</p>
                                    <p class="font-medium text-slate-800">{{ rps.approved_by_gkm || '-' }}</p>
                                    <p v-if="rps.approved_by_gkm_at" class="text-xs text-slate-400">{{ rps.approved_by_gkm_at }}</p>
                                </div>
                                <CheckCircleIcon v-if="rps.approved_by_gkm" class="w-5 h-5 text-emerald-500" />
                            </div>

                            <!-- Kaprodi -->
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                    <AcademicCapIcon class="w-4 h-4 text-emerald-600" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-slate-500">Ketua Program Studi</p>
                                    <p class="font-medium text-slate-800">{{ rps.approved_by_kaprodi || '-' }}</p>
                                    <p v-if="rps.approved_by_kaprodi_at" class="text-xs text-slate-400">{{ rps.approved_by_kaprodi_at }}</p>
                                </div>
                                <CheckCircleIcon v-if="rps.approved_by_kaprodi" class="w-5 h-5 text-emerald-500" />
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Not Found -->
                <template v-else>
                    <div class="text-center py-8">
                        <XCircleIcon class="w-16 h-16 text-red-400 mx-auto mb-4" />
                        <p class="text-slate-600">{{ message }}</p>
                        <p class="text-sm text-slate-400 mt-2">Pastikan kode verifikasi yang Anda masukkan sudah benar.</p>
                    </div>
                </template>

                <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-slate-200 text-center">
                    <p class="text-xs text-slate-400">Dokumen ini diverifikasi secara digital oleh sistem</p>
                    <p class="text-xs text-slate-400 mt-1">Pascasarjana UIKA Bogor</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { 
    CheckBadgeIcon, XCircleIcon, BookOpenIcon, BuildingOfficeIcon, 
    CalendarIcon, UserIcon, ShieldCheckIcon, AcademicCapIcon, CheckCircleIcon 
} from '@heroicons/vue/24/outline';

defineProps({
    found: Boolean,
    code: String,
    rps: Object,
    message: String,
});
</script>
