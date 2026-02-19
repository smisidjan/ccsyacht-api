<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.frontend_url', config('app.url'))">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
@if (config('mail.logo_url'))
<div class="footer-logo">
<img src="{{ config('mail.logo_url') }}" alt="{{ config('app.name') }}">
</div>
@endif

<p>{{ config('app.name') }}</p>
<p>Coating Consultants for Superyachts</p>

@if (config('mail.footer_links'))
<p style="margin-top: 12px;">
@foreach (config('mail.footer_links') as $label => $url)
<a href="{{ $url }}">{{ $label }}</a>@if (!$loop->last) &nbsp;|&nbsp; @endif
@endforeach
</p>
@endif

<p style="margin-top: 16px; font-size: 11px; color: #9ca3af;">
© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
</p>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
