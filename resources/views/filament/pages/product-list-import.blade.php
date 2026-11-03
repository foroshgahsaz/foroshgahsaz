<x-filament-panels::page>
    <div class="admin-order product-price-stock-update product-list-import" dir="rtl">
        <section class="ppu-card ppu-card--filters" aria-labelledby="pli-title">
            <header class="ppu-card__head">
                <span class="ppu-card__icon ppu-card__icon--bulk" aria-hidden="true">
                    <i class="fa-solid fa-file-excel"></i>
                </span>
                <div>
                    <h2 id="pli-title" class="ppu-card__title">ورود لیست محصول از اکسل</h2>
                    <p class="ppu-card__subtitle">
                        ستون‌ها: <strong>کد کالا</strong> → SKU، <strong>شرح کالا</strong> → نام، <strong>موجودی</strong>، <strong>قیمت سایت</strong> → قیمت (تومان)
                    </p>
                </div>
            </header>

            <div class="ppu-field">
                <label class="ppu-label">
                    <input type="checkbox" wire:model="updateExistingBySku" class="ppu-checkbox">
                    به‌روزرسانی محصول موجود با همان SKU
                </label>
                <p class="ppu-bulk-field-hint">
                    محصول جدید <strong>غیرفعال</strong> و <strong>بدون دسته</strong> ایجاد می‌شود — دسته و فعال‌سازی از «بروزرسانی قیمت».
                    ردیف با SKU تکراری فقط قیمت/موجودی/نام به‌روز می‌شود.
                </p>
            </div>

            <div class="ppu-field pli-upload">
                <label class="ppu-label" for="pli-file">فایل (.xlsx, .xls, .csv)</label>
                <input
                    id="pli-file"
                    type="file"
                    wire:model="importFile"
                    accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
                    class="ppu-file-input"
                >
                @error('importFile')
                    <p class="pli-error">{{ $message }}</p>
                @enderror
                <div wire:loading wire:target="importFile" class="pli-loading">در حال بارگذاری فایل…</div>
            </div>

            <div class="pli-actions">
                <a
                    href="{{ route('filament.admin.product-import.template') }}"
                    class="ppu-save-btn pli-template-btn"
                    download
                >
                    دانلود نمونه اکسل
                </a>
                <x-filament::button
                    color="primary"
                    icon="heroicon-o-arrow-up-tray"
                    wire:click="runImport"
                    wire:loading.attr="disabled"
                    wire:target="runImport,importFile"
                >
                    ایمپورت
                </x-filament::button>
            </div>
        </section>
    </div>

    @once
        @push('styles')
            <link rel="stylesheet" href="{{ asset('css/product-price-stock-update.css') }}?v={{ @filemtime(public_path('css/product-price-stock-update.css')) ?: 0 }}">
            <link rel="stylesheet" href="{{ asset('css/product-list-import.css') }}?v={{ @filemtime(public_path('css/product-list-import.css')) ?: 0 }}">
        @endpush
    @endonce
</x-filament-panels::page>
