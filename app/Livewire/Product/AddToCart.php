<?php

namespace App\Livewire\Product;

use App\Models\Product;
use App\Services\Cart\CartService;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Settings\SettingsService;
use App\Support\ShopLabels;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class AddToCart extends Component
{
    #[Locked]
    public Product $product;

    public int $quantity = 1;

    public ?int $variantId = null;

    public ?string $selectedLabel = null;

    public ?int $selectedPrice = null;

    public ?int $comparePrice = null;

    public ?int $selectedStock = null;

    public function mount(): void
    {
        $this->product->load(['variants' => fn ($q) => $q->where('is_active', true)]);

        if ($this->product->variants->isEmpty()) {
            $this->selectedPrice = $this->product->effective_price;
            $this->comparePrice = $this->product->hasDiscount() ? $this->product->price : null;
            $this->selectedStock = $this->product->stock;

            return;
        }

        $first = $this->product->variants->first();
        $this->applyVariant(
            $first->id,
            $first->name,
            $first->effective_price,
            ($first->sale_price && $first->sale_price < $first->price) ? $first->price : null,
            $first->stock,
        );
    }

    #[On('variant-changed')]
    public function onVariantChanged(
        int $variantId,
        string $label,
        int $price,
        ?int $comparePrice = null,
        ?string $image = null,
        ?int $stock = null,
    ): void {
        $this->applyVariant($variantId, $label, $price, $comparePrice, $stock);

        if ($image) {
            $this->dispatch('product-image-changed', image: $image);
        }
    }

    public function incrementQuantity(): void
    {
        $this->quantity = min(99, $this->quantity + 1);
    }

    public function decrementQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    protected function applyVariant(
        int $variantId,
        string $label,
        int $price,
        ?int $comparePrice,
        ?int $stock,
    ): void {
        $this->variantId = $variantId;
        $this->selectedLabel = $label;
        $this->selectedPrice = $price;
        $this->comparePrice = $comparePrice;
        $this->selectedStock = $stock;
    }

    public function add(CartService $cart): void
    {
        if (ShopLabels::shopPricesHidden()) {
            session()->flash('error', 'در حال حاضر خرید آنلاین غیرفعال است. لطفاً تماس بگیرید.');

            return;
        }

        if ($this->product->variants->isNotEmpty() && ! $this->variantId) {
            session()->flash('error', 'لطفاً واریانت محصول را انتخاب کنید.');

            return;
        }

        try {
            $cart->add($this->product->id, $this->quantity, $this->variantId);
            $this->dispatch('cart-updated');
            $this->js('window.toggleCartSidebar && window.toggleCartSidebar(true)');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(SettingsService $settings, PaymentGatewayCatalog $catalog)
    {
        $digipay = $settings->digipay();
        $showDigipayInstallmentPromo = ($digipay['enabled'] ?? false)
            && ($digipay['show_product_installment_promo'] ?? false);

        $site = $settings->site();

        return view('livewire.product.add-to-cart', [
            'showDigipayInstallmentPromo' => $showDigipayInstallmentPromo && ! ShopLabels::shopPricesHidden(),
            'digipayIconUrl' => $catalog->iconUrl('digipay'),
            'digipayInstallmentPromoText' => $digipay['product_installment_promo_text'] ?? '',
            'shopPricesHidden' => ShopLabels::shopPricesHidden(),
            'sitePhone' => is_string($site['phone'] ?? null) ? trim($site['phone']) : '',
        ]);
    }
}
