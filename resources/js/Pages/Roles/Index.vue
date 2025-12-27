<template>
    <AppLayout>
        <Head title="Manajemen Role" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Manajemen Role
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Kelola role dan hak akses sistem
                    </p>
                </div>
                <Link
                    href="/roles/create"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all hover:scale-105"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Role
                </Link>
            </div>

            <!-- Roles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="role in roles"
                    :key="role.id"
                    class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 hover:shadow-md transition-shadow"
                >
                    <!-- Role Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white capitalize">
                                {{ role.name }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ role.permissions_count || role.permissions?.length || 0 }} permissions
                            </p>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-semibold"
                            :class="getRoleBadgeClass(role.name)"
                        >
                            {{ role.users_count || 0 }} users
                        </span>
                    </div>

                    <!-- Permissions Preview -->
                    <div class="mb-4">
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="(permission, idx) in (role.permissions || []).slice(0, 5)"
                                :key="permission.id"
                                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded"
                            >
                                {{ permission.name }}
                            </span>
                            <span
                                v-if="(role.permissions || []).length > 5"
                                class="px-2 py-0.5 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs rounded"
                            >
                                +{{ (role.permissions || []).length - 5 }} lainnya
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <Link
                            :href="`/roles/${role.id}/edit`"
                            class="flex-1 py-2 text-center text-sm font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors"
                        >
                            Edit
                        </Link>
                        <button
                            v-if="!isSystemRole(role.name)"
                            @click="confirmDelete(role)"
                            class="flex-1 py-2 text-center text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        >
                            Hapus
                        </button>
                        <span
                            v-else
                            class="flex-1 py-2 text-center text-xs text-gray-400"
                        >
                            Role Sistem
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Hapus Role?</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            Anda yakin ingin menghapus role <strong class="capitalize">{{ roleToDelete?.name }}</strong>?
                        </p>
                        <div class="flex gap-3 justify-center">
                            <button
                                @click="showDeleteModal = false"
                                class="px-4 py-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                @click="deleteRole"
                                class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors"
                            >
                                Ya, Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    roles: Array,
});

const showDeleteModal = ref(false);
const roleToDelete = ref(null);

const systemRoles = ['akademik', 'staff_prodi', 'dosen', 'mahasiswa'];

const isSystemRole = (name) => systemRoles.includes(name);

const getRoleBadgeClass = (role) => {
    const classes = {
        akademik: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
        staff_prodi: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        dosen: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        mahasiswa: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    };
    return classes[role] || 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400';
};

const confirmDelete = (role) => {
    roleToDelete.value = role;
    showDeleteModal.value = true;
};

const deleteRole = () => {
    router.delete(`/roles/${roleToDelete.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            roleToDelete.value = null;
        },
    });
};
</script>
