<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\SmsHistory;
use Illuminate\Http\Request;

class SmsHistoryController extends Controller
{
    public function getSmsHistory()
    {
        try {
            $currentUser = auth()->user();
            $data = SmsHistory::where('user_id', $currentUser->id)->orderBy('created_at', 'desc')->get();
            if ($data) {
                return response()->json([
                    'status' => false,
                    'message' => 'All of sms history useage data',
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'No data found!',
                    'data' => []
                ]);
            }
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }
}
