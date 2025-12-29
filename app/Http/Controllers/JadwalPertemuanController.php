<?php

namespace App\Http\Controllers;

use App\Models\JadwalPertemuan;
use App\Models\Kelas;
use App\Models\KelasMatakuliah;
use App\Models\Absensi;
use App\Models\Jurnal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class JadwalPertemuanController extends Controller
{
    /**
     * Get list for Tab Jadwal
     */
    public function index(Kelas $kelas)
    {
        $pertemuans = JadwalPertemuan::whereHas('jadwal', function ($q) use ($kelas) {
            $q->where('kelas_id', $kelas->id);
        })
            ->with(['dosen', 'jurnal', 'jadwal.mataKuliah'])
            ->orderBy('tanggal') // Sort by Date first
            ->orderBy('pertemuan_ke')
            ->get();

        return response()->json($pertemuans);
    }

    /**
     * Show Detail (Absensi Page)
     */
    public function show(JadwalPertemuan $pertemuan)
    {
        // Fix: Avoid namespace collision between 'kelas' column and 'kelas' relation
        $pertemuan->load(['jadwal.mataKuliah', 'absensis', 'jurnal', 'dosen']);

        $kelas = \App\Models\Kelas::with('mahasiswas')->find($pertemuan->jadwal->kelas_id);

        // Transform list mahasiswa with attendance status
        $mahasiswas = $kelas->mahasiswas->map(function ($mhs) use ($pertemuan) {
            $absensi = $pertemuan->absensis->where('mahasiswa_id', $mhs->id)->first();
            return [
                'id' => $mhs->id,
                'nama' => $mhs->nama,
                'nim' => $mhs->nim, // Assuming NIM exists
                'status' => $absensi ? $absensi->status : 'hadir', // Default hadir for easy input
                'keterangan' => $absensi ? $absensi->keterangan : '',
                'jam_masuk' => $absensi ? $absensi->jam_masuk : null,
            ];
        })->sortBy('nama')->values();

        return Inertia::render('Kelas/Pertemuan/Show', [
            'pertemuan' => $pertemuan,
            'mahasiswas' => $mahasiswas,
            'jurnal' => $pertemuan->jurnal,
        ]);
    }

    /**
     * Save Absensi Bulk
     */
    public function storeAbsensi(Request $request, JadwalPertemuan $pertemuan)
    {
        $validated = $request->validate([
            'mahasiswas' => 'required|array',
            'mahasiswas.*.id' => 'required|exists:mahasiswas,id',
            'mahasiswas.*.status' => 'required|in:hadir,izin,sakit,alpha',
            'mahasiswas.*.keterangan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['mahasiswas'] as $item) {
                Absensi::updateOrCreate(
                    [
                        'jadwal_pertemuan_id' => $pertemuan->id,
                        'mahasiswa_id' => $item['id'],
                    ],
                    [
                        'status' => $item['status'],
                        'keterangan' => $item['keterangan'] ?? null,
                        'input_by' => auth()->id(),
                    ]
                );
            }

            // Recalculate summary for Jurnal
            $summary = [
                'jumlah_hadir' => Absensi::where('jadwal_pertemuan_id', $pertemuan->id)->where('status', 'hadir')->count(),
                'jumlah_izin' => Absensi::where('jadwal_pertemuan_id', $pertemuan->id)->where('status', 'izin')->count(),
                'jumlah_sakit' => Absensi::where('jadwal_pertemuan_id', $pertemuan->id)->where('status', 'sakit')->count(),
                'jumlah_alpha' => Absensi::where('jadwal_pertemuan_id', $pertemuan->id)->where('status', 'alpha')->count(),
            ];

            // If Jurnal exists, update it
            if ($pertemuan->jurnal) {
                $pertemuan->jurnal->update($summary);
            } else {
                // Auto create basic jurnal? Maybe later.
            }

            // Update pertemuan status to Selesai if not yet
            $pertemuan->update(['status' => 'Selesai']);

            DB::commit();
            return back()->with('success', 'Absensi berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['message' => 'Gagal menyimpan absensi: ' . $e->getMessage()]);
        }
    }

    /**
     * Save Jurnal
     */
    public function storeJurnal(Request $request, JadwalPertemuan $pertemuan)
    {
        $validated = $request->validate([
            'materi' => 'nullable|string',
            'aktivitas' => 'nullable|string',
            'catatan' => 'nullable|string',
            // 'bukti_perkuliahan' => 'nullable|file|image|max:2048',
        ]);

        $dosenId = $pertemuan->dosen_id ?? auth()->user()->dosen?->id; // Fallback to current user if dosen
        if (!$dosenId) {
            // Fallback to first dosen of MK? Or nullable.
            // Schema: dosen_id is constrained.
            // Assuming user is Dosen. If Admin, need to select Dosen?
            // Use pertemuan->dosen_id
            $dosenId = $pertemuan->dosen_id;
        }

        Jurnal::updateOrCreate(
            ['jadwal_pertemuan_id' => $pertemuan->id],
            array_merge($validated, [
                'dosen_id' => $dosenId,
                // Recalculate counts to be safe
                'jumlah_hadir' => Absensi::where('jadwal_pertemuan_id', $pertemuan->id)->where('status', 'hadir')->count(),
                'jumlah_izin' => Absensi::where('jadwal_pertemuan_id', $pertemuan->id)->where('status', 'izin')->count(),
                'jumlah_sakit' => Absensi::where('jadwal_pertemuan_id', $pertemuan->id)->where('status', 'sakit')->count(),
                'jumlah_alpha' => Absensi::where('jadwal_pertemuan_id', $pertemuan->id)->where('status', 'alpha')->count(),
            ])
        );

        return back()->with('success', 'Jurnal berhasil disimpan');
    }

    /**
     * Show MK Schedule Page (Dedicated)
     */
    public function indexMk(KelasMatakuliah $kelasMatakuliah)
    {
        $kelasMatakuliah->load(['mataKuliah', 'kelas.semester', 'dosens.dosen', 'ruangans']);

        $jadwals = JadwalPertemuan::whereHas('jadwal', function ($q) use ($kelasMatakuliah) {
            $q->where('mata_kuliah_id', $kelasMatakuliah->mata_kuliah_id)
                ->where('kelas_id', $kelasMatakuliah->kelas_id);
        })
            ->with(['dosen', 'ruangan', 'jadwal', 'jurnal'])
            ->orderBy('pertemuan_ke')
            ->get();

        // Available Dosens for Edit Dropdown
        $availableDosens = $kelasMatakuliah->dosens->map(fn($d) => $d->dosen)->filter();

        return Inertia::render('Kelas/MataKuliah/Jadwal', [
            'kelasMatakuliah' => $kelasMatakuliah,
            'jadwals' => $jadwals,
            'availableDosens' => $availableDosens->values(),
            'availableRuangans' => $kelasMatakuliah->ruangans,
        ]);
    }

    /**
     * Update a JadwalPertemuan
     */
    public function update(Request $request, JadwalPertemuan $jadwalPertemuan)
    {
        $validated = $request->validate([
            'tanggal' => 'nullable|date',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'dosen_id' => 'nullable|exists:dosens,id',
            'ruangan_id' => 'nullable|exists:ruangans,id',
            'tipe' => 'nullable|in:kuliah,uts,uas',
            'mode' => 'nullable|in:online,offline,hybrid',
            'status' => 'nullable|in:terjadwal,selesai,dibatalkan',
            'catatan' => 'nullable|string|max:500',
        ]);

        $jadwalPertemuan->update(array_filter($validated, fn($v) => $v !== null));

        return back()->with('success', 'Jadwal berhasil diperbarui');
    }

    /**
     * Delete a JadwalPertemuan
     */
    public function destroy(JadwalPertemuan $jadwalPertemuan)
    {
        $jadwalPertemuan->delete();
        return back()->with('success', 'Jadwal berhasil dihapus');
    }

    /**
     * Bulk Update JadwalPertemuans
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:jadwal_pertemuans,id',
            'dosen_id' => 'nullable|exists:dosens,id',
            'tipe' => 'nullable|in:kuliah,uts,uas',
            'mode' => 'nullable|in:online,offline,hybrid',
            'status' => 'nullable|in:terjadwal,selesai,dibatalkan',
            'tanggal' => 'nullable|date',
        ]);

        $updateData = [];
        if (isset($validated['dosen_id']))
            $updateData['dosen_id'] = $validated['dosen_id'];
        if (isset($validated['tipe']))
            $updateData['tipe'] = $validated['tipe'];
        if (isset($validated['mode']))
            $updateData['mode'] = $validated['mode'];
        if (isset($validated['status']))
            $updateData['status'] = $validated['status'];
        if (isset($validated['tanggal']))
            $updateData['tanggal'] = $validated['tanggal'];

        if (!empty($updateData)) {
            JadwalPertemuan::whereIn('id', $validated['ids'])->update($updateData);
        }

        $count = count($validated['ids']);
        return back()->with('success', "$count jadwal berhasil diperbarui");
    }

    /**
     * Check availability for a specific date/time/dosen/ruangan with Kelas-level conflict detection
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|string',
            'jam_selesai' => 'required|string',
            'kelas_id' => 'nullable|integer',
            'mata_kuliah_id' => 'nullable|integer',
            'dosen_id' => 'nullable|integer',
            'ruangan_id' => 'nullable|integer',
            'exclude_id' => 'nullable|integer',
        ]);

        $result = [
            'kelas_available' => true,
            'kelas_conflict' => null,
            'mk_conflict' => null, // Same MK at same time (fatal)
            'dosen_available' => true,
            'dosen_conflict' => null,
            'ruangan_available' => true,
            'ruangan_conflict' => null,
            'suggested_dates' => [],
        ];

        // 1. Check Kelas-Level Conflict (any MK at overlapping time in same Kelas)
        if (!empty($validated['kelas_id'])) {
            $kelasConflict = JadwalPertemuan::where('tanggal', $validated['tanggal'])
                ->when($validated['exclude_id'] ?? null, fn($q, $id) => $q->where('id', '!=', $id))
                ->whereHas('jadwal', function ($q) use ($validated) {
                    $q->where('kelas_id', $validated['kelas_id'])
                        ->where(function ($q2) use ($validated) {
                            $q2->where('jam_mulai', '<', $validated['jam_selesai'])
                                ->where('jam_selesai', '>', $validated['jam_mulai']);
                        });
                })
                ->with(['jadwal.mataKuliah', 'jadwal'])
                ->first();

            if ($kelasConflict) {
                $isSameMk = $kelasConflict->jadwal->mata_kuliah_id == ($validated['mata_kuliah_id'] ?? 0);

                $conflictInfo = [
                    'id' => $kelasConflict->id,
                    'pertemuan_ke' => $kelasConflict->pertemuan_ke,
                    'jam_mulai' => $kelasConflict->jadwal->jam_mulai,
                    'jam_selesai' => $kelasConflict->jadwal->jam_selesai,
                    'mata_kuliah' => $kelasConflict->jadwal->mataKuliah->nama ?? '-',
                    'is_same_mk' => $isSameMk,
                ];

                if ($isSameMk) {
                    $result['mk_conflict'] = $conflictInfo; // Fatal: same MK at same time
                }
                $result['kelas_available'] = false;
                $result['kelas_conflict'] = $conflictInfo;
            }
        }

        // 2. Check Dosen conflict
        if (!empty($validated['dosen_id'])) {
            $dosenConflict = JadwalPertemuan::where('tanggal', $validated['tanggal'])
                ->where('dosen_id', $validated['dosen_id'])
                ->when($validated['exclude_id'] ?? null, fn($q, $id) => $q->where('id', '!=', $id))
                ->whereHas('jadwal', function ($q) use ($validated) {
                    $q->where(function ($q2) use ($validated) {
                        $q2->where('jam_mulai', '<', $validated['jam_selesai'])
                            ->where('jam_selesai', '>', $validated['jam_mulai']);
                    });
                })
                ->with(['jadwal.mataKuliah', 'jadwal'])
                ->first();

            if ($dosenConflict) {
                $result['dosen_available'] = false;
                $result['dosen_conflict'] = [
                    'id' => $dosenConflict->id,
                    'pertemuan_ke' => $dosenConflict->pertemuan_ke,
                    'jam_mulai' => $dosenConflict->jadwal->jam_mulai,
                    'jam_selesai' => $dosenConflict->jadwal->jam_selesai,
                    'mata_kuliah' => $dosenConflict->jadwal->mataKuliah->nama ?? '-',
                ];
            }
        }

        // 3. Check Ruangan conflict
        if (!empty($validated['ruangan_id'])) {
            $ruanganConflict = JadwalPertemuan::where('tanggal', $validated['tanggal'])
                ->where('ruangan_id', $validated['ruangan_id'])
                ->when($validated['exclude_id'] ?? null, fn($q, $id) => $q->where('id', '!=', $id))
                ->whereHas('jadwal', function ($q) use ($validated) {
                    $q->where(function ($q2) use ($validated) {
                        $q2->where('jam_mulai', '<', $validated['jam_selesai'])
                            ->where('jam_selesai', '>', $validated['jam_mulai']);
                    });
                })
                ->with(['jadwal.mataKuliah', 'jadwal'])
                ->first();

            if ($ruanganConflict) {
                $result['ruangan_available'] = false;
                $result['ruangan_conflict'] = [
                    'id' => $ruanganConflict->id,
                    'pertemuan_ke' => $ruanganConflict->pertemuan_ke,
                    'jam_mulai' => $ruanganConflict->jadwal->jam_mulai,
                    'jam_selesai' => $ruanganConflict->jadwal->jam_selesai,
                    'mata_kuliah' => $ruanganConflict->jadwal->mataKuliah->nama ?? '-',
                ];
            }
        }

        // 4. Generate suggested available dates (next 4 weeks)
        if (!$result['kelas_available'] || !$result['dosen_available']) {
            $baseDate = \Carbon\Carbon::parse($validated['tanggal']);
            $suggestions = [];

            for ($i = 1; $i <= 28; $i++) { // Check next 4 weeks
                $checkDate = $baseDate->copy()->addDays($i);
                $dateStr = $checkDate->format('Y-m-d');

                // Check if this date is free for the Kelas at this time
                $hasKelasConflict = JadwalPertemuan::where('tanggal', $dateStr)
                    ->when($validated['exclude_id'] ?? null, fn($q, $id) => $q->where('id', '!=', $id))
                    ->whereHas('jadwal', function ($q) use ($validated) {
                        $q->where('kelas_id', $validated['kelas_id'] ?? 0)
                            ->where(function ($q2) use ($validated) {
                                $q2->where('jam_mulai', '<', $validated['jam_selesai'])
                                    ->where('jam_selesai', '>', $validated['jam_mulai']);
                            });
                    })->exists();

                $hasDosenConflict = false;
                if (!empty($validated['dosen_id'])) {
                    $hasDosenConflict = JadwalPertemuan::where('tanggal', $dateStr)
                        ->where('dosen_id', $validated['dosen_id'])
                        ->when($validated['exclude_id'] ?? null, fn($q, $id) => $q->where('id', '!=', $id))
                        ->whereHas('jadwal', function ($q) use ($validated) {
                            $q->where(function ($q2) use ($validated) {
                                $q2->where('jam_mulai', '<', $validated['jam_selesai'])
                                    ->where('jam_selesai', '>', $validated['jam_mulai']);
                            });
                        })->exists();
                }

                if (!$hasKelasConflict && !$hasDosenConflict) {
                    $suggestions[] = [
                        'date' => $dateStr,
                        'day' => $checkDate->locale('id')->translatedFormat('l, d F Y'),
                    ];
                    if (count($suggestions) >= 5)
                        break; // Max 5 suggestions
                }
            }
            $result['suggested_dates'] = $suggestions;
        }

        // 5. Generate suggested ruangans if ruangan conflict detected
        if (!$result['ruangan_available']) {
            $bookedRuanganIds = JadwalPertemuan::where('tanggal', $validated['tanggal'])
                ->when($validated['exclude_id'] ?? null, fn($q, $id) => $q->where('id', '!=', $id))
                ->whereHas('jadwal', function ($q) use ($validated) {
                    $q->where(function ($q2) use ($validated) {
                        $q2->where('jam_mulai', '<', $validated['jam_selesai'])
                            ->where('jam_selesai', '>', $validated['jam_mulai']);
                    });
                })
                ->pluck('ruangan_id')
                ->filter()
                ->toArray();

            $freeRuangans = \App\Models\Ruangan::whereNotIn('id', $bookedRuanganIds)
                ->where('is_active', true)
                ->take(5)
                ->get(['id', 'nama', 'gedung', 'kapasitas']);

            $result['suggested_ruangans'] = $freeRuangans;
        }

        // 6. Generate suggested time slots if there's a conflict on the same date
        if (!$result['kelas_available'] || !$result['dosen_available']) {
            // Get session duration in hours
            $jamMulai = \Carbon\Carbon::parse($validated['jam_mulai']);
            $jamSelesai = \Carbon\Carbon::parse($validated['jam_selesai']);
            $sessionHours = $jamSelesai->diffInHours($jamMulai);

            // Get all booked time slots on this date for Kelas/Dosen
            $bookedTimes = JadwalPertemuan::where('tanggal', $validated['tanggal'])
                ->when($validated['exclude_id'] ?? null, fn($q, $id) => $q->where('id', '!=', $id))
                ->where(function ($q) use ($validated) {
                    if (!empty($validated['kelas_id'])) {
                        $q->whereHas('jadwal', fn($j) => $j->where('kelas_id', $validated['kelas_id']));
                    }
                    if (!empty($validated['dosen_id'])) {
                        $q->orWhere('dosen_id', $validated['dosen_id']);
                    }
                })
                ->with('jadwal')
                ->get()
                ->map(fn($j) => [
                    'start' => $j->jam_mulai ?? $j->jadwal?->jam_mulai,
                    'end' => $j->jam_selesai ?? $j->jadwal?->jam_selesai,
                ])
                ->filter(fn($t) => $t['start'] && $t['end'])
                ->values();

            // Generate potential slots from 08:00 to 22:00
            $slots = [];
            $slotStart = \Carbon\Carbon::createFromFormat('H:i', '08:00');
            $maxEnd = \Carbon\Carbon::createFromFormat('H:i', '22:00');

            while ($slotStart->copy()->addHours($sessionHours)->lte($maxEnd)) {
                $slotEnd = $slotStart->copy()->addHours($sessionHours);
                $slotStartStr = $slotStart->format('H:i');
                $slotEndStr = $slotEnd->format('H:i');

                // Check if this slot overlaps with any booked time
                $hasOverlap = $bookedTimes->contains(function ($booked) use ($slotStartStr, $slotEndStr) {
                    return $slotStartStr < $booked['end'] && $slotEndStr > $booked['start'];
                });

                if (!$hasOverlap) {
                    $slots[] = [
                        'jam_mulai' => $slotStartStr,
                        'jam_selesai' => $slotEndStr,
                        'label' => $slotStartStr . ' - ' . $slotEndStr,
                    ];
                }

                $slotStart->addHours(2); // Move by 2 hours (1 sesi)
            }

            $result['suggested_time_slots'] = array_slice($slots, 0, 5); // Max 5 slots
        }

        return response()->json($result);
    }

    /**
     * Get date availability for a month (for calendar picker)
     */
    public function getDateAvailability(Request $request)
    {
        $validated = $request->validate([
            'dosen_id' => 'nullable|integer',
            'ruangan_id' => 'nullable|integer',
            'jam_mulai' => 'required|string',
            'jam_selesai' => 'required|string',
            'month' => 'required|date_format:Y-m', // e.g., 2025-09
            'exclude_id' => 'nullable|integer',
        ]);

        $startDate = \Carbon\Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get all booked dates for this dosen/ruangan in this month
        $conflicts = JadwalPertemuan::whereBetween('tanggal', [$startDate, $endDate])
            ->when($validated['exclude_id'] ?? null, fn($q, $id) => $q->where('id', '!=', $id))
            ->where(function ($q) use ($validated) {
                if (!empty($validated['dosen_id'])) {
                    $q->orWhere('dosen_id', $validated['dosen_id']);
                }
                if (!empty($validated['ruangan_id'])) {
                    $q->orWhere('ruangan_id', $validated['ruangan_id']);
                }
            })
            ->whereHas('jadwal', function ($q) use ($validated) {
                $q->where(function ($q2) use ($validated) {
                    $q2->where('jam_mulai', '<', $validated['jam_selesai'])
                        ->where('jam_selesai', '>', $validated['jam_mulai']);
                });
            })
            ->with(['jadwal.mataKuliah', 'dosen', 'ruangan'])
            ->get()
            ->groupBy(fn($j) => $j->tanggal->format('Y-m-d'));

        $result = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            if (isset($conflicts[$dateStr])) {
                $conflictList = $conflicts[$dateStr]->map(fn($c) => [
                    'id' => $c->id,
                    'jam' => substr($c->jadwal->jam_mulai, 0, 5) . '-' . substr($c->jadwal->jam_selesai, 0, 5),
                    'mk' => $c->jadwal->mataKuliah->nama ?? '-',
                    'type' => $c->dosen_id == ($validated['dosen_id'] ?? 0) ? 'dosen' : 'ruangan',
                ]);
                $result[] = [
                    'date' => $dateStr,
                    'available' => false,
                    'conflicts' => $conflictList->values(),
                ];
            } else {
                $result[] = [
                    'date' => $dateStr,
                    'available' => true,
                    'conflicts' => [],
                ];
            }
            $current->addDay();
        }

        return response()->json(['dates' => $result]);
    }
}
