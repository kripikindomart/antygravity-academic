<?php

namespace App\Services;

use App\Models\Dosen;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;

class DosenImportService
{
    protected array $errors = [];
    protected int $imported = 0;
    protected int $skipped = 0;
    protected ?int $prodiId = null;

    /**
     * Import dosens from Excel file
     */
    public function import(string $filePath, ?int $prodiId = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Get header row (first row)
        $headers = array_map('strtolower', array_map('trim', $rows[0] ?? []));

        // Map headers to field names
        $headerMap = $this->getHeaderMap($headers);

        // Store prodi_id for use in create
        $this->prodiId = $prodiId;

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
                'nip' => 'nullable|string|max:50',
                'nidn' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Baris {$rowNum}: " . implode(', ', $validator->errors()->all());
                $this->skipped++;
                continue;
            }

            // Check for duplicates
            if ($data['nip'] && Dosen::where('nip', $data['nip'])->exists()) {
                $this->errors[] = "Baris {$rowNum}: NIP '{$data['nip']}' sudah ada";
                $this->skipped++;
                continue;
            }

            if ($data['nidn'] && Dosen::where('nidn', $data['nidn'])->exists()) {
                $this->errors[] = "Baris {$rowNum}: NIDN '{$data['nidn']}' sudah ada";
                $this->skipped++;
                continue;
            }

            // Create dosen
            Dosen::create([
                'prodi_id' => $this->prodiId,
                'nama' => $data['nama'],
                'nip' => $data['nip'] ?: null,
                'nidn' => $data['nidn'] ?: null,
                'email' => $data['email'] ?: null,
                'telepon' => $data['telepon'] ?? null,
                'gelar_depan' => $data['gelar_depan'] ?? null,
                'gelar_belakang' => $data['gelar_belakang'] ?? null,
                'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                'jabatan_fungsional' => $data['jabatan_fungsional'] ?? null,
                'pendidikan_terakhir' => $data['pendidikan_terakhir'] ?? null,
                'bidang_keahlian' => $data['bidang_keahlian'] ?? null,
                'status' => $data['status'] ?? 'aktif',
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
            'nama' => ['nama', 'name', 'nama lengkap', 'nama dosen'],
            'nip' => ['nip', 'nomor induk pegawai'],
            'nidn' => ['nidn', 'nomor induk dosen nasional'],
            'email' => ['email', 'e-mail', 'alamat email'],
            'telepon' => ['telepon', 'telp', 'hp', 'no hp', 'no telp', 'phone'],
            'gelar_depan' => ['gelar depan', 'gelar_depan'],
            'gelar_belakang' => ['gelar belakang', 'gelar_belakang'],
            'jenis_kelamin' => ['jenis kelamin', 'jk', 'gender', 'jenis_kelamin'],
            'jabatan_fungsional' => ['jabatan fungsional', 'jabatan', 'jabatan_fungsional'],
            'pendidikan_terakhir' => ['pendidikan terakhir', 'pendidikan', 'pendidikan_terakhir'],
            'bidang_keahlian' => ['bidang keahlian', 'keahlian', 'bidang_keahlian'],
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
            $data[$field] = isset($row[$index]) ? trim($row[$index]) : null;
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
            if (!in_array($status, ['aktif', 'nonaktif', 'cuti'])) {
                $data['status'] = 'aktif';
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
