<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Admin has no tenant restriction (school_id is NULL)
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        // Explicitly forbid passing tenant identifiers from the client
        if ($request->has('school_id') || $request->has('tenant_school_id')) {
            abort(400, 'Tenant identifiers must not be provided in the request.');
        }

        // School and Student must have school_id
        if (!$user->school_id) {
            abort(403, 'No school association found.');
        }

        // Store tenant context server-side (NOT request input)
        $request->attributes->set('tenant_school_id', $user->school_id);

        return $next($request);
    }
}
