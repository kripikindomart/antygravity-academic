<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * The names of the attributes that should not be sanitized.
     *
     * @var array<int, string>
     */
    protected $except = [
        'password',
        'password_confirmation',
        'current_password',
        'deskripsi',
        'description',
        'content',
        'body',
        'html',
        'keterangan_html', // Suggestive name
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        if ($this->clean($input, $input)) {
            $request->merge($input);
        }

        return $next($request);
    }

    /**
     * Clean the given input.
     *
     * @param  array  $input
     * @param  array  $original
     * @return bool
     */
    protected function clean(&$input, $original)
    {
        $cleaned = false;

        foreach ($input as $key => &$value) {
            if (is_array($value)) {
                if ($this->clean($value, $original)) {
                    $cleaned = true;
                }
            } elseif (is_string($value) && !in_array($key, $this->except)) {
                // Strip tags
                $newValue = strip_tags($value);
                if ($newValue !== $value) {
                    $value = $newValue;
                    $cleaned = true;
                }
            }
        }

        return $cleaned;
    }
}
