<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransictionController extends Controller
{
    public function index()
    {
        try {
            $data = Transaction::where('user_id', auth()->user()->id)->orWhere('shared_id', auth()->user()->id)->orderBy('created_at', 'desc')->get();

            if (!$data) {
                return response()->json([
                    'status' => true,
                    'message' => 'No transaction data found!',
                    'data' => []
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'all transaction data',
                'data' => $data
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }
}
