<?php

namespace App\Http\Middleware;

use App\Enums\StatusEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {

            if(!is_domain_verified())  return redirect()->route('domain.unverified')->with('error', 'Domain verification failed.');;
            return $next($request);

        } catch (\Exception $ex) {

            return redirect()->route('domain.unverified')->with('error', 'Domain verification failed.');

        }
    }
}
