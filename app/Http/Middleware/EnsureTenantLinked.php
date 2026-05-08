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
            // Old account with no tenant — log out and send to register
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('register')
                ->withErrors(['email' => 'Please create a new account to set up your business.']);
        }

        return $next($request);
    }
}
