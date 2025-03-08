<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Deal;
use App\Models\BusinessProfile;

class CommonController extends Controller
{
    public function salesrep() {
        $salesReps = User::where('role', 3)->select('id', 'name')->get();

        return response()->json([
            'sales_reps' => $salesReps
        ], 200);
    }
    public function getNotification() {

    }

    public function GetAllDeals(Request $request)
    {
    
        $service = $request->service;
        $budget = $request->budget;
        $reviews = $request->reviews;
        $estimate_time = $request->estimate_time;
        $location = $request->location;
        $distance = $request->distance;
        $deals = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
            ->leftJoin('reviews', 'reviews.deal_id', '=', 'deals.id')
            ->orderBy('deals.id', 'desc')
            ->select('deals.*', 'users.name as user_name', 'users.personal_image', 'reviews.rating as review_rating');
            if($service){
                $deals = $deals->where('service_category', 'like', '%' . $request->service . '%');
            }
            if($reviews){
                $deals = $deals->where('reviews.rating', $reviews);
            }
            if($budget){
                $deals = $deals->where(function ($query) use ($budget) {
                    $query->where('deals.flat_rate_price','<=' , $budget)
                        ->orWhere('deals.hourly_rate','<=' , $budget)
                        ->orWhere('deals.price1','<=' , $budget);
                });
            }
            if($estimate_time){
                $deals = $deals->where(function ($query) use ($estimate_time) {
                    $query->where('deals.flat_estimated_service_time', $estimate_time)
                        ->orWhere('deals.hourly_estimated_service_time', $estimate_time)
                        ->orWhere('deals.estimated_service_timing1', $estimate_time);
                });
            }

            if($distance){
                $locationDistance = BusinessProfile::where('location_miles', '<=', $distance)->pluck('user_id')->toArray();
                $deals = $deals->whereIn('deals.user_id', $locationDistance);
            }

            if($location){
                $locationDistance = BusinessProfile::where('service_location', 'like', '%' . $request->location . '%')->pluck('user_id')->toArray();
                $deals = $deals->whereIn('deals.user_id', $locationDistance);
            }

            $deals = $deals->get();
        if ($deals) {
            return response()->json(['deals' => $deals], 200);
        } else {
            return response()->json(['message' => 'No deals found'], 401);
        }
    }

    public function GetDealDetail($id)
    {
        $deal = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
            ->leftJoin('reviews', 'reviews.deal_id', '=', 'deals.id')
            ->where('deals.id', $id)
            ->select('deals.*', 'users.name as user_name', 'users.personal_image', 'reviews.rating as review_rating')
            ->first();
        if ($deal) {
            return response()->json(['deal' => $deal], 200);
        } else {
            return response()->json(['message' => 'No deal found'], 401);
        }
    }

}
