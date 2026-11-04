<x-filament-panels::page>
    <div class="admin-order product-price-stock-update" dir="rtl">
        <section class="ppu-card ppu-card--site-sales" aria-labelledby="ppu-site-sales-title">
            <header class="ppu-card__head">
                <span class="ppu-card__icon ppu-card__icon--bulk" aria-hidden="true">
                    <i class="fa-solid fa-store-slash"></i>
                </span>
                <div>
                    <h2 id="ppu-site-sales-title" class="ppu-card__title">غیرفعال کردن فروش</h2>
                    <p class="ppu-card__subtitle">با فعال بودن این گزینه، <strong>قیمت در کل سایت</strong> پنهان می‌شود و دکمه خرید به <strong>تماس بگیرید</strong> تغییر می‌کند.</p>
                </div>
            </header>
            <label class="ppu-site-sales-toggle">
                <input type="checkbox" wire:model.live="hideShopPrices" class="ppu-checkbox">
                <span>عدم نمایش قیمت در سایت</span>
            </label>
        </section>

        <div class="ppu-toolbar">
            <section class="ppu-card ppu-card--filters" aria-labelledby="ppu-filters-title">
                <header class="ppu-card__head">
                    <span class="ppu-card__icon ppu-card__icon--filter" aria-hidden="true">
                        <i class="fa-solid fa-filter"></i>
                    </span>
                    <div>
                        <h2 id="ppu-filters-title" class="ppu-card__title">فیلتر لیست</h2>
                        <p class="ppu-card__subtitle">محدود کردن محصولات قبل از ویرایش یا اعمال گروهی</p>
                    </div>
                </header>
                <div class="ppu-fields ppu-fields--3">
                    <div class="ppu-field">
                        <label class="ppu-label" for="ppu-filter-category">دسته‌بندی</label>
                        <select id="ppu-filter-category" wire:model.live="filterCategoryId" class="ppu-select">
                            <option value="">همه دسته‌ها</option>
                            <option value="0">بدون دسته</option>
                            @foreach($this->categoryOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ppu-field">
                        <label class="ppu-label" for="ppu-filter-brand">برند</label>
                        <select id="ppu-filter-brand" wire:model.live="filterBrandId" class="ppu-select">
                            <option value="">همه برندها</option>
                            <option value="0">بدون برند</option>
                            @foreach($this->brandOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ppu-field">
                        <label class="ppu-label" for="ppu-filter-stock">موجودی</label>
                        <select id="ppu-filter-stock" wire:model.live="filterStock" class="ppu-select">
                            <option value="all">همه</option>
                            <option value="in_stock">موجود</option>
                            <option value="out_of_stock">ناموجود</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="ppu-card ppu-card--bulk" aria-labelledby="ppu-bulk-title">
                <header class="ppu-card__head">
                    <span class="ppu-card__icon ppu-card__icon--bulk" aria-hidden="true">
                        <i class="fa-solid fa-layer-group"></i>
                    </span>
                    <div>
                        <h2 id="ppu-bulk-title" class="ppu-card__title">تغییر گروهی قیمت و موجودی</h2>
                        <p class="ppu-card__subtitle">روی <strong>همهٔ {{ number_format($this->products->total()) }} محصول</strong> مطابق فیلتر بالا</p>
                    </div>
                </header>

                <div class="ppu-bulk-hint">
                    <p>قیمت و موجودی <strong>جداگانه</strong> اعمال می‌شوند — هر بخش دکمهٔ مخصوص خودش را دارد.</p>
                </div>

                <div class="ppu-bulk-form">
                    <div class="ppu-bulk-field ppu-bulk-field--price">
                        <label class="ppu-label" for="ppu-bulk-price">درصد تغییر قیمت پایه</label>
                        <p class="ppu-bulk-field-hint">مثبت افزایش (۱۰ = +۱۰٪)، منفی کاهش.</p>
                        <div class="ppu-bulk-field-row">
                            <div class="ppu-input-wrap">
                                <input
                                    id="ppu-bulk-price"
                                    type="number"
                                    step="0.01"
                                    wire:model.defer="bulkPricePercent"
                                    class="ppu-input ppu-input--ltr"
                                    placeholder="مثلاً 10"
                                    autocomplete="off"
                                >
                                <span class="ppu-input-suffix">٪</span>
                            </div>
                            <x-filament::button
                                color="warning"
                                icon="heroicon-o-bolt"
                                wire:click="applyBulkPricePercent"
                                wire:loading.attr="disabled"
                                wire:target="applyBulkPricePercent"
                                wire:confirm="درصد قیمت روی تمام محصولات مطابق فیلتر اعمال می‌شود. موجودی تغییر نمی‌کند. ادامه می‌دهید؟"
                                class="ppu-bulk-btn ppu-bulk-btn--inline"
                            >
                                اعمال درصد قیمت
                            </x-filament::button>
                        </div>
                    </div>
                    <div class="ppu-bulk-field ppu-bulk-field--stock">
                        <label class="ppu-label" for="ppu-bulk-stock">تغییر موجودی</label>
                        <p class="ppu-bulk-field-hint">عدد مثبت یا منفی به موجودی هر محصول اضافه می‌شود.</p>
                        <div class="ppu-bulk-field-row">
                            <div class="ppu-input-wrap">
                                <input
                                    id="ppu-bulk-stock"
                                    type="number"
                                    step="1"
                                    wire:model.defer="bulkStockDelta"
                                    class="ppu-input ppu-input--ltr"
                                    placeholder="مثلاً -5"
                                    autocomplete="off"
                                >
                                <span class="ppu-input-suffix">عدد</span>
                            </div>
                            <x-filament::button
                                color="gray"
                                icon="heroicon-o-cube"
                                wire:click="applyBulkStockDelta"
                                wire:loading.attr="disabled"
                                wire:target="applyBulkStockDelta"
                                wire:confirm="تغییر موجودی روی تمام محصولات مطابق فیلتر اعمال می‌شود. قیمت تغییر نمی‌کند. ادامه می‌دهید؟"
                                class="ppu-bulk-btn ppu-bulk-btn--inline"
                            >
                                اعمال موجودی
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section class="ppu-card ppu-card--table" aria-labelledby="ppu-table-title">
            <header class="ppu-table-head">
                <div>
                    <h2 id="ppu-table-title" class="ppu-card__title">لیست محصولات</h2>
                    <p class="ppu-card__subtitle">ویرایش سطری دسته، قیمت و موجودی — پس از تغییر «ذخیره» همان ردیف را بزنید</p>
                </div>
                <span class="ppu-count-badge">{{ number_format($this->products->total()) }} محصول</span>
            </header>

            <p class="ppu-table-note">قیمت‌ها به <strong>تومان</strong> (فیلد قیمت پایه)</p>

            <div class="ppu-table-wrap">
                <table class="ppu-table">
                    <thead>
                        <tr>
                            <th class="ppu-col-id">شناسه</th>
                            <th class="ppu-col-name">نام محصول</th>
                            <th class="ppu-col-cat">دسته</th>
                            <th class="ppu-col-price">قیمت (تومان)</th>
                            <th class="ppu-col-stock">موجودی</th>
                            <th class="ppu-col-active">وضعیت</th>
                            <th class="ppu-col-date">آخرین ویرایش</th>
                            <th class="ppu-col-action"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->products as $product)
                            <tr wire:key="product-row-{{ $product->id }}">
                                <td class="ppu-col-id" dir="ltr"><span class="ppu-id">{{ $product->id }}</span></td>
                                <td class="ppu-col-name">
                                    <span class="ppu-product-name" title="{{ $product->name }}">{{ $product->name }}</span>
                                </td>
                                <td class="ppu-col-cat">
                                    <select
                                        class="ppu-select ppu-select--cell"
                                        wire:model="rowCategories.{{ $product->id }}"
                                        aria-label="دسته محصول {{ $product->id }}"
                                    >
                                        <option value="">انتخاب دسته…</option>
                                        @foreach($this->categoryOptions as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="ppu-col-price">
                                    <input
                                        type="number"
                                        min="0"
                                        class="ppu-input ppu-input--cell ppu-input--ltr"
                                        dir="ltr"
                                        wire:model="rowPrices.{{ $product->id }}"
                                        aria-label="قیمت محصول {{ $product->id }}"
                                    >
                                </td>
                                <td class="ppu-col-stock">
                                    <input
                                        type="number"
                                        min="0"
                                        class="ppu-input ppu-input--cell ppu-input--stock ppu-input--ltr"
                                        dir="ltr"
                                        wire:model="rowStocks.{{ $product->id }}"
                                        aria-label="موجودی محصول {{ $product->id }}"
                                    >
                                </td>
                                <td class="ppu-col-active">
                                    <button
                                        type="button"
                                        class="ppu-active-btn {{ $product->is_active ? 'ppu-active-btn--on' : 'ppu-active-btn--off' }}"
                                        wire:click="toggleProductActive({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleProductActive({{ $product->id }})"
                                        aria-pressed="{{ $product->is_active ? 'true' : 'false' }}"
                                        title="{{ $product->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}"
                                    >
                                        <span wire:loading.remove wire:target="toggleProductActive({{ $product->id }})">
                                            {{ $product->is_active ? 'فعال' : 'غیرفعال' }}
                                        </span>
                                        <span wire:loading wire:target="toggleProductActive({{ $product->id }})">…</span>
                                    </button>
                                </td>
                                <td class="ppu-col-date">
                                    <time class="ppu-date">{{ $product->updated_at?->shopJalali('Y/m/d H:i') ?? '—' }}</time>
                                    @if($product->lastEditedBy)
                                        <span class="ppu-editor">{{ $product->lastEditedBy->name }}</span>
                                    @endif
                                </td>
                                <td class="ppu-col-action">
                                    <button
                                        type="button"
                                        class="ppu-save-btn"
                                        wire:click="saveProduct({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="saveProduct({{ $product->id }})"
                                    >
                                        <span wire:loading.remove wire:target="saveProduct({{ $product->id }})">ذخیره</span>
                                        <span wire:loading wire:target="saveProduct({{ $product->id }})">…</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="ppu-empty">محصولی با این فیلتر یافت نشد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($this->products->hasPages())
                <div class="ppu-pagination">
                    {{ $this->products->links() }}
                </div>
            @endif
        </section>
    </div>

    @once
        @push('styles')
            <link rel="stylesheet" href="{{ asset('css/product-price-stock-update.css') }}?v={{ @filemtime(public_path('css/product-price-stock-update.css')) ?: 0 }}">
        @endpush
    @endonce
</x-filament-panels::page>
