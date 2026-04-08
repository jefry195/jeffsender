<?php

namespace App\Http\Middleware;

use App\Helpers\Toastr;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotSubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = activeWorkspaceOwner();

        if (! $user->plan_data) {
            Toastr::danger(__('You are not subscribed to any plan. Please subscribe to a plan to continue.'));

            return $this->redirectToSubscription($request);
        }

        if ($user->will_expire == null) {
            Toastr::danger(__('Your subscription payment is not completed'));

            return $this->redirectToSubscription($request);
        }

        if ($user->will_expire < now()) {
            Toastr::danger(__('Your subscription payment was expired please renew the subscription'));

            return $this->redirectToSubscription($request);
        }

        return $next($request);
    }

    private function redirectToSubscription($request)
    {
        return inertia()->location('/user/subscription');
    }
}
