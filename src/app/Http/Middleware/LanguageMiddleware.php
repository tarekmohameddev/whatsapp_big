<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Language;
use App\Enums\StatusEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\RedirectResponse )  $next
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $lang_code = $request->header('X-App-Language') ?? session('locale');

            $language = Language::where('code', $lang_code)->first();
            if (!$language) {
                $lang_code = Language::where('is_default', StatusEnum::TRUE->status())->value('code') ?? 'us';
            }

            App::setLocale($lang_code);
            session(['locale' => $lang_code]);

        return $next($request);

        } catch (\Exception $ex) {
        
        }

        return $next($request);
    }
}
