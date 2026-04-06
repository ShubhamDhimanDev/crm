<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We'd love to know more</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .wrapper { max-width: 580px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e4e4e7; }
        .header { background-color: #7c3aed; padding: 32px 40px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; color: #ffffff; font-weight: 700; letter-spacing: -0.3px; }
        .body { padding: 36px 40px; }
        .body p { margin: 0 0 16px; font-size: 15px; line-height: 1.65; color: #3f3f46; }
        .cta-wrap { text-align: center; margin: 32px 0; }
        .cta-btn { display: inline-block; background-color: #7c3aed; color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-size: 15px; font-weight: 600; letter-spacing: 0.2px; }
        .cta-btn:hover { background-color: #6d28d9; }
        .link-fallback { font-size: 12px; color: #71717a; margin-top: 8px; word-break: break-all; }
        .footer { padding: 20px 40px; border-top: 1px solid #e4e4e7; text-align: center; font-size: 12px; color: #a1a1aa; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>

        <div class="body">
            <p>Hi <strong>{{ $name }}</strong>,</p>

            <p>We'd love to learn a little more about you and your needs so we can serve you better.</p>

            <p>It'll only take a minute — please click the button below to get started:</p>

            <div class="cta-wrap">
                <a href="{{ $linkUrl }}" class="cta-btn" target="_blank" rel="noopener noreferrer">
                    Fill Out the Form
                </a>
                <p class="link-fallback">Or copy this link: {{ $linkUrl }}</p>
            </div>

            <p>Thank you for your time — we look forward to connecting!</p>

            <p>Warm regards,<br><strong>{{ config('app.name') }} Team</strong></p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
