<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required',
            'otp'     => 'required|string|size:6',
        ]);

        $user = User::find($request->user_id);

        if (! $user || ! $user->verifyOtp($request->otp)) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP. Please try again.'],
            ]);
        }

        $user->update(['otp_code' => null, 'otp_expires_at' => null, 'otp_verified' => true]);

        // Revoke any stale 'web' tokens from previous OTP verification attempts
        $user->tokens()->where('name', 'web')->delete();

        $token = $user->createToken('web')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function resend(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required',
        ]);

        $throttleKey = 'otp-resend:'.$request->input('user_id').'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => "Too many resend attempts. Please wait {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($throttleKey, 60);

        $user = User::find($request->user_id);

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        app(NotificationService::class)->sendLoginOtp($user);

        return response()->json(['message' => 'OTP resent successfully.']);
    }
}
