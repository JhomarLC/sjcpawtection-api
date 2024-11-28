<?php

namespace App\Http\Controllers\Password\Veterinarians;

use App\Http\Controllers\Controller;
use App\Models\VetResetCodePassword;
use Illuminate\Http\Request;

class CodeCheckController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:vet_reset_code_passwords',
        ]);

        // Find the reset code record
        $passwordReset = VetResetCodePassword::firstWhere('code', $request->code);

        // Check if the code has expired (valid for 1 hour)
        if ($passwordReset->created_at->addHour()->isPast()) {
            $passwordReset->delete();
            return response(['message' => trans('OTP is Expired!')], 422);
        }

        return response([
            'code' => $passwordReset->code,
            'message' => trans('OTP is Valid!')
        ], 200);
    }
}