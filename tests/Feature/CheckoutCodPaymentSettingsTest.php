<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutCodPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cod_hidden_when_disabled_in_admin(): void
    {
        app(SettingsService::class)->set('cod', 'enabled', false);

        [$user] = $this->prepareCart();

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->assertDontSee('پرداخت در محل')
            ->assertDontSee('سایر روش‌ها');
    }

    public function test_cod_uses_custom_label_from_settings(): void
    {
        app(SettingsService::class)->setMany('cod', [
            'enabled' => true,
            'display_label' => 'پرداخت هنگام تحویل کالا',
            'display_description' => 'فقط تهران',
        ]);

        [$user] = $this->prepareCart();

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->assertSee('پرداخت هنگام تحویل کالا')
            ->assertSee('فقط تهران');
    }

    public function test_gateway_display_label_override_in_catalog(): void
    {
        app(SettingsService::class)->set('zarinpal', 'display_label', 'درگاه بانکی زرین');

        $catalog = app(PaymentGatewayCatalog::class);

        $this->assertSame('درگاه بانکی زرین', $catalog->definition('zarinpal')['label']);
    }

    /**
     * @return array{0: User}
     */
    protected function prepareCart(): array
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test-'.uniqid(),
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Product',
            'slug' => 'product-'.uniqid(),
            'price' => 100000,
            'stock' => 5,
            'is_active' => true,
        ]);
        ShippingMethod::query()->create([
            'name' => 'پست',
            'price' => 25000,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        app(CartService::class)->add($product->id, 1);

        return [$user];
    }
}
