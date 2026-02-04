<?php

namespace Winter\Storm\Foundation\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Response;

class CheckForMaintenanceMode extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next)
    {
        if ($this->app->maintenanceMode()->active() && request()->ajax()) {
            return Response::make(
                Lang::get('system::lang.page.maintenance.help'),
                503
            );
        }

        return parent::handle($request, $next);
    }
}
