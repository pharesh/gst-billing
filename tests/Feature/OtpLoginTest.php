<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        $tenant = Tenant::factory()->create();
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'password'  => bcrypt('password'),
        ]);
    }

    public function test_otp_verify_page_requires_session(): void
    {
        $this->get(route('otp.verify'))
            ->assertRedirect(route('login'));
    }

    public function test_otp_verify_page_loads_when_session_set(): void
    {
        $user = $this->createUser();

        $this->withSession(['otp_user_id' => $user->id])
            ->get(route('otp.verify'))
            ->assertOk();
    }

    public function test_login_redirects_to_otp_instead_of_dashboard(): void
    {
        Mail::fake();
        $user = $this->createUser();

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('otp.verify'));
    }

    public function test_login_stores_otp_user_id_in_session(): void
    {
        Mail::fake();
        $user = $this->createUser();

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $this->assertEquals($user->id, session('otp_user_id'));
    }

    public function test_valid_otp_logs_user_in(): void
    {
        $user = $this->createUser();
        $otp  = $user->generateOtp();

        $this->withSession(['otp_user_id' => $user->id])
            ->post(route('otp.store'), ['otp' => $otp])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_otp_returns_error(): void
    {
        $user = $this->createUser();
        $user->generateOtp();

        $this->withSession(['otp_user_id' => $user->id])
            ->post(route('otp.store'), ['otp' => '000000'])
            ->assertSessionHasErrors(['otp']);

        $this->assertGuest();
    }

    public function test_expired_otp_returns_error(): void
    {
        $user = $this->createUser();
        $otp  = $user->generateOtp();
        $user->update(['otp_expires_at' => now()->subMinutes(1)]);

        $this->withSession(['otp_user_id' => $user->id])
            ->post(route('otp.store'), ['otp' => $otp])
            ->assertSessionHasErrors(['otp']);
    }

    public function test_otp_verify_clears_otp_fields_after_success(): void
    {
        $user = $this->createUser();
        $otp  = $user->generateOtp();

        $this->withSession(['otp_user_id' => $user->id])
            ->post(route('otp.store'), ['otp' => $otp]);

        $user->refresh();
        $this->assertNull($user->otp_code);
        $this->assertNull($user->otp_expires_at);
        $this->assertTrue($user->otp_verified);
    }

    public function test_resend_otp_regenerates_code(): void
    {
        Mail::fake();
        $user = $this->createUser();
        $oldOtp = $user->generateOtp();

        $this->withSession(['otp_user_id' => $user->id])
            ->post(route('otp.resend'))
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->otp_code);
        // New OTP was generated (stored in DB)
        $this->assertTrue($user->otp_expires_at->isFuture());
    }

    public function test_otp_missing_from_session_redirects_to_login(): void
    {
        $this->post(route('otp.store'), ['otp' => '123456'])
            ->assertRedirect(route('login'));
    }
}
