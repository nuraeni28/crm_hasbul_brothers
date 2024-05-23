<?php

namespace App\Http\Controllers;

use App\Models\UserToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if ($request->isMethod('post')) {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
                // 'remember' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            

            // Mengambil input
            $credentials = $request->only('email', 'password', 'remember');
            
            // Memeriksa pengguna
            $user = DB::table('usr_login')
                ->where('acc_email', $credentials['email'])
                ->first();

            if (!$user || $credentials['password'] !== $user->acc_password) {
                return response([
                    'message' => 'User not found or incorrect password',
                    'user_detail' => '',
                    'token' => ''
                ], 404);
            }

            // Mendapatkan ID pengguna
            $userId = DB::table('usr_main')
                ->where('usr_login_id', $user->id)
                ->first();

            // Menghasilkan token (anda harus mengganti dengan logika generate token yang sesuai)
            $tokenDetails = $this->generateToken($userId->id, $credentials['remember']);
            
            return response([
                'message' => 'success',
                'user_detail' => $this->userDetail($userId->id),
                'token' => $tokenDetails['token'],
                'expired' => $tokenDetails['expired']
            ]);
        } else {
            return response([
                'message' => 'Method Not Allowed',
            ], 405);
        }
    }

    private static function generateToken($userId, $remember)
    {
        do {
            $token = bin2hex(random_bytes(32));
            $checkDupToken = UserToken::where('usr_token', $token)->first();
        } while ($checkDupToken);
    
        $expiredToken = $remember 
            ? now()->addDays(5) 
            : now()->addHours(2);
    
        UserToken::create([
            'usr_token' => $token,
            'usr_main_id' => $userId,
            'expired_at' => $expiredToken
        ]);
    
        return [
            'token' => $token,
            'expired' => $expiredToken
        ];
    }
    private static function userDetail($usr_main_id)
{
    $usrMain = DB::table('usr_main')
        ->select('usr_detail_id', 'usr_access_id', 'usr_acc_appear', 'usr_acc_status')
        ->where('id', $usr_main_id)
        ->first();

    if (!$usrMain) {
        return null; // Tidak ditemukan data pengguna
    }

    $usrDetail = DB::table('usr_detail')
        ->select('usr_fname', 'usr_lname', 'usr_birth', 'usr_code_phone', 'usr_no_phone', 'usr_image')
        ->where('id', $usrMain->usr_detail_id)
        ->first();

    $usrAccess = DB::table('usr_access')
        ->select('access_name', 'access_privilege', 'access_status')
        ->where('id', $usrMain->usr_access_id)
        ->first();

    $combinedObject = (object) array_merge(
        (array) $usrMain,
        (array) $usrDetail,
        (array) $usrAccess
    );

    return $combinedObject;
}

public static function logout(Request $request)
{
    // Retrieve the token from the Authorization header
    $authorizationHeader = $request->header('Authorization');

    $token = $authorizationHeader;
    
    // Check if the token exists in the database
    $checkToken = UserToken::where('usr_token', $token)->first();

    if (!$checkToken) {
        return response()->json([
            'message' => 'Token not found'
        ], 404);
    }

    if ($checkToken->revoked_at) {
        return response()->json([
            'message' => 'Token already revoked'
        ], 200);
    }

    // Revoke the token
    $checkToken->revoked_at = now();
    $checkToken->save();

    return response()->json([
        'message' => 'success'
    ], 200);
}

public function verifyToken($token)
    {
        $checkToken = DB::table('usr_outh_token')
            ->select('usr_main_id', 'expired_at', 'revoked_at')
            ->where('usr_token', $token)
            ->first();

        if (!$checkToken || $checkToken->revoked_at || Carbon::parse($checkToken->expired_at)->isPast()) {
            return response()->json([
                'message' => 'token expired',
                'user_detail' => ''
            ]);
        }

        $userDetail = $this->userDetail($checkToken->usr_main_id);

        return response()->json([
            'message' => 'success',
            'user_detail' => $userDetail
        ]);
    }


    
}
