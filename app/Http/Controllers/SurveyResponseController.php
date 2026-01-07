<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use App\Models\SurveyTarget;
use App\Models\SurveyPeriod;
use App\Models\SurveyAnswer;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SurveyResponseController extends Controller
{
    /**
     * List surveys available for current mahasiswa
     */
    public function index()
    {
        $user = Auth::user();
        $mahasiswa = $user->mahasiswa;

        if (!$mahasiswa) {
            return Inertia::render('Survey/Responses/Index', [
                'surveys' => [],
                'message' => 'Anda tidak terdaftar sebagai mahasiswa',
            ]);
        }

        // Get active survey periods for mahasiswa's classes
        $kelasMatakuliahIds = $mahasiswa->kelasMatakuliahs()->pluck('kelas_matakuliah.id');

        $targets = SurveyTarget::with([
            'period.template',
            'kelasMatakuliah.mataKuliah',
            'dosen',
        ])
            ->whereIn('kelas_matakuliah_id', $kelasMatakuliahIds)
            ->whereHas('period', function ($q) {
                $q->where('status', 'active')
                    ->whereDate('tanggal_mulai', '<=', now())
                    ->whereDate('tanggal_selesai', '>=', now());
            })
            ->get()
            ->map(function ($target) use ($mahasiswa) {
                $response = $target->responses()
                    ->where('mahasiswa_id', $mahasiswa->id)
                    ->first();

                return [
                    'id' => $target->id,
                    'period' => $target->period,
                    'kelas' => $target->kelasMatakuliah,
                    'dosen' => $target->dosen,
                    'is_completed' => $response && $response->submitted_at !== null,
                    'response_id' => $response?->id,
                ];
            });

        return Inertia::render('Survey/Responses/Index', [
            'surveys' => $targets,
        ]);
    }

    /**
     * Show survey form for specific target
     */
    public function create(SurveyTarget $target)
    {
        $user = Auth::user();
        $mahasiswa = $user->mahasiswa;

        if (!$mahasiswa) {
            abort(403, 'Anda tidak terdaftar sebagai mahasiswa');
        }

        // Check if already submitted
        $existingResponse = $target->responses()
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();

        if ($existingResponse && $existingResponse->submitted_at) {
            return redirect()->route('survey.responses.index')
                ->with('error', 'Anda sudah mengisi survei ini');
        }

        // Load full survey structure
        $target->load([
            'period.template.questions.options',
            'kelasMatakuliah.mataKuliah',
            'dosen',
        ]);

        // Get existing answers if any (draft)
        $existingAnswers = [];
        if ($existingResponse) {
            $existingAnswers = $existingResponse->answers()
                ->get()
                ->mapWithKeys(fn($a) => [$a->survey_question_id => $a])
                ->toArray();
        }

        return Inertia::render('Survey/Responses/Create', [
            'target' => $target,
            'existingAnswers' => $existingAnswers,
            'existingResponseId' => $existingResponse?->id,
        ]);
    }

    /**
     * Save survey response
     */
    public function store(Request $request, SurveyTarget $target)
    {
        $user = Auth::user();
        $mahasiswa = $user->mahasiswa;

        if (!$mahasiswa) {
            abort(403, 'Anda tidak terdaftar sebagai mahasiswa');
        }

        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:survey_questions,id',
            'answers.*.option_id' => 'nullable|exists:survey_options,id',
            'answers.*.text_answer' => 'nullable|string',
            'is_submit' => 'boolean',
        ]);

        DB::transaction(function () use ($target, $mahasiswa, $validated) {
            // Get or create response
            $response = SurveyResponse::firstOrCreate(
                [
                    'survey_target_id' => $target->id,
                    'mahasiswa_id' => $mahasiswa->id,
                ],
                ['submitted_at' => null]
            );

            // Save answers
            foreach ($validated['answers'] as $answerData) {
                SurveyAnswer::updateOrCreate(
                    [
                        'survey_response_id' => $response->id,
                        'survey_question_id' => $answerData['question_id'],
                    ],
                    [
                        'survey_option_id' => $answerData['option_id'] ?? null,
                        'text_answer' => $answerData['text_answer'] ?? null,
                    ]
                );
            }

            // Mark as submitted if requested
            if ($validated['is_submit'] ?? false) {
                $response->update(['submitted_at' => now()]);
            }
        });

        $message = ($validated['is_submit'] ?? false)
            ? 'Survei berhasil dikirim'
            : 'Draft survei tersimpan';

        return redirect()->route('survey.responses.index')
            ->with('success', $message);
    }

    /**
     * Dashboard/Report for Admin/Kaprodi/Dosen
     */
    public function dashboard(Request $request)
    {
        $periods = SurveyPeriod::with('tahunAkademik')
            ->withCount([
                'responses' => function ($q) {
                    $q->whereNotNull('submitted_at');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Survey/Dashboard', [
            'periods' => $periods,
        ]);
    }

    /**
     * Show single response with all answers
     */
    public function show(SurveyResponse $response)
    {
        $response->load(['mahasiswa', 'dosen', 'kelasMatakuliah.mataKuliah', 'period.template.questions.options', 'answers.option']);

        $answersFormatted = $response->answers->map(function ($answer) use ($response) {
            $question = $response->period->template->questions->firstWhere('id', $answer->survey_question_id);
            return [
                'question' => $question?->pertanyaan ?? 'Unknown',
                'kategori' => $question?->kategori,
                'tipe' => $question?->tipe,
                'answer' => $answer->option?->label ?? $answer->text_answer ?? '-',
                'nilai' => $answer->option?->nilai,
            ];
        });

        return Inertia::render('Survey/ResponseDetail', [
            'response' => [
                'id' => $response->id,
                'mahasiswa' => $response->mahasiswa?->nama,
                'nim' => $response->mahasiswa?->nim,
                'dosen' => $response->dosen?->nama,
                'matakuliah' => $response->kelasMatakuliah?->mataKuliah?->nama,
                'submitted_at' => $response->submitted_at?->format('d/m/Y H:i'),
            ],
            'answers' => $answersFormatted,
        ]);
    }

    /**
     * Analytics page with charts and filters
     */
    public function analytics(Request $request)
    {
        $periods = SurveyPeriod::with('tahunAkademik')->orderBy('created_at', 'desc')->get();
        $selectedPeriodId = $request->get('period_id', $periods->first()?->id);

        $dosenFilter = $request->get('dosen_id');
        $kelasFilter = $request->get('kelas_id');

        $chartData = [];
        $dosenList = [];
        $kelasList = [];

        if ($selectedPeriodId) {
            $period = SurveyPeriod::with('template.questions.options')->find($selectedPeriodId);

            if ($period) {
                $query = SurveyResponse::where('survey_period_id', $period->id)
                    ->whereNotNull('submitted_at')
                    ->with(['answers.option', 'dosen', 'kelasMatakuliah.mataKuliah']);

                // Apply filters
                if ($dosenFilter) {
                    $query->where('dosen_id', $dosenFilter);
                }
                if ($kelasFilter) {
                    $query->where('kelas_matakuliah_id', $kelasFilter);
                }

                $responses = $query->get();

                // Get unique dosen and kelas for filters
                $dosenList = $responses->pluck('dosen')->unique()->filter()->values()->map(fn($d) => ['id' => $d->id, 'nama' => $d->nama]);
                $kelasList = $responses->pluck('kelasMatakuliah')->unique()->filter()->values()->map(fn($k) => ['id' => $k->id, 'nama' => $k->mataKuliah?->nama]);

                // Build chart data per question
                $allAnswers = $responses->flatMap(fn($r) => $r->answers);

                foreach ($period->template->questions as $question) {
                    if ($question->tipe === 'scale') {
                        $questionAnswers = $allAnswers->where('survey_question_id', $question->id);

                        $distribution = $question->options->map(fn($opt) => [
                            'label' => $opt->label,
                            'nilai' => $opt->nilai,
                            'count' => $questionAnswers->where('survey_option_id', $opt->id)->count(),
                        ]);

                        $scaleValues = $questionAnswers->filter(fn($a) => $a->option?->nilai !== null)->map(fn($a) => $a->option->nilai);

                        $chartData[] = [
                            'question' => $question->pertanyaan,
                            'kategori' => $question->kategori,
                            'avg' => round($scaleValues->avg() ?? 0, 2),
                            'total' => $questionAnswers->count(),
                            'distribution' => $distribution,
                            'labels' => $distribution->pluck('label'),
                            'values' => $distribution->pluck('count'),
                        ];
                    }
                }
            }
        }

        return Inertia::render('Survey/Analytics', [
            'periods' => $periods,
            'selectedPeriodId' => $selectedPeriodId,
            'chartData' => $chartData,
            'dosenList' => $dosenList,
            'kelasList' => $kelasList,
            'filters' => [
                'dosen_id' => $dosenFilter,
                'kelas_id' => $kelasFilter,
            ],
        ]);
    }

    /**
     * Data View - List all responses with answers for a period
     */
    public function dataView(Request $request, SurveyPeriod $period)
    {
        $query = SurveyResponse::where('survey_period_id', $period->id)
            ->whereNotNull('submitted_at')
            ->with(['mahasiswa.prodi', 'dosen', 'kelasMatakuliah.mataKuliah', 'answers.option']);

        // Apply filters
        if ($request->filled('prodi_id')) {
            $query->whereHas('mahasiswa', fn($q) => $q->where('prodi_id', $request->prodi_id));
        }
        if ($request->filled('kelas_id')) {
            $query->where('kelas_matakuliah_id', $request->kelas_id);
        }

        $responses = $query->orderBy('submitted_at', 'desc')->get();

        $respondents = $responses->map(fn($r) => [
            'id' => $r->id,
            'mahasiswa' => $r->mahasiswa?->nama ?? 'Unknown',
            'nim' => $r->mahasiswa?->nim ?? '-',
            'prodi' => $r->mahasiswa?->prodi?->nama ?? '-',
            'dosen' => $r->dosen?->nama ?? 'Unknown',
            'kelas' => $r->kelasMatakuliah?->mataKuliah?->nama ?? '-',
            'kelas_id' => $r->kelas_matakuliah_id,
            'submitted_at' => $r->submitted_at?->format('d/m/Y H:i'),
            'answer_count' => $r->answers->count(),
        ]);

        // Get filter options from all responses in this period
        $allResponses = SurveyResponse::where('survey_period_id', $period->id)
            ->whereNotNull('submitted_at')
            ->with(['mahasiswa.prodi', 'kelasMatakuliah.mataKuliah'])
            ->get();

        $prodiList = $allResponses->pluck('mahasiswa.prodi')->filter()->unique('id')->values();
        $kelasList = $allResponses->pluck('kelasMatakuliah')->filter()->unique('id')->map(fn($k) => [
            'id' => $k->id,
            'nama' => $k->mataKuliah?->nama ?? 'Kelas ' . $k->id,
        ])->values();

        return Inertia::render('Survey/DataView', [
            'period' => $period,
            'respondents' => $respondents,
            'prodiList' => $prodiList,
            'kelasList' => $kelasList,
            'filters' => [
                'prodi_id' => $request->prodi_id,
                'kelas_id' => $request->kelas_id,
            ],
        ]);
    }

    /**
     * Chart View - Visualize responses with bar charts
     */
    public function chartView(Request $request, SurveyPeriod $period)
    {
        $period->load('template.questions.options');

        $query = SurveyResponse::where('survey_period_id', $period->id)
            ->whereNotNull('submitted_at')
            ->with(['mahasiswa.prodi', 'kelasMatakuliah', 'answers.option']);

        // Apply filters
        if ($request->filled('prodi_id')) {
            $query->whereHas('mahasiswa', fn($q) => $q->where('prodi_id', $request->prodi_id));
        }
        if ($request->filled('kelas_id')) {
            $query->where('kelas_matakuliah_id', $request->kelas_id);
        }

        $responses = $query->get();
        $allAnswers = $responses->flatMap(fn($r) => $r->answers);

        $chartData = [];
        if ($period->template) {
            foreach ($period->template->questions as $question) {
                if ($question->tipe === 'scale') {
                    $questionAnswers = $allAnswers->where('survey_question_id', $question->id);
                    $scaleValues = $questionAnswers->filter(fn($a) => $a->option?->nilai !== null)->map(fn($a) => $a->option->nilai);

                    $chartData[] = [
                        'question' => $question->pertanyaan,
                        'kategori' => $question->kategori,
                        'avg' => round($scaleValues->avg() ?? 0, 2),
                        'total' => $questionAnswers->count(),
                        'distribution' => $question->options->map(fn($opt) => [
                            'label' => $opt->label,
                            'nilai' => $opt->nilai,
                            'count' => $questionAnswers->where('survey_option_id', $opt->id)->count(),
                        ]),
                    ];
                }
            }
        }

        // Get filter options
        $allResponses = SurveyResponse::where('survey_period_id', $period->id)
            ->whereNotNull('submitted_at')
            ->with(['mahasiswa.prodi', 'kelasMatakuliah.mataKuliah'])
            ->get();

        $prodiList = $allResponses->pluck('mahasiswa.prodi')->filter()->unique('id')->values();
        $kelasList = $allResponses->pluck('kelasMatakuliah')->filter()->unique('id')->map(fn($k) => [
            'id' => $k->id,
            'nama' => $k->mataKuliah?->nama ?? 'Kelas ' . $k->id,
        ])->values();

        return Inertia::render('Survey/ChartView', [
            'period' => $period,
            'chartData' => $chartData,
            'totalResponses' => $responses->count(),
            'prodiList' => $prodiList,
            'kelasList' => $kelasList,
            'filters' => [
                'prodi_id' => $request->prodi_id,
                'kelas_id' => $request->kelas_id,
            ],
        ]);
    }

    /**
     * Matrix View - Cross tabulation of Dosen x Questions
     */
    public function matrixView(Request $request, SurveyPeriod $period)
    {
        $period->load('template.questions');

        $query = SurveyResponse::where('survey_period_id', $period->id)
            ->whereNotNull('submitted_at')
            ->with(['mahasiswa.prodi', 'kelasMatakuliah', 'dosen', 'answers.option']);

        // Apply filters
        if ($request->filled('prodi_id')) {
            $query->whereHas('mahasiswa', fn($q) => $q->where('prodi_id', $request->prodi_id));
        }
        if ($request->filled('kelas_id')) {
            $query->where('kelas_matakuliah_id', $request->kelas_id);
        }

        $responses = $query->get();

        // Get unique dosens
        $dosenList = $responses->pluck('dosen')->unique('id')->filter()->values();

        // Build matrix: rows = dosen, cols = questions
        $matrix = [];
        foreach ($dosenList as $dosen) {
            $dosenResponses = $responses->where('dosen_id', $dosen->id);
            $allAnswers = $dosenResponses->flatMap(fn($r) => $r->answers);

            $row = [
                'dosen' => $dosen->nama,
                'total_responses' => $dosenResponses->count(),
                'scores' => [],
            ];

            if ($period->template) {
                foreach ($period->template->questions as $question) {
                    if ($question->tipe === 'scale') {
                        $questionAnswers = $allAnswers->where('survey_question_id', $question->id);
                        $scaleValues = $questionAnswers->filter(fn($a) => $a->option?->nilai !== null)->map(fn($a) => $a->option->nilai);
                        $row['scores'][$question->id] = round($scaleValues->avg() ?? 0, 2);
                    }
                }
            }

            $matrix[] = $row;
        }

        $questions = $period->template?->questions->where('tipe', 'scale')->map(fn($q) => [
            'id' => $q->id,
            'pertanyaan' => $q->pertanyaan,
            'kategori' => $q->kategori,
        ]) ?? collect();

        // Get filter options
        $allResponses = SurveyResponse::where('survey_period_id', $period->id)
            ->whereNotNull('submitted_at')
            ->with(['mahasiswa.prodi', 'kelasMatakuliah.mataKuliah'])
            ->get();

        $prodiList = $allResponses->pluck('mahasiswa.prodi')->filter()->unique('id')->values();
        $kelasList = $allResponses->pluck('kelasMatakuliah')->filter()->unique('id')->map(fn($k) => [
            'id' => $k->id,
            'nama' => $k->mataKuliah?->nama ?? 'Kelas ' . $k->id,
        ])->values();

        return Inertia::render('Survey/MatrixView', [
            'period' => $period,
            'matrix' => $matrix,
            'questions' => $questions,
            'prodiList' => $prodiList,
            'kelasList' => $kelasList,
            'filters' => [
                'prodi_id' => $request->prodi_id,
                'kelas_id' => $request->kelas_id,
            ],
        ]);
    }

    /**
     * Delete a single response
     */
    public function destroy(SurveyResponse $response)
    {
        $periodId = $response->survey_period_id;
        $response->answers()->delete();
        $response->delete();

        return redirect()->route('survey.dashboard.data', $periodId)
            ->with('success', 'Response berhasil dihapus');
    }

    /**
     * Bulk delete responses
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada data yang dipilih');
        }

        $first = SurveyResponse::find($ids[0]);
        $periodId = $first?->survey_period_id;

        SurveyAnswer::whereIn('survey_response_id', $ids)->delete();
        SurveyResponse::whereIn('id', $ids)->delete();

        return redirect()->route('survey.dashboard.data', $periodId)
            ->with('success', count($ids) . ' response berhasil dihapus');
    }
}
