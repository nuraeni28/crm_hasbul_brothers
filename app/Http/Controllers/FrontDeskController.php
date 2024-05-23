<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientRcdPackage;
use App\Models\ClientRcdAttendance;
use App\Models\ClientMain;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FrontDeskController extends Controller
{
    public function listView(Request $request)
    {
        $classId = $request->class_id;
        $clientsAttendClass = ClientMain::whereHas('clientAttendance', function ($query) use ($classId) {
            $query->where('class_id', $classId);
        })->get();

        $dataArray1 = [];
        $dataArray2 = [];
        $idsToExclude = [];

        if ($clientsAttendClass->isNotEmpty()) {
            $idsToExclude = $clientsAttendClass->pluck('id')->toArray();
        }

        //get client not attendace the class
        $clientLists = ClientMain::with(['clientDetail', 'clientCompany', 'clientRcdPackage.preferencePackage'])
            ->where('client_status', '!=', '5')
            ->when(!empty($idsToExclude), function ($query) use ($idsToExclude) {
                $query->whereNotIn('id', $idsToExclude);
            })
            ->get();

        foreach ($clientLists as $clientList) {
            $dataArray1[] = [
                'client_id' => $clientList->id,
                'attend_id' => '',
                'full_name' => $clientList->clientDetail->full_name ?? '',
                'contact_number' => $clientList->clientDetail->contact_number ?? '',
                'position' => $clientList->clientDetail->current_position ?? '',
                'tshirt_size' => $clientList->clientDetail->tshirt_size ?? '',
                'company' => $clientList->clientCompany->company_name ?? '',
                'brand_name' => $clientList->clientCompany->brand_name ?? '',
                'pack_img_icon' => optional($clientList->clientRcdPackage)->preferencePackage ? 'https://s3-asset-system.s3.ap-southeast-1.amazonaws.com/hb-crm/image_client_detail/' . $clientList->clientRcdPackage->preferencePackage->pack_img_icon : '',
                'attendance_status' => '1',
            ];
        }

        //get client attendace the class
        if (!empty($idsToExclude)) {
            $attendanceLists = ClientMain::with(['clientDetail', 'clientCompany', 'clientRcdPackage.preferencePackage', 'attendance'])
                ->where('client_status', '!=', '5')
                ->whereIn('id', $idsToExclude)
                ->whereHas('attendance', function ($query) use ($classId) {
                    $query->where('class_id', $classId);
                })
                ->get();

            foreach ($attendanceLists as $attendanceList) {
                $dataArray2[] = [
                    'client_id' => $attendanceList->id,
                    'attend_id' => $attendanceList->attendance->where('class_id', $classId)->first()->id ?? '',
                    'full_name' => $attendanceList->clientDetail->full_name ?? '',
                    'contact_number' => $attendanceList->clientDetail->contact_number ?? '',
                    'position' => $attendanceList->clientDetail->current_position ?? '',
                    'tshirt_size' => $attendanceList->clientDetail->tshirt_size ?? '',
                    'company' => $attendanceList->clientCompany->company_name ?? '',
                    'brand_name' => $attendanceList->clientCompany->brand_name ?? '',
                    'pack_img_icon' => $attendanceList->clientRcdPackage->preferencePackage->pack_img_icon ? 'https://s3-asset-system.s3.ap-southeast-1.amazonaws.com/hb-crm/image_client_detail/' . $attendanceList->clientRcdPackage->preferencePackage->pack_img_icon : '',
                    'attendance_status' => '0',
                ];
            }
        }
        return response()->json(
            [
                'message' => 'success',
                'list' => array_merge($dataArray1, $dataArray2),
            ],
            200,
        );
    }

    public function listAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'classId' => 'required',
            'clientId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }
        // Begin transaction
        DB::beginTransaction();

        try {
            // Insert into attendance
            ClientRcdAttendance::create([
                'class_id' => $request->classId,
                'client_main_id' => $request->clientId,
                'attend_date' => now(),
            ]);

            // Commit transaction
            DB::commit();

            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            DB::rollBack();

            // Log the exception
            \Log::error('Error creating attendance: ' . $e->getMessage());

            return response()->json(['message' => 'Error creating attendance'], 500);
        }
    }

    public function listEdit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }
        // Begin transaction
        DB::beginTransaction();

        try {
            // Idelete attendance
            ClientRcdAttendance::where('id', $request->attendId)->delete();

            // Commit transaction
            DB::commit();

            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            DB::rollBack();

            // Log the exception
            \Log::error('Error delete attendance : ' . $e->getMessage());

            return response()->json(['message' => 'Error delete attendance'], 500);
        }
    }
}
