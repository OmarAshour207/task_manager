<?php

namespace App\Http\Controllers\Api\Auth;

use App\UserWorkspace;
use App\Workspace;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Hash;
use JWTFactory;
use JWTAuth;
use Validator;
use Response;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:100',
            'workspace'             => 'required|string|max:100',
            'email'                 => 'required|email|max:255|unique:users',
            'password'              => 'required|min:6|max:255|string|confirmed',
        ]);

        if($validator->fails()) {
            return response()->json(['message' => $validator->errors(),'status'  => false], 400);
        }

        $objUser = User::create([
            'name'              => $request->get('name'),
            'workspace'         => $request->get('workspace'),
            'email'             => $request->get('email'),
            'password'          => Hash::make($request->get('password')),
        ]);

        $objWorkspace = Workspace::create([
            'created_by'    => $objUser->id,
            'name'          => $request->get('workspace')
        ]);
        $objUser->currant_workspace = $objWorkspace->id;
        $objUser->save();
        UserWorkspace::create([
            'user_id'       => $objUser->id,
            'workspace_id'  => $objWorkspace->id,
            'permission'    => 'Owner']);


        $token = JWTAuth::fromUser($objUser);
        return response()->json(['token' => $token, 'message' => 'Registered Successfully','status'  => true],201);
    }

}
