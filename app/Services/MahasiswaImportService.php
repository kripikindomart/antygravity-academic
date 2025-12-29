<?php

namespace App\Services;

use App\Models\Mahasiswa;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MahasiswaImportService
{
    protected array $errors = [];
    protected int $imported = 0;
    protected int $skipped = 0;
    protected ?int $prodiId = null;
    protected ?array $allowedProdiIds = null;
    protected ?int $semesterMasukId = null;
    protected $globalSemester = null;
    protected array $taCache = [];
    protected array $prodiCache = [];

    /**
     * Import mahasiswas from Excel file
     */
    public function import(string $filePath, ?int $prodiId = null, ?int $semesterMasukId = null, ?array $allowedProdiIds = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Get header row (first row)
        $headers = array_map('strtolower', array_map('trim', $rows[0] ?? []));

        // Map headers to field names
        $headerMap = $this->getHeaderMap($headers);

        // Store prodi_id and semester_masuk_id
        $this->prodiId = $prodiId;
        $this->allowedProdiIds = $allowedProdiIds;
        $this->semesterMasukId = $semesterMasukId;

        if ($this->semesterMasukId) {
            $this->globalSemester = \App\Models\Semester::with('tahunAkademik')->find($this->semesterMasukId);
        }

        // Preload Prodis for cache if flexible import
        if (!$this->prodiId) {
            $this->prodiCache = \App\Models\ProgramStudi::pluck('id', 'nama')->mapWithKeys(function ($id, $nama) {
                return [strtolower($nama) => $id];
            })->toArray();
        }

        // Process data rows (skip header)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rowNum = $i + 1;

            // Skip empty rows
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $data = $this->mapRowToData($row, $headerMap);

            // Validate
            $validator = Validator::make($data, [
                'nama' => 'required|string|max:255',
                'nim' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Baris {$rowNum}: " . implode(', ', $validator->errors()->all());
                $this->skipped++;
                continue;
            }

            // Check for duplicates
            if ($data['nim'] && Mahasiswa::where('nim', $data['nim'])->exists()) {
                $this->errors[] = "Baris {$rowNum}: NIM '{$data['nim']}' sudah ada";
                $this->skipped++;
                continue;
            }

            // Determine Academic Year & Semester
            $taId = null;
            $angkatan = null;
            $semesterId = null;

            if ($this->globalSemester) {
                // Use Global Setting
                $taId = $this->globalSemester->tahun_akademik_id;
                $angkatan = \Carbon\Carbon::parse($this->globalSemester->tahunAkademik->tanggal_mulai)->year;
                $semesterId = $this->globalSemester->id;
            } else {
                // Use Excel 'angkatan' column or current year
                $angkatan = $data['angkatan'] ?? date('Y');
                if (!isset($this->taCache[$angkatan])) {
                    $ta = \App\Models\TahunAkademik::where('nama', 'like', $angkatan . '%')->first();
                    $this->taCache[$angkatan] = $ta ? $ta->id : null;
                }
                $taId = $this->taCache[$angkatan];

                if (!$taId) {
                    $this->errors[] = "Baris {$rowNum}: Tahun Akademik untuk angkatan '{$angkatan}' tidak ditemukan";
                    $this->skipped++;
                    continue;
                }
            }

            // Determine Prodi
            $targetProdiId = $this->prodiId;
            if (!$targetProdiId && isset($data['prodi'])) {
                $prodiName = strtolower(trim($data['prodi']));
                $targetProdiId = $this->prodiCache[$prodiName] ?? null;

                if (!$targetProdiId && !empty($data['prodi'])) {
                    $this->errors[] = "Baris {$rowNum}: Program Studi '{$data['prodi']}' tidak ditemukan";
                    $this->skipped++;
                    continue;
                }
            }

            // Check Permission
            if ($this->allowedProdiIds !== null) {
                if (!$targetProdiId) {
                    // If Staff Prodi imports without specifying prodi?
                    // Depends on policy. Assume if prodi is null it's OK? 
                    // Or block if they try to access 'global' scope?
                    // Usually they must assign to their prodi.
                    // But for now, only check if ID is present.
                } elseif (!in_array($targetProdiId, $this->allowedProdiIds)) {
                    $this->errors[] = "Baris {$rowNum}: Akses ditolak ke Program Studi";
                    $this->skipped++;
                    continue;
                }
            }

            // Create mahasiswa
            Mahasiswa::create([
                'prodi_id' => $targetProdiId,
                'nim' => $data['nim'],
                'nama' => $data['nama'],
                'email' => $data['email'] ?: null,
                'no_hp' => $data['no_hp'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                'angkatan' => $angkatan,
                'status' => $data['status'] ?? 'aktif',
                'tahun_akademik_masuk_id' => $taId,
                'semester_masuk_id' => $semesterId,
            ]);

            $this->imported++;
        }

        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }

    /**
     * Get header map from Excel headers
     */
    protected function getHeaderMap(array $headers): array
    {
        $map = [];
        $fieldMappings = [
            'nama' => ['nama', 'name', 'nama lengkap', 'nama mahasiswa'],
            'prodi' => ['prodi', 'program studi', 'jurusan', 'study program'],
            'nim' => ['nim', 'nomor induk mahasiswa'],
            'email' => ['email', 'e-mail', 'alamat email'],
            'no_hp' => ['no hp', 'nomor hp', 'telepon', 'telp', 'phone', 'hp'],
            'alamat' => ['alamat', 'address'],
            'tanggal_lahir' => ['tanggal lahir', 'tgl lahir', 'dob', 'birth date'],
            'jenis_kelamin' => ['jenis kelamin', 'jk', 'gender'],
            'angkatan' => ['angkatan', 'tahun masuk'],
            'status' => ['status'],
        ];

        foreach ($headers as $index => $header) {
            foreach ($fieldMappings as $field => $aliases) {
                if (in_array(strtolower($header), $aliases)) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Map row data to field values
     */
    protected function mapRowToData(array $row, array $headerMap): array
    {
        $data = [];
        foreach ($headerMap as $field => $index) {
            $value = isset($row[$index]) ? trim($row[$index]) : null;
            $data[$field] = $value ? strip_tags($value) : null;
        }

        // Normalize jenis_kelamin
        if (isset($data['jenis_kelamin'])) {
            $jk = strtoupper($data['jenis_kelamin']);
            if (in_array($jk, ['L', 'LAKI-LAKI', 'PRIA', 'M', 'MALE'])) {
                $data['jenis_kelamin'] = 'L';
            } elseif (in_array($jk, ['P', 'PEREMPUAN', 'WANITA', 'F', 'FEMALE'])) {
                $data['jenis_kelamin'] = 'P';
            } else {
                $data['jenis_kelamin'] = null;
            }
        }

        // Normalize status
        if (isset($data['status'])) {
            $status = strtolower($data['status']);
            $validStatuses = ['aktif', 'nonaktif', 'cuti', 'lulus', 'drop out', 'keluar'];
            if (!in_array($status, $validStatuses)) {
                $data['status'] = 'aktif';
            }
        }

        // Normalize tanggal_lahir (try parsing)
        if (isset($data['tanggal_lahir'])) {
            try {
                // Handle different date formats or Excel numeric dates if needed
                // For simplicity assuming YYYY-MM-DD or standard formats parsable by Carbon
                // If excel numeric, might need specialized handling, but usually IOFactory handles basic formats
                if (is_numeric($data['tanggal_lahir'])) {
                    $data['tanggal_lahir'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data['tanggal_lahir']);
                } else {
                    $data['tanggal_lahir'] = Carbon::parse($data['tanggal_lahir'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                $data['tanggal_lahir'] = null;
            }
        }

        return $data;
    }

    /**
     * Check if row is empty
     */
    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim($cell) !== '') {
                return false;
            }
        }
        return true;
    }
}
