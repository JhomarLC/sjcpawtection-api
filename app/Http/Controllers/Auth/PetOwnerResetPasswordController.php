<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\PetOwner;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PetOwnerResetPasswordController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::broker('petowner')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (PetOwner $petowner, string $password) {
                $petowner->forceFill([
                    'password' => Hash::make($password)
                ]);

                $petowner->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 400);
    }
}
