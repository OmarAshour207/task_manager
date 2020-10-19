<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use JWTFactory;
use JWTAuth;
use JWTAuthException;
use App\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password'=> 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $credentials = $request->only('email', 'password');
        $token = null;
        try {
            if (! $token = auth()->guard('api')->attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials','status'  => false], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token','status'  => false], 500);
        }
        return response()->json(['token' => $token,'message' => 'Logged Successfully','status'  => true],200);
    }

    public function me()
    {
        return response()->json(auth()->guard('api')->user());
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->guard('api')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->guard('api')->factory()->getTTL() * 60
        ]);
    }

    public function logout()
    {
        auth()->guard('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
