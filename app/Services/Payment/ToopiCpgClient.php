<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ToopiCpgClient
{
    public const SERVICE_REQUEST_TOKEN = 'CPG/Security/Token/RequestToken';

    public const SERVICE_VERIFY = 'CPG/Financial/VerifyTransaction';

    /**
     * @param  array<string, mixed>  $inputValue
     * @return array<string, mixed>
     */
    public function call(string $baseUrl, string $serviceName, array $inputValue): array
    {
        $url = rtrim($baseUrl, '/').'/';

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(45)
            ->post($url, [
                'InputValue' => $inputValue,
                'ServiceName' => $serviceName,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('خطا در اتصال به درگاه توپی (HTTP '.$response->status().').');
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new RuntimeException('پاسخ نامعتبر از درگاه توپی.');
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function hasErrors(array $response): bool
    {
        if ((bool) data_get($response, 'notification.hasErrors', false)) {
            return true;
        }

        return (bool) data_get($response, 'result.notification.hasErrors', false);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function firstErrorMessage(array $response): string
    {
        $errors = data_get($response, 'notification.errors', []);
        if (! is_array($errors) || $errors === []) {
            $errors = data_get($response, 'result.notification.errors', []);
        }

        if (is_array($errors) && isset($errors[0]['message'])) {
            return (string) $errors[0]['message'];
        }

        return 'خطا در پاسخ درگاه توپی.';
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>|null
     */
    public function entity(array $response): ?array
    {
        $entity = data_get($response, 'result.entity');

        return is_array($entity) ? $entity : null;
    }
}
