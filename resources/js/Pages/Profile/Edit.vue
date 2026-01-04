<template>
    <AppLayout>
        <Head title="Profil Saya" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Profil Saya
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">
                    Kelola informasi profil dan keamanan akun Anda
                </p>
            </div>

            <!-- Profile Info Form -->
            <form
                @submit.prevent="updateProfile"
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6"
            >
                <div class="flex items-center gap-6 mb-8">
                    <div class="relative group">
                        <div
                            class="w-24 h-24 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700"
                        >
                            <img
                                v-if="previewUrl || user?.avatar_url"
                                :src="previewUrl || user.avatar_url"
                                class="w-full h-full object-cover"
                            />
                            <div
                                v-else
                                class="w-full h-full flex items-center justify-center text-gray-400"
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
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <label
                            class="absolute bottom-0 right-0 p-1.5 bg-primary-600 text-white rounded-full cursor-pointer hover:bg-primary-700 shadow-lg transition-colors"
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
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                            <input
                                type="file"
                                class="hidden"
                                accept="image/*"
                                @change="handleAvatarChange"
                            />
                        </label>
                    </div>
                    <div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white"
                        >
                            {{ user.name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ user.email }}
                        </p>
                        <div class="flex gap-2 mt-2">
                            <span
                                v-for="role in user.roles || []"
                                :key="role.name"
                                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs font-medium rounded-full capitalize"
                            >
                                {{ role.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >Nama Lengkap</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >Email</label
                        >
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >Telepon</label
                        >
                        <input
                            v-model="form.phone"
                            type="text"
                            class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                        />
                        <p
                            v-if="form.errors.phone"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.phone }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? "Menyimpan..."
                                : "Simpan Perubahan"
                        }}
                    </button>
                </div>
            </form>

            <!-- Change Password Form -->
            <form
                @submit.prevent="updatePassword"
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6"
            >
                <h3
                    class="text-lg font-bold text-gray-900 dark:text-white mb-6"
                >
                    Ubah Password
                </h3>

                <div class="space-y-4 max-w-2xl">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >Password Saat Ini</label
                        >
                        <input
                            v-model="passwordForm.current_password"
                            type="password"
                            required
                            class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                        />
                        <p
                            v-if="passwordForm.errors.current_password"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >Password Baru</label
                            >
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                required
                                class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                            />
                            <p
                                v-if="passwordForm.errors.password"
                                class="mt-1 text-sm text-red-500"
                            >
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >Konfirmasi Password Baru</label
                            >
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                required
                                class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="submit"
                        :disabled="passwordForm.processing"
                        class="px-6 py-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                    >
                        {{
                            passwordForm.processing
                                ? "Menyimpan..."
                                : "Update Password"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";

const props = defineProps({
    user: Object,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone || "",
    avatar: null,
});

const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const previewUrl = ref(null);

const handleAvatarChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.avatar = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const updateProfile = () => {
    form.transform((data) => ({
        ...data,
        _method: "PATCH",
    })).post("/profile", {
        preserveScroll: true,
        onSuccess: () => {
            // handle success
        },
    });
};

const updatePassword = () => {
    passwordForm.put("/profile/password", {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};
</script>
