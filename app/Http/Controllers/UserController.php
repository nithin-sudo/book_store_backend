<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendEmailRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Validator;
use Illuminate\Validation\Rule;

/**
 * @since 06-nov-2021
 * 
 * This is the main controller that is responsible for user registration,login,user-profile 
 * refresh and logout API's.
 */
class UserController extends Controller
{
    
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
         $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * It takes a POST request and requires fields for the user to register,
     * and validates them if it is validated,creates those values in DB 
     * and returns success response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) 
    {

        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|between:2,20',
            'lastname' => 'required|string|between:2,20',
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
            'mobile' => 'required|digits:10',
            'role' =>  [Rule::in(['user', 'admin'])],
        ]);

        if($validator->fails())
        {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $useremail = User::where('email', $request->email)->first();
        $mobile = User::where('mobile', $request->mobile)->first();
        if ($useremail && !$mobile)
        {
            return response()->json(['message' => 'The email has already been taken'],401);
        }
        else if($mobile && !$useremail)
        {
            return response()->json(['message' => 'The mobile number has already been taken'],401);
        }
        else if($useremail && $mobile)
        {
            return response()->json(['message' => 'Both mobile number and email has already been taken'],401);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));
 
        Log::info('Registered user Email : '.'Email Id :'.$request->email);        

        $value = Cache::remember('users', 0.5, function () {
            return DB::table('users')->get();
        });

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

     /**
     * Takes the POST request and user credentials checks if it correct,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
    	$validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) 
        {
            return response()->json($validator->errors(), 422);
        }

        $value = Cache::remember('users', 1, function () {
            return User::all();
        });
        
        $user = User::where('email', $request->email)->first();
        if(!$user)
        {
            Log::error('User failed to login.', ['id' => $request->email]);
            return response()->json([
                     'message' => 'we can not find the user with that e-mail address You need to register first'
                  ], 401);
        }
         
        if (!$token = auth()->attempt($validator->validated()))
        {  
            return response()->json(['error' => 'Invalid Credentials'], 401);
        }

        Log::info('Login Success : '.'Email Id :'.$request->email ); 
       
        return response()->json([ 
            'message' => 'Login successfull',  
            'access_token' => $token
        ],200);
    }

    /**
     * refreshes and gives a new token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() 
    {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function logout() 
    {
        try
        {
            auth()->logout();
            return response()->json(['message' => 'User successfully signed out'],201);
        }
        catch(RouteNotFoundException $e)
        {
            return response()->json(['message' => 'Route [login] not defined'],500);
        }
        
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
        ]);
    }
}
