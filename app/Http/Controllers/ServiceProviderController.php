<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\Deal;
use App\Models\User;
use App\Models\Order;
use App\Models\Price;
use App\Models\Review;
use App\Models\Support;
use App\Models\FavoritDeal;
use App\Models\Notification;
use App\Models\DeliveryImage;
use App\Models\PaymentDetail;
use App\Models\SocialProfile;
use App\Models\RecentDealView;
use App\Models\PaymentHistory;
use App\Models\BusinessProfile;

class ServiceProviderController extends Controller
{



    public function Deals(Request $request)
    {
        $service = $request->search;
        $role = Auth::user()->role;
        if ($role == 2) {
            $userId = Auth::id();
            $deals = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
                ->leftJoin('business_profiles', 'business_profiles.user_id', '=', 'deals.user_id')
                ->leftJoin('reviews', 'reviews.deal_id', '=', 'deals.id')
                ->leftJoin('favorit_deals', 'favorit_deals.deal_id', '=', 'deals.id')
                ->orderBy('deals.id', 'desc')
                ->select(
                    'deals.id',
                    'deals.service_title',
                    'deals.service_category',
                    'deals.service_description',
                    'deals.pricing_model',
                    'deals.flat_rate_price',
                    'deals.hourly_rate',

                    'deals.flat_by_now_discount',
                    'deals.flat_final_list_price',
                    'deals.discount as hourly_discount',
                    'deals.hourly_final_list_price',
                    'deals.by_now_discount1',
                    'deals.final_list_price1',
                    'deals.by_now_discount2',
                    'deals.final_list_price2',
                    'deals.by_now_discount3',
                    'deals.final_list_price3',

                    'deals.images',
                    'deals.videos',
                    'deals.price1',
                    'deals.flat_estimated_service_time',
                    'deals.hourly_estimated_service_time',
                    'deals.estimated_service_timing1',
                    'deals.user_id',
                    'business_profiles.business_name as user_name',
                    'business_profiles.business_logo',
                    DB::raw('COALESCE(AVG(reviews.rating), 0) as avg_rating'),
                    DB::raw('COUNT(reviews.id) as total_reviews'),
                    DB::raw('GROUP_CONCAT(DISTINCT favorit_deals.user_id ORDER BY favorit_deals.user_id ASC) as favorite_user_ids')
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
                    'deals.flat_by_now_discount',
                    'deals.flat_final_list_price',
                    'deals.discount',
                    'deals.hourly_final_list_price',
                    'deals.by_now_discount1',
                    'deals.final_list_price1',
                    'deals.by_now_discount2',
                    'deals.final_list_price2',
                    'deals.by_now_discount3',
                    'deals.final_list_price3',
                )->where('deals.user_id', $userId);

            if ($service) {
                $deals = $deals->where(function ($query) use ($service) {
                    $query->where('deals.service_category', 'like', '%' . $service . '%')
                        ->orWhere('deals.service_title', 'like', '%' . $service . '%')
                        ->orWhere('deals.search_tags', 'like', '%' . $service . '%')
                        ->orWhere('deals.service_description', 'like', '%' . $service . '%')
                        ->orWhere('deals.commercial', 'like', '%' . $service . '%')
                        ->orWhere('deals.residential', 'like', '%' . $service . '%');
                });
            }

            $deals = $deals->orderBy('deals.id', 'desc')->paginate($request->number_of_deals ?? 12);

            $totalDeals = $deals->total();

            $deals->transform(function ($deal) {
                $deal->favorite_user_ids = $deal->favorite_user_ids ? explode(',', $deal->favorite_user_ids) : [];
                return $deal;
            });

            if ($deals) {
                if ($request->user_id) {
                    $userId = $request->user_id;
                    $favoritDeals = FavoritDeal::where('user_id', $userId)->pluck('deal_id')->toArray();
                } else {
                    $favoritDeals = null;
                }
                return response()->json(['deals' => $deals, 'totalDeals' => $totalDeals, 'favoritDeals' => $favoritDeals], 200);
            } else {
                return response()->json(['message' => 'No deals found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function Deal($id)
    {
        $userId = Auth::id();
        $deal = Deal::find($id);

        if ($deal) {
            $favoriteUserIds = DB::table('favorit_deals')
                ->where('deal_id', $deal->id)
                ->pluck('user_id')
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
            return response()->json(['deal' => $deal, 'businessProfile' => $businessProfile, 'reviews' => $reviews], 200);
        } else {
            return response()->json(['message' => 'No deal found'], 401);
        }
    }

    public function DealPublish($deal_id)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        $deal = Deal::find($deal_id);
        if ($deal) {
            $deal->update(['publish' => 1]);
            $deal = Deal::find($deal_id);
            return response()->json(['message' => 'Deal Publish successfully', 'deal' => $deal], 200);
        } else {
            return response()->json(['message' => 'No deals found'], 401);
        }
    }

    public function BasicInfo(Request $request)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        $userRole = Auth::user()->role;
        $validator = Validator::make($request->all(), [
            'service_title' => 'required',
            'service_category' => 'required',
            'service_description' => 'required',
            'commercial' => 'nullable',
            'residential' => 'nullable',
        ], [
            'at_least_one.required' => 'At least one of commercial or residential is required.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (empty($request->commercial) && empty($request->residential)) {
                $validator->errors()->add('at_least_one', 'At least one of commercial or residential is required.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $request->all();
        if (!empty($request->id)) {
            $deal = Deal::find($request->id);
            if ($deal) {
                $data = $request->all();
                if ($request->has('commercial')) {
                } else {
                    $data['commercial'] = null;
                }
                if ($request->has('residential')) {
                } else {
                    $data['residential'] = null;
                }
                $deal->update($data);

                return response()->json(['message' => 'Deal updated successfully', 'deal' => $deal], 200);
            } else {
                return response()->json(['message' => 'No deals found'], 401);
            }
        } else {

            $data['user_id'] = $userId;
            $data['publish'] = 0;
            $deal = Deal::create($data);

            return response()->json(['message' => 'Added new deal successfully', 'deal' => $deal], 200);
        }
    }


    public function PublishBasicInfo(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $userId = Auth::id();
            $userRole = Auth::user()->role;
            $validator = Validator::make($request->all(), [
                'service_title' => 'required',
                'service_category' => 'required',
                'service_description' => 'required',
                'commercial' => 'nullable',
                'residential' => 'nullable',
            ], [
                'at_least_one.required' => 'At least one of commercial or residential is required.',
            ]);

            $validator->after(function ($validator) use ($request) {
                if (empty($request->commercial) && empty($request->residential)) {
                    $validator->errors()->add('at_least_one', 'At least one of commercial or residential is required.');
                }
            });

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $data = $request->all();

            $data['user_id'] = $userId;
            $data['publish'] = 1;
            $deal = Deal::create($data);

            return response()->json(['message' => 'Added new deal and Publish successfully', 'deal' => $deal], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function PriceAndPackage(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $userId = Auth::id();
            $userRole = Auth::user()->role;

            $data = $request->all();
            if ($userRole != 2) {
                return response()->json(['message' => 'Access denied. Only providers can perform this action.'], 400);
            }
            if (!empty($request->id)) {
                $deal = Deal::find($request->id);

                if ($deal) {
                    $data = $request->all();
                    if ($data['pricing_model'] == 'Flat') {
                        $data['hourly_rate'] = null;
                        $data['discount'] = null;
                        $data['hourly_final_list_price'] = null;
                        $data['hourly_estimated_service_time'] = null;
                        $data['title1'] = null;
                        $data['deliverable1'] = null;
                        $data['price1'] = null;
                        $data['by_now_discount1'] = null;
                        $data['final_list_price1'] = null;
                        $data['estimated_service_timing1'] = null;
                        $data['title2'] = null;
                        $data['deliverable2'] = null;
                        $data['price2'] = null;
                        $data['by_now_discount2'] = null;
                        $data['final_list_price2'] = null;
                        $data['estimated_service_timing2'] = null;
                        $data['title3'] = null;
                        $data['deliverable3'] = null;
                        $data['price3'] = null;
                        $data['by_now_discount3'] = null;
                        $data['final_list_price3'] = null;
                        $data['estimated_service_timing3'] = null;
                    } elseif ($data['pricing_model'] == 'Hourly') {
                        $data['flat_rate_price'] = null;
                        $data['flat_by_now_discount'] = null;
                        $data['flat_final_list_price'] = null;
                        $data['flat_estimated_service_time'] = null;
                        $data['title1'] = null;
                        $data['deliverable1'] = null;
                        $data['price1'] = null;
                        $data['by_now_discount1'] = null;
                        $data['final_list_price1'] = null;
                        $data['estimated_service_timing1'] = null;
                        $data['title2'] = null;
                        $data['deliverable2'] = null;
                        $data['price2'] = null;
                        $data['by_now_discount2'] = null;
                        $data['final_list_price2'] = null;
                        $data['estimated_service_timing2'] = null;
                        $data['title3'] = null;
                        $data['deliverable3'] = null;
                        $data['price3'] = null;
                        $data['by_now_discount3'] = null;
                        $data['final_list_price3'] = null;
                        $data['estimated_service_timing3'] = null;
                    } else {
                        $data['flat_rate_price'] = null;
                        $data['flat_by_now_discount'] = null;
                        $data['flat_final_list_price'] = null;
                        $data['flat_estimated_service_time'] = null;
                        $data['hourly_rate'] = null;
                        $data['discount'] = null;
                        $data['hourly_final_list_price'] = null;
                        $data['hourly_estimated_service_time'] = null;
                    }
                    $deal->update($data);

                    return response()->json(['message' => 'Package deal updated successfully', 'deal' => $deal], 200);
                } else {
                    return response()->json(['message' => 'No deals found'], 401);
                }
            } else {

                $data['user_id'] = $userId;
                $data['publish'] = 0;
                $deal = Deal::create($data);

                return response()->json(['message' => 'Added new package deal successfully', 'deal' => $deal], 200);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function PublishPriceAndPackage(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $userId = Auth::id();
            $userRole = Auth::user()->role;

            $data = $request->all();
            if ($userRole != 2) {
                return response()->json(['message' => 'Access denied. Only providers can perform this action.'], 400);
            }


            $data['user_id'] = $userId;
            $data['publish'] = 1;
            $deal = Deal::create($data);

            return response()->json(['message' => 'Added new package deal and Publish successfully', 'deal' => $deal], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function MediaUpload(Request $request)
    {
        $userId = Auth::id();
        $DealImages = [];
        $DealVideos = [];
        $role = Auth::user()->role;
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $photo) {
                $photo_name = time() . '-' . $photo->getClientOriginalName();
                $photo->move(public_path('uploads'), $photo_name);
                $DealImages[] = $photo_name;
            }
        }

        if ($request->has('images') && is_array($request->input('images'))) {
            $DealImages = array_merge($DealImages, $request->input('images'));
        }

        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $video) {
                $video_name = time() . '-' . $video->getClientOriginalName();
                $video->move(public_path('uploads'), $video_name);
                $DealVideos[] = $video_name;
            }
        }

        if ($request->has('videos') && is_array($request->input('videos'))) {
            $DealVideos = array_merge($DealVideos, $request->input('videos'));
        }
        $deal = Deal::find($request->deal_id);
        if ($deal) {
            if ($role == 2) {
                if ($deal->user_id != $userId) {
                    return response()->json(['message' => 'You are not authorized to update this deal.'], 401);
                }
            }
            $deal->update([
                'images' => json_encode($DealImages),
                'videos' => json_encode($DealVideos),
            ]);
            return response()->json([
                'message' => 'Deal media updated successfully.',
                'deal' => $deal,
                'uploaded_images' => $DealImages,
                'uploaded_videos' => $DealVideos,
            ], 200);
        } else {
            if ($role == 0) {
                return response()->json(['message' => 'Admin is not authorized to create a new deal.'], 401);
            }

            $data = $request->all();
            $data['user_id'] = $userId;
            $data['images'] = json_encode($DealImages);
            $data['videos'] = json_encode($DealVideos);
            $deal = Deal::create($data);

            return response()->json([
                'message' => 'New deal created with media successfully.',
                'deal' => $deal,
                'uploaded_images' => $DealImages,
                'uploaded_videos' => $DealVideos,
            ], 200);
        }
    }

    public function DeleteMediaUpload(Request $request)
    {

        $getDeal = Deal::find($request->id);

        if ($request->type == 'images') {

            $images = json_decode($getDeal->images);
            $imagePath = public_path('uploads/' . $images[$request->index]);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            unset($images[$request->index]);
            $updateimages = array_values($images);

            $getDeal->update(['images' => json_encode($updateimages)]);
            return response()->json([
                'message' => 'Image deleted successfully'
            ], 200);
        } elseif ($request->type == 'videos') {
            $videos = json_decode($getDeal->videos);
            $videoPath = public_path('uploads/' . $videos[$request->index]);
            if (file_exists($videoPath)) {
                unlink($videoPath);
            }
            unset($videos[$request->index]);
            $updatevideos = array_values($videos);

            $getDeal->update(['videos' => json_encode($updatevideos)]);
            return response()->json([
                'message' => 'Video deleted successfully'
            ], 200);
        }
    }
    public function PublishMediaUpload(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $userId = Auth::id();
            $userRole = Auth::user()->role;
            $validator = Validator::make($request->all(), [

                'images' => 'required',
                'videos' => 'required',

            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            $DealImages = [];
            $DealVideos = [];

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $photo) {
                    $photo_name = time() . '-' . $photo->getClientOriginalName();
                    $photo->move(public_path('uploads'), $photo_name);
                    $DealImages[] = $photo_name;
                }
            }

            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $video) {
                    $video_name = time() . '-' . $video->getClientOriginalName();
                    $video->move(public_path('uploads'), $video_name);
                    $DealVideos[] = $video_name;
                }
            }


            $data['images'] = json_encode($DealImages);
            $data['videos'] = json_encode($DealVideos);
            $data['user_id'] = $userId;
            $data['publish'] = 1;

            $deal = Deal::create($data);
            return response()->json([
                'message' => 'Added new deal with Images and Publish successfully',
                'deal' => $deal,
            ], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdateBasicInfo(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $deal = Deal::find($request->id);
            if ($deal) {
                $data = $request->all();
                if ($request->has('commercial')) {
                } else {
                    $data['commercial'] = null;
                }
                if ($request->has('residential')) {
                } else {
                    $data['residential'] = null;
                }
                $deal->update($data);
                return response()->json(['message' => 'Deal updated successfully', 'deal' => $deal], 200);
            } else {
                return response()->json(['message' => 'No deals found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdatePriceAndPackage(Request $request)
    {

        $deal = Deal::find($request->id);
        if ($deal) {
            $data = $request->all();
            if ($data['pricing_model'] == 'Flat') {
                $data['hourly_rate'] = null;
                $data['discount'] = null;
                $data['hourly_final_list_price'] = null;
                $data['hourly_estimated_service_time'] = null;
                $data['title1'] = null;
                $data['deliverable1'] = null;
                $data['price1'] = null;
                $data['by_now_discount1'] = null;
                $data['final_list_price1'] = null;
                $data['estimated_service_timing1'] = null;
                $data['title2'] = null;
                $data['deliverable2'] = null;
                $data['price2'] = null;
                $data['by_now_discount2'] = null;
                $data['final_list_price2'] = null;
                $data['estimated_service_timing2'] = null;
                $data['title3'] = null;
                $data['deliverable3'] = null;
                $data['price3'] = null;
                $data['by_now_discount3'] = null;
                $data['final_list_price3'] = null;
                $data['estimated_service_timing3'] = null;
            } elseif ($data['pricing_model'] == 'Hourly') {
                $data['flat_rate_price'] = null;
                $data['flat_by_now_discount'] = null;
                $data['flat_final_list_price'] = null;
                $data['flat_estimated_service_time'] = null;
                $data['title1'] = null;
                $data['deliverable1'] = null;
                $data['price1'] = null;
                $data['by_now_discount1'] = null;
                $data['final_list_price1'] = null;
                $data['estimated_service_timing1'] = null;
                $data['title2'] = null;
                $data['deliverable2'] = null;
                $data['price2'] = null;
                $data['by_now_discount2'] = null;
                $data['final_list_price2'] = null;
                $data['estimated_service_timing2'] = null;
                $data['title3'] = null;
                $data['deliverable3'] = null;
                $data['price3'] = null;
                $data['by_now_discount3'] = null;
                $data['final_list_price3'] = null;
                $data['estimated_service_timing3'] = null;
            } else {
                $data['flat_rate_price'] = null;
                $data['flat_by_now_discount'] = null;
                $data['flat_final_list_price'] = null;
                $data['flat_estimated_service_time'] = null;
                $data['hourly_rate'] = null;
                $data['discount'] = null;
                $data['hourly_final_list_price'] = null;
                $data['hourly_estimated_service_time'] = null;
            }
            $deal->update($data);
            return response()->json(['deal' => $deal], 200);
        } else {
            return response()->json(['message' => 'No deals found'], 401);
        }
    }

    public function UpdateMediaUpload(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $deal = Deal::find($request->id);
            if ($deal) {
                $data = [];
                if ($request->hasFile('image')) {
                    $imagePath = public_path('uploads/' . $deal->image);
                    if (!empty($deal->image) && file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    $photo1 = $request->file('image');
                    $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                    $photo_destination = public_path('uploads');
                    $photo1->move($photo_destination, $photo_name1);
                    $data['image'] = $photo_name1;
                    $data['id'] = $request->id;
                    $deal->update($data);
                }
                return response()->json(['deal' => $deal], 200);
            } else {
                return response()->json(['message' => 'No deals found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function DeleteDeal($id)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();

        $deal = Deal::find($id);
        if (!$deal) {
            return response()->json(['message' => 'No deal found'], 404);
        }

        if ($role == 2 && $deal->user_id != $userId) {
            return response()->json(['message' => 'You are not authorized to delete this deal'], 403);
        }

        $images = is_array(json_decode($deal->images, true)) ? json_decode($deal->images, true) : [];
        $videos = is_array(json_decode($deal->videos, true)) ? json_decode($deal->videos, true) : [];

        foreach ($images as $image) {
            if (is_string($image)) {
                $imagePath = public_path('uploads/' . $image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        }

        foreach ($videos as $video) {
            if (is_string($video)) {
                $videoPath = public_path('uploads/' . $video);
                if (file_exists($videoPath)) {
                    unlink($videoPath);
                }
            }
        }

        FavoritDeal::where('deal_id', $id)->delete();

        $deal->delete();

        return response()->json(['message' => 'Deal and associated favorites deleted successfully'], 200);
    }
    public function MyDetails(Request $request, $id = null)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($id != null) {
            $user = User::find($id);
        } else {
            $user = User::find($userId);
        }


        if ($user) {
            $data = $request->all();
            if ($request->hasFile('personal_image')) {
                $imagePath = public_path('uploads/' . $user->personal_image);
                if (!empty($user->personal_image) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo1 = $request->file('personal_image');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['personal_image'] = $photo_name1;
            } 
         

            $validator = Validator::make($data, [
                'phone' => ['required', 'phone:AUTO'],
            ]);

            if ($validator->fails()) {
                return response()->json(['phone' => 'Invalid phone number'], 400);
            }
            if (!empty($data['phone']) && !str_starts_with($data['phone'], '+')) {
                $data['phone'] = '+' . $data['phone'];
            }

            $user->update($data);

            return response()->json(['message' => 'User Personal details updated successfully', 'user' => $user], 200);
        } else {
            return response()->json(['message' => 'No user found'], 401);
        }
    }

    public function UpdatePassword(Request $request, $id = null)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($id != null) {

            $user = User::find($id);
        } else {

            $user = User::find($userId);
        }
        if ($user) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 401);
            }
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json(['message' => 'User Password Updated successfully', 'user' => $user], 200);
        } else {
            return response()->json(['message' => 'No user found'], 401);
        }
    }

    public function BusinessProfile(Request $request, $id = null)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($id != null && $role != 0) {
            return response()->json(['message' => 'Only admin can pass id parameter'], 401);
        }
        if ($id != null) {
            $businessProfile = BusinessProfile::where('user_id', $id)->first();
            $userExist = User::find($id);
        } else {
            $businessProfile = BusinessProfile::where('user_id', $userId)->first();
            $userExist = User::where('id', $userId);
        }
        $data = $request->all();
        if ($businessProfile) {
            if ($request->hasFile('business_logo')) {
                $imagePath = public_path('uploads/' . $businessProfile->business_logo);
                if (!empty($businessProfile->business_logo) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo1 = $request->file('business_logo');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['business_logo'] = $photo_name1;
            }
            $businessProfileup = $businessProfile->update($data);
            $notifications = [
                'title' => 'Update User Business Profile',
                'message' => 'User Business Profile Updated successfully',
                'created_by' => $userId,
                'status' => 0,
                'clear' => 'no',

            ];
            Notification::create($notifications);

            return response()->json(['message' => 'Business Profile Updated successfully', 'BusinessProfile' => $businessProfile], 200);
        } else {
            if ($userExist) {
                if ($request->hasFile('business_logo')) {
                    $photo1 = $request->file('business_logo');
                    $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                    $photo_destination = public_path('uploads');
                    $photo1->move($photo_destination, $photo_name1);
                    $data['business_logo'] = $photo_name1;
                }
                if ($id != null) {
                    $data['user_id'] = $id;
                } else {
                    $data['user_id'] = $userId;
                }
                $businessProfile = BusinessProfile::create($data);
                $notifications = [
                    'title' => 'Created User Business Profile',
                    'message' => 'User Business Profile created successfully',
                    'created_by' => $userId,
                    'status' => 0,
                    'clear' => 'no',

                ];
                Notification::create($notifications);
            } else {
                return response()->json([
                    'message' => 'Error: User does not exist. In order to create or update business profile, please add a valid user first.'
                ], 404);
            }
        }

        return response()->json(['message' => 'User Business Profile created successfully', 'BusinessProfile' => $businessProfile], 200);
    }
    public function AddPaymentDetails(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $data = $request->all();
            $payment = PaymentDetail::where('user_id', $request->user_id)->first();
            if ($payment) {

                $payment->update($data);
                $notifications = [
                    'title' => 'update Payment details',
                    'message' => 'Updated Payment details successfully',
                    'created_by' => $payment->user_id,
                    'status' => 0,
                    'clear' => 'no',

                ];
                Notification::create($notifications);
                return response()->json(['message' => 'Updated Payment details successfully', 'payment' => $payment], 200);
            } else {
                if (isset($request->user_id)) {
                    $data['user_id'] = $request->user_id;
                    $payment = PaymentDetail::create($data);
                    $notifications = [
                        'title' => 'Create Payment details',
                        'message' => 'Added Payment details successfully',
                        'created_by' => $request->user_id,
                        'status' => 0,
                        'clear' => 'no',

                    ];
                    Notification::create($notifications);
                    return response()->json(['message' => 'Added Payment details successfully', 'payment' => $payment], 200);
                }
            }
            return response()->json(['message' => 'User not found'], 401);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdatePaymentDetails(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $payment = PaymentDetail::find($request->id);

            $data = $request->all();

            $payment->update($data);

            return response()->json(['message' => 'Updated Payment details successfully', 'payment' => $payment], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function DeletePaymentDetails($id)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $payment = PaymentDetail::find($id);
            $payment->delete();
            return response()->json(['message' => 'Deleted Payment details successfully', 'payment' => $payment], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function AdditionalPhotos(Request $request, $id = null)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();


        if ($id != null) {
            $businessProfile = BusinessProfile::where('user_id', $id)->first();
        } else {
            $businessProfile = BusinessProfile::where('user_id', $userId)->first();
        }

        $data = $request->all();

        if ($businessProfile) {
            $fields = ['about_video', 'technician_photo', 'vehicle_photo', 'facility_photo', 'project_photo'];
            foreach ($fields as $field) {
                $uploadedFiles = [];

                if ($request->hasFile($field)) {
                    foreach ($request->file($field) as $file) {
                        $fileName = time() . '-' . $file->getClientOriginalName();
                        $file->move(public_path('uploads'), $fileName);
                        $uploadedFiles[] = $fileName;
                    }
                }

                if ($request->has($field) && is_array($request->input($field))) {
                    $uploadedFiles = array_merge($uploadedFiles, $request->input($field));
                }

                if (!empty($uploadedFiles)) {
                    $data[$field] = json_encode($uploadedFiles);
                } else {
                    $data[$field] = [];
                }
            }
            $businessProfile->update($data);

            Notification::create([
                'title' => 'Update User Business Additional Info',
                'message' => 'User Business Additional Info Updated successfully',
                'created_by' => $businessProfile->user_id,
                'status' => 0,
                'clear' => 'no',
            ]);

            return response()->json([
                'message' => 'User Business Additional Info Updated successfully',
                'BusinessProfile' => $businessProfile
            ], 200);
        } else {
            if ($request->hasFile('technician_photo')) {
                $photo1 = $request->file('technician_photo');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['technician_photo'] = $photo_name1;
            }
            if ($request->hasFile('about_video')) {
                $photo1 = $request->file('about_video');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['about_video'] = $photo_name1;
            }
            if ($request->hasFile('vehicle_photo')) {
                $photo1 = $request->file('vehicle_photo');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['vehicle_photo'] = $photo_name1;
            }
            if ($request->hasFile('facility_photo')) {
                $photo1 = $request->file('facility_photo');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['facility_photo'] = $photo_name1;
            }
            if ($request->hasFile('project_photo')) {
                $photo1 = $request->file('project_photo');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['project_photo'] = $photo_name1;
            }
            $data['user_id'] = $userId;
            $businessProfile = BusinessProfile::create($data);
            $notifications = [
                'title' => 'Created User Business Additional Info',
                'message' => 'User Business Additional Info created successfully',
                'created_by' => $userId,
                'status' => 0,
                'clear' => 'no',

            ];
            Notification::create($notifications);
        }

        return response()->json(['message' => 'User Business Additional Info created successfully', 'BusinessProfile' => $businessProfile], 200);
    }

    public function AddCertificateHours(Request $request, $id = null)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        $data = $request->except(['insurance_certificate', 'license_certificate', 'award_certificate']);

        if ($id != null) {
            $updateCertificateHours = BusinessProfile::where('user_id', $id)->first();
            $userExist = User::find($id);
        } else {
            $updateCertificateHours = BusinessProfile::where('user_id', $userId)->first();
            $userExist = User::find($userId);
        }
        if ($role != 0 && $id != Null) {
            return response()->json(['message' => 'Unauthorized! Only admin can provide id parameter'], 403);
        }
        if ($role == 0 && $id == Null) {
            return response()->json(['message' => 'Unauthorized! Incorrect token. Please use provider token'], 403);
        }
        $uploadMultiple = function ($fieldName, $existingPaths = []) use ($request) {
            $newFiles = [];
            foreach ((array) $existingPaths as $oldFile) {
                $oldPath = public_path('uploads/' . $oldFile);
            }

            if ($request->hasFile($fieldName)) {
                foreach ($request->file($fieldName) as $file) {
                    $filename = time() . '-' . $file->getClientOriginalName();
                    $file->move(public_path('uploads'), $filename);
                    $newFiles[] = $filename;
                }
            }

            if ($request->has($fieldName) && is_array($request->input($fieldName))) {
                $newFiles = array_merge($newFiles, $request->input($fieldName));
            }
            return $newFiles;
        };

        if ($userExist) {
            if ($updateCertificateHours) {
                if ($id != Null) {
                    $data['user_id'] = $id;
                } else {
                    $data['user_id'] = $userId;
                }
                $data['insurance_certificate'] = json_encode($uploadMultiple('insurance_certificate', json_decode($updateCertificateHours->insurance_certificate, true)));
                $data['license_certificate'] = json_encode($uploadMultiple('license_certificate', json_decode($updateCertificateHours->license_certificate, true)));
                $data['award_certificate'] = json_encode($uploadMultiple('award_certificate', json_decode($updateCertificateHours->award_certificate, true)));

                $updateCertificateHours->update($data);

                Notification::create([
                    'title' => 'Update Business CertificateHour',
                    'message' => 'Business CertificateHour updated successfully',
                    'created_by' => $updateCertificateHours->user_id,
                    'status' => 0,
                    'clear' => 'no',
                ]);

                return response()->json([
                    'message' => 'Business CertificateHour updated successfully',
                    'updateCertificateHours' => $updateCertificateHours
                ], 200);
            } else {
                $data['insurance_certificate'] = json_encode($uploadMultiple('insurance_certificate'));
                $data['license_certificate'] = json_encode($uploadMultiple('license_certificate'));
                $data['award_certificate'] = json_encode($uploadMultiple('award_certificate'));
                if ($id != Null) {
                    $data['user_id'] = $id;
                } else {
                    $data['user_id'] = $userId;
                }

                $certificate = BusinessProfile::create($data);

                Notification::create([
                    'title' => 'Business CertificateHour',
                    'message' => 'Business CertificateHour created successfully',
                    'created_by' => $userId,
                    'status' => 0,
                    'clear' => 'no',
                ]);

                return response()->json([
                    'message' => 'Business CertificateHour created successfully',
                    'certificate' => $certificate
                ], 200);
            }
        } else {
            return response()->json(['message' => 'Invalid User'], 403);
        }
    }

    public function UpdateCertificateHours(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $data = $request->all();
            $updateCertificateHours = BusinessProfile::where('user_id', $request->id)->first();
            if ($request->hasFile('insurance_certificate')) {
                $imagePath = public_path('uploads/' . $updateCertificateHours->insurance_certificate);
                if (!empty($updateCertificateHours->insurance_certificate) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo1 = $request->file('insurance_certificate');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['insurance_certificate'] = $photo_name1;
            }
            if ($request->hasFile('license_certificate')) {
                $imagePath = public_path('uploads/' . $updateCertificateHours->license_certificate);
                if (!empty($updateCertificateHours->license_certificate) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo2 = $request->file('license_certificate');
                $photo_name2 = time() . '-' . $photo2->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo2->move($photo_destination, $photo_name2);
                $data['license_certificate'] = $photo_name2;
            }
            if ($request->hasFile('award_certificate')) {
                $imagePath = public_path('uploads/' . $updateCertificateHours->award_certificate);
                if (!empty($updateCertificateHours->award_certificate) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo3 = $request->file('award_certificate');
                $photo_name3 = time() . '-' . $photo3->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo3->move($photo_destination, $photo_name3);
                $data['award_certificate'] = $photo_name3;
            }
            $updateCertificateHours->update($data);

            return response()->json(['message' => 'CertificateHour updated successfully', 'updateCertificateHours' => $updateCertificateHours], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function AddConversation(Request $request, $id = null)
    {
        $role = Auth::user()->role;

        $userId = Auth::id();

        $data = $request->all();
        if ($id != null) {


            $conversation = BusinessProfile::where('user_id', $id)->first();
        } else {

            $conversation = BusinessProfile::where('user_id', $userId)->first();
        }
        if ($conversation) {
            if (!empty($data['conversation_call_number']) && !str_starts_with($data['conversation_call_number'], '+')) {
                $data['conversation_call_number'] = '+' . $data['conversation_call_number'];
            }

            if (!empty($data['conversation_text_number']) && !str_starts_with($data['conversation_text_number'], '+')) {
                $data['conversation_text_number'] = '+' . $data['conversation_text_number'];
            }

            $validator = Validator::make($data, [
                'conversation_call_number' => ['nullable', 'phone:AUTO'],
                'conversation_text_number' => ['nullable', 'phone:AUTO'],
            ], [
                'conversation_call_number.phone' => 'Invalid phone number',
                'conversation_text_number.phone' => 'Invalid phone number',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
            $conversation->update($data);
            $notifications = [
                'title' => 'Updated Conversation Details',
                'message' => 'Conversation Details updated successfully',
                'created_by' => $conversation->user_id,
                'status' => 0,
                'clear' => 'no',

            ];
            Notification::create($notifications);
            return response()->json(['message' => 'Conversation Details updated successfully', 'conversation' => $conversation], 200);
        } else {
            $data['user_id'] = $userId;
            if (!empty($data['conversation_call_number']) && !str_starts_with($data['conversation_call_number'], '+')) {
                $data['conversation_call_number'] = '+' . $data['conversation_call_number'];
            }

            if (!empty($data['conversation_text_number']) && !str_starts_with($data['conversation_text_number'], '+')) {
                $data['conversation_text_number'] = '+' . $data['conversation_text_number'];
            }

            $validator = Validator::make($data, [
                'conversation_call_number' => ['nullable', 'phone:AUTO'],
                'conversation_text_number' => ['nullable', 'phone:AUTO'],
            ], [
                'conversation_call_number.phone' => 'Invalid phone number',
                'conversation_text_number.phone' => 'Invalid phone number',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
            $conversation = BusinessProfile::create($data);
            $notifications = [
                'title' => 'Created Conversation Details',
                'message' => 'Conversation Details created successfully',
                'created_by' => $userId,
                'status' => 0,
                'clear' => 'no',

            ];
            Notification::create($notifications);
            return response()->json(['message' => 'Conversation Details created successfully', 'conversation' => $conversation], 200);
        }
    }
    public function Social(Request $request, $id = null)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($id != null) {
            $user = User::find($id);
            $social = SocialProfile::where('user_id', $id)->first();
        } else {
            $user = User::find($userId);
            $social = SocialProfile::where('user_id', $userId)->first();
        }
        $data = $request->all();
        if ($social) {
            $social->update($data);
            $notifications = [
                'title' => 'Updated Social Link',
                'message' => 'Social Link updated successfully',
                'created_by' => $userId,
                'status' => 0,
                'clear' => 'no',

            ];
            Notification::create($notifications);
            return response()->json(['message' => 'Social Link updated successfully', 'user' => $user, 'Social' => $social], 200);
        } else {

            if ($id != null) {
                $data['user_id'] = $id;
            } else {
                $data['user_id'] = $userId;
            }
            $social = SocialProfile::create($data);
            $notifications = [
                'title' => 'Added Social Link',
                'message' => 'Social Link added successfully',
                'created_by' => $userId,
                'status' => 0,
                'clear' => 'no',

            ];
            Notification::create($notifications);
            return response()->json(['message' => 'Social Link added successfully', 'user' => $user, 'Social' => $social], 200);
        }
    }

    public function UserDetails($id = null)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();


        if ($id != null) {

            $user = User::find($id);
            $businessProfile = BusinessProfile::where('user_id', $id)->get();
        } else {

            $user = User::find($userId);
            $businessProfile = BusinessProfile::where('user_id', $userId)->get();
        }
        $getPayment = PaymentDetail::where('user_id', $userId)->get();
        $getDeal = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
            ->leftJoin('business_profiles', 'business_profiles.user_id', '=', 'deals.user_id')
            ->leftJoin('favorit_deals', 'favorit_deals.deal_id', '=', 'deals.id')
            ->leftJoin('reviews', 'reviews.deal_id', '=', 'deals.id')
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
                'deals.pricing_model',
                'deals.flat_estimated_service_time',
                'deals.hourly_estimated_service_time',
                'deals.estimated_service_timing1',
                'deals.user_id',
                'business_profiles.business_name as user_name',
                'business_profiles.business_logo',
                DB::raw('COALESCE(AVG(reviews.rating), 0) as avg_rating'),
                DB::raw('COUNT(reviews.id) as total_reviews'),
                DB::raw('GROUP_CONCAT(DISTINCT favorit_deals.user_id ORDER BY favorit_deals.user_id ASC) as favorite_user_ids')
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
                'deals.pricing_model',
                'deals.flat_estimated_service_time',
                'deals.hourly_estimated_service_time',
                'deals.estimated_service_timing1',
                'deals.user_id',
                'business_profiles.business_name',
                'business_profiles.business_logo',
            )->where('deals.publish', 1)->where('deals.user_id', $userId)->orderBy('deals.id', 'desc')->get();
        $getDeal->transform(function ($deal) {
            $deal->favorite_user_ids = $deal->favorite_user_ids ? explode(',', $deal->favorite_user_ids) : [];
            return $deal;
        });
        if ($id != null) {
            $getSocial = SocialProfile::where('user_id', $id)->get();
        } else {
            $getSocial = SocialProfile::where('user_id', $userId)->get();
        }

        if ($id != null) {
            $getReviews = Review::where('provider_id', $userId)->get();
        } else {
            $getReviews = Review::where('provider_id', $userId)->get();
        }
        if ($getReviews->isNotEmpty()) {
            $provider_reviews = [];
            $provider_reviews['average'] = floor($getReviews->avg('rating'));
            $provider_reviews['total'] = $getReviews->count();
        } else {
            $provider_reviews = [];
            $provider_reviews['average'] = 0;
            $provider_reviews['total'] = 0;
        }

        $stars = Review::select(
            DB::raw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star'),
            DB::raw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star'),
            DB::raw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star'),
            DB::raw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star'),
            DB::raw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star')
        )
            ->where('provider_id', $userId)
            ->first();

        $detailReviews = Review::leftJoin('users', 'users.id', '=', 'reviews.user_id')
            ->leftJoin('deals', 'deals.id', '=', 'reviews.deal_id')
            ->select(
                'reviews.*',
                'users.name as user_name',
                'users.personal_image',
                'deals.service_title'
            )
            ->where('reviews.provider_id', $userId)
            ->get();



        if ($user) {

            return response()->json(['user' => $user, 'businessProfile' => $businessProfile, 'getPayment' => $getPayment, 'getDeal' => $getDeal, 'getSocial' => $getSocial, 'provider_reviews' => $provider_reviews, 'stars' => $stars, 'detailReviews' => $detailReviews], 200);
        }
    }

    public function SocialDelete(Request $request, $id = null)
    {
        $role = Auth::user()->role;
        $uid = Auth::user()->id;

        if ($role == 2 || $role == 0) {
            if ($role == 0 && $id == null) {
                return response()->json(['error' => "Admin is not allowed to update his profile"], 401);
            } else {
                if ($id != null) {
                    $social = SocialProfile::where('user_id', $id)->first();
                } else {
                    $social = SocialProfile::where('user_id', $uid)->first();
                }
                if ($social) {
                    $message = 'Old link is incorrect or already null';
                    if ($social->facebook != null && $request['facebook'] == $social->facebook) {
                        $social->update(['facebook' => null]);
                        $message = 'Social facebook has been removed successfully';
                    }
                    if ($social->twitter != null && $request['twitter'] == $social->twitter) {

                        $social->update(['twitter' => null]);
                        $message = 'Social twitter has been removed successfully';
                    }
                    if ($social->tiktok != null && $request['tiktok'] == $social->tiktok) {

                        $social->update(['tiktok' => null]);
                        $message = 'Social tiktok has been removed successfully';
                    }
                    if ($social->instagram != null && $request['instagram'] == $social->instagram) {

                        $social->update(['instagram' => null]);
                        $message = 'Social Instagram has been removed successfully';
                    }
                    if ($social->linkedin != null && $request['linkedin'] == $social->linkedin) {

                        $social->update(['linkedin' => null]);
                        $message = 'Social Linkdin has been removed successfully';
                    }
                    if ($social->youtube != null && $request['youtube'] == $social->youtube) {

                        $social->update(['youtube' => null]);
                        $message = 'Social Youtube has been removed successfully';
                    }
                    if ($social->google_business != null && $request['google_business'] == $social->google_business) {

                        $social->update(['google_business' => null]);
                        $message = 'Social Google Business has been removed successfully';
                    }
                    if ($social->alignable != null && $request['alignable'] == $social->alignable) {

                        $social->update(['alignable' => null]);
                        $message = 'Social alignable has been removed successfully';
                    }

                    return response()->json(['message' => $message, 'social' => $social], 200);
                } else {
                    return response()->json(['error' => "Invalid Profile User"], 403);
                }
            }
        } else {
            return response()->json(['message' => 'You are not authorized, This api is only for provider and admin'], 401);
        }
    }
    public function AddBusinessLocation(Request $request, $id = null)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        $data = $request->all();

        if ($id != null) {

            $businesslocation = BusinessProfile::where('user_id', $id)->first();
        } else {

            $businesslocation = BusinessProfile::where('user_id', $userId)->first();
        }
        if ($businesslocation) {

            if (isset($data['business_location'])) {
                $data['business_location'] = json_encode($data['business_location']);
            }

            if (isset($data['service_location'])) {
                $data['service_location'] = json_encode($data['service_location']);
            }

            if (isset($data['restrict_location'])) {
                $data['restrict_location'] = json_encode($data['restrict_location']);
            }

            $updatedbusinesslocation = $businesslocation->update($data);
            $notifications = [
                'title' => 'Update Service Area',
                'message' => 'Service Area updated successfully',
                'created_by' => $businesslocation->user_id,
                'status' => 0,
                'clear' => 'no',

            ];
            Notification::create($notifications);
            return response()->json(['message' => 'Service Area updated successfully', 'servicelocation' => $businesslocation], 200);
        } else {
            if (isset($data['business_location'])) {
                $data['business_location'] = json_encode($data['business_location']);
            }

            if (isset($data['service_location'])) {
                $data['service_location'] = json_encode($data['service_location']);
            }

            if (isset($data['restrict_location'])) {
                $data['restrict_location'] = json_encode($data['restrict_location']);
            }
            $data['user_id'] = $userId;
            $servicelocation = BusinessProfile::create($data);
            $notifications = [
                'title' => 'Created Service Area',
                'message' => 'Service Area created successfully',
                'created_by' => $userId,
                'status' => 0,
                'clear' => 'no',

            ];
            Notification::create($notifications);
            return response()->json(['message' => 'Service Area created successfully', 'servicelocation' => $servicelocation], 200);
        }
    }

    public function UpdateBusinessLocation(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $data = $request->all();
            $businesslocation = BusinessProfile::find($request->id);

            $businesslocation->update($data);

            return response()->json(['message' => 'Service Location updated successfully', 'servicelocation' => $businesslocation], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetBusiness($id)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $getBusiness = BusinessProfile::where('user_id', $id)->first();
            $getSocial = SocialProfile::where('user_id', $id)->first();

            return response()->json(['getBusiness' => $getBusiness, 'getSocial' => $getSocial], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function OrdersList(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $userId = Auth::id();

            $orders = Order::leftjoin('users', 'users.id', '=', 'orders.customer_id')->leftjoin('deals', 'deals.id', '=', 'orders.deal_id')->select('orders.*', 'users.personal_image', 'users.name', 'deals.service_title', 'users.email', 'users.phone', 'users.location', 'users.personal_image', 'deals.images')->where('orders.provider_id', $userId)
                ->get()->map(function ($order) {

                    $beforeImages = DB::table('delivery_images')
                        ->where('order_id', $order->id)
                        ->where('type', 'before')
                        ->pluck('before_images');


                    $afterImages = DB::table('delivery_images')
                        ->where('order_id', $order->id)
                        ->where('type', 'after')
                        ->pluck('after_images');
                    return [
                        'order' => $order,
                        'before_images' => $beforeImages,
                        'after_images' => $afterImages,

                    ];
                });


            if ($orders) {
                return response()->json(['message' => 'Orders List', 'orders' => $orders], 200);
            } else {
                return response()->json(['message' => 'No order available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function SettingPublish($id = null)
    {

        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($id != null) {
            $setting = BusinessProfile::where('user_id', $id)->first();
        } else {
            $setting = BusinessProfile::where('user_id', $userId)->first();
        }

        if ($setting) {
            $setting->update(['publish' => 1]);

            $notifications = [
                'title' => 'Setting Publish',
                'message' => 'Setting Publish successfully',
                'created_by' => $setting->user_id,
                'status' => 0,
                'clear' => 'no',

            ];
            Notification::create($notifications);
            return response()->json(['message' => 'Setting Publish successfully', 'setting' => $setting], 200);
        } else {
            return response()->json(['message' => 'No Setting found'], 401);
        }
    }

    public function GetDealsByCategory(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $getDeals = Deal::where('service_category', '=', $request->category)->where('user_id', $request->user_id)->get();

            return response()->json(['getDeals' => $getDeals], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function OrdeAfterImages(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $imageNames = [];

            $data = $request->all();
            $existingImage = DeliveryImage::where('order_id', $request->order_id)
                ->where('type', 'after')
                ->first();


            $existingImageArray = $existingImage ? json_decode($existingImage->after_images, true) : [];


            if ($request->hasFile('after_images')) {
                foreach ($request->file('after_images') as $beforeImage) {
                    $photo_name = time() . '-' . $beforeImage->getClientOriginalName();
                    $photo_destination = public_path('uploads');
                    $beforeImage->move($photo_destination, $photo_name);

                    $imageNames[] = $photo_name;
                }
            }


            $mergedImages = array_merge($existingImageArray, $imageNames);

            if ($existingImage) {

                $data['after_images'] = json_encode($mergedImages);
                $existingImage->update($data);
                return response()->json(['message' => 'Before Delivery Image update successfully', 'GetOrderAfterImages' => $mergedImages]);
            } else {

                DeliveryImage::create([
                    'order_id' => $request->order_id,
                    'type' => 'after',
                    'after_images' => json_encode($mergedImages),
                ]);

                return response()->json(['message' => 'Before Delivery Image created successfully', 'GetOrderAfterImages' => $mergedImages]);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function OrderBeforeImages(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $imageNames = [];

            $data = $request->all();
            $existingImage = DeliveryImage::where('order_id', $request->order_id)
                ->where('type', 'before')
                ->first();


            $existingImageArray = $existingImage ? json_decode($existingImage->before_images, true) : [];


            if ($request->hasFile('before_images')) {
                foreach ($request->file('before_images') as $beforeImage) {
                    $photo_name = time() . '-' . $beforeImage->getClientOriginalName();
                    $photo_destination = public_path('uploads');
                    $beforeImage->move($photo_destination, $photo_name);

                    $imageNames[] = $photo_name;
                }
            }


            $mergedImages = array_merge($existingImageArray, $imageNames);

            if ($existingImage) {

                $data['before_images'] = json_encode($mergedImages);
                $existingImage->update($data);
                return response()->json(['message' => 'Before Delivery Image update successfully', 'GetOrderBeforeImages' => $mergedImages]);
            } else {

                DeliveryImage::create([
                    'order_id' => $request->order_id,
                    'type' => 'before',
                    'before_images' => json_encode($mergedImages),
                ]);

                return response()->json(['message' => 'Before Delivery Image created successfully', 'GetOrderBeforeImages' => $mergedImages]);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function OrderConfirm(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {

            $data = $request->all();

            $data['status'] = 'delivered';

            $GetConfirm = Order::where('id', '=', $request->order_id)->first();
            if ($GetConfirm) {

                $BeforeDeliveryImage = $GetConfirm->update($data);

                return response()->json(['message' => 'Order Delivery successfully', 'BeforeDeliveryImage' => $GetConfirm]);
            } else {


                $BeforeDeliveryImage = DeliveryImage::create($data);

                return response()->json(['message' => 'Order Delivery successfully', 'BeforeDeliveryImage' => $BeforeDeliveryImage]);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function CreateOffer(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required',
                'provider_id' => 'required',
                'deal_id' => 'required',
                'total_amount' => 'required',
            ]);
            $data = $request->all();

            $data['status'] = 'new';
            $Offer = Order::create($data);

            return response()->json(['message' => 'Offer created successfully', 'Offer' => $Offer]);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function PaymentHistory(Request $request)
    {

        $role = Auth::user()->role;
        if ($role == 2) {

            $data = $request->all();

            $payment = PaymentHistory::create($data);

            return response()->json(['message' => 'Payment History created successfully', 'payment' => $payment]);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function GetProviderPaymentHistory()
    {

        $role = Auth::user()->role;
        if ($role == 2) {
            $GetHistory = PaymentHistory::all();
            $GetPayoutPayment = PaymentHistory::where('payment_type', '=', 'payout')->count();
            $GetReceivablePayment = PaymentHistory::where('payment_type', '=', 'receivable')->count();
            $GetPendingPayment = PaymentHistory::where('status', '=', 'pending')->count();


            return response()->json(['GetHistory' => $GetHistory, 'GetReceivablePayment' => $GetReceivablePayment, 'GetPendingPayment' => $GetPendingPayment, 'GetPayoutPayment' => $GetPayoutPayment]);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetOrderDetails($id)
    {

        $role = Auth::user()->role;
        if ($role == 2) {
            $GetOrderDetails = Deal::leftjoin('orders', 'orders.deal_id', '=', 'deals.id')
                ->leftjoin('users', 'users.id', '=', 'orders.customer_id')->select('users.*', 'orders.id as order_id', 'orders.status as order_status', 'deals.service_title', 'orders.scheduleDate', 'orders.total_amount', 'orders.notes', 'deals.images')
                ->where('orders.id', '=', $id)->first();
            $GetOrderBeforeImages = DeliveryImage::where('order_id', '=', $id)->where('type', 'before')->get();
            $GetOrderAfterImages = DeliveryImage::where('order_id', '=', $id)->where('type', 'after')->get();
            $GetOrderDeliver = DeliveryImage::where('order_id', '=', $id)->where('type', 'delivered')->get();


            return response()->json(['GetOrderDetails' => $GetOrderDetails, 'GetOrderBeforeImages' => $GetOrderBeforeImages, 'GetOrderAfterImages' => $GetOrderAfterImages, 'GetOrderDeliver' => $GetOrderDeliver]);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function FavoritService(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $user = User::find($request->user_id);
            if ($user) {
                $getFavorit = FavoritDeal::where('user_id', $request->user_id)->where('deal_id', $request->deal_id)->first();
                if ($getFavorit) {
                    FavoritDeal::where('user_id', $request->user_id)->where('deal_id', $request->deal_id)->delete();
                    $notification = [
                        'title' => 'Removed from favorite list',
                        'message' => 'favorit Service has been remove successfully',
                        'created_by' => $user->id,
                        'status' => 0,
                        'clear' => 'no',
                    ];
                    Notification::create($notification);
                    return response()->json(['message' => 'Removed from favorite list', 'favoritService' => $getFavorit], 200);
                } else {
                    $data = $request->all();
                    $favoritService = FavoritDeal::create($data);
                    $notification = [
                        'title' => 'Added Favorit Service',
                        'message' => 'Service has been favorit successfully',
                        'created_by' => $user->id,
                        'status' => 0,
                        'clear' => 'no',
                    ];
                    Notification::create($notification);
                    return response()->json(['message' => 'Added to favorite list', 'favoritService' => $favoritService], 200);
                }
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetFavoritService(Request $request)
    {
        $userId = Auth::id();
        $favoritService = FavoritDeal::where('user_id', $userId)->pluck('deal_id')->toArray();
        $deals = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
            ->leftJoin('business_profiles', 'business_profiles.user_id', '=', 'deals.user_id')
            ->leftJoin('reviews', 'reviews.deal_id', '=', 'deals.id')
            ->leftJoin('favorit_deals', 'favorit_deals.deal_id', '=', 'deals.id')
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
                DB::raw('COALESCE(AVG(reviews.rating), 0) as avg_rating'),
                DB::raw('COUNT(reviews.id) as total_reviews'),
                DB::raw('GROUP_CONCAT(DISTINCT favorit_deals.user_id ORDER BY favorit_deals.user_id ASC) as favorite_user_ids')
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
            )->whereIn('deals.id', $favoritService)->orderBy('deals.id', 'desc')->paginate($request->number_of_deals ?? 12);
        $totalDeals = $deals->total();

        $deals->transform(function ($deal) {
            $deal->favorite_user_ids = $deal->favorite_user_ids ? explode(',', $deal->favorite_user_ids) : [];
            return $deal;
        });

        return response()->json(['deals' => $deals, 'totalDeals' => $totalDeals], 200);
    }

    public function SearchDealLocation(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $deals = Deal::query();
            if ($request->service) {
                $deals = $deals->where('service_category', 'like', '%' . $request->service . '%');
            }

            if ($request->location) {
                $location = BusinessProfile::where('service_location', 'like', '%' . $request->location . '%')->pluck('user_id')->toArray();
                $deals = $deals->whereIn('user_id', $location);
            }
            $deals = $deals->get();
            return response()->json(['message' => 'No user found', 'services' => $deals], 401);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetInprogressOrder()
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 2) {

            $GetInprogressOrder = Order::leftJoin('deals', 'deals.id', '=', 'orders.deal_id')
                ->where('orders.status', 'in progress')
                ->where('orders.customer_id', $userId)
                ->select('orders.*', 'deals.service_title as deal_name')
                ->get();

            return response()->json(['GetInprogressOrder' => $GetInprogressOrder], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function GetLoginDetails()
    {
        $userId = Auth::id();

        $GetUser = User::find($userId);

        return response()->json(['GetUser' => $GetUser], 200);
    }
    public function CustomerSupport(Request $request)
    {

        $userId = Auth::id();
        $data = $request->all();

        $data['user_id'] = $userId;
        $data['status'] = 'pending';
        $CustomerSupport = Support::create($data);

        return response()->json(['CustomerSupport' => $CustomerSupport], 200);
    }

    public function GetSalesRep($role)
    {
        $userId = Auth::id();

        $GetSalesRep = User::where('role', $role)->where('id', "<>", $userId)->orderBy('id', 'desc')->get();
        return response()->json(['sales_reps' => $GetSalesRep]);
    }

    public function AssignSalesRep($id)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $assignSaleRep = User::find($id);
            if ($assignSaleRep && $assignSaleRep->role == 3) {
                $user = User::find(Auth::id());
                $user->update(['assign_sales_rep' => $assignSaleRep->id]);
                return response()->json(['message' => 'Sales Rep assigned successfully', 'assignSaleRep' => $user], 200);
            } else {
                return response()->json(['message' => 'invalid sale rep id'], 401);
            }

            return response()->json(['sales_reps' => $GetSalesRep]);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetInformationPrice()
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $GetPriceDetails = Price::first();
            if ($GetPriceDetails) {
                return response()->json(['GetPriceDetails' => $GetPriceDetails], 200);
            }
            return response()->json(['message' => 'price not available'], 401);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SearchHomeServices(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $deals = Deal::query();
            if ($request->service) {
                $deals = $deals->where('service_category', 'like', '%' . $request->service . '%')->where('user_id', Auth::id());
            }

            if ($request->location) {
                $location = BusinessProfile::where('user_id', Auth::id())->where('service_location', 'like', '%' . $request->location . '%')->pluck('user_id')->toArray();
                $deals = $deals->whereIn('user_id', $location);
            }
            $deals = $deals->pluck('id')->toArray();

            $searchDeal = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
                ->leftJoin('orders', 'orders.deal_id', '=', 'deals.id')
                ->leftJoin('reviews', 'reviews.order_id', '=', 'orders.id')
                ->orderBy('deals.id', 'desc')
                ->select('deals.*', 'users.name as user_name', 'users.personal_image', 'orders.id as order_id', 'reviews.rating as review_rating')
                ->where('deals.user_id', Auth::id())->where('deals.id', $deals)
                ->get();
            return response()->json(['message' => 'No user found', 'services' => $searchDeal], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function FilterHomeDeals(Request $request)
    {

        $service = $request->service;
        $budget = $request->budget;
        $reviews = $request->reviews;
        $estimate_time = $request->estimate_time;
        $location = $request->location;
        $distance = $request->distance;
        $business_name = $request->business_name;

        $deals = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
            ->leftJoin('business_profiles', 'business_profiles.user_id', '=', 'deals.user_id')
            ->leftJoin('reviews', 'reviews.deal_id', '=', 'deals.id')
            ->leftJoin('favorit_deals', 'favorit_deals.deal_id', '=', 'deals.id')
            ->orderBy('deals.id', 'desc')
            ->select(
                'deals.id',
                'deals.service_title',
                'deals.search_tags',
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
                DB::raw('COALESCE(AVG(reviews.rating), 0) as avg_rating'),
                DB::raw('COUNT(reviews.id) as total_reviews'),
                DB::raw('GROUP_CONCAT(DISTINCT favorit_deals.user_id ORDER BY favorit_deals.user_id ASC) as favorite_user_ids')
            )
            ->groupBy(
                'deals.id',
                'deals.service_title',
                'deals.search_tags',
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
                'users.name',
                'users.personal_image',
                'business_profiles.business_name',
                'business_profiles.business_logo',
            )->where('deals.publish', 1);

        if ($service) {
            $deals = $deals->where(function ($query) use ($service) {
                $query->where('deals.service_category', 'like', '%' . $service . '%')
                    ->orWhere('deals.service_title', 'like', '%' . $service . '%')
                    ->orWhere('deals.search_tags', 'like', '%' . $service . '%')
                    ->orWhere('deals.service_description', 'like', '%' . $service . '%')
                    ->orWhere('deals.commercial', 'like', '%' . $service . '%')
                    ->orWhere('deals.residential', 'like', '%' . $service . '%')
                    ->orWhere('business_profiles.business_name', 'like', '%' . $service . '%');
            });
        }
        if ($business_name) {
            $deals = $deals->where('business_profiles.business_name', 'like', '%' . $business_name . '%');
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
            $locationDistance = BusinessProfile::where(function ($query) use ($location) {
                $query->where('business_location', 'like', '%' . $location . '%')
                    ->orWhere('service_location', 'like', '%' . $location . '%');
            })->pluck('user_id')->toArray();
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

        return response()->json(['deals' => $deals, 'totalDeals' => $totalDeals, 'favoritDeals' => $favoritDeals], 200);
    }


    public function AddScheduleOrder(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {

            $GetOrder = Order::find($request->id);

            $GetOrder->update([

                'scheduleDate' => $request->scheduleDate,
                'status' => 'scheduled'
            ]);
            return response()->json(['message' => 'Order status updated  successfully.'], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function HomeProviderOrders(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $userId = Auth::id();
            $GetActiveOrders = Order::where('provider_id', $userId)->where('status', '!=', 'completed')->orderBy('id', 'desc')->limit(3)->get();
            if ($GetActiveOrders) {
                return response()->json(['message' => 'Orders List', 'activeOrders' => $GetActiveOrders], 200);
            } else {
                return response()->json(['message' => 'No order available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function RecentViewDeals(Request $request)
    {
        $role = Auth::user()->role;

        $userId = Auth::id();
        $recentDealId = RecentDealView::where('user_id', $userId)->orderBy('created_at', 'desc')->pluck('deal_id')->toArray();
        $recentDeal = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
            ->leftJoin('business_profiles', 'business_profiles.user_id', '=', 'deals.user_id')
            ->leftJoin('reviews', 'reviews.deal_id', '=', 'deals.id')
            ->leftJoin('favorit_deals', 'favorit_deals.deal_id', '=', 'deals.id')
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
                DB::raw('COALESCE(AVG(reviews.rating), 0) as avg_rating'),
                DB::raw('COUNT(reviews.id) as total_reviews'),
                DB::raw('GROUP_CONCAT(DISTINCT favorit_deals.user_id ORDER BY favorit_deals.user_id ASC) as favorite_user_ids')
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
            )->where('deals.publish', 1)->whereIn('deals.id', $recentDealId)->orderBy('deals.id', 'desc')->paginate($request->number_of_deals ?? 12);
        $totalViewDeals = $recentDeal->total();

        $recentDeal->transform(function ($deal) {
            $deal->favorite_user_ids = $deal->favorite_user_ids ? explode(',', $deal->favorite_user_ids) : [];
            return $deal;
        });
        if ($recentDeal) {
            return response()->json(['message' => 'Recent View Deals', 'recentDeal' => $recentDeal, 'totalViewDeals' => $totalViewDeals], 200);
        } else {
            return response()->json(['message' => 'No deal available'], 401);
        }
    }
    public function AddRecentDeal($id)
    {
        $userId = Auth::id();
        if ($userId) {
            $viewedDeal = RecentDealView::where('user_id', $userId)->where('deal_id', $id)->first();
            if ($viewedDeal) {
                $viewedDeal->update([
                    'created_at' => now()
                ]);
            } else {
                $viewedDeal = RecentDealView::create([
                    'user_id' => $userId,
                    'deal_id' => $id,
                ]);
            }
            return response()->json(['message' => 'Add in recent view successfully', 'recentDeal' => $viewedDeal], 200);
        } else {
            return response()->json(['message' => 'You need to login'], 200);
        }
    }
    public function GetGoogleReviews(Request $request)
    {
        $businessLink = $request->business_link;

        if (!$businessLink) {
            return response()->json(['error' => 'Google Business Profile link is required'], 400);
        }

        $placeId = $this->extractPlaceId($businessLink);

        if (!$placeId) {
            return response()->json(['error' => 'Invalid Google Business Profile link'], 400);
        }

        $apiKey = config('services.google_reviews.place_api');

        $reviewsUrl = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=name,rating,reviews&key={$apiKey}";

        $reviewsResponse = Http::get($reviewsUrl);
        $reviewsData = $reviewsResponse->json();

        if (!isset($reviewsData['result']['reviews'])) {
            return response()->json(['error' => 'No reviews found'], 404);
        }

        return response()->json($reviewsData['result']['reviews']);
    }

    private function extractPlaceId($url)
    {
        if (preg_match('/place_id:([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }
        return $this->resolveShortLink($url);
    }

    private function resolveShortLink($shortUrl)
    {
        $apiKey = config('services.google_reviews.place_api');

        $resolveUrl = "https://maps.googleapis.com/maps/api/place/details/json?key={$apiKey}&fields=place_id&place_id={$shortUrl}";

        $response = Http::get($resolveUrl);
        $data = $response->json();

        return $data['result']['place_id'] ?? null;
    }
}
