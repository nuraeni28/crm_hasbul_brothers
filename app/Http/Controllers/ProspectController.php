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
                'id' => (string) $list_event->id,
                'event_name' => $list_event->event_name,
            ];
        }

        foreach ($list_classes as $list_class) {
            $dataArray2[] = [
                'id' => (string) $list_class->id,
                'full_name' => $list_class->full_name,
                'contact_number' => $list_class->contact_number,
                'niche_market' => $list_class->niche_market,
                'brand_name' => $list_class->brand_name,
                'event' => (string) $list_class->event_id,
                'prospect_status' => (string) $list_class->prospect_status,
                'sales_avg' => (string) $list_class->sales_avg,
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
            'pFullName' => 'required',
            'pBusinessIndustry' => 'required',
            'pContactNumber' => 'required',
            'pEventName' => 'required',
            'pBrandName' => 'required',
            'pSalesAverage' => 'required',
            'pStatus' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }
        // Begin transaction
        DB::beginTransaction();

        try {
            // Insert into prospect detail
            ProspectDetail::create([
                'full_name' => $request->pFullName,
                'contact_number' => $request->pContactNumber,
                'niche_market' => $request->pBusinessIndustry,
                'brand_name' => $request->pBrandName,
                'event_id' => $request->pEventName,
                'sales_avg' => $request->pSalesAverage,
                'prospect_status' => $request->pStatus,
            ]);

            // Commit transaction
            DB::commit();

            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            DB::rollBack();

            // Log the exception
            \Log::error('Error creating prospect detail : ' . $e->getMessage());

            return response()->json(['message' => 'Error creating prospect detail'], 500);
        }
    }

    public function listEdit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pFullName' => 'required',
            'pBusinessIndustry' => 'required',
            'pContactNumber' => 'required',
            'pEventName' => 'required',
            'pBrandName' => 'required',
            'pSalesAverage' => 'required',
            'pStatus' => 'required',
            'pId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }
        // Begin transaction
        DB::beginTransaction();

        try {
            // check prospect detail
            $checkId = ProspectDetail::where('id', $request->pId)->first();

            if (!$checkId) {
                return response()->json(['message' => 'prospect detail not found'], 440);
            }

            ProspectDetail::where('id', $request->pId)->update([
                'full_name' => $request->pFullName,
                'contact_number' => $request->pContactNumber,
                'niche_market' => $request->pBusinessIndustry,
                'brand_name' => $request->pBrandName,
                'event_id' => $request->pEventName,
                'sales_avg' => $request->pSalesAverage,
                'prospect_status' => $request->pStatus,
            ]);
            // Commit transaction
            DB::commit();

            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            DB::rollBack();

            // Log the exception
            \Log::error('Error edit prospect detail: ' . $e->getMessage());

            return response()->json(['message' => 'Error edit prospect'], 500);
        }
    }
}
