<template>
    <AppLayout>
        <Head title="Manajemen User" />
        
        <div class="space-y-6">
            <!-- Header with Stats -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen User</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola pengguna sistem dan hak akses</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="reloadData" class="p-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl transition-all" title="Refresh Data">
                        <svg class="w-5 h-5" :class="{'animate-spin': isLoading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <button @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all hover:scale-105 hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah User
                    </button>
                </div>
            </div>

            <!-- Role Stats Cards -->
            <div v-if="roleStats && Object.keys(roleStats).length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="(count, role) in roleStats" :key="role" class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', getRoleIconClass(role)]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count || 0 }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ (role || '').replace('_', ' ') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-xl p-1 w-fit">
                <button @click="switchTab('active')" :class="['px-4 py-2.5 rounded-lg text-sm font-medium transition-all', activeTab === 'active' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Aktif
                        <span class="px-2 py-0.5 rounded-full text-xs bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400">{{ counts?.active || 0 }}</span>
                    </span>
                </button>
                <button @click="switchTab('trash')" :class="['px-4 py-2.5 rounded-lg text-sm font-medium transition-all', activeTab === 'trash' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Sampah
                        <span v-if="(counts?.trash || 0) > 0" class="px-2 py-0.5 rounded-full text-xs bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">{{ counts?.trash || 0 }}</span>
                    </span>
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input v-model="localFilters.search" type="text" placeholder="Cari nama, email..." class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500/20" @input="debouncedSearch"/>
                        </div>
                    </div>
                    <select v-model="localFilters.role" @change="applyFilters" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Semua Role</option>
                        <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                    </select>
                    <select v-if="activeTab === 'active'" v-model="localFilters.status" @change="applyFilters" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-0 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Non-aktif</option>
                    </select>
                </div>
            </div>

            <!-- Bulk Actions -->
            <Transition name="slide">
                <div v-if="selectedIds.length > 0" class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl p-4 flex items-center justify-between shadow-lg">
                    <span class="text-white font-medium">{{ selectedIds.length }} user dipilih</span>
                    <div class="flex gap-2">
                        <template v-if="activeTab === 'active'">
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
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">User</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Role</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Telepon</th>
                                <th v-if="activeTab === 'active'" class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Status</th>
                                <th v-else class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Dihapus</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="user in (users?.data || [])" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <input type="checkbox" :value="user.id" v-model="selectedIds" :disabled="!canDelete(user)" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 disabled:opacity-50"/>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img :src="user.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'User')}&color=7F9CF5&background=EBF4FF`" :alt="user.name" class="w-10 h-10 rounded-lg object-cover"/>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium text-gray-900 dark:text-white">{{ user.name || '-' }}</p>
                                                <span v-if="user.id === currentUserId" class="px-1.5 py-0.5 text-[10px] font-bold bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded">ANDA</span>
                                            </div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ user.email || '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="role in (user.roles || [])" :key="role.id" class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold capitalize" :class="getRoleBadgeClass(role.name)">{{ role.name || '-' }}</span>
                                        <span v-if="!user.roles || user.roles.length === 0" class="text-gray-400">-</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-600 dark:text-gray-300">{{ user.phone || '-' }}</span>
                                </td>
                                <td v-if="activeTab === 'active'" class="px-6 py-4">
                                    <button @click="toggleStatus(user)" :disabled="!canDelete(user)" :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold transition-colors', user.is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400', !canDelete(user) && 'opacity-50 cursor-not-allowed']">
                                        <span :class="['w-1.5 h-1.5 rounded-full', user.is_active ? 'bg-green-500' : 'bg-red-500']"></span>
                                        {{ user.is_active ? 'Aktif' : 'Non-aktif' }}
                                    </button>
                                </td>
                                <td v-else class="px-6 py-4">
                                    <span class="text-sm text-gray-500">{{ formatDate(user.deleted_at) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <template v-if="activeTab === 'active'">
                                            <button @click="openModal(user)" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button v-if="canDelete(user)" @click="confirmDelete(user)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                            <span v-else class="p-2 text-gray-300 dark:text-gray-600" title="Tidak dapat dihapus">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            </span>
                                        </template>
                                        <template v-else>
                                            <button @click="restoreUser(user)" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="Pulihkan">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                            <button @click="confirmForceDelete(user)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Hapus Permanen">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!users?.data || users.data.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                            </svg>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400 font-medium">{{ activeTab === 'trash' ? 'Tidak ada data di sampah' : 'Tidak ada user ditemukan' }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users?.data && users.data.length > 0" class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Menampilkan {{ users.from || 0 }} - {{ users.to || 0 }} dari {{ users.total || 0 }} user</p>
                    <div class="flex gap-1">
                        <template v-for="link in (users.links || [])" :key="link.label">
                            <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', link.active ? 'bg-primary-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800']" v-html="link.label"/>
                            <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modern User Form Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showModal = false">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-xl w-full max-h-[90vh] overflow-hidden animate-modal-in">
                        <!-- Gradient Header -->
                        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-white">{{ editingUser ? 'Edit User' : 'Tambah User Baru' }}</h2>
                                        <p class="text-white/70 text-sm">{{ editingUser ? 'Perbarui informasi pengguna' : 'Isi data untuk menambah pengguna' }}</p>
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
                                <!-- Identitas -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                                        <input v-model="form.name" type="text" required placeholder="Masukkan nama lengkap" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label>
                                        <input v-model="form.email" type="email" required placeholder="email@example.com" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon</label>
                                        <input v-model="form.phone" type="text" placeholder="08xxxxxxxxxx" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password {{ editingUser ? '' : '*' }}</label>
                                        <input v-model="form.password" type="password" :required="!editingUser" placeholder="••••••••" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                        <p v-if="editingUser" class="mt-1.5 text-xs text-gray-500">Kosongkan jika tidak ingin mengubah</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi Password</label>
                                        <input v-model="form.password_confirmation" type="password" placeholder="••••••••" class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-0 focus:border-primary-500 transition-colors"/>
                                    </div>
                                </div>

                                <!-- Role -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Role <span class="text-red-500">*</span></label>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <label v-for="role in roles" :key="role.id" :class="['relative flex flex-col items-center p-3 border-2 rounded-xl cursor-pointer transition-all hover:shadow-md', form.roles.includes(role.name) ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300']">
                                            <input type="checkbox" :value="role.name" v-model="form.roles" class="sr-only"/>
                                            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center mb-1.5', getRoleIconClass(role.name)]">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </div>
                                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 capitalize text-center">{{ (role.name || '').replace('_', ' ') }}</span>
                                            <div v-if="form.roles.includes(role.name)" class="absolute top-1.5 right-1.5 w-4 h-4 bg-primary-500 rounded-full flex items-center justify-center">
                                                <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Status Toggle -->
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                                    <div>
                                        <p class="font-medium text-gray-700 dark:text-gray-300">Status Aktif</p>
                                        <p class="text-sm text-gray-500">User dapat login ke sistem</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.is_active" class="sr-only peer"/>
                                        <div class="w-14 h-7 bg-gray-200 peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" @click="showModal = false" class="px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Batal</button>
                                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span class="flex items-center gap-2">
                                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        {{ form.processing ? 'Menyimpan...' : (editingUser ? 'Update' : 'Simpan') }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 animate-modal-in">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ isForceDelete ? 'Hapus Permanen?' : 'Hapus User?' }}</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            <template v-if="isForceDelete">Data <strong>{{ userToDelete?.name || 'User' }}</strong> akan dihapus secara permanen.</template>
                            <template v-else>Yakin ingin menghapus <strong>{{ userToDelete?.name || 'User' }}</strong>?</template>
                        </p>
                        <div class="flex gap-3 justify-center">
                            <button @click="showDeleteModal = false" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors">Batal</button>
                            <button @click="executeDelete" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors">{{ isForceDelete ? 'Hapus Permanen' : 'Ya, Hapus' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
    counts: Object,
    roleStats: Object,
});

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);

const activeTab = ref(props.filters?.tab || 'active');
const localFilters = ref({
    search: props.filters?.search || '',
    role: props.filters?.role || '',
    status: props.filters?.status || '',
});

const selectedIds = ref([]);
const selectAll = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const userToDelete = ref(null);
const editingUser = ref(null);
const isForceDelete = ref(false);
const isLoading = ref(false);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    is_active: true,
    roles: [],
});

let searchTimeout = null;

// Check if user can be deleted (not admin/administrator/akademik and not current user)
const canDelete = (user) => {
    if (!user) return false;
    // Can't delete yourself
    if (user.id === currentUserId.value) return false;
    // Can't delete protected roles
    const roles = (user.roles || []).map(r => r.name);
    if (roles.includes('administrator') || roles.includes('akademik')) return false;
    return true;
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
};

const applyFilters = () => {
    router.get('/users', { ...localFilters.value, tab: activeTab.value }, { preserveState: true, replace: true });
};

const reloadData = () => {
    isLoading.value = true;
    router.reload({ onFinish: () => { isLoading.value = false; } });
};

const switchTab = (tab) => {
    activeTab.value = tab;
    selectedIds.value = [];
    selectAll.value = false;
    router.get('/users', { tab }, { preserveState: true, replace: true });
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedIds.value = (props.users?.data || []).filter(u => canDelete(u)).map(u => u.id);
    } else {
        selectedIds.value = [];
    }
};

const getRoleBadgeClass = (role) => {
    const classes = {
        administrator: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        akademik: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
        staff_prodi: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        keuangan: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
        dosen: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        mahasiswa: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    };
    return classes[role] || 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400';
};

const getRoleIconClass = (role) => {
    const classes = {
        administrator: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
        akademik: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
        staff_prodi: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        keuangan: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
        dosen: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
        mahasiswa: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    };
    return classes[role] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const openModal = (user = null) => {
    editingUser.value = user;
    if (user) {
        form.name = user.name || '';
        form.email = user.email || '';
        form.phone = user.phone || '';
        form.password = '';
        form.password_confirmation = '';
        form.is_active = user.is_active ?? true;
        form.roles = (user.roles || []).map(r => r.name);
    } else {
        form.reset();
        form.is_active = true;
    }
    showModal.value = true;
};

const submitForm = () => {
    if (editingUser.value) {
        form.put(`/users/${editingUser.value.id}`, { onSuccess: () => { showModal.value = false; form.reset(); } });
    } else {
        form.post('/users', { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const toggleStatus = (user) => {
    if (!canDelete(user)) return;
    router.post(`/users/${user.id}/toggle-status`, {}, { preserveScroll: true });
};

const confirmDelete = (user) => {
    userToDelete.value = user;
    isForceDelete.value = false;
    showDeleteModal.value = true;
};

const confirmForceDelete = (user) => {
    userToDelete.value = user;
    isForceDelete.value = true;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    const id = userToDelete.value?.id;
    if (!id) return;
    
    if (isForceDelete.value) {
        router.delete(`/users/${id}/force-delete`, { onSuccess: () => { showDeleteModal.value = false; userToDelete.value = null; } });
    } else {
        router.delete(`/users/${id}`, { onSuccess: () => { showDeleteModal.value = false; userToDelete.value = null; } });
    }
};

const restoreUser = (user) => {
    router.post(`/users/${user.id}/restore`, {}, { preserveScroll: true });
};

const bulkDelete = () => {
    const deletableIds = selectedIds.value.filter(id => {
        const user = (props.users?.data || []).find(u => u.id === id);
        return canDelete(user);
    });
    if (deletableIds.length === 0) return;
    if (confirm(`Hapus ${deletableIds.length} user terpilih?`)) {
        router.post('/users/bulk-destroy', { ids: deletableIds }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; } });
    }
};

const bulkRestore = () => {
    router.post('/users/bulk-restore', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; } });
};

const bulkForceDelete = () => {
    if (confirm(`Hapus permanen ${selectedIds.value.length} user terpilih?`)) {
        router.post('/users/bulk-force-delete', { ids: selectedIds.value }, { onSuccess: () => { selectedIds.value = []; selectAll.value = false; } });
    }
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
