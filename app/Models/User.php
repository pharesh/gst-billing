<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasIntegerPrimaryKey;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'password', 'role',
        'otp_code', 'otp_expires_at', 'otp_verified', 'is_super_admin',
    ];

    protected $hidden = [
        'password', 'remember_token', 'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'otp_verified' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function generateOtp(): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'otp_code' => $code,
            'otp_expires_at' => now()->addMinutes(10),
            'otp_verified' => false,
        ]);
        return $code;
    }

    public function verifyOtp(string $code): bool
    {
        return $this->otp_code === $code
            && $this->otp_expires_at?->isFuture();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }
}
