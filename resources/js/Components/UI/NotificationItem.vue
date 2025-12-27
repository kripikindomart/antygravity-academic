<template>
    <div :class="[
        'flex items-start gap-3 p-4 transition-colors cursor-pointer',
        notification.read 
            ? 'bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50' 
            : 'bg-primary-50/50 dark:bg-primary-900/10 hover:bg-primary-50 dark:hover:bg-primary-900/20'
    ]">
        <!-- Indicator -->
        <div class="flex-shrink-0 mt-1">
            <span v-if="!notification.read" class="block w-2 h-2 bg-primary-500 rounded-full"></span>
            <span v-else class="block w-2 h-2 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
        </div>
        
        <!-- Content -->
        <div class="flex-1 min-w-0">
            <p :class="[
                'text-sm font-medium truncate',
                notification.read ? 'text-gray-700 dark:text-gray-300' : 'text-gray-900 dark:text-white'
            ]">
                {{ notification.title }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">
                {{ notification.message }}
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                {{ notification.time }}
            </p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    notification: {
        type: Object,
        required: true,
    },
});
</script>
