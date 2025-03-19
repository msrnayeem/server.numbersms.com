<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Checkout\Session;
use Illuminate\Log\Logger;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaymentController extends Controller
{
    // ✅ Generate Payment Link (Stripe / PayPal)
    public function createPayment(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'user_id' => 'required',
                'gateway' => 'required|string|in:stripe,paypal',
            ]);

            $amount = $request->amount;
            $currency = strtoupper('usd');
            $gateway = strtolower($request->gateway);
            $successUrl = route('pay_success');
            $failedUrl = route('pay_faild');
            $userId = $request->user_id;

            if ($gateway === 'stripe') {
                return $this->createStripePayment($amount, $currency, $successUrl, $failedUrl, $userId);
            } elseif ($gateway === 'paypal') {
                return $this->createPayPalPayment($amount, $currency, $successUrl, $failedUrl, $userId);
            }

            return response()->json(['status' => 'error', 'message' => 'Invalid payment gateway'], 400);
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // ✅ Stripe Payment Link
    private function createStripePayment($amount, $currency, $successUrl, $failedUrl, $currentUser)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => ['name' => 'Payment'],
                            'unit_amount' => $amount * 100, // Convert to cents
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => "{$successUrl}?session_id={CHECKOUT_SESSION_ID}&gateway=stripe&id={$currentUser}",
                'cancel_url' => $failedUrl,
            ]);

            return response()->json([
                'status' => true,
                'gateway' => 'stripe',
                'payment_link' => $session->url,
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // ✅ PayPal Payment Link
    private function createPayPalPayment($amount, $currency, $successUrl, $failedUrl, $currentUser)
    {
        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $order = $provider->createOrder([
                "intent" => "CAPTURE",
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => $currency,
                            "value" => $amount,
                        ]
                    ]
                ],
                "application_context" => [
                    "return_url" => "{$successUrl}?&gateway=paypal&id={$currentUser}",
                    "cancel_url" => $failedUrl,
                ]
            ]);

            return response()->json([
                'status' => true,
                'gateway' => 'paypal',
                'payment_link' => $order['links'][1]['href'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    // ✅ Payment Success
    public function success(Request $request)
    {
        $gateway = $request->query('gateway');
        $userId = $request->query('id');

        if ($gateway === 'stripe') {
            $sessionId = $request->query('session_id');
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $session = StripeSession::retrieve($sessionId);

            // Save payment to database
            $this->savePayment($session->id, $session->amount_total / 100, 'stripe', ($session->payment_status == 'paid' ? true : false), $session->customer_email, $userId);

            // return view
            return view('success', [
                'data' => [
                    'payment_id' => $session->id,
                    'amount' => $session->amount_total / 100,
                    'geteway' => 'stripe',
                    'total_amount' => User::find($userId)->coin
                ]
            ]);

        } elseif ($gateway === 'paypal') {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $paymentId = $request->query('token');
            $order = $provider->capturePaymentOrder($paymentId);

            // Save payment to database
            $this->savePayment($paymentId, $order['purchase_units'][0]['payments']['captures'][0]['amount']['value'], 'paypal', true, $order['payer']['email_address'], $userId);

            // return view
            return view('success', [
                'data' => [
                    'payment_id' => $paymentId,
                    'amount' => $order['purchase_units'][0]['payments']['captures'][0]['amount']['value'],
                    'geteway' => 'paypal',
                    'total_amount' => User::find($userId)->coin
                ]
            ]);
        }
    }

    // ❌ Payment Failed
    public function faild(Request $request)
    {
        return view('faild');
    }

    // 📌 Save Payment to Database
    private function savePayment($paymentId, $amount, $gateway, $status, $email, $user)
    {
        if ($paymentId) {
            $currentUser = User::find($user);
            $transectionData = Transaction::where('pay_id', $paymentId)->exists();
            if (!$transectionData) {
                $currentUser->coin = $currentUser->coin + $amount;
                $currentUser->save();

                // save history
                Transaction::create([
                    'user_id' => $currentUser->id,
                    'username' => $currentUser->name,
                    'getway' => $gateway,
                    'amount' => $amount,
                    'pay_id' => $paymentId,
                    'status' => $status
                ]);
            }
        }
    }
    
}
