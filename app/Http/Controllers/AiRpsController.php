<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Rps;
use App\Models\Cpmk;
use App\Models\SubCpmk;
use App\Models\MataKuliah;
use App\Models\Semester;
use App\Services\AiService;

class AiRpsController extends Controller
{
    /**
     * Store OpenAI API Key for current user (Legacy - can be removed or kept for per-user override)
     */
    public function storeSettings(Request $request)
    {
        $request->validate([
            'openai_api_key' => 'nullable|string',
        ]);

        $user = Auth::user();
        $user->openai_api_key = $request->openai_api_key;
        $user->save();

        return back()->with('success', 'API Key disimpan.');
    }

    /**
     * Complete RPS Generation
     */
    public function generateComplete(Request $request, AiService $aiService)
    {
        $request->validate([
            'mata_kuliah_id' => 'required|exists:mata_kuliahs,id',
            'mode' => 'required|in:by_data,manual',
            'topics' => 'required_if:mode,manual|nullable|string',
        ]);

        // Get Active Semester
        $activeSemester = Semester::active()->first();
        if (!$activeSemester) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada semester aktif. Harap set semester aktif terlebih dahulu.'
            ], 400);
        }

        // Load Mata Kuliah
        $mk = MataKuliah::with('prodi')->findOrFail($request->mata_kuliah_id);

        // Determine Topics
        $topics = $request->topics;
        if ($request->mode === 'by_data') {
            $topics = "Generate topik berdasarkan Deskripsi Mata Kuliah: {$mk->deskripsi}. Pastikan mencakup seluruh materi standar untuk mata kuliah ini.";
        }

        // Get or create RPS for ACTIVE SEMESTER
        $rps = Rps::firstOrCreate(
            ['mata_kuliah_id' => $mk->id, 'semester_id' => $activeSemester->id],
            [
                'dosen_id' => Auth::id(),
                'status' => 'draft',
                'nomor' => 'RPS-' . $mk->kode . '-' . $activeSemester->kode,
                'tanggal_penyusunan' => now(),
            ]
        );

        // Get ALL CPMKs with Sub-CPMKs
        $cpmks = Cpmk::where('mata_kuliah_id', $mk->id)
            ->with(['subCpmks'])
            ->orderBy('kode')
            ->get();

        if ($cpmks->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada CPMK untuk Mata Kuliah ini. Silakan generate CPMK di menu Kurikulum terlebih dahulu.'
            ], 400);
        }

        // Build prompt with EXISTING Sub-CPMKs
        $prompt = $this->buildPrompt($mk, $topics, $cpmks);
        $systemPrompt = $this->getSystemPrompt();

        try {
            // Use AiService
            $content = $aiService->generate($prompt, ['system_prompt' => $systemPrompt]);

            // Clean response
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);
            $content = trim($content);

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('AI JSON Parse Error', ['content' => $content]);
                return response()->json([
                    'success' => false,
                    'message' => 'AI menghasilkan format yang tidak valid. Coba lagi.'
                ], 500);
            }

            // Save to database
            return $this->saveGeneratedData($rps, $cpmks, $data);

        } catch (\Exception $e) {
            Log::error('AI Generation Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system prompt for AI
     */
    private function getSystemPrompt(): string
    {
        return "Anda adalah asisten ahli kurikulum perguruan tinggi Indonesia.
TUGAS: Buat RPS LENGKAP berbasis OBE (Outcome Based Education) format JSON untuk 16 Pertemuan.

ATURAN PENTING:
1. Pertemuan 8 = UTS, Pertemuan 16 = UAS.
2. Gunakan Sub-CPMK yang DISEDIAKAN dalam prompt. JANGAN MEMBUAT SUB-CPMK BARU. Pilih dari daftar db.
3. Petakan 'sub_cpmk_code_ref' di setiap pertemuan sesuai dengan kode yang diberikan.
4. Total bobot nilai 'details' HARUS PERSIS 100%. (UTS=30, UAS=30, Tugas=40 misalnya).
5. Pustaka WAJIB minimal 15-20 item, format APA style.
   Harus terdiri dari: Jurnal Nasional Indonesia, Jurnal Internasional, Buku, dan Website.

OUTPUT FORMAT (JSON):
{
    \"deskripsi\": \"...\",
    \"bahan_kajian\": \"...\",
    \"pustaka_utama\": \"...\",
    \"pustaka_pendukung\": \"...\",
    \"details\": [
        {
            \"pertemuan\": 1, 
            \"materi\": \"...\", 
            \"metode\": \"...\", 
            \"indikator\": \"...\", 
            \"bobot_nilai\": 5, 
            \"sub_cpmk_code_ref\": \"KODE_SUB_CPMK_DARI_LIST\"
        }
    ]
}";
    }

    /**
     * Build user prompt with MK context
     */
    private function buildPrompt(MataKuliah $mk, string $topics, $cpmks): string
    {
        $sks = ($mk->sks_teori ?? 0) + ($mk->sks_praktik ?? 0);
        $prodi = $mk->prodi->nama ?? 'Program Studi';

        // Format CPMK & Sub-CPMK List
        $cpmkList = $cpmks->map(function ($c) {
            $subs = $c->subCpmks->map(fn($s) => "    - [{$s->kode}] {$s->deskripsi}")->join("\n");
            return "- {$c->kode}: {$c->deskripsi}\n{$subs}";
        })->join("\n");

        return "Buatkan RPS berbasis OBE untuk mata kuliah:

INFORMASI:
- Nama: {$mk->nama} ({$mk->kode})
- SKS: {$sks}
- Prodi: {$prodi}

DAFTAR SUB-CPMK YANG TERSEDIA (Gunakan kode dalam kurung siku [...] untuk referensi):
{$cpmkList}

TOPIK/CAKUPAN:
{$topics}

INSTRUKSI UTAMA (WAJIB DIPATUHI):
1. Susun 16 pertemuan (Pertemuan 8 UTS, Pertemuan 16 UAS).
2. TUGAS DISTRIBUSI: Anda memiliki 14 SLOT pertemuan efektif (1-7 dan 9-15).
   - Gunakan daftar Sub-CPMK di atas untuk mengisi KE-14 slot tersebut.
   - JIKA jumlah Sub-CPMK lebih sedikit dari 14, MAKA SATU SUB-CPMK HARUS DIGUNAKAN UNTUK BEBERAPA PERTEMUAN BERTURUT-TURUT.
   - Contoh: Sub-CPMK-1 digunakan di Pertemuan 1 (Materi A) dan Pertemuan 2 (Materi B).
   - JANGAN HANYA MEMASUKKAN SATU KALI LALU HABIS. Distribusikan secara proporsional agar materi mendalam.
3. Setiap pertemuan (Kecuali 8 & 16) WAJIB memiliki 'sub_cpmk_code_ref' dari daftar.
4. Buat 'materi' perkuliahan BERDASARKAN Deskripsi Sub-CPMK yang kamu plot pada pertemuan tersebut.
5. 'details' bobot harus total 100%.
6. PUSTAKA (DAFTAR PUSTAKA) - SANGAT KRUSIAL:
   - WAJIB TOTAL MINIMAL 15-20 ITEM. (JIKA KURANG DARI 15 SAYA AKAN MERASA GAGAL).
   - KOMPOSISI WAJIB:
     a. Pustaka Utama (Minimal 8): Buku Teks, Jurnal Ilmiah Bereputasi.
     b. Pustaka Pendukung (Minimal 7): Artikel Jurnal, Website Resmi, Peraturan/UU.
   - FORMAT: APA STYLE.
   - JANGAN HANYA MENULIS 2-3!!! TULISKAN DAFTAR PANJANG.
   - Contoh Output Pustaka Utama:
     1. Author A. (2020)...
     2. Author B. (2019)...
     (dan seterusnya sampai minimal 8)
   - Contoh Output Pustaka Pendukung:
     1. Author X. (2021)...
     2. Author Y. (2022)...
     (dan seterusnya sampai minimal 7)
7. 'details' bobot harus total 100%.

";
    }

    /**
     * Save generated data to database
     */
    private function saveGeneratedData(Rps $rps, $cpmks, array $data)
    {
        DB::beginTransaction();

        try {
            // 1. Update RPS info
            $rps->update([
                'deskripsi' => $data['deskripsi'] ?? null,
                'bahan_kajian' => $data['bahan_kajian'] ?? null,
                'pustaka_utama' => $data['pustaka_utama'] ?? null,
                'pustaka_pendukung' => $data['pustaka_pendukung'] ?? null,
                'tanggal_penyusunan' => now(),
            ]);

            // 2. Map Sub-CPMK Codes to IDs
            // We need a map of Code -> ID for all existing Sub-CPMKs
            $subCpmkMap = [];
            foreach ($cpmks as $cpmk) {
                foreach ($cpmk->subCpmks as $sub) {
                    $subCpmkMap[$sub->kode] = $sub->id;
                    // Also fuzzy match by stripping whitespace or case if needed
                    $subCpmkMap[strtoupper($sub->kode)] = $sub->id;
                }
            }

            // Allow matching by index or simple code if AI hallucinates format
            // But relying on Prompt to return exact code is better.

            // 3. Create/Update RPS Details
            $rawDetails = $data['details'] ?? [];

            // Ensure 1-16 meetings exist
            $detailsData = [];
            $aiDetailsMap = [];
            foreach ($rawDetails as $row) {
                if (isset($row['pertemuan'])) {
                    $aiDetailsMap[$row['pertemuan']] = $row;
                }
            }

            for ($i = 1; $i <= 16; $i++) {
                if (isset($aiDetailsMap[$i])) {
                    $d = $aiDetailsMap[$i];
                    // Force naming for UTS/UAS if weird
                    if ($i == 8 && stripos($d['materi'], 'UTS') === false)
                        $d['materi'] = "Ujian Tengah Semester (UTS)";
                    if ($i == 16 && stripos($d['materi'], 'UAS') === false)
                        $d['materi'] = "Ujian Akhir Semester (UAS)";
                    $detailsData[] = $d;
                } else {
                    // Inject missing meeting
                    $isUts = ($i == 8);
                    $isUas = ($i == 16);
                    $materiName = $isUts ? 'Ujian Tengah Semester (UTS)' : ($isUas ? 'Ujian Akhir Semester (UAS)' : "Pertemuan $i");
                    $bobotDefault = ($isUts || $isUas) ? 15 : 5; // Placeholder

                    $detailsData[] = [
                        'pertemuan' => $i,
                        'materi' => $materiName,
                        'metode' => $isUts || $isUas ? 'Ujian' : 'Kuliah',
                        'indikator' => '',
                        'bobot_nilai' => $bobotDefault,
                        'sub_cpmk_code_ref' => '' // No Sub-CPMK for missing ones
                    ];
                }
            }

            // Normalization: Ensure Total Bobot is exactly 100%
            $totalBobot = array_sum(array_column($detailsData, 'bobot_nilai'));
            if ($totalBobot > 0 && abs($totalBobot - 100) > 0.01) {
                $runningTotal = 0;
                $count = count($detailsData);
                foreach ($detailsData as $i => &$d) {
                    if ($i === $count - 1) {
                        // Last item absorbs the remainder to force 100
                        $d['bobot_nilai'] = max(0, 100 - $runningTotal);
                    } else {
                        // Scale to 100 then round to nearest 0.5
                        $scaled = ($d['bobot_nilai'] / $totalBobot) * 100;
                        $normalized = round($scaled * 2) / 2;
                        $d['bobot_nilai'] = $normalized;
                        $runningTotal += $normalized;
                    }
                }
            } else if ($totalBobot == 0 && count($detailsData) > 0) {
                // If AI gave 0, distribute evenly with 0.5 steps
                $avg = 100 / count($detailsData);
                $roundedAvg = round($avg * 2) / 2;
                $runningTotal = 0;
                foreach ($detailsData as $i => &$d) {
                    if ($i === count($detailsData) - 1) {
                        $d['bobot_nilai'] = max(0, 100 - $runningTotal);
                    } else {
                        $d['bobot_nilai'] = $roundedAvg;
                        $runningTotal += $roundedAvg;
                    }
                }
            }

            // But let's keep basic normalization strictly to 100 if user requested.
            // ... Assuming user can edit.

            $rps->details()->delete(); // Reset details for this generated session? Or updateOrCreate?
            // "data RPS akan 0 kembali" implied fresh start for new generation.
            // Using delete() ensures clean slate.

            // FALLBACK & SAVING LOOP
            $lastValidSubCpmkId = (!empty($subCpmkMap)) ? reset($subCpmkMap) : null; // Default to first if all fail

            foreach ($detailsData as $detail) {
                $subCpmkId = null;
                $pertemuan = $detail['pertemuan'];

                // Skip Sub-CPMK for UTS/UAS
                if ($pertemuan == 8 || $pertemuan == 16) {
                    $subCpmkId = null;
                } else {
                    $ref = $detail['sub_cpmk_code_ref'] ?? '';
                    // Normalize: Remove brackets causing mismatch
                    $ref = trim(str_replace(['[', ']'], '', $ref));

                    if ($ref && isset($subCpmkMap[$ref])) {
                        $subCpmkId = $subCpmkMap[$ref];
                    } elseif ($ref && isset($subCpmkMap[strtoupper($ref)])) {
                        $subCpmkId = $subCpmkMap[strtoupper($ref)];
                    }

                    // FALLBACK: If still null, use last valid
                    if (!$subCpmkId && $lastValidSubCpmkId) {
                        $subCpmkId = $lastValidSubCpmkId;
                    }

                    // Update last valid
                    if ($subCpmkId) {
                        $lastValidSubCpmkId = $subCpmkId;
                    }
                }

                $rps->details()->create([
                    'pertemuan' => $detail['pertemuan'],
                    'materi' => $detail['materi'] ?? '',
                    'metode' => $detail['metode'] ?? '',
                    'indikator' => $detail['indikator'] ?? '',
                    'bobot_nilai' => $detail['bobot_nilai'] ?? 0,
                    'sub_cpmk_id' => $subCpmkId,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'RPS berhasil di-generate!',
                'rps_id' => $rps->id,
                'redirect_url' => route('rps.edit', $rps->id), // Ensure route exists
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DB Save Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Database Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
