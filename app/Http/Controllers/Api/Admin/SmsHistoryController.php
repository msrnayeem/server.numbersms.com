<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Services;
use App\Models\SmsHistory;
use Illuminate\Http\Request;

class SmsHistoryController extends Controller
{
    public function getSmsHistory()
    {
        try {
            $data = SmsHistory::orderBy('created_at', 'desc')->get();
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

    // services image
    public function servicesImage(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
                'image' => 'required'
            ]);

            $services = Services::find($request->id);
            if ($services->image) {
                $oldImagePath = public_path('uploads/' . basename($services->image));
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $filename);
            $services->image = asset('uploads/' . $filename);
            $services->save();

            return response()->json([
                'status' => true,
                'message' => 'Services image update',
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
}
