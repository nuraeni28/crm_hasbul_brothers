<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientRcdPackage;
use App\Models\ClientMain;

class FrontDeskController extends Controller
{
    public function listView(Request $request)
    {
        $classId = $request->class_id;
        $clientsAttendClass = ClientMain::whereHas('clientAttendance', function ($query) use ($classId) {
            $query->where('class_id', $classId);
        })->get();

        $dataArray2 = [];
        $dataArray3 = [];
        $idsToExclude = [];

        if ($clientsAttendClass->isNotEmpty()) {
            $idsToExclude = $clientsAttendClass->pluck('id')->toArray();
        }

        // Query untuk mendapatkan klien yang tidak menghadiri kelas
        $clientLists = ClientMain::with(['clientDetail', 'clientCompany', 'clientRcdPackage.preferencePackage'])
            ->where('client_status', '!=', '5')
            ->when(!empty($idsToExclude), function ($query) use ($idsToExclude) {
                $query->whereNotIn('id', $idsToExclude);
            })
            ->get();

        foreach ($clientLists as $clientList) {
            $dataArray2[] = [
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

        // Query untuk mendapatkan klien yang menghadiri kelas
        if (!empty($idsToExclude)) {
            $attendanceLists = ClientMain::with(['clientDetail', 'clientCompany', 'clientRcdPackage.preferencePackage', 'attendance'])
                ->where('client_status', '!=', '5')
                ->whereIn('id', $idsToExclude)
                ->whereHas('attendance', function ($query) use ($classId) {
                    $query->where('class_id', $classId);
                })
                ->get();

            foreach ($attendanceLists as $attendanceList) {
                $dataArray3[] = [
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
                'list' => array_merge($dataArray2, $dataArray3),
            ],
            200,
        );
    }
}
