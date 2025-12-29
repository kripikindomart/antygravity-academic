<template>
    <AppLayout>
        <Head title="Manajemen Dosen" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Dosen</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola data dosen dan buat akun pengguna</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="reloadData" class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl transition-all" title="Refresh">
                        <svg class="w-5 h-5" :class="{'animate-spin': isLoading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <button v-if="activeTab === 'active'" @click="showImportModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import
                    </button>
                    <button v-if="activeTab === 'active'" @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all hover:scale-105">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Dosen
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Dosen</p>
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
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.nonaktif }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Non-Aktif</p>
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
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
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
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input v-model="localFilters.search" type="text" placeholder="Cari nama, NIP, NIDN, email..." class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500/20" @input="debouncedSearch"/>
                        </div>
                    </div>
                    <select v-model="localFilters.prodi_id" @change="applyFilters" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Semua Prodi</option>
                        <option v-for="p in prodis" :key="p.id" :value="p.id">{{ p.nama }}</option>
                    </select>
                    <select v-model="localFilters.status" @change="applyFilters" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Non-Aktif</option>
                        <option value="cuti">Cuti</option>
                    </select>
                </div>
            </div>

            <!-- Bulk Actions -->
            <Transition name="slide">
                <div v-if="selectedIds.length > 0" class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl p-4 flex items-center justify-between shadow-lg">
                    <span class="text-white font-medium">{{ selectedIds.length }} dosen dipilih</span>
                    <div class="flex gap-2">
                        <template v-if="activeTab === 'active'">
                            <button @click="bulkCreateAccount" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm font-medium transition-colors backdrop-blur-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                Buat Akun
                            </button>
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
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">NIP / NIDN</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Nama Dosen</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Prodi</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Akun</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="dosen in dosens.data" :key="dosen.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <input type="checkbox" :value="dosen.id" v-model="selectedIds" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"/>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-mono text-gray-900 dark:text-white">{{ dosen.nip || '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ dosen.nidn || '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(dosen.nama || 'D')}&color=7F9CF5&background=EBF4FF`" class="w-10 h-10 rounded-lg object-cover"/>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ dosen.gelar_depan }} {{ dosen.nama }}{{ dosen.gelar_belakang ? ', ' + dosen.gelar_belakang : '' }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ dosen.email || '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-600 dark:text-gray-300">{{ dosen.prodi?.nama || '-' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold', getStatusClass(dosen.status)]">
                                        <span :class="['w-1.5 h-1.5 rounded-full', dosen.status === 'aktif' ? 'bg-green-500' : dosen.status === 'cuti' ? 'bg-amber-500' : 'bg-gray-500']"></span>
                                        {{ dosen.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="dosen.user_id" class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Ada
                                    </span>
                                    <button v-else-if="activeTab !== 'trash'" @click="createAccount(dosen)" class="inline-flex items-center gap-1 px-2 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded-full text-xs font-medium hover:bg-primary-200 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                        Buat
                                    </button>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <template v-if="activeTab === 'active'">
                                            <button @click="openModal(dosen)" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button @click="confirmDelete(dosen)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button @click="restoreDosen(dosen.id)" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="Pulihkan">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                            <button @click="forceDeleteDosen(dosen.id)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus Permanen">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!dosens?.data || dosens.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400 font-medium">{{ activeTab === 'trash' ? 'Tidak ada data di sampah' : 'Tidak ada dosen ditemukan' }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="dosens?.data && dosens.data.length > 0" class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Tampilkan</span>
                        <select v-model="localFilters.per_page" @change="applyFilters" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-700 dark:text-gray-300 focus:ring-primary-500 focus:border-primary-500">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-sm text-gray-500 dark:text-gray-400">dari {{ dosens.total || 0 }} data</span>
                    </div>
                    
                    <div class="flex gap-1 overflow-x-auto max-w-full pb-2 md:pb-0">
                        <template v-for="link in (dosens.links || [])" :key="link.label">
                            <Link v-if="link.url" :href="link.url" :data="{ per_page: localFilters.per_page }" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors whitespace-nowrap', link.active ? 'bg-primary-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800']" v-html="link.label"/>
                            <span v-else class="px-3 py-1.5 text-sm text-gray-400 whitespace-nowrap" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showModal = false">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden animate-modal-in">
                        <!-- Gradient Header -->
                        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-white">{{ editingDosen ? 'Edit Dosen' : 'Tambah Dosen Baru' }}</h2>
                                        <p class="text-white/70 text-sm">{{ editingDosen ? 'Perbarui data dosen' : 'Isi data untuk menambah dosen' }}</p>
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
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                                        <input v-model="form.nama" type="text" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Gelar Depan</label>
                                        <input v-model="form.gelar_depan" type="text" placeholder="Dr., Prof." class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Gelar Belakang</label>
                                        <input v-model="form.gelar_belakang" type="text" placeholder="M.Kom, Ph.D" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">NIP</label>
                                        <input v-model="form.nip" type="text" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">NIDN</label>
                                        <input v-model="form.nidn" type="text" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                                        <input v-model="form.email" type="email" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon</label>
                                        <input v-model="form.telepon" type="text" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Program Studi</label>
                                        <select v-model="form.prodi_id" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors">
                                            <option value="">Pilih Prodi</option>
                                            <option v-for="p in prodis" :key="p.id" :value="p.id">{{ p.nama }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status <span class="text-red-500">*</span></label>
                                        <select v-model="form.status" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors">
                                            <option value="aktif">Aktif</option>
                                            <option value="nonaktif">Non-Aktif</option>
                                            <option value="cuti">Cuti</option>
                                        </select>
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
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jabatan Fungsional</label>
                                        <input v-model="form.jabatan_fungsional" type="text" placeholder="Lektor, Guru Besar" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Pendidikan Terakhir</label>
                                        <select v-model="form.pendidikan_terakhir" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors">
                                            <option value="">Pilih</option>
                                            <option value="S1">S1</option>
                                            <option value="S2">S2</option>
                                            <option value="S3">S3</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bidang Keahlian</label>
                                        <input v-model="form.bidang_keahlian" type="text" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                </div>

                                <!-- Dosen Luar Toggle -->
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                                    <div>
                                        <p class="font-medium text-gray-700 dark:text-gray-300">Dosen Luar Biasa</p>
                                        <p class="text-sm text-gray-500">Tandai jika dosen luar biasa</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.is_dosen_luar" class="sr-only peer"/>
                                        <div class="w-14 h-7 bg-gray-200 peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" @click="showModal = false" class="px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Batal</button>
                                <button type="submit" :disabled="isProcessing" class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all disabled:opacity-50">
                                    <span class="flex items-center gap-2">
                                        <svg v-if="isProcessing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        {{ isProcessing ? 'Menyimpan...' : (editingDosen ? 'Update' : 'Simpan') }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Import Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showImportModal = false">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden animate-modal-in">
                        <!-- Gradient Header -->
                        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-white">Import Dosen</h2>
                                        <p class="text-white/70 text-sm">Upload file Excel untuk import data dosen</p>
                                    </div>
                                </div>
                                <button @click="showImportModal = false" class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Form Body -->
                        <form @submit.prevent="submitImport" class="p-6 space-y-5">
                            <!-- Download Template -->
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <p class="text-sm text-blue-800 dark:text-blue-300">Download template Excel terlebih dahulu untuk memastikan format data sesuai.</p>
                                    <a href="/dosen/download-template" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-800 mt-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Download Template
                                    </a>
                                </div>
                            </div>

                            <!-- Prodi Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Program Studi (Opsional)</label>
                                <select v-model="importProdiId" class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-emerald-500 transition-colors">
                                    <option value="">Deteksi dari File (jika ada) atau Kosongkan</option>
                                    <option v-for="p in prodis" :key="p.id" :value="p.id">{{ p.nama }}</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Jika dipilih, semua dosen yang diimport akan dimasukkan ke prodi ini.</p>
                            </div>

                            <!-- File Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">File Excel <span class="text-red-500">*</span></label>
                                <div 
                                    class="border-2 border-dashed rounded-xl p-8 text-center transition-colors"
                                    :class="importFile ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'"
                                >
                                    <input 
                                        type="file" 
                                        ref="fileInput"
                                        @change="handleFileSelect" 
                                        accept=".xlsx,.xls,.csv" 
                                        class="hidden"
                                    />
                                    <div v-if="importFile" class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-emerald-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ importFile.name }}</p>
                                        <p class="text-sm text-gray-500">{{ formatFileSize(importFile.size) }}</p>
                                        <button type="button" @click="removeFile" class="mt-2 text-sm text-red-600 hover:text-red-800">Hapus file</button>
                                    </div>
                                    <div v-else class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <p class="text-gray-600 dark:text-gray-400">Drag & drop file atau</p>
                                        <button type="button" @click="$refs.fileInput.click()" class="mt-1 text-primary-600 font-semibold hover:text-primary-700">Pilih File</button>
                                        <p class="text-xs text-gray-500 mt-2">Format: .xlsx, .xls, .csv (Max 5MB)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" @click="showImportModal = false" class="px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Batal</button>
                                <button type="submit" :disabled="!importFile || isImporting" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/30 transition-all disabled:opacity-50">
                                    <span class="flex items-center gap-2">
                                        <svg v-if="isImporting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        {{ isImporting ? 'Mengimport...' : 'Import Data' }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
        <!-- Confirm Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showConfirmModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4" @click.self="handleConfirm">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden animate-modal-in border border-gray-100 dark:border-gray-800">
                        <div class="p-6 text-center">
                            <div :class="['w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4', confirmState.type === 'danger' ? 'bg-red-100 text-red-600' : confirmState.type === 'warning' ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600']">
                                <svg v-if="confirmState.type === 'danger'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <svg v-else-if="confirmState.type === 'warning'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ confirmState.title }}</h3>
                            <div class="text-gray-500 dark:text-gray-400 mb-6" v-html="confirmState.message"></div>
                            <div class="flex gap-3 justify-center">
                                <button @click="showConfirmModal = false" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">{{ confirmState.cancelText }}</button>
                                <button @click="handleConfirm" :class="['px-5 py-2.5 text-white font-medium rounded-xl shadow-lg transition-all hover:scale-105', confirmState.type === 'danger' ? 'bg-red-600 hover:bg-red-700 shadow-red-500/30' : confirmState.type === 'warning' ? 'bg-amber-600 hover:bg-amber-700 shadow-amber-500/30' : 'bg-primary-600 hover:bg-primary-700 shadow-primary-500/30']">
                                    {{ confirmState.confirmText }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Toast Notifications -->
        <Teleport to="body">
            <div class="fixed bottom-4 right-4 z-[70] flex flex-col gap-2 pointer-events-none">
                <TransitionGroup name="toast">
                    <div v-for="toast in toasts" :key="toast.id" class="pointer-events-auto flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 pr-5 min-w-[300px]">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0', toast.type === 'success' ? 'bg-green-100 text-green-600' : toast.type === 'error' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600']">
                            <svg v-if="toast.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <svg v-else-if="toast.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h4 :class="['font-semibold text-sm', toast.type === 'success' ? 'text-green-600' : toast.type === 'error' ? 'text-red-600' : 'text-amber-600']">{{ toast.type === 'success' ? 'Berhasil' : toast.type === 'error' ? 'Gagal' : 'Peringatan' }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ toast.message }}</p>
                        </div>
                        <button @click="removeToast(toast.id)" class="ml-auto text-gray-400 hover:text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </TransitionGroup>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const page = usePage();
const props = defineProps({
    dosens: Object,
    prodis: Array,
    filters: Object,
    stats: Object,
});

// -- State --
const activeTab = ref(props.filters?.tab || 'active');
const localFilters = ref({
    search: props.filters?.search || '',
    prodi_id: props.filters?.prodi_id || '',
    status: props.filters?.status || '',
    per_page: props.filters?.per_page || 15,
});

const selectedIds = ref([]);
const selectAll = ref(false);
const showModal = ref(false);
const showImportModal = ref(false);
const showConfirmModal = ref(false);
const editingDosen = ref(null);
const isLoading = ref(false);
const isProcessing = ref(false);
const isImporting = ref(false);
const importFile = ref(null);
const fileInput = ref(null);
const importProdiId = ref('');

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
    type: 'danger', // danger, warning, info
    action: null,
    confirmText: 'Ya, Lanjutkan',
    cancelText: 'Batal'
});

const form = ref({
    prodi_id: '',
    nip: '',
    nidn: '',
    nama: '',
    gelar_depan: '',
    gelar_belakang: '',
    email: '',
    telepon: '',
    jenis_kelamin: '',
    jabatan_fungsional: '',
    pendidikan_terakhir: '',
    bidang_keahlian: '',
    is_dosen_luar: false,
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

const applyFilters = () => {
    router.get('/dosen', { ...localFilters.value, tab: activeTab.value === 'trash' ? 'trash' : undefined }, { preserveState: true, replace: true });
};

const reloadData = () => {
    isLoading.value = true;
    router.reload({ onFinish: () => { isLoading.value = false; } });
};

const switchTab = (tab) => {
    activeTab.value = tab;
    selectedIds.value = [];
    selectAll.value = false;
    router.get('/dosen', { tab: tab === 'trash' ? 'trash' : undefined }, { preserveState: true, replace: true });
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedIds.value = (props.dosens?.data || []).map(d => d.id);
    } else {
        selectedIds.value = [];
    }
};

const getStatusClass = (status) => {
    const classes = {
        aktif: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        cuti: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        nonaktif: 'bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

// -- CRUD Actions --
const openModal = (dosen = null) => {
    editingDosen.value = dosen;
    if (dosen) {
        form.value = { ...dosen, prodi_id: dosen.prodi_id || '' };
    } else {
        form.value = {
            prodi_id: '', nip: '', nidn: '', nama: '', gelar_depan: '', gelar_belakang: '',
            email: '', telepon: '', jenis_kelamin: '', jabatan_fungsional: '', pendidikan_terakhir: '',
            bidang_keahlian: '', is_dosen_luar: false, status: 'aktif',
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
    
    if (editingDosen.value) {
        router.put(`/dosen/${editingDosen.value.id}`, form.value, options);
    } else {
        router.post('/dosen', form.value, options);
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

const confirmDelete = (dosen) => {
    const hasAccount = !!dosen.user_id;
    openConfirm({
        title: 'Hapus Dosen',
        message: `Apakah Anda yakin ingin menghapus <b>${dosen.nama}</b>?` + 
                 (hasAccount ? '<br><span class="text-amber-500 text-sm mt-2 block">Note: Akun pengguna terkait akan dinonaktifkan.</span>' : ''),
        type: 'danger',
        confirmText: 'Hapus',
        action: () => router.delete(`/dosen/${dosen.id}`)
    });
};

const restoreDosen = (id) => {
    router.post(`/dosen/${id}/restore`);
};

const forceDeleteDosen = (id) => {
    openConfirm({
        title: 'Hapus Permanen',
        message: 'Data yang dihapus permanen tidak dapat dikembalikan. <br><span class="text-red-500 font-semibold text-sm mt-2 block">PERINGATAN: Akun pengguna terkait juga akan dihapus permanen!</span>',
        type: 'danger',
        confirmText: 'Hapus Permanen',
        action: () => router.delete(`/dosen/${id}/force-delete`)
    });
};

const createAccount = (dosen) => {
    openConfirm({
        title: 'Buat Akun Pengguna',
        message: `Buat akun login untuk <b>${dosen.nama}</b>? <br>Password default: <b>password123</b>`,
        type: 'info',
        confirmText: 'Buat Akun',
        action: () => router.post(`/dosen/${dosen.id}/create-account`)
    });
};

// -- Bulk Actions --
const bulkDelete = () => {
    openConfirm({
        title: 'Hapus Dosen Terpilih',
        message: `Hapus ${selectedIds.value.length} dosen terpilih? <br><span class="text-amber-500 text-sm">Akun pengguna terkait akan dinonaktifkan.</span>`,
        confirmText: 'Hapus Semua',
        action: () => router.post('/dosen/bulk-destroy', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; } })
    });
};

const bulkRestore = () => {
    router.post('/dosen/bulk-restore', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; } });
};

const bulkForceDelete = () => {
    openConfirm({
        title: 'Hapus Permanen Terpilih',
        message: `Hapus permanen ${selectedIds.value.length} dosen terpilih? <br><span class="text-red-500 font-bold">AKUN PENGGUNA TERKAIT JUGA AKAN DIHAPUS!</span>`,
        confirmText: 'Hapus Permanen',
        action: () => router.post('/dosen/bulk-force-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; } })
    });
};

const bulkCreateAccount = () => {
    openConfirm({
        title: 'Buat Akun Massal',
        message: `Buat akun untuk ${selectedIds.value.length} dosen terpilih? <br>Hanya dosen yang memiliki email dan belum punya akun yang akan diproses.`,
        type: 'info',
        confirmText: 'Proses',
        action: () => router.post('/dosen/bulk-create-account', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; } })
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
    
    router.post('/dosen/import', formData, {
        onSuccess: () => {
            showImportModal.value = false;
            importFile.value = null;
            importProdiId.value = '';
        },
        onFinish: () => { isImporting.value = false; }
    });
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .animate-modal-in { transform: scale(0.95); opacity: 0; }
.animate-modal-in { animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes modalIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(30px); }

.slide-enter-active, .slide-leave-active { transition: all 0.3s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
