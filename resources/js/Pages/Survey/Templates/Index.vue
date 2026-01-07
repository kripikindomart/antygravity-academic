<template>
    <AppLayout>
        <Head title="Template Survei EDOM" />

        <div class="space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Template Survei EDOM
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Kelola template pertanyaan untuk survei evaluasi dosen
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="reloadData"
                        class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl transition-all"
                        title="Refresh"
                    >
                        <svg
                            class="w-5 h-5"
                            :class="{ 'animate-spin': isLoading }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                    </button>
                    <button
                        @click="showImportModal = true"
                        class="p-2.5 bg-green-50 hover:bg-green-100 text-green-600 dark:bg-green-900/20 dark:hover:bg-green-900/30 dark:text-green-400 rounded-xl transition-all"
                        title="Import Template"
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
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                            />
                        </svg>
                    </button>
                    <Link
                        :href="route('survey.templates.create')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all hover:scale-105"
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
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Buat Template
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400"
                        >
                            <svg
                                class="w-6 h-6"
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
                        <div>
                            <p
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ stats?.total || 0 }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Total Template
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ stats?.active || 0 }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Aktif
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ stats?.total_questions || 0 }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Total Pertanyaan
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ stats?.total_periods || 0 }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Periode Aktif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Filter -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4"
            >
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                            >
                                <svg
                                    class="w-5 h-5 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama template..."
                                class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500/20"
                                @input="debouncedSearch"
                            />
                        </div>
                    </div>
                    <select
                        v-model="statusFilter"
                        @change="applyFilters"
                        class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead
                            class="bg-gradient-to-r from-primary-600 to-primary-700"
                        >
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider"
                                >
                                    Template
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider"
                                >
                                    Pertanyaan
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider"
                                >
                                    Periode
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-800"
                        >
                            <tr
                                v-for="template in templates.data"
                                :key="template.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center"
                                        >
                                            <svg
                                                class="w-5 h-5 text-primary-600 dark:text-primary-400"
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
                                        <div>
                                            <p
                                                class="font-medium text-gray-900 dark:text-white"
                                            >
                                                {{ template.nama }}
                                            </p>
                                            <p
                                                class="text-sm text-gray-500 dark:text-gray-400 line-clamp-1"
                                            >
                                                {{
                                                    template.deskripsi ||
                                                    "Tidak ada deskripsi"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400"
                                    >
                                        {{
                                            template.questions_count
                                        }}
                                        Pertanyaan
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400"
                                    >
                                        {{ template.periods_count }} Periode
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <button
                                        @click="toggleStatus(template)"
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold transition-colors cursor-pointer',
                                            template.is_active
                                                ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                                                : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'w-1.5 h-1.5 rounded-full',
                                                template.is_active
                                                    ? 'bg-green-500'
                                                    : 'bg-red-500',
                                            ]"
                                        ></span>
                                        {{
                                            template.is_active
                                                ? "Aktif"
                                                : "Nonaktif"
                                        }}
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'survey.templates.edit',
                                                    template.id
                                                )
                                            "
                                            class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors"
                                            title="Edit"
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
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="duplicateTemplate(template)"
                                            class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                            title="Duplikat"
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
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click="confirmDelete(template)"
                                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                            title="Hapus"
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-if="
                                    !templates.data ||
                                    templates.data.length === 0
                                "
                            >
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4"
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
                                        <p
                                            class="text-gray-500 dark:text-gray-400 font-medium"
                                        >
                                            Belum ada template survei
                                        </p>
                                        <p class="text-sm text-gray-400 mt-1">
                                            Mulai dengan membuat template baru
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="templates.data && templates.data.length > 0"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Menampilkan {{ templates.from || 0 }} -
                        {{ templates.to || 0 }} dari
                        {{ templates.total || 0 }} template
                    </p>
                    <div class="flex gap-1">
                        <template
                            v-for="link in templates.links"
                            :key="link.label"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                    link.active
                                        ? 'bg-primary-600 text-white'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800',
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1.5 text-sm text-gray-400"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showImportModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="showImportModal = false"
                >
                    <div
                        class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"
                    ></div>
                    <div
                        class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden"
                    >
                        <div
                            class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-5"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm"
                                    >
                                        <svg
                                            class="w-6 h-6 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2
                                            class="text-xl font-bold text-white"
                                        >
                                            Import Template
                                        </h2>
                                        <p class="text-white/70 text-sm">
                                            Upload file JSON template survei
                                        </p>
                                    </div>
                                </div>
                                <button
                                    @click="showImportModal = false"
                                    class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                                >
                                    <svg
                                        class="w-6 h-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div
                                class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-8 text-center hover:border-primary-500 transition-colors"
                            >
                                <input
                                    type="file"
                                    ref="importInput"
                                    accept=".json"
                                    class="hidden"
                                    @change="handleImport"
                                />
                                <svg
                                    class="w-12 h-12 text-gray-400 mx-auto mb-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                    />
                                </svg>
                                <p
                                    class="text-gray-600 dark:text-gray-400 mb-2"
                                >
                                    Drag & drop file JSON atau
                                </p>
                                <button
                                    @click="$refs.importInput.click()"
                                    class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors"
                                >
                                    Pilih File
                                </button>
                            </div>
                            <div
                                class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl"
                            >
                                <p
                                    class="text-sm text-blue-700 dark:text-blue-400"
                                >
                                    <strong>Format JSON:</strong> { "nama":
                                    "...", "deskripsi": "...", "questions": [{
                                    "pertanyaan": "...", "tipe": "scale",
                                    "options": [...] }] }
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";
import debounce from "lodash/debounce";

const props = defineProps({
    templates: Object,
    stats: Object,
    filters: Object,
});

const isLoading = ref(false);
const search = ref(props.filters?.search || "");
const statusFilter = ref(props.filters?.status || "");
const showImportModal = ref(false);
const importInput = ref(null);

const reloadData = () => {
    isLoading.value = true;
    router.reload({ onFinish: () => (isLoading.value = false) });
};

const debouncedSearch = debounce(() => applyFilters(), 300);

const applyFilters = () => {
    router.get(
        route("survey.templates.index"),
        { search: search.value, status: statusFilter.value },
        { preserveState: true }
    );
};

const toggleStatus = (template) => {
    router.put(
        route("survey.templates.update", template.id),
        { ...template, is_active: !template.is_active },
        { preserveScroll: true }
    );
};

const duplicateTemplate = (template) => {
    if (confirm('Duplikat template "' + template.nama + '"?')) {
        router.post(route("survey.templates.duplicate", template.id));
    }
};

const confirmDelete = (template) => {
    if (
        confirm(
            'Hapus template "' +
                template.nama +
                '"? Semua periode dan response terkait akan ikut terhapus.'
        )
    ) {
        router.delete(route("survey.templates.destroy", template.id));
    }
};

const handleImport = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const data = JSON.parse(e.target.result);
            router.post(
                route("survey.templates.store"),
                {
                    nama: data.nama || "Imported Template",
                    deskripsi: data.deskripsi || "",
                    is_active: true,
                    questions: data.questions || [],
                },
                {
                    onSuccess: () => {
                        showImportModal.value = false;
                        importInput.value.value = "";
                    },
                }
            );
        } catch (err) {
            alert("Format JSON tidak valid");
        }
    };
    reader.readAsText(file);
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
    transform: scale(0.95);
}
</style>
