<?php

namespace App\Http\Controllers\Api;

use App\Models\SsoUser;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $currentUser;

    public function __construct(Request $request)
    {
        // if (isset($request->header('x-rccl-session-id'))) {
        //     $user = $request->header()->get('x-rccl-session-id');
        // } else {
        //     $this->badRequest('Session Not Set');
        // }
        // $this->currentUser = SsoUser::ofUserAccess($currentSession->user)->first();

    }

    public function successfulResponse($responseData = [])
    {
        return response([
            'success' => true,
            'status' => $responseData['status'] ?? 200,
            'code' => $responseData['code'] ?? 'S200',
            'message' => $responseData['message'] ?? 'Request Successful',
            'result' => $responseData['result'] ?? [],
        ], $responseData['status'] ?? 200);
    }
    public function internalError($result = 'Internal Server Error.')
    {
        return response([
            'success' => false,
            'status' => 500,
            'code' => 'E505',
            'result' => $message
        ], 500);
    }
    public function badRequest($result = 'Bad Request.')
    {
        return response([
            'success' => false,
            'status' => 400,
            'code' => 'E400',
            'result' => $result
        ], 400);
    }
}
