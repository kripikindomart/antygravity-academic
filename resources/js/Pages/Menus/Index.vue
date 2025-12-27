<template>
    <Head title="Menu Management" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Header with Gradient -->
            <div class="relative overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-800 rounded-3xl p-8 text-white">
                <div class="absolute inset-0 bg-grid-white/10"></div>
                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold">Menu Management</h1>
                        <p class="text-primary-100 mt-1">Kelola menu navigasi dan akses per role</p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="syncFromSidebar" class="px-5 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 rounded-xl transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Sync Sidebar
                        </button>
                        <button @click="openModal()" class="px-5 py-2.5 bg-white text-primary-700 font-semibold rounded-xl hover:bg-primary-50 transition-all flex items-center gap-2 shadow-lg shadow-primary-900/30">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah Menu
                        </button>
                    </div>
                </div>
                <!-- Stats -->
                <div class="relative mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <p class="text-3xl font-bold">{{ totalMenus }}</p>
                        <p class="text-sm text-primary-100">Total Menu</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <p class="text-3xl font-bold">{{ Object.keys(groupedMenus).length }}</p>
                        <p class="text-sm text-primary-100">Sections</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <p class="text-3xl font-bold">{{ roles.length }}</p>
                        <p class="text-sm text-primary-100">Roles</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <p class="text-3xl font-bold">{{ permissions.length }}</p>
                        <p class="text-sm text-primary-100">Permissions</p>
                    </div>
                </div>
            </div>

            <!-- Menu List by Section -->
            <div class="space-y-6">
                <div v-for="(items, section) in groupedMenus" :key="section" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800/50 dark:to-gray-800 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </div>
                            <h2 class="font-semibold text-gray-900 dark:text-white">{{ section || 'Tanpa Section' }}</h2>
                        </div>
                        <span class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-full text-sm text-gray-600 dark:text-gray-300">{{ items.length }} menu</span>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <div v-for="menu in items" :key="menu.id" class="group p-4 hover:bg-gradient-to-r hover:from-gray-50 hover:to-transparent dark:hover:from-gray-800/50 transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center text-white font-bold shadow-lg shadow-primary-500/30 group-hover:scale-110 transition-transform">
                                        {{ menu.order }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ menu.name }}</span>
                                            <span v-if="menu.icon" class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-800 text-gray-500 rounded">{{ menu.icon }}</span>
                                            <span v-if="!menu.is_active" class="px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full animate-pulse">Nonaktif</span>
                                        </div>
                                        <div class="flex items-center gap-3 text-sm text-gray-500 mt-0.5">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                                {{ menu.href || '-' }}
                                            </span>
                                            <span v-if="menu.permission" class="px-2.5 py-1 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg text-xs font-medium">
                                                🔐 {{ menu.permission }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openModal(menu)" class="p-2.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-xl transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click="confirmDelete(menu)" class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Role Visibility Pills -->
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button v-for="role in roles" :key="role.id" 
                                    @click="toggleRoleVisibility(menu, role)"
                                    :class="['px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200', getRoleVisibility(menu, role) ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-md shadow-green-500/30 hover:shadow-lg' : 'bg-gray-100 dark:bg-gray-800 text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 line-through']">
                                    {{ role.name }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="Object.keys(groupedMenus).length === 0" class="text-center py-16 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800">
                    <div class="w-20 h-20 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Belum Ada Menu</h3>
                    <p class="text-gray-500 mb-4">Klik "Sync Sidebar" untuk import menu existing atau tambah manual</p>
                    <button @click="syncFromSidebar" class="px-4 py-2 bg-primary-600 text-white rounded-xl hover:bg-primary-700">Sync dari Sidebar</button>
                </div>
            </div>
        </div>

        <!-- Menu Modal Modern -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="showModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full animate-modal-in overflow-hidden">
                        <!-- Modal Header with Gradient -->
                        <div class="bg-gradient-to-r from-primary-600 to-indigo-600 px-8 py-6 text-white">
                            <h3 class="text-xl font-bold">{{ editingMenu ? 'Edit Menu' : 'Tambah Menu Baru' }}</h3>
                            <p class="text-primary-100 text-sm mt-1">Konfigurasi item navigasi</p>
                        </div>
                        
                        <form @submit.prevent="submitForm" class="p-8 space-y-6">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Nama Menu <span class="text-red-500">*</span></label>
                                    <input v-model="form.name" @input="autoSlug" type="text" required placeholder="Dashboard" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition-colors"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Slug <span class="text-red-500">*</span></label>
                                    <input v-model="form.slug" type="text" required placeholder="dashboard" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition-colors font-mono text-sm"/>
                                </div>
                            </div>

                            <!-- Icon Picker & URL -->
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Icon</label>
                                    <div class="relative">
                                        <button type="button" @click="showIconPicker = !showIconPicker" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition-colors flex items-center gap-3">
                                            <div v-if="form.icon" class="w-6 h-6 text-primary-600">
                                                <component :is="getIconComponent(form.icon)" />
                                            </div>
                                            <div v-else class="w-6 h-6 text-gray-400">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                            <span :class="form.icon ? 'text-gray-900 dark:text-white' : 'text-gray-400'">{{ form.icon || 'Pilih icon...' }}</span>
                                            <svg class="w-5 h-5 ml-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        
                                        <!-- Icon Picker Dropdown -->
                                        <div v-if="showIconPicker" class="absolute z-20 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 p-3">
                                            <p class="text-xs text-gray-500 mb-2">Pilih Icon:</p>
                                            <div class="grid grid-cols-6 gap-2 max-h-48 overflow-y-auto">
                                                <button v-for="icon in availableIcons" :key="icon.name" type="button" @click="selectIcon(icon.name)" :class="['p-2 rounded-lg transition-all hover:scale-110', form.icon === icon.name ? 'bg-primary-100 dark:bg-primary-900/30 ring-2 ring-primary-500' : 'hover:bg-gray-100 dark:hover:bg-gray-700']" :title="icon.name">
                                                    <div class="w-6 h-6 text-gray-600 dark:text-gray-400" v-html="icon.svg"></div>
                                                </button>
                                            </div>
                                            <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                                <input v-model="form.icon" type="text" placeholder="Atau ketik manual..." class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">URL / Route</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                        </div>
                                        <input v-model="form.href" type="text" placeholder="/dashboard" class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition-colors font-mono text-sm"/>
                                    </div>
                                </div>
                            </div>

                            <!-- Permission & Section -->
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Permission Required</label>
                                    <select v-model="form.permission" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition-colors">
                                        <option value="">🌐 Publik (Semua Role)</option>
                                        <option v-for="perm in permissions" :key="perm" :value="perm">🔐 {{ perm }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Section / Group</label>
                                    <div class="relative">
                                        <select v-if="!showCustomSection" v-model="form.section" @change="checkCustomSection" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition-colors">
                                            <option value="">-- Pilih Section --</option>
                                            <option v-for="section in existingSections" :key="section" :value="section">{{ section }}</option>
                                            <option value="__custom__">➕ Tambah Section Baru...</option>
                                        </select>
                                        <div v-else class="flex gap-2">
                                            <input v-model="customSection" type="text" placeholder="Nama section baru..." class="flex-1 px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition-colors"/>
                                            <button type="button" @click="cancelCustomSection" class="px-3 py-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order & Status -->
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Urutan Tampil</label>
                                    <input v-model="form.order" type="number" min="0" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-0 focus:border-primary-500 transition-colors"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Status</label>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Menu aktif ditampilkan</span>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input v-model="form.is_active" type="checkbox" class="sr-only peer"/>
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" @click="showModal = false" class="px-6 py-3 text-gray-600 dark:text-gray-400 font-medium hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors">Batal</button>
                                <button type="submit" :disabled="form.processing" class="px-6 py-3 bg-gradient-to-r from-primary-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-primary-700 hover:to-indigo-700 shadow-lg shadow-primary-500/30 transition-all disabled:opacity-50">
                                    <span class="flex items-center gap-2">
                                        <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        {{ form.processing ? 'Menyimpan...' : 'Simpan Menu' }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    menus: Array,
    groupedMenus: Object,
    roles: Array,
    permissions: Array,
});

const showModal = ref(false);
const editingMenu = ref(null);
const showCustomSection = ref(false);
const customSection = ref('');
const showIconPicker = ref(false);

const form = useForm({
    name: '',
    slug: '',
    icon: '',
    href: '',
    permission: '',
    section: '',
    order: 0,
    is_active: true,
});

const totalMenus = computed(() => props.menus?.length || 0);

// Available icons from NavItem
const availableIcons = [
    { name: 'home', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>' },
    { name: 'calendar', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' },
    { name: 'academic', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>' },
    { name: 'book', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>' },
    { name: 'clipboard-check', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>' },
    { name: 'document-text', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>' },
    { name: 'chart-bar', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>' },
    { name: 'star', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>' },
    { name: 'users', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>' },
    { name: 'shield', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>' },
    { name: 'building', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>' },
    { name: 'location', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>' },
    { name: 'clock', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
    { name: 'document-report', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>' },
    { name: 'document', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>' },
    { name: 'currency', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
    { name: 'cog', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>' },
    { name: 'menu', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>' },
];

const selectIcon = (iconName) => {
    form.icon = iconName;
    showIconPicker.value = false;
};

const getIconComponent = (iconName) => {
    const icon = availableIcons.find(i => i.name === iconName);
    if (icon) {
        return { template: icon.svg };
    }
    return { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>' };
};

const existingSections = computed(() => {
    const sections = new Set();
    (props.menus || []).forEach(m => {
        if (m.section) sections.add(m.section);
    });
    return Array.from(sections).sort();
});

const autoSlug = () => {
    if (!editingMenu.value) {
        form.slug = form.name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
    }
};

const checkCustomSection = () => {
    if (form.section === '__custom__') {
        showCustomSection.value = true;
        form.section = '';
    }
};

const cancelCustomSection = () => {
    showCustomSection.value = false;
    customSection.value = '';
    form.section = '';
};

const openModal = (menu = null) => {
    editingMenu.value = menu;
    showCustomSection.value = false;
    customSection.value = '';
    if (menu) {
        form.name = menu.name;
        form.slug = menu.slug;
        form.icon = menu.icon || '';
        form.href = menu.href || '';
        form.permission = menu.permission || '';
        form.section = menu.section || '';
        form.order = menu.order || 0;
        form.is_active = menu.is_active;
    } else {
        form.reset();
        form.is_active = true;
    }
    showModal.value = true;
};

const submitForm = () => {
    // Use custom section if provided
    if (showCustomSection.value && customSection.value) {
        form.section = customSection.value;
    }
    
    if (editingMenu.value) {
        form.put(`/menus/${editingMenu.value.id}`, { onSuccess: () => { showModal.value = false; } });
    } else {
        form.post('/menus', { onSuccess: () => { showModal.value = false; } });
    }
};

const confirmDelete = (menu) => {
    if (confirm(`Hapus menu "${menu.name}"?`)) {
        router.delete(`/menus/${menu.id}`);
    }
};

const syncFromSidebar = () => {
    router.post('/menus/sync-sidebar');
};

const getRoleVisibility = (menu, role) => {
    const roleOverride = (menu.roles || []).find(r => r.id === role.id);
    if (roleOverride) {
        return roleOverride.pivot.is_visible;
    }
    return true;
};

const toggleRoleVisibility = (menu, role) => {
    const currentVisibility = getRoleVisibility(menu, role);
    router.post(`/menus/${menu.id}/role-visibility`, {
        role_id: role.id,
        is_visible: !currentVisibility,
    }, { preserveScroll: true });
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.animate-modal-in { animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes modalIn { from { transform: scale(0.9) translateY(20px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
.bg-grid-white\/10 { background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
</style>
