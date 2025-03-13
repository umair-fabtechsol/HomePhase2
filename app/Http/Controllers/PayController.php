<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\Charge;
use Stripe\Transfer;
use Stripe\Webhook;

use App\Models\User;
use Exception;

class PayController extends Controller
{
    // public function createStripeAccount(Request $request)
    // {
    //     try {
    //         Stripe::setApiKey(env('STRIPE_SECRET'));

    //         $account = Account::create([
    //             'type' => 'express',
    //             'country' => 'US',
    //             'email' => $request->email,
    //         ]);
    //         $account = Account::create([
    //             'type' => 'express',
    //             'country' => 'US',
    //             'email' => $request->email,
    //             'capabilities' => [
    //                 'transfers' => ['requested' => true],
    //             ],
    //         ]);

    //         // Store stripe_account_id in the database
    //         $user = User::where('email', $request->email)->first();
    //         $user->stripe_account_id = $account->id;
    //         $user->save();

    //         return response()->json([
    //             'message' => 'Stripe Connect Account Created',
    //             'stripe_account_id' => $account->id
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }

    public function createStripeAccount(Request $request)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));
    
            // Create a Stripe Express Account
            $account = Account::create([
                'type' => 'express',
                'country' => 'US',
                'email' => $request->email,
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],
            ]);
    
            // Store stripe_account_id in the database
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
    
            $user->stripe_account_id = $account->id;
            $user->save();
    
            // Generate an onboarding link
            $accountLink = \Stripe\AccountLink::create([
                'account' => $account->id,
                'refresh_url' => route('stripe.onboarding', ['id' => $user->id]),
                'return_url' => route('home'),
                'type' => 'account_onboarding',
            ]);
    
            return response()->json([
                'message' => 'Stripe Connect Account Created',
                'stripe_account_id' => $account->id,
                'onboarding_url' => $accountLink->url
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function onboardStripe($id)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));
    
            $user = User::find($id);
            if (!$user || !$user->stripe_account_id) {
                return response()->json(['error' => 'User not found or Stripe account missing'], 404);
            }
    
            $accountLink = \Stripe\AccountLink::create([
                'account' => $user->stripe_account_id,
                'refresh_url' => route('stripe.onboarding', ['id' => $user->id]),
                'return_url' => route('home'),
                'type' => 'account_onboarding',
            ]);
    
            return response()->json([
                'message' => 'Onboarding link generated',
                'onboarding_url' => $accountLink->url
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    

    // Step 2: Charge a Customer (Payment to Superadmin)
    public function chargeCustomer(Request $request)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $charge = Charge::create([
                'amount' => $request->amount * 100,
                'currency' => 'usd',
                'source' => $request->stripeToken,
                'description' => 'Payment for services',
                'transfer_data' => [
                    'destination' => $request->stripe_account_id, // Send to connected account
                ]
            ]);


            return response()->json([
                'message' => 'Payment Successful',
                'charge' => $charge
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Step 3: Transfer Payment from Superadmin to Provider/Sales Rep
    public function payoutProvider(Request $request)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $provider = User::find($request->id);

            if (!$provider || !$provider->stripe_account_id) {
                return response()->json(['error' => 'Provider not found or not connected to Stripe'], 400);
            }

            $amount = $request->amount * 100; // Convert to cents
            $platform_fee = $amount * 0.20; // 20% Superadmin fee
            $payout_amount = $amount - $platform_fee;

            $transfer = Transfer::create([
                'amount' => $payout_amount,
                'currency' => 'usd',
                'destination' => $provider->stripe_account_id,
                'transfer_group' => 'ORDER_' . $request->provider_id
            ]);

            return response()->json([
                'message' => 'Payout Successful',
                'transfer' => $transfer
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Step 4: Webhook for Automatic Payouts
    public function stripeWebhook(Request $request)
    {
        $payload = $request->all();

        // Handle the webhook event
        if ($payload['type'] === 'checkout.session.completed') {
            // Perform actions after payment is completed
            return response()->json(['status' => 'Webhook received']);
        }

        return response()->json(['status' => 'Unhandled event']);
    }
  
}
