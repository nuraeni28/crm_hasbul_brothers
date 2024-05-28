<?php

namespace App\Http\Controllers;

use App\Models\PreferenceAccess;
use App\Models\PreferenceClass;
use App\Models\PreferencePackage;
use App\Models\PreferenceSchedule;
use App\Models\UserAccess;
use App\Models\UserMain;
use App\Models\UserToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PreferenceController extends Controller
{
    public function schedulerView(Request $request)
    {
        $dataArray1 = [];
        $dataArray2 = [];

        // Fetch schedule data
        $listOfSchedules = PreferenceSchedule::orderBy('schedule_status', 'ASC')->get();

        foreach ($listOfSchedules as $schedule) {
            $dataArray1[] = [
                'scheduler_id' => $schedule->id,
                'schedule_status' => $schedule->schedule_status,
                'event_time_start' => $schedule->event_time_start,
                'event_time_end' => $schedule->event_time_end,
                'event_title' => $schedule->event_title,
                'speaker_id' => $schedule->speaker_id,
                'client_id' => $schedule->client_main_id,
                'client_main_id' => $schedule->client_main_id,
                'event_location' => $schedule->event_location,
                'event_detail' => $schedule->event_detail,
            ];
        }

        // Fetch user data
        $listUsers = UserMain::with(['userDetail', 'userLogin', 'userAccess'])
            ->where('client_detail_id', 0)
            ->get();

        foreach ($listUsers as $user) {
            $dataArray2[] = [
                'usr_main_id' => $user->id,
                'usr_detail_id' => $user->user_detail_id ?? '',
                'usr_login_id' => $user->usr_login_id ?? '',
                'first_name' => $user->userDetail->usr_fname ?? '',
                'last_name' => $user->userDetail->usr_lname ?? '',
                'usr_birth' => date('Y-m-d', strtotime($user->userDetail->usr_birth ?? '')) ?? '',
                'usr_email' => $user->userLogin->acc_email ?? '',
                'usr_access' => $user->userAccess->id ?? '',
                'usr_no_phone' => $user->userDetail->usr_no_phone ?? '',
                'usr_status' => $user->userAccess->usr_acc_status ?? '',
            ];
        }

        return response()->json(
            [
                'message' => 'success',
                'data' => $dataArray1,
                'speaker' => $dataArray2,
            ],
            200,
        );
    }
    public function schedulerAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scheduleStatus' => 'required',
            'eventTimeStart' => 'required',
            'eventTimeEnd' => 'required',
            'eventTitle' => 'required',
            'eventLocation' => 'required',
            'eventDetail' => 'required',
            'speakerId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }
        // Begin transaction
        DB::beginTransaction();
        try {
            //create schedule
            $schedule = new PreferenceSchedule();
            $schedule->schedule_status = $request->scheduleStatus;
            $schedule->event_time_start = $request->eventTimeStart;
            $schedule->event_time_end = $request->eventTimeEnd;
            $schedule->event_title = $request->eventTitle;
            $schedule->event_location = $request->eventLocation;
            $schedule->event_detail = $request->eventDetail;
            $schedule->speaker_id = $request->speakerId;
            $schedule->save();
            // Commit transaction
            DB::commit();

            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            DB::rollBack();

            // Log the exception
            \Log::error('Error creating scheduler: ' . $e->getMessage());

            return response()->json(['message' => 'Error creating scheduler'], 500);
        }
    }
    public static function schedulerEdit(Request $request)
    {
        $schedule = PreferenceSchedule::findOrFail($request->input('schedulerId'));
        // Begin transaction
        DB::beginTransaction();
        try {
            $schedule->update([
                'schedule_status' => $request->input('scheduleStatus'),
                'event_time_start' => $request->input('eventTimeStart'),
                'event_time_end' => $request->input('eventTimeEnd'),
                'event_title' => $request->input('eventTitle'),
                'event_location' => $request->input('eventLocation'),
                'event_detail' => $request->input('eventDetail'),
                'speaker_id' => $request->input('speakerId'),
            ]); // Commit transaction
            DB::commit();

            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public static function schedulerDelete(Request $request)
    {
        try {
            $schedule = PreferenceSchedule::findOrFail($request->input('schedulerId'));

            $schedule->delete();

            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function accessView(Request $request)
    {
        // Fetch list of modules and their privileges
        $list_of_modules = PreferenceAccess::orderBy('access_module', 'ASC')->get();

        $data_array1 = [];
        foreach ($list_of_modules as $list_of_module) {
            $list_privileges = explode('/', $list_of_module->access_privilege);

            foreach ($list_privileges as $list_privilege) {
                $data_array1[] = [
                    'module' => $list_of_module->access_module,
                    'features' => $list_of_module->access_permission,
                    'permission' => $list_privilege,
                ];
            }
        }

        // Fetch access pages
        $access_pages = UserAccess::orderBy('access_name', 'ASC')->get();

        $data_array2 = [];
        foreach ($access_pages as $access_page) {
            $data_array2[] = [
                'id' => $access_page->id,
                'permission_name' => $access_page->access_name,
                'access_privilege' => $access_page->access_privilege,
                'access_status' => strval($access_page->access_status),
                'permission_status' => $access_page->access_status,
                'trainer_status' => $access_page->trainer_status,
                'seller_status' => $access_page->seller_status,
            ];
        }

        return response()->json(
            [
                'message' => 'success',
                'data' => $data_array2,
                'permission_module' => $data_array1,
            ],
            200,
        );
    }
    public function accessAdd(Request $request): JsonResponse
    {
        try {
            // Ensure all required inputs are present
            $requiredFields = ['permissionName', 'permissionPrivilege', 'permissionStatus', 'permissionTrainerStatus', 'permissionSellerStatus'];

            foreach ($requiredFields as $field) {
                if (!$request->has($field)) {
                    return response()->json(
                        [
                            'message' => 'Missing field: ' . $field,
                        ],
                        422,
                    );
                }
            }

            // Create a new UserAccess entry
            $userAccess = UserAccess::create([
                'access_name' => $request->input('permissionName'),
                'access_privilege' => $request->input('permissionPrivilege'),
                'access_status' => $request->input('permissionStatus'),
                'trainer_status' => $request->input('permissionTrainerStatus'),
                'seller_status' => $request->input('permissionSellerStatus'),
            ]);

            // Return success response
            return response()->json(['message' => 'success'], 201);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public static function accessEdit(Request $request)
    {
        try {
            $userAccess = UserAccess::findOrFail($request->input('permissionId'));

            $userAccess->update([
                'access_name' => $request->input('permissionName'),
                'access_privilege' => $request->input('permissionPrivilege'),
                'access_status' => $request->input('permissionStatus'),
                'trainer_status' => $request->input('permissionTrainerStatus'),
                'seller_status' => $request->input('permissionSellerStatus'),
            ]);

            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public static function accessDelete(Request $request)
    {
        try {
            $userAccess = UserAccess::findOrFail($request->input('permissionId'));

            $userAccess->update(['access_status' => 2]);

            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function packageView(Request $request)
    {
        $authorizationHeader = $request->header('Authorization');

        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();

        if (!$checkToken) {
            return response()->json(
                [
                    'message' => 'Unauthorized',
                ],
                401,
            );
        }
        // Fetch all packages, ordered by pack_name
        $list_packages = PreferencePackage::orderBy('pack_name', 'ASC')->get();

        // Transform the data
        $data_array = $list_packages->map(function ($list_package) {
            return [
                'id' => $list_package->id,
                'pack_img_icon' => 'https://s3-asset-system.s3.ap-southeast-1.amazonaws.com/hb-crm/image_client_detail/' . $list_package->pack_img_icon,
                'pack_img_banner' => 'https://s3-asset-system.s3.ap-southeast-1.amazonaws.com/hb-crm/image_client_detail/' . $list_package->pack_img_banner,
                'pack_name' => $list_package->pack_name,
                'pack_price' => $list_package->pack_price,
                'pack_detail' => $list_package->pack_detail,
                'pack_intake_start' => $list_package->pack_intake_start,
                'pack_intake_end' => $list_package->pack_intake_end,
                'pack_class_quo' => $list_package->pack_class_quo,
                'pack_status' => strval($list_package->pack_status),
            ];
        });

        // Return a JSON response
        return response()->json(
            [
                'message' => 'success',
                'data' => $data_array,
            ],
            200,
        );
    }
    public function packageAdd(Request $request)
    {
        $image1_filepath = null;
        $image2_filepath = null;

        if ($request->file('photo1')) {
            $file1 = $request->file('photo1')[0]['file'];
            $filename1 = 'img_icon-' . now()->format('Y-m-d-H-i-s') . '.' . $file1->getClientOriginalExtension();
            Storage::disk('s3')->put('hb-crm/image_client_detail/' . $filename1, file_get_contents($file1));
            $image1_filepath = $filename1;
        }

        if ($request->file('photo2')) {
            $file2 = $request->file('photo2')[0]['file'];
            $filename2 = 'img_banner-' . now()->format('Y-m-d-H-i-s') . '.' . $file2->getClientOriginalExtension();
            Storage::disk('s3')->put('hb-crm/image_client_detail/' . $filename2, file_get_contents($file2));
            $image2_filepath = $filename2;
        }

        $package = new PreferencePackage();
        if ($image1_filepath) {
            $package->pack_img_icon = $image1_filepath;
        }
        if ($image2_filepath) {
            $package->pack_img_banner = $image2_filepath;
        }
        $package->pack_name = $request->input('packageName');
        $package->pack_price = $request->input('packagePrice');
        $package->pack_detail = $request->input('packageDetail');
        $package->pack_class_quo = $request->input('packageClass');
        $package->pack_intake_start = $request->input('packageIntakeStart');
        $package->pack_intake_end = $request->input('packageIntakeEnd');
        $package->pack_status = $request->input('packageStatus');
        // $package->pack_img_icon = '';
        // $package->pack_img_banner = '';

        $package->save();

        return response()->json(['message' => 'success'], 200);
    }
    public function packageEdit(Request $request)
    {
        $filename1 = null;
        $filename2 = null;

        if ($request->file('photo1')) {
            $file1 = $request->file('photo1')[0]['file'];
            $filename1 = 'img_icon-' . now()->format('Y-m-d-H-i-s') . '.' . $file1->getClientOriginalExtension();
            Storage::disk('s3')->put('hb-crm/image_client_detail/' . $filename1, file_get_contents($file1));
            $image1_filepath = $filename1;
        }

        if ($request->file('photo2')) {
            $file2 = $request->file('photo2')[0]['file'];
            $filename2 = 'img_banner-' . now()->format('Y-m-d-H-i-s') . '.' . $file2->getClientOriginalExtension();
            Storage::disk('s3')->put('hb-crm/image_client_detail/' . $filename2, file_get_contents($file2));
            $image2_filepath = $filename2;
        }

        $data_form = $request->all();

        $updateData = [
            'pack_name' => $data_form['packageName'],
            'pack_price' => $data_form['packagePrice'],
            'pack_detail' => $data_form['packageDetail'],
            'pack_class_quo' => $data_form['packageClass'],
            'pack_intake_start' => $data_form['packageIntakeStart'],
            'pack_intake_end' => $data_form['packageIntakeEnd'],
            'pack_status' => $data_form['packageStatus'],
        ];

        if ($filename1) {
            $updateData['pack_img_icon'] = $filename1;
        }
        if ($filename2) {
            $updateData['pack_img_banner'] = $filename2;
        }

        PreferencePackage::where('id', $data_form['packageId'])->update($updateData);

        return response()->json(['message' => 'success'], 200);
    }
    public function packageDelete(Request $request)
    {
        $deleted = DB::table('preference_package')
            ->where('id', $request->packageId)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'success'], 200);
        } else {
            return response()->json(['message' => 'failed to delete package'], 400);
        }
    }

    public function classView(Request $request)
    {
        try {
            $list_classes = PreferenceClass::orderBy('class_date_start', 'DESC')->get();

            $data_array1 = $list_classes->map(function ($class) {
                return [
                    'id' => $class->id,
                    'class_name' => $class->class_name,
                    'class_badge' => $class->class_badge,
                    'class_img' => $class->class_img,
                    'class_date_start' => $class->class_date_start,
                    'class_date_end' => $class->class_date_end,
                    'class_detail' => $class->class_detail,
                    'class_cap' => $class->class_cap,
                    'class_location' => $class->class_location,
                    'class_trainer' => $class->class_trainer,
                    'class_status' => strval($class->class_status),
                ];
            });

            $list_users = UserMain::whereHas('userAccess', function ($query) {
                $query->where('trainer_status', '1');
            })
                ->where('client_detail_id', '0')
                ->with([
                    'userDetail' => function ($query) {
                        $query->select('id', 'usr_fname', 'usr_lname');
                    },
                ])
                ->get(['id as usr_main_id']);

            $data_array2 = $list_users->map(function ($user) {
                return [
                    'usr_main_id' => $user->usr_main_id,
                    'first_name' => $user->usr_fname,
                    'last_name' => $user->usr_lname,
                ];
            });

            return response()->json(
                [
                    'message' => 'success',
                    'data' => $data_array1,
                    'trainer' => $data_array2,
                ],
                200,
            );
        } catch (\Exception $e) {
            Log::error('Error in classView method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'message' => 'Internal Server Error',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function classAdd(Request $request)
    {
        try {
            $preferenceClass = new PreferenceClass();
            $preferenceClass->class_name = $request->input('className');
            $preferenceClass->class_date_start = $request->input('classDateStart');
            $preferenceClass->class_date_end = $request->input('classDateEnd');
            $preferenceClass->class_badge = $request->input('classBadge');
            $preferenceClass->class_detail = $request->input('classDetails');
            $preferenceClass->class_cap = $request->input('classCapacity');
            $preferenceClass->class_location = $request->input('classLocation');
            $preferenceClass->class_trainer = $request->input('classTrainer');
            $preferenceClass->class_status = $request->input('classStatus');
            $preferenceClass->class_img = '';
            $preferenceClass->save();

            return response()->json(
                [
                    'message' => 'success',
                ],
                200,
            );
        } catch (\Exception $e) {
            log::error('Error in classAdd method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(
                [
                    'message' => 'Internal Server Error',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }
    public function classEdit(Request $request)
    {
        try {
            $preferenceClass = PreferenceClass::find($request->input('classId'));
            $preferenceClass->class_name = $request->input('className');
            $preferenceClass->class_badge = $request->input('classBadge');
            $preferenceClass->class_date_start = $request->input('classDateStart');
            $preferenceClass->class_date_end = $request->input('classDateEnd');
            $preferenceClass->class_detail = $request->input('classDetails');
            $preferenceClass->class_cap = $request->input('classCapacity');
            $preferenceClass->class_location = $request->input('classLocation');
            $preferenceClass->class_trainer = $request->input('classTrainer');
            $preferenceClass->class_status = $request->input('classStatus');
            $preferenceClass->save();
            return response()->json(
                [
                    'message' => 'success',
                ],
                200,
            );
        } catch (\Exception $e) {
            Log::error('Error in classEdit method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'message' => 'Internal Server Error',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }
    public function classDelete(Request $request)
    {
        try {
            $preferenceClass = PreferenceClass::find($request->input('classId'));

            if (!$preferenceClass) {
                return response()->json(
                    [
                        'message' => 'Class not found',
                    ],
                    404,
                );
            }

            $deleted = PreferenceClass::destroy($request->input('classId'));

            if ($deleted) {
                return response()->json(
                    [
                        'message' => 'success',
                    ],
                    200,
                );
            } else {
                return response()->json(
                    [
                        'message' => 'Failed to delete class',
                    ],
                    500,
                );
            }
        } catch (\Exception $e) {
            Log::error('Error in classDelete method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'message' => 'Internal Server Error',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }
    public function eventView(Request $request)
    {
        return 'event view';
    }

    public function eventAdd(Request $request)
    {
        return 'event add';
    }

    public function eventEdit(Request $request)
    {
        return 'event edit';
    }

    public function eventDelete(Request $request)
    {
        return 'event delete';
    }
}
