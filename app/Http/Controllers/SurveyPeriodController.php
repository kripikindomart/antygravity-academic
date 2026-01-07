<?php

namespace App\Http\Controllers;

use App\Models\SurveyPeriod;
use App\Models\SurveyTemplate;
use App\Models\SurveyTarget;
use App\Models\TahunAkademik;
use App\Models\KelasMatakuliah;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SurveyPeriodController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyPeriod::with(['template', 'tahunAkademik'])->withCount('targets');

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $periods = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $stats = [
            'total' => SurveyPeriod::count(),
            'active' => SurveyPeriod::where('status', 'active')->count(),
            'total_targets' => SurveyTarget::count(),
            'total_responses' => \App\Models\SurveyResponse::count(),
        ];

        // For modal form
        $templates = SurveyTemplate::where('is_active', true)->get();
        $tahunAkademiks = TahunAkademik::orderBy('kode', 'desc')->get();
        $activeTa = TahunAkademik::where('is_active', true)->first();

        return Inertia::render('Survey/Periods/Index', [
            'periods' => $periods,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
            'templates' => $templates,
            'tahunAkademiks' => $tahunAkademiks,
            'activeTahunAkademikId' => $activeTa?->id,
        ]);
    }

    public function create()
    {
        $templates = SurveyTemplate::where('is_active', true)->get();
        $tahunAkademiks = TahunAkademik::orderBy('kode', 'desc')->get();
        $activeTa = TahunAkademik::where('is_active', true)->first();

        $kelasMatakuliahs = KelasMatakuliah::with(['mataKuliah', 'dosens'])
            ->when($activeTa, function ($q) use ($activeTa) {
                $q->where('tahun_akademik_id', $activeTa->id);
            })
            ->get();

        return Inertia::render('Survey/Periods/Create', [
            'templates' => $templates,
            'tahunAkademiks' => $tahunAkademiks,
            'kelasMatakuliahs' => $kelasMatakuliahs,
            'activeTahunAkademikId' => $activeTa?->id,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_template_id' => 'required|exists:survey_templates,id',
            'tahun_akademik_id' => 'required|exists:tahun_akademiks,id',
            'nama' => 'nullable|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:draft,active,closed',
            'is_mandatory' => 'boolean',
            'allow_guest' => 'boolean',
            'targets' => 'nullable|array',
            'targets.*' => 'exists:kelas_matakuliah,id',
        ]);

        $period = SurveyPeriod::create([
            'survey_template_id' => $validated['survey_template_id'],
            'tahun_akademik_id' => $validated['tahun_akademik_id'],
            'nama' => $validated['nama'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'status' => $validated['status'],
            'is_mandatory' => $validated['is_mandatory'] ?? false,
            'allow_guest' => $validated['allow_guest'] ?? true,
        ]);

        // Create targets - just kelas_matakuliah (dosen selected during fill)
        if (!empty($validated['targets'])) {
            foreach ($validated['targets'] as $kelasId) {
                $period->targets()->create([
                    'kelas_matakuliah_id' => $kelasId,
                ]);
            }
        }

        return redirect()->route('survey.periods.index')
            ->with('success', 'Periode survei berhasil dibuat');
    }

    public function show(SurveyPeriod $period)
    {
        $period->load([
            'template.questions',
            'tahunAkademik',
            'targets.kelasMatakuliah.mataKuliah',
            'targets.dosen',
            'targets.responses.mahasiswa',
        ]);

        // Calculate stats
        $stats = [
            'total_targets' => $period->targets->count(),
            'total_responses' => $period->targets->sum(fn($t) => $t->responses->count()),
            'completion_rate' => 0,
        ];

        return Inertia::render('Survey/Periods/Show', [
            'period' => $period,
            'stats' => $stats,
        ]);
    }

    public function edit(SurveyPeriod $period)
    {
        $period->load('targets');

        $templates = SurveyTemplate::where('is_active', true)->get();
        $tahunAkademiks = TahunAkademik::orderBy('kode', 'desc')->get();
        $kelasMatakuliahs = KelasMatakuliah::with(['mataKuliah', 'dosens'])
            ->whereHas('tahunAkademik', function ($q) {
                $q->where('is_active', true);
            })
            ->get();

        return Inertia::render('Survey/Periods/Edit', [
            'period' => $period,
            'templates' => $templates,
            'tahunAkademiks' => $tahunAkademiks,
            'kelasMatakuliahs' => $kelasMatakuliahs,
        ]);
    }

    public function update(Request $request, SurveyPeriod $period)
    {
        $validated = $request->validate([
            'survey_template_id' => 'required|exists:survey_templates,id',
            'tahun_akademik_id' => 'required|exists:tahun_akademiks,id',
            'nama' => 'nullable|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:draft,active,closed',
            'is_mandatory' => 'boolean',
            'allow_guest' => 'boolean',
            'targets' => 'nullable|array',
            'targets.*' => 'exists:kelas_matakuliah,id',
        ]);

        $period->update([
            'survey_template_id' => $validated['survey_template_id'],
            'tahun_akademik_id' => $validated['tahun_akademik_id'],
            'nama' => $validated['nama'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'status' => $validated['status'],
            'is_mandatory' => $validated['is_mandatory'] ?? false,
            'allow_guest' => $validated['allow_guest'] ?? true,
        ]);

        // Sync targets - just kelas_matakuliah IDs
        $period->targets()->delete();
        if (!empty($validated['targets'])) {
            foreach ($validated['targets'] as $kelasId) {
                $period->targets()->create([
                    'kelas_matakuliah_id' => $kelasId,
                ]);
            }
        }

        return redirect()->route('survey.periods.index')
            ->with('success', 'Periode survei berhasil diperbarui');
    }

    public function destroy(SurveyPeriod $period)
    {
        $period->delete();

        return redirect()->route('survey.periods.index')
            ->with('success', 'Periode survei berhasil dihapus');
    }

    public function activate(SurveyPeriod $period)
    {
        $period->update(['status' => 'active']);

        return back()->with('success', 'Periode survei diaktifkan');
    }

    public function close(SurveyPeriod $period)
    {
        $period->update(['status' => 'closed']);

        return back()->with('success', 'Periode survei ditutup');
    }
}
