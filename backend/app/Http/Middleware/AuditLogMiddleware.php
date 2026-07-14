<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    private array $auditableMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            in_array($request->method(), $this->auditableMethods)
            && $request->user()
            && $response->isSuccessful()
        ) {
            try {
                $this->logAction($request, $response);
            } catch (\Throwable) {
                // Don't break the request if audit logging fails
            }
        }

        return $response;
    }

    private function logAction(Request $request, Response $response): void
    {
        $user = $request->user();
        $method = $request->method();
        $path = $request->path();

        $action = match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => $method,
        };

        $sensitiveFields = ['password', 'equatorial_password', 'api_credentials', 'token', 'secret'];
        $inputData = collect($request->except($sensitiveFields))
            ->filter(fn($v, $k) => !in_array($k, $sensitiveFields))
            ->toArray();

        AuditLog::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => $action,
            'auditable_type' => $this->resolveAuditableType($path),
            'auditable_id' => $this->resolveAuditableId($path),
            'old_values' => null,
            'new_values' => $inputData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function resolveAuditableType(string $path): string
    {
        $segments = explode('/', str_replace('api/', '', $path));
        return $segments[0] ?? 'unknown';
    }

    private function resolveAuditableId(string $path): ?int
    {
        $segments = explode('/', str_replace('api/', '', $path));
        return isset($segments[1]) && is_numeric($segments[1]) ? (int) $segments[1] : null;
    }
}
