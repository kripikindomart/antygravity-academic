<template>
    <Link
        :href="href"
        class="flex flex-col items-center justify-center gap-2 p-4 bg-gray-50 dark:bg-gray-800 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-xl transition-all duration-200 hover:scale-105 group"
    >
        <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-700 shadow-sm flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:text-primary-600 dark:group-hover:text-primary-400 group-hover:shadow-md transition-all">
            <!-- Calendar Plus -->
            <svg v-if="icon === 'calendar-plus'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v6m-3-3h6"/>
            </svg>
            
            <!-- Clipboard Check -->
            <svg v-else-if="icon === 'clipboard-check'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            
            <!-- Document Text -->
            <svg v-else-if="icon === 'document-text'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            
            <!-- Chart Bar -->
            <svg v-else-if="icon === 'chart-bar'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-300 group-hover:text-primary-600 dark:group-hover:text-primary-400 text-center">
            {{ label }}
        </span>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    icon: String,
    label: String,
    href: String,
});
</script>
