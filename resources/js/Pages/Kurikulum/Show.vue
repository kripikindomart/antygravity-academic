<template>
    <Head :title="`Kurikulum: ${kurikulum.nama}`" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-3xl p-8 text-white shadow-xl">
                <div class="absolute inset-0 bg-grid-white/10"></div>
                <div class="relative">
                    <Link href="/kurikulum" class="inline-flex items-center gap-2 text-white/80 hover:text-white mb-4 transition font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Kembali ke Daftar
                    </Link>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-bold shadow-sm tracking-wide">{{ kurikulum.kode }}</span>
                                <span :class="['px-3 py-1 rounded-full text-sm font-bold shadow-sm', kurikulum.is_active ? 'bg-green-500 text-white' : 'bg-gray-500/50 text-gray-200']">
                                    {{ kurikulum.is_active ? 'Aktif' : 'Non-aktif' }}
                                </span>
                            </div>
                            <h1 class="text-3xl md:text-4xl font-extrabold mt-3 tracking-tight">{{ kurikulum.nama }}</h1>
                            <p class="text-indigo-100 mt-2 font-medium">{{ kurikulum.prodi?.nama }} • Angkatan {{ kurikulum.tahun }}</p>
                        </div>
                        <div class="flex gap-3">
                            <div class="text-center px-6 py-3 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/10 shadow-lg">
                                <p class="text-3xl font-bold">{{ localCpls.length }}</p>
                                <p class="text-xs uppercase tracking-wider text-indigo-100 mt-1 font-semibold">CPL</p>
                            </div>
                            <div class="text-center px-6 py-3 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/10 shadow-lg">
                                <p class="text-3xl font-bold">{{ kurikulum.mata_kuliahs?.length || 0 }}</p>
                                <p class="text-xs uppercase tracking-wider text-indigo-100 mt-1 font-semibold">Mata Kuliah</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex gap-2 bg-white dark:bg-gray-900 p-1.5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800">
                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="['flex-1 py-2.5 px-4 rounded-xl text-sm font-bold transition-all duration-200', activeTab === tab.id ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900 shadow-md transform scale-[1.02]' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800']">
                    {{ tab.label }}
                </button>
            </div>

            <!-- Profil Lulusan Tab -->
            <div v-if="activeTab === 'profil_lulusan'" class="animate-fade-in-up">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Daftar Profil Lulusan</h2>
                        <button @click="openPlModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-500/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah Profil
                        </button>
                    </div>

                    <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-xl mb-8">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode PL</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Profil Lulusan</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                <tr v-for="pl in localProfilLulusans" :key="pl.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">{{ pl.kode }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ pl.deskripsi }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openPlModal(pl)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                        <button @click="deletePl(pl)" class="text-red-600 hover:text-red-900">Hapus</button>
                                    </td>
                                </tr>
                                <tr v-if="localProfilLulusans.length === 0">
                                    <td colspan="3" class="px-6 py-8 text-center text-gray-500">Belum ada Profil Lulusan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Matrix Pemetaan PL -> CPL -->
                    <div v-if="localProfilLulusans.length > 0 && sortedLocalCpls.length > 0">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Pemetaan Profil Lulusan → CPL</h3>
                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-indigo-900 text-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold w-24 border-r border-indigo-800">Kode PL</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold min-w-[200px] border-r border-indigo-800">Profil Lulusan</th>
                                        <template v-for="cpl in sortedLocalCpls" :key="cpl.id">
                                            <th class="px-2 py-3 text-center text-xs font-bold border-r border-indigo-800 min-w-[60px]" :title="cpl.deskripsi">
                                                {{ cpl.kode }}
                                            </th>
                                        </template>
                                        <th class="px-4 py-3 text-center text-xs font-bold w-20">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                    <tr v-for="pl in localProfilLulusans" :key="pl.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 text-sm font-bold text-gray-900 border-r border-gray-100 dark:border-gray-700">{{ pl.kode }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 border-r border-gray-100 dark:border-gray-700">{{ pl.deskripsi }}</td>
                                        <template v-for="cpl in sortedLocalCpls" :key="cpl.id">
                                            <td class="p-2 border-r border-gray-100 dark:border-gray-700 text-center">
                                                <input 
                                                    type="number" 
                                                    :value="getPlScore(pl, cpl.id)"
                                                    @input="updatePlMapping(pl, cpl.id, $event.target.value)"
                                                    class="w-full border border-gray-200 dark:border-gray-600 rounded-md text-center text-sm py-1.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 dark:bg-gray-800 placeholder-gray-300 transition-all font-medium"
                                                    placeholder="-"
                                                >
                                            </td>
                                        </template>
                                        <td class="px-4 py-3 text-center font-bold text-indigo-600 bg-gray-50 dark:bg-gray-900/50">
                                            {{ calculatePlTotal(pl) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CPL Tab (Inline Editing) -->
            <div v-if="activeTab === 'cpl'" class="space-y-4">
                <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Capaian Pembelajaran Lulusan</h2>
                        <span class="text-sm text-gray-500 font-medium">{{ localCpls.length }} item terdaftar</span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50/80 dark:bg-gray-800/80 border-b border-gray-100 dark:border-gray-700">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Kode</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider w-1/2">Deskripsi</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 uppercase tracking-wider w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-for="cpl in localCpls" :key="cpl.id || cpl._tempId" class="group hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <!-- View Mode -->
                                    <template v-if="!cpl.isEditing">
                                        <td class="px-6 py-4 px-6 md:px-8 py-4 align-top">
                                            <span class="font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-lg border border-gray-200 dark:border-gray-700 text-sm">{{ cpl.kode }}</span>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <span :class="['px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wide', getCategoryBadge(cpl.kategori)]">
                                                {{ cpl.kategori?.replace('_', ' ') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <p class="text-gray-600 dark:text-gray-300 text-sm leading-relaxed">{{ cpl.deskripsi }}</p>
                                        </td>
                                        <td class="px-6 py-4 align-top text-center">
                                            <div class="flex items-center justify-center gap-1 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-all duration-200">
                                                <button @click="editCpl(cpl)" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" title="Edit">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </button>
                                                <button @click="deleteCpl(cpl)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="Hapus">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </template>

                                    <!-- Edit Mode -->
                                    <template v-else>
                                        <td class="px-6 py-4 align-top">
                                            <input v-model="cpl.editData.kode" type="text" placeholder="Kode" class="w-full px-3 py-2 text-sm rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-shadow">
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <select v-model="cpl.editData.kategori" class="w-full px-3 py-2 text-sm rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-shadow cursor-pointer">
                                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <textarea v-model="cpl.editData.deskripsi" rows="2" placeholder="Deskripsi..." class="w-full px-3 py-2 text-sm rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-shadow resize-none"></textarea>
                                        </td>
                                        <td class="px-6 py-4 align-top text-center">
                                            <div class="flex items-center justify-center gap-2 pt-1">
                                                <button @click="saveCpl(cpl)" :disabled="cpl.isSaving" class="p-2 bg-green-50 text-green-600 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400 rounded-lg transition-colors shadow-sm disabled:opacity-50">
                                                    <svg v-if="!cpl.isSaving" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <svg v-else class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                </button>
                                                <button @click="cancelCpl(cpl)" class="p-2 bg-gray-50 text-gray-500 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 rounded-lg transition-colors shadow-sm">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Button -->
                <button @click="addCplRow" class="w-full py-4 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-2xl text-gray-500 hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-all font-medium flex items-center justify-center gap-2 group">
                    <span class="bg-gray-200 dark:bg-gray-700 rounded-full p-1 text-gray-500 dark:text-gray-400 group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </span>
                    Tambah CPL Baru
                </button>
            </div>

            <!-- Matrix CPL-MK Tab -->
            <div v-if="activeTab === 'matrix'" class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Pemetaan CPL → Mata Kuliah</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50/80 dark:bg-gray-800/80 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Semester</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Kode MK</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Mata Kuliah</th>
                                <th v-for="cpl in sortedLocalCpls.filter(c => !c.isEditing)" :key="cpl.id" class="px-3 py-4 text-center text-xs font-bold text-gray-400 uppercase min-w-[60px]">
                                    {{ cpl.kode }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="mk in kurikulum.mata_kuliahs" :key="mk.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-6 py-4 text-sm font-medium text-gray-500">{{ mk.semester }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ mk.kode }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ mk.nama }}</td>
                                <td v-for="cpl in sortedLocalCpls.filter(c => !c.isEditing)" :key="cpl.id" class="px-3 py-4 text-center border-l border-gray-100 dark:border-gray-800">
                                    <input type="checkbox" 
                                        :checked="isAssigned(cpl.id, mk.id)" 
                                        @change="toggleAssignment(cpl, mk)"
                                        class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!kurikulum.mata_kuliahs?.length" class="p-12 text-center text-gray-400">
                    Belum ada Mata Kuliah yang ditambahkan ke kurikulum ini
                </div>
            </div>

            <!-- MK Tab -->
            <div v-if="activeTab === 'mk'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Mata Kuliah dalam Kurikulum</h2>
                    <button @click="openAssignMkModal" class="px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-500/30 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah MK
                    </button>
                </div>

                <!-- MK By Semester -->
                <div v-for="sem in [1,2,3,4,5,6,7,8]" :key="sem">
                    <div v-if="getMkBySemester(sem).length > 0" class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden mb-6">
                        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="w-2 h-6 bg-indigo-500 rounded-full"></span>
                                Semester {{ sem }}
                            </h3>
                             <label class="flex items-center gap-2 text-sm text-gray-500 cursor-pointer select-none">
                                <input type="checkbox" @change="toggleSemesterSelect(sem, $event)" :checked="isSemesterSelected(sem)" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Pilih Semua
                            </label>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="mk in getMkBySemester(sem)" :key="mk.id" class="p-6 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors group" :class="{'bg-indigo-50/30 dark:bg-indigo-900/10': selectedMkIdsToRemove.includes(mk.id)}">
                                 <input type="checkbox" :value="mk.id" v-model="selectedMkIdsToRemove" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                
                                <div class="w-14 h-14 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl flex flex-col items-center justify-center text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800 flex-shrink-0">
                                    <span class="text-lg font-bold">{{ mk.sks_teori + mk.sks_praktik }}</span>
                                    <span class="text-[10px] uppercase font-bold tracking-wider">SKS</span>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-lg text-gray-900 dark:text-white mb-1">{{ mk.nama }}</h4>
                                    <p class="text-sm text-gray-500 font-medium flex items-center gap-3">
                                        <span class="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded text-xs border border-gray-200 dark:border-gray-700">{{ mk.kode }}</span>
                                        <span>•</span>
                                        <span class="capitalize">{{ mk.jenis }}</span>
                                    </p>
                                </div>
                                <div class="flex gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                    <button @click="openCpmkManager(mk)" class="px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        Kelola CPMK
                                    </button>
                                    <button @click="confirmRemoveMk(mk)" class="p-2 text-gray-400 hover:text-red-500 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Hapus dari Kurikulum">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bulk Remove Floating Bar -->
                 <Transition enter-active-class="transform ease-out duration-300 transition" enter-from-class="translate-y-full opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition ease-in duration-200" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-full opacity-0">
                    <div v-if="selectedMkIdsToRemove.length > 0" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 p-4 z-40 flex items-center gap-6 min-w-[320px]">
                        <div class="flex items-center gap-3">
                            <span class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">{{ selectedMkIdsToRemove.length }}</span>
                            <span class="font-medium text-gray-700 dark:text-gray-200">MK Dipilih</span>
                        </div>
                        <div class="h-8 w-px bg-gray-200 dark:bg-gray-700"></div>
                        <div class="flex gap-2">
                             <button @click="selectedMkIdsToRemove = []" class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl text-sm font-medium transition-colors text-gray-600 dark:text-gray-400">Batal</button>
                             <button @click="removeMkBulk" class="px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-red-500/30 hover:bg-red-700 transition-all">Hapus Terpilih</button>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- Assign MK Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showAssignMkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="showAssignMkModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-4xl w-full animate-modal-in overflow-hidden flex flex-col max-h-[90vh]">
                        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                            <div>
                                <h3 class="font-bold text-lg dark:text-white">Tambah Mata Kuliah</h3>
                                <p class="text-sm text-gray-500">Pilih mata kuliah dari berbagai program studi.</p>
                            </div>
                            <button @click="showAssignMkModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <!-- Filter Bar -->
                        <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 flex gap-4 items-center">
                            <div class="w-1/3">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Program Studi</label>
                                <select v-model="filterProdiId" class="w-full px-3 py-2 text-sm rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-shadow">
                                    <option v-for="prodi in prodis" :key="prodi.id" :value="prodi.id">
                                        {{ prodi.jenjang }} - {{ prodi.nama }}
                                    </option>
                                </select>
                            </div>
                            <div class="w-1/4">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Target Semester</label>
                                <select v-model="targetSemester" class="w-full px-3 py-2 text-sm rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-shadow">
                                    <option v-for="i in 8" :key="i" :value="i">Semester {{ i }}</option>
                                </select>
                            </div>
                            <div class="flex-1 text-right">
                                <span class="text-sm text-gray-500">{{ availableMks.length }} MK ditemukan</span>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto p-0">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 z-10 shadow-sm">
                                    <tr>
                                        <th class="p-4 border-b border-gray-100 dark:border-gray-700 w-12 text-center">
                                            <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </th>
                                        <th class="p-4 border-b border-gray-100 dark:border-gray-700 text-xs font-bold text-gray-500 uppercase">Kode</th>
                                        <th class="p-4 border-b border-gray-100 dark:border-gray-700 text-xs font-bold text-gray-500 uppercase w-1/3">Mata Kuliah</th>
                                        <th class="p-4 border-b border-gray-100 dark:border-gray-700 text-xs font-bold text-gray-500 uppercase text-center">SMT</th>
                                        <th class="p-4 border-b border-gray-100 dark:border-gray-700 text-xs font-bold text-gray-500 uppercase text-center">SKS</th>
                                        <th class="p-4 border-b border-gray-100 dark:border-gray-700 text-xs font-bold text-gray-500 uppercase">Jenis</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <tr v-if="isLoadingMk">
                                        <td colspan="6" class="p-8 text-center text-gray-500">
                                            <svg class="animate-spin h-6 w-6 text-indigo-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Memuat Data...
                                        </td>
                                    </tr>
                                    <tr v-else-if="availableMks.length === 0">
                                        <td colspan="6" class="p-8 text-center text-gray-500">Tidak ada MK tersedia untuk Prodi ini.</td>
                                    </tr>
                                    <tr v-for="mk in availableMks" :key="mk.id" class="hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 cursor-pointer transition-colors" @click="toggleSelect(mk.id)">
                                        <td class="p-4 text-center">
                                            <input type="checkbox" :value="mk.id" v-model="selectedAssignIds" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 pointers-events-none">
                                        </td>
                                        <td class="p-4 text-sm font-bold text-gray-700 dark:text-gray-300">{{ mk.kode }}</td>
                                        <td class="p-4 text-sm font-medium text-gray-900 dark:text-white">{{ mk.nama }}</td>
                                        <td class="p-4 text-sm text-center text-gray-500">{{ mk.semester }}</td>
                                        <td class="p-4 text-center">
                                            <span class="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded text-xs font-bold">{{ mk.sks_teori + mk.sks_praktik }}</span>
                                        </td>
                                        <td class="p-4 text-sm">
                                            <span class="capitalize px-2 py-1 rounded text-xs border" :class="mk.jenis === 'wajib' ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-amber-50 text-amber-700 border-amber-100'">{{ mk.jenis }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="p-6 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center">
                            <div class="text-sm text-gray-500">
                                <span v-if="selectedAssignIds.length > 0" class="font-bold text-indigo-600">{{ selectedAssignIds.length }} MK dipilih</span>
                                <span v-else>Pilih MK untuk ditambahkan</span>
                            </div>
                            <div class="flex gap-3">
                                <button @click="showAssignMkModal = false" class="px-5 py-2.5 text-gray-600 dark:text-gray-300 font-medium hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-colors">Batal</button>
                                <button @click="submitAssignMk" :disabled="selectedAssignIds.length === 0" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-indigo-500/30 transition-all">
                                    Tambahkan Terpilih
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- PL Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showPlModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="showPlModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-md w-full animate-modal-in overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-4 dark:text-white">{{ plForm.id ? 'Edit' : 'Tambah' }} Profil Lulusan</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kode PL</label>
                                    <input v-model="plForm.kode" type="text" class="w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg focus:ring-indigo-500 shadow-sm px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi Profil Lulusan</label>
                                    <textarea v-model="plForm.deskripsi" rows="3" class="w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg focus:ring-indigo-500 shadow-sm px-3 py-2"></textarea>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <button @click="showPlModal = false" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">Batal</button>
                                <button @click="submitPl" :disabled="isPlSaving" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span v-if="isPlSaving" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                                    <span v-else>Simpan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- CPMK Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showCpmkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="showCpmkModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-4xl w-full animate-modal-in overflow-hidden p-6 max-h-[85vh] overflow-y-auto">
                        <CpmkManager 
                            :kurikulum-id="kurikulum.id"
                            :mata-kuliah="selectedMkForCpmk"
                            :available-cpls="getAssignedCplsForMk(selectedMkForCpmk)"
                            @close="showCpmkModal = false"
                            @toast="showToast"
                        />
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Toast -->
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
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import CpmkManager from './Partials/CpmkManager.vue';

const props = defineProps({
    kurikulum: Object,
    prodis: { type: Array, default: () => [] },
    cplMkMapping: { type: Array, default: () => [] } // From controller
});

const activeTab = ref('cpl');
const localCpls = ref([...(props.kurikulum.cpls || [])]);
const toast = ref({ show: false, message: '', type: 'success' });
const assignedCplMk = ref(new Set(props.cplMkMapping));

const isAssigned = (cplId, mkId) => {
    return assignedCplMk.value.has(`${cplId}-${mkId}`);
};

const toggleAssignment = async (cpl, mk) => {
    if (!cpl.id || !mk.id) return;
    const key = `${cpl.id}-${mk.id}`;
    const attached = !assignedCplMk.value.has(key);
    
    // Optimistic Update
    if (attached) assignedCplMk.value.add(key);
    else assignedCplMk.value.delete(key);

    try {
        await axios.post(`/kurikulum/${props.kurikulum.id}/toggle-cpl-mk`, {
            cpl_id: cpl.id,
            mata_kuliah_id: mk.id,
            attached: attached
        });
        showToast('Mapping diperbarui');
    } catch (error) {
        // Revert on error
        if (attached) assignedCplMk.value.delete(key);
        else assignedCplMk.value.add(key);
        showToast('Gagal update mapping', 'error');
    }
};

const getAssignedCplsForMk = (mk) => {
    return localCpls.value.filter(c => c.id && !c.isEditing && isAssigned(c.id, mk.id));
};

// CPMK Modal State
const showCpmkModal = ref(false);
const selectedMkForCpmk = ref(null);

// Assign MK State
const showAssignMkModal = ref(false);
const availableMks = ref([]);
const isLoadingMk = ref(false);
const selectedAssignIds = ref([]);
const filterProdiId = ref(props.kurikulum.prodi_id);
const targetSemester = ref(1);

const openAssignMkModal = () => {
    showAssignMkModal.value = true;
    selectedAssignIds.value = [];
    filterProdiId.value = props.kurikulum.prodi_id;
    targetSemester.value = 1;
    fetchAvailableMks();
};

const fetchAvailableMks = async () => {
    isLoadingMk.value = true;
    try {
        const res = await axios.get(`/kurikulum/${props.kurikulum.id}/available-mk`, {
            params: { prodi_id: filterProdiId.value }
        });
        availableMks.value = res.data;
    } catch (e) {
        showToast('Gagal memuat daftar Mata Kuliah', 'error');
    } finally {
        isLoadingMk.value = false;
    }
};

watch(filterProdiId, () => {
    fetchAvailableMks();
});

const isAllSelected = computed(() => {
    return availableMks.value.length > 0 && selectedAssignIds.value.length === availableMks.value.length;
});

// Profil Lulusan Logic
const localProfilLulusans = ref(props.kurikulum.profil_lulusans || []);
const sortedLocalCpls = computed(() => {
    return [...localCpls.value].sort((a, b) => {
        return a.kode.localeCompare(b.kode, undefined, { numeric: true });
    });
});

const showPlModal = ref(false);
const plForm = ref({ id: null, kode: '', deskripsi: '' });
const isPlSaving = ref(false);

const openPlModal = (pl = null) => {
    if (pl) {
        plForm.value = { ...pl };
    } else {
        plForm.value = { id: null, kode: '', deskripsi: '' };
    }
    showPlModal.value = true;
};

const submitPl = async () => {
    isPlSaving.value = true;
    try {
        let response;
        if (plForm.value.id) {
            response = await axios.put(`/kurikulum/pl/${plForm.value.id}`, plForm.value);
            const idx = localProfilLulusans.value.findIndex(p => p.id === plForm.value.id);
            if (idx !== -1) {
                localProfilLulusans.value[idx] = { ...localProfilLulusans.value[idx], ...response.data.profil_lulusan };
            }
            showToast(response.data.message || 'Profil Lulusan diperbarui');
        } else {
            response = await axios.post(`/kurikulum/${props.kurikulum.id}/pl`, plForm.value);
            if (response.data.profil_lulusan) {
                localProfilLulusans.value.push(response.data.profil_lulusan);
            }
            showToast(response.data.message || 'Profil Lulusan ditambahkan');
        }
        showPlModal.value = false;
    } catch (e) {
        showToast('Gagal simpan Profil Lulusan', 'error');
    } finally {
        isPlSaving.value = false;
    }
};

const deletePl = async (pl) => {
    if (!confirm('Hapus Profil Lulusan ini?')) return;
    try {
        await axios.delete(`/kurikulum/pl/${pl.id}`);
        localProfilLulusans.value = localProfilLulusans.value.filter(p => p.id !== pl.id);
        showToast('Profil Lulusan dihapus');
    } catch (e) {
        showToast('Gagal hapus', 'error');
    }
};

const getPlScore = (pl, cplId) => {
    const relation = pl.cpls?.find(c => c.id === cplId);
    return relation?.pivot?.skor ?? ''; // Return empty string if null
};

// Debounced mapping update
const updatePlMapping = (pl, cplId, value) => {
    // Optimistic Update locally
    const cplIndex = pl.cpls ? pl.cpls.findIndex(c => c.id === cplId) : -1;
    if (cplIndex !== -1) {
         if (value === '' || value == 0) pl.cpls.splice(cplIndex, 1);
         else pl.cpls[cplIndex].pivot.skor = value;
    } else if (value !== '' && value != 0) {
        if (!pl.cpls) pl.cpls = [];
        pl.cpls.push({ id: cplId, pivot: { skor: value } });
    }

    sendMappingUpdate(pl.id, cplId, value);
};

// Use lodash debounce if available, or simple timeout
let mappingTimeout;
const sendMappingUpdate = (plId, cplId, value) => {
    if (mappingTimeout) clearTimeout(mappingTimeout);
    mappingTimeout = setTimeout(async () => {
        try {
            await axios.post(`/kurikulum/pl/${plId}/mapping`, { cpl_id: cplId, skor: value });
        } catch(e) { console.error(e); }
    }, 800);
};

const calculatePlTotal = (pl) => {
    if (!pl.cpls) return 0;
    return pl.cpls.reduce((sum, c) => sum + (parseFloat(c.pivot?.skor) || 0), 0);
};

// MK Removal Logic
const selectedMkIdsToRemove = ref([]);

const toggleSemesterSelect = (semester, event) => {
    const mkInSemester = getMkBySemester(semester).map(mk => mk.id);
    if (event.target.checked) {
        selectedMkIdsToRemove.value = [...new Set([...selectedMkIdsToRemove.value, ...mkInSemester])];
    } else {
        selectedMkIdsToRemove.value = selectedMkIdsToRemove.value.filter(id => !mkInSemester.includes(id));
    }
};

const isSemesterSelected = (semester) => {
    const mkInSemester = getMkBySemester(semester).map(mk => mk.id);
    return mkInSemester.length > 0 && mkInSemester.every(id => selectedMkIdsToRemove.value.includes(id));
};

const confirmRemoveMk = (mk) => {
    if (confirm(`Yakin hapus ${mk.nama} dari Kurikulum ini?`)) {
        router.delete(`/kurikulum/${props.kurikulum.id}/remove-mk/${mk.id}`, {
            onSuccess: () => showToast('MK berhasil dihapus')
        });
    }
};

const removeMkBulk = () => {
    if (!confirm(`Yakin hapus ${selectedMkIdsToRemove.value.length} MK terpilih?`)) return;
    router.post(`/kurikulum/${props.kurikulum.id}/remove-mk-bulk`, { mata_kuliah_ids: selectedMkIdsToRemove.value }, {
        onSuccess: () => {
             selectedMkIdsToRemove.value = [];
             showToast('MK terpilih dihapus');
        }
    });
};

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedAssignIds.value = [];
    } else {
        selectedAssignIds.value = availableMks.value.map(mk => mk.id);
    }
};

const toggleSelect = (id) => {
    const idx = selectedAssignIds.value.indexOf(id);
    if (idx === -1) selectedAssignIds.value.push(id);
    else selectedAssignIds.value.splice(idx, 1);
};

const submitAssignMk = () => {
    if (selectedAssignIds.value.length === 0) return;
    router.post(`/kurikulum/${props.kurikulum.id}/assign-mk`, {
        mata_kuliah_ids: selectedAssignIds.value,
        semester: targetSemester.value
    }, {
        onSuccess: () => {
            showAssignMkModal.value = false;
            showToast('Mata Kuliah berhasil ditambahkan');
        },
        onError: () => showToast('Gagal menambahkan Mata Kuliah', 'error')
    });
};

const tabs = [
    { id: 'profil_lulusan', label: 'Profil Lulusan' },
    { id: 'cpl', label: 'CPL' },
    { id: 'matrix', label: 'Matrix' },
    { id: 'mk', label: 'Mata Kuliah' },
];

const categories = [
    { value: 'sikap', label: 'Sikap', class: 'bg-blue-100 text-blue-800' },
    { value: 'pengetahuan', label: 'Pengetahuan', class: 'bg-green-100 text-green-800' },
    { value: 'keterampilan_umum', label: 'Keterampilan Umum', class: 'bg-orange-100 text-orange-800' },
    { value: 'keterampilan_khusus', label: 'Keterampilan Khusus', class: 'bg-purple-100 text-purple-800' },
];

const getCategoryBadge = (catVal) => {
    const cat = categories.find(c => c.value === catVal);
    return cat ? cat.class : 'bg-gray-100 text-gray-800';
};

const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => toast.value.show = false, 3000);
};

// CPL Inline Edit Logic
const addCplRow = () => {
    localCpls.value.push({
        id: null,
        _tempId: Date.now(),
        kode: '',
        kategori: 'pengetahuan',
        deskripsi: '',
        urutan: localCpls.value.length + 1,
        isEditing: true,
        editData: { kode: '', kategori: 'pengetahuan', deskripsi: '' }
    });
};

const editCpl = (cpl) => {
    cpl.editData = { kode: cpl.kode, kategori: cpl.kategori, deskripsi: cpl.deskripsi };
    cpl.isEditing = true;
};

const cancelCpl = (cpl) => {
    if (!cpl.id) {
        localCpls.value = localCpls.value.filter(item => item !== cpl);
    } else {
        cpl.isEditing = false;
    }
};

const saveCpl = async (cpl) => {
    if (!cpl.editData.kode || !cpl.editData.deskripsi) {
        showToast('Kode dan Deskripsi wajib diisi', 'error');
        return;
    }
    
    cpl.isSaving = true;
    try {
        let response;
        if (cpl.id) {
            response = await axios.put(`/kurikulum/cpl/${cpl.id}`, cpl.editData);
        } else {
            response = await axios.post(`/kurikulum/${props.kurikulum.id}/cpl`, cpl.editData);
        }
        Object.assign(cpl, response.data.cpl);
        cpl.isEditing = false;
        cpl.isSaving = false;
        showToast(response.data.message, 'success');
    } catch (error) {
        cpl.isSaving = false;
        showToast('Gagal menyimpan CPL', 'error');
    }
};

const deleteCpl = async (cpl) => {
    if(!confirm(`Hapus CPL ${cpl.kode}?`)) return;
    try {
        await axios.delete(`/kurikulum/cpl/${cpl.id}`);
        localCpls.value = localCpls.value.filter(item => item.id !== cpl.id);
        showToast('CPL terhapus', 'success');
    } catch (error) {
        showToast('Gagal hapus', 'error');
    }
};

const getMkBySemester = (semester) => {
    return (props.kurikulum.mata_kuliahs || []).filter(mk => mk.semester === semester);
};

// CPMK Modal
const openCpmkManager = (mk) => {
    selectedMkForCpmk.value = mk;
    showCpmkModal.value = true;
};

</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.animate-modal-in { animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes modalIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.bg-grid-white\/10 { background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
</style>
