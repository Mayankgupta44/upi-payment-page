<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Throwable;

class PaymentController extends Controller
{
    public function create()
    {
        return view('payments.create');
    }

    public function store(Request $request)
    {
        
        $validated = $request->validate([
            'full_name' => [
                'bail',
                'required',
                'string',
                'min:3',
                'max:100',
            ],

            'mobile_number' => [
                'bail',
                'required',
                'digits:10',
                'regex:/^[6-9][0-9]{9}$/',
            ],

            'email' => [
                'bail',
                'required',
                'email',
                'max:150',
            ],

            'father_name' => [
                'bail',
                'required',
                'string',
                'min:3',
                'max:100',
            ],

            'amount' => [
                'bail',
                'required',
                'numeric',
                'min:1',
                'max:100000',
                'decimal:0,2',
            ],
        ], [
            'full_name.required' => 'Please enter your full name.',
            'full_name.min' => 'Full name must contain at least 3 characters.',
            'full_name.max' => 'Full name cannot exceed 100 characters.',

            'mobile_number.required' => 'Please enter your mobile number.',
            'mobile_number.digits' => 'Mobile number must contain exactly 10 digits.',
            'mobile_number.regex' => 'Please enter a valid Indian mobile number starting with 6, 7, 8 or 9.',

            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email cannot exceed 150 characters.',

            'father_name.required' => "Please enter your father's name.",
            'father_name.min' => "Father's name must contain at least 3 characters.",
            'father_name.max' => "Father's name cannot exceed 100 characters.",

            'amount.required' => 'Please enter the payment amount.',
            'amount.numeric' => 'Payment amount must be a valid number.',
            'amount.min' => 'Minimum payment amount is ₹1.',
            'amount.max' => 'Maximum payment amount is ₹1,00,000.',
            'amount.decimal' => 'Payment amount can have a maximum of 2 decimal places.',
        ]);

        try {
            $payment = DB::transaction(function () use ($validated) {
                $customer = Customer::create([
                    'full_name' => $validated['full_name'],
                    'mobile_number' => $validated['mobile_number'],
                    'email' => $validated['email'],
                    'father_name' => $validated['father_name'],
                ]);

                return Payment::create([
                    'customer_id' => $customer->id,
                    'amount' => $validated['amount'],
                    'currency' => 'INR',
                    'payment_status' => 'pending',
                ]);
            });

            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            $order = $api->order->create([
                'receipt' => 'payment_' . $payment->id,
                'amount' => (int) round($payment->amount * 100),
                'currency' => 'INR',
            ]);

            $payment->update([
                'gateway_order_id' => $order['id'],
            ]);

            return view('payments.create', [
                'razorpayOrder' => [
                    'key' => config('services.razorpay.key'),
                    'order_id' => $order['id'],
                    'amount' => (int) round($payment->amount * 100),
                    'customer_name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'mobile' => $validated['mobile_number'],
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Razorpay order creation failed: ' . $e->getMessage());

            return redirect()
                ->route('payment.create')
                ->withInput()
                ->withErrors([
                    'payment' => 'Could not start payment. Please check your Razorpay configuration and try again.',
                ]);
        }
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $payment = Payment::where(
            'gateway_order_id',
            $data['razorpay_order_id']
        )->firstOrFail();

        try {
            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            // Verify against the order ID stored by our server.
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $payment->gateway_order_id,
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature' => $data['razorpay_signature'],
            ]);

            // Confirm the payment's actual status with Razorpay.
            $gatewayPayment = $api->payment->fetch(
                $data['razorpay_payment_id']
            );

            if (
                $gatewayPayment['order_id'] !== $payment->gateway_order_id ||
                (int) $gatewayPayment['amount'] !== (int) round($payment->amount * 100) ||
                $gatewayPayment['currency'] !== 'INR' ||
                $gatewayPayment['status'] !== 'captured'
            ) {
                return response()->json([
                    'message' => 'Payment is not yet confirmed as captured.',
                ], 422);
            }

            $payment->update([
                'gateway_payment_id' => $data['razorpay_payment_id'],
                'payment_status' => 'success',
                'payment_method' => $gatewayPayment['method'] ?? null,
                'paid_at' => now(),
            ]);

            return response()->json([
                'message' => 'Payment verified successfully.',
                'redirect' => route('payment.create'),
            ]);

        } catch (Throwable $e) {
            Log::warning('Payment verification failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Payment verification failed. Please contact the administrator if money was deducted.',
            ], 422);
        }
    }
}