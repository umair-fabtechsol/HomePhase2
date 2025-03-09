<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Transfer;

class StripePaymentController extends Controller
{
    public function charge(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $charge = Charge::create([
                'amount' => $request->amount * 100, // Convert to cents
                'currency' => 'usd',
                'source' => $request->stripeToken, // Token from frontend
                'description' => 'Payment Charge'
            ]);

            return response()->json(['success' => true, 'charge' => $charge]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function transfer(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $transfer = Transfer::create([
                'amount' => $request->amount * 100, // Convert to cents
                'currency' => 'usd',
                'destination' => $request->account_id, // Stripe connected account ID
                'description' => 'Payment Transfer'
            ]);

            return response()->json(['success' => true, 'transfer' => $transfer]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
