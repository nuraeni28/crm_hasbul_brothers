<?php

namespace App\Http\Controllers;

use App\Models\PreferenceEvent;
use App\Models\ProspectDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProspectController extends Controller
{
    public function listView(Request $request)
    {
        $dataArray1 = [];
        $dataArray2 = [];

        $list_classes = ProspectDetail::orderBy('full_name', 'asc')->get();
        $list_events = PreferenceEvent::orderBy('event_name', 'asc')->get();

        foreach ($list_events as $list_event) {
            $dataArray1[] = [
                'id' => $list_event->id,
                'event_name' => $list_event->event_name,
            ];
        }
        foreach ($list_classes as $list_class) {
            $dataArray1[] = [
                'id' => $list_class->id,
                'full_name' => $list_class->full_name,
                'contact_number' => $list_class->contact_number,
                'niche_market' => $list_event->niche_market,
                'brand_name' => $list_event->brand_name,
                'event' => $list_class->event_id,
                'prospect_status' => $list_class->prospect_status,
                'sales_avg' => $list_class['sales_avg'],
            ];
        }
        return response()->json(
            [
                'message' => 'success',
                'data' => $dataArray2,
                'events' => $dataArray1,
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
            \Log::error('Error creating user: ' . $e->getMessage());

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
            \Log::error('Error creating user: ' . $e->getMessage());

            return response()->json(['message' => 'Error creating attendance'], 500);
        }
    }
}
