<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\DiscountModel;

class CreaditController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'amount' => 'required',
            'user_id' => 'required',
        ]);

        try {
            $amount = floatval($request->amount);

            $currentUser = User::where('id', $request->user_id)->orWhere('email', $request->user_id)->first();
            if(!$currentUser){
                return response()->json([
                    'status'=>false,
                    'message'=>'Not found user by this user_id',
                    'data'=>[]
                ]);
            }

            $currentUser->coin = $currentUser->coin + $amount;
            $currentUser->save();
            // for current user
            Transaction::create([
                'user_id' => $currentUser->id,
                'username' => $currentUser->name,
                'getway' => 'Admin',
                'amount' => $amount,
                'status' => true
            ]);

            return response()->json([
                'status' => true,
                'message' => $request->amount . ' Amount added success and now your current balance is ' . $currentUser->coin,
                'amount' => $amount,
                'totalamount' => $currentUser->coin,
                'getway' => 'Admin',
                'data' => []
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }
    
    // manage discount
    public function discountManegar(Request $request){
        try{
            $request->validate([
                'discount'=>'required|numeric'
            ]);
            
            // update data
            $discount = DiscountModel::first();

            if ($discount) {
                $discount->update(['discount' => $request->discount]);
            } else {
                DiscountModel::create(['discount' => $request->discount]);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'New Discount addedd',
                'data'=>[
                   'discount'=>$discount->discount   
                  ]
            ]);

        }catch(\Exception $th){
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }
    
    // get discount
    public function discountManegarGet(){
        try{
            $discount = DiscountModel::first();
            if(!$discount){
                return response()->json([
                 'status'=>false,
                 'message'=>"No data found!",
                 'data'=>[]
                ]);
            }
            
            return response()->json([
                 'status'=>true,
                 'message'=>"Discount data",
                 'data'=>[
                   'discount'=>$discount->discount   
                  ]
            ]);
        }catch(\Exception $th){
            return response()->json([
             'status'=>false,
             'message'=>$th->getMessage(),
             'data'=>[]
            ]);
        }
    }
}
