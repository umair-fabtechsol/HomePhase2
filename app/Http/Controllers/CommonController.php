<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

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
 

}
