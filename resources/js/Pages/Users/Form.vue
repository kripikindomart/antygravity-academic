<template>
    <AppLayout>
        <Head :title="user ? 'Edit User' : 'Tambah User'" />
        
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link href="/users" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali
                </Link>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ user ? 'Edit User' : 'Tambah User Baru' }}
                </h1>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 space-y-6">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                :class="{ 'border-red-500': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Telepon
                            </label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                            />
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700" />

                    <!-- Identifiers -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                NIP
                            </label>
                            <input
                                v-model="form.nip"
                                type="text"
                                class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                :class="{ 'border-red-500': form.errors.nip }"
                            />
                            <p v-if="form.errors.nip" class="mt-1 text-sm text-red-500">{{ form.errors.nip }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                NIM
                            </label>
                            <input
                                v-model="form.nim"
                                type="text"
                                class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                :class="{ 'border-red-500': form.errors.nim }"
                            />
                            <p v-if="form.errors.nim" class="mt-1 text-sm text-red-500">{{ form.errors.nim }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                NIDN
                            </label>
                            <input
                                v-model="form.nidn"
                                type="text"
                                class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                :class="{ 'border-red-500': form.errors.nidn }"
                            />
                            <p v-if="form.errors.nidn" class="mt-1 text-sm text-red-500">{{ form.errors.nidn }}</p>
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700" />

                    <!-- Password -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Password {{ user ? '' : '*' }}
                            </label>
                            <input
                                v-model="form.password"
                                type="password"
                                :required="!user"
                                class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                :class="{ 'border-red-500': form.errors.password }"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-500">{{ form.errors.password }}</p>
                            <p v-if="user" class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin mengubah password</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Konfirmasi Password {{ user ? '' : '*' }}
                            </label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                :required="!user && form.password"
                                class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                            />
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700" />

                    <!-- Roles -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label
                                v-for="role in roles"
                                :key="role.id"
                                :class="[
                                    'flex items-center gap-3 p-4 border rounded-xl cursor-pointer transition-all',
                                    form.roles.includes(role.name)
                                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                ]"
                            >
                                <input
                                    type="checkbox"
                                    :value="role.name"
                                    v-model="form.roles"
                                    class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                                <span class="font-medium text-gray-700 dark:text-gray-300 capitalize">{{ role.name }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.roles" class="mt-2 text-sm text-red-500">{{ form.errors.roles }}</p>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700" />

                    <!-- Status -->
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                        </label>
                        <span class="text-gray-700 dark:text-gray-300 font-medium">User Aktif</span>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <Link
                        href="/users"
                        class="px-6 py-3 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : (user ? 'Update' : 'Simpan') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    user: Object,
    roles: Array,
});

const form = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    phone: props.user?.phone || '',
    nip: props.user?.nip || '',
    nim: props.user?.nim || '',
    nidn: props.user?.nidn || '',
    password: '',
    password_confirmation: '',
    is_active: props.user?.is_active ?? true,
    roles: props.user?.roles?.map(r => r.name) || [],
});

const submit = () => {
    if (props.user) {
        form.put(`/users/${props.user.id}`);
    } else {
        form.post('/users');
    }
};
</script>
