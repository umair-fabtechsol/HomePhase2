<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Deal;
use App\Models\DeliveryImage;
use App\Models\FavoritDeal;
use App\Models\User;
use App\Models\PaymentDetail;
use App\Models\Hour;
use App\Models\Order;
use App\Models\Price;
use App\Models\RecentDealView;
use App\Models\Offer;
use App\Models\DealUpload;
use App\Models\Notification;
use App\Models\Support;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\SocialProfile;
use App\Models\PaymentHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceProviderController extends Controller
{



    public function Deals(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $userId = Auth::id();
            $deals = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
                ->leftJoin('orders', 'orders.deal_id', '=', 'deals.id')
                ->leftJoin('reviews', 'reviews.order_id', '=', 'orders.id')
                ->orderBy('deals.id', 'desc')
                ->select('deals.*', 'users.name as user_name', 'users.personal_image', 'orders.id as order_id', 'reviews.rating as review_rating')
                ->where('deals.user_id', $userId)
                ->get();

            if ($deals) {
                return response()->json(['deals' => $deals], 200);
            } else {
                return response()->json(['message' => 'No deals found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function Deal($id)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 2) {
            $deal =Deal::
            where('id', $id)
            ->first();
            if ($deal) {
                $recentDeal = RecentDealView::create([
                    'user_id' => $userId,
                    'deal_id' => $id,
                ]);
                return response()->json(['deal' => $deal], 200);
            } else {
                return response()->json(['message' => 'No deal found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function DealPublish($deal_id)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 2) {
            $deal = Deal::find($deal_id);
            if ($deal) {
                $deal->update(['publish' => 1]);
                $deal = Deal::find($deal_id);
                $notifications = [
                    'title' => 'Deal Publish',
                    'message' => '"' . $deal->service_title . '" Deal Publish successfully',
                    'created_by' => $deal->user_id,
                    'status' => 0,
                    'clear' => 'no',

                ];
                Notification::create($notifications);
                return response()->json(['message' => 'Deal Publish successfully', 'deal' => $deal], 200);
            } else {
                return response()->json(['message' => 'No deals found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function BasicInfo(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $userId = Auth::id();
            $userRole = Auth::user()->role;
            $validator = Validator::make($request->all(), [
                'service_title' => 'required',
                'service_category' => 'required',
                'search_tags' => 'required',
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
            if ($userRole != 2) {
                return response()->json(['message' => 'Access denied. Only providers can perform this action.'], 400);
            }
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

    public function MediaUpload(Request $request)
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
            if ($userRole != 2) {
                return response()->json(['message' => 'Access denied. Only providers can perform this action.'], 400);
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
            $deal = Deal::find($request->deal_id);
            if ($deal) {


                $existingImages = json_decode($deal->images, true) ?? [];
                $existingVideos = json_decode($deal->videos, true) ?? [];
                $deal->update([
                    'images' => json_encode(array_merge($existingImages, $DealImages)),
                    'videos' => json_encode(array_merge($existingVideos, $DealVideos))
                ]);

                return response()->json([
                    'message' => 'Deal Images updated successfully',
                    'deal' => $deal,
                ], 200);
            } else {

                $deal = Deal::create($data);
                return response()->json([
                    'message' => 'Added new deal with Images successfully',
                    'deal' => $deal,
                ], 200);
            }
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
        $role = Auth::user()->role;
        if ($role == 2) {
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
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
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
        if ($role == 2) {
            $deal = Deal::find($id);
            $images = json_decode($deal->images, true);
            $videos = json_decode($deal->videos, true);

            if ($deal) {
                if (!empty($images)) {
                    foreach ($images as $image) {
                        $imagePath = public_path('uploads/' . $image);
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                }
                if (!empty($videos)) {
                    foreach ($videos as $video) {
                        $videoPath = public_path('uploads/' . $video);
                        if (file_exists($videoPath)) {
                            unlink($videoPath);
                        }
                    }
                }
                $deal->delete();

                return response()->json(['message' => 'Deal deleted successfully', 'deal' => $deal], 200);
            } else {
                return response()->json(['message' => 'No deal found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function MyDetails(Request $request)
    {
        $role = Auth::user()->role;
        
        if ($role == 2) {
            $user = User::find($request->id);
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
                $user->update($data);

                return response()->json(['message' => 'User Personal details updated successfully', 'user' => $user], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdatePassword(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $user = User::find($request->id);
            if ($user) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json(['message' => 'Current password is incorrect'], 200);
                }
                $user->password = Hash::make($request->password);
                $user->save();

                return response()->json(['message' => 'User Password Updated successfully', 'user' => $user], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function BusinessProfile(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $user = User::find($request->user_id);
            if ($user) {
                $data = $request->all();
                $businessProfile = BusinessProfile::where('user_id', $user->id)->first();
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
                        $user->update($data);
                    }
                    $businessProfile->update($data);
                    $notifications = [
                        'title' => 'Update User Business Profile',
                        'message' => 'User Business Profile Updated successfully',
                        'created_by' => $businessProfile->user_id,
                        'status' => 0,
                        'clear' => 'no',

                    ];
                    Notification::create($notifications);

                    return response()->json(['message' => 'User Business Profile Updated successfully', 'user' => $user, 'BusinessProfile' => $businessProfile], 200);
                } else {
                    if ($request->hasFile('business_logo')) {
                        $photo1 = $request->file('business_logo');
                        $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                        $photo_destination = public_path('uploads');
                        $photo1->move($photo_destination, $photo_name1);
                        $data['business_logo'] = $photo_name1;
                    }
                    $businessProfile = BusinessProfile::create($data);

                    $notifications = [
                        'title' => 'Created User Business Profile',
                        'message' => 'User Business Profile created successfully',
                        'created_by' => $request->user_id,
                        'status' => 0,
                        'clear' => 'no',

                    ];
                    Notification::create($notifications);
                }

                return response()->json(['message' => 'User Business Profile created successfully', 'user' => $user, 'BusinessProfile' => $businessProfile], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
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

    public function AdditionalPhotos(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $user = User::find($request->user_id);
            if ($user) {
                $data = $request->all();
                $businessProfile = BusinessProfile::where('user_id', $user->id)->first();
                if ($businessProfile) {
                    if ($request->hasFile('about_video')) {
                        $imagePath = public_path('uploads/' . $businessProfile->about_video);
                        if (!empty($businessProfile->about_video) && file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                        $photo1 = $request->file('about_video');
                        $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                        $photo_destination = public_path('uploads');
                        $photo1->move($photo_destination, $photo_name1);
                        $data['about_video'] = $photo_name1;
                        $user->update($data);
                    }
                    if ($request->hasFile('technician_photo')) {
                        $imagePath = public_path('uploads/' . $businessProfile->technician_photo);
                        if (!empty($businessProfile->technician_photo) && file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                        $photo1 = $request->file('technician_photo');
                        $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                        $photo_destination = public_path('uploads');
                        $photo1->move($photo_destination, $photo_name1);
                        $data['technician_photo'] = $photo_name1;
                        $user->update($data);
                    }
                    if ($request->hasFile('vehicle_photo')) {
                        $imagePath = public_path('uploads/' . $businessProfile->vehicle_photo);
                        if (!empty($businessProfile->vehicle_photo) && file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                        $photo1 = $request->file('vehicle_photo');
                        $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                        $photo_destination = public_path('uploads');
                        $photo1->move($photo_destination, $photo_name1);
                        $data['vehicle_photo'] = $photo_name1;
                        $user->update($data);
                    }
                    if ($request->hasFile('facility_photo')) {
                        $imagePath = public_path('uploads/' . $businessProfile->facility_photo);
                        if (!empty($businessProfile->facility_photo) && file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                        $photo1 = $request->file('facility_photo');
                        $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                        $photo_destination = public_path('uploads');
                        $photo1->move($photo_destination, $photo_name1);
                        $data['facility_photo'] = $photo_name1;
                        $user->update($data);
                    }
                    if ($request->hasFile('project_photo')) {
                        $imagePath = public_path('uploads/' . $businessProfile->project_photo);
                        if (!empty($businessProfile->project_photo) && file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                        $photo1 = $request->file('project_photo');
                        $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                        $photo_destination = public_path('uploads');
                        $photo1->move($photo_destination, $photo_name1);
                        $data['project_photo'] = $photo_name1;
                        $user->update($data);
                    }
                    $businessProfile->update($data);
                    $notifications = [
                        'title' => 'Update User Business Additional Info',
                        'message' => 'User Business Additional Info Updated successfully',
                        'created_by' => $businessProfile->user_id,
                        'status' => 0,
                        'clear' => 'no',

                    ];
                    Notification::create($notifications);
                    return response()->json(['message' => 'User Business Additional Info Updated successfully', 'user' => $user, 'BusinessProfile' => $businessProfile], 200);
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
                    $businessProfile = BusinessProfile::create($data);
                    $notifications = [
                        'title' => 'Created User Business Additional Info',
                        'message' => 'User Business Additional Info created successfully',
                        'created_by' => $request->user_id,
                        'status' => 0,
                        'clear' => 'no',

                    ];
                    Notification::create($notifications);
                }

                return response()->json(['message' => 'User Business Additional Info created successfully', 'user' => $user, 'BusinessProfile' => $businessProfile], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function AddCertificateHours(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $data = $request->all();
            $updateCertificateHours = BusinessProfile::where('user_id', $request->user_id)->first();
            if ($updateCertificateHours) {

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

                $notifications = [
                    'title' => 'Update Business CertificateHour',
                    'message' => 'Business CertificateHour updated successfully',
                    'created_by' => $updateCertificateHours->user_id,
                    'status' => 0,
                    'clear' => 'no',

                ];
                Notification::create($notifications);
                return response()->json(['message' => 'Business CertificateHour updated successfully', 'updateCertificateHours' => $updateCertificateHours], 200);
            } else {


                if ($request->hasFile('insurance_certificate')) {
                    $photo1 = $request->file('insurance_certificate');
                    $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                    $photo_destination = public_path('uploads');
                    $photo1->move($photo_destination, $photo_name1);
                    $data['insurance_certificate'] = $photo_name1;
                }
                if ($request->hasFile('license_certificate')) {
                    $photo2 = $request->file('license_certificate');
                    $photo_name2 = time() . '-' . $photo2->getClientOriginalName();
                    $photo_destination = public_path('uploads');
                    $photo2->move($photo_destination, $photo_name2);
                    $data['license_certificate'] = $photo_name2;
                }
                if ($request->hasFile('award_certificate')) {
                    $photo3 = $request->file('award_certificate');
                    $photo_name3 = time() . '-' . $photo3->getClientOriginalName();
                    $photo_destination = public_path('uploads');
                    $photo3->move($photo_destination, $photo_name3);
                    $data['award_certificate'] = $photo_name3;
                }



                $certificate = BusinessProfile::create($data);
                $notifications = [
                    'title' => 'Business CertificateHour ',
                    'message' => 'Business CertificateHour created successfully',
                    'created_by' => $request->user_id,
                    'status' => 0,
                    'clear' => 'no',

                ];
                Notification::create($notifications);

                return response()->json(['message' => 'Business CertificateHour created successfully', 'certificate' => $certificate], 200);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
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

    public function AddConversation(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {

            $data = $request->all();
            $conversation = BusinessProfile::where('user_id', $request->id)->first();
            if ($conversation) {


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

                $conversation = BusinessProfile::create($data);
                $notifications = [
                    'title' => 'Created Conversation Details',
                    'message' => 'Conversation Details created successfully',
                    'created_by' => $request->id,
                    'status' => 0,
                    'clear' => 'no',

                ];
                Notification::create($notifications);
                return response()->json(['message' => 'Conversation Details created successfully', 'conversation' => $conversation], 200);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function Social(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $user = User::find($request->user_id);
            if (!$user) {
                return response()->json(['message' => 'No user found'], 404);
            }

            $social = SocialProfile::where('user_id', $user->id)->first();
            $data = $request->all();


            if ($request->has('facebook') && !preg_match('/^(https?:\/\/)?(www\.)?facebook\.com\/[a-zA-Z0-9(\.\?)?]/', $request->facebook)) {
                return response()->json(['message' => 'Invalid Facebook URL'], 400);
            }


            if ($request->has('twitter') && !preg_match('/^(https?:\/\/)?(www\.)?twitter\.com\/[a-zA-Z0-9_]+$/', $request->twitter)) {
                return response()->json(['message' => 'Invalid Twitter URL'], 400);
            }


            if ($request->has('instagram') && !preg_match('/^(https?:\/\/)?(www\.)?instagram\.com\/[a-zA-Z0-9_]+\/?$/', $request->instagram)) {
                return response()->json(['message' => 'Invalid Instagram URL'], 400);
            }


            if ($request->has('linkedin') && !preg_match('/^(https?:\/\/)?(www\.)?linkedin\.com\/in\/[a-zA-Z0-9_-]+\/?$/', $request->linkedin)) {
                return response()->json(['message' => 'Invalid LinkedIn URL'], 400);
            }


            if ($request->has('youtube') && !preg_match('/^(https?:\/\/)?(www\.)?youtube\.com\/(channel|c|user)\/[a-zA-Z0-9_-]+$/', $request->youtube)) {
                return response()->json(['message' => 'Invalid YouTube URL'], 400);
            }


            if ($request->has('google_business') && !preg_match('/^(https?:\/\/)?(www\.)?g\.page\/[a-zA-Z0-9_-]+$/', $request->google_business)) {
                return response()->json(['message' => 'Invalid Google Business URL'], 400);
            }

            if ($social) {
                $social->update($data);
                $certificate = BusinessProfile::create($data);
                $notifications = [
                    'title' => 'Updated Social Link',
                    'message' => 'Social Link updated successfully',
                    'created_by' => $social->user_id,
                    'status' => 0,
                    'clear' => 'no',

                ];
                Notification::create($notifications);
                return response()->json(['message' => 'Social Link updated successfully', 'user' => $user, 'Social' => $social], 200);
            } else {
                $social = SocialProfile::create($data);
                $notifications = [
                    'title' => 'Added Social Link',
                    'message' => 'Social Link added successfully',
                    'created_by' => $request->user_id,
                    'status' => 0,
                    'clear' => 'no',

                ];
                Notification::create($notifications);
                return response()->json(['message' => 'Social Link added successfully', 'user' => $user, 'Social' => $social], 200);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UserDetails()
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 2) {
            $user = User::find($userId);
            $businessProfile = BusinessProfile::where('user_id', $userId)->get();

            $getPayment = PaymentDetail::where('user_id', $userId)->get();
            $getDeal = Deal::where('user_id', $userId)->get();
            $getSocial = SocialProfile::where('user_id', $userId)->get();
            if ($user) {

                return response()->json(['user' => $user, 'businessProfile' => $businessProfile, 'getPayment' => $getPayment, 'getDeal' => $getDeal, 'getSocial' => $getSocial], 200);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SocialDelete(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $social = SocialProfile::where('user_id', $request->id)->first();

            if ($request['facebook'] == $social->facebook) {

                $social->update(['facebook' => null]);
            }
            if ($request['twitter'] == $social->twitter) {

                $social->update(['twitter' => null]);
            }
            if ($request['instagram'] == $social->instagram) {

                $social->update(['instagram' => null]);
            }
            if ($request['linkedin'] == $social->linkedin) {

                $social->update(['linkedin' => null]);
            }
            if ($request['youtube'] == $social->youtube) {

                $social->update(['youtube' => null]);
            }
            if ($request['google_business'] == $social->google_business) {

                $social->update(['google_business' => null]);
            }
            if ($social && is_null($social->facebook) && is_null($social->twitter) && is_null($social->instagram) && is_null($social->linkedin) && is_null($social->youtube) && is_null($social->google_business)) {
                $social->delete();
            }
            $notifications = [
                'title' => 'Delete Social Link',
                'message' => 'Socials Link deleted successfully',
                'created_by' => $social->user_id,
                'status' => 0,
                'clear' => 'no',

            ];
            Notification::create($notifications);
            return response()->json(['social' => $social], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function AddBusinessLocation(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $data = $request->all();
            $businesslocation = BusinessProfile::where('user_id', $request->user_id)->first();
            if ($businesslocation) {
                if ($request->service_location_type == 'location') {


                    $data['location_miles'] = null;
                }
                if ($request->service_location_type == 'radius') {
                    $data['business_location'] = null;
                    $data['restrict_location'] = null;
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

                $servicelocation = BusinessProfile::create($data);
                $notifications = [
                    'title' => 'Created Service Area',
                    'message' => 'Service Area created successfully',
                    'created_by' => $request->user_id,
                    'status' => 0,
                    'clear' => 'no',

                ];
                Notification::create($notifications);
                return response()->json(['message' => 'Service Area created successfully', 'servicelocation' => $servicelocation], 200);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
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
       
            $orders = Order::leftjoin('users', 'users.id', '=', 'orders.customer_id')->leftjoin('deals', 'deals.id', '=', 'orders.deal_id')->select('orders.*', 'users.personal_image', 'users.name', 'deals.service_title','users.email','users.phone','users.location','users.personal_image','deals.images')->where('orders.provider_id', $userId)
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
    public function SettingPublish($id)
    {

        $role = Auth::user()->role;
        if ($role == 2) {
            $setting = BusinessProfile::where('user_id', $id)->first();
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
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
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

            $GetConfirm=Order::where('id','=',$request->order_id)->first();
            if($GetConfirm){

                $BeforeDeliveryImage = $GetConfirm->update($data);

                return response()->json(['message' => 'Order Delivery successfully', 'BeforeDeliveryImage' => $GetConfirm]);
                
            }else{
            
            
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

            $data['status']= 'new';
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
            $GetOrderDetails=Deal::leftjoin('orders','orders.deal_id','=','deals.id')
            ->leftjoin('users','users.id','=','orders.customer_id')->select('users.*','orders.id as order_id','orders.status as order_status','deals.service_title','orders.scheduleDate','orders.total_amount','orders.notes','deals.images')
            ->where('orders.id','=',$id)->first();
        $GetOrderBeforeImages=DeliveryImage::where('order_id','=',$id)->where('type', 'before')->get();
       $GetOrderAfterImages=DeliveryImage::where('order_id','=',$id)->where('type', 'after')->get();
       $GetOrderDeliver=DeliveryImage::where('order_id','=',$id)->where('type', 'delivered')->get();

       
         return response()->json(['GetOrderDetails' => $GetOrderDetails ,'GetOrderBeforeImages' => $GetOrderBeforeImages,'GetOrderAfterImages'=> $GetOrderAfterImages,'GetOrderDeliver' => $GetOrderDeliver]);
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
                        'title' => 'Remove Favorit Service',
                        'message' => 'favorit Service has been remove successfully',
                        'created_by' => $user->id,
                        'status' => 0,
                        'clear' => 'no',
                    ];
                    Notification::create($notification);
                    return response()->json(['message' => 'Remove Favorit Service', 'favoritService' => $getFavorit], 200);
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
                    return response()->json(['message' => 'Added Favorit Service', 'favoritService' => $favoritService], 200);
                }
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetFavoritService(){
        $userId = Auth::id();
        $deals=Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
                ->leftJoin('orders', 'orders.deal_id', '=', 'deals.id')
                ->leftJoin('reviews', 'reviews.order_id', '=', 'orders.id')
                ->leftJoin('favorit_deals', 'favorit_deals.deal_id', '=', 'deals.id')
                ->orderBy('deals.id', 'desc')
                ->select('deals.*', 'users.name as user_name', 'users.personal_image', 'orders.id as order_id', 'reviews.rating as review_rating')
                ->where('favorit_deals.user_id', $userId)
                ->get();
        
                return response()->json(['deals' => $deals], 200);
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
    public function GetLoginDetails(){
        $userId = Auth::id();

        $GetUser=User::find($userId);

        return response()->json(['GetUser' => $GetUser], 200);
        
    }
    public function CustomerSupport(Request $request){
 
        $userId = Auth::id();
        $data = $request->all();

        $data['user_id'] = $userId;
        $data['status'] = 'pending';     
        $CustomerSupport = Support::create($data);
        
        return response()->json(['CustomerSupport' => $CustomerSupport], 200);

        
    }

    public function GetSalesRep()
    {
        $role = Auth::user()->role;
        if ($role == 2) {
            $GetSalesRep = User::where('role', 3)->orderBy('id', 'desc')->get();

            return response()->json(['sales_reps' => $GetSalesRep]);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
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

    // for home section service provider 
    public function SearchHomeServices(Request $request){
        $role = Auth::user()->role;
        if ($role == 2) {
            $deals = Deal::query();
            if ($request->service) {
                $deals = $deals->where('service_category', 'like', '%' . $request->service . '%')->where('user_id', Auth::id());
            }

            if ($request->location) {
                $location = BusinessProfile::where('user_id',Auth::id())->where('service_location', 'like', '%' . $request->location . '%')->pluck('user_id')->toArray();
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
    // for home page for provider and customer 
    public function FilterHomeDeals(Request $request)
    {
        $role = Auth::user()->role;
    
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

    public function AddScheduleOrder(Request $request){
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

    public function HomeProviderOrders(Request $request){
        $role = Auth::user()->role;
        $userId = Auth::id();
        $GetActiveOrders = Order::where('status', '!=', 'completed');
        if($role == 1){
            $GetActiveOrders = $GetActiveOrders->where('customer_id', $userId);
        }
        if($role == 2){
            $GetActiveOrders = $GetActiveOrders->where('provider_id', $userId);
        }
        $GetActiveOrders = $GetActiveOrders->orderBy('id','desc')->limit(3)->get();
        if($GetActiveOrders){
            return response()->json(['message' => 'Orders List', 'activeOrders' => $GetActiveOrders], 200);
        }else{
            return response()->json(['message' => 'No order available'], 401);
        }
        
    }

    // for home page for provider and customer 
    public function RecentViewDeals(Request $request){
        $role = Auth::user()->role;

        $userId = Auth::id();
        $recentDealId = RecentDealView::where('user_id', $userId)->pluck('deal_id')->toArray();
        $recentDeal = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
            ->leftJoin('orders', 'orders.deal_id', '=', 'deals.id')
            ->leftJoin('reviews', 'reviews.order_id', '=', 'orders.id')
            ->orderBy('deals.id', 'desc')
            ->select('deals.*', 'users.name as user_name', 'users.personal_image', 'orders.id as order_id', 'reviews.rating as review_rating')
            ->where('deals.user_id', Auth::id())->where('deals.id', $recentDealId)->orderBy('deals.id', 'desc')->limit(8)->get();
        if($recentDeal){
            return response()->json(['message' => 'Orders List', 'recentDeal' => $recentDeal], 200);
        }else{
            return response()->json(['message' => 'No deal available'], 401);
        }
    }    
    
}