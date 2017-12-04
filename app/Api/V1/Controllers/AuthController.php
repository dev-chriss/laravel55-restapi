<?php

namespace App\Api\V1\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use App\Api\V1\Requests\LoginRequest;
use App\Api\V1\Requests\ForgotPasswordRequest;
use App\Api\V1\Requests\ResetPasswordRequest;
use App\Api\V1\Requests\SignUpRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tymon\JWTAuth\JWTAuth;
use Auth;
use Config;
use Validator;
use jeremykenedy\LaravelRoles\Models\Role;
use Hash;
use App\Mail\EmailVerification;
use App\Mail\EmailActivation;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Log the user in
     *
     * @param LoginRequest $request
     * @param JWTAuth $JWTAuth
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request, JWTAuth $JWTAuth)
    {
        $validator = Validator::make($request->all(), [
            'email'       => 'required|email',
            'password'    => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response([
                'message' => 'Validation fail',
                'error' => $validator->errors()
            ], 400);
        }

        $user = User::where([
            ['email', '=', $request->email]
        ])->first();

        if (!$user || !Hash::check($request->get('password'), $user->password)) {
            throw new HttpException(403, "User credential is not match");
        }

        if ($user->status != 'active' || $user->role() == null) {
            throw new HttpException(403, "User is not in active status. Please contact web admin.");
        }

        $credentials = $request->only(['email', 'password']);

        try {
            $token = Auth::guard()->attempt($credentials);

            if(!$token) {
                throw new AccessDeniedHttpException();
            }

        } catch (JWTException $e) {
            throw new HttpException(500);
        }

        $user['role'] = $user->role();
        unset($user['api_key']); // hide the API Key

        return response()
            ->json([
                'status' => 'OK',
                'user' => $user,
                'token' => $token,
                'expires_in' => Auth::guard()->factory()->getTTL() * 1440 // 1440 minutes = 1 day
            ]);
    }

    public function sendResetEmail(ForgotPasswordRequest $request)
    {
        $user = User::where('email', '=', $request->get('email'))->first();

        if(!$user) {
            throw new NotFoundHttpException('Email is not registered');
        }

        $broker = $this->getPasswordBroker();
        $sendingResponse = $broker->sendResetLink($request->only('email'));

        if($sendingResponse !== Password::RESET_LINK_SENT) {
            throw new HttpException(500);
        }

        return response()->json([
            'status' => 'OK'
        ], 200);
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    private function getPasswordBroker()
    {
        return Password::broker();
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $token = Auth::guard()->refresh();

        return response()->json([
            'status' => 'OK',
            'token' => $token,
            'expires_in' => Auth::guard()->factory()->getTTL() * 1440 // 1440 minutes = 1 day
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request, JWTAuth $JWTAuth)
    {
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->reset($user, $password);
            }
        );

        // \Log::info('$response='. $response);
        // \Log::info('PASSWORD_RESET='. Password::PASSWORD_RESET);

        if($response !== Password::PASSWORD_RESET) {
            throw new HttpException(500, trans($response));
        }

        if(!Config::get('boilerplate.reset_password.release_token')) {
            return response()->json([
                'status' => 'OK',
            ]);
        }

        $user = User::where('email', '=', $request->get('email'))->first();

        return response()->json([
            'status' => 'OK',
            'token' => $JWTAuth->fromUser($user)
        ]);
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @param  ResetPasswordRequest  $request
     * @return array
     */
    protected function credentials(ResetPasswordRequest $request)
    {
        return $request->only(
            'email', 'password', 'password_confirmation', 'token'
        );
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function reset($user, $password)
    {
        $user->password = $password;
        $user->save();
    }

    public function register(SignUpRequest $request, JWTAuth $JWTAuth)
    {
        $validator = Validator::make($request->all(), [
            'email'       => 'required|email|unique:users',
            'name'        => 'required|min:3',
            'password'    => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            throw new HttpException(400, $validator->errors()->first());
        }

        \DB::beginTransaction();
        try{
            $user = User::create([
                'name'     => $request->input('name'),
                'email'    => $request->input('email'),
                'password' => $request->input('password'),
                'status' => 'unverified', // email is not verified yet
                'verification_token' => str_random(30),
            ]);

            if (!$user) {
                throw new HttpException(500, 'Failed to create user');
            }
            $email = new EmailVerification($user);
            \Mail::to($user->email)->queue($email);

            \DB::commit();
        }catch(\Exception $e){
            \DB::rollback();
            throw new HttpException(500, $e->getMessage());
        }

        if(Config::get('boilerplate.sign_up.release_token')) {
          $token = $JWTAuth->fromUser($user);
          return response()->json([
              'status' => 'OK',
              'token' => $token
          ], 201);
        }

        return response()->json([
            'status' => 'OK'
        ], 201);
    }

    public function emailconfirmation($token)
    {
        $user = User::where('verification_token', $token)->first();
        if(!$user) {
            throw new NotFoundHttpException('Token is not match or invalid');
        }

        \DB::beginTransaction();
        try{
            $user->status  = 'verified'; // email verified
            $user->verification_token  = null;
            $user->save();

            // give user role
            $role = Role::where('slug', '=', 'user')->first();
            $user->attachRole($role);

            \DB::commit();
        }catch(\Exception $e){
            \DB::rollback();
            throw new HttpException(500, $e->getMessage());
        }
        return response()->json(['message' => 'OK']);
    }

    public function activate($id)
    {
        $user = User::find($id);
        if (! $user) {
            throw new HttpException(500, 'User not found');
        }

        \DB::beginTransaction();
        try{

            //update user
            $user->status = 'active';
            $user->save();

            //update user roles
            $role = Role::where('slug', '=', 'user')->first();
            $user->syncRoles($role);

            $email = new EmailActivation($user);
            Mail::to($user->email)->queue($email);

            \DB::commit();
        }catch(\Exception $e){
            \DB::rollback();
            throw new HttpException(500, $e->getMessage());
        }

        return response()
            ->json([
                    'message' => 'OK'
                ]);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = User::find(Auth::guard()->user()->id);

        if (! $user) {
            return response(['message' => 'User not found'], 500 );
        }

        $user['role'] = $user->role();
        unset($user['api_key']); // hide the API Key

        return response()->json($user);
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::guard()->logout();

        return response()
            ->json(['message' => 'OK']);
    }
}
