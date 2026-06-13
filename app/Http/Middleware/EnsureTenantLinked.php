<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantLinked
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->tenant_id) {
            return response()->json(
                ['message' => 'Account is not linked to a business. Please register again.'],
                403
            );
        }

        return $next($request);
    }
}
