<?php

namespace App\Http\Controllers;

use App\Models\SurveyTemplate;
use App\Models\SurveyQuestion;
use App\Models\SurveyOption;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SurveyTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyTemplate::withCount(['questions', 'periods']);

        // Search filter
        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $templates = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Stats
        $stats = [
            'total' => SurveyTemplate::count(),
            'active' => SurveyTemplate::where('is_active', true)->count(),
            'total_questions' => SurveyQuestion::count(),
            'total_periods' => \App\Models\SurveyPeriod::where('status', 'active')->count(),
        ];

        return Inertia::render('Survey/Templates/Index', [
            'templates' => $templates,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Survey/Templates/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean',
            'questions' => 'required|array|min:1',
            'questions.*.kategori' => 'nullable|string|max:100',
            'questions.*.pertanyaan' => 'required|string',
            'questions.*.tipe' => 'required|in:scale,choice,text',
            'questions.*.is_required' => 'boolean',
            'questions.*.options' => 'array',
            'questions.*.options.*.label' => 'required|string',
            'questions.*.options.*.nilai' => 'integer',
        ]);

        $template = SurveyTemplate::create([
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        foreach ($validated['questions'] as $index => $questionData) {
            $question = $template->questions()->create([
                'kategori' => $questionData['kategori'] ?? null,
                'pertanyaan' => $questionData['pertanyaan'],
                'tipe' => $questionData['tipe'],
                'urutan' => $index,
                'is_required' => $questionData['is_required'] ?? true,
            ]);

            if (!empty($questionData['options'])) {
                foreach ($questionData['options'] as $optIndex => $optionData) {
                    $question->options()->create([
                        'label' => $optionData['label'],
                        'nilai' => $optionData['nilai'] ?? ($optIndex + 1),
                        'urutan' => $optIndex,
                    ]);
                }
            }
        }

        return redirect()->route('survey.templates.index')
            ->with('success', 'Template survei berhasil dibuat');
    }

    public function show(SurveyTemplate $template)
    {
        $template->load(['questions.options']);

        return Inertia::render('Survey/Templates/Show', [
            'template' => $template,
        ]);
    }

    public function edit(SurveyTemplate $template)
    {
        $template->load(['questions.options']);

        return Inertia::render('Survey/Templates/Edit', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, SurveyTemplate $template)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer',
            'questions.*.kategori' => 'nullable|string|max:100',
            'questions.*.pertanyaan' => 'required|string',
            'questions.*.tipe' => 'required|in:scale,choice,text',
            'questions.*.is_required' => 'boolean',
            'questions.*.options' => 'array',
            'questions.*.options.*.id' => 'nullable|integer',
            'questions.*.options.*.label' => 'required|string',
            'questions.*.options.*.nilai' => 'integer',
        ]);

        $template->update([
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Get existing question IDs
        $existingQuestionIds = $template->questions->pluck('id')->toArray();
        $updatedQuestionIds = [];

        foreach ($validated['questions'] as $index => $questionData) {
            if (!empty($questionData['id'])) {
                // Update existing question
                $question = SurveyQuestion::find($questionData['id']);
                if ($question) {
                    $question->update([
                        'kategori' => $questionData['kategori'] ?? null,
                        'pertanyaan' => $questionData['pertanyaan'],
                        'tipe' => $questionData['tipe'],
                        'urutan' => $index,
                        'is_required' => $questionData['is_required'] ?? true,
                    ]);
                    $updatedQuestionIds[] = $question->id;
                }
            } else {
                // Create new question
                $question = $template->questions()->create([
                    'kategori' => $questionData['kategori'] ?? null,
                    'pertanyaan' => $questionData['pertanyaan'],
                    'tipe' => $questionData['tipe'],
                    'urutan' => $index,
                    'is_required' => $questionData['is_required'] ?? true,
                ]);
                $updatedQuestionIds[] = $question->id;
            }

            // Handle options
            if (!empty($questionData['options'])) {
                $existingOptionIds = $question->options->pluck('id')->toArray();
                $updatedOptionIds = [];

                foreach ($questionData['options'] as $optIndex => $optionData) {
                    if (!empty($optionData['id'])) {
                        $option = SurveyOption::find($optionData['id']);
                        if ($option) {
                            $option->update([
                                'label' => $optionData['label'],
                                'nilai' => $optionData['nilai'] ?? ($optIndex + 1),
                                'urutan' => $optIndex,
                            ]);
                            $updatedOptionIds[] = $option->id;
                        }
                    } else {
                        $option = $question->options()->create([
                            'label' => $optionData['label'],
                            'nilai' => $optionData['nilai'] ?? ($optIndex + 1),
                            'urutan' => $optIndex,
                        ]);
                        $updatedOptionIds[] = $option->id;
                    }
                }

                // Delete removed options
                SurveyOption::whereIn('id', array_diff($existingOptionIds, $updatedOptionIds))->delete();
            }
        }

        // Delete removed questions
        SurveyQuestion::whereIn('id', array_diff($existingQuestionIds, $updatedQuestionIds))->delete();

        return redirect()->route('survey.templates.index')
            ->with('success', 'Template survei berhasil diperbarui');
    }

    public function destroy(SurveyTemplate $template)
    {
        $template->delete();

        return redirect()->route('survey.templates.index')
            ->with('success', 'Template survei berhasil dihapus');
    }

    public function duplicate(SurveyTemplate $template)
    {
        $newTemplate = $template->replicate();
        $newTemplate->nama = $template->nama . ' (Copy)';
        $newTemplate->save();

        foreach ($template->questions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->survey_template_id = $newTemplate->id;
            $newQuestion->save();

            foreach ($question->options as $option) {
                $newOption = $option->replicate();
                $newOption->survey_question_id = $newQuestion->id;
                $newOption->save();
            }
        }

        return redirect()->route('survey.templates.edit', $newTemplate)
            ->with('success', 'Template berhasil diduplikasi');
    }
}
