<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .wrapper { max-width: 580px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e4e4e7; }
        .header { background-color: #2563eb; padding: 32px 40px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; color: #ffffff; font-weight: 700; letter-spacing: -0.3px; }
        .body { padding: 36px 40px; }
        .body p { margin: 0 0 16px; font-size: 15px; line-height: 1.65; color: #3f3f46; }
        .profile-box { background: #eff6ff; border-left: 4px solid #2563eb; border-radius: 4px; padding: 20px 24px; margin: 24px 0; font-size: 15px; line-height: 1.7; color: #1e3a5f; white-space: pre-line; }
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

            <p>We wanted to share a little more about who we are and how we can help you.</p>

            <div class="profile-box">
                {!! nl2br(e($profileText)) !!}
            </div>

            <p>We're excited about the possibility of working together. Don't hesitate to reach out if you'd like to know more.</p>

            <p>Warm regards,<br><strong>{{ config('app.name') }} Team</strong></p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
