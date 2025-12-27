<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
        <div class="flex flex-col items-center gap-4">
            <!-- Animated Spinner -->
            <div class="relative">
                <div class="w-16 h-16 border-4 border-primary-200 dark:border-primary-800 rounded-full"></div>
                <div class="absolute top-0 left-0 w-16 h-16 border-4 border-transparent border-t-primary-600 dark:border-t-primary-400 rounded-full animate-spin"></div>
            </div>
            
            <!-- Loading Text -->
            <div class="text-center">
                <p class="text-lg font-medium text-white">{{ message || 'Memuat...' }}</p>
                <p v-if="subMessage" class="text-sm text-gray-300 mt-1">{{ subMessage }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    message: String,
    subMessage: String,
});
</script>
