<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\User;
use App\Models\Task;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SaleRapController extends Controller
{
    public function Dashboard()
    {
        $role = Auth::user()->role;
        if ($role == 3) {

            $GetTotalClient = User::where('role', 1)->count();
            $GetCurrentMonthTotalClient = User::where('role', 1)->whereMonth('created_at', Carbon::now()->month)->count();
            $GetTotalActiveProvider = User::where('role', 2)->count();
            $GetTotalCompletedServices = Deal::where('publish', 1)->count();


            return response()->json(['GetTotalClient' => $GetTotalClient, 'GetCurrentMonthTotalClient' => $GetCurrentMonthTotalClient, 'GetTotalActiveProvider' => $GetTotalActiveProvider, 'GetTotalCompletedServices' => $GetTotalCompletedServices], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }
    public function SaleRepProviders()
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $serviceProviders = DB::table('users')
                ->leftJoin('deals', 'users.id', '=', 'deals.user_id')
                ->select(
                    'users.id',
                    'users.personal_image',
                    'users.name',
                    'users.email',
                    'users.phone',
                    DB::raw('COUNT(deals.id) as total_deals')
                )
                ->where('users.role', 2)
                ->groupBy('users.id', 'users.personal_image', 'users.name', 'users.email', 'users.phone')
                ->get();

            if ($serviceProviders) {
                return response()->json(['serviceProviders' => $serviceProviders], 200);
            } else {
                return response()->json(['message' => 'No Service Provider Available'], 401);
            }
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SalesPersonal(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
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

    public function SalesSecurity(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $user = User::find($request->id);
            if ($user) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json(['message' => 'Current password is incorrect'], 422);
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

    public function AddTask(Request $request)
    {
        $role = Auth::user()->role;
        $userId = Auth::id();
        if ($role == 3) {

            $data = $request->all();
            if ($request->hasFile('files')) {
                $photo1 = $request->file('files');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['files'] = $photo_name1;
            }
            $data['created_by']=$userId;
            $task = Task::create($data);
            return response()->json(['message' => 'Task created successfully', 'task' => $task], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function FetchAllTask()
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $task = Task::all();


            return response()->json(['task' => $task], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function ViewTask($id)
    {
        $role = Auth::user()->role;
        if ($role == 3) {

            $task = Task::find($id);

            return response()->json(['task' => $task], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function UpdateTask(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $task = Task::find($request->id);
            $data = $request->all();

            if ($request->hasFile('files')) {
                $imagePath = public_path('uploads/' . $task->files);
                if (!empty($task->files) && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $photo1 = $request->file('files');
                $photo_name1 = time() . '-' . $photo1->getClientOriginalName();
                $photo_destination = public_path('uploads');
                $photo1->move($photo_destination, $photo_name1);
                $data['files'] = $photo_name1;
            }

            $task->update($data);
            return response()->json(['message' => 'Task updated successfully', 'task' => $task], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function DeleteTask($id)
    {

        $role = Auth::user()->role;
        if ($role == 3) {
            $task = Task::find($id);
            $imagePath = public_path('uploads/' . $task->files);
            if (!empty($task->files) && file_exists($imagePath)) {
                unlink($imagePath);
            }
            $task->delete();
            return response()->json(['message' => 'Task deleted successfully', 'task' => $task], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetSettingSale($id)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
            $GetSettingSale = User::find($id);
            if ($GetSettingSale) {
                $setting = [
                    'name' => $GetSettingSale->name,
                    'email' => $GetSettingSale->email,
                    'phone' => $GetSettingSale->phone,
                    'personal_image' => $GetSettingSale->personal_image,
                ];
                return response()->json(['setting' => $setting], 200);
            }
            return response()->json(['message' => 'Setting not available'], 401);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function SaleCustomers(Request $request)
    {
        $role = Auth::user()->role;
        
        if ($role == 3) {
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

    public function SaleCustomer($id)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
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

    public function UpdateSaleCustomer(Request $request)
    {
        $role = Auth::user()->role;
        if ($role == 3) {
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

            return response()->json(['message' => 'Customer updated successfully', 'GetSaleRep' => $GetSaleRep], 200);
        } else {
            return response()->json(['message' => 'You are not authorized'], 401);
        }
    }

    public function GetServiceRevenue(){
            
      
        $totalRevenue = Order::sum('total_amount');

      
        $reportData = Deal::select('deals.service_category', DB::raw('SUM(orders.total_amount) as revenue'))
            ->join('orders', 'orders.deal_id', '=', 'deals.id')
            ->groupBy('deals.service_category')
            ->get()
            ->map(function ($data) use ($totalRevenue) {
                return [
                    'category' => $data->service_category,
                    'revenue' => $data->revenue,
                    'percentage' => $totalRevenue ? round(($data->revenue / $totalRevenue) * 100, 2) : 0
                ];
            });


        return response()->json(['reportData' => $reportData], 200);
        
    } 

    public function quarterlyReport()
    {
        $quarters = [
            'Q1' => [1, 3],  // January - March
            'Q2' => [4, 6],  // April - June
            'Q3' => [7, 9],  // July - September
            'Q4' => [10, 12] // October - December
        ];

        $quarterlyData = [];
        $previousRevenue = 0;
        $totalRevenue = Order::sum('total_amount');

        foreach ($quarters as $quarter => $months) {
            $revenue = Order::whereMonth('created_at', '>=', $months[0])
                ->whereMonth('created_at', '<=', $months[1])
                ->sum('total_amount');

        
            $growth = $previousRevenue > 0 ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 2) : '-';
            $previousRevenue = $revenue;

            $quarterlyData[] = [
                'quarter' => $quarter,
                'revenue' => $revenue,
                'growth' => $growth
            ];
        }

        
        $totalGrowth = round(($totalRevenue > 0 ? ($totalRevenue / max($previousRevenue, 1)) * 100 : 0), 2);
        $quarterlyData[] = [
            'quarter' => 'Total',
            'revenue' => $totalRevenue,
            'growth' => $totalGrowth
        ];

        return response()->json(['quarterlyData' => $quarterlyData], 200);
    }
}