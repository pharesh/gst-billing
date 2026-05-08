@extends('mail.layout')

@section('content')
<div class="header">
  <h1>Your Login OTP</h1>
  <p>GST Billing — Secure Login Verification</p>
</div>
<div class="body">
  <p>Hi <strong>{{ $userName }}</strong>,</p>
  <p>Your one-time password (OTP) for login is:</p>

  <div class="otp-code">{{ $otp }}</div>

  <p style="text-align:center;color:#888;font-size:13px;">This OTP is valid for <strong>10 minutes</strong> only.</p>

  <p style="margin-top:24px;color:#666;font-size:13px;">
    If you did not request this OTP, please ignore this email. Your account is safe.
  </p>
</div>
@endsection
