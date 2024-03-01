<?php

namespace App\Services;

use MercadoPago\SDK;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Mail\PaymentApprovedMail;
use Carbon\Carbon;
use MercadoPago\MercadoPagoConfig;
use Illuminate\Support\Facades\Mail;
use MercadoPago\Client\Payment\PaymentClient;

class PaymentService
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('payment.mercadopago.access_token'));
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
    }

    public function update($external_id): void
    {
        $mp_payment = (new PaymentClient())->get($external_id);
        $payment = Payment::with('order.user')->where('external_id', $external_id)->firstOrFail();

        // $mp_payment->status = 'approved';

        $payment->status = PaymentStatusEnum::parse($mp_payment->status);
        $payment->save();

        if ($payment->status === PaymentStatusEnum::PAID) {
            $payment->approved_at = $mp_payment->date_approved ?? Carbon::now()->format('Y-m-d H:i:s');
            $payment->order->status = OrderStatusEnum::PAID;
            $payment->order->save();

            Mail::to($payment->order->user->email)->queue(new PaymentApprovedMail($payment->order));
        }

        if ($payment->status === PaymentStatusEnum::CANCELLED || $payment->status === PaymentStatusEnum::REJECTED) {
            $payment->order->status = OrderStatusEnum::parse($mp_payment->status);
            $payment->order->save();
        }
    }
}