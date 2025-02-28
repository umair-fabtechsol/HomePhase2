<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Deal;
use App\Models\User;
use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    public function SuperAdminDashboard(){


        $GetNumberOfDeals=Deal::all()->count();
        $GetTotalServiceProvider=User::where('role',2)->count();
        $GetTotalClient=User::where('role',1)->count();
      
        
    }
    public function ServiceProviders()
    {
        $serviceProviders = DB::table('users')->leftJoin(DB::raw('(SELECT user_id, COUNT(id) as total_deals FROM deals GROUP BY user_id) as deals'), 'users.id', '=', 'deals.user_id')->leftJoin('reviews', 'users.id', '=', 'reviews.provider_id')->select(
        'users.id',
        'users.personal_image',
        'users.name',
        'users.email',
        'users.phone',
        DB::raw('COALESCE(deals.total_deals, 0) as total_deals'),
        DB::raw('AVG(reviews.rating) as rating')
    )
    ->where('users.role', 2)
    ->groupBy('users.id', 'users.personal_image', 'users.name', 'users.email', 'users.phone', 'deals.total_deals')
    ->paginate(8);
            
        $totalProviders = User::where('role', 2)->count();

        if ($serviceProviders) {
            return response()->json(['totalProviders' => $totalProviders, 'serviceProviders' => $serviceProviders], 200);
        } else {
            return response()->json(['message' => 'No Service Provider Available'], 200);
        }
    }

    public function ProviderDetail($user_id)
    {
        $user = User::find($user_id);
        $deals = Deal::where('user_id', $user_id)->get();
        $business = BusinessProfile::where('user_id', $user_id)->first();
        return response()->json(['message' => 'Provider Details', 'user' => $user, 'deals' => $deals, 'business' => $business], 200);
    }

    public function Customers()
    {
        $customers = User::where('role', 1)->get();
        if ($customers) {
            return response()->json(['Customers' => $customers], 200);
        } else {
            return response()->json(['message' => 'No Customer Available'], 200);
        }
    }
    public function AddSalesReps(Request $request)
    {


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
    }

    public function ViewSalesReps($id)
    {

        $GetSalesReps = User::find($id);

        return response()->json(['GetSalesReps' => $GetSalesReps], 200);
    }

    public function UpdateSalesReps(Request $request)
    {

        $data = $request->all();

        $GetSaleRep = User::find($request->id);
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
    }

    public function DeleteSalesReps($id)
    {

        $GetSaleRep = User::find($id);
        $imagePath = public_path('uploads/' . $GetSaleRep->personal_image);
        if (!empty($GetSaleRep->personal_image) && file_exists($imagePath)) {
            unlink($imagePath);
        }

        $GetSaleRep->delete();
        return response()->json(['message' => 'Sales Reps deleted successfully', 'GetSaleRep' => $GetSaleRep], 200);
    }

    public function UpdatePersonal(Request $request)
    {
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
                $user->update($data);
            }
            return response()->json(['message' => 'User Personal details updated successfully', 'user' => $user], 200);
        } else {
            return response()->json(['message' => 'No user found'], 200);
        }
    }

    public function Security(Request $request)
    {
        $user = User::find($request->id);
        if ($user) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 200);
            }
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json(['message' => 'User Password Updated successfully', 'user' => $user], 200);
        } else {
            return response()->json(['message' => 'No user found'], 200);
        }
    }

    public function NotificationSetting(Request $request)
    {
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
            return response()->json(['message' => 'No user found'], 200);
        }
    }
    
    public function AddPriceDetails(Request $request){

        
        $data = $request()->all();
        $price = Price::create($data);
        return response()->json(['message' => 'Price Details create successfully', 'price' => $price], 200);
        

        
    }
    public function GetProvidersSummary()
    {
       
        $providers = User::where('role',3) 
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
    }

    public function GetClientsSummary()
    {
       
        $providers = User::where('role',2) 
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
    }
}