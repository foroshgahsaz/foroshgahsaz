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
                    <input type="checkbox" wire:model="updateExistingBySku" class="ppu-checkbox" @disabled($importRunning)>
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
                    @disabled($importRunning)
                >
                @error('importFile')
                    <p class="pli-error">{{ $message }}</p>
                @enderror
                <div wire:loading wire:target="importFile" class="pli-loading">در حال بارگذاری فایل…</div>
            </div>

            @if($importRunning)
                <section class="pli-progress" aria-live="polite" wire:poll.400ms="processImportTick">
                    <div class="pli-progress__head">
                        <span class="pli-progress__label">در حال ایمپورت…</span>
                        <span class="pli-progress__count">{{ number_format($importProgress) }} / {{ number_format($importTotal) }}</span>
                    </div>
                    <div class="pli-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $this->importPercent }}">
                        <div class="pli-progress__bar" style="width: {{ $this->importPercent }}%"></div>
                    </div>
                    @if($importCurrentName !== '')
                        <p class="pli-progress__current">
                            <span class="pli-progress__current-label">محصول:</span>
                            <span class="pli-progress__current-name">{{ $importCurrentName }}</span>
                        </p>
                    @endif
                </section>
            @endif

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
                    wire:click="beginImport"
                    wire:loading.attr="disabled"
                    wire:target="beginImport,importFile"
                    :disabled="$importRunning"
                >
                    @if($importRunning)
                        در حال ایمپورت…
                    @else
                        ایمپورت
                    @endif
                </x-filament::button>
            </div>
        </section>

        @if($importFinished)
            <section class="ppu-card pli-summary" aria-labelledby="pli-summary-title">
                <header class="ppu-card__head">
                    <span class="ppu-card__icon ppu-card__icon--filter" aria-hidden="true">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </span>
                    <div>
                        <h2 id="pli-summary-title" class="ppu-card__title">نتیجه ایمپورت</h2>
                        <p class="ppu-card__subtitle">
                            {{ number_format($importCreated) }} محصول جدید، {{ number_format($importUpdated) }} به‌روزرسانی
                            @if($importFailures !== [])
                                — {{ number_format(count($importFailures)) }} مورد ناموفق
                            @endif
                        </p>
                    </div>
                </header>

                <div class="pli-summary__grid">
                    @if($importSuccesses !== [])
                        <div class="pli-summary__panel pli-summary__panel--ok">
                            <h3 class="pli-summary__panel-title">وارد شد</h3>
                            <ul class="pli-summary__list">
                                @foreach($importSuccesses as $item)
                                    <li>
                                        <span class="pli-summary__badge pli-summary__badge--{{ $item['action'] === 'جدید' ? 'new' : 'update' }}">{{ $item['action'] }}</span>
                                        {{ $item['name'] }}
                                    </li>
                                @endforeach
                            </ul>
                            @if($importSuccessOverflow > 0)
                                <p class="pli-summary__more">+ {{ number_format($importSuccessOverflow) }} مورد دیگر (فقط در خلاصه عددی)</p>
                            @endif
                        </div>
                    @endif

                    @if($importFailures !== [])
                        <div class="pli-summary__panel pli-summary__panel--fail">
                            <h3 class="pli-summary__panel-title">ناموفق / خطا</h3>
                            <ul class="pli-summary__list pli-summary__list--errors">
                                @foreach(array_slice($importFailures, 0, 80) as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            @if(count($importFailures) > 80)
                                <p class="pli-summary__more">+ {{ number_format(count($importFailures) - 80) }} خطای دیگر</p>
                            @endif
                        </div>
                    @elseif($importSuccesses === [])
                        <p class="pli-summary__empty">ردیفی پردازش نشد.</p>
                    @endif
                </div>
            </section>
        @endif
    </div>

    @once
        @push('styles')
            <link rel="stylesheet" href="{{ asset('css/product-price-stock-update.css') }}?v={{ @filemtime(public_path('css/product-price-stock-update.css')) ?: 0 }}">
            <link rel="stylesheet" href="{{ asset('css/product-list-import.css') }}?v={{ @filemtime(public_path('css/product-list-import.css')) ?: 0 }}">
        @endpush
    @endonce
</x-filament-panels::page>
