<template>
    <div class="relative">
        <VueDatePicker
            v-model="localValue"
            :locale="'id'"
            :enable-time-picker="false"
            :format="'dd MMMM yyyy'"
            :preview-format="'dd MMMM yyyy'"
            :auto-apply="true"
            :close-on-auto-apply="true"
            :clearable="clearable"
            :disabled="disabled"
            :placeholder="placeholder"
            :min-date="parsedMinDate"
            :max-date="maxDate"
            :dark="isDark"
            :teleport="true"
            :month-name-format="'long'"
            :week-start="1"
            text-input
            hide-input-icon
            @update:model-value="handleChange"
        >
            <template #dp-input="{ value, onInput, onEnter, onTab, onClear, onBlur, onKeypress, onPaste }">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        :value="value"
                        :placeholder="placeholder"
                        readonly
                        class="w-full px-4 py-3 pl-12 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 focus:border-primary-500 focus:outline-none transition-colors cursor-pointer"
                        @blur="onBlur"
                    />
                    <div v-if="value && clearable" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                        <button type="button" @click.stop="onClear" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
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

// Parse incoming value
const parseValue = (val) => {
    if (!val) return null;
    if (val instanceof Date) return val;
    const date = new Date(val);
    return isNaN(date.getTime()) ? null : date;
};

// Format for output (ISO string for backend)
const formatForBackend = (date) => {
    if (!date) return null;
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
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
/* Custom styles for VueDatePicker */
:root {
    --dp-font-family: 'Inter', system-ui, sans-serif;
    --dp-border-radius: 16px;
    --dp-cell-border-radius: 8px;
    --dp-button-height: 40px;
    --dp-month-year-row-height: 44px;
    --dp-menu-padding: 16px;
    --dp-primary-color: #0284c7;
    --dp-primary-text-color: #ffffff;
    --dp-background-color: #ffffff;
    --dp-text-color: #1f2937;
    --dp-hover-color: #f0f9ff;
    --dp-hover-text-color: #0284c7;
    --dp-border-color: #e5e7eb;
    --dp-menu-border-color: #e5e7eb;
    --dp-disabled-color: #f3f4f6;
    --dp-icon-color: #9ca3af;
}

.dark {
    --dp-background-color: #1f2937;
    --dp-text-color: #ffffff;
    --dp-hover-color: #374151;
    --dp-hover-text-color: #38bdf8;
    --dp-border-color: #4b5563;
    --dp-menu-border-color: #4b5563;
    --dp-disabled-color: #374151;
    --dp-icon-color: #6b7280;
}

.dp__menu {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
    border-radius: 16px !important;
}

.dp__calendar_header_item {
    font-weight: 600 !important;
    color: #6b7280 !important;
}

.dp__today {
    border: 2px solid #0284c7 !important;
}

.dp__active_date,
.dp__overlay_cell_active {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
}

.dp__calendar_item {
    transition: all 0.15s ease !important;
}

.dp__month_year_select:hover {
    color: #0284c7 !important;
}
</style>
