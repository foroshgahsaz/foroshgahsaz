@props([
    'amount',
    'compare' => null,
    'inline' => false,
])

@php
    use App\Support\ShopLabels;

    $hidden = ShopLabels::shopPricesHidden();
    $currentRial = ShopLabels::shopDisplayAmountInRial(filled($amount) ? (int) $amount : null);
    $compareRial = ShopLabels::shopDisplayAmountInRial(filled($compare) ? (int) $compare : null);
    $showCompare = $currentRial !== null && $compareRial !== null && $compareRial > $currentRial;
@endphp

<div {{ $attributes->class([
    'shop-price',
    'shop-price--inline' => $inline,
    'shop-price--hidden' => $hidden,
]) }}>
    @if ($hidden || $currentRial === null)
        <span class="shop-price__current shop-price__current--hidden">—</span>
    @elseif ($inline)
        <span class="shop-price__current">{{ number_format($currentRial) }} <span>ریال</span></span>
        @if ($showCompare)
            <span class="shop-price__compare">{{ number_format($compareRial) }} <span>ریال</span></span>
        @endif
    @else
        @if ($showCompare)
            <span class="shop-price__compare">{{ number_format($compareRial) }} <span>ریال</span></span>
        @endif
        <span class="shop-price__current">{{ number_format($currentRial) }} <span>ریال</span></span>
    @endif
</div>
