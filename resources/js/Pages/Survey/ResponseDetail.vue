<template>
    <AppLayout>
        <Head :title="`Response - ${response.mahasiswa}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('survey.dashboard')"
                    class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 transition"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </Link>
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Detail Response
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400">
                        {{ response.mahasiswa }} ({{ response.nim }})
                    </p>
                </div>
            </div>

            <!-- Response Info -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6"
            >
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Mahasiswa
                        </p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ response.mahasiswa }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            NIM
                        </p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ response.nim }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Dosen
                        </p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ response.dosen }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Waktu Submit
                        </p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ response.submitted_at }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Answers -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden"
            >
                <div
                    class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4"
                >
                    <h2 class="text-lg font-semibold text-white">
                        Jawaban ({{ answers.length }})
                    </h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="(ans, idx) in answers" :key="idx" class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <span
                                    v-if="ans.kategori"
                                    class="inline-block px-2 py-0.5 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-medium rounded-full mb-1"
                                >
                                    {{ ans.kategori }}
                                </span>
                                <p
                                    class="text-gray-900 dark:text-white font-medium"
                                >
                                    {{ idx + 1 }}. {{ ans.question }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span
                                    v-if="ans.nilai"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400"
                                >
                                    {{ ans.nilai }}/5
                                </span>
                                <p
                                    class="text-gray-600 dark:text-gray-400 mt-1"
                                >
                                    {{ ans.answer }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="answers.length === 0"
                        class="p-8 text-center text-gray-500"
                    >
                        Tidak ada jawaban ditemukan
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";

defineProps({
    response: Object,
    answers: Array,
});
</script>
