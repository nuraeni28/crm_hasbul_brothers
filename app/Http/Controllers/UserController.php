<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\UserMain;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function listUserView(Request $request)
    {
        $listUsers = UserMain::with([
            'userLogin' => function ($query) {
                $query->select('id', 'acc_email');
            },
            'userDetail' => function ($query) {
                $query->select('id', 'usr_fname', 'usr_lname', 'usr_birth', 'usr_code_phone', 'usr_no_phone');
            },
            'userAccess' => function ($query) {
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
    public function listUserAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permissionUserPermission' => 'required',
            'permissionUserStatus' => 'required',
            'permissionEmail' => 'required',
            'permissionFirstName' => 'required',
            'permissionLastName' => 'required',
            'permissionContactNumber' => 'required',
            'permissionBrithday' => 'required',
            'permissionPassword' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }
        // Begin transaction
        DB::beginTransaction();

        try {
            // Insert into usr_login
            $userLogin = User::create([
                'acc_email' => $request->permissionEmail,
                'acc_password' => $request->permissionPassword,
            ]);

            // Retrieve the ID of the newly inserted usr_login record
            $user_login_id = $userLogin->id;

            // Insert into usr_detail
            $userDetail = UserDetail::create([
                'usr_fname' => $request->permissionFirstName,
                'usr_lname' => $request->permissionLastName,
                'usr_birth' => $request->permissionBrithday,
                'usr_no_phone' => $request->permissionContactNumber,
                'usr_code_phone' => '6',
            ]);

            // Retrieve the ID of the newly inserted usr_detail record
            $user_detail_id = $userDetail->id;

            // Insert into usr_main
            UserMain::create([
                'usr_login_id' => $user_login_id,
                'usr_access_id' => $request->permissionUserPermission,
                'client_detail_id' => '0',
                'usr_acc_appear' => '1',
                'usr_acc_status' => $request->permissionUserStatus,
                'usr_detail_id' => $user_detail_id,
            ]);

            // Commit transaction
            DB::commit();

            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            DB::rollBack();

            // Log the exception
            \Log::error('Error creating user: ' . $e->getMessage());

            return response()->json(['message' => 'Error creating user'], 500);
        }
    }

    public function listUserEdit(Request $request)
    {
        // Validate the edit request

        $validator = Validator::make($request->all(), [
            'permissionUserMainId' => 'required',
            'permissionUserPermission' => 'required',
            'permissionUserStatus' => 'required',
            'permissionUserLoginId' => 'required',
            'permissionEmail' => 'required',
            'permissionFirstName' => 'required',
            'permissionLastName' => 'required',
            'permissionContactNumber' => 'required',
            'permissionBrithday' => 'required',
            'permissionPassword' => 'required',
            'permissionUserDetailId' => 'required',
        ]);

        if ($validator->fails()) {
            // if email already exists

            return response()->json(['message' => $validator->errors()], 422);
        }
        // Begin transaction
        DB::beginTransaction();

        try {
            UserMain::where('id', $request->permissionUserMainId)->update([
                'usr_access_id' => $request->permissionUserPermission,
                'usr_acc_status' => $request->permissionUserStatus,
            ]);

            //update user login
            $userLoginData = [
                'acc_email' => $request->permissionEmail,
            ];

            if (!empty($request->permissionPassword)) {
                $userLoginData['acc_password'] = $request->permissionPassword;
            }

            User::where('id', $request->permissionUserLoginId)->update($userLoginData);

            //update user detail
            UserDetail::where('id', $request->permissionUserDetailId)->update([
                'usr_fname' => $request->permissionFirstName,
                'usr_lname' => $request->permissionLastName,
                'usr_no_phone' => $request->permissionContactNumber,
                'usr_birth' => $request->permissionBrithday,
            ]);

            // Commit transaction
            DB::commit();

            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            //throw $th;
            // Rollback transaction
            DB::rollBack();

            // Log the exception and return an error response
            \Log::error('Error updating user: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating user'], 500);
        }
    }

    public function listUserDelete(Request $request)
    {
        $userMain = UserMain::find($request->userId);
        if ($userMain) {
            $userMain->usr_acc_status = 4;
            $userMain->save();

            return response()->json(['message' => 'success'], 200);
        } else {
            // Handle the case where the user is not found
            return response()->json(['message' => 'User not found'], 404);
        }
    }
}
