<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\LoginAuthRequest;
use App\Http\Requests\RegisterAuthRequest;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class AuthController extends Controller
{
    public function login(LoginAuthRequest $request)
    {

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response([
                'message' => ['O e-mail ou senha fornecidos estão incorretos.']
            ], 401);
        }

        if (!$user->active) {
            return response([
                'message' => ['O usuário está inativo.']
            ], 401);
        }

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        $permissions = [];
         if ($user->isAdmin()) {
            $permissions = Permission::pluck('name')->toArray();
        } else {
            $permissions = ['read-customer', 'create-customer', 'update-customer', 'delete-customer'];
        }
        

        $response = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'token' => $token,
            'permissions' => $permissions
        ];

        return response($response, 200);
    }

    public function register(RegisterAuthRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;

        $response = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'token' => $token
        ];

        return response($response, 201);
    }

    public function logout()
    {
        Auth::user()->tokens->each(function ($token, $key) {
            $token->delete();
        });

        return response()->json(['message' => 'Logout realizado com sucesso']);
    }
}
