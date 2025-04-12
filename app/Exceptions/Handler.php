<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\ForbiddenHttpException;

class Handler extends Exception
{
    public function render($request, Throwable $e): \Symfony\Component\HttpFoundation\Response
    {
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'status' => 'failed',
                'error' => true,
                'message' => ['Unauthenticated.']
            ], 401);
        }

        if ($exception instanceof ForbiddenHttpException) {
            // Return a custom JSON response for 403 errors
            return response()->json([
                'status' => 'failed',
                'error' => true,
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }
    
        return parent::render($request, $e);
    }
    
}
