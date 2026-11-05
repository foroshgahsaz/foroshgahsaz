<?php

namespace App\Services\Payment;

use RuntimeException;

class ToopiCpgPasswordEncryptor
{
    /**
     * رمز سرویس طبق مستند CPG (رمزنگاری با publicKey) — در صورت شکست، از فیلد «رمز رمزنگاری‌شده» در ادمین استفاده کنید.
     */
    public function encryptServicePassword(string $plainPassword, string $publicKeyMaterial): string
    {
        $plainPassword = trim($plainPassword);
        if ($plainPassword === '') {
            throw new RuntimeException('رمز سرویس خالی است.');
        }

        $keyMaterial = trim($publicKeyMaterial);
        if ($keyMaterial === '') {
            throw new RuntimeException('کلید عمومی توپی تنظیم نشده است.');
        }

        $publicKey = $this->toPublicKeyPem($keyMaterial);

        $encrypted = '';
        $ok = openssl_public_encrypt($plainPassword, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);

        if (! $ok || $encrypted === '') {
            throw new RuntimeException(
                'رمزنگاری رمز سرویس ناموفق بود. مقدار رمزنگاری‌شده را از ابزار CPG در فیلد «رمز رمزنگاری‌شده» قرار دهید.'
            );
        }

        return base64_encode($encrypted);
    }

    public function resolveEncryptedPassword(array $config): string
    {
        $preEncrypted = trim((string) ($config['service_password_encrypted'] ?? ''));
        if ($preEncrypted !== '') {
            return $preEncrypted;
        }

        $plain = (string) ($config['service_password'] ?? '');
        $publicKey = (string) ($config['public_key'] ?? '');

        return $this->encryptServicePassword($plain, $publicKey);
    }

    protected function toPublicKeyPem(string $material): string
    {
        if (str_contains($material, 'BEGIN PUBLIC KEY')) {
            return $material;
        }

        $body = preg_replace('/\s+/', '', $material) ?? $material;

        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split($body, 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }
}
