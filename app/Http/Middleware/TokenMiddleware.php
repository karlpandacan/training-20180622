<?php
namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use App\Models\SsoUser;
use App\Models\LdapSession;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * Check if the request need a token (X-RCCL-SESSION-ID)
         */
        $tokenExemption = ['api/login'];
        if (!in_array($request->path(), $tokenExemption)) {
            $requestToken = $request->header('x-rccl-session-id');
            if ($requestToken === null) {
                return response([
                    'success' => false,
                    'status' => 401,
                    'code' => 'E401',
                    'result' => 'You are not Authorized to make this request.'
                ], 401);
            }
            /**
             * Check if this is a valid session
             */
            $activeToken = LdapSession::where('sid', $requestToken)
                ->where('expiry', '>=', Carbon::now())->first();
            if (!$activeToken) {
                return response([
                    'success' => false,
                    'status' => 401,
                    'code' => 'E401',
                    'result' => 'Session Expired or Invalid.'
                ], 401);
            }
            /**
             * Add User to the Request since this is an API and have no Session
             */
            switch ($activeToken->cn) {
                case 'tm' : $applicationSession = 'travel_mart';break;
                case 'ctrac' : $applicationSession = 'ctrac_employee';break;
                case 'ctrac_app' : $applicationSession = 'ctrac_applicant';break;
                default : $applicationSession = $activeToken->cn;break;
            }
            $currentUser = SsoUser::where($applicationSession, $activeToken->user)->first();
            $request['currentUser'] = $currentUser;
        }
        return $next($request);
    }
}
