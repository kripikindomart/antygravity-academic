<template>
    <AppLayout>
        <Head title="Mata Kuliah" />
        
        <div class="py-8">
            <!-- Tabs -->
            <div class="flex items-center gap-1 mb-6 border-b border-gray-200 dark:border-gray-700">
                <button @click="switchTab('active')" :class="['px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px', currentTab === 'active' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']">
                    Aktif
                </button>
                <button @click="switchTab('trash')" :class="['px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px flex items-center gap-2', currentTab === 'trash' ? 'border-red-500 text-red-600 dark:text-red-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Sampah
                </button>
            </div>

            <!-- Filters & Search -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mata Kuliah</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola data mata kuliah dan SKS</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/master/mata-kuliah/template" class="p-2.5 bg-green-100 dark:bg-green-900/30 hover:bg-green-200 text-green-600 dark:text-green-400 rounded-xl transition-all flex items-center gap-2" title="Download Template">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Template
                    </a>
                    <button @click="showImportModal = true" class="px-4 py-2.5 bg-amber-100 dark:bg-amber-900/30 hover:bg-amber-200 text-amber-600 dark:text-amber-400 rounded-xl transition-all flex items-center gap-2" title="Import Excel">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import
                    </button>
                    <button @click="reloadData" class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl transition-all" title="Refresh">
                        <svg class="w-5 h-5" :class="{'animate-spin': isLoading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <button @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all hover:scale-105">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Mata Kuliah
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input v-model="localFilters.search" type="text" placeholder="Cari nama atau kode MK..." class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500/20" @input="debouncedSearch"/>
                    </div>
                    <select v-model="localFilters.prodi_id" @change="applyFilters" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 max-w-xs">
                        <option value="">Semua Program Studi</option>
                        <option v-for="prodi in prodis" :key="prodi.id" :value="prodi.id">{{ prodi.nama }} ({{ prodi.jenjang }})</option>
                    </select>
                    <select v-model="localFilters.semester" @change="applyFilters" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Semua Semester</option>
                        <option v-for="i in 8" :key="i" :value="i">Semester {{ i }}</option>
                    </select>
                </div>
            </div>

            <!-- Bulk Action Toolbar -->
            <Transition name="slide">
                <div v-if="selectedIds.length > 0" class="bg-gradient-to-r from-red-500 to-pink-500 rounded-2xl p-4 flex items-center justify-between text-white shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center font-bold">{{ selectedIds.length }}</div>
                        <span class="font-medium">Item dipilih</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="selectedIds = []; selectAll = false" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl font-medium transition">Batal</button>
                        
                        <template v-if="currentTab === 'active'">
                            <button @click="bulkDelete(false)" class="px-4 py-2 bg-white text-red-600 hover:bg-white/90 rounded-xl font-semibold transition flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus Terpilih
                            </button>
                        </template>
                        <template v-else>
                            <button @click="bulkRestore" class="px-4 py-2 bg-white text-green-600 hover:bg-white/90 rounded-xl font-semibold transition flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Pulihkan
                            </button>
                            <button @click="bulkDelete(true)" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl font-medium transition flex items-center gap-2 border border-white/40">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus Permanen
                            </button>
                        </template>
                    </div>
                </div>
            </Transition>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-primary-600 to-primary-700">
                            <tr>
                                <th class="px-4 py-4 text-left">
                                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="w-4 h-4 rounded border-white/30 text-white focus:ring-0"/>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Kode</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Nama Mata Kuliah</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">SKS</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Smt</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Prodi</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase">Jenis</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-white uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="mk in (mataKuliahs?.data || [])" :key="mk.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-4 py-4">
                                    <input type="checkbox" :value="mk.id" v-model="selectedIds" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"/>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-2 py-1 rounded">{{ mk.kode }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ mk.nama }}</div>
                                    <div v-if="mk.nama_en" class="text-xs text-gray-500 italic">{{ mk.nama_en }}</div>
                                    <div v-if="mk.prasyarat" class="mt-1 flex items-center gap-1">
                                        <span class="text-[10px] text-gray-400 uppercase tracking-wider">Syarat:</span>
                                        <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded">{{ mk.prasyarat.kode }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ mk.total_sks }}</span>
                                        <span class="text-xs text-gray-400">(T:{{ mk.sks_teori }} P:{{ mk.sks_praktik }})</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                    {{ mk.semester }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ mk.prodi?.nama }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full capitalize', mk.jenis === 'wajib' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400']">{{ mk.jenis }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <template v-if="currentTab === 'active'">
                                            <button @click="openModal(mk)" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button @click="confirmDelete(mk)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button @click="restoreItem(mk)" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="Pulihkan">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                            <button @click="confirmForceDelete(mk)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus Permanen">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!mataKuliahs?.data?.length">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium">Belum ada mata kuliah</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div v-if="mataKuliahs?.data?.length" class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm text-gray-500">Menampilkan {{ mataKuliahs.from || 0 }} - {{ mataKuliahs.to || 0 }} dari {{ mataKuliahs.total || 0 }}</p>
                    <div class="flex gap-1">
                        <template v-for="link in (mataKuliahs.links || [])" :key="link.label">
                            <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', link.active ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 dark:text-gray-400']" v-html="link.label"/>
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
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden animate-modal-in max-h-[90vh] flex flex-col">
                        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-white">{{ editingItem ? 'Edit' : 'Tambah' }} Mata Kuliah</h2>
                                        <p class="text-white/70 text-sm">Masuk sebagai data master kurikulum</p>
                                    </div>
                                </div>
                                <button @click="showModal = false" class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="submitForm" class="p-6 space-y-5 overflow-y-auto flex-1">
                            <!-- Prodi Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Program Studi <span class="text-red-500">*</span></label>
                                <select v-model="form.prodi_id" required :disabled="isProdiLocked && !editingItem" :class="['block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-gray-900 dark:text-white', isProdiLocked && !editingItem ? 'cursor-not-allowed opacity-70' : '']">
                                    <option value="" disabled>Pilih Program Studi</option>
                                    <option v-for="prodi in prodis" :key="prodi.id" :value="prodi.id">{{ prodi.nama }} ({{ prodi.jenjang?.toUpperCase() }})</option>
                                </select>
                                <p v-if="isProdiLocked && !editingItem" class="text-xs text-gray-500 mt-1">* Prodi otomatis sesuai akun Anda</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode MK <span class="text-red-500">*</span></label>
                                    <input v-model="form.kode" type="text" required placeholder="MK001" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500"/>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Mata Kuliah (Indonesia) <span class="text-red-500">*</span></label>
                                    <input v-model="form.nama" type="text" required placeholder="Metodologi Penelitian" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500"/>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Inggris (Optional)</label>
                                <input v-model="form.nama_en" type="text" placeholder="Research Methodology" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 italic"/>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">SKS Teori <span class="text-red-500">*</span></label>
                                    <input v-model.number="form.sks_teori" type="number" min="0" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-center"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">SKS Praktik <span class="text-red-500">*</span></label>
                                    <input v-model.number="form.sks_praktik" type="number" min="0" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-center"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Semester <span class="text-red-500">*</span></label>
                                    <select v-model="form.semester" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-center">
                                        <option v-for="i in 8" :key="i" :value="i">{{ i }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jenis <span class="text-red-500">*</span></label>
                                    <select v-model="form.jenis" required class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 text-center capitalize">
                                        <option value="wajib">Wajib</option>
                                        <option value="pilihan">Pilihan</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Detailed info or Prerequisite -->
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Mata Kuliah Prasyarat (Optional)</label>
                                <select v-model="form.prasyarat_id" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500">
                                    <option :value="null">Tidak ada prasyarat</option>
                                    <template v-for="mk in availablePrasyarat" :key="mk.id">
                                        <option v-if="mk.id !== form.id && (!editingItem || mk.id !== editingItem.id)" :value="mk.id">
                                            {{ mk.kode }} - {{ mk.nama }}
                                        </option>
                                    </template>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Hanya menampilkan mata kuliah dalam prodi yang sama.</p>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" @click="showModal = false" class="px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Batal</button>
                                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-medium rounded-xl disabled:opacity-50 hover:shadow-lg hover:shadow-primary-500/30 transition-all">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
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
                <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="showImportModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-lg w-full animate-modal-in overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-white">Import Mata Kuliah</h2>
                                    <p class="text-white/70 text-sm">Upload file Excel (.xlsx)</p>
                                </div>
                            </div>
                        </div>
                        <form @submit.prevent="submitImport" class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Program Studi <span class="text-red-500">*</span></label>
                                <select v-model="importProdiId" required :disabled="isProdiLocked" :class="['block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl', isProdiLocked ? 'cursor-not-allowed opacity-70' : '']">
                                    <option value="" disabled>Pilih Program Studi</option>
                                    <option v-for="prodi in prodis" :key="prodi.id" :value="prodi.id">{{ prodi.nama }} ({{ prodi.jenjang?.toUpperCase() }})</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">File Excel <span class="text-red-500">*</span></label>
                                <input ref="fileInput" type="file" @change="onFileChange" accept=".xlsx,.xls" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-600 hover:file:bg-amber-100 dark:file:bg-amber-900/30 dark:file:text-amber-400"/>
                                <p class="text-xs text-gray-500 mt-1">Download template terlebih dahulu untuk format yang benar</p>
                            </div>
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" @click="showImportModal = false" class="px-5 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-100">Batal</button>
                                <button type="submit" :disabled="isImporting" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-medium rounded-xl disabled:opacity-50 hover:shadow-lg">
                                    {{ isImporting ? 'Mengimport...' : 'Import' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Bulk Delete Modal -->
        <Teleport to="body">
            <div v-if="showBulkDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="showBulkDeleteModal = false"></div>
                <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 animate-modal-in">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ isBulkForceDelete ? `Hapus Permanen ${selectedIds.length} Item?` : `Hapus ${selectedIds.length} Mata Kuliah?` }}</h3>
                        <p class="text-gray-500 mb-6" v-if="isBulkForceDelete">Tindakan ini tidak dapat dibatalkan. Semua data terkait akan hilang permanen.</p>
                        <p class="text-gray-500 mb-6" v-else>Data akan dipindahkan ke folder sampah dan dapat dipulihkan nanti.</p>
                        <div class="flex gap-3 justify-center">
                            <button @click="showBulkDeleteModal = false" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700">Batal</button>
                            <button @click="executeBulkDelete" class="px-5 py-2.5 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 shadow-lg shadow-red-500/30">Hapus Semua</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 animate-modal-in">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ isForceDelete ? 'Hapus Permanen?' : 'Hapus Mata Kuliah?' }}</h3>
                        <p class="text-gray-500 mb-6" v-if="isForceDelete">Yakin ingin menghapus permanen <strong>{{ itemToDelete?.nama }}</strong>?<br>Data yang dihapus tidak dapat dikembalikan.</p>
                        <p class="text-gray-500 mb-6" v-else>Yakin ingin menghapus <strong>{{ itemToDelete?.nama }}</strong>?<br>Data akan dipindahkan ke folder sampah.</p>
                        <div class="flex gap-3 justify-center">
                            <button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700">Batal</button>
                            <button @click="executeDelete" class="px-5 py-2.5 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 shadow-lg shadow-red-500/30">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Toast Notification -->
        <Teleport to="body">
            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="toast.show" class="fixed bottom-4 right-4 z-[60] max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden flex items-center p-4 gap-3 border-l-4" :class="toast.type === 'error' ? 'border-red-500' : 'border-green-500'">
                    <div class="flex-shrink-0">
                        <svg v-if="toast.type === 'success'" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ toast.message }}</p>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import axios from 'axios'; // Import Axios

const props = defineProps({
    mataKuliahs: Object,
    prodis: Array,
    allMataKuliahs: Array,
    filters: Object,
    userProdiId: [Number, String],
});

const isProdiLocked = !!props.userProdiId;



const localFilters = ref({
    search: props.filters?.search || '',
    prodi_id: props.filters?.prodi_id || '',
    semester: props.filters?.semester || '',
    trash: props.filters?.trash || false,
});

const currentTab = ref(props.filters?.trash ? 'trash' : 'active');

const switchTab = (tab) => {
    currentTab.value = tab;
    localFilters.value.trash = tab === 'trash';
    applyFilters();
};

const isLoading = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const showImportModal = ref(false);
const showBulkDeleteModal = ref(false);
const editingItem = ref(null);
const itemToDelete = ref(null);
const selectedIds = ref([]);
const selectAll = ref(false);

const isForceDelete = ref(false);
const isBulkForceDelete = ref(false);

// Toggle select all
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedIds.value = (props.mataKuliahs?.data || []).map(mk => mk.id);
    } else {
        selectedIds.value = [];
    }
};

// Bulk delete - open modal
const bulkDelete = (force = false) => {
    if (selectedIds.value.length === 0) return;
    isBulkForceDelete.value = force;
    showBulkDeleteModal.value = true;
};

// Execute bulk delete
const executeBulkDelete = () => {
    router.post('/master/mata-kuliah/bulk-delete', { 
        ids: selectedIds.value,
        force: isBulkForceDelete.value 
    }, {
        onSuccess: () => {
            selectedIds.value = [];
            selectAll.value = false;
            showBulkDeleteModal.value = false;
        }
    });
};

const bulkRestore = () => {
    if (selectedIds.value.length === 0) return;
    if (!confirm(`Pulihkan ${selectedIds.value.length} mata kuliah?`)) return;
    
    router.post('/master/mata-kuliah/bulk-restore', { ids: selectedIds.value }, {
        onSuccess: () => {
            selectedIds.value = [];
            selectAll.value = false;
        }
    });
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    isForceDelete.value = false;
    showDeleteModal.value = true;
};

const confirmForceDelete = (item) => {
    itemToDelete.value = item;
    isForceDelete.value = true;
    showDeleteModal.value = true;
};

const restoreItem = (item) => {
    if (confirm(`Pulihkan mata kuliah ${item.nama}?`)) {
        router.post(`/master/mata-kuliah/${item.id}/restore`);
    }
};

const executeDelete = () => {
    const url = isForceDelete.value 
        ? `/master/mata-kuliah/${itemToDelete.value.id}/force-delete`
        : `/master/mata-kuliah/${itemToDelete.value.id}`;
    
    router.delete(url, { 
        onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; } 
    });
};

const form = useForm({
    id: null,
    prodi_id: '',
    kode: '',
    nama: '',
    nama_en: '',
    sks_teori: 2,
    sks_praktik: 0,
    semester: 1,
    jenis: 'wajib',
    prasyarat_id: null,
    deskripsi: '',
    is_active: true,
});

// Computed available prerequisites based on selected prodi
const availablePrasyarat = computed(() => {
    if (!form.prodi_id) return [];
    return props.allMataKuliahs.filter(mk => mk.prodi_id == form.prodi_id);
});

let searchTimeout = null;

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
};

const applyFilters = () => {
    // Clean filters: remove null, empty strings, and false values
    const params = Object.keys(localFilters.value).reduce((acc, key) => {
        const value = localFilters.value[key];
        if (value !== null && value !== '' && value !== false) {
            acc[key] = value;
        }
        return acc;
    }, {});
    
    router.get('/master/mata-kuliah', params, { preserveState: true, replace: true });
};

const reloadData = () => {
    isLoading.value = true;
    router.reload({ onFinish: () => { isLoading.value = false; } });
};

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        form.id = item.id;
        form.prodi_id = item.prodi_id;
        form.kode = item.kode;
        form.nama = item.nama;
        form.nama_en = item.nama_en || '';
        form.sks_teori = item.sks_teori;
        form.sks_praktik = item.sks_praktik;
        form.semester = item.semester;
        form.jenis = item.jenis;
        form.prasyarat_id = item.prasyarat_id;
        form.is_active = !!item.is_active;
    } else {
        form.reset();
        form.id = null;
        form.sks_teori = 2;
        form.sks_praktik = 0;
        form.semester = 1;
        form.jenis = 'wajib';
        // Auto-set prodi for bound users
        if (isProdiLocked) {
            form.prodi_id = props.userProdiId;
        }
    }
    showModal.value = true;
};

const submitForm = () => {
    if (editingItem.value) {
        form.put(`/master/mata-kuliah/${editingItem.value.id}`, { onSuccess: () => { showModal.value = false; form.reset(); } });
    } else {
        form.post('/master/mata-kuliah', { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};



// Toast Logic
const toast = ref({ show: false, message: '', type: 'success' });
const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => toast.value.show = false, 3000);
};

// Import
const importProdiId = ref(props.userProdiId || '');
const importFile = ref(null);
const isImporting = ref(false);
const fileInput = ref(null);

const onFileChange = (e) => {
    importFile.value = e.target.files[0];
};

const submitImport = async () => {
    if (!importFile.value || !importProdiId.value) return;
    
    isImporting.value = true;
    
    const formData = new FormData();
    formData.append('file', importFile.value);
    formData.append('prodi_id', importProdiId.value);
    
    try {
        // Use Axios directly to bypass Inertia state handling issues with file uploads
        const response = await axios.post('/master/mata-kuliah/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        showImportModal.value = false;
        importFile.value = null;
        if (fileInput.value) fileInput.value.value = '';
        
        // Reload data
        router.reload({
            onSuccess: () => {
                showToast(response.data.message || 'Import berhasil!', 'success');
            }
        });

    } catch (error) {
        console.error(error);
        showToast('Gagal import: ' + (error.response?.data?.message || error.message), 'error');
    } finally {
        isImporting.value = false;
    }
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.animate-modal-in { animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes modalIn { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.slide-enter-active, .slide-leave-active { transition: all 0.3s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
