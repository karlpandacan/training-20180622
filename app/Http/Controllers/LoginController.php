<?php

namespace App\Http\Controllers;

use Session;
use Carbon\Carbon;
use App\Models\SsoUser;
use App\Models\LdapSession;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function index(Request $request)
    {
        // dd($request);
        try {
            if (!$request->has('username') or !$request->has('password')) {
                session()->flash('alert-warning', 'Username and Password is Required');
                return view('login');
            }
            $user = SsoUser::ofUserAccess($request->input('username'))
                ->where('passwd', SsoUser::encryptPassword($request->input('password')))
                ->first();
            if ($user === null) {
                session()->flash('alert-warning', 'Invalid username or password!');
                return view('login');
            }
            if ((int)$user->status === 0) {
                session()->flash('alert-warning', 'Cannot login inactive user!');
                return view('login');
            }
            if ((int)$user->is_migrated === 0) {
                session()->flash('alert-warning', 'Cannot login user not migrated!');
                return view('login');
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
                        session()->flash('alert-warning', 'Error while logging in please try again later!');
                    }
                }
            }
            session(['user' => $user]);
            session(['user_applications' => $applications]);
            return redirect()->route('home');
        } catch (Exception $e) {
            session()->flash('alert-warning', 'Error while logging in please try again later!');
            return view('login');
        }
    }

    public function logout()
    {
        $user = session()->get('user');
        $applications = ['tm', 'mstr', 'rclcrew', 'ctrac', 'ctrac_app'];
        foreach ($applications as $application) {
            if ($user->{$application} !== null) {
                LdapSession::where('user', $user->{$application})
                    ->where('expiry', '>=', Carbon::now())
                    ->update(['expiry' => Carbon::now()]);
            }
        }
        session()->forget('user');
        session()->forget('user_applications');
        session()->flush();
        session()->flash('alert-success', 'You are logged out.');
        return redirect()->route('login');
    }
}
