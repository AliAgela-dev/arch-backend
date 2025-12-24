<?php

namespace App\Http\Controllers\auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\registerRequest;
use App\Http\Resources\User\UserResourse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        if (!auth()->attempt($data)) {
            return response()->json([
                'message' => 'Invalid credentials.'
            ], 401);
        }

        $user = auth()->user();

        if ($user->status === UserStatus::inactive) {
            auth()->logout();
            return response()->json([
                'message' => 'Your account is inactive. Please contact support.'
            ], 403);
        }

        $user->update(['last_login' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;
        return (new UserResourse($user))->additional([
            'token' => $token,
            'message' => 'User logged in successfully.'
        ])->response()->setStatusCode(200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->noContent();
    }


}
