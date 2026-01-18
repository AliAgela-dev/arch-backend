<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;

class AuthController extends AdminController
{
    /**
     * Authenticate user and return token.
     */
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        if (!auth()->attempt($data)) {
            return $this->unauthorized('Invalid credentials.');
        }

        $user = auth()->user();

        if ($user->status === UserStatus::inactive) {
            auth()->logout();
            return $this->forbidden('Your account is inactive. Please contact support.');
        }

        $user->update(['last_login' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return (new UserResource($user))
            ->additional([
                'token' => $token,
                'message' => 'User logged in successfully.',
            ]);
    }

    /**
     * Logout user and revoke current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->noContent();
    }
}
