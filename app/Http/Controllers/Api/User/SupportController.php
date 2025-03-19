<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Support;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    // create new support
    public function index(Request $request)
    {
        $request->validate([
            'couses' => 'required',
            'subject' => 'required|min:1',
            'message' => 'required|min:1',
        ]);

        try {
            $support = new Support();
            $support->user_id = auth()->user()->id;
            $support->couses = $request->couses;
            $support->suject = $request->subject;
            $support->message = $request->message;

            if ($request->hasFile('ss')) {
                $file = $request->file('ss');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads'), $filename);
                $support->screenshot = asset('uploads/' . $filename);
            }

            $support->save();

            return response()->json([
                'status' => true,
                'message' => 'Support open success',
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

    // get all support
    public function getSupport()
    {
        try {
            $data = Support::where('user_id', auth()->user()->id)->orderBy('created_at', 'desc')->get();
            if ($data->count() < 0) {
                return response()->json([
                    'status' => true,
                    'message' => 'Support data not found',
                    'data' => []
                ]);
            }
            return response()->json([
                'status' => true,
                'message' => 'Support data',
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
