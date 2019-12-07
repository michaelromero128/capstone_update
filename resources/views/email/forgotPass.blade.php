<!DOCTYPE html>
<html>
  <head>
    <title>Reset Password Notification</title>
  </head>
  <body>
    <a>You are receiving this email because we received a password reset request for your account.</a>
    <br/>
    <a>Click the link below to reset your password.</a>
    <br/>
    <a href="{{\config('authController.password_reset_url')}}?token={{$token}}">Reset Password</a>
    <br/>
    This password reset link will expire in 60 minutes
    <br/>
    
    
  </body>
</html>


