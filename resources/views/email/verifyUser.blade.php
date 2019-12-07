<!DOCTYPE html>
<html>
  <head>
    <title>Welcome Email</title>
  </head>
  <body>
    <h2>Welcome to the site {{$user['name']}}</h2>
    <br/>
    Your registered email-id is {{$user['email']}} , Here is the verification token to verify your email <strong>{{$user->verifyUser->token}}</strong>
    <br/>
    Click the link below to verify your email.
    <br/>
    <a href="{{\config('authController.front_url')}}?id={{$user->id}}">Verify Email</a>
  </body>
</html>