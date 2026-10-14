<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

/**
 * Seeds the IOTEC mobile-money gateway record used by the original system.
 *
 * The original SQL dump had live client_id/client_secret values embedded in the
 * `payment_gateways` table. Storing API secrets in a database row (let alone a
 * seeder file that gets committed to source control) is unsafe — those credentials
 * were exposed in the files you uploaded and should be rotated with IOTEC regardless
 * of what happens here. This seeder only fills in the non-secret/structural fields;
 * secrets are pulled from .env (see services.iotec) and should be set there instead.
 */
class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        PaymentGateway::updateOrCreate(
            ['gateway_code' => 'iotec'],
            [
                'gateway_name' => 'IOTEC',
                'display_name' => 'Mobile Money',
                'provider_type' => 'mobile_money',
                'description' => 'IOTEC collection gateway',
                'is_active' => true,
                'is_default' => true,
                'sandbox_mode' => true,
                'token_url' => 'https://id.iotec.io/connect/token',
                'collect_url' => 'https://pay.iotec.io/api/collections/collect',
                'status_url' => 'https://pay.iotec.io/api/collections/status/',
                'client_id' => config('services.iotec.client_id'),
                'client_secret' => config('services.iotec.client_secret'),
                'callback_url' => url('/api/iotec/webhook'),
                'return_url' => url('/api/iotec/check-status'),
                'supports_collection' => true,
                'supports_disbursement' => false,
                'supports_mtn' => true,
                'supports_airtel' => true,
            ]
        );
    }
}
