<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $next($request);
    }

}
