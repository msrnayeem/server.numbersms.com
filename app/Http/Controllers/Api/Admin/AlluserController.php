<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class AlluserController extends Controller
{
    // get all users
    public function allUsers()
    {
        try {
            $data = User::orderBy('created_at', 'desc')->get();

            if ($data) {
                return response()->json([
                    'status' => true,
                    'message' => 'All users data',
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'status' => true,
                    'message' => 'No user data found',
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

    // delete user
    public function deluser(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|numeric'
            ]);

            $user = User::find($request->id);

            // delete transaction
            $transaction = Transaction::where("user_id", $user->id)->get();

            if ($transaction) {
                foreach ($transaction as $item) {
                    $item->delete();
                }
            }

            // del user
            $user->delete();

            return response()->json([
                'status' => true,
                'message' => 'One user deleted success. id ' . $request->id,
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

    // make admin
    public function userRoleChnage(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|numeric',
                'role' => 'required|in:admin,user'
            ]);

            $user = User::find($request->id);

            if ($user->role == $request->role) {
                return response()->json([
                    'status' => false,
                    'message' => 'User already ' . $request->role,
                    'data' => []
                ]);
            }

            $user->role = $request->role;
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'This user role has been change success by ' . $request->role,
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
