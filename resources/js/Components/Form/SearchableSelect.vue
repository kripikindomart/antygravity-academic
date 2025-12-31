<template>
    <div class="relative" ref="containerRef">
        <div 
            @click="toggleDropdown"
            class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer transition flex items-center justify-between"
            :class="{'border-primary-500': isOpen}"
        >
            <span :class="selectedLabel ? 'text-gray-900 dark:text-white' : 'text-gray-400'">
                {{ selectedLabel || placeholder }}
            </span>
            <ChevronDownIcon class="w-5 h-5 text-gray-400 transition-transform" :class="{'rotate-180': isOpen}" />
        </div>

        <!-- Dropdown Panel -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div v-if="isOpen" class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Search Input -->
                <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                    <div class="relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input 
                            ref="searchInput"
                            v-model="searchQuery"
                            type="text"
                            class="w-full pl-9 pr-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500 bg-gray-50 dark:bg-gray-700"
                            :placeholder="searchPlaceholder"
                            @keydown.escape="closeDropdown"
                        />
                    </div>
                </div>

                <!-- Options List -->
                <ul class="max-h-60 overflow-y-auto py-1">
                    <!-- Empty/Clear Option -->
                    <li 
                        @click="selectOption(null)"
                        class="px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 italic text-sm"
                    >
                        {{ clearText }}
                    </li>
                    
                    <li 
                        v-for="option in filteredOptions" 
                        :key="option[valueKey]"
                        @click="selectOption(option)"
                        class="px-4 py-2 cursor-pointer hover:bg-primary-50 dark:hover:bg-primary-900/30 flex items-center gap-2"
                        :class="modelValue === option[valueKey] ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'text-gray-700 dark:text-gray-300'"
                    >
                        <span class="flex-1">{{ getOptionLabel(option) }}</span>
                        <CheckIcon v-if="modelValue === option[valueKey]" class="w-4 h-4 text-primary-600" />
                    </li>
                    
                    <li v-if="filteredOptions.length === 0" class="px-4 py-3 text-center text-gray-400 text-sm">
                        Tidak ditemukan
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { ChevronDownIcon, MagnifyingGlassIcon, CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: [String, Number, null],
    options: { type: Array, default: () => [] },
    valueKey: { type: String, default: 'id' },
    labelKey: { type: String, default: 'nama' },
    labelFormatter: { type: Function, default: null },
    placeholder: { type: String, default: 'Pilih...' },
    searchPlaceholder: { type: String, default: 'Cari...' },
    clearText: { type: String, default: '-- Kosongkan --' },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref(null);
const searchInput = ref(null);

const getOptionLabel = (option) => {
    if (props.labelFormatter) {
        return props.labelFormatter(option);
    }
    return option[props.labelKey];
};

const selectedLabel = computed(() => {
    if (!props.modelValue) return null;
    const selected = props.options.find(opt => opt[props.valueKey] === props.modelValue);
    return selected ? getOptionLabel(selected) : null;
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt => {
        const label = getOptionLabel(opt).toLowerCase();
        return label.includes(query);
    });
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        nextTick(() => {
            searchInput.value?.focus();
        });
    }
};

const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
};

const selectOption = (option) => {
    emit('update:modelValue', option ? option[props.valueKey] : null);
    closeDropdown();
};

// Click outside to close
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
