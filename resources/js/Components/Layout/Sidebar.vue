<template>
    <aside
        :class="[
            'fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 transform transition-transform duration-300 ease-in-out lg:translate-x-0',
            isOpen ? 'translate-x-0' : '-translate-x-full'
        ]"
    >
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200 dark:border-gray-800">
                <Link href="/dashboard" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-900 dark:text-white">SIAKAD</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pascasarjana UIKA</p>
                    </div>
                </Link>
                
                <!-- Close Button (Mobile) -->
                <button
                    @click="$emit('close')"
                    class="lg:hidden p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <!-- Main Menu -->
                <div class="mb-6">
                    <p class="px-3 mb-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Menu Utama
                    </p>
                    <NavItem href="/dashboard" icon="home" label="Dashboard" />
                    <NavItem v-if="hasPermission('jadwal.view')" href="/jadwal" icon="calendar" label="Jadwal Kuliah" />
                    <NavItem v-if="hasPermission('kurikulum.view')" href="/kurikulum" icon="academic" label="Kurikulum OBE" />
                    <NavItem v-if="hasPermission('matakuliah.view')" href="/matakuliah" icon="book" label="Mata Kuliah" />
                </div>
                
                <!-- Akademik -->
                <div v-if="hasPermission('absensi.view') || hasPermission('jurnal.view') || hasPermission('nilai.view')" class="mb-6">
                    <p class="px-3 mb-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Akademik
                    </p>
                    <NavItem v-if="hasPermission('absensi.view')" href="/absensi" icon="clipboard-check" label="Absensi" />
                    <NavItem v-if="hasPermission('jurnal.view')" href="/jurnal" icon="document-text" label="Jurnal Perkuliahan" />
                    <NavItem v-if="hasPermission('nilai.view')" href="/nilai" icon="chart-bar" label="Nilai" />
                    <NavItem v-if="hasPermission('survei.view') || hasPermission('survei.respond')" href="/survei" icon="star" label="Survei Evaluasi" />
                </div>
                
                <!-- Master Data -->
                <div v-if="hasPermission('prodi.view') || hasPermission('ruangan.view') || hasPermission('semester.view')" class="mb-6">
                    <p class="px-3 mb-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Master Data
                    </p>
                    <NavItem v-if="hasPermission('prodi.view')" href="/prodi" icon="building" label="Program Studi" />
                    <NavItem v-if="hasPermission('ruangan.view')" href="/ruangan" icon="location" label="Ruangan" />
                    <NavItem v-if="hasPermission('semester.view')" href="/master/tahun-akademik" icon="clock" label="Tahun Akademik" />
                </div>
                
                <!-- Admin Menu -->
                <div v-if="hasPermission('users.view') || hasPermission('roles.view')" class="mb-6">
                    <p class="px-3 mb-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Manajemen
                    </p>
                    <NavItem v-if="hasPermission('users.view')" href="/users" icon="users" label="Pengguna" />
                    <NavItem v-if="hasPermission('roles.view')" href="/roles" icon="shield" label="Roles & Permissions" />
                    <NavItem v-if="hasPermission('settings.view')" href="/settings" icon="cog" label="Pengaturan" />
                </div>
                
                <!-- Laporan & Keuangan -->
                <div v-if="hasPermission('laporan.view') || hasPermission('sk_mengajar.view')" class="mb-6">
                    <p class="px-3 mb-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Laporan
                    </p>
                    <NavItem v-if="hasPermission('laporan.view')" href="/laporan" icon="document-report" label="Laporan" />
                    <NavItem v-if="hasPermission('absensi.rekap')" href="/laporan/honorarium" icon="currency" label="Rekap Honorarium" />
                    <NavItem v-if="hasPermission('sk_mengajar.view')" href="/sk-mengajar" icon="document" label="SK Mengajar" />
                </div>
            </nav>
            
            <!-- User Info at Bottom -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-800">
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <img
                        :src="auth.user?.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(auth.user?.name || 'User')}&color=7F9CF5&background=EBF4FF`"
                        :alt="auth.user?.name"
                        class="w-10 h-10 rounded-lg object-cover"
                    />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ auth.user?.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ auth.user?.email }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import NavItem from './NavItem.vue';

defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);

const page = usePage();
const auth = computed(() => page.props.auth);

const hasPermission = (permission) => {
    return auth.value.user?.permissions?.includes(permission) || false;
};
</script>
