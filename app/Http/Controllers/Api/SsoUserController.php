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
    public function index(Request $request)
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
            $userData['passwd'] = encryptPassword($request->password);
            $userData['creation_date'] = Carbon::now();
            $userData['passwd_update'] = Carbon::now();
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
            $user->update($request->except(['passwd']));
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
