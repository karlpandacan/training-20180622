<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\SsoUser;
use App\Models\LdapSession;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;

class AuthController extends Controller
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function login(Request $request)
    {
        try {
            if (!$request->has('username') or !$request->has('password')) {
                return $this->badRequest([
                    'message' => 'Invalid Parameters.',
                    'required-fields' => ['username', 'password']
                ]);
            }
            $user = SsoUser::ofUserAccess($request->input('username'))
                ->where('passwd', SsoUser::encryptPassword($request->input('password')))
                ->first();
            if ($user === null) {
                return $this->badRequest('Invalid Username or Password.');
            }
            if ((int)$user->status === 0) {
                return $this->badRequest('User is Inactive.');
            }
            if ((int)$user->is_migrated === 0) {
                return $this->badRequest('The user is not yet migrated.');
            }
            $sessionDetails = [];
            $applications = [
                'tm' => 'travel_mart',
                'mstr' => 'mstr',
                'rclcrew' => 'rclcrew',
                'ctrac' => 'ctrac_employee',
                'ctrac_app' => 'ctrac_applicant'
            ];
            foreach ($applications as $key => $application) {
                if ($user->{$key} !== null) {
                    LdapSession::where('user', $user->{$key})
                        ->where('expiry', '>=', Carbon::now())
                        ->update(['expiry' => Carbon::now()]);
                    $tokenizer = md5(base64_encode(openssl_random_pseudo_bytes(512))) . '-' .
                        md5($user->rw_id . Carbon::now()->timestamp) . '-' .
                        md5(mt_rand() . Carbon::now()->timestamp) . '-' .
                        md5(Carbon::now()->timestamp) . '-' .
                        md5($user->{$key} . Carbon::now()->timestamp);
                    $token = new LdapSession;
                    $token->user = $user->{$key};
                    $token->sid = $tokenizer;
                    $token->cn = $application;
                    $token->created = Carbon::now();
                    $token->expiry = Carbon::now()->addHours(3);
                    $sessionDetails[$key] = [
                        'user' => $user->{$key},
                        'cn' => $application,
                        'sid' => $tokenizer,
                        'created' => $token->created,
                        'expiry' => $token->expiry,
                    ];
                    if (!$token->save()) {
                        return $this->internalError('Error While Logging In.');
                    }
                }
            }
            return $this->successfulResponse([
              'message' => 'Login Successful',
              'result' => $sessionDetails
            ]);
        } catch (Exception $e) {
            return $this->internalError('Error Logging In.');
        }
    }

    public function logout(Request $request)
    {
        $user = $this->currentUser;
        $applications = ['tm', 'mstr', 'rclcrew', 'ctrac', 'ctrac_app'];
        foreach ($applications as $application) {
            if ($user->{$application} !== null) {
                LdapSession::where('user', $user->{$application})
                    ->where('expiry', '>=', Carbon::now())
                    ->update(['expiry' => Carbon::now()]);
            }
        }
        return $this->successfulResponse(['result' => 'Logout Successful.']);
    }

    public function changePasswordBySession(Request $request)
    {
        if (!$request->has('current_password') or
            !$request->has('password') or
            !$request->has('confirm_password')
        ) {
            return $this->badRequest([
                'message' => 'Invalid Parameters.',
                'required-fields' => ['current_password', 'password', 'confirm_password']
            ]);
        }
        if ($request->input('password') !== $request->input('confirm_password')) {
            return $this->badRequest([
                'message' => 'Password Does Not Match',
                'password-matching' => ['password', 'confirm_password']
            ]);
        }
        $currentSession = LdapSession::where('sid', $request->header('x-rccl-session-id'))->first();
        $user = SsoUser::ofUserAccess($currentSession->user)->first();
        if ($user->passwd !== $request->input('current_password')) {
            return $this->badRequest([
                'message' => 'Current Password is Incorrect',
                'password-matching' => ['password', 'confirm_password']
            ]);
        }
    }

    public function resetPasswordByUsername(Request $request)
    {
        if (!$request->has('username') or
              !$request->has('password') or
              !$request->has('confirm_new_password')
        ) {
            return $this->badRequest([
                  'message' => 'Invalid Parameters.',
                  'required-fields' => ['username', 'password', 'confirm_password']
            ]);
        }
        if ($request->input('password') !== $request->input('confirm_password')) {
            return $this->badRequest([
                'message' => 'Password Does Not Match',
                'password-matching' => ['password', 'confirm_password']
            ]);
        }
        $user = $this->currentUser
            ->update([
                'passwd' => SsoUser::encryptPassword($request->input('password')),
                'passwd_update' => Carbon::now()
            ]);

    }
}
