<?php

namespace App\Http\Middleware;

use App\Enums\StatusEnum;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class DomainVerificationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $is_domain_verified = site_settings('is_domain_verified');
        $next_verification  = site_settings('next_verification');
        $current_time       = Carbon::now();
        if (is_null($is_domain_verified) || is_null($next_verification) ||($next_verification && Carbon::parse($next_verification)->lte($current_time))) {

            try {
                $params = [
                    'domain'            => url('/'),
                    'software_id'       => config('installer.software_id'),
                    'version'           => $request->input('version', ''),
                    'purchase_key'      => env('PURCHASE_KEY'),
                    'envato_username'   => env('ENVATO_USERNAME'),
                ];

                $url = 'https://verifylicense.online/api/licence-verification/check-domain';
                $response = Http::timeout(120)->post($url, $params);

                Setting::updateOrInsert(
                    ['key' => 'next_verification'],
                    ['value' => $current_time->addDays(3)]
                );


                if ($response->successful() && ($apiResponse = $response->json()) && ($apiResponse['success'] ?? false) && ($apiResponse['code'] ?? null) === 200) {


                    Setting::updateOrInsert(
                        ['key' => 'is_domain_verified'],
                        ['value' => StatusEnum::TRUE->status()]
                    );

                    Setting::updateOrInsert(
                        ['key' => 'domain_verified_at'],
                        ['value' => $current_time]
                    );

                    optimize_clear();
                    return $next($request);
                }

                Setting::updateOrInsert(['key' => 'is_domain_verified'], ['value' => StatusEnum::FALSE->status()]);

                return redirect()->route('domain.unverified')->with('error', $data['data']['error'] ?? 'Invalid Domain');

            } catch (\Exception $ex) {

                Setting::updateOrInsert(['key' => 'is_domain_verified'], ['value' => StatusEnum::FALSE->status()]);
                return redirect()->route('domain.unverified')->with('error', 'Domain verification failed.');
            }
        }

        return $next($request);
    }
}
