<script setup>
import AppLayout from "../../Components/Layout/AppLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, watch, onMounted, computed } from "vue";
import { debounce } from "lodash";
import {
    MagnifyingGlassIcon,
    PencilSquareIcon,
    DocumentPlusIcon,
    CheckBadgeIcon,
    ClockIcon,
    SparklesIcon,
    BookOpenIcon,
    ExclamationTriangleIcon,
    InboxIcon,
    Cog6ToothIcon,
    XMarkIcon,
    UserCircleIcon,
    AcademicCapIcon,
    CalendarIcon,
    UserGroupIcon,
    CheckIcon,
    ChevronUpDownIcon,
} from "@heroicons/vue/24/outline";
import {
    Combobox,
    ComboboxInput,
    ComboboxButton,
    ComboboxOptions,
    ComboboxOption,
    TransitionRoot,
} from "@headlessui/vue";

const props = defineProps({
    mataKuliahs: Object,
    prodis: Array,
    allDosens: Array, // Passed from controller
    canFilterProdi: Boolean,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const activeFilter = ref(props.filters?.filter || "all");
const perPage = ref(props.filters?.per_page || 10);
const selectedProdiId = ref(props.filters?.prodi_id || "");

// Live Edit State
const editingId = ref(null);
const editingField = ref(null); // 'pengembang' or 'tanggal'
const editValue = ref(null);
const queryDosen = ref("");

const filteredDosens = computed(() => {
    const dosens = props.allDosens || [];

    if (queryDosen.value === "") {
        return dosens;
    }
    return dosens.filter((person) =>
        person.nama_gelar
            .toLowerCase()
            .replace(/\s+/g, "")
            .includes(queryDosen.value.toLowerCase().replace(/\s+/g, ""))
    );
});

const startEdit = (mk, field) => {
    if (!mk.rps) return; // Cannot edit if no RPS
    editingId.value = mk.rps.id;
    editingField.value = field;

    if (field === "pengembang") {
        // Map existing developers to selected array
        // mk.rps.pengembang is array of Dosen objects from relationship
        const existingIds = mk.rps.pengembang?.map((p) => p.id) || [];
        // Map dosen id to Dosen items from allDosens
        editValue.value = props.allDosens.filter((d) =>
            existingIds.includes(d.id)
        );
        queryDosen.value = "";
    } else if (field === "tanggal_penyusunan") {
        editValue.value = mk.rps.tanggal_penyusunan;
    }
};

const cancelEdit = () => {
    editingId.value = null;
    editingField.value = null;
    editValue.value = null;
};

const saveMeta = (rpsId) => {
    let payload = editValue.value;
    let fieldName = editingField.value;

    if (fieldName === "pengembang") {
        // Extract dosen_ids from selected dosen objects
        payload = editValue.value
            .map((d) => d.id)
            .filter((id) => id != null && id !== ""); // Filter null/empty

        console.log("Saving pengembang:", payload); // Debug
    }

    router.put(
        route("rps.update-meta", rpsId),
        {
            field: fieldName,
            value: payload,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelEdit();
                // Show success toast (using flash message from Laravel)
            },
            onError: (errors) => {
                console.error("Save error:", errors);
                alert("Gagal menyimpan: " + JSON.stringify(errors));
            },
        }
    );
};

// Settings Modal
const showSettingsModal = ref(false);
const selectedProdi = ref(null);
const settingsForm = useForm({
    prodi_id: null,
    kaprodi_id: null,
    koordinator_rmk_id: null,
});

const openSettings = (prodi = null) => {
    if (prodi) {
        selectedProdi.value = prodi;
        settingsForm.prodi_id = prodi.id;
        settingsForm.kaprodi_id = prodi.kaprodi_id;
        settingsForm.koordinator_rmk_id = prodi.koordinator_rmk_id;
    } else {
        selectedProdi.value = props.prodis?.[0] || null;
        if (selectedProdi.value) {
            settingsForm.prodi_id = selectedProdi.value.id;
        }
    }
    showSettingsModal.value = true;
};

const closeSettings = () => {
    showSettingsModal.value = false;
    settingsForm.reset();
};

const saveSettings = () => {
    settingsForm.post(route("rps-settings.update"), {
        preserveScroll: true,
        onSuccess: () => closeSettings(),
    });
};

// Watchers for Server-Side Filtering
const updateParams = debounce(() => {
    router.get(
        route("rps.index"),
        {
            search: search.value,
            filter: activeFilter.value,
            per_page: perPage.value,
            prodi_id: selectedProdiId.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}, 300);

watch(search, updateParams);
watch(activeFilter, updateParams);
watch(perPage, updateParams);
watch(selectedProdiId, updateParams);

const getStatusBadge = (status) => {
    switch (status) {
        case "approved":
            return {
                class: "bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow-lg",
                text: "Disetujui",
                icon: CheckBadgeIcon,
            };
        case "submitted":
            return {
                class: "bg-gradient-to-r from-blue-400 to-indigo-500 text-white shadow-lg",
                text: "Diajukan",
                icon: ClockIcon,
            };
        case "draft":
            return {
                class: "bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow-lg",
                text: "Draft",
                icon: PencilSquareIcon,
            };
        default:
            return {
                class: "bg-gradient-to-r from-gray-300 to-gray-400 text-gray-700 shadow-md",
                text: "Belum Ada",
                icon: DocumentPlusIcon,
            };
    }
};
</script>

<template>
    <AppLayout title="Rencana Pembelajaran Semester">
        <template #header>
            <!-- STUNNING GRADIENT HEADER -->
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 p-6 shadow-2xl"
            >
                <!-- Animated Background -->
                <div class="absolute inset-0 opacity-20">
                    <div
                        class="absolute top-0 right-0 w-72 h-72 bg-white rounded-full blur-3xl animate-pulse"
                    ></div>
                    <div
                        class="absolute bottom-0 left-0 w-48 h-48 bg-pink-300 rounded-full blur-3xl animate-pulse"
                        style="animation-delay: 0.5s"
                    ></div>
                </div>
                <div
                    class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4"
                >
                    <div>
                        <h1
                            class="text-3xl font-black text-white drop-shadow-lg flex items-center gap-3"
                        >
                            <SparklesIcon class="w-8 h-8" />
                            Rencana Pembelajaran Semester
                        </h1>
                        <p class="text-white/80 mt-2 text-sm">
                            Kelola RPS untuk setiap mata kuliah aktif dengan
                            mudah
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="openSettings()"
                            class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl text-sm font-semibold transition backdrop-blur-sm border border-white/20"
                        >
                            <Cog6ToothIcon class="w-5 h-5" />
                            Setting
                        </button>
                        <div
                            class="text-white/90 text-sm bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2 border border-white/20"
                        >
                            <span class="font-bold">{{
                                mataKuliahs.total
                            }}</span>
                            <span> Mata Kuliah</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-4">
            <div class="px-6 lg:px-8 space-y-4">
                <!-- Statistics Cards - Reduced margin (mb-4 instead of mb-6) -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white"
                            >
                                <BookOpenIcon class="w-7 h-7" />
                            </div>
                            <div>
                                <p
                                    class="text-2xl font-black text-gray-900 dark:text-white"
                                >
                                    {{ mataKuliahs.total }}
                                </p>
                                <p class="text-xs text-gray-500">Total MK</p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white"
                            >
                                <CheckBadgeIcon class="w-7 h-7" />
                            </div>
                            <div>
                                <p class="text-2xl font-black text-green-600">
                                    {{
                                        mataKuliahs.data.filter(
                                            (m) => m.rps?.status === "approved"
                                        ).length
                                    }}
                                </p>
                                <p class="text-xs text-gray-500">Disetujui</p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white"
                            >
                                <PencilSquareIcon class="w-7 h-7" />
                            </div>
                            <div>
                                <p class="text-2xl font-black text-amber-600">
                                    {{
                                        mataKuliahs.data.filter(
                                            (m) => m.rps?.status === "draft"
                                        ).length
                                    }}
                                </p>
                                <p class="text-xs text-gray-500">Draft</p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center text-white"
                            >
                                <ExclamationTriangleIcon class="w-7 h-7" />
                            </div>
                            <div>
                                <p class="text-2xl font-black text-gray-600">
                                    {{
                                        mataKuliahs.data.filter((m) => !m.rps)
                                            .length
                                    }}
                                </p>
                                <p class="text-xs text-gray-500">Belum Ada</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden"
                >
                    <!-- Toolbar with Search and Filter -->
                    <div
                        class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900"
                    >
                        <div
                            class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
                        >
                            <!-- Search -->
                            <div
                                class="flex items-center gap-2 w-full md:w-auto flex-wrap"
                            >
                                <div class="relative w-full md:w-64">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                                    >
                                        <MagnifyingGlassIcon
                                            class="h-5 w-5 text-gray-400"
                                        />
                                    </div>
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Cari MK..."
                                        class="pl-12 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition text-sm"
                                    />
                                </div>
                                <select
                                    v-if="canFilterProdi"
                                    v-model="selectedProdiId"
                                    class="border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="">Semua Prodi</option>
                                    <option
                                        v-for="p in prodis"
                                        :key="p.id"
                                        :value="p.id"
                                    >
                                        {{ p.nama }}
                                    </option>
                                </select>
                                <select
                                    v-model="perPage"
                                    class="border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="flex flex-wrap gap-2">
                                <button
                                    @click="activeFilter = 'all'"
                                    :class="[
                                        'px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2',
                                        activeFilter === 'all'
                                            ? 'bg-indigo-600 text-white shadow-lg'
                                            : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    Semua
                                </button>
                                <button
                                    @click="activeFilter = 'approved'"
                                    :class="[
                                        'px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2',
                                        activeFilter === 'approved'
                                            ? 'bg-green-600 text-white shadow-lg'
                                            : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    <CheckBadgeIcon class="w-4 h-4" /> Disetujui
                                </button>
                                <button
                                    @click="activeFilter = 'draft'"
                                    :class="[
                                        'px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2',
                                        activeFilter === 'draft'
                                            ? 'bg-amber-500 text-white shadow-lg'
                                            : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    <PencilSquareIcon class="w-4 h-4" /> Draft
                                </button>
                                <button
                                    @click="activeFilter = 'none'"
                                    :class="[
                                        'px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2',
                                        activeFilter === 'none'
                                            ? 'bg-gray-600 text-white shadow-lg'
                                            : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    <ExclamationTriangleIcon class="w-4 h-4" />
                                    Belum Ada
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- List Content -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="bg-gray-50/80 dark:bg-gray-700/50 text-xs text-gray-500 uppercase tracking-wider font-bold border-b border-gray-100 dark:border-gray-700"
                                >
                                    <th class="px-6 py-4">Mata Kuliah</th>
                                    <th class="px-6 py-4">SKS / Semester</th>
                                    <th class="px-6 py-4">Status RPS</th>
                                    <th class="px-6 py-4">Tim Pengembang</th>
                                    <th class="px-6 py-4">
                                        Tanggal Penyusunan
                                    </th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800"
                            >
                                <tr
                                    v-for="mk in mataKuliahs.data"
                                    :key="mk.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition group"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shadow-sm"
                                            >
                                                {{ mk.kode.substring(0, 3) }}
                                            </div>
                                            <div>
                                                <div
                                                    class="font-bold text-gray-900 dark:text-gray-100"
                                                >
                                                    {{ mk.nama }}
                                                </div>
                                                <div
                                                    class="text-xs text-gray-500 font-mono"
                                                >
                                                    {{ mk.kode }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        {{ mk.sks_teori + mk.sks_praktik }} SKS
                                        • Sem {{ mk.semester }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border',
                                                getStatusBadge(mk.rps?.status)
                                                    .class,
                                            ]"
                                        >
                                            <component
                                                :is="
                                                    getStatusBadge(
                                                        mk.rps?.status
                                                    ).icon
                                                "
                                                class="w-3.5 h-3.5 mr-1"
                                            />
                                            {{
                                                getStatusBadge(mk.rps?.status)
                                                    .text
                                            }}
                                        </div>
                                    </td>

                                    <!-- Pengembang RPS Live Edit -->
                                    <td class="px-6 py-4 text-sm">
                                        <div v-if="mk.rps" class="relative">
                                            <!-- Edit Mode -->
                                            <div
                                                v-if="
                                                    editingId === mk.rps.id &&
                                                    editingField ===
                                                        'pengembang'
                                                "
                                                class="fixed inset-0 z-40 flex items-center justify-center bg-black/20"
                                                @click.self="cancelEdit"
                                            >
                                                <div
                                                    class="bg-white rounded-xl shadow-2xl border border-gray-200 p-4 w-[400px] max-w-[90vw]"
                                                >
                                                    <div
                                                        class="flex items-center justify-between mb-3"
                                                    >
                                                        <h4
                                                            class="font-bold text-gray-800"
                                                        >
                                                            Pilih Tim Pengembang
                                                            RPS
                                                        </h4>
                                                        <button
                                                            @click="cancelEdit"
                                                            class="text-gray-400 hover:text-gray-600"
                                                        >
                                                            <XMarkIcon
                                                                class="w-5 h-5"
                                                            />
                                                        </button>
                                                    </div>
                                                    <Combobox
                                                        v-model="editValue"
                                                        multiple
                                                    >
                                                        <div class="relative">
                                                            <div
                                                                class="relative w-full cursor-default overflow-hidden rounded-lg bg-gray-50 border border-gray-200 text-left focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500"
                                                            >
                                                                <ComboboxInput
                                                                    class="w-full border-none bg-transparent py-3 pl-4 pr-10 text-sm leading-5 text-gray-900 focus:ring-0"
                                                                    :displayValue="
                                                                        (
                                                                            people
                                                                        ) =>
                                                                            people
                                                                                .map(
                                                                                    (
                                                                                        p
                                                                                    ) =>
                                                                                        p.nama_gelar
                                                                                )
                                                                                .join(
                                                                                    ', '
                                                                                )
                                                                    "
                                                                    @change="
                                                                        queryDosen =
                                                                            $event
                                                                                .target
                                                                                .value
                                                                    "
                                                                    placeholder="Ketik untuk mencari dosen..."
                                                                />
                                                                <ComboboxButton
                                                                    class="absolute inset-y-0 right-0 flex items-center pr-3"
                                                                >
                                                                    <ChevronUpDownIcon
                                                                        class="h-5 w-5 text-gray-400"
                                                                        aria-hidden="true"
                                                                    />
                                                                </ComboboxButton>
                                                            </div>
                                                            <TransitionRoot
                                                                leave="transition ease-in duration-100"
                                                                leaveFrom="opacity-100"
                                                                leaveTo="opacity-0"
                                                                @after-leave="
                                                                    queryDosen =
                                                                        ''
                                                                "
                                                            >
                                                                <ComboboxOptions
                                                                    class="absolute mt-1 max-h-48 w-full overflow-auto rounded-lg bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm z-50"
                                                                >
                                                                    <div
                                                                        v-if="
                                                                            filteredDosens.length ===
                                                                                0 &&
                                                                            queryDosen !==
                                                                                ''
                                                                        "
                                                                        class="relative cursor-default select-none py-3 px-4 text-gray-500 text-center"
                                                                    >
                                                                        Dosen
                                                                        tidak
                                                                        ditemukan.
                                                                    </div>
                                                                    <ComboboxOption
                                                                        v-for="person in filteredDosens"
                                                                        as="template"
                                                                        :key="
                                                                            person.id
                                                                        "
                                                                        :value="
                                                                            person
                                                                        "
                                                                        v-slot="{
                                                                            selected,
                                                                            active,
                                                                        }"
                                                                    >
                                                                        <li
                                                                            class="relative cursor-pointer select-none py-2.5 pl-10 pr-4"
                                                                            :class="{
                                                                                'bg-indigo-600 text-white':
                                                                                    active,
                                                                                'text-gray-900':
                                                                                    !active,
                                                                            }"
                                                                        >
                                                                            <span
                                                                                class="block truncate"
                                                                                :class="{
                                                                                    'font-semibold':
                                                                                        selected,
                                                                                    'font-normal':
                                                                                        !selected,
                                                                                }"
                                                                            >
                                                                                {{
                                                                                    person.nama_gelar
                                                                                }}
                                                                            </span>
                                                                            <span
                                                                                v-if="
                                                                                    selected
                                                                                "
                                                                                class="absolute inset-y-0 left-0 flex items-center pl-3"
                                                                                :class="{
                                                                                    'text-white':
                                                                                        active,
                                                                                    'text-indigo-600':
                                                                                        !active,
                                                                                }"
                                                                            >
                                                                                <CheckIcon
                                                                                    class="h-5 w-5"
                                                                                    aria-hidden="true"
                                                                                />
                                                                            </span>
                                                                        </li>
                                                                    </ComboboxOption>
                                                                </ComboboxOptions>
                                                            </TransitionRoot>
                                                        </div>
                                                    </Combobox>

                                                    <!-- Selected Tags -->
                                                    <div
                                                        v-if="
                                                            editValue &&
                                                            editValue.length > 0
                                                        "
                                                        class="flex flex-wrap gap-1.5 mt-3"
                                                    >
                                                        <span
                                                            v-for="dev in editValue"
                                                            :key="dev.id"
                                                            class="inline-flex items-center gap-1 text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full"
                                                        >
                                                            {{ dev.nama_gelar }}
                                                            <button
                                                                @click="
                                                                    editValue =
                                                                        editValue.filter(
                                                                            (
                                                                                d
                                                                            ) =>
                                                                                d.id !==
                                                                                dev.id
                                                                        )
                                                                "
                                                                class="hover:text-indigo-900"
                                                            >
                                                                <XMarkIcon
                                                                    class="w-3 h-3"
                                                                />
                                                            </button>
                                                        </span>
                                                    </div>

                                                    <!-- Action Buttons -->
                                                    <div
                                                        class="flex justify-end gap-3 mt-4 pt-3 border-t border-gray-100"
                                                    >
                                                        <button
                                                            @click="cancelEdit"
                                                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 font-medium"
                                                        >
                                                            Batal
                                                        </button>
                                                        <button
                                                            @click="
                                                                saveMeta(
                                                                    mk.rps.id
                                                                )
                                                            "
                                                            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium shadow-sm"
                                                        >
                                                            Simpan
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- View Mode -->
                                            <div
                                                v-else
                                                @click="
                                                    startEdit(mk, 'pengembang')
                                                "
                                                class="cursor-pointer hover:bg-indigo-50 p-2 rounded -m-2 group/edit relative min-h-[32px]"
                                            >
                                                <div
                                                    v-if="
                                                        mk.rps.pengembang &&
                                                        mk.rps.pengembang
                                                            .length > 0
                                                    "
                                                    class="flex flex-wrap gap-1"
                                                >
                                                    <span
                                                        v-for="dev in mk.rps
                                                            .pengembang"
                                                        :key="dev.id"
                                                        class="text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded-full border border-indigo-200"
                                                    >
                                                        {{
                                                            dev.nama_gelar ||
                                                            dev.nama
                                                        }}
                                                    </span>
                                                </div>
                                                <div
                                                    v-else-if="mk.rps.dosen"
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{
                                                        mk.rps.dosen?.name ||
                                                        "-"
                                                    }}
                                                </div>
                                                <div
                                                    v-else
                                                    class="text-gray-400 italic text-xs"
                                                >
                                                    Klik untuk pilih
                                                </div>
                                                <PencilSquareIcon
                                                    class="w-3.5 h-3.5 text-indigo-400 absolute top-1 right-1 opacity-0 group-hover/edit:opacity-100 transition"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="text-gray-300 text-xs"
                                        >
                                            -
                                        </div>
                                    </td>

                                    <!-- Tanggal Penyusunan Live Edit -->
                                    <td class="px-6 py-4 text-sm">
                                        <div v-if="mk.rps" class="relative">
                                            <!-- Edit Mode - Modal -->
                                            <div
                                                v-if="
                                                    editingId === mk.rps.id &&
                                                    editingField ===
                                                        'tanggal_penyusunan'
                                                "
                                                class="fixed inset-0 z-40 flex items-center justify-center bg-black/20"
                                                @click.self="cancelEdit"
                                            >
                                                <div
                                                    class="bg-white rounded-xl shadow-2xl border border-gray-200 p-4 w-[340px] max-w-[90vw]"
                                                >
                                                    <div
                                                        class="flex items-center justify-between mb-3"
                                                    >
                                                        <h4
                                                            class="font-bold text-gray-800"
                                                        >
                                                            Tanggal Penyusunan
                                                            RPS
                                                        </h4>
                                                        <button
                                                            @click="cancelEdit"
                                                            class="text-gray-400 hover:text-gray-600"
                                                        >
                                                            <XMarkIcon
                                                                class="w-5 h-5"
                                                            />
                                                        </button>
                                                    </div>
                                                    <input
                                                        type="date"
                                                        v-model="editValue"
                                                        class="w-full text-sm border-gray-300 rounded-lg py-3 px-4 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                    />
                                                    <div
                                                        class="flex justify-end gap-3 mt-4 pt-3 border-t border-gray-100"
                                                    >
                                                        <button
                                                            @click="cancelEdit"
                                                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 font-medium"
                                                        >
                                                            Batal
                                                        </button>
                                                        <button
                                                            @click="
                                                                saveMeta(
                                                                    mk.rps.id
                                                                )
                                                            "
                                                            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium shadow-sm"
                                                        >
                                                            Simpan
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- View Mode -->
                                            <div
                                                v-else
                                                @click="
                                                    startEdit(
                                                        mk,
                                                        'tanggal_penyusunan'
                                                    )
                                                "
                                                class="cursor-pointer hover:bg-indigo-50 p-2 rounded -m-2 group/edit relative"
                                            >
                                                <span>{{
                                                    mk.rps.tanggal_penyusunan
                                                        ? new Date(
                                                              mk.rps.tanggal_penyusunan
                                                          ).toLocaleDateString(
                                                              "id-ID",
                                                              {
                                                                  day: "numeric",
                                                                  month: "short",
                                                                  year: "numeric",
                                                              }
                                                          )
                                                        : "-"
                                                }}</span>
                                                <PencilSquareIcon
                                                    class="w-3.5 h-3.5 text-indigo-400 absolute top-1 right-1 opacity-0 group-hover/edit:opacity-100 transition"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="text-gray-300 text-xs"
                                        >
                                            -
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Link
                                            :href="
                                                route('rps.create', {
                                                    mata_kuliah: mk.id,
                                                })
                                            "
                                            class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50 hover:text-indigo-600 transition group-hover:border-indigo-300"
                                        >
                                            <PencilSquareIcon
                                                class="w-4 h-4 mr-1.5"
                                            />
                                            {{
                                                mk.rps ? "Edit RPS" : "Buat RPS"
                                            }}
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="mataKuliahs.data.length === 0">
                                    <td
                                        colspan="4"
                                        class="px-6 py-12 text-center text-gray-500"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <InboxIcon
                                                class="w-16 h-16 text-gray-300"
                                            />
                                            <p>
                                                Tidak ada mata kuliah yang
                                                ditemukan.
                                            </p>
                                            <button
                                                v-if="activeFilter !== 'all'"
                                                @click="activeFilter = 'all'"
                                                class="text-indigo-600 hover:underline font-bold"
                                            >
                                                Tampilkan Semua
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between"
                    >
                        <div class="text-xs text-gray-500">
                            Menampilkan {{ mataKuliahs.from }} -
                            {{ mataKuliahs.to }} dari
                            {{ mataKuliahs.total }} data
                        </div>
                        <div class="flex gap-1">
                            <template
                                v-for="(link, i) in mataKuliahs.links"
                                :key="i"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-3 py-1 text-xs font-medium rounded border transition"
                                    :class="
                                        link.active
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                    "
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-1 text-xs text-gray-400"
                                    v-html="link.label"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Modal -->
        <Teleport to="body">
            <div
                v-if="showSettingsModal"
                class="fixed inset-0 z-50 overflow-y-auto"
            >
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div
                        class="fixed inset-0 bg-black/50 transition-opacity"
                        @click="closeSettings"
                    ></div>

                    <div
                        class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 z-10"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <h3
                                class="text-lg font-bold text-gray-900 flex items-center gap-2"
                            >
                                <Cog6ToothIcon
                                    class="w-5 h-5 text-indigo-600"
                                />
                                Pengaturan Struktural Prodi
                            </h3>
                            <button
                                @click="closeSettings"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <XMarkIcon class="w-6 h-6" />
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Program Studi</label
                                >
                                <select
                                    v-model="settingsForm.prodi_id"
                                    class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option
                                        v-for="p in prodis"
                                        :key="p.id"
                                        :value="p.id"
                                    >
                                        {{ p.nama }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    <AcademicCapIcon
                                        class="w-4 h-4 inline text-emerald-500 mr-1"
                                    />
                                    Ketua Prodi (Kaprodi)
                                </label>
                                <select
                                    v-model="settingsForm.kaprodi_id"
                                    class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option :value="null">
                                        -- Pilih Kaprodi --
                                    </option>
                                    <option
                                        v-for="d in prodis.find(
                                            (p) =>
                                                p.id === settingsForm.prodi_id
                                        )?.dosens || []"
                                        :key="d.id"
                                        :value="d.id"
                                    >
                                        {{ d.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    <UserCircleIcon
                                        class="w-4 h-4 inline text-blue-500 mr-1"
                                    />
                                    Koordinator RMK (Gugus Kendali Mutu)
                                </label>
                                <select
                                    v-model="settingsForm.koordinator_rmk_id"
                                    class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option :value="null">
                                        -- Pilih Koordinator RMK --
                                    </option>
                                    <option
                                        v-for="d in prodis.find(
                                            (p) =>
                                                p.id === settingsForm.prodi_id
                                        )?.dosens || []"
                                        :key="d.id"
                                        :value="d.id"
                                    >
                                        {{ d.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                @click="closeSettings"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition"
                            >
                                Batal
                            </button>
                            <button
                                @click="saveSettings"
                                :disabled="settingsForm.processing"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition disabled:opacity-50"
                            >
                                Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
