<?php

namespace App\Http\Controllers\Password;

use App\Http\Controllers\Controller;
use App\Models\PetOwner;
use App\Models\ResetCodePassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:reset_code_passwords',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Find the reset code record
        $passwordReset = ResetCodePassword::firstWhere('code', $request->code);

        // Check if the code has expired (valid for 1 hour)
        if ($passwordReset->created_at->addHour()->isPast()) {
            $passwordReset->delete();
            return response(['message' => trans('passwords.code_is_expire')], 422);
        }

        // Find the user by email
        $user = PetOwner::firstWhere('email', $passwordReset->email);
        // $user = PetOwner::where('email', $passwordReset->email)->first();

        // if (!$user) {
        //     return response(['message' => 'User not found.'], 404);
        // }
        // Update the user's password
        $user->update(['password' => Hash::make($request->password)]);

        // Delete the reset code record
        $passwordReset->delete();

        return response(['message' => 'Password has been successfully reset'], 200);
    }
}
