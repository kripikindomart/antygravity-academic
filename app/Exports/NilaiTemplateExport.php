<?php

namespace App\Exports;

use App\Models\KelasMatakuliah;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NilaiTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $kelasMatakuliah;
    protected $komponens;

    public function __construct(KelasMatakuliah $kelasMatakuliah, $komponens)
    {
        $this->kelasMatakuliah = $kelasMatakuliah;
        $this->komponens = $komponens;
    }

    public function headings(): array
    {
        $headers = ['No', 'NIM', 'Nama Mahasiswa'];
        foreach ($this->komponens as $comp) {
            // Only include manual components
            if ($comp->source_type !== 'kehadiran') {
                $headers[] = $comp->nama . ' (ID:' . $comp->id . ')'; // Include ID in header for easier mapping on import? Or just name?
                // Name might duplicate. ID is safer but ugly.
                // Let's use Name and map by name? Or Name (Bobot).
                // Best practice: Use ID in a hidden way or strictly match name.
                // Let's use "Nama". We'll handle mapping in preview.
            }
        }
        return $headers;
    }

    public function collection()
    {
        // Ensure students are loaded
        $students = $this->kelasMatakuliah->kelas->mahasiswas;
        $data = [];
        $no = 1;

        foreach ($students as $mhs) {
            $row = [
                $no++,
                $mhs->nim,
                $mhs->nama,
            ];

            foreach ($this->komponens as $comp) {
                if ($comp->source_type !== 'kehadiran') {
                    // Leave empty for template
                    // Check if current score exists?
                    // Ideally yes, but for "Blank Template" request we keep it simple.
                    // If user wants to "Export Data", that's different.
                    $row[] = '';
                }
            }
            $data[] = $row;
        }

        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
