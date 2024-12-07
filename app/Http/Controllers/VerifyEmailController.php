<?php

namespace App\Http\Controllers;

use App\Mail\VerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VerifyEmailController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:pet_owners,email',
        ]);

        $verificationCode = Str::random(6);
        $expiresAt = now()->addMinutes(10);

        DB::table('email_verifications')->updateOrInsert(
            ['email' => $request->email],
            ['verification_code' => $verificationCode, 'expires_at' => $expiresAt]
        );

        Mail::to($request->email)->send(new VerificationMail($verificationCode));

        return response()->json(['message' => 'Verification code sent to your email.'], 200);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'verification_code' => 'required',
        ]);

        $record = DB::table('email_verifications')
            ->where('email', $request->email)
            ->where('verification_code', $request->verification_code)
            ->first();

        if (!$record || $record->expires_at < now()) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 400);
        }

        DB::table('email_verifications')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Account verified']);
    }


    public function sendVet(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:veterinarians,email',
        ]);

        $verificationCode = Str::random(6);
        $expiresAt = now()->addMinutes(10);

        DB::table('vet_email_verifications')->updateOrInsert(
            ['email' => $request->email],
            ['verification_code' => $verificationCode, 'expires_at' => $expiresAt]
        );

        Mail::to($request->email)->send(new VerificationMail($verificationCode));

        return response()->json(['message' => 'Verification code sent to your email.'], 200);
    }

    public function verifyVet(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'verification_code' => 'required',
        ]);

        $record = DB::table('vet_email_verifications')
            ->where('email', $request->email)
            ->where('verification_code', $request->verification_code)
            ->first();

        if (!$record || $record->expires_at < now()) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 400);
        }

        DB::table('vet_email_verifications')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Account verified']);
    }
}
