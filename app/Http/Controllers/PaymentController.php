<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Notifications\PaymentSuccessNotification;
use App\Services\Payment\PaymentAuditLogger;
use App\Services\Payment\PaymentAuditStep;
use App\Services\Payment\PaymentService;
use App\Services\Payment\TaraGateway;
use App\Services\Settings\SettingsService;
use App\Services\Sms\OrderSmsNotifier;
use App\Support\PaymentReturnUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected PaymentAuditLogger $paymentAudit,
        protected OrderSmsNotifier $sms,
    ) {}

    public function callback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function taraCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function bajetCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function digipayCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function parsianCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function sadadCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function nopayCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function taraRedirect(string $tracking, TaraGateway $gateway, SettingsService $settings)
    {
        $model = Payment::query()
            ->where('tracking_code', $tracking)
            ->where('gateway', 'tara')
            ->firstOrFail();

        if ($model->status !== Payment::STATUS_PENDING || ! $model->transaction_id) {
            return redirect()
                ->to($this->paymentReturnUrl($model))
                ->with('error', 'این درخواست پرداخت تارا معتبر نیست.');
        }

        $config = $settings->tara();

        return response()
            ->view('payments.tara-redirect', [
                'action' => $gateway->purchaseAction($config),
                'username' => $config['username'],
                'token' => $model->transaction_id,
            ])
            ->header('Cache-Control', 'no-store');
    }

    protected function handleCallback(Request $request)
    {
        $payment = $this->resolvePayment($request);

        if ($payment->gateway === 'bajet') {
            $authority = (string) ($request->input('id') ?: $payment->transaction_id);
            $status = (string) $request->input('status', '');
        } elseif ($payment->gateway === 'digipay') {
            $authority = (string) ($request->input('trackingCode') ?: $payment->transaction_id);
            $status = (string) $request->input('result', '');
        } elseif ($payment->gateway === 'parsian') {
            $authority = (string) ($request->input('Token') ?: $request->input('token') ?: $payment->transaction_id);
            $status = (string) $request->input('status', '');
            $payment->update([
                'raw_response' => array_merge(
                    is_array($payment->raw_response) ? $payment->raw_response : [],
                    ['callback' => $request->except(['payment'])]
                ),
            ]);
            $payment = $payment->fresh();
        } elseif ($payment->gateway === 'sadad') {
            $authority = (string) ($request->input('token') ?: $request->input('Token') ?: $payment->transaction_id);
            $status = (string) $request->input('ResCode', '');
            $payment->update([
                'raw_response' => array_merge(
                    is_array($payment->raw_response) ? $payment->raw_response : [],
                    ['callback' => $request->except(['payment'])]
                ),
            ]);
            $payment = $payment->fresh();
        } elseif (in_array($payment->gateway, ['nopay', 'toopi'], true)) {
            $authority = (string) (
                $request->input('token')
                ?: $request->input('Token')
                ?: $payment->transaction_id
            );
            $status = (string) ($request->input('status') ?: $request->input('Status') ?: '');
            $payment->update([
                'raw_response' => array_merge(
                    is_array($payment->raw_response) ? $payment->raw_response : [],
                    ['callback' => $request->except(['payment'])]
                ),
            ]);
            $payment = $payment->fresh();
        } else {
            $authority = (string) ($request->input('Authority') ?: $request->input('token') ?: $payment->transaction_id);
            $status = (string) ($request->input('Status') ?: $request->input('result') ?: '');
        }

        $order = $payment->order()->first();

        $this->paymentAudit->step(
            $payment,
            PaymentAuditStep::CALLBACK_RECEIVED,
            'callback_received',
            'بازگشت کاربر از درگاه به سایت.',
            [
                'query' => $request->query(),
                'authority' => $authority,
                'status' => $status,
            ],
            $request->all()
        );

        try {
            $payment = $this->paymentService->verify($payment, $authority, $status);
        } catch (\Throwable $e) {
            $this->paymentAudit->failure(
                $payment,
                PaymentAuditStep::VERIFY_RESULT,
                'callback_verify_exception',
                'خطا هنگام پردازش بازگشت از درگاه.',
                $e
            );

            Log::error('Payment verify failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            if ($order) {
                try {
                    $this->sms->paymentFailed($order->fresh(['user']), $payment);
                } catch (\Throwable $smsError) {
                    Log::error('Payment callback payment-failed sms failed after verify exception', [
                        'payment_id' => $payment->id,
                        'error' => $smsError->getMessage(),
                    ]);
                }
            }

            $payment = $payment->fresh();

            return $this->redirectToPaymentResult($payment);
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            try {
                $payment->loadMissing('user', 'order');
                $payment->user?->notify(new PaymentSuccessNotification($payment));

                if ($order) {
                    $order = $order->fresh();
                    $this->sms->orderPaid($order, $payment);

                    if (! $order->isPaid()) {
                        $this->sms->paymentPartialRemaining($order, (int) $order->remainingAmount());
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Payment callback post-success notification failed', [
                    'payment_id' => $payment->id,
                    'tracking_code' => $payment->tracking_code,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($order && in_array($payment->status, [Payment::STATUS_FAILED, Payment::STATUS_CANCELED], true)) {
            try {
                $this->sms->paymentFailed($order->fresh(['user']), $payment);
            } catch (\Throwable $e) {
                Log::error('Payment callback payment-failed sms failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->redirectToPaymentResult($payment->fresh());
    }

    protected function redirectToPaymentResult(Payment $payment)
    {
        $flash = ['payment_status' => $payment->status];
        $order = $payment->order;

        if ($payment->status === Payment::STATUS_SUCCESS && $order && ! $order->isPaid()) {
            $flash['payment_remaining'] = $order->remainingAmount();
        }

        $resultRoute = 'payment.result';
        if ($payment->wasPaidByRepresentative() && $payment->order?->isRepresentativeOrder()) {
            $resultRoute = 'representative.payment.result';
        }

        return redirect()
            ->to(URL::temporarySignedRoute($resultRoute, now()->addHours(6), [
                'payment' => $payment->tracking_code,
            ]))
            ->with($flash);
    }

    protected function paymentReturnUrl(Payment $payment): string
    {
        return PaymentReturnUrl::for($payment);
    }

    protected function resolvePayment(Request $request): Payment
    {
        $tracking = (string) $request->input('payment', $request->query('payment', ''));

        if ($tracking !== '') {
            $byTracking = Payment::query()->where('tracking_code', $tracking)->first();
            if ($byTracking) {
                return $byTracking;
            }
        }

        $orderId = (string) ($request->input('OrderId') ?: $request->input('orderId', ''));
        if ($orderId !== '' && ctype_digit($orderId)) {
            $byPecOrderId = Payment::query()
                ->whereKey((int) $orderId)
                ->whereIn('gateway', ['parsian', 'sadad'])
                ->first();
            if ($byPecOrderId) {
                return $byPecOrderId;
            }
        }
        if ($orderId !== '') {
            $byOrderId = Payment::query()->where('tracking_code', $orderId)->first();
            if ($byOrderId) {
                return $byOrderId;
            }
        }

        $token = (string) ($request->input('Token') ?: $request->input('token', ''));
        if ($token !== '') {
            $byToken = Payment::query()->where('transaction_id', $token)->first();
            if ($byToken) {
                return $byToken;
            }
        }

        $additional = (string) $request->input('additionalData', '');
        if ($additional !== '') {
            $byAdditional = Payment::query()->where('tracking_code', $additional)->first();
            if ($byAdditional) {
                return $byAdditional;
            }
        }

        abort(404);
    }
}
