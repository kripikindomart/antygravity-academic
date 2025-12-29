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

class AiRpsController extends Controller
{
    /**
     * Store OpenAI API Key for current user
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
     * Complete RPS Generation - Simplified endpoint
     * Accepts mata_kuliah_id, auto-creates RPS if needed, generates everything
     */
    public function generateComplete(Request $request)
    {
        $request->validate([
            'mata_kuliah_id' => 'required|exists:mata_kuliahs,id',
            'topics' => 'required|string|min:10',
            'model' => 'nullable|string|in:gpt-4o,gpt-3.5-turbo,gpt-4-turbo',
        ]);

        // Get API Key
        $apiKey = Auth::user()->openai_api_key;
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key belum diset. Silakan isi di Pengaturan AI.'
            ], 400);
        }

        // Load Mata Kuliah
        $mk = MataKuliah::with('prodi')->findOrFail($request->mata_kuliah_id);
        $topics = $request->topics;
        $model = $request->model ?: 'gpt-3.5-turbo';

        // Get or create RPS
        $rps = Rps::firstOrCreate(
            ['mata_kuliah_id' => $mk->id, 'semester_id' => 1],
            [
                'dosen_id' => Auth::id(),
                'status' => 'draft',
                'nomor' => 'RPS-' . date('YmdHis'),
                'tanggal_penyusunan' => now(),
            ]
        );

        // Get ALL CPMKs for this MK
        $cpmks = Cpmk::where('mata_kuliah_id', $mk->id)->orderBy('kode')->get();
        if ($cpmks->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada CPMK untuk Mata Kuliah ini. Hubungi Kaprodi untuk menambahkan CPMK.'
            ], 400);
        }

        // Build comprehensive prompt with CPMK list
        $prompt = $this->buildPrompt($mk, $topics, $cpmks);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(120)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->getSystemPrompt()],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 4000,
                ]);

            if ($response->failed()) {
                Log::error('OpenAI API Error', ['body' => $response->body()]);
                return response()->json([
                    'success' => false,
                    'message' => 'OpenAI Error: ' . $response->json('error.message', 'Unknown error')
                ], 500);
            }

            $content = $response->json('choices.0.message.content');
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
        return "Anda adalah asisten ahli kurikulum perguruan tinggi Indonesia yang berpengalaman menyusun Rencana Pembelajaran Semester (RPS) sesuai standar KKNI, SN-DIKTI, dan Outcome Based Education (OBE).

TUGAS: Buat RPS LENGKAP dalam format JSON yang valid (tanpa markdown).

ATURAN PENTING:
1. Pertemuan 8 = UTS (Ujian Tengah Semester)
2. Pertemuan 16 = UAS (Ujian Akhir Semester)
3. Distribusikan topik ke pertemuan 1-7 dan 9-15 secara logis dan progresif.
4. Distribusikan Sub-CPMK secara merata ke SEMUA kode CPMK yang tersedia (jangan hanya ke satu CPMK). Setiap CPMK harus memiliki minimal 1-2 Sub-CPMK.
5. Buat total 8-12 Sub-CPMK yang spesifik dan terukur.
6. Metode pembelajaran harus bervariasi (Case Study, Project Based Learning, Diskusi, dll).
7. Total bobot nilai HARUS PERSIS 100%. HITUNG ULANG SEBELUM OUTPUT. (UTS=30%, UAS=30-40%, Tugas/Lainny=30-40%, pastikan total sum = 100). Validasi penjumlahan bobot harus 100.
8. PUSTAKA WAJIB JURNAL DAN BUKU (MINIMAL 10-15 REFERENSI TOTAL):
   - JANGAN HANYA MENULIS NAMA JURNAL! ANDA HARUS MENGUTIP JUDUL ARTIKEL SPESIFIK.
   - SALAH: \"Journal of Business Research. (2021). Elsevier.\" (INI DILARANG!)
   - BENAR: \"Anderson, J. (2021). The Impact of AI on Business. Journal of Business Research, 10(2), 45-60.\"
   - WAJIB ADA Minimal 5 Jurnal Nasional (Indonesia).
   - WAJIB ADA Minimal 5 Jurnal Internasional.
   - Format Referensi: Penulis. (Tahun). Judul Artikel/Buku. Penerbit/Jurnal.
9. Setiap indikator harus SMART (Specific, Measurable, Achievable, Relevant, Time-bound).

OUTPUT FORMAT (JSON only, no markdown):
{
    \"deskripsi\": \"Deskripsi mata kuliah 2-3 paragraf\",
    \"bahan_kajian\": \"Daftar bahan kajian...\",
    \"pustaka_utama\": \"1. Penulis (Tahun). Judul. Kota: Penerbit. (Buku)\\n2. [Judul Buku Lain]...\\n... (Min 5 Item)\",
    \"pustaka_pendukung\": \"1. Penulis (Tahun). Judul Artikel. Nama Jurnal. (Jurnal Nasional)\\n2. ...\\n3. ...\\n4. ...\\n5. ... (Min 5-10 Item)\",
    \"sub_cpmks\": [
        {
            \"text_cpmk_terkait\": \"Kode CPMK yang sesuai (misal CPMK-01)\",
            \"kode\": \"SC-01\", 
            \"deskripsi\": \"Mahasiswa mampu...\"
        }
    ],
    \"details\": [
        {\"pertemuan\": 1, \"materi\": \"...\", \"metode\": \"...\", \"indikator\": \"...\", \"bobot_nilai\": 5, \"sub_cpmk_code_ref\": \"SC-01\"}
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

        // Format CPMK list string
        $cpmkList = $cpmks->map(function ($c) {
            return "- {$c->kode}: {$c->deskripsi}";
        })->join("\n");

        return "Buatkan RPS berbasis OBE untuk mata kuliah berikut:

INFORMASI MATA KULIAH:
- Nama: {$mk->nama}
- Kode: {$mk->kode}
- SKS: {$sks} ({$mk->sks_teori} Teori + {$mk->sks_praktik} Praktik)
- Program Studi: {$prodi}

DAFTAR CPMK YANG TERSEDIA (Petakan Sub-CPMK ke kode-kode ini):
{$cpmkList}

TOPIK/SILABUS YANG HARUS DICAKUP:
{$topics}

INSTRUKSI KHUSUS:
1. Pastikan Sub-CPMK terdistribusi ke CPMK-CPMK di atas.
2. Referensi (Pustaka) minimal 10-15 sumber (Jurnal Nasional/Internasional & Buku).
3. Total Bobot Penilaian = 100%.

Buatkan RPS lengkap dengan 16 pertemuan.";
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

            // Map CPMKs by Kode for easy lookup
            $cpmkMapByKode = $cpmks->pluck('id', 'kode'); // ['CPMK-01' => 10, 'CPMK-02' => 11]

            // Allow fuzzy matching if AI creates slight variations
            $firstCpmkId = $cpmks->first()->id;

            // 2. Create Sub-CPMKs with unique codes
            $subCpmkIdMap = []; // Maps AI 'SC-01' or index to DB ID
            $timestamp = now()->format('His'); // Unique per generation

            foreach ($data['sub_cpmks'] ?? [] as $index => $sc) {
                // Determine Parent CPMK ID
                $parentCpmkId = $firstCpmkId; // Default fallback

                if (isset($sc['text_cpmk_terkait'])) {
                    $aiCpmkKode = strtoupper(trim($sc['text_cpmk_terkait']));
                    // Try exact match
                    if ($cpmkMapByKode->has($aiCpmkKode)) {
                        $parentCpmkId = $cpmkMapByKode->get($aiCpmkKode);
                    } else {
                        // Try matching first 7 chars (e.g. CPMK-01)
                        foreach ($cpmkMapByKode as $code => $id) {
                            if (strpos($aiCpmkKode, $code) !== false) {
                                $parentCpmkId = $id;
                                break;
                            }
                        }
                    }
                }

                $kode = 'SC-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT) . '-' . $timestamp;

                // Create Sub-CPMK
                $created = SubCpmk::create([
                    'cpmk_id' => $parentCpmkId,
                    'kode' => $kode,
                    'deskripsi' => $sc['deskripsi'],
                    'urutan' => $index + 1,
                ]);

                // Map the code used in JSON (e.g., 'SC-01') to the real DB ID
                $aiCodeRef = $sc['kode'] ?? ('SC-' . ($index + 1)); // The code AI said it used
                $subCpmkIdMap[$aiCodeRef] = $created->id;

                // Also map by index as fallback
                $subCpmkIdMap[$index] = $created->id;
            }

            // 3. Create/Update RPS Details
            // Pre-calculation for Weight Normalization
            $detailsData = $data['details'] ?? [];
            $totalBobot = array_reduce($detailsData, function ($carry, $item) {
                return $carry + ($item['bobot_nilai'] ?? 0);
            }, 0);

            // Normalize weights if not 0
            if ($totalBobot > 0 && $totalBobot != 100) {
                $runningTotal = 0;
                $count = count($detailsData);
                foreach ($detailsData as $k => &$dt) {
                    $original = $dt['bobot_nilai'] ?? 0;
                    // Calculate proportional weight
                    $normalized = ($original / $totalBobot) * 100;
                    $rounded = round($normalized, 2);

                    $dt['bobot_nilai'] = $rounded;
                    $runningTotal += $rounded;
                }

                // Fix rounding error on the last non-zero item
                $diff = 100 - $runningTotal;
                if (abs($diff) > 0.0001) {
                    for ($i = $count - 1; $i >= 0; $i--) {
                        if (($detailsData[$i]['bobot_nilai'] ?? 0) > 0) {
                            $detailsData[$i]['bobot_nilai'] += $diff;
                            break;
                        }
                    }
                }
            }

            $detailsCount = 0;
            foreach ($detailsData as $detail) {
                $subCpmkId = null;

                // Try finding by code ref
                if (isset($detail['sub_cpmk_code_ref']) && isset($subCpmkIdMap[$detail['sub_cpmk_code_ref']])) {
                    $subCpmkId = $subCpmkIdMap[$detail['sub_cpmk_code_ref']];
                }
                // Fallback: Try by index
                elseif (isset($detail['sub_cpmk_index']) && isset($subCpmkIdMap[$detail['sub_cpmk_index']])) {
                    $subCpmkId = $subCpmkIdMap[$detail['sub_cpmk_index']];
                }

                $rps->details()->updateOrCreate(
                    ['pertemuan' => $detail['pertemuan']],
                    [
                        'materi' => $detail['materi'] ?? '',
                        'metode' => $detail['metode'] ?? '',
                        'indikator' => $detail['indikator'] ?? '',
                        'bobot_nilai' => $detail['bobot_nilai'] ?? 0,
                        'sub_cpmk_id' => $subCpmkId,
                    ]
                );
                $detailsCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'RPS berhasil di-generate!',
                'rps_id' => $rps->id,
                'sub_cpmks_created' => count($subCpmkIdMap),
                'details_created' => $detailsCount,
                'redirect_url' => route('rps.edit', $rps->id),
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
