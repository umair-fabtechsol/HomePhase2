<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Payout;
use Stripe\Balance;
class PaymentController extends Controller
{
    public function createPayout(Request $request)
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $payout = Payout::create([
                'amount' => $request->amount * 100,
                'currency' => 'usd',
                'method' => 'standard',
            ]);
            return response()->json(['success' => true, 'payout' => $payout]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function checkBalance()
{
    try {
        Stripe::setApiKey(config('services.stripe.secret'));

        $balance = Balance::retrieve();
        return response()->json(['balance' => $balance]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}