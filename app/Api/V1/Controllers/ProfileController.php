<?php

namespace App\Api\V1\Controllers;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Auth;
use Validator;
use Illuminate\Http\Request;
use App\User;
use jeremykenedy\LaravelRoles\Models\Role;

class ProfileController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', []);
    }

    public function show($id)
    {
        if (Auth::guard()->user()->hasRole('user')) {
          if (Auth::guard()->user()->id != $id ) {
              throw new HttpException(403, "Access denied to view other profile");
          }
        }

        //$user = User::findOrFail($id);
        $user = User::findOrFail($id);
        $user['role'] = $user->role();

        return response()
        ->json(['message' => 'OK',
                'user' => $user,
                ]);
    }

    public function update($id, Request $request)
    {
        if (Auth::guard()->user()->hasRole('user')) {
          if (Auth::guard()->user()->id != $id ) {
              throw new HttpException(403, "Admin role can not modify other admin user");
          }
        }

        $user = User::find($id);
        if (! $user) {
            return response(['message' => 'User not found'], 500);
        }

        $rules = [];
        $rules['email'] = 'required|min:3|email|unique:users,email,'. $id;
        $rules['name'] = 'required|min:3|max:100';
        if($request->has('password') && $request->password != "") {
            $rules['password'] = 'required|confirmed|min:6';
        }

        $validator = \Validator::make($request->input(),$rules);
        if ($validator->fails()) {
            throw new HttpException(400, $validator->errors()->first());
        }

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->password != "") {
            $user->password = $request->password;
        }

        $user->save();

        //default response is 200
        return response([
            'message' => 'Profile updated'
        ]);
    }

}
