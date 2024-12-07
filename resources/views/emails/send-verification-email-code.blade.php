@component('mail::message')
# Email Verification Code

We received a request to verify your email. Use the code below to verify it:

@component('mail::panel')
**{{ $code }}**
@endcomponent

This code is valid for the next 10 minutes.

If you did not request an email verification, please ignore this email.

Thanks,<br>
San Jose {{ config('app.name') }}
@endcomponent
