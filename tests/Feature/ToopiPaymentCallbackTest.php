<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ToopiPaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        Setting::query()->updateOrCreate(['group' => 'toopi', 'key' => 'enabled'], ['value' => '1']);
        Setting::query()->updateOrCreate(['group' => 'toopi', 'key' => 'api_base_url'], ['value' => 'https://toopi.test']);
        Setting::query()->updateOrCreate(['group' => 'toopi', 'key' => 'service_username'], ['value' => 'user']);
        Setting::query()->updateOrCreate(['group' => 'toopi', 'key' => 'service_password_encrypted'], ['value' => 'enc-pass']);
        Setting::query()->updateOrCreate(['group' => 'toopi', 'key' => 'merchant_number'], ['value' => '111']);
    }

    public function test_toopi_callback_verify_failure_marks_payment_failed(): void
    {
        Http::fake([
            'https://toopi.test/*' => Http::response([
                'result' => null,
                'notification' => [
                    'hasErrors' => true,
                    'errors' => [
                        ['message' => 'توکن نامعتبر', 'code' => -3],
                    ],
                ],
            ]),
        ]);

        [$user, $order] = $this->makeUserAndOrder();
        $token = 'toopi-token-fail';

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 50_000,
            'gateway' => 'toopi',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => 'TOOPIFAIL1',
            'transaction_id' => $token,
        ]);

        $response = $this->get('/payment/callback/toopi?'.http_build_query(['payment' => 'TOOPIFAIL1']));

        $response->assertRedirect();
        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
    }

    /**
     * @return array{0: User, 1: Order}
     */
    protected function makeUserAndOrder(): array
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => 50_000,
            'final_amount' => 50_000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'toopi',
            'status' => Order::STATUS_PENDING,
            'stock_reserved' => true,
        ]);

        return [$user, $order];
    }
}
