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
        $user = Auth::user();
        $dosenId = $user->dosen?->id;

        $periods = SurveyPeriod::with('tahunAkademik')->orderBy('created_at', 'desc')->get();
        $selectedPeriodId = $request->get('period_id', $periods->first()?->id);

        $stats = ['total_responses' => 0, 'avg_score' => 0];
        $questionStats = [];

        if ($selectedPeriodId) {
            $period = SurveyPeriod::with('template.questions.options')->find($selectedPeriodId);

            if ($period) {
                // Query Responses Directly
                $query = SurveyResponse::where('survey_period_id', $period->id)
                    ->whereNotNull('submitted_at');

                // Filter by dosen if not admin
                if ($dosenId && !$user->hasRole(['admin', 'akademik', 'kaprodi'])) {
                    $query->where('dosen_id', $dosenId);
                }

                // DEBUG RELATIONSHIPS
                $responses = $query->get();

                try {
                    $responses->load('answers.option');
                } catch (\Exception $e) {
                    dd("ERROR LOADING ANSWERS: " . $e->getMessage());
                }

                try {
                    $responses->load('kelasMatakuliah.mataKuliah');
                } catch (\Exception $e) {
                    dd("ERROR LOADING KELAS/MK: " . $e->getMessage());
                }

                try {
                    $responses->load('dosen');
                } catch (\Exception $e) {
                    dd("ERROR LOADING DOSEN: " . $e->getMessage());
                }

                // Calculate aggregated stats
                $allAnswers = $responses->flatMap(fn($r) => $r->answers);

                foreach ($period->template->questions as $question) {
                    if ($question->tipe === 'scale') {
                        $questionAnswers = $allAnswers->where('survey_question_id', $question->id);
                        $avgScore = $questionAnswers->avg(fn($a) => $a->option?->nilai ?? 0);

                        $questionStats[] = [
                            'question' => $question->pertanyaan,
                            'kategori' => $question->kategori,
                            'avg_score' => round($avgScore, 2),
                            'total_responses' => $questionAnswers->count(),
                            'distribution' => $question->options->map(fn($opt) => [
                                'label' => $opt->label,
                                'count' => $questionAnswers->where('survey_option_id', $opt->id)->count(),
                            ]),
                        ];
                    }
                }

                $stats = [
                    'total_responses' => $responses->count(),
                    'avg_score' => round($allAnswers->avg(fn($a) => $a->option?->nilai ?? 0), 2),
                ];
            }
        }

        return Inertia::render('Survey/Dashboard', [
            'periods' => $periods,
            'selectedPeriodId' => $selectedPeriodId,
            'stats' => $stats,
            'questionStats' => $questionStats,
        ]);
    }
}
