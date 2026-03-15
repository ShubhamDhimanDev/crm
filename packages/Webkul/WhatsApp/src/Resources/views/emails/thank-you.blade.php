<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .wrapper { max-width: 580px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e4e4e7; }
        .header { background-color: #16a34a; padding: 32px 40px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; color: #ffffff; font-weight: 700; letter-spacing: -0.3px; }
        .body { padding: 36px 40px; }
        .body p { margin: 0 0 16px; font-size: 15px; line-height: 1.65; color: #3f3f46; }
        .message-box { background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 4px; padding: 16px 20px; margin: 24px 0; font-size: 15px; line-height: 1.65; color: #166534; }
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

            <div class="message-box">
                {!! nl2br(e($bodyText)) !!}
            </div>

            <p>We'll review your details and get back to you as soon as possible.</p>

            <p>In the meantime, feel free to reply to this email if you have any questions.</p>

            <p>Warm regards,<br><strong>{{ config('app.name') }} Team</strong></p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
