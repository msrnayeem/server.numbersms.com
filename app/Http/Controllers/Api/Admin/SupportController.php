<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Support;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    // index
    public function index()
    {
        try {
            $data = Support::orderBy('created_at', 'desc')->get();

            if ($data) {
                return response()->json([
                    'status' => true,
                    'message' => 'All support data',
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Not foud data!',
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

    // delete
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required'
            ]);

            $support = Support::find($request->id);
            if ($support) {
                if ($support->screenshot) {
                    $oldImagePath = public_path('uploads/' . basename($support->screenshot));
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $support->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Support delete success',
                    'data' => []
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid request',
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

    // single
    public function single(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required'
            ]);

            $support = Support::find($request->id);
            if ($support) {
                return response()->json([
                    'status' => true,
                    'message' => 'Support data',
                    'data' => $support
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid request',
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
