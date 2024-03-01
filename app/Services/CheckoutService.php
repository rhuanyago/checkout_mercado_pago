<?php

namespace App\Services;

use MercadoPago\SDK;
use App\Models\Order;
use MercadoPago\Payer;
use MercadoPago\Payment;
use Illuminate\Support\Str;
use App\Enums\OrderStatusEnum;
use Database\Seeders\OrderSeeder;
use MercadoPago\MercadoPagoConfig;
use App\Exceptions\PaymentException;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;

class CheckoutService
{

    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('payment.mercadopago.access_token'));
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
    }

    public function loadCart(): array
    {
        $cart = Order::with('skus.product', 'skus.features')
            ->where('status', OrderStatusEnum::CART)
            ->where(function ($query) {
                $query->where('session_id', session()->getId());
                if (auth()->check()) {
                    $query->orWhere('user_id', auth()->user()->id);
                }
            })->first();

        if (!$cart && config('app.env') == 'local' || config('app.env') == 'testing') {
            $seed = new OrderSeeder();
            $seed->run(session()->getId());
            return $this->loadCart();
        }

        return $cart->toArray();
    }

    public function creditCardPayment($data, $user, $address)
    {
        $client = new PaymentClient();

        $request_options = new RequestOptions();
        $request_options->setCustomHeaders(["X-Idempotency-Key: " . Str::uuid()]);

        $payment = $client->create($data, $request_options);

        throw_if(
            !$payment->id || $payment->status === 'rejected',
            PaymentException::class,
            $payment?->status_detail ?? "Verifique os dados do cartão"
        );

        return $payment;
    }

    public function pixOrBankSlipPayment($data, $user, $address)
    {
        $createRequest = [
            "transaction_amount" => (int)$data['amount'],
            "description" => "Pagamento de produto",
            "payment_method_id" => $data['method'],
            "payer" => $this->buildPayer($user, $address)
        ];

        $client = new PaymentClient();

        $request_options = new RequestOptions();
        $request_options->setCustomHeaders(["X-Idempotency-Key: " . Str::uuid()]);

        if ($data['method'] === 'bolbradesco') {
            $createRequest['payer']['identification'] = [
                "type" => 'CPF',
                "number" => $user['cpf']
            ];
        }

        $payment = $client->create($createRequest, $request_options);

        throw_if(
            !$payment->id || $payment->status === 'rejected',
            PaymentException::class,
            $payment?->status_detail ?? "Verifique seu CPF"
        );

        return $payment;
    }

    public function buildPayer($user, $address)
    {
        $first_name = explode(' ', $user['name'])[0];
        return [
            "email" => $user['email'],
            "first_name" => 'Rhuan',
            "last_name" => 'Yago',
            "address" =>  [
                "zip_code" => $address['zipcode'],
                "street_name" => $address['address'],
                "street_number" => $address['number'],
                "neighborhood" => $address['district'],
                "city" => $address['city'],
                "federal_unit" => $address['state']
            ]
        ];
    }
}