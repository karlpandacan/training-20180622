<?php
namespace App\Http\Middleware;
use Closure;
use Carbon\Carbon;
use App\Models\Token;
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
        dd($request->path());
        if ('api/login' !== $request->path()) {
            $requestToken = $request->header('x-rccl-session-id');
            if ($requestToken === null) {
                return response([
                    'success' => false,
                    'status' => 401,
                    'code' => 'E401',
                    'result' => 'You are not Authorized to make this request.'
                ], 401);
            }
            $activeToken = Token::where('token', $requestToken)
                ->where('expires_at', '>=', Carbon::now())->first();
            if (!$activeToken) {
                return response([
                    'success' => false,
                    'status' => 401,
                    'code' => 'E401',
                    'result' => 'Session Expired or Invalid.'
                ], 401);
            }
            $request['requestUserId'] = $activeToken->user_id;
        }
        return $next($request);
    }
}
