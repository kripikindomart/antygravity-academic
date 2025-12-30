<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::where('group', 'ai')->pluck('value', 'key');

        return Inertia::render('Settings/Ai', [
            'settings' => [
                'ai_provider' => $settings['ai_provider'] ?? 'gemini',
                'openai_api_key' => $settings['openai_api_key'] ?? '',
                'openai_model' => $settings['openai_model'] ?? 'gpt-3.5-turbo',
                'gemini_api_key' => $settings['gemini_api_key'] ?? '',
                'gemini_model' => $settings['gemini_model'] ?? 'gemini-1.5-flash',
            ]
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'ai_provider' => 'required|in:openai,gemini',
            'openai_api_key' => 'nullable|string',
            'openai_model' => 'nullable|string',
            'gemini_api_key' => 'nullable|string',
            'gemini_model' => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'ai',
                    'type' => 'string'
                ]
            );
        }

        return back()->with('success', 'Pengaturan AI berhasil disimpan.');
    }
}
