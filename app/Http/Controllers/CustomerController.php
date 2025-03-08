<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Deal;
use App\Models\DeliveryImage;
use App\Models\FavoritDeal;
use App\Models\Notification;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentDetail;
use App\Models\Review;
use App\Models\RecentDealView;
use App\Models\User;
use App\Models\PaymentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\SocialProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class CustomerController extends Controller
{
    public function MyDetail(Request $request)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 1) {
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
                $notification = [
                    'title' => 'Profile Updated',
                    'message' => 'Profile has been updated successfully',
                    'created_by' => $userId,
                    'status' => 0,
                    'clear' => 'no',
                ];
                Notification::create($notification);
                return response()->json(['message' => 'User Personal details updated successfully', 'user' => $user], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function NewPassword(Request $request)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 1) {
            $user = User::find($request->id);
            if ($user) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json(['message' => 'Current password is incorrect'], 422);
                }
                $user->password = Hash::make($request->password);
                $user->save();
                $notification = [
                    'title' => 'Password Updated',
                    'message' => 'Password has been updated successfully',
                    'created_by' => $userId,
                    'status' => 0,
                    'clear' => 'no',
                ];
                Notification::create($notification);
                return response()->json(['message' => 'User Password Updated successfully', 'user' => $user], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function AddPaymentMethod(Request $request)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 1) {
            $user = User::find($request->id);
            if ($user) {
                $data = $request->all();
                $paymentMethod = PaymentDetail::create($data);
                $notification = [
                    'title' => 'Payment Method Added',
                    'message' => 'New Payment Method has been Added successfully',
                    'created_by' => $userId,
                    'status' => 0,
                    'clear' => 'no',
                ];
                Notification::create($notification);
                return response()->json(['message' => 'Added New Payment Method successfully', 'user' => $user, 'Payment Method' => $paymentMethod], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function DeletePaymentMethod($id)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $paymentMethod = PaymentMethod::find($id);
            if ($paymentMethod) {
                $paymentMethod->delete();
                $notification = [
                    'title' => 'Payment Method Deleted',
                    'message' => 'Payment Method has been deleted successfully',
                    'created_by' => $paymentMethod->user_id,
                    'status' => 0,
                    'clear' => 'no',
                ];
                Notification::create($notification);
                return response()->json(['message' => 'Payment Method deleted successfully', 'PaymentMethod' => $paymentMethod], 200);
            } else {
                return response()->json(['message' => 'No Payment Method found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdatePaymentMethod(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $paymentMethod = PaymentMethod::find($request->id);
            if ($paymentMethod) {
                $data = $request->all();
                $paymentMethod->update($data);
                $notification = [
                    'title' => 'Payment Method Updated',
                    'message' => 'Payment Method has been updated successfully',
                    'created_by' => $paymentMethod->user_id,
                    'status' => 0,
                    'clear' => 'no',
                ];
                Notification::create($notification);
                return response()->json(['message' => 'Payment Method Updated successfully', 'PaymentMethod' => $paymentMethod], 200);
            } else {
                return response()->json(['message' => 'No Payment Method found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function ListDeals(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            // $deals = Deal::orderBy('id', 'desc')->get();
            $deals = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
                ->leftJoin('orders', 'orders.deal_id', '=', 'deals.id')
                ->leftJoin('reviews', 'reviews.order_id', '=', 'orders.id')
                ->orderBy('deals.id', 'desc')
                ->select('deals.*', 'users.name as user_name', 'users.personal_image', 'orders.id as order_id', 'reviews.rating as review_rating')
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
    // for home page 
    public function FilterHomeService(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
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
                    $deals = $deals->where('deals.service_category', $service);
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
                    $locationDistance = BusinessProfile::where('service_location', '<=', $location)->pluck('user_id')->toArray();
                    $deals = $deals->whereIn('deals.user_id', $locationDistance);
                }

                $deals = $deals->get();
            if ($deals) {
                return response()->json(['deals' => $deals], 200);
            } else {
                return response()->json(['message' => 'No deals found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SearchHomeDeals(Request $request){
        $role = Auth::user()->role;
        if ($role == 1) {
            $deals = Deal::query();
            if ($request->service) {
                $deals = $deals->where('service_category', 'like', '%' . $request->service . '%');
            }

            if ($request->location) {
                $location = BusinessProfile::where('service_location', 'like', '%' . $request->location . '%')->pluck('user_id')->toArray();
                $deals = $deals->whereIn('user_id', $location);
            }
            $deals = $deals->pluck('id')->toArray();

            $searchDeal = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
                ->leftJoin('orders', 'orders.deal_id', '=', 'deals.id')
                ->leftJoin('reviews', 'reviews.order_id', '=', 'orders.id')
                ->orderBy('deals.id', 'desc')
                ->select('deals.*', 'users.name as user_name', 'users.personal_image', 'orders.id as order_id', 'reviews.rating as review_rating')
                ->where('deals.id', $deals)
                ->get();
            return response()->json(['message' => 'No user found', 'services' => $searchDeal], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SingleDeal($id)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 1) {
            // $deal = Deal::where('id', $id)->get();
            $deal = Deal::leftJoin('users', 'users.id', '=', 'deals.user_id')
                ->leftJoin('orders', 'orders.deal_id', '=', 'deals.id')
                ->leftJoin('reviews', 'reviews.order_id', '=', 'orders.id')
                ->where('deals.id', $id)
                ->orderBy('deals.id', 'desc')
                ->select('deals.*', 'users.name as user_name', 'users.personal_image', 'orders.id as order_id', 'reviews.rating as review_rating')
                ->get();
            
            $fetchDeal = Deal::find($id); 
            $businessProfile = BusinessProfile::where('user_id', $fetchDeal->user_id)->first();
            if ($deal) {
                $viewedDeal = RecentDealView::where('user_id', $userId)->where('deal_id', $id)->first();
                if($viewedDeal){
                    $viewedDeal->update([
                        'created_at' => now()
                    ]);
                } else{
                    $recentDeal = RecentDealView::create([
                        'user_id' => $userId,
                        'deal_id' => $id,
                    ]);
                }
                return response()->json(['deal' => $deal, 'businessProfile' => $businessProfile], 200);
            } else {
                return response()->json(['message' => 'No deal found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function AddSocial(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $user = User::find($request->user_id);
            if ($user) {
                $social = SocialProfile::where('user_id', $user->id)->first();
                $data = $request->all();
                if ($social) {
                    $notification = [
                        'title' => 'Social Profile Updated',
                        'message' => 'Social Profile has been updated successfully',
                        'created_by' => $social->user_id,
                        'status' => 0,
                        'clear' => 'no',
                    ];
                    Notification::create($notification);
                    $social->update($data);
                    return response()->json(['message' => 'Social Added successfully', 'user' => $user, 'Social' => $social], 200);
                } else {
                    $social = SocialProfile::create($data);
                    $notification = [
                        'title' => 'Social Profile Created',
                        'message' => 'Social Profile has been created successfully',
                        'created_by' => $social->user_id,
                        'status' => 0,
                        'clear' => 'no',
                    ];
                    Notification::create($notification);
                }
                return response()->json(['message' => 'Added Social successfully', 'user' => $user, 'Social' => $social], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function DeleteSocial(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {

            $social = SocialProfile::find($request->id);

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
            $notification = [
                'title' => 'Delete Social Link',
                'message' => 'Social link has been deleted successfully',
                'created_by' => $social->user_id,
                'status' => 0,
                'clear' => 'no',
            ];
            Notification::create($notification);
            return response()->json(['social' => $social], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function DealProvider($user_id)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $user = User::find($user_id);
            $deals = Deal::where('user_id', $user_id)->get();
            $business = BusinessProfile::where('user_id', $user_id)->first();
            return response()->json(['message' => 'Social Added successfully', 'user' => $user, 'deals' => $deals, 'business' => $business], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function DetailUser($id)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $user = User::find($id);

            $getPayment = PaymentDetail::where('user_id', $id)->get();
            $getSocial = SocialProfile::where('user_id', $id)->get();
            if ($user) {

                return response()->json(['user' => $user, 'getPayment' => $getPayment], 200);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function AddOrder(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $user = User::find($request->customer_id);
            if ($user) {
                $data = $request->all();
                $provider = Deal::find($request->deal_id);
                $data['provider_id'] = $provider->user_id; 
                $order = Order::create($data);
                $notification = [
                    'title' => 'Added New Order',
                    'message' => 'New Order has been added successfully',
                    'created_by' => $order->customer_id,
                    'status' => 0,
                    'clear' => 'no',
                ];
                Notification::create($notification);
                return response()->json(['message' => 'Added Order successfully', 'user' => $user, 'order' => $order], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdateOrder(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $order = Order::find($request->id);
            if ($order) {
                $data = $request->all();
                $order->update($data);
                $notification = [
                    'title' => 'Added New Order',
                    'message' => 'New Order has been added successfully',
                    'created_by' => $order->customer_id,
                    'status' => 0,
                    'clear' => 'no',
                ];
                Notification::create($notification);
                return response()->json(['message' => 'Updated Order successfully', 'order' => $order], 200);
            } else {
                return response()->json(['message' => 'No order found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function Orders(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $userId = Auth::id();
            $orders = Order::leftjoin('users', 'users.id', '=', 'orders.customer_id')->leftjoin('deals', 'deals.id', '=', 'orders.deal_id')->select('orders.*', 'users.personal_image', 'users.name', 'deals.service_title','users.email','users.phone','users.location','users.personal_image','deals.images')->where('orders.provider_id',3)
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

    public function Order($id)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $order =  $orders = Order::leftjoin('users', 'users.id', '=', 'orders.customer_id')->leftjoin('deals', 'deals.id', '=', 'orders.deal_id')->select('orders.*', 'users.personal_image', 'users.name', 'deals.service_title','users.email','users.phone','users.location','users.personal_image','deals.images')->where('orders.provider_id', 3)
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
    
           
            
    
             return response()->json(['order' => $order ]);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UploadReview(Request $request)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 1) {
            $user = User::find($userId);
            if ($user) {
                $order = Order::find($request->order_id);
                $dealId = $order->deal_id;
                $deal = Deal::find($dealId);
                $providerId = $deal->user_id;
                $data = $request->all();
                $data['provider_id'] = $providerId;
                $data['user_id'] = $userId;
                $data['deal_id'] = $dealId;
                $review = Review::create($data);
                $notification = [
                    'title' => 'Added Review',
                    'message' => 'A new review has been added successfully',
                    'created_by' => $userId,
                    'status' => 0,
                    'clear' => 'no',
                ];
                Notification::create($notification);
                return response()->json(['message' => 'Added Review successfully', 'user' => $user, 'review' => $review], 200);
            } else {
                return response()->json(['message' => 'No user login'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdateReview(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $review = Review::find($request->id);
            if ($review) {
                $data = $request->all();
                $review->update($data);
                $notification = [
                    'title' => 'Review Update',
                    'message' => 'Review has been updated successfully',
                    'created_by' => $review->user_id,
                    'status' => 0,
                    'clear' => 'no',
                ];
                Notification::create($notification);
                return response()->json(['message' => 'Review updated successfully', 'review' => $review], 200);
            } else {
                return response()->json(['message' => 'No review found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function DeleteReview($id)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $review = Review::find($id);
            if ($review) {
                $review->delete();
                $notification = [
                    'title' => 'Review Delete',
                    'message' => 'Review has been deleted successfully',
                    'created_by' => $review->user_id,
                    'status' => 0,
                    'clear' => 'no',
                ];
                Notification::create($notification);
                return response()->json(['message' => 'Review delete successfully', 'review' => $review], 200);
            } else {
                return response()->json(['message' => 'No review found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function FilterService(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $services = Deal::where('service_category', $request->service)->get();
            if ($services) {
                return response()->json(['message' => 'Services List', 'services' => $services], 200);
            } else {
                return response()->json(['message' => 'No service available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function AskForRevison(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $order = Order::find($request->order_id);
            if ($order) {
                $data = $request->all();
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $image) {
                        $photo1 = $image;
                        $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                        $photo_destination = public_path('uploads');
                        $photo1->move($photo_destination, $photo_name1);
                        $images[] = $photo_name1;
                    }
                }
                
                $data['revision_images'] =  json_encode($images);
                $data['type'] =  'revision';
                $afterImages = DeliveryImage::create($data);

                if($order->status == 'delivered'){
                    
                    $order->update([
                    'status' => 'in_revision'
                    ]);
                }
                $notification = [
                    'title' => 'Revision Request',
                    'message' => 'Revision request has been added successfully',
                    'created_by' => $order->customer_id,
                    'status' => 0,
                    'clear' => 'no',
                ];
                Notification::create($notification);
                return response()->json(['message' => 'Added revision successfully', 'afterImages' => $afterImages], 200);
            } else {
                return response()->json(['message' => 'No order found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetPaymentHistory()
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $paymentHistory = PaymentHistory::orderBy('id', 'desc')->get();
            if ($paymentHistory) {
                $totalPayouts = PaymentHistory::where('payment_type', 'payout')->sum('amount');
                $totalReceiveable = PaymentHistory::where('payment_type', 'receivable')->sum('amount');
                $pendingPayments = PaymentHistory::where('status', 'pending')->sum('amount');
                return response()->json(['paymentHistory' => $paymentHistory, 'totalPayouts' => $totalPayouts, 'totalReceiveable' => $totalReceiveable, 'pendingPayments' => $pendingPayments], 200);
            } else {
                return response()->json(['message' => 'no history available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function FavoritDeal(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
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

    public function OrderStatus($id)
    {
        $role = Auth::user()->role;
        if ($role == 1) {
            $order = Order::find($id);
            if ($order) {
                $order->update(['status' => 'completed']);
               
                return response()->json(['message' => 'Order status completed successfully', 'order' => $order], 200);
            } else {
                return response()->json(['message' => 'No Order found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetCustomerInprogressOrder($id)
    {
        $role = Auth::user()->role;
        if ($role == 1) {

            $GetInprogressOrder = Order::leftJoin('deals', 'deals.id', '=', 'orders.deal_id')
                ->where('orders.status', 'in progress')
                ->where('orders.customer_id', $id)
                ->select('orders.*', 'deals.service_title as deal_name')
                ->get();
            if ($GetInprogressOrder->isNotEmpty()) {

                return response()->json(['GetInprogressOrder' => $GetInprogressOrder], 200);
            } else {
                return response()->json(['message' => 'Order Not Found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function uploadImage(Request $request)
    {
        if (!auth()->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $request->validate([
            'img' => 'required|image|mimes:jpeg,png,jpg,gif', 
        ]);

        if ($request->hasFile('img')) {
            $photo = $request->file('img');
            $photo_name = time() . '-' . $photo->getClientOriginalName();
            $photo_destination = public_path('uploads');
            $photo->move($photo_destination, $photo_name);
            return response()->json([
                'message' => 'Image uploaded successfully',
                'image_name' => $photo_name
            ], 200);
        }

    return response()->json(['message' => 'No image uploaded'], 400);
}


    public function PublishSetting($id)
    {

        $role = Auth::user()->role;
        if ($role == 1) {
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

    public function CustomerDetail()
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 1) {
            $user = User::find($userId);

            $getPayment = PaymentDetail::where('user_id', $userId)->get();
            $getSocial = SocialProfile::where('user_id', $userId)->get();
            if ($user) {

                return response()->json(['user' => $user, 'getPayment' => $getPayment, 'getSocial' => $getSocial], 200);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function AddCustomerPayment(Request $request)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 1) {
            $data = $request->all();
            $payment = PaymentDetail::where('user_id', $userId)->first();
            if ($payment) {

                $payment->update($data);
                $notifications = [
                    'title' => 'update Payment details',
                    'message' => 'Updated Payment details successfully',
                    'created_by' => $userId,
                    'status' => 0,
                    'clear' => 'no',

                ];
                Notification::create($notifications);
                return response()->json(['message' => 'Updated Payment details successfully', 'payment' => $payment], 200);
            } else {
                    $data['user_id'] = $userId;
                    $payment = PaymentDetail::create($data);
                    $notifications = [
                        'title' => 'Create Payment details',
                        'message' => 'Added Payment details successfully',
                        'created_by' => $userId,
                        'status' => 0,
                        'clear' => 'no',

                    ];
                    Notification::create($notifications);
                    return response()->json(['message' => 'Added Payment details successfully', 'payment' => $payment], 200);
                
            }
            return response()->json(['message' => 'User not found'], 401);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetCustomerFavoritService(){
        $role = Auth::user()->role;
        if ($role == 1) {
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
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function HomeCustomerOrders(Request $request){
        $role = Auth::user()->role;
        if ($role == 1) {
            $userId = Auth::id();
            $GetActiveOrders = Order::where('customer_id', $userId)->where('status', '!=', 'completed')->get();
            if($GetActiveOrders){
                return response()->json(['message' => 'Orders List', 'activeOrders' => $GetActiveOrders], 200);
            }else{
                return response()->json(['message' => 'No order available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
        
    }
    
}