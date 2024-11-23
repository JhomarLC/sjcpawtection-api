@component('mail::message')
# Password Reset Request

We received a request to reset your password. Use the code below to reset it:

@component('mail::panel')
**{{ $code }}**
@endcomponent

This code is valid for the next 60 minutes.

If you did not request a password reset, please ignore this email.

Thanks,<br>
San Jose {{ config('app.name') }}
@endcomponent
