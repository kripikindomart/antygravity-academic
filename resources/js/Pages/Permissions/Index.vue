<template>
    <AppLayout>
        <Head title="Manajemen Permission" />

        <div class="space-y-6">
            <!-- Header with Stats -->
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Manajemen Permission
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Kelola hak akses sistem secara granular
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="openBulkModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-all shadow-sm"
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
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                            />
                        </svg>
                        Bulk Create
                    </button>
                    <button
                        @click="openModal()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg shadow-primary-500/30 transition-all hover:scale-105 hover:shadow-xl"
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
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Tambah Permission
                    </button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Total Permission
                    </p>
                    <p
                        class="text-2xl font-bold text-gray-900 dark:text-white mt-1"
                    >
                        {{ totalPermissions }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Total Module
                    </p>
                    <p
                        class="text-2xl font-bold text-gray-900 dark:text-white mt-1"
                    >
                        {{ groupedPermissions.length }}
                    </p>
                </div>
            </div>

            <!-- Permissions Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <div
                    v-for="group in groupedPermissions"
                    :key="group.module"
                    class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden"
                >
                    <div
                        class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="p-2 bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-lg"
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
                                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"
                                    />
                                </svg>
                            </span>
                            <div>
                                <h3
                                    class="font-bold text-gray-900 dark:text-white capitalize"
                                >
                                    {{ group.module }}
                                </h3>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{ group.permissions.length }} actions
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 space-y-2">
                        <div
                            v-for="perm in group.permissions"
                            :key="perm.id"
                            class="group flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"
                                ></div>
                                <span
                                    class="font-mono text-sm text-gray-600 dark:text-gray-300"
                                    >{{ perm.name }}</span
                                >
                            </div>

                            <div
                                class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                <span
                                    class="text-xs text-gray-400"
                                    title="Digunakan di role"
                                    >{{ perm.roles_count }} roles</span
                                >
                                <button
                                    @click="confirmDelete(perm)"
                                    class="p-1 px-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded text-xs font-medium"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"
                @click="showModal = false"
            ></div>
            <div
                class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 animate-modal-in"
            >
                <h3
                    class="text-xl font-bold text-gray-900 dark:text-white mb-4"
                >
                    Tambah Permission Baru
                </h3>

                <form @submit.prevent="submitForm">
                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                            >Nama Permission</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="module.action (ex: users.create)"
                            class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Format: <code>module.action</code>
                        </p>
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg shadow-lg shadow-primary-500/30"
                        >
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bulk Create Modal -->
        <div
            v-if="showBulkModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"
                @click="showBulkModal = false"
            ></div>
            <div
                class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-lg w-full p-6 animate-modal-in"
            >
                <h3
                    class="text-xl font-bold text-gray-900 dark:text-white mb-4"
                >
                    Bulk Create Permissions
                </h3>

                <form @submit.prevent="submitBulkForm">
                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                            >Module Name</label
                        >
                        <input
                            v-model="bulkForm.module"
                            type="text"
                            placeholder="example: products"
                            class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                        />
                        <p
                            v-if="bulkForm.errors.module"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ bulkForm.errors.module }}
                        </p>
                    </div>

                    <div class="mb-6">
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >Actions</label
                        >
                        <div class="grid grid-cols-2 gap-3">
                            <label
                                v-for="action in standardActions"
                                :key="action"
                                class="flex items-center gap-2 p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                <input
                                    type="checkbox"
                                    :value="action"
                                    v-model="bulkForm.actions"
                                    class="w-4 h-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500"
                                />
                                <span
                                    class="text-sm text-gray-700 dark:text-gray-300"
                                    >{{ action }}</span
                                >
                            </label>
                        </div>
                        <div class="mt-3">
                            <label
                                class="block text-xs font-medium text-gray-500 mb-1"
                                >Custom Actions (comma separated)</label
                            >
                            <input
                                v-model="customActions"
                                type="text"
                                placeholder="approve, reject, export"
                                @blur="addCustomActions"
                                class="block w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm"
                            />
                        </div>
                        <p
                            v-if="bulkForm.errors.actions"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ bulkForm.errors.actions }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="showBulkModal = false"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="bulkForm.processing"
                            class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg shadow-lg shadow-primary-500/30"
                        >
                            Generate Permissions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import AppLayout from "@/Components/Layout/AppLayout.vue";

const props = defineProps({
    groupedPermissions: Array,
    modules: Array,
    standardActions: Array,
    totalPermissions: Number,
});

const showModal = ref(false);
const showBulkModal = ref(false);
const customActions = ref("");

const form = useForm({
    name: "",
});

const bulkForm = useForm({
    module: "",
    actions: ["view", "create", "edit", "delete"],
});

const openModal = () => {
    form.reset();
    showModal.value = true;
};

const openBulkModal = () => {
    bulkForm.reset();
    bulkForm.actions = ["view", "create", "edit", "delete"];
    customActions.value = "";
    showBulkModal.value = true;
};

const submitForm = () => {
    form.post("/permissions", {
        onSuccess: () => (showModal.value = false),
    });
};

const addCustomActions = () => {
    if (customActions.value) {
        const newActions = customActions.value
            .split(",")
            .map((a) => a.trim())
            .filter((a) => a);
        newActions.forEach((action) => {
            if (!bulkForm.actions.includes(action)) {
                bulkForm.actions.push(action);
            }
        });
        customActions.value = "";
    }
};

const submitBulkForm = () => {
    addCustomActions();
    bulkForm.post("/permissions/bulk", {
        onSuccess: () => (showBulkModal.value = false),
    });
};

const confirmDelete = (perm) => {
    if (confirm(`Hapus permission '${perm.name}'?`)) {
        router.delete(`/permissions/${perm.id}`);
    }
};
</script>

<style>
.animate-modal-in {
    animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modalIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
