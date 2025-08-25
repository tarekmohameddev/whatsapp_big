<?php

namespace App\Http\Middleware;

use App\Http\Utility\Api\ApiJsonResponse;
use App\Managers\GatewayManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AndroidGatewayToken
{
    protected $gatewayManager;

    public function __construct()
    {
        $this->gatewayManager   = new GatewayManager();
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return ApiJsonResponse::error(
                'Bearer token is required',
                null,
                Response::HTTP_UNAUTHORIZED
            );
        }
        if($token) {
            $androidSession = $this->gatewayManager->getAndroidSession(column: "token", value: $token, ignoreUser: true);
            if(!$androidSession)
                return ApiJsonResponse::error(
                    'Session is not authorized',
                    null,
                    Response::HTTP_UNAUTHORIZED
                );
            if ($androidSession->user) {
                Auth::guard('api')->setUser($androidSession->user);
            }
        }
        
        

        return $next($request);
    }
}