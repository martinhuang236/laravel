<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // 用户注册
    public function register(RegisterRequest $request)
    {
        // 验证请求数据
        $param = $request->validated();

        // 创建用户
        User::create([
            'name'     => $param['name'],
            'email'    => $param['email'],
            'password' => Hash::make($param['password']),
        ]);

        // 创建 Sanctum 令牌
//        $token = $user->createToken('auth_token')->plainTextToken;

        // 返回响应
        return response()->json(['message' => 'register success'], 200);
    }


    // User login
    public function login(LoginRequest $request)
    {
        $param = $request->validated();

        // Attempt authentication
        $user = User::where('email', $param['email'])->first();

        if (!$user || !Hash::check($param['password'], $user->password)) {
            return response()->json(['message' => '邮箱或密码错误'], 401);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return token
        return response()->json([
            'message'      => 'Login successful',
            'access_token' => $token,
//            'token_type'   => 'Bearer',
        ]);
    }

    // User logout
    public function logout(Request $request)
    {
        // Revoke tokens
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
