@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.front_url')])
            {{ config('app.name')}}
        @endcomponent
    @endslot

    <h1>Commission payment</h1>
    <br>
    <h3>You are receiving this email because a payment related to a sales commission has been registered.</h3>
    <h5>You have received {{$amount}} as commission payment .</h5>

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            ©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
        @endcomponent
    @endslot

@endcomponent
