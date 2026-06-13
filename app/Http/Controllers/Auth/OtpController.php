<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $token = $user->createToken('web')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function resend(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required',
        ]);

        $user = User::find($request->user_id);

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        app(NotificationService::class)->sendLoginOtp($user);

        return response()->json(['message' => 'OTP resent successfully.']);
    }
}
