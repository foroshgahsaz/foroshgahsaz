<?php

use App\Services\Payment\BajetPayGateway;
use App\Services\Payment\DigiPayGateway;
use App\Services\Payment\ParsianGateway;
use App\Services\Payment\SadadGateway;
use App\Services\Payment\TaraGateway;
use App\Services\Payment\ToopiGateway;
use App\Services\Payment\ZarinpalGateway;

return [

    'default' => env('PAYMENT_GATEWAY', 'zarinpal'),

    'gateways' => [

        'parsian' => [
            'driver' => ParsianGateway::class,
            'type' => 'cash',
            'label' => 'پارسیان',
            'description' => 'پرداخت نقدی آنلاین (تجارت الکترونیک پارسیان)',
            'login_account' => env('PARSIAN_LOGIN_ACCOUNT', ''),
            'callback_url' => env('PARSIAN_CALLBACK_URL', '/payment/callback/pec'),
            'amount_unit' => env('PARSIAN_AMOUNT_UNIT', 'toman'),
        ],

        'sadad' => [
            'driver' => SadadGateway::class,
            'type' => 'cash',
            'label' => 'سداد',
            'description' => 'پرداخت نقدی آنلاین (درگاه سداد — بانک ملی)',
            'merchant_id' => env('SADAD_MERCHANT_ID', ''),
            'terminal_id' => env('SADAD_TERMINAL_ID', ''),
            'terminal_key' => env('SADAD_TERMINAL_KEY', ''),
            'callback_url' => env('SADAD_CALLBACK_URL', '/payment/callback/sadad'),
            'amount_unit' => env('SADAD_AMOUNT_UNIT', 'toman'),
        ],

        'zarinpal' => [
            'driver' => ZarinpalGateway::class,
            'type' => 'cash',
            'label' => 'زرین‌پال',
            'description' => 'پرداخت نقدی آنلاین',
            'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
            'sandbox' => env('ZARINPAL_SANDBOX', true),
            'callback_url' => env('ZARINPAL_CALLBACK_URL', '/payment/callback'),
            'amount_unit' => env('ZARINPAL_AMOUNT_UNIT', 'toman'),
        ],

        'bajet' => [
            'driver' => BajetPayGateway::class,
            'type' => 'credit',
            'label' => 'باجت‌پی',
            'description' => 'پرداخت اعتباری جت‌پی (باجت)',
            'username' => env('BAJET_USERNAME', ''),
            'password' => env('BAJET_PASSWORD', ''),
            'terminal_id' => env('BAJET_TERMINAL_ID', ''),
            'sandbox' => env('BAJET_SANDBOX', true),
            'base_url' => env('BAJET_BASE_URL', ''),
            'sandbox_base_url' => env('BAJET_SANDBOX_BASE_URL', ''),
            'portal_sandbox_base_url' => env('BAJET_PORTAL_SANDBOX_BASE_URL', ''),
            'portal_base_url' => env('BAJET_PORTAL_BASE_URL', ''),
            'callback_url' => env('BAJET_CALLBACK_URL', '/payment/callback/bajet'),
            'return_url_base' => env('BAJET_RETURN_URL_BASE', ''),
            'amount_unit' => env('BAJET_AMOUNT_UNIT', 'toman'),
            'default_product_type' => env('BAJET_DEFAULT_PRODUCT_TYPE', 2),
            'default_brand' => env('BAJET_DEFAULT_BRAND', 'general'),
        ],

        'digipay' => [
            'driver' => DigiPayGateway::class,
            'type' => 'credit',
            'label' => 'دیجی‌پی',
            'description' => 'پرداخت اعتباری در ۴ قسط (دیجی‌پی)',
            'username' => env('DIGIPAY_USERNAME', ''),
            'password' => env('DIGIPAY_PASSWORD', ''),
            'client_id' => env('DIGIPAY_CLIENT_ID', ''),
            'client_secret' => env('DIGIPAY_CLIENT_SECRET', ''),
            'sandbox' => env('DIGIPAY_SANDBOX', true),
            'base_url' => env('DIGIPAY_BASE_URL', 'https://api.mydigipay.com/digipay/api'),
            'sandbox_base_url' => env('DIGIPAY_SANDBOX_BASE_URL', 'https://uat.mydigipay.info/digipay/api'),
            'callback_url' => env('DIGIPAY_CALLBACK_URL', '/payment/callback/digipay'),
            'amount_unit' => env('DIGIPAY_AMOUNT_UNIT', 'toman'),
            'ticket_type' => env('DIGIPAY_TICKET_TYPE', 11),
            'default_product_type' => env('DIGIPAY_DEFAULT_PRODUCT_TYPE', 1),
        ],

        'toopi' => [
            'driver' => ToopiGateway::class,
            'type' => 'credit',
            'label' => 'توپی',
            'description' => 'پرداخت اعتباری CPG (توپی — پیشگامان پویا)',
            'api_base_url' => env('TOOPI_API_BASE_URL', ''),
            'service_username' => env('TOOPI_SERVICE_USERNAME', ''),
            'service_password' => env('TOOPI_SERVICE_PASSWORD', ''),
            'service_password_encrypted' => env('TOOPI_SERVICE_PASSWORD_ENCRYPTED', ''),
            'merchant_number' => env('TOOPI_MERCHANT_NUMBER', ''),
            'public_key' => env('TOOPI_PUBLIC_KEY', 'WtX3qPVc1lrHX/+ug9iILQ=='),
            'callback_url' => env('TOOPI_CALLBACK_URL', '/payment/callback/toopi'),
            'amount_unit' => env('TOOPI_AMOUNT_UNIT', 'toman'),
            'test_mode' => env('TOOPI_TEST_MODE', false),
            'test_cell_number' => env('TOOPI_TEST_CELL_NUMBER', ''),
        ],

        'tara' => [
            'driver' => TaraGateway::class,
            'type' => 'credit',
            'label' => 'تارا',
            'description' => 'پرداخت اعتباری از کیف پول تارا',
            'username' => env('TARA_USERNAME', 'tara_ipg'),
            'password' => env('TARA_PASSWORD', 'tara_ipg@123_$'),
            'service_id' => env('TARA_SERVICE_ID', '101'),
            'sandbox' => env('TARA_SANDBOX', true),
            'base_url' => env('TARA_BASE_URL', 'https://pay.tara360.ir/pay'),
            'sandbox_base_url' => env('TARA_SANDBOX_BASE_URL', 'https://stage-pay.tara360.ir/pay'),
            'callback_url' => env('TARA_CALLBACK_URL', '/payment/callback/tara'),
            'amount_unit' => env('TARA_AMOUNT_UNIT', 'toman'),
            'client_ip' => env('TARA_CLIENT_IP'),
            'default_group' => env('TARA_DEFAULT_GROUP', '15'),
            'default_group_title' => env('TARA_DEFAULT_GROUP_TITLE', 'خانه و آشپزخانه'),
            'refund_principal' => env('TARA_REFUND_PRINCIPAL', 'ipg_refund'),
            'refund_password' => env('TARA_REFUND_PASSWORD', '123456'),
            'refund_base_url' => env('TARA_REFUND_BASE_URL', 'https://club.tara-club.ir/club'),
            'sandbox_refund_base_url' => env('TARA_SANDBOX_REFUND_BASE_URL', 'https://stage.tara-club.ir/club'),
        ],

    ],

    'currency' => 'IRT',

];
