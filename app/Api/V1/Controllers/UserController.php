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
use App\RequestSettings;
use App\RequestLog;
use jeremykenedy\LaravelRoles\Models\Role;

class UserController extends Controller
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

    public function index()
    {
        $users = \DB::table('users')
            ->leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
            ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
            ->select(\DB::raw(
                  'users.id, users.name, users.email, users.status,  roles.slug as role, roles.name as rolename '))
            ->get();

        if (Auth::guard()->user()->role() === "admin") {
            $users = $users
            ->where('role', '!=','superadmin')
            ->where('id', '!=', Auth::guard()->user()->id)
            ->all();
        }

        return response()
            ->json(['message' => 'OK',
                'users' => $users,
            ]);
    }

    public function show($idfilter)
    {
        $sql = "SELECT users.id, users.name, users.email, users.status, roles.slug as role, roles.name as rolename " .
               "FROM users ".
               "LEFT JOIN role_user ON users.id = role_user.user_id ".
               "LEFT JOIN roles ON role_user.role_id = roles.id ";

         $sqlWhere = "";
         if ($idfilter == 0) { // all user level
             $sqlWhere = " roles.slug = 'user' ";
         }
         else if ($idfilter == 1) { // all verified user level
             $sqlWhere = " roles.slug = 'user' AND users.status = 'verified' " ;
         }
         else if ($idfilter == 2) { // all unverified user level
             $sqlWhere = " roles.slug = 'user' AND users.status = 'unverified' " ;
         }
         else if ($idfilter == 3) {// all inactive
             $sqlWhere = " users.status = 'inactive' ";
         }
         else{ // ALL the user
         }

         if (Auth::guard()->user()->hasRole('admin')) {
             if (!empty($sqlWhere)) $sqlWhere =  $sqlWhere . " AND ";

             $sqlWhere = $sqlWhere . " users.id != " . Auth::guard()->user()->id .
                                     " AND roles.slug != 'superadmin' ";
         }

         if (!empty($sqlWhere))  {
             $sql = $sql . " WHERE " . $sqlWhere ;
         }

         // \DB::connection()->enableQueryLog();

         $user = \DB::select($sql);

         // $query = \DB::getQueryLog();
         // $lastQuery = end($query);
         // \Log::info($lastQuery);

        //if (!$user) {
        //    return response(['message' => 'User not found'], 500 );
        //}

        return response()
        ->json(['message' => 'OK',
                'user' => $user,
            ]);
    }

    public function store(Request $request)
    {
        $rules = [];
        $rules['email']     = 'required|min:3|email|unique:users';
        $rules['name']      = 'required|min:3|max:100';
        $rules['password']  = 'required|confirmed|min:6';
        $rules['role']      = 'required|string';
        $rules['status']    = 'required|string';

        $validator = \Validator::make($request->input(),$rules);

        if ($validator->fails()) {
            throw new HttpException(400, $validator->errors()->first());
        }

        if (Auth::guard()->user()->role() === "admin") {
            if ($request->input('role') === "superadmin") {
                throw new HttpException(400, "Admin role can not add superadmin user");
            }
        }

        \DB::beginTransaction();
        try{
                // insert new user
                $user = new User;
                $user->name = $request->input('name');
                $user->email = $request->input('email');
                $user->password = $request->input('password');
                $user->status = $request->input('status');
                $user->save();

                //insert new user roles
                $role = Role::where('slug', '=', $request->input('role'))->first();
                $user->attachRole($role);

            \DB::commit();
        }catch(\Exception $e){
            \DB::rollback();
            throw new HttpException(500, $e->getMessage());
        }

        return response([
            'message' => 'New user created'
        ], 201);
    }

    public function update($id, Request $request)
    {
        $user = User::find($id);
        if (! $user) {
            throw new HttpException(500, 'User not found');
        }

        $rules = [];
        $rules['email']     = 'required|min:3|email|unique:users,email,'. $id;
        $rules['name']      = 'required|min:3|max:100';
        $rules['role']      = 'required|string';
        $rules['status']    = 'required|string';

        if($request->has('password') && $request->password != "") {
            $rules['password'] = 'required|confirmed|min:6';
        }

        $validator = \Validator::make($request->input(),$rules);

        if ($validator->fails()) {
            throw new HttpException(400, $validator->errors()->first());
        }

        if (Auth::guard()->user()->role() === "admin") {
            if ($user->hasRole(['superadmin'])) {
                throw new HttpException(400, "Admin role can not modify superadmin user");
            }
        }

        \DB::beginTransaction();
        try{
            //update user
            $user->name = $request->name;
            $user->email = $request->email;
            $user->status = $request->status;
            if ($request->password != "") {
                $user->password = $request->password;
            }
            $user->save();

            //update user roles
            $role = Role::where('slug', '=', $request->input('role'))->first();
            $user->syncRoles($role);

            \DB::commit();
        }catch(\Exception $e){
            \DB::rollback();
            throw new HttpException(500, $e->getMessage());
        }

        return response(['message' => 'User updated']); // default response is 200
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (Auth::guard()->user()->id == $id ) {
            throw new HttpException(403, "You can not delete yourself");
        }

        $user = User::find($id);
        if (! $user) {
            throw new HttpException(500, 'User not found');
        }

        if (Auth::guard()->user()->role() === "admin") {
            if ($user->hasRole(['superadmin'])) {
                throw new HttpException(400, "Admin role can not delete superadmin user");
            }
        }

        \DB::beginTransaction();
        try{
            $user->detachAllRoles(); //delete record at role_user table
            $user->destroy($user->id);

            \DB::commit();
        }catch(\Exception $e){
            \DB::rollback();
            throw new HttpException(500, $e->getMessage());
        }

        return response(['message' => 'User deleted']);
    }
}
