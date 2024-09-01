@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>Reset Password Notification</h1>
<br>
<h3>You are receiving this email because we received a password reset request for your account.</h3>
<p>Please click the button below to reset your password.

@component('mail::button', ['url' => $url])
        Reset
@endcomponent

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
