<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Password Reset Code</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h1 style="font-size: 22px; margin-bottom: 16px;">Reset your password</h1>

    <p>Hello {{ $student->name }},</p>

    <p>We received a request to reset your Breakthrough LMS password.</p>

    <p style="margin: 24px 0;">
        Your verification code is:
        <strong style="font-size: 24px; letter-spacing: 4px;">{{ $code }}</strong>
    </p>

    <p>
        You can also open this reset link:
        <br>
        <a href="{{ $resetUrl }}" style="color: #2563eb;">Reset your password</a>
    </p>

    <p>This code and link will expire soon. If you did not request this, you can ignore this email.</p>

    <p>Breakthrough LMS</p>
</body>
</html>
