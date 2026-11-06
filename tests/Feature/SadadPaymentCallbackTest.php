<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SadadPaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_sadad_failed_callback_marks_payment_canceled(): void
    {
        [$user, $order] = $this->makeUserAndOrder();
        $token = 'sadad-token-fail';

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 50_000,
            'gateway' => 'sadad',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => 'SADADFAIL1',
            'transaction_id' => $token,
        ]);

        $response = $this->post('/payment/callback/sadad?'.http_build_query(['payment' => 'SADADFAIL1']), [
            'token' => $token,
            'ResCode' => '1',
            'OrderId' => (string) $payment->id,
        ]);

        $response->assertRedirect();
        $this->assertSame(Payment::STATUS_CANCELED, $payment->fresh()->status);
    }

    public function test_sadad_callback_resolves_payment_by_order_id_without_query(): void
    {
        [$user, $order] = $this->makeUserAndOrder();
        $token = 'sadad-token-order';

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 50_000,
            'gateway' => 'sadad',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => 'SADADORD2',
            'transaction_id' => $token,
        ]);

        $response = $this->post('/payment/callback/sadad', [
            'token' => $token,
            'ResCode' => '17',
            'OrderId' => (string) $payment->id,
        ]);

        $response->assertRedirect();
        $this->assertSame(Payment::STATUS_CANCELED, $payment->fresh()->status);
    }

    /** پنل بانک ملی گاهی فقط مسیر `/payment/callback/bmi` را می‌پذیرد؛ باید همان کنترلر سداد اجرا شود. */
    public function test_sadad_failed_callback_works_on_bmi_alias_path(): void
    {
        [$user, $order] = $this->makeUserAndOrder();
        $token = 'sadad-token-bmi-fail';

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 50_000,
            'gateway' => 'sadad',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => 'SADADBMI1',
            'transaction_id' => $token,
        ]);

        $response = $this->post('/payment/callback/bmi?'.http_build_query(['payment' => 'SADADBMI1']), [
            'token' => $token,
            'ResCode' => '1',
            'OrderId' => (string) $payment->id,
        ]);

        $response->assertRedirect();
        $this->assertSame(Payment::STATUS_CANCELED, $payment->fresh()->status);
    }

    /**
     * @return array{0: User, 1: Order}
     */
    protected function makeUserAndOrder(): array
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => 50_000,
            'final_amount' => 50_000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'sadad',
            'status' => Order::STATUS_PENDING,
            'stock_reserved' => true,
        ]);

        return [$user, $order];
    }
}
