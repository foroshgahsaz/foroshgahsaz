<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use RuntimeException;

class ToopiGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected PaymentActivityLogger $paymentLog,
        protected PaymentAuditLogger $audit,
        protected OrderActivityLogger $orderLog,
        protected SettingsService $settings,
        protected ToopiCpgClient $client,
        protected ToopiCpgPasswordEncryptor $passwordEncryptor,
    ) {}

    public function initiate(Payment $payment, Order $order): string
    {
        $config = $this->settings->toopi();

        if (! ($config['enabled'] ?? false)) {
            throw new RuntimeException('درگاه توپی فعال نیست.');
        }

        $this->assertConfig($config);

        $order->loadMissing('user');

        $cellNumber = $this->resolveCellNumber($config, $order);
        if ($cellNumber === '') {
            throw new RuntimeException('شماره موبایل مشتری برای پرداخت توپی الزامی است.');
        }

        $amountUnit = (string) ($config['amount_unit'] ?? AmountConverter::UNIT_TOMAN);
        $amount = AmountConverter::toGateway($payment->amount, $amountUnit);

        $encryptedPassword = $this->passwordEncryptor->resolveEncryptedPassword($config);

        $input = [
            'serviceUserName' => (string) $config['service_username'],
            'servicePassword' => $encryptedPassword,
            'cellNumber' => $cellNumber,
            'amount' => $amount,
            'returnURL' => ToopiReturnUrl::absolute($config, $payment),
            'merchantNumber' => (string) $config['merchant_number'],
        ];

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'toopi_request_token',
            'درخواست Token به CPG توپی ارسال شد.',
            [
                'amount' => $amount,
                'cell_number' => $cellNumber,
            ]
        );

        try {
            $response = $this->client->call(
                (string) $config['api_base_url'],
                ToopiCpgClient::SERVICE_REQUEST_TOKEN,
                $input,
            );
        } catch (RuntimeException $e) {
            $this->markInitiateFailed($payment, $order, ['exception' => $e->getMessage()]);

            throw $e;
        }

        if ($this->client->hasErrors($response)) {
            $message = $this->client->firstErrorMessage($response);
            $this->markInitiateFailed($payment, $order, $response);

            throw new RuntimeException($message);
        }

        $entity = $this->client->entity($response);
        $token = trim((string) ($entity['token'] ?? ''));
        $redirectUrl = trim((string) ($entity['redirectURL'] ?? $entity['redirectUrl'] ?? ''));

        if ($token === '' || $redirectUrl === '') {
            $this->markInitiateFailed($payment, $order, $response);

            throw new RuntimeException('پاسخ توکن/redirect از درگاه توپی ناقص است.');
        }

        $payment->update([
            'transaction_id' => $token,
            'raw_response' => ['request_token' => $response],
        ]);

        $this->audit->step(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'toopi_token_received',
            'Token از درگاه توپی دریافت شد.',
            ['token' => $token],
            $response
        );

        $this->paymentLog->gatewayResponse(
            $payment->fresh(),
            'کاربر به درگاه توپی هدایت شد.',
            ['redirect_url' => $redirectUrl]
        );

        return $redirectUrl;
    }

    public function verify(Payment $payment, string $authority, string $status): GatewayVerificationResult
    {
        $config = $this->settings->toopi();

        $token = trim($authority !== '' ? $authority : (string) $payment->transaction_id);
        if ($token === '') {
            return GatewayVerificationResult::failed([], 'توکن پرداخت توپی یافت نشد.');
        }

        if ($payment->transaction_id && $token !== $payment->transaction_id) {
            return GatewayVerificationResult::failed(
                ['token' => $token, 'expected' => $payment->transaction_id],
                'توکن بازگشتی با تراکنش مطابقت ندارد.'
            );
        }

        try {
            $encryptedPassword = $this->passwordEncryptor->resolveEncryptedPassword($config);
        } catch (RuntimeException $e) {
            return GatewayVerificationResult::failed([], $e->getMessage());
        }

        $input = [
            'serviceUserName' => (string) ($config['service_username'] ?? ''),
            'servicePassword' => $encryptedPassword,
            'token' => $token,
        ];

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'toopi_verify_request',
            'درخواست VerifyTransaction به توپی ارسال شد.',
            ['token' => $token]
        );

        try {
            $response = $this->client->call(
                (string) ($config['api_base_url'] ?? ''),
                ToopiCpgClient::SERVICE_VERIFY,
                $input,
            );
        } catch (RuntimeException $e) {
            return GatewayVerificationResult::failed(
                ['exception' => $e->getMessage()],
                'خطا در تأیید تراکنش توپی.'
            );
        }

        if ($this->client->hasErrors($response)) {
            return GatewayVerificationResult::failed(
                $response,
                $this->client->firstErrorMessage($response)
            );
        }

        $entity = $this->client->entity($response);
        if ($entity === null) {
            return GatewayVerificationResult::failed($response, 'پاسخ VerifyTransaction نامعتبر است.');
        }

        $approved = filter_var($entity['isApproved'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (! $approved) {
            return GatewayVerificationResult::canceled(
                $response,
                'تراکنش توپی تأیید نشد.'
            );
        }

        $amountUnit = (string) ($config['amount_unit'] ?? AmountConverter::UNIT_TOMAN);
        $paidInShop = AmountConverter::fromGateway($entity['amount'] ?? 0, $amountUnit);
        $refNumber = (string) ($entity['refNumber'] ?? '');

        return GatewayVerificationResult::success(
            $refNumber !== '' ? $refNumber : null,
            [
                'verify' => $response,
                'callback_status' => $status,
            ],
            $paidInShop,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function assertConfig(array $config): void
    {
        foreach (['api_base_url', 'service_username', 'merchant_number'] as $key) {
            if (trim((string) ($config[$key] ?? '')) === '') {
                throw new RuntimeException('تنظیمات درگاه توپی ناقص است.');
            }
        }

        $hasEncrypted = trim((string) ($config['service_password_encrypted'] ?? '')) !== '';
        $hasPlain = trim((string) ($config['service_password'] ?? '')) !== '';

        if (! $hasEncrypted && ! $hasPlain) {
            throw new RuntimeException('رمز سرویس توپی تنظیم نشده است.');
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function resolveCellNumber(array $config, Order $order): string
    {
        if ((bool) ($config['test_mode'] ?? false)) {
            $testCell = trim((string) ($config['test_cell_number'] ?? ''));
            if ($testCell !== '') {
                return $this->normalizeMobile($testCell);
            }
        }

        return $this->normalizeMobile((string) ($order->user?->phone ?? ''));
    }

    protected function normalizeMobile(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return strlen($digits) === 11 && str_starts_with($digits, '09') ? $digits : '';
    }

    protected function markInitiateFailed(Payment $payment, Order $order, mixed $raw): void
    {
        $previous = $payment->status;
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'raw_response' => is_array($raw) ? $raw : ['error' => $raw],
        ]);

        $this->audit->failure(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'toopi_request_failed',
            'درخواست Token توپی ناموفق بود.',
            null,
            is_array($raw) ? $raw : ['error' => (string) $raw],
            is_array($raw) ? $raw : ['error' => (string) $raw]
        );

        $this->paymentLog->statusChanged($payment->fresh(), $previous, Payment::STATUS_FAILED, 'خطا در اتصال به درگاه');
        $this->orderLog->paymentLinked($order, $payment->tracking_code, Payment::STATUS_FAILED);
    }
}
