<template>
    <div :class="[
        'flex items-start gap-4 p-4 rounded-xl transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-800/50',
        schedule.status === 'ongoing' && 'bg-primary-50 dark:bg-primary-900/20 border-l-4 border-primary-500'
    ]">
        <!-- Time -->
        <div class="flex-shrink-0 text-center min-w-[70px]">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ schedule.time.split(' - ')[0] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ schedule.time.split(' - ')[1] }}
            </p>
        </div>
        
        <!-- Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white truncate">
                        {{ schedule.subject }}
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-1">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ schedule.room }}
                        </span>
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        {{ schedule.lecturer }}
                    </p>
                </div>
                
                <!-- Status Badge -->
                <span :class="[
                    'flex-shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold',
                    schedule.status === 'ongoing' 
                        ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
                ]">
                    {{ schedule.status === 'ongoing' ? 'Berlangsung' : 'Mendatang' }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    schedule: {
        type: Object,
        required: true,
    },
});
</script>
