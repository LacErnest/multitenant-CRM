@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.front_url')])
            {{ config('app.name')}}
        @endcomponent
    @endslot

    <h1>Commission has been marked as unpaid</h1>
    <br>
    <h3>{{$amount}} has been removed from your commission payment .</h3>

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            ©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
        @endcomponent
    @endslot

@endcomponent
