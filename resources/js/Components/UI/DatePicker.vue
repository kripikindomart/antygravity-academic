<template>
    <div class="relative">
        <VueDatePicker
            v-model="localValue"
            :locale="'id'"
            :enable-time-picker="enableTime"
            :format="displayFormat"
            :preview-format="displayFormat"
            :auto-apply="true"
            :close-on-auto-apply="true"
            :clearable="clearable"
            :disabled="disabled"
            :placeholder="placeholder"
            :min-date="minDate"
            :max-date="maxDate"
            :dark="isDark"
            :teleport="true"
            :month-name-format="'long'"
            :week-start="1"
            :text-input="textInput"
            :input-class-name="inputClass"
            position="left"
            @update:model-value="handleChange"
        >
            <template #input-icon>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </template>
            <template #clear-icon="{ clear }">
                <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24" @click="clear">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
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
    enableTime: { type: Boolean, default: false },
    format: { type: String, default: 'dd/MM/yyyy' },
    placeholder: { type: String, default: 'Pilih tanggal...' },
    clearable: { type: Boolean, default: true },
    disabled: { type: Boolean, default: false },
    minDate: { type: [String, Date, null], default: null },
    maxDate: { type: [String, Date, null], default: null },
    textInput: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue']);

const localValue = ref(null);

const isDark = computed(() => {
    if (typeof window !== 'undefined') {
        return document.documentElement.classList.contains('dark');
    }
    return false;
});

const displayFormat = computed(() => {
    if (props.enableTime) {
        return 'dd/MM/yyyy HH:mm';
    }
    return props.format;
});

const inputClass = computed(() => {
    return 'w-full px-4 py-3 pl-11 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-0 focus:border-primary-500 focus:outline-none transition-colors';
});

// Parse incoming value
const parseValue = (val) => {
    if (!val) return null;
    if (val instanceof Date) return val;
    // Handle ISO string format (from database)
    const date = new Date(val);
    return isNaN(date.getTime()) ? null : date;
};

// Format for output (ISO string for backend)
const formatForBackend = (date) => {
    if (!date) return null;
    const d = new Date(date);
    // Adjust for timezone offset to get correct date
    const offset = d.getTimezoneOffset();
    d.setMinutes(d.getMinutes() - offset);
    return d.toISOString().split('T')[0];
};

const handleChange = (val) => {
    emit('update:modelValue', formatForBackend(val));
};

watch(() => props.modelValue, (newVal) => {
    localValue.value = parseValue(newVal);
}, { immediate: true });

onMounted(() => {
    localValue.value = parseValue(props.modelValue);
});
</script>

<style>
/* Custom styles for VueDatePicker to match our theme */
:root {
    --dp-font-family: 'Inter', system-ui, sans-serif;
    --dp-border-radius: 12px;
    --dp-cell-border-radius: 8px;
    --dp-button-height: 40px;
    --dp-month-year-row-height: 40px;
    --dp-menu-padding: 12px;
    
    /* Light mode */
    --dp-background-color: #ffffff;
    --dp-text-color: #1f2937;
    --dp-hover-color: #f3f4f6;
    --dp-hover-text-color: #1f2937;
    --dp-hover-icon-color: #6b7280;
    --dp-primary-color: #0284c7;
    --dp-primary-text-color: #ffffff;
    --dp-secondary-color: #e5e7eb;
    --dp-border-color: #e5e7eb;
    --dp-menu-border-color: #e5e7eb;
    --dp-border-color-hover: #0284c7;
    --dp-disabled-color: #f3f4f6;
    --dp-scroll-bar-background: #f3f4f6;
    --dp-scroll-bar-color: #d1d5db;
    --dp-success-color: #22c55e;
    --dp-success-color-disabled: #86efac;
    --dp-icon-color: #9ca3af;
    --dp-danger-color: #ef4444;
    --dp-highlight-color: rgba(2, 132, 199, 0.1);
}

.dark {
    --dp-background-color: #1f2937;
    --dp-text-color: #ffffff;
    --dp-hover-color: #374151;
    --dp-hover-text-color: #ffffff;
    --dp-hover-icon-color: #9ca3af;
    --dp-primary-color: #0284c7;
    --dp-primary-text-color: #ffffff;
    --dp-secondary-color: #4b5563;
    --dp-border-color: #4b5563;
    --dp-menu-border-color: #4b5563;
    --dp-border-color-hover: #0284c7;
    --dp-disabled-color: #374151;
    --dp-scroll-bar-background: #374151;
    --dp-scroll-bar-color: #6b7280;
    --dp-success-color: #22c55e;
    --dp-success-color-disabled: #15803d;
    --dp-icon-color: #6b7280;
    --dp-danger-color: #ef4444;
    --dp-highlight-color: rgba(2, 132, 199, 0.2);
}

/* Make the calendar popup more modern */
.dp__menu {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border-radius: 16px !important;
}

.dp__action_buttons {
    gap: 8px;
}

.dp__action_button {
    border-radius: 8px !important;
    padding: 8px 16px !important;
    font-weight: 500 !important;
}

.dp__select {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
}

.dp__today {
    border: 2px solid #0284c7 !important;
}

.dp__active_date {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
}

/* Smooth transitions */
.dp__calendar_item {
    transition: all 0.15s ease;
}

.dp__overlay_cell_active {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
}
</style>
