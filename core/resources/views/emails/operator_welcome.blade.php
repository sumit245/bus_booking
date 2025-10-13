<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ env('APP_NAME') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #007bff;
            font-size: 28px;
            margin: 0;
        }

        .content {
            margin-bottom: 30px;
        }

        .content p {
            font-size: 16px;
            margin-bottom: 15px;
        }

        .credentials {
            background-color: #f8f9fa;
            padding: 20px;
            border-left: 4px solid #007bff;
            margin: 25px 0;
            border-radius: 5px;
        }

        .credentials h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
        }

        .credentials p {
            margin: 8px 0;
            font-size: 14px;
        }

        .credentials strong {
            color: #333;
        }

        .credentials a {
            color: #007bff;
            text-decoration: none;
        }

        .credentials a:hover {
            text-decoration: underline;
        }

        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .warning strong {
            color: #856404;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
            text-align: center;
        }

        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }

        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to {{ env('APP_NAME') }}!</h1>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $credentials['name'] }}</strong>,</p>

            <p>We are thrilled to have you as a partner operator on {{ env('APP_NAME') }}. Your account has been
                successfully created and is ready for use.</p>

            <p>You can now log in to the admin panel to manage your fleet, trips, bookings, and more. Here are your
                login credentials:</p>

            <div class="credentials">
                <h3>🔐 Your Login Credentials</h3>
                <p><strong>Login URL:</strong> <a
                        href="{{ $credentials['login_url'] }}">{{ $credentials['login_url'] }}</a></p>
                <p><strong>Email:</strong> {{ $credentials['email'] }}</p>
                <p><strong>Password:</strong> {{ $credentials['password'] }}</p>
            </div>

            <div class="warning">
                <strong>⚠️ Important Security Notice:</strong><br>
                For your account security, we strongly recommend changing your password immediately after your first
                login.
            </div>

            <p>If you have any questions or need assistance, please don't hesitate to contact our support team. We're
                here to help you succeed!</p>

            <p>Thank you for choosing {{ env('APP_NAME') }} as your business partner.</p>
        </div>

        <div class="footer">
            <p>Best regards,<br>
                <strong>The {{ env('APP_NAME') }} Team</strong>
            </p>

            <p style="margin-top: 20px; font-size: 12px; color: #999;">
                This is an automated email. Please do not reply to this message.
            </p>
        </div>
    </div>
</body>

</html>
