<template>
    <AppLayout>
        <Head :title="role ? 'Edit Role' : 'Tambah Role'" />
        
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link href="/roles" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali
                </Link>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ role ? 'Edit Role' : 'Tambah Role Baru' }}
                </h1>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Role Name -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6">
                    <div class="max-w-md">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nama Role <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            :disabled="role && isSystemRole(role.name)"
                            class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 disabled:opacity-50"
                            :class="{ 'border-red-500': form.errors.name }"
                            placeholder="contoh: supervisor"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Permissions</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pilih hak akses untuk role ini</p>
                        </div>
                        <button
                            type="button"
                            @click="toggleAll"
                            class="text-sm font-medium text-primary-600 hover:text-primary-700"
                        >
                            {{ allSelected ? 'Hapus Semua' : 'Pilih Semua' }}
                        </button>
                    </div>

                    <div class="space-y-6">
                        <div
                            v-for="(perms, module) in permissions"
                            :key="module"
                            class="border border-gray-100 dark:border-gray-800 rounded-xl overflow-hidden"
                        >
                            <!-- Module Header -->
                            <div
                                class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-800/50 cursor-pointer"
                                @click="toggleModule(module)"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        :checked="isModuleChecked(module, perms)"
                                        :indeterminate="isModuleIndeterminate(module, perms)"
                                        @click.stop="toggleModulePermissions(module, perms)"
                                        class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="font-semibold text-gray-700 dark:text-gray-300 capitalize">{{ module }}</span>
                                    <span class="text-xs text-gray-500 bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                        {{ perms.length }}
                                    </span>
                                </div>
                                <svg
                                    class="w-5 h-5 text-gray-400 transition-transform"
                                    :class="{ 'rotate-180': expandedModules.includes(module) }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>

                            <!-- Module Permissions -->
                            <div v-show="expandedModules.includes(module)" class="p-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                                <label
                                    v-for="perm in perms"
                                    :key="perm.id"
                                    class="flex items-center gap-2 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="perm.name"
                                        v-model="form.permissions"
                                        class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ getPermissionLabel(perm.name) }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <p v-if="form.errors.permissions" class="mt-4 text-sm text-red-500">{{ form.errors.permissions }}</p>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <Link
                        href="/roles"
                        class="px-6 py-3 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : (role ? 'Update' : 'Simpan') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    role: Object,
    permissions: Object,
});

const systemRoles = ['akademik', 'staff_prodi', 'dosen', 'mahasiswa'];
const isSystemRole = (name) => systemRoles.includes(name);

const expandedModules = ref(Object.keys(props.permissions || {}));

const form = useForm({
    name: props.role?.name || '',
    permissions: props.role?.permissions?.map(p => p.name) || [],
});

const allPermissions = computed(() => {
    return Object.values(props.permissions || {}).flat().map(p => p.name);
});

const allSelected = computed(() => {
    return allPermissions.value.length > 0 && allPermissions.value.every(p => form.permissions.includes(p));
});

const toggleAll = () => {
    if (allSelected.value) {
        form.permissions = [];
    } else {
        form.permissions = [...allPermissions.value];
    }
};

const toggleModule = (module) => {
    const idx = expandedModules.value.indexOf(module);
    if (idx > -1) {
        expandedModules.value.splice(idx, 1);
    } else {
        expandedModules.value.push(module);
    }
};

const isModuleChecked = (module, perms) => {
    return perms.every(p => form.permissions.includes(p.name));
};

const isModuleIndeterminate = (module, perms) => {
    const checked = perms.filter(p => form.permissions.includes(p.name));
    return checked.length > 0 && checked.length < perms.length;
};

const toggleModulePermissions = (module, perms) => {
    const allChecked = isModuleChecked(module, perms);
    const permNames = perms.map(p => p.name);
    
    if (allChecked) {
        form.permissions = form.permissions.filter(p => !permNames.includes(p));
    } else {
        form.permissions = [...new Set([...form.permissions, ...permNames])];
    }
};

const getPermissionLabel = (name) => {
    const action = name.split('.').pop();
    const labels = {
        view: 'Lihat',
        create: 'Tambah',
        edit: 'Edit',
        delete: 'Hapus',
        manage: 'Kelola',
        export: 'Export',
        import: 'Import',
        approve: 'Approve',
    };
    return labels[action] || action;
};

const submit = () => {
    if (props.role) {
        form.put(`/roles/${props.role.id}`);
    } else {
        form.post('/roles');
    }
};
</script>
