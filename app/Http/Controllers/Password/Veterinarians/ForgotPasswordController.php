<?php

namespace App\Http\Controllers\Password\Veterinarians;

use App\Http\Controllers\Controller;
use App\Mail\SendCodeResetPassword;
use App\Models\VetResetCodePassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:veterinarians',
        ]);

        // Delete all old codes for the user
        VetResetCodePassword::where('email', $request->email)->delete();

        // Generate a random 6-digit code
        $code = mt_rand(1000, 9999);

        // Create a new reset code record without setting updated_at
        VetResetCodePassword::withoutTimestamps(function () use ($data, $code) {
            VetResetCodePassword::create([
                'email'      => $data['email'],
                'code'       => $code,
                'created_at' => now(),
            ]);
        });

        // Send the code to the user's email
        Mail::to($request->email)->send(new SendCodeResetPassword($code));

        return response(['message' => trans('OTP Sent!')], 200);
    }
}