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
        }

        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .credentials {
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
        }

        .credentials p {
            margin: 5px 0;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">Welcome Aboard, {{ $credentials['name'] }}!</div>
        <p>We are thrilled to have you as a partner operator on {{ env('APP_NAME') }}. Your account has been
            successfully created.</p>
        <p>You can now log in to the admin panel to manage your fleet, trips, and more. Please use the following
            credentials:</p>

        <div class="credentials">
            <p><strong>Login URL:</strong> <a href="{{ $credentials['login_url'] }}">{{ $credentials['login_url'] }}</a>
            </p>
            <p><strong>Username/Email:</strong> {{ $credentials['email'] }}</p>
            <p><strong>Password:</strong> {{ $credentials['password'] }}</p>
        </div>

        <p>We recommend changing your password after your first login for security purposes.</p>
        <p>If you have any questions, feel free to contact our support team.</p>
        <div class="footer">Thank you,<br>The {{ env('APP_NAME') }} Team</div>
    </div>
</body>

</html>
