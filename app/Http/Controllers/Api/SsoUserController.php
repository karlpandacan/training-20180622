<?php

namespace App\Http\Controllers\Api;

use App\Models\SsoUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;

class SsoUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $response['result'] = SsoUser::paginate(10);
        return $this->successfulResponse($response);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return $this->internalError('Invalid URL');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $userData = $request->all();
            $userData['password'] = md5($request->password);
            $userData['updated_at'] = Carbon::now();
            $userData['created_at'] = Carbon::now();
            $userData['updated_by'] = $request->input('requestUserId', null);
            $userData['created_by'] = $request->input('requestUserId', null);
            User::create($userData);
        } catch (Exception $e) {
            return $this->internalError('Error Adding User');
        }
        return $this->successfulResponse(['result' => 'User Successfully Added.']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $ldap_api_enc_method = 'aes-128-cbc';
        $ldap_api_enc_iv = md5(sprintf("%s-%s", $ldap_api_enc_method, '#!/ldap/restapi/rccl/0123455@'));
        $ldap_api_enc_pass = md5(sprintf("%s-%s", $ldap_api_enc_method, '#!/ldap/restapi/rccl/9876543$'));
        $user = SsoUser::where('email', $id)->first();
        $userPassword = rtrim( base64_decode( openssl_decrypt(
            base64_decode( $user->passwd ),
            $ldap_api_enc_method,
            $ldap_api_enc_pass,
            false,
            mb_substr($ldap_api_enc_iv, 0, 16)
        ) ), "\0" );
        return $userPassword;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return $this->internalError('Invalid URL');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->updated_at = Carbon::now();
            $user->updated_by = $request->input('requestUserId', null);
            $user->update($request->except(['email', 'password']));
        } catch (Exception  $e) {
            return $this->internalError('Error Updating User');
        }
        return $this->successfulResponse(['result' => 'User Successfully Updated.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
          $user = User::findOrFail($id);
          $user->updated_by = $request->input('requestUserId', null);
          $user->delete();
        } catch (Exception  $e) {
          return $this->internalError('Error Deleting User');
        }
        return $this->successfulResponse(['result' => 'User Successfully Deleted.']);

    }
}
