<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify the original, unmodified request body.
        $rawBody = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $secret = config('services.razorpay.webhook_secret');

        if (!$signature || !$secret) {
            return response('Invalid webhook configuration', 400);
        }

        $expectedSignature = hash_hmac('sha256', $rawBody, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Razorpay webhook signature mismatch');
            return response('Invalid signature', 400);
        }

        $event = json_decode($rawBody, true);

        if (!is_array($event)) {
            return response('Invalid payload', 400);
        }

        $eventName = $event['event'] ?? null;
        $entity = $event['payload']['payment']['entity'] ?? null;

        if (!is_array($entity)) {
            return response('Invalid payment payload', 400);
        }

        if (!in_array($eventName, ['payment.captured', 'payment.failed'], true)) {
            return response('Event ignored', 200);
        }

        $orderId = $entity['order_id'] ?? null;
        $paymentId = $entity['id'] ?? null;

        if (!$orderId || !$paymentId) {
            return response('Missing payment details', 400);
        }

        DB::transaction(function () use (
            $eventName,
            $entity,
            $orderId,
            $paymentId
        ) {
            $payment = Payment::where('gateway_order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                Log::warning('Webhook received for unknown Razorpay order', [
                    'order_id' => $orderId,
                ]);
                return;
            }

            // Never allow a later failure event to downgrade a success.
            if ($payment->payment_status === 'success') {
                return;
            }

            if (
                (int) ($entity['amount'] ?? 0) !==
                    (int) round($payment->amount * 100) ||
                ($entity['currency'] ?? '') !== 'INR'
            ) {
                Log::warning('Razorpay webhook amount/currency mismatch', [
                    'order_id' => $orderId,
                ]);
                return;
            }

            if ($eventName === 'payment.captured') {
                $payment->update([
                    'gateway_payment_id' => $paymentId,
                    'payment_status' => 'success',
                    'payment_method' => $entity['method'] ?? null,
                    'paid_at' => now(),
                ]);
            }

            if ($eventName === 'payment.failed') {
                $payment->update([
                    'gateway_payment_id' => $paymentId,
                    'payment_status' => 'failed',
                    'payment_method' => $entity['method'] ?? null,
                ]);
            }
        });

        return response('Webhook processed', 200);
    }
}