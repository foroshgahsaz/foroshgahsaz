<div wire:key="variant-selector-{{ $product->id }}">
<span class="variant-loading-sentinel" wire:loading.delay.short.class="is-active" wire:target="selectAttribute, selectVariant" aria-hidden="true"></span>
@if ($this->variationAttributes->isNotEmpty())
    <div class="product-info__section">
        <p class="product-option-label">انتخاب واریانت</p>

        @foreach ($this->variationAttributes as $attribute)
            @php $values = $this->valuesForAttribute($attribute); @endphp
            @if ($values->isNotEmpty())
                <div class="mb-3" wire:key="variant-attr-group-{{ $attribute->id }}">
                    <p class="text-xs text-gray-500 mb-2 font-medium">{{ $attribute->name }}</p>
                    <div class="product-option-pills flex flex-wrap gap-2">
                        @foreach ($values as $value)
                            <button type="button"
                                    wire:click="selectAttribute({{ (int) $attribute->id }}, {{ (int) $value->id }})"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-60"
                                    wire:target="selectAttribute, selectVariant"
                                    wire:key="attr-{{ $attribute->id }}-{{ $value->id }}"
                                    class="product-storage-btn @if($this->isValueSelected((int) $attribute->id, (int) $value->id)) is-active @endif">
                                {{ $value->value }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        @if ($product->variants->count() <= 9)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-500 mb-2 font-medium">ترکیب‌های موجود</p>
                <div class="product-option-pills flex flex-wrap gap-2">
                    @foreach ($product->variants as $variant)
                        <button type="button"
                                wire:click="selectVariant({{ (int) $variant->id }})"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-60"
                                wire:target="selectAttribute, selectVariant"
                                wire:key="variant-chip-{{ $variant->id }}"
                                class="product-storage-btn @if((int) $selectedVariantId === (int) $variant->id) is-active @endif">
                            {{ $variant->name }} — {{ shop_money($variant->effective_price) }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
</div>
