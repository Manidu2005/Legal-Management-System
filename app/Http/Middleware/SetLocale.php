<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported application locales.
     *
     * @var list<string>
     */
    public const SUPPORTED_LOCALES = ['en', 'si', 'ta'];

    /**
     * Handle an incoming request.
     *
     * Resolves the locale for this request — preferring the authenticated
     * user's saved preference, then falling back to whatever was last set
     * in the session, then the app default — and applies it for the
     * duration of the request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()->locale ?? session('locale', config('app.locale'));

        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
