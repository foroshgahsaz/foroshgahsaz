@php
    $formAction = match (true) {
        isset($category) => route('categories.show', $category),
        isset($brand) => route('brands.show', $brand),
        default => route('products.index'),
    };

    $priceMax = $priceBounds['max'] ?? 10000000;
    $priceMin = $priceBounds['min'] ?? 0;
    $currentMin = (int) request('min_price', $priceMin);
    $currentMax = (int) request('max_price', $priceMax);
    $currentMin = max($priceMin, min($currentMin, $priceMax));
    $currentMax = max($currentMin, min($currentMax, $priceMax));
    $priceStep = max(50000, (int) round($priceMax / 200));
@endphp

<div id="filterSidebarBackdrop" class="listing-filters-backdrop" data-close-filters aria-hidden="true"></div>

<aside id="filterSidebar" class="listing-filters" aria-label="فیلتر محصولات">
    <div class="listing-filters__header">
        <h2 class="listing-filters__title">فیلترها</h2>
        <button type="button" class="listing-filters__close lg:hidden" data-close-filters aria-label="بستن فیلترها">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <form action="{{ $formAction }}" method="GET" class="listing-filters__form" id="productFiltersForm">
        @if(request('search'))
            <input type="hidden" name="search" value="{{ request('search') }}">
        @endif

        <div class="listing-filters__section">
            <p class="listing-filters__label">محدوده قیمت</p>
            <div class="price-range-slider-wrap" data-price-range-wrap data-max="{{ $priceMax }}">
                <div class="price-range-track-wrap">
                    <div class="price-range-track">
                        <div class="price-range-fill"></div>
                    </div>
                    <div class="price-range-inputs">
                        <input type="range" class="price-range-min" min="{{ $priceMin }}" max="{{ $priceMax }}" step="{{ $priceStep }}" value="{{ $currentMin }}" aria-label="حداقل قیمت">
                        <input type="range" class="price-range-max" min="{{ $priceMin }}" max="{{ $priceMax }}" step="{{ $priceStep }}" value="{{ $currentMax }}" aria-label="حداکثر قیمت">
                    </div>
                </div>
                <div class="price-range-labels">
                    <span class="price-range-min-label">{{ shop_money($currentMin) }}</span>
                    <span class="price-range-max-label">{{ shop_money($currentMax) }}</span>
                </div>
            </div>
            <input type="hidden" name="min_price" class="price-range-min-input" value="{{ request('min_price') }}">
            <input type="hidden" name="max_price" class="price-range-max-input" value="{{ request('max_price') }}">
        </div>

        @unless(isset($category))
            <div class="listing-filters__section">
                <p class="listing-filters__label">دسته‌بندی</p>
                <div class="listing-filters__options">
                    @foreach($categories as $cat)
                        <label class="listing-filters__check">
                            <input type="radio" name="category_id" value="{{ $cat->id }}"
                                   @checked((string) request('category_id', $category->id ?? '') === (string) $cat->id)>
                            <span>{{ $cat->name }}</span>
                        </label>
                    @endforeach
                    <label class="listing-filters__check">
                        <input type="radio" name="category_id" value=""
                               @checked(! request('category_id') && ! isset($category))>
                        <span>همه دسته‌ها</span>
                    </label>
                </div>
            </div>
        @endunless

        @unless(isset($brand))
            <div class="listing-filters__section">
                <p class="listing-filters__label">برند</p>
                <div class="listing-filters__options">
                    @foreach($brands as $b)
                        <label class="listing-filters__check">
                            <input type="radio" name="brand_id" value="{{ $b->id }}"
                                   @checked((string) request('brand_id', $brand->id ?? '') === (string) $b->id)>
                            <span>{{ $b->name }}</span>
                        </label>
                    @endforeach
                    <label class="listing-filters__check">
                        <input type="radio" name="brand_id" value=""
                               @checked(! request('brand_id') && ! isset($brand))>
                        <span>همه برندها</span>
                    </label>
                </div>
            </div>
        @endunless

        <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
        <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

        <div class="listing-filters__actions">
            <button type="submit" class="listing-filters__submit">اعمال فیلتر</button>
            @if(request()->hasAny(['search', 'category_id', 'brand_id', 'min_price', 'max_price', 'sort']))
                <a href="{{ $formAction }}" class="listing-filters__reset">پاک کردن</a>
            @endif
        </div>
    </form>
</aside>
