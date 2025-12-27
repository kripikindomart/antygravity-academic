<template>
    <div class="flex items-start gap-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded-lg transition-colors">
        <!-- Icon -->
        <div :class="[
            'w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
            iconClasses
        ]">
            <!-- Jadwal -->
            <svg v-if="activity.type === 'jadwal'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            
            <!-- Nilai -->
            <svg v-else-if="activity.type === 'nilai'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            
            <!-- Absensi -->
            <svg v-else-if="activity.type === 'absensi'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            
            <!-- Survei -->
            <svg v-else-if="activity.type === 'survei'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            
            <!-- Default -->
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        
        <!-- Content -->
        <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-700 dark:text-gray-300 truncate">
                {{ activity.message }}
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                {{ activity.time }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    activity: {
        type: Object,
        required: true,
    },
});

const iconClasses = computed(() => {
    const types = {
        jadwal: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        nilai: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
        absensi: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
        survei: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    };
    return types[props.activity.type] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400';
});
</script>
