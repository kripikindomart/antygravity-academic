<template>
    <AppLayout>
        <Head title="Periode Survei EDOM" />

        <div class="space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Periode Survei EDOM
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Kelola jadwal pelaksanaan survei evaluasi
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="reloadData"
                        class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 text-gray-600 rounded-xl transition-all"
                        :class="{ 'animate-spin': isLoading }"
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
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                    </button>
                    <button
                        @click="openModal()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg transition-all"
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
                        Buat Periode
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-600"
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
                                {{ stats?.total || 0 }}
                            </p>
                            <p class="text-sm text-gray-500">Total Periode</p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-green-100 dark:bg-green-900/30 text-green-600"
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
                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ stats?.active || 0 }}
                            </p>
                            <p class="text-sm text-gray-500">Aktif</p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-amber-100 dark:bg-amber-900/30 text-amber-600"
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
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ stats?.total_targets || 0 }}
                            </p>
                            <p class="text-sm text-gray-500">Target</p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-purple-100 dark:bg-purple-900/30 text-purple-600"
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
                                {{ stats?.total_responses || 0 }}
                            </p>
                            <p class="text-sm text-gray-500">Response</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4"
            >
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1 relative">
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
                            placeholder="Cari periode..."
                            class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl"
                            @input="debouncedSearch"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        @change="applyFilters"
                        class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl"
                    >
                        <option value="">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="active">Aktif</option>
                        <option value="closed">Ditutup</option>
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
                                    class="px-6 py-4 text-left text-xs font-semibold text-white uppercase"
                                >
                                    Periode
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-white uppercase"
                                >
                                    Template
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-white uppercase"
                                >
                                    Tanggal
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-white uppercase"
                                >
                                    Target
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-white uppercase"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-white uppercase"
                                >
                                    Public URL
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold text-white uppercase"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-800"
                        >
                            <tr
                                v-for="period in periods.data"
                                :key="period.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <p
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {{
                                            period.nama ||
                                            "Periode " + period.id
                                        }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ period.tahun_akademik?.nama }}
                                    </p>
                                </td>
                                <td
                                    class="px-6 py-4 text-gray-600 dark:text-gray-300"
                                >
                                    {{ period.template?.nama }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <p class="text-gray-900 dark:text-white">
                                        {{ formatDate(period.tanggal_mulai) }}
                                    </p>
                                    <p class="text-gray-500">
                                        s/d
                                        {{ formatDate(period.tanggal_selesai) }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700"
                                        >{{
                                            period.targets_count || 0
                                        }}
                                        Kelas</span
                                    >
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold',
                                            period.status === 'active'
                                                ? 'bg-green-100 text-green-700'
                                                : period.status === 'closed'
                                                ? 'bg-red-100 text-red-700'
                                                : 'bg-gray-100 text-gray-600',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'w-1.5 h-1.5 rounded-full',
                                                period.status === 'active'
                                                    ? 'bg-green-500'
                                                    : period.status === 'closed'
                                                    ? 'bg-red-500'
                                                    : 'bg-gray-400',
                                            ]"
                                        ></span>
                                        {{
                                            period.status === "active"
                                                ? "Aktif"
                                                : period.status === "closed"
                                                ? "Ditutup"
                                                : "Draft"
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        v-if="period.status === 'active'"
                                        class="flex items-center gap-2"
                                    >
                                        <input
                                            type="text"
                                            :value="getPublicUrl(period)"
                                            readonly
                                            class="w-40 px-2 py-1 text-xs bg-gray-50 border border-gray-200 rounded truncate"
                                        />
                                        <button
                                            @click="copyUrl(period)"
                                            class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg"
                                            title="Copy URL"
                                        >
                                            <svg
                                                class="w-4 h-4"
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
                                    </div>
                                    <span v-else class="text-xs text-gray-400"
                                        >-</span
                                    >
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <button
                                            v-if="period.status === 'draft'"
                                            @click="activate(period)"
                                            class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg"
                                            title="Aktifkan"
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
                                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="period.status === 'active'"
                                            @click="closePeriod(period)"
                                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg"
                                            title="Tutup"
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
                                                    d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click="openModal(period)"
                                            class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg"
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
                                        </button>
                                        <button
                                            @click="confirmDelete(period)"
                                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg"
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
                            <tr v-if="!periods.data?.length">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <p class="text-gray-500 font-medium">
                                        Belum ada periode survei
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-if="periods.data?.length"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between"
                >
                    <p class="text-sm text-gray-500">
                        Menampilkan {{ periods.from }} - {{ periods.to }} dari
                        {{ periods.total }}
                    </p>
                    <div class="flex gap-1">
                        <template
                            v-for="link in periods.links"
                            :key="link.label"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-sm font-medium',
                                    link.active
                                        ? 'bg-primary-600 text-white'
                                        : 'text-gray-600 hover:bg-gray-100',
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="showModal = false"
                >
                    <div
                        class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"
                    ></div>
                    <div
                        class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden"
                    >
                        <div
                            class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-5 flex items-center justify-between"
                        >
                            <h2 class="text-xl font-bold text-white">
                                {{
                                    editingPeriod
                                        ? "Edit Periode"
                                        : "Buat Periode Baru"
                                }}
                            </h2>
                            <button
                                @click="showModal = false"
                                class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg"
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
                        <form
                            @submit.prevent="submitForm"
                            class="p-6 space-y-5 overflow-y-auto max-h-[calc(90vh-150px)]"
                        >
                            <!-- Basic Info -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                        >Nama Periode</label
                                    >
                                    <input
                                        v-model="form.nama"
                                        type="text"
                                        placeholder="EDOM Semester Ganjil 2025/2026"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:border-primary-500 focus:ring-0"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                        >Tahun Akademik</label
                                    >
                                    <select
                                        v-model="form.tahun_akademik_id"
                                        @change="loadKelasByTa"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
                                    >
                                        <option
                                            v-for="ta in tahunAkademiks"
                                            :key="ta.id"
                                            :value="ta.id"
                                        >
                                            {{ ta.nama }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                        >Template</label
                                    >
                                    <select
                                        v-model="form.survey_template_id"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
                                    >
                                        <option
                                            v-for="t in templates"
                                            :key="t.id"
                                            :value="t.id"
                                        >
                                            {{ t.nama }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                        >Tanggal Mulai</label
                                    >
                                    <input
                                        v-model="form.tanggal_mulai"
                                        type="date"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                        >Tanggal Selesai</label
                                    >
                                    <input
                                        v-model="form.tanggal_selesai"
                                        type="date"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl"
                                    />
                                </div>
                            </div>

                            <!-- Target Kelas Selection -->
                            <div
                                class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800"
                            >
                                <div
                                    class="flex items-center justify-between mb-3"
                                >
                                    <label
                                        class="font-medium text-amber-800 dark:text-amber-300"
                                        >Target Kelas Matakuliah</label
                                    >
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="selectAllKelas"
                                            class="text-xs px-3 py-1 bg-amber-200 hover:bg-amber-300 text-amber-800 rounded-lg"
                                        >
                                            Pilih Semua
                                        </button>
                                        <button
                                            type="button"
                                            @click="form.targets = []"
                                            class="text-xs px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg"
                                        >
                                            Hapus Semua
                                        </button>
                                    </div>
                                </div>
                                <div
                                    v-if="loadingKelas"
                                    class="py-4 text-center text-gray-500"
                                >
                                    Memuat kelas...
                                </div>
                                <div
                                    v-else-if="kelasList.length === 0"
                                    class="py-4 text-center text-gray-500 text-sm"
                                >
                                    Tidak ada kelas pada tahun akademik ini
                                </div>
                                <div
                                    v-else
                                    class="max-h-48 overflow-y-auto space-y-2"
                                >
                                    <label
                                        v-for="kelas in kelasList"
                                        :key="kelas.id"
                                        class="flex items-start gap-3 p-2 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/30 cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="kelas.id"
                                            v-model="form.targets"
                                            class="mt-1 w-4 h-4 rounded text-primary-600"
                                        />
                                        <div class="flex-1">
                                            <p
                                                class="text-sm font-medium text-gray-900 dark:text-white"
                                            >
                                                {{ kelas.nama }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ kelas.mata_kuliah?.nama }}
                                                <span
                                                    v-if="kelas.semester"
                                                    class="ml-1 px-1.5 py-0.5 bg-primary-100 text-primary-700 rounded text-xs"
                                                    >{{ kelas.semester }}</span
                                                >
                                            </p>
                                        </div>
                                    </label>
                                </div>
                                <p
                                    class="mt-2 text-xs text-amber-700 dark:text-amber-400"
                                >
                                    Dipilih: {{ form.targets.length }} kelas
                                </p>
                            </div>

                            <!-- Status & Options -->
                            <div class="flex flex-wrap items-center gap-6">
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="form.status"
                                        type="radio"
                                        value="draft"
                                        class="w-4 h-4 text-primary-600"
                                    />
                                    <span class="text-sm">Draft</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="form.status"
                                        type="radio"
                                        value="active"
                                        class="w-4 h-4 text-primary-600"
                                    />
                                    <span class="text-sm">Langsung Aktif</span>
                                </label>
                                <label class="flex items-center gap-2 ml-auto">
                                    <input
                                        v-model="form.is_mandatory"
                                        type="checkbox"
                                        class="w-4 h-4 rounded text-primary-600"
                                    />
                                    <span class="text-sm">Wajib Isi</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="form.allow_guest"
                                        type="checkbox"
                                        class="w-4 h-4 rounded text-primary-600"
                                    />
                                    <span class="text-sm">Izinkan Guest</span>
                                </label>
                            </div>

                            <!-- Actions -->
                            <div
                                class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800"
                            >
                                <button
                                    type="button"
                                    @click="showModal = false"
                                    class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white rounded-xl font-medium disabled:opacity-50 transition-all"
                                >
                                    {{
                                        form.processing
                                            ? "Menyimpan..."
                                            : "Simpan"
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Toast for copy -->
        <Transition name="toast">
            <div
                v-if="showToast"
                class="fixed bottom-4 right-4 z-50 px-4 py-3 bg-green-600 text-white rounded-xl shadow-lg flex items-center gap-2"
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
                        d="M5 13l4 4L19 7"
                    />
                </svg>
                URL berhasil disalin!
            </div>
        </Transition>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showDeleteModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div
                        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                        @click="showDeleteModal = false"
                    ></div>
                    <div
                        class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-md w-full p-6 text-center"
                    >
                        <div
                            class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center"
                        >
                            <svg
                                class="w-8 h-8 text-red-600"
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
                        </div>
                        <h3
                            class="text-xl font-bold text-gray-900 dark:text-white mb-2"
                        >
                            Hapus Periode?
                        </h3>
                        <p class="text-gray-500 mb-6">
                            Apakah Anda yakin ingin menghapus periode
                            <span
                                class="font-medium text-gray-700 dark:text-gray-300"
                                >"{{ deletingPeriod?.nama }}"</span
                            >? Tindakan ini tidak dapat dibatalkan.
                        </p>
                        <div class="flex gap-3 justify-center">
                            <button
                                @click="showDeleteModal = false"
                                class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                @click="executeDelete"
                                :disabled="deleteProcessing"
                                class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium disabled:opacity-50 transition-colors"
                            >
                                {{
                                    deleteProcessing
                                        ? "Menghapus..."
                                        : "Ya, Hapus"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";
import debounce from "lodash/debounce";
import axios from "axios";

const props = defineProps({
    periods: Object,
    stats: Object,
    filters: Object,
    templates: Array,
    tahunAkademiks: Array,
    activeTahunAkademikId: Number,
    kelasMatakuliahs: { type: Array, default: () => [] },
});

const isLoading = ref(false);
const search = ref(props.filters?.search || "");
const statusFilter = ref(props.filters?.status || "");
const showModal = ref(false);
const showToast = ref(false);
const editingPeriod = ref(null);
const kelasList = ref(props.kelasMatakuliahs || []);
const loadingKelas = ref(false);

// Delete Modal
const showDeleteModal = ref(false);
const deletingPeriod = ref(null);
const deleteProcessing = ref(false);

const form = useForm({
    nama: "",
    tahun_akademik_id:
        props.activeTahunAkademikId || props.tahunAkademiks?.[0]?.id,
    survey_template_id: props.templates?.[0]?.id,
    tanggal_mulai: new Date().toISOString().split("T")[0],
    tanggal_selesai: new Date(Date.now() + 14 * 86400000)
        .toISOString()
        .split("T")[0],
    status: "draft",
    is_mandatory: false,
    allow_guest: true,
    targets: [],
});

const formatDate = (d) =>
    new Date(d).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });

const getPublicUrl = (period) => {
    const baseUrl = window.location.origin;
    return `${baseUrl}/survey/s/${period.slug || period.id}`;
};

const copyUrl = async (period) => {
    try {
        await navigator.clipboard.writeText(getPublicUrl(period));
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 2000);
    } catch (e) {
        alert("Gagal menyalin URL");
    }
};

const reloadData = () => {
    isLoading.value = true;
    router.reload({ onFinish: () => (isLoading.value = false) });
};
const debouncedSearch = debounce(() => applyFilters(), 300);
const applyFilters = () =>
    router.get(
        route("survey.periods.index"),
        { search: search.value, status: statusFilter.value },
        { preserveState: true }
    );
const activate = (p) => {
    if (confirm("Aktifkan periode ini?"))
        router.post(route("survey.periods.activate", p.id));
};
const closePeriod = (p) => {
    if (confirm("Tutup periode ini?"))
        router.post(route("survey.periods.close", p.id));
};
const confirmDelete = (p) => {
    deletingPeriod.value = p;
    showDeleteModal.value = true;
};
const executeDelete = () => {
    if (!deletingPeriod.value) return;
    deleteProcessing.value = true;
    router.delete(route("survey.periods.destroy", deletingPeriod.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingPeriod.value = null;
        },
        onFinish: () => {
            deleteProcessing.value = false;
        },
    });
};

const loadKelasByTa = async () => {
    if (!form.tahun_akademik_id) return;
    loadingKelas.value = true;
    try {
        const res = await axios.get(route("survey.api.data", "kelas"), {
            params: { tahun_akademik_id: form.tahun_akademik_id },
        });
        kelasList.value = res.data.data || [];
    } catch (e) {
        kelasList.value = [];
    }
    loadingKelas.value = false;
};

const selectAllKelas = () => {
    form.targets = kelasList.value.map((k) => k.id);
};

const openModal = (period = null) => {
    editingPeriod.value = period;
    if (period) {
        form.nama = period.nama || "";
        form.tahun_akademik_id = period.tahun_akademik_id;
        form.survey_template_id = period.survey_template_id;
        form.tanggal_mulai =
            period.tanggal_mulai?.split("T")[0] || period.tanggal_mulai;
        form.tanggal_selesai =
            period.tanggal_selesai?.split("T")[0] || period.tanggal_selesai;
        form.status = period.status;
        form.is_mandatory = period.is_mandatory;
        form.allow_guest = period.allow_guest ?? true;
        form.targets = period.targets?.map((t) => t.kelas_matakuliah_id) || [];
        loadKelasByTa();
    } else {
        form.reset();
        form.tahun_akademik_id =
            props.activeTahunAkademikId || props.tahunAkademiks?.[0]?.id;
        form.survey_template_id = props.templates?.[0]?.id;
        form.targets = [];
        loadKelasByTa();
    }
    showModal.value = true;
};

const submitForm = () => {
    // Targets is now just array of kelas IDs (dosen selected during fill)
    if (editingPeriod.value) {
        form.put(route("survey.periods.update", editingPeriod.value.id), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post(route("survey.periods.store"), {
            onSuccess: () => (showModal.value = false),
        });
    }
};

onMounted(() => {
    if (props.kelasMatakuliahs?.length) {
        kelasList.value = props.kelasMatakuliahs;
    }
});
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
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>
