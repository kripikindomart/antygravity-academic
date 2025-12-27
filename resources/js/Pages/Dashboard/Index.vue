<template>
    <AppLayout>
        <Head title="Dashboard" />
        
        <div class="space-y-6">
            <!-- Welcome Banner -->
            <div class="relative overflow-hidden bg-gradient-to-r from-primary-600 via-primary-700 to-primary-800 rounded-2xl p-6 lg:p-8 text-white">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                    <svg class="absolute right-0 top-0 h-full" viewBox="0 0 400 400" fill="none">
                        <circle cx="400" cy="0" r="300" stroke="white" stroke-width="100" fill="none"/>
                        <circle cx="400" cy="400" r="200" stroke="white" stroke-width="50" fill="none"/>
                    </svg>
                </div>
                
                <div class="relative z-10">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold mb-2">
                                Selamat Datang, {{ auth.user?.name?.split(' ')[0] }}! 👋
                            </h1>
                            <p class="text-primary-100 text-sm lg:text-base max-w-xl">
                                {{ getGreeting() }}. Kelola aktivitas akademik Anda dengan mudah melalui dashboard ini.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right hidden sm:block">
                                <p class="text-primary-100 text-sm">{{ currentDate }}</p>
                                <p class="text-xl font-semibold">{{ currentTime }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                <StatCard
                    title="Total Jadwal"
                    value="24"
                    change="+4"
                    changeType="increase"
                    icon="calendar"
                    color="primary"
                />
                <StatCard
                    title="Mata Kuliah"
                    value="12"
                    change="+2"
                    changeType="increase"
                    icon="book"
                    color="secondary"
                />
                <StatCard
                    title="Dosen Aktif"
                    value="18"
                    change="0"
                    changeType="neutral"
                    icon="users"
                    color="amber"
                />
                <StatCard
                    title="Mahasiswa"
                    value="156"
                    change="+12"
                    changeType="increase"
                    icon="academic"
                    color="rose"
                />
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Jadwal Hari Ini -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Jadwal Hari Ini
                            </h2>
                            <Link href="/jadwal" class="text-sm text-primary-600 hover:text-primary-500 font-medium">
                                Lihat Semua →
                            </Link>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <ScheduleItem
                                v-for="(schedule, index) in todaySchedules"
                                :key="index"
                                :schedule="schedule"
                                :style="{ animationDelay: `${index * 100}ms` }"
                                class="animate-slide-up"
                            />
                            
                            <div v-if="todaySchedules.length === 0" class="text-center py-12">
                                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">Tidak ada jadwal hari ini</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Aksi Cepat
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-3">
                            <QuickActionButton
                                icon="calendar-plus"
                                label="Tambah Jadwal"
                                href="/jadwal/create"
                            />
                            <QuickActionButton
                                icon="clipboard-check"
                                label="Input Absensi"
                                href="/absensi/create"
                            />
                            <QuickActionButton
                                icon="document-text"
                                label="Jurnal"
                                href="/jurnal"
                            />
                            <QuickActionButton
                                icon="chart-bar"
                                label="Laporan"
                                href="/laporan"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity & Notifications -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Activity -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Aktivitas Terbaru
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <ActivityItem
                                v-for="(activity, index) in recentActivities"
                                :key="index"
                                :activity="activity"
                            />
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Notifikasi
                            </h2>
                            <span class="px-2.5 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-semibold rounded-full">
                                3 Baru
                            </span>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <NotificationItem
                            v-for="(notification, index) in notifications"
                            :key="index"
                            :notification="notification"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import ScheduleItem from '@/Components/UI/ScheduleItem.vue';
import QuickActionButton from '@/Components/UI/QuickActionButton.vue';
import ActivityItem from '@/Components/UI/ActivityItem.vue';
import NotificationItem from '@/Components/UI/NotificationItem.vue';

const page = usePage();
const auth = computed(() => page.props.auth);

const currentTime = ref('');
const currentDate = ref('');
let timeInterval;

const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Selamat Pagi';
    if (hour < 15) return 'Selamat Siang';
    if (hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
};

onMounted(() => {
    updateTime();
    timeInterval = setInterval(updateTime, 1000);
});

onUnmounted(() => {
    clearInterval(timeInterval);
});

// Sample data - will be replaced with real data from backend
const todaySchedules = ref([
    {
        time: '08:00 - 10:30',
        subject: 'Metodologi Penelitian',
        room: 'Ruang 301',
        lecturer: 'Dr. Ahmad Fauzi, M.Pd',
        status: 'ongoing',
    },
    {
        time: '13:00 - 15:30',
        subject: 'Manajemen Strategik',
        room: 'Ruang 302',
        lecturer: 'Prof. Dr. Siti Aminah',
        status: 'upcoming',
    },
    {
        time: '16:00 - 18:30',
        subject: 'Statistik Lanjutan',
        room: 'Lab Komputer',
        lecturer: 'Dr. Budi Santoso',
        status: 'upcoming',
    },
]);

const recentActivities = ref([
    {
        type: 'jadwal',
        message: 'Jadwal Metodologi Penelitian diperbarui',
        time: '5 menit yang lalu',
    },
    {
        type: 'nilai',
        message: 'Nilai UTS Manajemen Strategik telah diinput',
        time: '1 jam yang lalu',
    },
    {
        type: 'absensi',
        message: 'Absensi pertemuan ke-8 telah dicatat',
        time: '2 jam yang lalu',
    },
    {
        type: 'survei',
        message: 'Survei evaluasi dosen dibuka',
        time: '1 hari yang lalu',
    },
]);

const notifications = ref([
    {
        title: 'Perubahan Jadwal',
        message: 'Jadwal Statistik Lanjutan dipindahkan ke Ruang 305',
        time: '10 menit yang lalu',
        read: false,
    },
    {
        title: 'Pengingat SK Mengajar',
        message: 'SK Mengajar semester ini belum digenerate',
        time: '1 jam yang lalu',
        read: false,
    },
    {
        title: 'Deadline Input Nilai',
        message: 'Batas waktu input nilai UTS: 3 hari lagi',
        time: '3 jam yang lalu',
        read: true,
    },
]);
</script>
