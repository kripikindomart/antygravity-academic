<template>
    <AppLayout>
        <Head title="Isi Survei EDOM" />

        <div class="space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Survei EDOM
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Berikan evaluasi untuk dosen Anda
                    </p>
                </div>
            </div>

            <!-- Surveys Grid -->
            <div
                v-if="targets && targets.length > 0"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            >
                <div
                    v-for="target in targets"
                    :key="target.id"
                    class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 hover:shadow-md transition-all"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0"
                        >
                            <svg
                                class="w-6 h-6 text-primary-600 dark:text-primary-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3
                                class="font-semibold text-gray-900 dark:text-white truncate"
                            >
                                {{
                                    target.kelas_matakuliah?.mata_kuliah
                                        ?.nama || "Mata Kuliah"
                                }}
                            </h3>
                            <p
                                class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"
                            >
                                {{ target.dosen?.nama || "Dosen" }}
                            </p>
                            <p
                                class="text-xs text-gray-400 dark:text-gray-500 mt-1"
                            >
                                {{ target.kelas_matakuliah?.nama }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold',
                                target.has_response
                                    ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                                    : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
                            ]"
                        >
                            <span
                                :class="[
                                    'w-1.5 h-1.5 rounded-full',
                                    target.has_response
                                        ? 'bg-green-500'
                                        : 'bg-amber-500',
                                ]"
                            ></span>
                            {{
                                target.has_response
                                    ? "Sudah Diisi"
                                    : "Belum Diisi"
                            }}
                        </span>
                        <Link
                            v-if="!target.has_response"
                            :href="route('survey.responses.create', target.id)"
                            class="px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white text-sm font-medium rounded-lg transition-all"
                        >
                            Isi Survei
                        </Link>
                        <span
                            v-else
                            class="text-sm text-green-600 dark:text-green-400 font-medium"
                            >✓ Selesai</span
                        >
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-12 text-center"
            >
                <div
                    class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4"
                >
                    <svg
                        class="w-8 h-8 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Tidak Ada Survei
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mt-1">
                    Tidak ada survei yang perlu diisi saat ini
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";

defineProps({ targets: Array });
</script>
