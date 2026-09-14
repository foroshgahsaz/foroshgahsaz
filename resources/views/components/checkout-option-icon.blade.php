@props(['src' => null, 'alt' => ''])

@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" class="checkout-option__icon">
@endif
