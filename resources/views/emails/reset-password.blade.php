<!DOCTYPE html>
<html>

<head>
    <title>Reset Password</title>
</head>

<body>
    <p>Hello,</p>
    <p>You requested a password reset. Click the button below to reset your password:</p>
    <p>
        <a href="{{ $resetUrl }}"
            style="display: inline-block; padding: 10px 15px; color: #fff; background-color: #007bff; text-decoration: none; border-radius: 5px;">
            Reset Password
        </a>
    </p>
    <p>If you did not request a password reset, please ignore this email.</p>
</body>

</html>
