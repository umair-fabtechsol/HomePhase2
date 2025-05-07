<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Deal;
use App\Models\BusinessProfile;
use App\Models\Review;
use App\Models\RecentDealView;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class CommonController extends Controller
{
    public function salesrep()
    {
        $salesReps = User::where('role', 3)->select('id', 'name')->get();

        return response()->json([
            'sales_reps' => $salesReps
        ], 200);
    }
    public function getNotification() {}


    public function GetAllDeals(Request $request)
    {
        $service = $request->service;
        $budget = $request->budget;
        $reviews = $request->reviews;
        $estimate_time = $request->estimate_time;
        $location = $request->location;
        $distance = $request->distance;

        $deals = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
            ->leftJoin('business_profiles', 'business_profiles.user_id', '=', 'deals.user_id')
            ->leftJoin('reviews', 'reviews.deal_id', '=', 'deals.id')
            ->leftJoin('favorit_deals', 'favorit_deals.deal_id', '=', 'deals.id') // Join favorit_deals table
            ->orderBy('deals.id', 'desc')
            ->select(
                'deals.id',
                'deals.service_title',
                'deals.service_category',
                'deals.service_description',
                'deals.pricing_model',
                'deals.flat_rate_price',
                'deals.hourly_rate',
                'deals.images',
                'deals.videos',
                'deals.price1',
                'deals.flat_estimated_service_time',
                'deals.hourly_estimated_service_time',
                'deals.estimated_service_timing1',
                'deals.user_id',
                'business_profiles.business_name as user_name',
                'business_profiles.business_logo',
                // 'users.personal_image',
                \DB::raw('COALESCE(AVG(reviews.rating), 0) as avg_rating'),
                \DB::raw('COUNT(reviews.id) as total_reviews'),
                \DB::raw('GROUP_CONCAT(DISTINCT favorit_deals.user_id ORDER BY favorit_deals.user_id ASC) as favorite_user_ids') // Get all user_ids from favorit_deals
            )
            ->groupBy(
                'deals.id',
                'deals.service_title',
                'deals.service_category',
                'deals.service_description',
                'deals.pricing_model',
                'deals.flat_rate_price',
                'deals.hourly_rate',
                'deals.price1',
                'deals.images',
                'deals.videos',
                'deals.flat_estimated_service_time',
                'deals.hourly_estimated_service_time',
                'deals.estimated_service_timing1',
                'deals.user_id',
                'business_profiles.business_name',
                'business_profiles.business_logo',
            )->where('deals.publish', 1);

        // Apply Filters
        if ($service) {
            $deals = $deals->where('deals.service_category', 'like', '%' . $service . '%');
        }

        if ($reviews) {
            $deals = $deals->havingRaw('avg_rating >= ? AND avg_rating < ?', [$reviews, $reviews + 1]);
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

        $deals = $deals->paginate($request->number_of_deals ?? 12);
        $totalDeals = $deals->total();

        $deals->transform(function ($deal) {
            $deal->favorite_user_ids = $deal->favorite_user_ids ? explode(',', $deal->favorite_user_ids) : [];
            return $deal;
        });

        if ($request->user_id) {
            $userId = $request->user_id;
            $favoritDeals = FavoritDeal::where('user_id', $userId)->pluck('deal_id')->toArray();
        } else {
            $favoritDeals = null;
        }

        // if ($deals->isNotEmpty()) {
        return response()->json(['deals' => $deals, 'totalDeals' => $totalDeals, 'favoritDeals' => $favoritDeals], 200);
        // } else {
        //     return response()->json(['message' => 'No deals found'], 200);
        // }
    }

    public function GetDealDetail(Request $request, $id)
    {
        $deal = Deal::find($id);

        $token = $request->bearerToken();

        if ($token) {
            // Find the user by token
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken) {
                $user = $accessToken->tokenable; // Get the associated user
                $userId = $user->id;
            }
        } else {
            $userId = null;
        }

        if ($deal) {
            $favoriteUserIds = \DB::table('favorit_deals')
                ->where('deal_id', $deal->id)
                ->pluck('user_id') // Get only user IDs
                ->toArray();

            $deal->favorite_user_ids = $favoriteUserIds;

            $businessProfile = BusinessProfile::where('user_id', $deal->user_id)->first();
            $getReviews = Review::where('deal_id', $id)->get();
            if ($getReviews->isNotEmpty()) {
                $reviews = [];
                $reviews['average'] = floor($getReviews->avg('rating'));
                $reviews['total'] = $getReviews->count();
            } else {
                $reviews = [];
                $reviews['average'] = 0;
                $reviews['total'] = 0;
            }

            if ($userId) {
                $viewedDeal = RecentDealView::where('user_id', $userId)->where('deal_id', $id)->first();
                if ($viewedDeal) {
                    $viewedDeal->update([
                        'created_at' => now()
                    ]);
                } else {
                    $recentDeal = RecentDealView::create([
                        'user_id' => $userId,
                        'deal_id' => $id,
                    ]);
                }
            }
            return response()->json(['deal' => $deal, 'businessProfile' => $businessProfile, 'reviews' => $reviews], 200);
        } else {
            return response()->json(['message' => 'No deal found'], 401);
        }
    }
    public function deleteMyAccount()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        if ($user->role == 0) {
            return response()->json(['error' => 'SuperAdmin cannot delete their account'], 404);
        }

        if ($user) {
            $user->delete();
            return response()->json(['message' => 'Account deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    public function googleReview($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $businessProfile = BusinessProfile::where('user_id', $id)->first();
        if (!$businessProfile) {
            return response()->json(['message' => 'Business profile not found'], 404);
        }

        $placeId = $businessProfile->placeId;
        if (!$placeId) {
            return response()->json(['message' => 'Place ID not found'], 404);
        }
        // echo $placeId;die();

        $apiKey = env('GOOGLE_API_KEY');
        $apiKey = 'AIzaSyAu1gwHCSzLG9ACacQqLk-LG8oJMkarNF0';
        // $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&key={$apiKey}";
        $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=name,rating,user_ratings_total,reviews&key={$apiKey}";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        if (isset($data['result']['reviews'])) {
            $googleReview = $data['result']['reviews'];
        } else {
            return response()->json(['message' => 'No reviews found'], 404);
        }
        $reviews = [];
        foreach ($googleReview as $review) {
            $reviews[] = [
                'author_name' => $review['author_name'],
                'rating' => $review['rating'],
                'text' => $review['text'],
                'time' => date('Y-m-d H:i:s', $review['time']),
                'profile_photo_url' => $review['profile_photo_url'],
            ];
        }
        return response()->json(['reviews' => $reviews], 200);
    }
    // public function searchBusiness(Request $request)
    // {
    //     $request->validate([
    //         'search_address' => 'required|json',
    //     ]);

    //     $searchAddress = $request->input('search_address');

    //     $geocode = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($searchAddress) . "&key=AIzaSyAu1gwHCSzLG9ACacQqLk-LG8oJMkarNF0");
    //     $geocode = json_decode($geocode);

    //     if (isset($geocode->results[0])) {
    //         $addressComponents = $geocode->results[0]->address_components;
    //         $country = null;
    //         $province = null;
    //         $city = null;
    //         $zip = null;

    //         foreach ($addressComponents as $component) {
    //             if (in_array('country', $component->types)) {
    //                 $country = $component->long_name;
    //             }
    //             if (in_array('administrative_area_level_1', $component->types)) {
    //                 $province = $component->long_name;
    //             }
    //             if (in_array('locality', $component->types)) {
    //                 $city = $component->long_name;
    //             }
    //             if (in_array('postal_code', $component->types)) {
    //                 $zip = $component->long_name;
    //             }
    //         }

    //         $query = BusinessProfile::query();

    //         if ($country) {
    //             $query->where('business_location', 'LIKE', '%' . $country . '%');
    //         }

    //         if ($province) {
    //             $query->where('business_location', 'LIKE', '%' . $province . '%');
    //         }

    //         if ($city) {
    //             $query->where('business_location', 'LIKE', '%' . $city . '%');
    //         }

    //         if ($zip) {
    //             $query->where('business_location', 'LIKE', '%' . $zip . '%');
    //         }

    //         $businesses = $query->pluck('user_id');


    //         $deals = Deal::whereIn('user_id', $businesses)->get();

    //         return response()->json([
    //             'businesses' => $businesses,
    //             'deals' => $deals,
    //         ]);
    //     } else {
    //         return response()->json([
    //             'no data found'
    //         ]);
    //     }
    // }
    public function searchBusiness(Request $request)
    {
        $request->validate([
            'search_address' => 'required|string',
            'service' => 'nullable|string', // new input
        ]);

        $searchAddress = $request->input('search_address');
        $searchService = $request->input('service');

        $geocode = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($searchAddress) . "&key=AIzaSyAu1gwHCSzLG9ACacQqLk-LG8oJMkarNF0");
        $geocode = json_decode($geocode);

        if (isset($geocode->results[0])) {
            $addressComponents = $geocode->results[0]->address_components;
            $country = null;
            $province = null;
            $city = null;
            $zip = null;

            foreach ($addressComponents as $component) {
                if (in_array('country', $component->types)) {
                    $country = $component->long_name;
                }
                if (in_array('administrative_area_level_1', $component->types)) {
                    $province = $component->long_name;
                }
                if (in_array('locality', $component->types)) {
                    $city = $component->long_name;
                }
                if (in_array('postal_code', $component->types)) {
                    $zip = $component->long_name;
                }
            }

            $query = BusinessProfile::query();

            $query->where(function ($q) use ($country, $province, $city, $zip) {
                if ($country) $q->orWhere('business_location', 'LIKE', '%' . $country . '%');
                if ($province) $q->orWhere('business_location', 'LIKE', '%' . $province . '%');
                if ($city) $q->orWhere('business_location', 'LIKE', '%' . $city . '%');
                if ($zip) $q->orWhere('business_location', 'LIKE', '%' . $zip . '%');
            });

            $businesses = $query->pluck('user_id');

            $dealsQuery = Deal::whereIn('user_id', $businesses);

            if (!empty($searchService)) {
                $dealsQuery->where(function ($q) use ($searchService) {
                    $q->where('service_title', 'LIKE', '%' . $searchService . '%')
                        ->orWhere('service_category', 'LIKE', '%' . $searchService . '%')
                        ->orWhere('search_tags', 'LIKE', '%' . $searchService . '%');
                });
            }

            $deals = $dealsQuery->get();

            return response()->json([
                'businesses' => $businesses,
                'deals' => $deals,
            ]);
        } else {
            return response()->json([
                'message' => 'No data found.',
            ], 404);
        }
    }
}
