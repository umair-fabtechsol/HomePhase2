<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Deal;
use App\Models\User;
use App\Models\Order;
use App\Models\Price;
use App\Models\Review;
use App\Models\Support;
use App\Models\PaymentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\InviteSalesRepMail;
use App\Models\contact_pro;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    public function SuperAdminDashboard()
    {
        $role = Auth::user()->role;
        if ($role == 0) {

            $total_revenue_generated = Order::where('status', 'completed')->sum('total_amount');
            $total_service_providers = User::where('role', 2)->count();
            $total_customers = User::where('role', 1)->count();
            $total_service_listed = Deal::all()->count();

            $total_active_sales = User::where('role', 3)->where('status', 0)->count();
            $total_active_providers = User::where('role', 2)->where('status', 0)->count();
            $total_active_customers = User::where('role', 1)->where('status', 0)->count();

            $total_transactions = PaymentHistory::where('payment_type', 'payout')->count();

            // Calculate the number of new providers added each day of the current week
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();
            $newProvidersByDay = User::where('role', 2)
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->select(DB::raw('DAYNAME(created_at) as day'), DB::raw('COUNT(*) as count'))
                ->groupBy('day')
                ->orderBy('created_at')
                ->get()
                ->pluck('count', 'day')
                ->toArray();

            $newCustomersByDay = User::where('role', 1)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->select(DB::raw('DAYNAME(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->orderBy('created_at')
            ->get()
            ->pluck('count', 'day')
            ->toArray();

            $newSaleRapByDay = User::where('role', 3)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->select(DB::raw('DAYNAME(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->orderBy('created_at')
            ->get()
            ->pluck('count', 'day')
            ->toArray();

            $dayName = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $addCurrentWeeklyProvider = [];
            foreach ($dayName as $day) {
                $addCurrentWeeklyProvider[] = $newProvidersByDay[$day] ?? 0;
            }

            $addCurrentWeeklyCustomer = [];
            foreach ($dayName as $day) {
                $addCurrentWeeklyCustomer[] = $newCustomersByDay[$day] ?? 0;
            }

            $addCurrentWeeklySales = [];
            foreach ($dayName as $day) {
                $addCurrentWeeklySales[] = $newSaleRapByDay[$day] ?? 0;
            }

            // Calculate the number of active users for each day of the current month
            $currentMonth = Carbon::now()->month;
            $previousMonth = Carbon::now()->subMonth()->month;
            $currentYear = Carbon::now()->year;
            $daysInMonth = Carbon::now()->daysInMonth;
            $daysInPreviousMonth = Carbon::now()->subMonth()->daysInMonth;
            $currentMonthActiveUser = [];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $date = Carbon::create($currentYear, $currentMonth, $i);
                $currentMonthActiveUser[] = User::where('role', '!=', 0)->whereDate('created_at', $date)->count();
            }

            $previousMonthActiveUser = [];
            for ($i = 1; $i <= $daysInPreviousMonth; $i++) {
                $date = Carbon::create($currentYear, $previousMonth, $i);
                $previousMonthActiveUser[] = User::where('role', '!=', 0)->whereDate('created_at', $date)->count();
            }


            return response()->json([
                'total_service_providers' => $total_service_providers,
                'total_customers' => $total_customers,
                'total_transactions' => $total_transactions,
                'total_service_listed' => $total_service_listed,
                'total_revenue_generated' => $total_revenue_generated,
                'total_active_sales' => $total_active_sales,
                'total_active_providers' => $total_active_providers,
                'total_active_customers' => $total_active_customers,
                'addCurrentWeeklyProvider' => $addCurrentWeeklyProvider,
                'addCurrentWeeklyCustomer' => $addCurrentWeeklyCustomer,
                'addCurrentWeeklySales' => $addCurrentWeeklySales,
                'currentMonthActiveUser' => $currentMonthActiveUser,
                'previousMonthActiveUser' => $previousMonthActiveUser,
            ], 200);

        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function ServiceProviders(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $serviceProviders = DB::table('users')
                ->leftJoin(DB::raw('(SELECT user_id, COUNT(id) as total_deals FROM deals GROUP BY user_id) as deals'), 'users.id', '=', 'deals.user_id')
                ->leftJoin('reviews', 'users.id', '=', 'reviews.provider_id')
                ->select(
                    'users.id',
                    'users.personal_image',
                    'users.name',
                    'users.email',
                    'users.phone',
                    DB::raw('COALESCE(deals.total_deals, 0) as total_deals'),
                    DB::raw('AVG(reviews.rating) as rating')
                )
                ->where('users.role', 2)
                ->groupBy('users.id', 'users.personal_image', 'users.name', 'users.email', 'users.phone', 'deals.total_deals', 'reviews.provider_id');

            if ($request->has('search')) {
                $search = $request->search;
                $serviceProviders->where(function ($query) use ($search) {
                    $query->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.phone', 'like', "%{$search}%");
                });
            }

            $serviceProviders = $serviceProviders->paginate($request->providers ?? 8);


            $totalProviders = $serviceProviders->total();

            if ($serviceProviders) {
                return response()->json(['totalProviders' => $totalProviders, 'serviceProviders' => $serviceProviders], 200);
            } else {
                return response()->json(['message' => 'No Service Provider Available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function ProviderDetail($user_id)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $user = User::find($user_id);
            $deals = Deal::where('user_id', $user_id)->get();
            $business = BusinessProfile::where('user_id', $user_id)->first();
            $averageRating = DB::table('reviews')->where('provider_id', $user_id)->avg('rating');
            $totalReview = DB::table('reviews')->where('provider_id', $user_id)->count();

            $stars = Review::select(
                DB::raw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star'),
                DB::raw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star'),
                DB::raw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star'),
                DB::raw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star'),
                DB::raw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star')
            )
                ->where('provider_id', $user_id)
                ->first();

            return response()->json(['message' => 'Provider Details', 'user' => $user, 'deals' => $deals, 'business' => $business, 'averageRating' => $averageRating, 'totalReview' => $totalReview, 'stars' => $stars], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdateProvider(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {

            $data = $request->all();

            $getProvider = User::find($request->id);
            if($getProvider->role != 2){
                return response()->json(['message' => 'Invalid User Id'], 401);
            }
            if ($request->hasFile('personal_image')) {
                $imagePath = public_path('uploads/' . $getProvider->personal_image);
                if (!empty($getProvider->personal_image) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo1 = $request->file('personal_image');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['personal_image'] = $photo_name1;
            }
            $getProvider->update($data);

            return response()->json(['message' => 'Provider updated successfully', 'getProvider' => $getProvider], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function Customers(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $customers = User::where('role', 1);
            if ($request->has('search')) {
                $search = $request->search;
                $customers->where(function ($query) use ($search) {
                    $query->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.phone', 'like', "%{$search}%");
                });
            }

            $customers = $customers->paginate($request->clients ?? 8);

            $total_customers = $customers->total();
            if ($customers) {
                return response()->json(['total_customers' => $total_customers, 'Customers' => $customers], 200);
            } else {
                return response()->json(['message' => 'No Customer Available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function Customer($id)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $customer = User::find($id);
            if ($customer) {
                return response()->json(['Customer' => $customer], 200);
            } else {
                return response()->json(['message' => 'No Customer Available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function AddSalesReps(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $data = $request->all();

            if ($request->hasFile('personal_image')) {
                $photo1 = $request->file('personal_image');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['personal_image'] = $photo_name1;
            }
            $data['terms'] = 1;
            $Salesreps = User::create($data);


            return response()->json(['message' => 'Sales Reps created successfully', 'Salesreps' => $Salesreps], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function ViewSalesReps($id)
    {
        $role = Auth::user()->role;
        if ($role == 0) {

            $GetSalesReps = User::find($id);

            return response()->json(['GetSalesReps' => $GetSalesReps], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdateSalesReps(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $data = $request->all();

            $GetSaleRep = User::find($request->id);
            if($getProvider->role != 3){
                return response()->json(['message' => 'Invalid User Id'], 401);
            }
            if ($request->hasFile('personal_image')) {
                $imagePath = public_path('uploads/' . $GetSaleRep->personal_image);
                if (!empty($GetSaleRep->personal_image) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo1 = $request->file('personal_image');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['personal_image'] = $photo_name1;
            }
            $GetSaleRep->update($data);

            return response()->json(['message' => 'Sales Reps updated successfully', 'GetSaleRep' => $GetSaleRep], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function DeleteSalesReps($id)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $GetSaleRep = User::find($id);
            $imagePath = public_path('uploads/' . $GetSaleRep->personal_image);
            if (!empty($GetSaleRep->personal_image) && file_exists($imagePath)) {
                unlink($imagePath);
            }

            $GetSaleRep->delete();
            return response()->json(['message' => 'Sales Reps deleted successfully', 'GetSaleRep' => $GetSaleRep], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdateCustomer(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $data = $request->all();

            $GetSaleRep = User::find($request->id);
            if($getProvider->role != 1){
                return response()->json(['message' => 'Invalid User Id'], 401);
            }
            if ($request->hasFile('personal_image')) {
                $imagePath = public_path('uploads/' . $GetSaleRep->personal_image);
                if (!empty($GetSaleRep->personal_image) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo1 = $request->file('personal_image');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['personal_image'] = $photo_name1;
            }
            $GetSaleRep->update($data);

            return response()->json(['message' => 'Customer updated successfully', 'GetSaleRep' => $GetSaleRep], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function DeleteCustomer($id)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $GetSaleRep = User::find($id);
            $imagePath = public_path('uploads/' . $GetSaleRep->personal_image);
            if (!empty($GetSaleRep->personal_image) && file_exists($imagePath)) {
                unlink($imagePath);
            }

            $GetSaleRep->delete();
            return response()->json(['message' => 'Customer deleted successfully', 'GetSaleRep' => $GetSaleRep], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetAllSaleRep(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $GetSaleRep = User::where('role', '=', 3);
            if ($request->has('search')) {
                $search = $request->search;
                $GetSaleRep->where(function ($query) use ($search) {
                    $query->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.phone', 'like', "%{$search}%");
                });
            }

            $GetSaleRep = $GetSaleRep->paginate($request->sales_rap ?? 8);

            $total_sales_rap = $GetSaleRep->total();


            return response()->json(['total_sales_rap' => $total_sales_rap, 'GetSaleRep' => $GetSaleRep], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function UpdatePersonal(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
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

    public function Security(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
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

    public function NotificationSetting(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $user = User::find($request->id);
            if ($user) {
                $data = $request->all();
                if ($request->has('general_notification')) {
                } else {
                    $data['general_notification'] = null;
                }
                if ($request->has('provider_notification')) {
                } else {
                    $data['provider_notification'] = null;
                }
                if ($request->has('customer_notification')) {
                } else {
                    $data['customer_notification'] = null;
                }
                if ($request->has('sales_notification')) {
                } else {
                    $data['sales_notification'] = null;
                }
                if ($request->has('message_notification')) {
                } else {
                    $data['message_notification'] = null;
                }
                $user->update($data);
                return response()->json(['message' => 'Notificaiton Setting Updated successfully', 'user' => $user], 200);
            } else {
                return response()->json(['message' => 'No user found'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function AddPriceDetails(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $getPriceDetail = Price::where('user_id', $request->user_id)->first();
            if ($getPriceDetail) {
                $getPriceDetail->update($request->all());
                return response()->json(['message' => 'Price Details updated successfully', 'price' => $getPriceDetail], 200);
            }
            $data = $request->all();

            $price = Price::create($data);
            return response()->json(['message' => 'Price Details create successfully', 'price' => $price], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetPriceDetails()
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $userId = Auth::id();
            $GetPriceDetails = Price::where('user_id', $userId)->first();
            if ($GetPriceDetails) {
                return response()->json(['GetPriceDetails' => $GetPriceDetails], 200);
            }
            return response()->json(['message' => 'price not available'], 401);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetSettingDetail($id)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $GetSettingDetail = User::find($id);
            if ($GetSettingDetail) {
                $setting = [
                    'name' => $GetSettingDetail->name,
                    'email' => $GetSettingDetail->email,
                    'phone' => $GetSettingDetail->phone,
                    'personal_image' => $GetSettingDetail->personal_image,
                ];
                return response()->json(['setting' => $setting], 200);
            }
            return response()->json(['message' => 'Setting not available'], 401);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }


    public function GetProvidersSummary()
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $providers = User::where('role', 3)
                ->selectRaw('QUARTER(created_at) as quarter, COUNT(id) as total')
                ->groupBy('quarter')
                ->orderBy('quarter')
                ->get();


            $report = [];
            $cumulativeTotal = 0;

            for ($i = 1; $i <= 4; $i++) {
                $monthlyTotal = $providers->where('quarter', $i)->first()->total ?? 0;
                $cumulativeTotal += $monthlyTotal;
                $report[] = [
                    'period' => "Q{$i}",
                    'new_providers' => $monthlyTotal,
                    'total_providers' => $cumulativeTotal
                ];
            }

            // Yearly summary
            $totalYearly = array_sum(array_column($report, 'new_providers'));

            return response()->json([
                'status' => 'success',
                'data' => [
                    'report' => $report,
                    'total_yearly' => $totalYearly
                ]
            ], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetClientsSummary()
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $providers = User::where('role', 2)
                ->selectRaw('QUARTER(created_at) as quarter, COUNT(id) as total')
                ->groupBy('quarter')
                ->orderBy('quarter')
                ->get();


            $report = [];
            $cumulativeTotal = 0;

            for ($i = 1; $i <= 4; $i++) {
                $monthlyTotal = $providers->where('quarter', $i)->first()->total ?? 0;
                $cumulativeTotal += $monthlyTotal;
                $report[] = [
                    'period' => "Q{$i}",
                    'new_clients' => $monthlyTotal,
                    'total_clients' => $cumulativeTotal
                ];
            }

            // Yearly summary
            $totalYearly = array_sum(array_column($report, 'new_clients'));

            return response()->json([
                'status' => 'success',
                'data' => [
                    'report' => $report,
                    'total_yearly' => $totalYearly
                ]
            ], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }


    public function sendInvite(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $request->validate([
                'name'  => 'required|string',
                'email' => 'required|email',
            ]);


            $signupUrl = url('/register?email=' . urlencode($request->email));


            Mail::to($request->email)->send(new InviteSalesRepMail($signupUrl));

            return response()->json(['message' => 'Invitation sent successfully!']);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function contact(){
        $getcontact=contact_pro::get()->all();
        return response()->json(['message' => 'Invitation sent successfully!','getcontact' => $getcontact]);
    }

    public function GetSupport(Request $request){
     
      
      $GetSupport = Support::leftJoin('users', 'users.id', '=', 'supports.user_id')
        ->select('supports.*', 'users.name as user_name', 'users.personal_image','users.role')
        ->get();
    


        
        return response()->json(['GetSupport' => $GetSupport]);

        
    }
    public function UpdateSupport(Request $request){


        $data=$request->all();

        $GetSupport = Support::find($request->id);

        $data['status'] = $request->status;

        $GetSupport->update($data);
        
        return response()->json(['GetSupport' => $GetSupport]);
    }

    public function ServiceProviderReport()
    {
        $role = Auth::user()->role;
        if ($role == 0) {
            $currentYear = Carbon::now()->year;

            $quarters = [
                'Q1' => [Carbon::create($currentYear, 1, 1), Carbon::create($currentYear, 3, 31)],
                'Q2' => [Carbon::create($currentYear, 4, 1), Carbon::create($currentYear, 6, 30)],
                'Q3' => [Carbon::create($currentYear, 7, 1), Carbon::create($currentYear, 9, 30)],
                'Q4' => [Carbon::create($currentYear, 10, 1), Carbon::create($currentYear, 12, 31)],
            ];

            $report = [];
            $totalNewServiceProviders = 0;
            $totalCumulativeServiceProviders = 0;

            foreach ($quarters as $quarter => $dates) {
                $newServiceProviders = User::where('role', 2)
                    ->whereBetween('created_at', [$dates[0], $dates[1]])
                    ->count();

                $cumulativeServiceProviders = User::where('role', 2)
                    ->where('created_at', '<=', $dates[1])
                    ->count();

                $report[] = [
                    'period' => $quarter,
                    'new_service_providers' => $newServiceProviders,
                    'cumulative_service_providers' => $cumulativeServiceProviders,
                ];

                $totalNewServiceProviders += $newServiceProviders;
                $totalCumulativeServiceProviders += $cumulativeServiceProviders; // This will be the last cumulative count
            }

            // Add total row
            $report[] = [
                'period' => 'Total',
                'new_service_providers' => $totalNewServiceProviders,
                'cumulative_service_providers' => $totalCumulativeServiceProviders,
            ];

            return response()->json(['report' => $report], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function banProvider(Request $request)
    {
        $user = User::find($request->id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        if ($user->role != 2) {
            return response()->json(['message' => 'Invalid User'], 403);
        }
        $user->update(['status' => 1]);
        return response()->json(['message' => 'User banned successfully', 'user' => $user], 200);
    }
}

