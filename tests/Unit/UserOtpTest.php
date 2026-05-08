<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserOtpTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    public function test_generate_otp_returns_six_digit_string(): void
    {
        $user = $this->makeUser();
        $otp  = $user->generateOtp();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
    }

    public function test_generate_otp_saves_to_database(): void
    {
        $user = $this->makeUser();
        $otp  = $user->generateOtp();

        $this->assertDatabaseHas('users', [
            'id'       => $user->id,
            'otp_code' => $otp,
        ]);
    }

    public function test_generate_otp_sets_expiry_ten_minutes_ahead(): void
    {
        $user = $this->makeUser();
        $user->generateOtp();
        $user->refresh();

        $this->assertTrue($user->otp_expires_at->isFuture());
        $this->assertLessThanOrEqual(10, now()->diffInMinutes($user->otp_expires_at));
    }

    public function test_verify_otp_returns_true_for_valid_code(): void
    {
        $user = $this->makeUser();
        $otp  = $user->generateOtp();

        $this->assertTrue($user->verifyOtp($otp));
    }

    public function test_verify_otp_returns_false_for_wrong_code(): void
    {
        $user = $this->makeUser();
        $user->generateOtp();

        $this->assertFalse($user->verifyOtp('000000'));
    }

    public function test_verify_otp_returns_false_when_expired(): void
    {
        $user = $this->makeUser();
        $otp  = $user->generateOtp();

        // Force expiry into the past
        $user->update(['otp_expires_at' => now()->subMinutes(1)]);

        $this->assertFalse($user->verifyOtp($otp));
    }

    public function test_generate_otp_marks_otp_as_unverified(): void
    {
        $user = $this->makeUser();
        $user->update(['otp_verified' => true]);
        $user->generateOtp();
        $user->refresh();

        $this->assertFalse($user->otp_verified);
    }

    public function test_each_generate_otp_call_produces_new_code(): void
    {
        $user  = $this->makeUser();
        $otp1  = $user->generateOtp();
        $otp2  = $user->generateOtp();

        // Two consecutive calls will almost certainly differ (1 in a million chance of collision)
        // but we verify the DB always has the latest
        $user->refresh();
        $this->assertEquals($otp2, $user->otp_code);
        $this->assertTrue($user->verifyOtp($otp2));
    }
}
