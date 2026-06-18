<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->guard('admin')->guest()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', Response::HTTP_UNAUTHORIZED);
            }

            // redirect()->guest() stores the intended URL so login can return here.
            return redirect()->guest(route('admin.login'))
                ->with('error', __('admin/frontend.general.login-first'));
        }

        return $next($request);
    }
}
