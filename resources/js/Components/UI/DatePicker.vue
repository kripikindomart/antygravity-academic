<template>
    <div class="w-full">
        <VueDatePicker
            v-model="localValue"
            locale="id-ID"
            :enable-time-picker="false"
            :format="formatDate"
            :clearable="clearable"
            :disabled="disabled"
            :placeholder="placeholder"
            :min-date="parsedMinDate"
            :max-date="maxDate"
            :dark="isDark"
            teleport="body"
            auto-apply
            :month-change-on-scroll="false"
            :week-start="1"
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
                        class="w-full px-4 py-3 pl-11 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 focus:border-primary-500 focus:outline-none transition-colors cursor-pointer text-sm"
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
import { ref, watch, computed, onMounted } from 'vue';
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

const parsedMinDate = computed(() => {
    if (!props.minDate) return null;
    return new Date(props.minDate);
});

const parseValue = (val) => {
    if (!val) return null;
    if (val instanceof Date) return val;
    // Handle YYYY-MM-DD string
    const date = new Date(val);
    return isNaN(date.getTime()) ? null : date;
};

// Custom formatter for the input display
const displayValue = computed(() => {
    if (!localValue.value) return '';
    return new Date(localValue.value).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
});

// Format for the DatePicker internal display (if triggers fallback)
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const formatForBackend = (date) => {
    if (!date) return null;
    const d = new Date(date);
    // Adjust for timezone offset to ensure YYYY-MM-DD is correct for the selected date
    const offset = d.getTimezoneOffset() * 60000;
    const localDate = new Date(d.getTime() - offset);
    return localDate.toISOString().split('T')[0];
};

const clearDate = () => {
    localValue.value = null;
    emit('update:modelValue', null);
};

// Watch for internal changes
watch(localValue, (newVal) => {
    if (newVal) {
        emit('update:modelValue', formatForBackend(newVal));
    } else {
        // Only emit null if it was previously set (avoid loop)
        if (props.modelValue) emit('update:modelValue', null);
    }
});

// Watch for external changes
watch(() => props.modelValue, (newVal) => {
    // Avoid resetting if the value is effectively the same (comparing YYYY-MM-DD)
    const currentBackend = formatForBackend(localValue.value);
    if (newVal !== currentBackend) {
        localValue.value = parseValue(newVal);
    }
}, { immediate: true });

</script>

<style>
/* Override variables for modern look */
.dp__theme_light {
    --dp-primary-color: #0284c7;
    --dp-border-radius: 12px;
    --dp-font-family: 'Inter', sans-serif;
}
.dp__theme_dark {
    --dp-primary-color: #38bdf8;
    --dp-border-radius: 12px;
    --dp-font-family: 'Inter', sans-serif;
    --dp-background-color: #1f2937;
    --dp-text-color: #ffffff;
    --dp-border-color: #374151;
}

/* Ensure z-index is high enough */
.dp__menu {
    z-index: 99999 !important;
    border: none !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
}

/* Hide the time picker toggle just in case */
.dp__action_row svg {
    display: none;
}
</style>
