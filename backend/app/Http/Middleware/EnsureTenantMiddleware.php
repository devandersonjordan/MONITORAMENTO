<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Reads company_id from the authenticated user and sets it on the request
     * for downstream use. Admin users can optionally switch company via the
     * X-Company-Id header.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $companyId = $user->company_id;

        // Admin users may switch company context via header
        if ($user->hasRole('admin') && $request->hasHeader('X-Company-Id')) {
            $companyId = (int) $request->header('X-Company-Id');
        }

        if (! $companyId) {
            return response()->json([
                'message' => 'No company associated with this user.',
            ], 403);
        }

        $request->merge(['tenant_company_id' => $companyId]);

        return $next($request);
    }
}
