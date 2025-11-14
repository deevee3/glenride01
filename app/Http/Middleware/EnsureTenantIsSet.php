<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsSet
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->tenant === null) {
            abort(403, 'Tenant context not found.');
        }

        TenantContext::set($user->tenant);

        return tap($next($request), static function (): void {
            TenantContext::forget();
        });
    }
}
