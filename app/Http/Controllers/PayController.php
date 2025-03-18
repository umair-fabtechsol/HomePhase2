<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\Charge;
use Stripe\Transfer;
use Stripe\Webhook;
use App\Models\Transection;
use App\Models\User;
use App\Models\Price;
use Exception;

class PayController extends Controller
{
    // -------connect account------
    public function createStripeAccount(Request $request)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            if (!in_array($user->role, [1, 2])) {
                return response()->json(['Access Denied!' => 'Only provider and customer can create payments'], 403);
            }

            // Check if user already has a Stripe account
            if ($user->stripe_account_id) {
                $accountLink = \Stripe\AccountLink::create([
                    'account' => $user->stripe_account_id,
                    'refresh_url' => route('stripe.onboarding', ['id' => $user->id]),
                    'return_url' => route('home'),
                    'type' => 'account_onboarding',
                ]);

                return response()->json([
                    'message' => 'Stripe account already exists',
                    'stripe_account_id' => $user->stripe_account_id,
                    'onboarding_url' => $accountLink->url // Always return a fresh onboarding link
                ]);
            }

            // Create new Stripe Express account
            $account = Account::create([
                'type' => 'express',
                'country' => 'US',
                'email' => $request->email,
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],
            ]);

            // Save new stripe_account_id in database
            $user->stripe_account_id = $account->id;
            $user->save();

            // Generate onboarding link
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
            // Only Providers (role 2) and Admin (role 1) can make payments
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized. Please login first.'], 401);
            }
            if (!in_array($user->role, [1])) {
                return response()->json(['error' => 'Access Denied! Login with customer.'], 403);
            }
            // -----get login id, role-----------------
            $loginId = $user->id;
            $login = User::find($loginId);
            $loginRole = $login->role; 
            // -----get payer id, role-----------------
            $payerId = $request->payerId;
            $payer = User::find($payerId);
            $payerRole = $payer->role; 
            // ===============================================================
            //  for contact pro::if login is customer and payer is provider
            // ================================================================
            if($loginRole==1 && $payerRole==2)  {
                // -----get payer clickable contact pro and thrash-----
                $contactProName = $request->contactProName;
                $contactProThrashName = "th_".$contactProName;
                // -----------get value of clickable contact pro---------
                $price = Price::first(); 
                if ($price && isset($price[$contactProName])) {
                    $contactProPrice = $price[$contactProName]; 
                } 
                // -----------get value of clickable th_contact pro---------
                if ($price && isset($price[$contactProThrashName])) {
                    $contactProThrashPrice = $price[$contactProThrashName]; 
                } 
                // echo $customerId; die();
                // -----------check customer is new or old---------
                $customerRecord = Transection::where('providers_cus_id', $loginId)->first();
                // -------Case 1::If old customer-----------
                if ($customerRecord) {
                    echo "You are not charged because customer is old.";
                } 
                // ---------Case 2::If new customer----------
                else {
                    // --------Case 3::If new customer but threshold reach--------
                    $charge = Charge::create([
                        'amount' => $contactProPrice * 100,
                        'currency' => 'usd',
                        'source' => $request->stripeToken,
                        'description' => 'Payment for services'
                    ]);
        
                    Transection::create([
                        'payer_id' => $payerId, 
                        'payer_role' => $payerRole, 
                        'providers_cus_id' => $user->id, 
                        'stripe_charge_id' => $charge->id,
                        'amount' => $contactProPrice,
                        'currency' => 'usd',
                        'type' => 'payment',
                        'status' => 'successful'
                    ]);
                    return response()->json([
                        'message' => 'Payment Successful',
                        'charge' => $charge
                    ]);
                    
                }
            }
            elseif($loginRole==1) {
                return response()->json(['error' => 'Access Denied! You are not allowed to access this.'], 403);
            }
            else {
                return response()->json(['error' => 'Access Denied! You are not allowed to access this.'], 403);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Step 3: Transfer Payment from Superadmin to Provider/Sales Rep
    public function payoutProvider(Request $request)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            // ------------Only Superadmin (role 0) can process payouts
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized. Please login first.'], 401);
            }
            if ($user->role != 0) {
                return response()->json(['error' => 'Access Denied! Only superadmin can do payouts'], 403);
            }

            $actor = User::find($request->id);

            if (!$actor || !$actor->stripe_account_id) {
                return response()->json(['error' => 'Provider not found or not connected to Stripe'], 400);
            }

            $amount = $request->amount * 100; // Convert to cents
            $platform_fee = $amount * 0.20; // 20% Superadmin fee
            $payout_amount = $amount - $platform_fee;
            $payout_amount = $amount;


            //-------------- Check if Provider is Onboarded
            $account = \Stripe\Account::retrieve($actor->stripe_account_id);
            if (!$account->charges_enabled) {
                return response()->json(['error' => 'Provider has not completed Stripe onboarding'], 400);
            }

            $balance = \Stripe\Balance::retrieve();
            // print_r($balance); die();
            if ($balance->available[0]->amount < $payout_amount) {
                return response()->json(['error' => 'Insufficient balance for payout'], 400);
            }

            $transfer = Transfer::create([
                'amount' => $payout_amount,
                'currency' => 'usd',
                'destination' => $actor->stripe_account_id,
                'transfer_group' => 'ORDER_' . $actor->id,
            ]);
            Transection::create([
                'user_id' => $actor->id, 
                'user_role' => $actor->role, 
                'stripe_transfer_id' => $transfer->id,
                'amount' => $payout_amount / 100, 
                'currency' => 'usd',
                'type' => 'payout',
                'status' => 'successful'
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
