<template>
    <div :class="[
        'relative overflow-hidden p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 transition-all duration-300 hover:shadow-md hover:scale-[1.02]',
        'animate-slide-up'
    ]">
        <!-- Background Gradient -->
        <div :class="[
            'absolute top-0 right-0 w-32 h-32 rounded-full blur-3xl opacity-20 -translate-y-1/2 translate-x-1/2',
            colorClasses.bg
        ]"></div>
        
        <div class="relative">
            <div class="flex items-center justify-between mb-4">
                <!-- Icon -->
                <div :class="[
                    'w-12 h-12 rounded-xl flex items-center justify-center',
                    colorClasses.iconBg
                ]">
                    <!-- Calendar -->
                    <svg v-if="icon === 'calendar'" :class="['w-6 h-6', colorClasses.iconText]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    
                    <!-- Book -->
                    <svg v-else-if="icon === 'book'" :class="['w-6 h-6', colorClasses.iconText]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    
                    <!-- Users -->
                    <svg v-else-if="icon === 'users'" :class="['w-6 h-6', colorClasses.iconText]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    
                    <!-- Academic -->
                    <svg v-else-if="icon === 'academic'" :class="['w-6 h-6', colorClasses.iconText]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    </svg>
                </div>
                
                <!-- Change Indicator -->
                <div v-if="change" :class="[
                    'flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold',
                    changeType === 'increase' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' :
                    changeType === 'decrease' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' :
                    'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
                ]">
                    <svg v-if="changeType === 'increase'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    <svg v-else-if="changeType === 'decrease'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                    <span>{{ change }}</span>
                </div>
            </div>
            
            <!-- Value -->
            <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                {{ value }}
            </p>
            
            <!-- Title -->
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ title }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: String,
    value: [String, Number],
    change: String,
    changeType: {
        type: String,
        default: 'neutral',
        validator: (value) => ['increase', 'decrease', 'neutral'].includes(value),
    },
    icon: String,
    color: {
        type: String,
        default: 'primary',
    },
});

const colorClasses = computed(() => {
    const colors = {
        primary: {
            bg: 'bg-primary-500',
            iconBg: 'bg-primary-100 dark:bg-primary-900/30',
            iconText: 'text-primary-600 dark:text-primary-400',
        },
        secondary: {
            bg: 'bg-secondary-500',
            iconBg: 'bg-secondary-100 dark:bg-secondary-900/30',
            iconText: 'text-secondary-600 dark:text-secondary-400',
        },
        amber: {
            bg: 'bg-amber-500',
            iconBg: 'bg-amber-100 dark:bg-amber-900/30',
            iconText: 'text-amber-600 dark:text-amber-400',
        },
        rose: {
            bg: 'bg-rose-500',
            iconBg: 'bg-rose-100 dark:bg-rose-900/30',
            iconText: 'text-rose-600 dark:text-rose-400',
        },
    };
    return colors[props.color] || colors.primary;
});
</script>
