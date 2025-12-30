<template>
    <AppLayout title="Pengaturan AI">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Pengaturan Artificial Intelligence
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Provider Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilih Provider AI</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                    :class="form.ai_provider === 'gemini' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700'">
                                    <input type="radio" v-model="form.ai_provider" value="gemini" class="text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">Google Gemini</div>
                                        <div class="text-xs text-gray-500">Free Tier Available, Fast</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                    :class="form.ai_provider === 'openai' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700'">
                                    <input type="radio" v-model="form.ai_provider" value="openai" class="text-green-600 focus:ring-green-500">
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">OpenAI GPT</div>
                                        <div class="text-xs text-gray-500">Industry Standard (Paid)</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Gemini Settings -->
                            <div :class="{ 'opacity-50 pointer-events-none': form.ai_provider !== 'gemini' }" class="space-y-4 transition">
                                <h3 class="font-bold text-lg text-indigo-600 flex items-center gap-2">
                                    Google Gemini Configuration
                                    <span v-if="form.ai_provider === 'gemini'" class="px-2 py-0.5 rounded text-[10px] bg-indigo-100 text-indigo-800">Active</span>
                                </h3>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Key</label>
                                    <input v-model="form.gemini_api_key" type="password" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500">Dapatkan di <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-indigo-500 underline">Google AI Studio</a>.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model</label>
                                    <select v-model="form.gemini_model" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="gemini-1.5-flash">Gemini 1.5 Flash (Recommended)</option>
                                        <option value="gemini-1.5-pro">Gemini 1.5 Pro</option>
                                        <option value="gemini-1.0-pro">Gemini 1.0 Pro</option>
                                    </select>
                                </div>
                            </div>

                            <!-- OpenAI Settings -->
                            <div :class="{ 'opacity-50 pointer-events-none': form.ai_provider !== 'openai' }" class="space-y-4 transition">
                                <h3 class="font-bold text-lg text-green-600 flex items-center gap-2">
                                    OpenAI Configuration
                                    <span v-if="form.ai_provider === 'openai'" class="px-2 py-0.5 rounded text-[10px] bg-green-100 text-green-800">Active</span>
                                </h3>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Key</label>
                                    <input v-model="form.openai_api_key" type="password" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                     <p class="mt-1 text-xs text-gray-500">Dapatkan di <a href="https://platform.openai.com/api-keys" target="_blank" class="text-green-500 underline">OpenAI Platform</a>.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model</label>
                                    <select v-model="form.openai_model" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Fast & Cheap)</option>
                                        <option value="gpt-4o">GPT-4o (Smartest)</option>
                                        <option value="gpt-4-turbo">GPT-4 Turbo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                             <div  class="mr-3">
                                <div v-if="form.recentlySuccessful" class="text-sm text-green-600">
                                    Berhasil disimpan.
                                </div>
                            </div>
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    ai_provider: props.settings.ai_provider || 'gemini',
    openai_api_key: props.settings.openai_api_key || '',
    openai_model: props.settings.openai_model || 'gpt-3.5-turbo',
    gemini_api_key: props.settings.gemini_api_key || '',
    gemini_model: props.settings.gemini_model || 'gemini-1.5-flash',
});

const submit = () => {
    form.post(route('settings.ai.update'), {
        preserveScroll: true,
    });
};
</script>
