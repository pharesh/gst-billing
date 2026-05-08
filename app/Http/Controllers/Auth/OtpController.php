<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OtpController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if (! session('otp_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/VerifyOtp', [
            'email' => $this->maskedEmail($request),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $userId = session('otp_user_id');
        if (! $userId) {
            return redirect()->route('login')->withErrors(['otp' => 'Session expired. Please login again.']);
        }

        $user = User::find($userId);

        if (! $user || ! $user->verifyOtp($request->otp)) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP. Please try again.']);
        }

        // Clear OTP and log user in
        $user->update(['otp_code' => null, 'otp_expires_at' => null, 'otp_verified' => true]);
        $request->session()->forget('otp_user_id');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resend(Request $request): RedirectResponse
    {
        $userId = session('otp_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if ($user) {
            app(NotificationService::class)->sendLoginOtp($user);
        }

        return back()->with('status', 'OTP resent successfully.');
    }

    private function maskedEmail(Request $request): string
    {
        $userId = session('otp_user_id');
        $user = $userId ? User::find($userId) : null;
        if (! $user) return '';

        [$local, $domain] = explode('@', $user->email);
        $masked = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2));
        return $masked . '@' . $domain;
    }
}
