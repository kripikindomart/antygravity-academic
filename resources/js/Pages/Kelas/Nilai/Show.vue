<template>
    <AppLayout>
        <Head title="Input Nilai" />

        <div class="space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
            >
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('dosen.nilai.index')"
                        class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl transition-all"
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
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </Link>
                    <div>
                        <h1
                            class="text-2xl font-bold text-gray-900 dark:text-white"
                        >
                            Input Nilai
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">
                            {{ kelasMatakuliah.kelas?.nama }} -
                            {{ kelasMatakuliah.mata_kuliah?.nama }}
                        </p>
                    </div>
                </div>
                <div
                    v-if="activeTab === 'nilai'"
                    class="flex items-center gap-3"
                >
                    <!-- Mode Toggle -->
                    <div
                        class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 rounded-xl"
                    >
                        <span class="text-xs font-medium text-gray-600"
                            >Mode:</span
                        >
                        <button
                            @click="inputMode = 'komponen'"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition',
                                inputMode === 'komponen'
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-600 hover:bg-gray-200',
                            ]"
                        >
                            Per Komponen
                        </button>
                        <button
                            @click="inputMode = 'langsung'"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition',
                                inputMode === 'langsung'
                                    ? 'bg-purple-600 text-white'
                                    : 'text-gray-600 hover:bg-gray-200',
                            ]"
                        >
                            Nilai Akhir
                        </button>
                    </div>

                    <!-- Bulk Action Button -->
                    <button
                        @click="openBulkModal"
                        class="flex items-center gap-2 px-3 py-1.5 bg-orange-100 hover:bg-orange-200 text-orange-700 rounded-xl transition-colors text-xs font-bold"
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
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                            />
                        </svg>
                        Bulk Input
                    </button>

                    <!-- Import Excel Button -->
                    <button
                        @click="openImportModal"
                        class="flex items-center gap-2 px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 rounded-xl transition-colors text-xs font-bold"
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
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        Import Excel
                    </button>

                    <button
                        @click="submitForm('draft')"
                        :disabled="isSubmitting"
                        class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-all"
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
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"
                            />
                        </svg>
                        Simpan Draft
                    </button>
                    <button
                        @click="submitForm('submit')"
                        :disabled="isSubmitting"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium rounded-xl shadow-lg shadow-green-500/30 transition-all hover:scale-105"
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
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        Submit Final
                    </button>
                </div>
            </div>

            <!-- Skala Nilai Reference Card -->
            <div
                class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-4 border border-indigo-100 dark:border-indigo-800"
            >
                <div class="flex items-center gap-3 mb-3">
                    <svg
                        class="w-5 h-5 text-indigo-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span class="font-bold text-indigo-800 dark:text-indigo-300"
                        >Referensi Skala Nilai</span
                    >
                </div>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="skala in skalaNilais"
                        :key="skala.id"
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700"
                    >
                        <span
                            class="font-black text-lg"
                            :class="getGradeClass(skala.huruf)"
                            >{{ skala.huruf }}</span
                        >
                        <span class="text-xs text-gray-500"
                            >({{ skala.bobot }})</span
                        >
                        <span class="text-xs text-gray-600 dark:text-gray-400"
                            >{{ skala.min_nilai }} - {{ skala.max_nilai }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden"
            >
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                    <button
                        @click="activeTab = 'nilai'"
                        :class="[
                            'flex-1 px-6 py-4 text-sm font-bold flex items-center justify-center gap-2 transition-all',
                            activeTab === 'nilai'
                                ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50/50'
                                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50',
                        ]"
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
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                            />
                        </svg>
                        Input Nilai
                    </button>
                    <button
                        @click="activeTab = 'kehadiran'"
                        :class="[
                            'flex-1 px-6 py-4 text-sm font-bold flex items-center justify-center gap-2 transition-all',
                            activeTab === 'kehadiran'
                                ? 'text-emerald-600 border-b-2 border-emerald-600 bg-emerald-50/50'
                                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50',
                        ]"
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
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        Rekap Kehadiran
                        <span
                            class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 rounded-full text-xs"
                            >{{ totalMeetings }} Pertemuan</span
                        >
                    </button>
                </div>

                <!-- TAB: Input Nilai -->
                <div v-show="activeTab === 'nilai'" class="p-0">
                    <!-- Component Info Tags -->
                    <div
                        class="flex flex-wrap gap-2 p-4 bg-gray-50 dark:bg-gray-800/50 border-b"
                    >
                        <span
                            v-for="comp in komponens"
                            :key="comp.id"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm"
                        >
                            <span
                                class="font-medium text-gray-700 dark:text-gray-300"
                                >{{ comp.nama }}</span
                            >
                            <span
                                class="px-2 py-0.5 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-sm font-bold rounded"
                                >{{ comp.bobot }}%</span
                            >
                        </span>
                        <span
                            v-if="inputMode === 'langsung'"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 dark:bg-purple-900/30 border border-purple-300 rounded-xl"
                        >
                            <svg
                                class="w-4 h-4 text-purple-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>
                            <span class="text-sm font-medium text-purple-700"
                                >Mode Langsung: Input nilai akhir, komponen
                                otomatis terdistribusi</span
                            >
                        </span>
                    </div>

                    <!-- Spreadsheet -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead
                                class="bg-gradient-to-r from-blue-600 to-cyan-500 sticky top-0 z-10"
                            >
                                <tr>
                                    <th class="px-4 py-4 text-center w-10">
                                        <Checkbox v-model:checked="selectAll" />
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider w-12"
                                    >
                                        No
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider min-w-[200px]"
                                    >
                                        Mahasiswa
                                    </th>
                                    <th
                                        class="px-4 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider min-w-[80px] bg-emerald-600/50"
                                    >
                                        <div>Hadir</div>
                                        <div
                                            class="text-[10px] opacity-80 font-normal"
                                        >
                                            %
                                        </div>
                                    </th>

                                    <!-- Mode Langsung: Show Final Grade Input First -->
                                    <th
                                        v-if="inputMode === 'langsung'"
                                        class="px-4 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider min-w-[140px] bg-purple-600"
                                    >
                                        <div>Nilai Akhir</div>
                                        <div
                                            class="text-[10px] opacity-80 font-normal"
                                        >
                                            Input Langsung
                                        </div>
                                    </th>

                                    <!-- Komponen columns (disabled in langsung mode) -->
                                    <th
                                        v-for="comp in komponens"
                                        :key="comp.id"
                                        class="px-4 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider min-w-[120px]"
                                        :class="
                                            inputMode === 'langsung'
                                                ? 'opacity-50'
                                                : ''
                                        "
                                    >
                                        <div>{{ comp.nama }}</div>
                                        <div
                                            class="text-[10px] opacity-80 font-normal"
                                        >
                                            {{ comp.bobot }}%
                                        </div>
                                    </th>

                                    <!-- Mode Komponen: Show Preview -->
                                    <th
                                        v-if="inputMode === 'komponen'"
                                        class="px-4 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider min-w-[120px] bg-blue-700/50"
                                    >
                                        <div>Nilai Akhir</div>
                                        <div
                                            class="text-[10px] opacity-80 font-normal"
                                        >
                                            Preview
                                        </div>
                                    </th>
                                    <th
                                        class="px-4 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider min-w-[100px]"
                                    >
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-gray-800"
                            >
                                <tr
                                    v-for="(mhs, idx) in mahasiswas"
                                    :key="mhs.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                                    :class="{
                                        'bg-blue-50/30 dark:bg-blue-900/10':
                                            selectedStudents.has(mhs.id),
                                    }"
                                >
                                    <td class="px-4 py-3 text-center">
                                        <Checkbox
                                            :checked="
                                                selectedStudents.has(mhs.id)
                                            "
                                            @update:checked="
                                                toggleSelection(mhs.id)
                                            "
                                        />
                                    </td>
                                    <td
                                        class="px-4 py-3 text-center text-sm text-gray-500"
                                    >
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center"
                                            >
                                                <span
                                                    class="text-sm font-bold text-gray-600 dark:text-gray-400"
                                                    >{{
                                                        getInitials(mhs.nama)
                                                    }}</span
                                                >
                                            </div>
                                            <div>
                                                <p
                                                    class="font-medium text-gray-900 dark:text-white"
                                                >
                                                    {{ mhs.nama }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{ mhs.nim }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kehadiran % (Read-only) -->
                                    <td
                                        class="px-4 py-3 text-center bg-emerald-50/50 dark:bg-emerald-900/10"
                                    >
                                        <span
                                            class="text-lg font-bold"
                                            :class="
                                                getAttendanceClass(
                                                    attendanceSummary[mhs.id]
                                                        ?.percent || 0
                                                )
                                            "
                                        >
                                            {{
                                                attendanceSummary[mhs.id]
                                                    ?.percent || 0
                                            }}%
                                        </span>
                                    </td>

                                    <!-- Mode Langsung: Direct Final Grade Input -->
                                    <td
                                        v-if="inputMode === 'langsung'"
                                        class="px-2 py-2 bg-purple-50/50"
                                    >
                                        <input
                                            v-model="directFinalGrades[mhs.id]"
                                            @input="clampDirectGrade(mhs.id)"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            class="w-full px-3 py-2 bg-purple-100 dark:bg-purple-900/30 border-2 border-purple-400 dark:border-purple-600 rounded-lg focus:border-purple-600 focus:ring-0 text-center font-bold text-purple-800 transition-colors"
                                            placeholder="0-100"
                                        />
                                    </td>

                                    <!-- Komponen inputs -->
                                    <td
                                        v-for="comp in komponens"
                                        :key="comp.id"
                                        class="px-2 py-2"
                                        :class="[
                                            inputMode === 'langsung'
                                                ? 'opacity-50'
                                                : '',
                                            comp.source_type === 'kehadiran'
                                                ? 'bg-emerald-50/30'
                                                : '',
                                        ]"
                                    >
                                        <input
                                            v-if="grades[mhs.id]"
                                            v-model="grades[mhs.id][comp.id]"
                                            @input="clampGrade(mhs.id, comp.id)"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            :disabled="
                                                inputMode === 'langsung' ||
                                                comp.source_type === 'kehadiran'
                                            "
                                            :class="
                                                comp.source_type === 'kehadiran'
                                                    ? 'bg-emerald-100 dark:bg-emerald-900/30 border-emerald-300'
                                                    : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700'
                                            "
                                            class="w-full px-3 py-2 border-2 rounded-lg focus:border-primary-500 focus:ring-0 text-center font-medium transition-colors disabled:opacity-70 disabled:cursor-not-allowed"
                                            placeholder="0"
                                        />
                                    </td>

                                    <!-- Mode Komponen: Preview -->
                                    <td
                                        v-if="inputMode === 'komponen'"
                                        class="px-4 py-3 text-center bg-blue-50/50 dark:bg-blue-900/10"
                                    >
                                        <div class="flex flex-col items-center">
                                            <span
                                                class="text-xl font-black text-gray-900 dark:text-white"
                                                >{{
                                                    calcFinal(mhs.id).score
                                                }}</span
                                            >
                                            <span
                                                class="px-2 py-0.5 mt-1 rounded text-sm font-bold"
                                                :class="
                                                    getGradeClass(
                                                        calcFinal(mhs.id).letter
                                                    )
                                                "
                                            >
                                                {{ calcFinal(mhs.id).letter }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <span
                                            v-if="rekaps[mhs.id]"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold capitalize"
                                            :class="{
                                                'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400':
                                                    rekaps[mhs.id].status ===
                                                    'published',
                                                'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400':
                                                    rekaps[mhs.id].status ===
                                                    'submitted',
                                                'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400':
                                                    rekaps[mhs.id].status ===
                                                    'draft',
                                            }"
                                        >
                                            <span
                                                :class="{
                                                    'bg-green-500':
                                                        rekaps[mhs.id]
                                                            .status ===
                                                        'published',
                                                    'bg-blue-500':
                                                        rekaps[mhs.id]
                                                            .status ===
                                                        'submitted',
                                                    'bg-gray-400':
                                                        rekaps[mhs.id]
                                                            .status === 'draft',
                                                }"
                                                class="w-1.5 h-1.5 rounded-full"
                                            ></span>
                                            {{ rekaps[mhs.id].status }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-gray-400 text-xs"
                                            >-</span
                                        >
                                    </td>
                                </tr>
                                <tr v-if="mahasiswas.length === 0">
                                    <td
                                        :colspan="6 + komponens.length"
                                        class="px-6 py-16 text-center"
                                    >
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
                                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                                    />
                                                </svg>
                                            </div>
                                            <p
                                                class="text-gray-500 dark:text-gray-400 font-medium"
                                            >
                                                Tidak ada mahasiswa terdaftar
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB: Rekap Kehadiran -->
                <div v-show="activeTab === 'kehadiran'" class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead
                                class="bg-gradient-to-r from-emerald-600 to-teal-500 sticky top-0 z-10"
                            >
                                <tr>
                                    <th
                                        class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider w-12 sticky left-0 bg-emerald-600"
                                    >
                                        No
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider min-w-[200px] sticky left-12 bg-emerald-600"
                                    >
                                        Mahasiswa
                                    </th>
                                    <th
                                        v-for="pt in attendanceDetail"
                                        :key="pt.id"
                                        class="px-2 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider min-w-[60px]"
                                    >
                                        <div>P{{ pt.pertemuan_ke }}</div>
                                        <div
                                            class="text-[10px] opacity-80 font-normal"
                                        >
                                            {{ formatDate(pt.tanggal) }}
                                        </div>
                                    </th>
                                    <th
                                        class="px-4 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider min-w-[100px] bg-emerald-700/50"
                                    >
                                        <div>Total</div>
                                        <div
                                            class="text-[10px] opacity-80 font-normal"
                                        >
                                            H/I/S/A
                                        </div>
                                    </th>
                                    <th
                                        class="px-4 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider min-w-[80px] bg-emerald-800/50"
                                    >
                                        %
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-gray-800"
                            >
                                <tr
                                    v-for="(mhs, idx) in mahasiswas"
                                    :key="mhs.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                                >
                                    <td
                                        class="px-4 py-3 text-center text-sm text-gray-500 sticky left-0 bg-white dark:bg-gray-900"
                                    >
                                        {{ idx + 1 }}
                                    </td>
                                    <td
                                        class="px-4 py-3 sticky left-12 bg-white dark:bg-gray-900"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center"
                                            >
                                                <span
                                                    class="text-xs font-bold text-gray-600 dark:text-gray-400"
                                                    >{{
                                                        getInitials(mhs.nama)
                                                    }}</span
                                                >
                                            </div>
                                            <div>
                                                <p
                                                    class="font-medium text-gray-900 dark:text-white text-sm"
                                                >
                                                    {{ mhs.nama }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{ mhs.nim }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Status per pertemuan -->
                                    <td
                                        v-for="pt in attendanceDetail"
                                        :key="pt.id"
                                        class="px-1 py-2 text-center"
                                    >
                                        <span
                                            v-if="pt.absensis[mhs.id]"
                                            class="inline-flex w-8 h-8 items-center justify-center rounded-lg text-xs font-bold"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700':
                                                    pt.absensis[mhs.id] ===
                                                    'hadir',
                                                'bg-blue-100 text-blue-700':
                                                    pt.absensis[mhs.id] ===
                                                    'izin',
                                                'bg-amber-100 text-amber-700':
                                                    pt.absensis[mhs.id] ===
                                                    'sakit',
                                                'bg-red-100 text-red-700':
                                                    pt.absensis[mhs.id] ===
                                                    'alpha',
                                            }"
                                        >
                                            {{
                                                getStatusLabel(
                                                    pt.absensis[mhs.id]
                                                )
                                            }}
                                        </span>
                                        <span v-else class="text-gray-300"
                                            >-</span
                                        >
                                    </td>

                                    <td
                                        class="px-4 py-3 text-center bg-emerald-50/50"
                                    >
                                        <div class="text-xs font-medium">
                                            <span class="text-emerald-600">{{
                                                attendanceSummary[mhs.id]
                                                    ?.hadir || 0
                                            }}</span
                                            >/
                                            <span class="text-blue-600">{{
                                                attendanceSummary[mhs.id]
                                                    ?.izin || 0
                                            }}</span
                                            >/
                                            <span class="text-amber-600">{{
                                                attendanceSummary[mhs.id]
                                                    ?.sakit || 0
                                            }}</span
                                            >/
                                            <span class="text-red-600">{{
                                                attendanceSummary[mhs.id]
                                                    ?.alpha || 0
                                            }}</span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-center bg-emerald-100/50"
                                    >
                                        <span
                                            class="text-lg font-black"
                                            :class="
                                                getAttendanceClass(
                                                    attendanceSummary[mhs.id]
                                                        ?.percent || 0
                                                )
                                            "
                                        >
                                            {{
                                                attendanceSummary[mhs.id]
                                                    ?.percent || 0
                                            }}%
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="mahasiswas.length === 0">
                                    <td
                                        :colspan="4 + attendanceDetail.length"
                                        class="px-6 py-16 text-center"
                                    >
                                        <p
                                            class="text-gray-500 dark:text-gray-400 font-medium"
                                        >
                                            Tidak ada data
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Removed closing AppLayout here to include Modal inside -->
        <!-- Bulk Action Modal -->
        <DialogModal :show="showBulkModal" @close="closeBulkModal">
            <template #title> Bulk Input Nilai </template>
            <template #content>
                <div class="space-y-4">
                    <p class="text-sm text-gray-500">
                        Mengisi nilai untuk
                        <strong class="text-indigo-600"
                            >{{ selectedStudents.size }} Mahasiswa
                            Terpilih</strong
                        >. Nilai yang sudah ada akan ditimpa.
                    </p>

                    <div>
                        <InputLabel value="Pilih Komponen" />
                        <select
                            v-model="bulkForm.component_id"
                            class="mt-1 block w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-lg focus:border-primary-500 focus:ring-0 font-medium transition-colors text-gray-700 dark:text-gray-300"
                        >
                            <!-- Options for Mode Komponen -->
                            <template v-if="inputMode === 'komponen'">
                                <option value="all">
                                    -- Semua Komponen (Manual) --
                                </option>
                                <template
                                    v-for="comp in komponens"
                                    :key="comp.id"
                                >
                                    <option
                                        v-if="comp.source_type !== 'kehadiran'"
                                        :value="comp.id"
                                    >
                                        {{ comp.nama }} (Bobot:
                                        {{ comp.bobot }}%)
                                    </option>
                                </template>
                            </template>

                            <!-- Options for Mode Langsung -->
                            <template v-else>
                                <option value="final">
                                    -- Nilai Akhir Langsung --
                                </option>
                            </template>
                        </select>
                        <p
                            v-if="bulkForm.component_id === 'all'"
                            class="text-xs text-orange-600 mt-1"
                        >
                            *Hanya komponen manual yang akan diisi. Komponen
                            kehadiran tidak akan berubah.
                        </p>
                    </div>

                    <div>
                        <InputLabel value="Nilai (0-100)" />
                        <input
                            v-model="bulkForm.value"
                            type="number"
                            min="0"
                            max="100"
                            class="mt-1 block w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-lg focus:border-primary-500 focus:ring-0 font-medium transition-colors text-gray-700 dark:text-gray-300"
                            placeholder="Contoh: 85"
                            @keyup.enter="applyBulkInput"
                        />
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeBulkModal" class="mr-3">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    @click="applyBulkInput"
                    :disabled="!bulkForm.value"
                >
                    Terapkan Nilai
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Import Modal -->
        <DialogModal
            :show="showImportModal"
            @close="closeImportModal"
            maxWidth="4xl"
        >
            <template #title> Import Nilai Excel </template>
            <template #content>
                <!-- Step 1: Upload -->
                <div v-if="importStep === 1" class="space-y-6">
                    <div
                        class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-100 dark:border-blue-800"
                    >
                        <h4
                            class="font-bold text-blue-800 dark:text-blue-300 mb-2"
                        >
                            Instruksi:
                        </h4>
                        <ol
                            class="list-decimal list-inside text-sm text-blue-700 dark:text-blue-400 space-y-1"
                        >
                            <li>
                                Download template Excel melalui tombol di bawah.
                            </li>
                            <li>
                                Isi nilai pada kolom komponen yang tersedia.
                            </li>
                            <li>
                                Biarkan kolom kosong jika tidak ingin mengubah
                                nilai mahasiswa tersebut.
                            </li>
                            <li>Upload file Excel yang sudah diisi.</li>
                        </ol>
                        <div class="mt-4">
                            <a
                                :href="
                                    route(
                                        'kelas.nilai.template',
                                        kelasMatakuliah.id
                                    )
                                "
                                target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 text-sm font-bold rounded-lg border border-blue-200 dark:border-blue-700 shadow-sm hover:bg-blue-50 transition-colors"
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
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                    />
                                </svg>
                                Download Template
                            </a>
                        </div>
                    </div>

                    <!-- Drag & Drop Area -->
                    <div
                        class="border-2 border-dashed rounded-2xl p-10 flex flex-col items-center justify-center text-center transition-colors cursor-pointer relative"
                        :class="[
                            dragActive
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600',
                        ]"
                        @dragenter.prevent="dragActive = true"
                        @dragleave.prevent="dragActive = false"
                        @dragover.prevent
                        @drop.prevent="handleDrop"
                        @click="$refs.fileInput.click()"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            class="hidden"
                            accept=".xlsx, .xls, .csv"
                            @change="handleFileSelect"
                        />
                        <div
                            class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4 text-gray-400"
                        >
                            <svg
                                class="w-8 h-8"
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
                        </div>
                        <p
                            class="text-lg font-bold text-gray-700 dark:text-gray-300"
                        >
                            Drag & Drop file Excel di sini
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            atau klik untuk memilih file
                        </p>
                    </div>
                </div>

                <!-- Step 2: Preview -->
                <div v-else class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3
                            class="font-bold text-lg text-gray-800 dark:text-white"
                        >
                            Preview Data Import
                        </h3>
                        <span class="text-sm text-gray-500"
                            >{{ previewData.length }} Data ditemukan</span
                        >
                    </div>

                    <div
                        class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg max-h-96"
                    >
                        <table class="w-full text-sm">
                            <thead
                                class="bg-gray-50 dark:bg-gray-800 sticky top-0"
                            >
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        NIM
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        Nama
                                    </th>
                                    <th
                                        v-for="comp in manualKomponens"
                                        :key="comp.id"
                                        class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        {{ comp.nama }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-200 dark:divide-gray-700"
                            >
                                <tr
                                    v-for="row in previewData"
                                    :key="row.mahasiswa_id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                >
                                    <td class="px-4 py-2 font-mono text-xs">
                                        {{ row.nim }}
                                    </td>
                                    <td class="px-4 py-2">{{ row.nama }}</td>
                                    <td
                                        v-for="comp in manualKomponens"
                                        :key="comp.id"
                                        class="px-4 py-2 text-center"
                                    >
                                        <span
                                            v-if="
                                                row.grades[comp.id] !==
                                                undefined
                                            "
                                            class="font-bold text-gray-800 dark:text-gray-200"
                                        >
                                            {{ row.grades[comp.id] }}
                                        </span>
                                        <span v-else class="text-gray-400"
                                            >-</span
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeImportModal" class="mr-3">
                    {{ importStep === 1 ? "Batal" : "Kembali" }}
                </SecondaryButton>
                <PrimaryButton
                    v-if="importStep === 2"
                    @click="saveImport"
                    :disabled="isSubmitting"
                    class="bg-emerald-600 hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800"
                >
                    {{ isSubmitting ? "Menyimpan..." : "Simpan Nilai" }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import axios from "axios"; // Ensure axios is imported for file processing
import AppLayout from "@/Components/Layout/AppLayout.vue";
import DialogModal from "@/Components/DialogModal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Checkbox from "@/Components/Checkbox.vue";

const props = defineProps({
    kelasMatakuliah: Object,
    mahasiswas: Array,
    komponens: Array,
    scores: Object,
    rekaps: Object,
    skalaNilais: Array,
    attendanceSummary: Object,
    attendanceDetail: Array,
    totalMeetings: Number,
});

const activeTab = ref("nilai");
const inputMode = ref("komponen"); // 'komponen' or 'langsung'
const grades = ref({});
const directFinalGrades = ref({});
const isSubmitting = ref(false);

// Bulk Action State
const showBulkModal = ref(false);
const bulkForm = ref({
    component_id: "all",
    value: "",
});
const selectedStudents = ref(new Set());

const selectAll = computed({
    get: () =>
        props.mahasiswas.length > 0 &&
        selectedStudents.value.size === props.mahasiswas.length,
    set: (val) => {
        selectedStudents.value = new Set(
            val ? props.mahasiswas.map((m) => m.id) : []
        );
    },
});

const toggleSelection = (id) => {
    if (selectedStudents.value.has(id)) {
        selectedStudents.value.delete(id);
    } else {
        selectedStudents.value.add(id);
    }
};

const openBulkModal = () => {
    if (selectedStudents.value.size === 0) {
        alert("Pilih minimal satu mahasiswa!");
        return;
    }

    if (inputMode.value === "langsung") {
        bulkForm.value.component_id = "final";
    } else {
        // Default to first manual component if available
        const firstManual = props.komponens.find(
            (c) => c.source_type !== "kehadiran"
        );
        bulkForm.value.component_id = firstManual ? firstManual.id : "all";
    }

    bulkForm.value.value = "";
    showBulkModal.value = true;
};

const closeBulkModal = () => {
    showBulkModal.value = false;
};

// Import Logic
const showImportModal = ref(false);
const importStep = ref(1);
const dragActive = ref(false);
const isUploading = ref(false);
const previewData = ref([]);
const previewComponentIds = ref([]); // IDs involved in the import

const manualKomponens = computed(() => {
    return props.komponens.filter((c) => c.source_type !== "kehadiran");
});

const openImportModal = () => {
    importStep.value = 1;
    previewData.value = [];
    showImportModal.value = true;
};

const closeImportModal = () => {
    if (importStep.value === 2) {
        // Confirm close if in preview? nah just simple
        importStep.value = 1;
    }
    showImportModal.value = false;
};

const handleDrop = (e) => {
    dragActive.value = false;
    const file = e.dataTransfer.files[0];
    if (file) handleFile(file);
};

const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) handleFile(file);
    e.target.value = ""; // Reset input
};

const handleFile = async (file) => {
    // Validate type roughly
    if (!file.name.match(/\.(xlsx|xls|csv)$/)) {
        alert("Format file tidak didukung. Gunakan Excel atau CSV.");
        return;
    }

    isUploading.value = true;
    const formData = new FormData();
    formData.append("file", file);

    try {
        const response = await axios.post(
            route("kelas.nilai.import-preview", props.kelasMatakuliah.id),
            formData,
            {
                headers: { "Content-Type": "multipart/form-data" },
            }
        );

        previewData.value = response.data.preview;
        previewComponentIds.value = response.data.component_ids;
        importStep.value = 2;
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.error || "Gagal memproses file.");
    } finally {
        isUploading.value = false;
    }
};

const saveImport = () => {
    isSubmitting.value = true;
    router.post(
        route("kelas.nilai.import-store", props.kelasMatakuliah.id),
        {
            data: previewData.value,
        },
        {
            onSuccess: () => {
                closeImportModal();
                isSubmitting.value = false;
                // Additional notify handled by flash message
            },
            onError: (errors) => {
                alert(
                    "Gagal menyimpan: " +
                        (errors.message || "Terjadi kesalahan")
                );
                isSubmitting.value = false;
            },
        }
    );
};

const applyBulkInput = () => {
    const val = parseFloat(bulkForm.value.value);
    if (isNaN(val) || val < 0 || val > 100) {
        alert("Masukkan nilai valid (0-100)");
        return;
    }

    // Apply logic to SELECTED students only
    props.mahasiswas.forEach((mhs) => {
        if (!selectedStudents.value.has(mhs.id)) return;

        if (!grades.value[mhs.id]) grades.value[mhs.id] = {};

        if (bulkForm.value.component_id === "all") {
            // Apply to all manual components
            props.komponens.forEach((comp) => {
                if (comp.source_type !== "kehadiran") {
                    grades.value[mhs.id][comp.id] = val;
                }
            });
        } else if (bulkForm.value.component_id === "final") {
            // Set direct final grade and distribute
            directFinalGrades.value[mhs.id] = val;
            distributeGrade(mhs.id);
            // distributeGrade updates grades.value, so no need to manual set here
            // But we must ensure specific logic:
            // distributeGrade takes directFinalGrades[mhs.id] and puts it into manual components.
            // So we are done for this student.
            return;
        } else {
            // Apply to specific component
            grades.value[mhs.id][bulkForm.value.component_id] = val;
        }

        // Recalculate totals for direct grade view if needed
        let total = 0;
        props.komponens.forEach((comp) => {
            const v = parseFloat(grades.value[mhs.id][comp.id]) || 0;
            total += (v * parseFloat(comp.bobot)) / 100;
        });
        directFinalGrades.value[mhs.id] = total.toFixed(2);
    });

    closeBulkModal();
};

onMounted(() => {
    // Select all by default as requested
    selectedStudents.value = new Set(props.mahasiswas.map((m) => m.id));

    props.mahasiswas.forEach((mhs) => {
        grades.value[mhs.id] = {};
        let existingTotal = 0;

        props.komponens.forEach((comp) => {
            let val = 0;

            // Check if this component is auto-filled from kehadiran data (based on source_type mapping)
            if (comp.source_type === "kehadiran") {
                // Use attendance percentage as the grade value
                val = props.attendanceSummary[mhs.id]?.percent || 0;
            } else if (props.scores[mhs.id]) {
                // Use existing saved score for manual components
                const found = props.scores[mhs.id].find(
                    (s) => s.komponen_nilai_id === comp.id
                );
                if (found) val = found.nilai;
            }

            grades.value[mhs.id][comp.id] = val;
            existingTotal += (val * parseFloat(comp.bobot)) / 100;
        });

        // Initialize direct final grade from existing scores
        directFinalGrades.value[mhs.id] =
            existingTotal > 0 ? existingTotal.toFixed(2) : "";
    });
});

// Clamp grade value between 0 and 100
const clampGrade = (mhsId, compId) => {
    let val = parseFloat(grades.value[mhsId][compId]) || 0;
    if (val < 0) val = 0;
    if (val > 100) val = 100;
    grades.value[mhsId][compId] = val;
};

// Clamp direct final grade between 0 and 100
const clampDirectGrade = (mhsId) => {
    let val = parseFloat(directFinalGrades.value[mhsId]) || 0;
    if (val < 0) val = 0;
    if (val > 100) val = 100;
    directFinalGrades.value[mhsId] = val;
    distributeGrade(mhsId);
};

// Distribute final grade to components proportionally
const distributeGrade = (mhsId) => {
    let finalVal = parseFloat(directFinalGrades.value[mhsId]) || 0;
    // Clamp the value
    if (finalVal < 0) finalVal = 0;
    if (finalVal > 100) finalVal = 100;

    // ONLY distribute to MANUAL components.
    // Kehadiran component (source_type === 'kehadiran') must stay as is (from attendance data).
    props.komponens.forEach((comp) => {
        if (comp.source_type !== "kehadiran") {
            grades.value[mhsId][comp.id] = finalVal;
        }
    });
};

const getInitials = (name) => {
    if (!name) return "?";
    const parts = name.split(" ");
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const calcFinal = (mhsId) => {
    let total = 0;
    if (!grades.value[mhsId]) return { score: 0, letter: "E" };

    props.komponens.forEach((comp) => {
        const val = parseFloat(grades.value[mhsId][comp.id]) || 0;
        total += (val * parseFloat(comp.bobot)) / 100;
    });

    let letter = "E";
    for (const skala of props.skalaNilais) {
        if (
            total >= parseFloat(skala.min_nilai) &&
            total <= parseFloat(skala.max_nilai)
        ) {
            letter = skala.huruf;
            break;
        }
    }
    return { score: total.toFixed(2), letter };
};

const getGradeClass = (letter) => {
    const map = {
        A: "bg-green-100 text-green-700",
        "A-": "bg-green-100 text-green-600",
        "B+": "bg-blue-100 text-blue-700",
        B: "bg-blue-100 text-blue-600",
        "B-": "bg-blue-100 text-blue-500",
        "C+": "bg-yellow-100 text-yellow-700",
        C: "bg-yellow-100 text-yellow-600",
        D: "bg-orange-100 text-orange-600",
        E: "bg-red-100 text-red-600",
    };
    return map[letter] || "bg-gray-100 text-gray-600";
};

const getAttendanceClass = (percent) => {
    if (percent >= 80) return "text-emerald-600";
    if (percent >= 60) return "text-amber-600";
    return "text-red-600";
};

const getStatusLabel = (status) => {
    const map = { hadir: "H", izin: "I", sakit: "S", alpha: "A" };
    return map[status] || "-";
};

const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    const d = new Date(dateStr);
    return `${d.getDate()}/${d.getMonth() + 1}`;
};

const submitForm = (action) => {
    const gradesArray = [];
    for (const mhsId in grades.value) {
        for (const compId in grades.value[mhsId]) {
            gradesArray.push({
                mahasiswa_id: mhsId,
                komponen_nilai_id: compId,
                nilai: grades.value[mhsId][compId],
            });
        }
    }

    isSubmitting.value = true;
    router.post(
        route("dosen.nilai.store", props.kelasMatakuliah.id),
        {
            grades: gradesArray,
            action: action,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
            },
        }
    );
};
</script>
