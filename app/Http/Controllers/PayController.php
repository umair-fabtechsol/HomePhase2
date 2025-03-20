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
use App\Models\Deal;
use App\Models\Order;
use Exception;
use Carbon\Carbon;
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

            // -----get payer id and role-----------------
            $payerId = $request->payerId;
            $payer = User::find($payerId);
            $payerRole = $payer->role; 
            // ===============================================================
            //  for contact pro::if login is customer and payer is provider
            // ===============================================================
            if($loginRole==1 && $payerRole==2)  {
                $request->validate([
                    'stripeToken'  => 'required',
                    'payerId'  => 'required',
                    'contactProName'  => 'required',
                ]);
                // -----get payer clickable contact pro and thrash-----
                $contactProName = $request->contactProName;
                $contactProThrashName = "th_".$contactProName;
                // -----------find value of clickable contact pro---------
                $price = Price::first(); 
                if ($price && isset($price[$contactProName])) {
                    $contactProPrice = $price[$contactProName]; 
                } 
                // -----------find value of clickable th_contact pro---------
                if ($price && isset($price[$contactProThrashName])) {
                    $contactProThrashPrice = $price[$contactProThrashName]; 
                } 
                // -----------check customer is new or old---------
                $customerRecord = Transection::where('type',"contactPro")
                ->where('customer_id', $loginId)
                ->where('provider_id', $payerId)->first();
                // ---------------------------------
                //  Case 1::If old customer
                //  --------------------------------
                if ($customerRecord) {
                    return response()->json(['alert' => 'You are not charged because customer is old.'], 403);
                }
                // ---------------------------------
                //  Case 2::If new customer
                //  --------------------------------
                else {
                    $totalAmount = Transection::where('provider_id', $payerId)
                    ->where('type', "dealPayment")
                    ->where('created_at', '>=', Carbon::now()->subDays(60))
                    ->sum('amount');
                    // -----------------------------------------------
                    //  Case 2a::If new customer & threshold reach
                    //  -----------------------------------------------
                    if($totalAmount >= $contactProThrashPrice) {
                        return response()->json(['alert' => 'You are not charged because threshold reached.'], 403);
                    }
                    // -----------------------------------------------
                    //  Case 2a::If new customer but threshold not reach
                    //  -----------------------------------------------
                    else {
                        $charge = Charge::create([
                            'amount' => $contactProPrice * 100,
                            'currency' => 'usd',
                            'source' => $request->stripeToken,
                            'description' => 'Payment for services'
                        ]);
            
                        Transection::create([
                            'type' => 'contactPro', 
                            'payer_id' => $payerId, 
                            'payer_role' => $payerRole, 
                            'customer_id' => $user->id, 
                            'provider_id' => $payerId, 
                            'stripe_charge_id' => $charge->id,
                            'amount' => $contactProPrice,
                            'currency' => 'usd',
                            'admin_balance' => $contactProPrice,
                            'provider_deduction' => $contactProPrice,
                            'provider_balance' => 0,
                            'customer_deduction' => 0,
                            'provider_payment_status' => 'success',
                            'customer_payment_status' => 'NA',
                            'provider_payout_status' => 'NA',
                            'customer_payout_status' => 'NA',
    
                        ]);
                        return response()->json([
                            'message' => 'Payment Successful',
                            'charge' => $charge
                        ]);
                    }
                    
                }
            }
            // ===============================================================
            //  for purchasing deals::if login is customer
            // ===============================================================
            elseif($loginRole==1 && $payerRole==1) {
                $request->validate([
                    'stripeToken'  => 'required',
                    'payerId'  => 'required',
                    'orderId'  => 'required',
                ]);
                $order = Order::find($request->orderId);
                $providerId = $order->provider_id;
                if($order) {
                    // ---------get percentages that are set by superadmin-------
                    $orderPrice = $order->total_amount;
                    $PlatformPricing = Price::first();
                    $PFCustomerFee = $PlatformPricing->customer_service_fee;
                    $PFProviderFee = $PlatformPricing->provider_service_fee;
                    // --------calculate amount on base of above percentages-------
                    $CustomerFeeAmount = $orderPrice * ($PFCustomerFee / 100);
                    $CustomerFeeAmount = 0;
                    $ProviderFeeAmount = $orderPrice * ($PFProviderFee / 100);

                    $customerDeduction = $orderPrice + $CustomerFeeAmount;
                    $providerDeduction = $ProviderFeeAmount;

                    $providerBalance = $orderPrice - $providerDeduction;
                    $adminBalance = $ProviderFeeAmount + $CustomerFeeAmount;

                    // echo $adminBalance; die();

                    $charge = Charge::create([
                        'amount' => $customerDeduction * 100,
                        'currency' => 'usd',
                        'source' => $request->stripeToken,
                        'description' => 'Payment for deals'
                    ]);
        
                    Transection::create([
                        'type' => 'dealPayment',
                        'payer_id' => $payerId, 
                        'payer_role' => $payerRole, 
                        'customer_id' => $payerId, 
                        'provider_id' => $providerId,
                        'order_id' => $request->orderId, 
                        'stripe_charge_id' => $charge->id,
                        'amount' => $orderPrice,
                        'currency' => 'usd',
                        'admin_balance' => $adminBalance,
                        'provider_deduction' => $providerDeduction,
                        'provider_balance' => $providerBalance,
                        'customer_deduction' => $customerDeduction,
                        'customer_payment_status' => 'success',
                        'provider_payment_status' => 'NA',
                    ]);
                    return response()->json([
                        'message' => 'Payment Successful',
                        'charge' => $charge
                    ]);
                }
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
            $request->validate([
                'transection_id'  => 'required',
            ]);

            $transectionId = $request->transection_id;
            $trans = Transection::find($transectionId);
            $providerPayout = $trans->provider_balance;
            $providerId = $trans->provider_id;
            $amount = $providerPayout * 100;
            $payout_amount = $amount;

            $actor = User::find($providerId);
            if (!$actor || !$actor->stripe_account_id) {
                return response()->json(['error' => 'Provider not found or not connected to Stripe'], 400);
            }
            //-------------- Check if Provider is not Onboarded
            $account = \Stripe\Account::retrieve($actor->stripe_account_id);
            if (!$account->charges_enabled) {
                return response()->json(['error' => 'Provider has not completed Stripe onboarding'], 400);
            }
            //-------------- Check if S.Admin's balance is insufficient
            $balance = \Stripe\Balance::retrieve();
            if ($balance->available[0]->amount < $payout_amount) {
                return response()->json(['error' => 'Insufficient balance for payout'], 400);
            }

            // ---------------if not already payout ----------
            if($trans->provider_payout_status=="pending") {
                $transfer = Transfer::create([
                    'amount' => $payout_amount,
                    'currency' => 'usd',
                    'destination' => $actor->stripe_account_id,
                    'transfer_group' => 'ORDER_' . $actor->id,
                ]);
                Transection::where('id', $transectionId)->update([
                    'provider_payout_status' => 'success'
                ]);
                return response()->json([
                    'message' => 'Payout Successful',
                    'transfer' => $transfer
                ]);
            }
            else {
                return response()->json(['error' => 'Already payout'], 400);
            }
          
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
