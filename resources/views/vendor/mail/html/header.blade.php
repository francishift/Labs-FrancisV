@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@php
    $logoPath = public_path('img/logo.png');
    if (!file_exists($logoPath)) {
        $logoPath = public_path('logo-icono.png');
    }
@endphp
@if (isset($message) && file_exists($logoPath))
    {{-- Incrustar logotipo mediante CID (adjunto inline) compatible con clientes de correo --}}
    <img src="{{ $message->embed($logoPath) }}" class="logo" alt="{{ config('app.name') }}" style="width: auto; max-width: 250px; height: auto; max-height: 50px; border: none; display: block;" />
@elseif (file_exists($logoPath))
    <img src="{{ config('app.url') }}/img/logo.png" class="logo" alt="{{ config('app.name') }}" style="width: auto; max-width: 250px; height: auto; max-height: 50px; border: none; display: block;" />
@else
    {{ config('app.name') }}
@endif
</a>
</td>
</tr>
