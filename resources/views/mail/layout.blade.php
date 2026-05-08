<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #f5f5f5; color: #333; }
  .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
  .header { background: #1a1a2e; padding: 28px 32px; }
  .header h1 { color: #fff; font-size: 20px; font-weight: 600; }
  .header p { color: #aaa; font-size: 13px; margin-top: 4px; }
  .body { padding: 32px; }
  .body p { line-height: 1.7; color: #444; margin-bottom: 16px; }
  .highlight-box { background: #f8f9ff; border-left: 4px solid #4f46e5; padding: 16px 20px; border-radius: 4px; margin: 20px 0; }
  .highlight-box .label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
  .highlight-box .value { font-size: 22px; font-weight: 700; color: #1a1a2e; margin-top: 4px; }
  .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
  .info-table td { padding: 8px 0; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
  .info-table td:first-child { color: #888; width: 140px; }
  .info-table td:last-child { font-weight: 500; color: #333; }
  .btn { display: inline-block; padding: 12px 28px; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; margin: 8px 0; }
  .footer { background: #f9f9f9; padding: 20px 32px; border-top: 1px solid #eee; text-align: center; font-size: 12px; color: #999; }
  .otp-code { font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #4f46e5; text-align: center; padding: 24px; background: #f0f0ff; border-radius: 8px; margin: 20px 0; }
  .status-paid { color: #16a34a; font-weight: 600; }
  .status-unpaid { color: #dc2626; font-weight: 600; }
</style>
</head>
<body>
<div class="wrapper">
  @yield('content')
  <div class="footer">
    This is an automated message from <strong>{{ config('app.name') }}</strong>. Please do not reply to this email.
  </div>
</div>
</body>
</html>
