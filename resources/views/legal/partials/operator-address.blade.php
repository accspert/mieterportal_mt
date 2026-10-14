<address>
    {{ config('legal.company') }}<br>
    {{ config('legal.street') }}<br>
    {{ config('legal.postal_code') }} {{ config('legal.city') }}<br>
    {{ config('legal.country') }}
    @if (config('legal.email'))
        <br>E-Mail: <a href="mailto:{{ config('legal.email') }}">{{ config('legal.email') }}</a>
    @endif
    @if (config('legal.phone'))
        <br>Telefon: {{ config('legal.phone') }}
    @endif
</address>
