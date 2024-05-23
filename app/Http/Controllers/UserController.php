<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\UserMain;

class UserController extends Controller
{
    public function listUserView(Request $request)
    {
        $listUsers = UserMain::with([
            'login' => function ($query) {
                $query->select('id', 'acc_email');
            },
            'detail' => function ($query) {
                $query->select('id', 'usr_fname', 'usr_lname', 'usr_birth', 'usr_code_phone', 'usr_no_phone');
            },
            'access' => function ($query) {
                $query->select('id');
            },
        ])
            ->select('id as usr_main_id', 'usr_detail_id', 'usr_login_id', 'usr_access_id', 'usr_acc_status')
            ->where('client_detail_id', '=', '0')
            ->get();
        // Transform the data
        $transformedUsers = $listUsers->map(function ($user) {
            return [
                'usr_main_id' => $user->usr_main_id,
                'usr_detail_id' => $user->usr_detail_id,
                'usr_login_id' => $user->usr_login_id,
                'first_name' => optional($user->detail)->usr_fname,
                'last_name' => optional($user->detail)->usr_lname,
                'usr_birth' => optional($user->detail)->usr_birth,
                'usr_email' => optional($user->login)->acc_email,
                'usr_access' => $user->usr_access_id,
                'usr_no_phone' => optional($user->detail)->usr_no_phone,
                'usr_status' => $user->usr_acc_status,
            ];
        });

        return response()->json(
            [
                'message' => 'success',
                'data' => $transformedUsers,
            ],
            200,
        );
    }
}
