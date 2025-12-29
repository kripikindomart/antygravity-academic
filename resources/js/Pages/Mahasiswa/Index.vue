<template>
    <AppLayout>
        <Head title="Manajemen Mahasiswa" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Mahasiswa</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola data mahasiswa dan buat akun pengguna</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="reloadData" class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl transition-all" title="Refresh">
                        <svg class="w-5 h-5" :class="{'animate-spin': isLoading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <button @click="showImportModal = true" class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl transition-all inline-flex items-center gap-2" title="Import Excel">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="hidden sm:inline font-medium">Import</span>
                    </button>
                    <button v-if="activeTab === 'active'" @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all hover:scale-105">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Mahasiswa
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-green-100 dark:bg-green-900/30 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.aktif }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Aktif</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-amber-100 dark:bg-amber-900/30 text-amber-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.cuti }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Cuti</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-purple-100 dark:bg-purple-900/30 text-purple-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.lulus }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Lulus</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-red-100 dark:bg-red-900/30 text-red-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.trashed }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Trash</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-xl p-1 w-fit">
                <button @click="switchTab('active')" :class="['px-4 py-2.5 rounded-lg text-sm font-medium transition-all', activeTab === 'active' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
                        Aktif
                        <span class="px-2 py-0.5 rounded-full text-xs bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400">{{ stats.total }}</span>
                    </span>
                </button>
                <button @click="switchTab('trash')" :class="['px-4 py-2.5 rounded-lg text-sm font-medium transition-all', activeTab === 'trash' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Sampah
                        <span v-if="stats.trashed > 0" class="px-2 py-0.5 rounded-full text-xs bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">{{ stats.trashed }}</span>
                    </span>
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
                <div class="flex flex-col lg:flex-row gap-4 items-center">
                    <div class="w-full lg:w-80">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input v-model="localFilters.search" type="text" placeholder="Cari nama, NIM, email..." class="block w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500/20" @input="debouncedSearch"/>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3 items-center flex-1">
                        <select v-if="prodis.length > 1" v-model="localFilters.prodi_id" @change="applyFilters" class="px-4 py-2 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20">
                            <option value="">Semua Prodi</option>
                            <option v-for="p in prodis" :key="p.id" :value="p.id">{{ p.nama }}</option>
                        </select>
                        <select v-model="localFilters.angkatan" @change="applyFilters" class="px-4 py-2 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20">
                            <option value="">Semua Angkatan</option>
                            <option v-for="a in angkatans" :key="a" :value="a">{{ a }}</option>
                        </select>
                        <select v-model="localFilters.status" @change="applyFilters" class="px-4 py-2 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="cuti">Cuti</option>
                            <option value="lulus">Lulus</option>
                            <option value="keluar">Keluar</option>
                        </select>
                        <select v-model="localFilters.semester_ke" @change="applyFilters" class="px-4 py-2 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 w-32">
                            <option value="">Semua Sem</option>
                            <option v-for="i in 14" :key="i" :value="i">Sem {{ i }}</option>
                        </select>
                        <button @click="resetFilters" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-colors" title="Reset Filter">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <Transition name="slide">
                <div v-if="selectedIds.length > 0" class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl p-4 flex items-center justify-between shadow-lg">
                    <span class="text-white font-medium">{{ selectedIds.length }} mahasiswa dipilih</span>
                    <div class="flex gap-2">
                        <template v-if="activeTab === 'active'">
                            <button @click="bulkCreateAccount" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm font-medium transition-colors backdrop-blur-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                Buat Akun
                            </button>
                            <button @click="showBulkSemesterModal = true" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm font-medium transition-colors backdrop-blur-sm">Set Semester</button>
                            <button @click="bulkDelete" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm font-medium transition-colors backdrop-blur-sm">Hapus Terpilih</button>
                        </template>
                        <template v-else>
                            <button @click="bulkRestore" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition-colors">Pulihkan</button>
                            <button @click="bulkForceDelete" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition-colors">Hapus Permanen</button>
                        </template>
                        <button @click="selectedIds = []" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm font-medium transition-colors backdrop-blur-sm">Batal</button>
                    </div>
                </div>
            </Transition>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-primary-600 to-primary-700">
                            <tr>
                                <th class="px-6 py-4 text-left">
                                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="w-4 h-4 rounded border-white/30 text-white focus:ring-white/50 bg-white/20"/>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">NIM</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Mahasiswa</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Prodi</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Angkatan</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Semester</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Akun</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="mhs in mahasiswas.data" :key="mhs.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <input type="checkbox" :value="mhs.id" v-model="selectedIds" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"/>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm text-gray-900 dark:text-white">{{ mhs.nim }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(mhs.nama || 'M')}&color=7F9CF5&background=EBF4FF`" class="w-10 h-10 rounded-lg object-cover"/>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ mhs.nama }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ mhs.email || '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-600 dark:text-gray-300">{{ mhs.prodi?.nama || '-' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ mhs.angkatan }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-gray-900 dark:text-gray-300" v-if="mhs.semester_ke">Sem. {{ mhs.semester_ke }}</span>
                                    <span class="text-gray-400" v-else>-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold capitalize', getStatusClass(mhs.status)]">
                                        <span :class="['w-1.5 h-1.5 rounded-full', getStatusDot(mhs.status)]"></span>
                                        {{ mhs.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="mhs.user_id" class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Ada
                                    </span>
                                    <button v-else @click="createAccount(mhs)" class="inline-flex items-center gap-1 px-2 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded-full text-xs font-medium hover:bg-primary-200 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                        Buat
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <template v-if="activeTab === 'active'">
                                            <button @click="openModal(mhs)" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button @click="confirmDelete(mhs)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button @click="restoreMhs(mhs.id)" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="Pulihkan">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                            <button @click="forceDeleteMhs(mhs.id)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus Permanen">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!mahasiswas?.data || mahasiswas.data.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400 font-medium">{{ activeTab === 'trash' ? 'Tidak ada data di sampah' : 'Tidak ada mahasiswa ditemukan' }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <!-- Pagination -->
            <div prop="pagination" v-if="mahasiswas?.data && mahasiswas.data.length > 0" class="bg-gray-50 dark:bg-gray-800/50 px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                    <span>Menampilkan {{ mahasiswas.from || 0 }} - {{ mahasiswas.to || 0 }} dari {{ mahasiswas.total || 0 }} mahasiswa</span>
                    <select v-model="localFilters.per_page" @change="applyFilters" class="text-sm border-gray-200 dark:border-gray-700 rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 py-1 pl-2 pr-6">
                        <option :value="10">10 / hal</option>
                        <option :value="15">15 / hal</option>
                        <option :value="25">25 / hal</option>
                        <option :value="50">50 / hal</option>
                        <option :value="100">100 / hal</option>
                    </select>
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, i) in (mahasiswas.links || [])" :key="i">
                        <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', link.active ? 'bg-primary-600 text-white shadow-sm shadow-primary-500/30' : 'text-gray-600 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-700 hover:text-primary-600 dark:hover:text-primary-400']" v-html="link.label"/>
                        <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label"/>
                    </template>
                </div>
            </div>
            </div>
        </div>

        <!-- Modals & Toasts -->
        <Teleport to="body">
            <!-- Import Modal -->
            <Transition name="modal">
                <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showImportModal = false">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden animate-modal-in">
                        <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-5">
                            <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                Import Data Mahasiswa
                            </h2>
                            <p class="text-emerald-100 text-sm mt-1">Upload file Excel data mahasiswa</p>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-800">
                                <div class="flex gap-3">
                                    <div class="shrink-0 w-10 h-10 bg-emerald-100 dark:bg-emerald-800 rounded-lg flex items-center justify-center text-emerald-600 dark:text-emerald-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-emerald-900 dark:text-emerald-100">Panduan Import</h4>
                                        <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-1">Gunakan template Excel yang telah disediakan untuk memastikan format data sesuai.</p>
                                        <a href="/mahasiswa/download-template" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 mt-2 hover:underline">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Download Template
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Program Studi (Opsional)</label>
                                    <select v-model="importProdiId" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="">Deteksi Otomatis / Sesuai Excel</option>
                                        <option v-for="p in prodis" :key="p.id" :value="p.id">{{ p.nama }}</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Jika dipilih, semua mahasiswa akan dimasukkan ke prodi ini.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Semester Masuk (Menentukan Angkatan)</label>
                                    <select v-model="importSemesterMasukId" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="">Deteksi Otomatis / Sesuai Excel</option>
                                        <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.nama }} {{ s.tahun_akademik?.nama }}</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Jika dipilih, angkatan akan diset berdasarkan semester ini.</p>
                                </div>

                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-8 text-center hover:border-emerald-500 dark:hover:border-emerald-500 transition-colors cursor-pointer bg-gray-50 dark:bg-gray-800/50" @click="$refs.fileInput.click()" @dragover.prevent @drop.prevent="handleFileSelect">
                                    <input type="file" ref="fileInput" class="hidden" accept=".xlsx,.xls,.csv" @change="handleFileSelect">
                                    <div v-if="!importFile">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        </div>
                                        <p class="font-medium text-gray-900 dark:text-white">Klik untuk upload atau drag & drop</p>
                                        <p class="text-sm text-gray-500 mt-1">XLSX, XLS, CSV (Max 5MB)</p>
                                    </div>
                                    <div v-else class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                            <div class="text-left">
                                                <p class="font-medium text-gray-900 dark:text-white truncate max-w-[200px]">{{ importFile.name }}</p>
                                                <p class="text-xs text-gray-500">{{ formatFileSize(importFile.size) }}</p>
                                            </div>
                                        </div>
                                        <button @click.stop="removeFile" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full text-gray-400 hover:text-red-500 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                            <button @click="showImportModal = false" class="px-5 py-2.5 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">Batal</button>
                            <button @click="submitImport" :disabled="!importFile || isImporting" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/30 transition-all disabled:opacity-50 flex items-center gap-2">
                                <svg v-if="isImporting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                {{ isImporting ? 'Mengimport...' : 'Import Data' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
            
            <!-- Bulk Semester Modal -->
            <Transition name="modal">
                <div v-if="showBulkSemesterModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showBulkSemesterModal = false">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden animate-modal-in">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Set Semester Masuk Massal</h3>
                            <p class="text-sm text-gray-500 mb-4">Pilih semester masuk untuk {{ selectedIds.length }} mahasiswa terpilih. Angkatan akan disesuaikan otomatis.</p>
                            
                            <select v-model="bulkSemesterId" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white mb-6">
                                <option value="">Pilih Semester</option>
                                <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.nama }} {{ s.tahun_akademik?.nama }}</option>
                            </select>

                            <div class="flex gap-3">
                                <button @click="showBulkSemesterModal = false" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl">Batal</button>
                                <button @click="submitBulkSemester" :disabled="!bulkSemesterId || isProcessing" class="flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl shadow-lg disabled:opacity-50 flex items-center justify-center gap-2">
                                    <svg v-if="isProcessing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    {{ isProcessing ? 'Menyimpan...' : 'Simpan' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Form Modal -->
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showModal = false">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-xl w-full max-h-[90vh] overflow-hidden animate-modal-in">
                        <!-- Gradient Header -->
                        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-white">{{ editingMhs ? 'Edit Mahasiswa' : 'Tambah Mahasiswa Baru' }}</h2>
                                        <p class="text-white/70 text-sm">{{ editingMhs ? 'Perbarui data mahasiswa' : 'Isi data untuk menambah mahasiswa' }}</p>
                                    </div>
                                </div>
                                <button @click="showModal = false" class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Form Body -->
                        <form @submit.prevent="submitForm" class="overflow-y-auto max-h-[calc(90vh-180px)]">
                            <div class="p-6 space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">NIM <span class="text-red-500">*</span></label>
                                        <input v-model="form.nim" type="text" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                        <div v-if="page.props.errors.nim" class="text-red-500 text-xs mt-1">{{ page.props.errors.nim }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                                        <input v-model="form.nama" type="text" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                        <div v-if="page.props.errors.nama" class="text-red-500 text-xs mt-1">{{ page.props.errors.nama }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Program Studi <span class="text-red-500">*</span></label>
                                        <select v-model="form.prodi_id" required :disabled="prodis.length === 1" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                                            <option value="">Pilih Prodi</option>
                                            <option v-for="p in prodis" :key="p.id" :value="p.id">{{ p.nama }}</option>
                                        </select>
                                        <div v-if="page.props.errors.prodi_id" class="text-red-500 text-xs mt-1">{{ page.props.errors.prodi_id }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Semester Masuk <span class="text-red-500">*</span></label>
                                        <select v-model="form.semester_masuk_id" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors">
                                            <option value="">Pilih Semester Masuk</option>
                                            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.nama }} {{ s.tahun_akademik?.nama }}</option>
                                        </select>
                                        <div v-if="page.props.errors.semester_masuk_id" class="text-red-500 text-xs mt-1">{{ page.props.errors.semester_masuk_id }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                                        <input v-model="form.email" type="email" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                        <div v-if="page.props.errors.email" class="text-red-500 text-xs mt-1">{{ page.props.errors.email }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">No. HP</label>
                                        <input v-model="form.no_hp" type="text" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                        <div v-if="page.props.errors.no_hp" class="text-red-500 text-xs mt-1">{{ page.props.errors.no_hp }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jenis Kelamin</label>
                                        <select v-model="form.jenis_kelamin" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors">
                                            <option value="">Pilih</option>
                                            <option value="L">Laki-laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tanggal Lahir</label>
                                        <input v-model="form.tanggal_lahir" type="date" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status <span class="text-red-500">*</span></label>
                                        <select v-model="form.status" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors">
                                            <option value="aktif">Aktif</option>
                                            <option value="cuti">Cuti</option>
                                            <option value="lulus">Lulus</option>
                                            <option value="keluar">Keluar</option>
                                        </select>
                                        <div v-if="page.props.errors.status" class="text-red-500 text-xs mt-1">{{ page.props.errors.status }}</div>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat</label>
                                        <textarea v-model="form.alamat" rows="2" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" @click="showModal = false" class="px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Batal</button>
                                <button type="submit" :disabled="isProcessing" class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all disabled:opacity-50">
                                    <span class="flex items-center gap-2">
                                        <svg v-if="isProcessing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        {{ isProcessing ? 'Menyimpan...' : (editingMhs ? 'Update' : 'Simpan') }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
            
            <!-- Confirm Modal -->
            <Transition name="modal">
                <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showConfirmModal = false">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden animate-modal-in">
                        <div class="p-6 text-center">
                            <div :class="['w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4', confirmState.type === 'danger' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600']">
                                <svg v-if="confirmState.type === 'danger'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ confirmState.title }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6" v-html="confirmState.message"></p>
                            <div class="flex gap-3">
                                <button @click="showConfirmModal = false" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">{{ confirmState.cancelText }}</button>
                                <button @click="handleConfirm" :class="['flex-1 px-4 py-2.5 text-white font-medium rounded-xl shadow-lg transition-all', confirmState.type === 'danger' ? 'bg-red-600 hover:bg-red-700 shadow-red-500/30' : 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/30']">
                                    {{ confirmState.confirmText }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Toasts -->
            <div class="fixed bottom-6 right-6 z-[60] flex flex-col gap-3 pointer-events-none">
                <TransitionGroup enter-active-class="transform ease-out duration-300 transition" enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-for="toast in toasts" :key="toast.id" :class="['pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border border-white/10 backdrop-blur-md min-w-[300px]', toast.type === 'success' ? 'bg-emerald-600/90 text-white' : toast.type === 'error' ? 'bg-red-600/90 text-white' : 'bg-amber-600/90 text-white']">
                        <div class="shrink-0">
                            <svg v-if="toast.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <svg v-else-if="toast.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <p class="font-medium text-sm">{{ toast.message }}</p>
                        <button @click="removeToast(toast.id)" class="ml-auto p-1 rounded-lg hover:bg-white/20 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </TransitionGroup>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const page = usePage();
const props = defineProps({
    mahasiswas: Object,
    prodis: Array,
    semesters: Array,
    angkatans: Array,
    filters: Object,
    stats: Object,
});

// -- State --
const activeTab = ref(props.filters?.tab || 'active');
const localFilters = ref({
    search: props.filters?.search || '',
    prodi_id: props.filters?.prodi_id || '',
    angkatan: props.filters?.angkatan || '',
    angkatan: props.filters?.angkatan || '',
    status: props.filters?.status || '',
    semester_ke: props.filters?.semester_ke || '',
    per_page: props.filters?.per_page || 15,
});

const selectedIds = ref([]);
const selectAll = ref(false);
const showModal = ref(false);
const showImportModal = ref(false);
const showBulkSemesterModal = ref(false);
const showConfirmModal = ref(false);
const editingMhs = ref(null);
const isLoading = ref(false);
const isProcessing = ref(false);
const isImporting = ref(false);
const importFile = ref(null);
const fileInput = ref(null);
const importProdiId = ref('');
const importSemesterMasukId = ref('');
const bulkSemesterId = ref('');

// Toast State
const toasts = ref([]);
const addToast = (message, type = 'success') => {
    const id = Date.now();
    toasts.value.push({ id, message, type });
    setTimeout(() => removeToast(id), 3000);
};
const removeToast = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id);
};

// Confirm Modal State
const confirmState = ref({
    title: '',
    message: '',
    type: 'danger',
    action: null,
    confirmText: 'Ya, Lanjutkan',
    cancelText: 'Batal'
});

const form = ref({
    prodi_id: '',
    nim: '',
    nama: '',
    email: '',
    no_hp: '',
    alamat: '',
    tanggal_lahir: '',
    jenis_kelamin: '',
    semester_masuk_id: '',
    status: 'aktif',
});

// -- Watchers --
watch(() => page.props.flash, (flash) => {
    if (flash.success) addToast(flash.success, 'success');
    if (flash.error) addToast(flash.error, 'error');
    if (flash.warning) addToast(flash.warning, 'warning');
}, { deep: true });

// -- Methods --
let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
};

const resetFilters = () => {
    localFilters.value = {
        search: '',
        prodi_id: props.prodis.length === 1 ? props.prodis[0].id : '',
        angkatan: '',
        status: '',
        semester_ke: '',
        per_page: 15
    };
    applyFilters();
};

const applyFilters = () => {
    router.get('/mahasiswa', { ...localFilters.value, tab: activeTab.value === 'trash' ? 'trash' : undefined }, { preserveState: true, replace: true });
};

const reloadData = () => {
    isLoading.value = true;
    router.reload({ onFinish: () => { isLoading.value = false; } });
};

const switchTab = (tab) => {
    activeTab.value = tab;
    selectedIds.value = [];
    selectAll.value = false;
    router.get('/mahasiswa', { tab: tab === 'trash' ? 'trash' : undefined }, { preserveState: true, replace: true });
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedIds.value = (props.mahasiswas?.data || []).map(m => m.id);
    } else {
        selectedIds.value = [];
    }
};

const getStatusClass = (status) => {
    const classes = {
        aktif: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        cuti: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        lulus: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
        keluar: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getStatusDot = (status) => {
    const dots = { aktif: 'bg-green-500', cuti: 'bg-amber-500', lulus: 'bg-purple-500', keluar: 'bg-red-500' };
    return dots[status] || 'bg-gray-500';
};

// -- CRUD Actions --
const openModal = (mhs = null) => {
    editingMhs.value = mhs;
    if (mhs) {
        form.value = { ...mhs, prodi_id: mhs.prodi_id || '', semester_masuk_id: mhs.semester_masuk_id || '' };
    } else {
        form.value = {
            prodi_id: props.prodis.length === 1 ? props.prodis[0].id : '', 
            nim: '', nama: '', email: '', no_hp: '', alamat: '',
            tanggal_lahir: '', jenis_kelamin: '', semester_masuk_id: '', status: 'aktif',
        };
    }
    showModal.value = true;
};

const submitForm = () => {
    isProcessing.value = true;
    const options = {
        onSuccess: () => { showModal.value = false; },
        onFinish: () => { isProcessing.value = false; }
    };

    if (editingMhs.value) {
        router.put(`/mahasiswa/${editingMhs.value.id}`, form.value, options);
    } else {
        router.post('/mahasiswa', form.value, options);
    }
};

// -- Confirmation Actions --
const openConfirm = ({ title, message, type = 'danger', confirmText = 'Ya, Lanjutkan', action }) => {
    confirmState.value = { title, message, type, confirmText, action, cancelText: 'Batal' };
    showConfirmModal.value = true;
};

const handleConfirm = () => {
    if (confirmState.value.action) {
        confirmState.value.action();
    }
    showConfirmModal.value = false;
};

const confirmDelete = (mhs) => {
    const hasAccount = !!mhs.user_id;
    openConfirm({
        title: 'Hapus Mahasiswa',
        message: `Apakah Anda yakin ingin menghapus <b>${mhs.nama}</b>?` + 
                 (hasAccount ? '<br><span class="text-amber-500 text-sm mt-2 block">Note: Akun pengguna terkait akan dinonaktifkan.</span>' : ''),
        type: 'danger',
        confirmText: 'Hapus',
        action: () => router.delete(`/mahasiswa/${mhs.id}`)
    });
};

const restoreMhs = (id) => {
    router.post(`/mahasiswa/${id}/restore`);
};

const forceDeleteMhs = (id) => {
    openConfirm({
        title: 'Hapus Permanen',
        message: 'Data yang dihapus permanen tidak dapat dikembalikan. <br><span class="text-red-500 font-semibold text-sm mt-2 block">PERINGATAN: Akun pengguna terkait juga akan dihapus permanen!</span>',
        type: 'danger',
        confirmText: 'Hapus Permanen',
        action: () => router.delete(`/mahasiswa/${id}/force-delete`)
    });
};

const createAccount = (mhs) => {
    openConfirm({
        title: 'Buat Akun Pengguna',
        message: `Buat akun login untuk <b>${mhs.nama}</b>? <br>Password default: <b>password123</b>`,
        type: 'info',
        confirmText: 'Buat Akun',
        action: () => router.post(`/mahasiswa/${mhs.id}/create-account`)
    });
};

// -- Bulk Actions --
const bulkDelete = () => {
    openConfirm({
        title: 'Hapus Mahasiswa Terpilih',
        message: `Hapus ${selectedIds.value.length} mahasiswa terpilih? <br><span class="text-amber-500 text-sm">Akun pengguna terkait akan dinonaktifkan.</span>`,
        confirmText: 'Hapus Semua',
        action: () => router.post('/mahasiswa/bulk-destroy', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; } })
    });
};

const bulkRestore = () => {
    router.post('/mahasiswa/bulk-restore', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; } });
};

const bulkForceDelete = () => {
    openConfirm({
        title: 'Hapus Permanen Terpilih',
        message: `Hapus permanen ${selectedIds.value.length} mahasiswa terpilih? <br><span class="text-red-500 font-bold">AKUN PENGGUNA TERKAIT JUGA AKAN DIHAPUS!</span>`,
        confirmText: 'Hapus Permanen',
        action: () => router.post('/mahasiswa/bulk-force-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; } })
    });
};

const bulkCreateAccount = () => {
    openConfirm({
        title: 'Buat Akun Massal',
        message: `Buat akun untuk ${selectedIds.value.length} mahasiswa terpilih? <br>Hanya mahasiswa yang memiliki email dan belum punya akun yang akan diproses.`,
        type: 'info',
        confirmText: 'Proses',
        action: () => router.post('/mahasiswa/bulk-create-account', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; } })
    });
};

const submitBulkSemester = () => {
    isProcessing.value = true;
    router.post('/mahasiswa/bulk-update-semester', {
        ids: selectedIds.value,
        semester_masuk_id: bulkSemesterId.value
    }, {
        onSuccess: () => {
            showBulkSemesterModal.value = false;
            bulkSemesterId.value = '';
            selectedIds.value = [];
            selectAll.value = false;
        },
        onFinish: () => { isProcessing.value = false; }
    });
};

// -- Import Functions --
const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) importFile.value = file;
};

const removeFile = () => {
    importFile.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
};

const submitImport = () => {
    if (!importFile.value) return;
    
    isImporting.value = true;
    const formData = new FormData();
    formData.append('file', importFile.value);
    if (importProdiId.value) formData.append('prodi_id', importProdiId.value);
    if (importSemesterMasukId.value) formData.append('semester_masuk_id', importSemesterMasukId.value);
    
    router.post('/mahasiswa/import', formData, {
        onSuccess: () => {
            showImportModal.value = false;
            importFile.value = null;
            importProdiId.value = '';
            importSemesterMasukId.value = '';
        },
        onFinish: () => { isImporting.value = false; }
    });
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .animate-modal-in, .modal-leave-to .animate-modal-in { transform: scale(0.9) translateY(20px); }
.animate-modal-in { animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes modalIn { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.slide-enter-active, .slide-leave-active { transition: all 0.3s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
