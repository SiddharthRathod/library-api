<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckRolePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is authenticated
        $user = Auth::user();

        if (!$user || !$user->hasRole('admin')) {
            // Throw ForbiddenHttpException with a custom message
            throw new AccessDeniedHttpException('USER DOES NOT HAVE THE RIGHT ROLES');
        }

        return $next($request);
    }
}
