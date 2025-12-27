<template>
    <div class="w-full">
        <VueDatePicker
            v-model="localValue"
            :enable-time-picker="false"
            :clearable="clearable"
            :disabled="disabled"
            :placeholder="placeholder"
            :min-date="minDate"
            :max-date="maxDate"
            :dark="isDark"
            teleport="body"
            auto-apply
            :week-start="1"
            :transitions="false"
        >
            <template #trigger>
                <div class="relative cursor-pointer group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        :value="displayValue"
                        :placeholder="placeholder"
                        readonly
                        class="w-full px-4 py-3 pl-11 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 focus:border-primary-500 focus:outline-none transition-colors cursor-pointer text-sm font-medium"
                    />
                    <div v-if="localValue && clearable" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button type="button" @click.stop="clearDate" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </VueDatePicker>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    modelValue: { type: [String, Date, null], default: null },
    placeholder: { type: String, default: 'Pilih tanggal...' },
    clearable: { type: Boolean, default: true },
    disabled: { type: Boolean, default: false },
    minDate: { type: [String, Date, null], default: null },
    maxDate: { type: [String, Date, null], default: null },
});

const emit = defineEmits(['update:modelValue']);

const localValue = ref(null);

const isDark = computed(() => {
    if (typeof window !== 'undefined') {
        return document.documentElement.classList.contains('dark');
    }
    return false;
});

// Display formatter (Indonesia)
const displayValue = computed(() => {
    if (!localValue.value) return '';
    try {
        const d = new Date(localValue.value);
        if (isNaN(d.getTime())) return '';
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    } catch (e) {
        return '';
    }
});

const formatForBackend = (date) => {
    if (!date) return null;
    try {
        const d = new Date(date);
        // Using local ISO string trick
        const offset = d.getTimezoneOffset() * 60000;
        const localDate = new Date(d.getTime() - offset);
        return localDate.toISOString().split('T')[0];
    } catch (e) {
        return null;
    }
};

const clearDate = () => {
    localValue.value = null;
    emit('update:modelValue', null);
};

// Sync internal value -> parent
watch(localValue, (newVal) => {
    const backendVal = formatForBackend(newVal);
    emit('update:modelValue', backendVal);
});

// Sync parent value -> internal
watch(() => props.modelValue, (newVal) => {
    if (!newVal) {
        localValue.value = null;
        return;
    }
    const d = new Date(newVal);
    if (!isNaN(d.getTime())) {
        localValue.value = d;
    }
}, { immediate: true });
</script>

<style>
.dp__theme_light {
    --dp-primary-color: #0284c7;
    --dp-border-radius: 12px;
}
.dp__theme_dark {
    --dp-primary-color: #38bdf8;
    --dp-border-radius: 12px;
    --dp-background-color: #1f2937;
    --dp-text-color: #ffffff;
    --dp-border-color: #374151;
}
.dp__menu {
    border: none !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
    z-index: 99999 !important;
}
/* Hide default input just in case */
.dp__input {
    display: none;
}
</style>
