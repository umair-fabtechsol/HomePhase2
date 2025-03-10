<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Deal;
use App\Models\BusinessProfile;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;

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
            ->select(
                'deals.id',
                'deals.service_title',
                'deals.service_category',
                'deals.service_description',
                'deals.flat_rate_price',
                'deals.hourly_rate',
                'deals.images',
                'deals.videos',
                'deals.price1',
                'deals.flat_estimated_service_time',
                'deals.hourly_estimated_service_time',
                'deals.estimated_service_timing1',
                'deals.user_id',
                'users.name as user_name',
                'users.personal_image',
                \DB::raw('COALESCE(AVG(reviews.rating), 0) as avg_rating'),
                \DB::raw('COUNT(reviews.id) as total_reviews')
            )
            ->groupBy(
                'deals.id',
                'deals.service_title',
                'deals.service_category',
                'deals.service_description',
                'deals.flat_rate_price',
                'deals.hourly_rate',
                'deals.price1',
                'deals.images',
                'deals.videos',
                'deals.flat_estimated_service_time',
                'deals.hourly_estimated_service_time',
                'deals.estimated_service_timing1',
                'deals.user_id',
                'users.name',
                'users.personal_image'
            );

        // Apply Filters
        if ($service) {
            $deals = $deals->where('deals.service_category', 'like', '%' . $service . '%');
        }

        if ($reviews) {
            $deals = $deals->having('avg_rating', '>=', $reviews);
        }

        if ($budget) {
            $deals = $deals->where(function ($query) use ($budget) {
                $query->whereRaw("CAST(REPLACE(deals.flat_rate_price, '$', '') AS DECIMAL(10,2)) <= ?", [$budget])
                    ->orWhereRaw("CAST(REPLACE(deals.hourly_rate, '$', '') AS DECIMAL(10,2)) <= ?", [$budget])
                    ->orWhereRaw("CAST(REPLACE(deals.price1, '$', '') AS DECIMAL(10,2)) <= ?", [$budget]);
            });
        } 

        if ($estimate_time) {
            $deals = $deals->where(function ($query) use ($estimate_time) {
                $query->where('deals.flat_estimated_service_time', $estimate_time)
                    ->orWhere('deals.hourly_estimated_service_time', $estimate_time)
                    ->orWhere('deals.estimated_service_timing1', $estimate_time);
            });
        }

        if ($distance) {
            $locationDistance = BusinessProfile::where('location_miles', '<=', $distance)->pluck('user_id')->toArray();
            $deals = $deals->whereIn('deals.user_id', $locationDistance);
        }

        if ($location) {
            $locationDistance = BusinessProfile::where('service_location', 'like', '%' . $location . '%')->pluck('user_id')->toArray();
            $deals = $deals->whereIn('deals.user_id', $locationDistance);
        }

        $deals = $deals->get();

        if ($deals->isNotEmpty()) {
            return response()->json(['deals' => $deals], 200);
        } else {
            return response()->json(['message' => 'No deals found'], 401);
        }
    }

    public function GetDealDetail($id)
    {
        $deal = Deal::find($id);
        
        if ($deal) {
            $businessProfile = BusinessProfile::where('user_id', $deal->user_id)->first();
            $getReviews = Review::where('deal_id', $id)->get();
            if($getReviews->isNotEmpty()) {
                $reviews = [];
                $reviews['average'] = floor($reviews->avg('rating'));
                $reviews['total'] = $reviews->count();
            } else {
                $reviews = 0;
            }
            return response()->json(['deal' => $deal, 'businessProfile' => $businessProfile , 'reviews' => $reviews], 200);
        } else {
            return response()->json(['message' => 'No deal found'], 401);
        }
    }

}
