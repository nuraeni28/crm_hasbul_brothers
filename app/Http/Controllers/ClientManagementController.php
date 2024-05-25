<?php

namespace App\Http\Controllers;

use App\Models\ClientCompany;
use App\Models\ClientDetail;
use App\Models\ClientMain;
use App\Models\ClientPaymentBalance;
use App\Models\ClientRcdPackage;
use App\Models\CLientRcdPhoto;
use App\Models\ClientRcdSales;
use App\Models\ClientRcdSuccessStory;
use App\Models\UserMain;
use App\Models\ProspectDetail;
use App\Models\PreferencePackage;
use App\Models\UserToken;
use Aws\S3\S3Client;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Psy\Readline\Userland;

class ClientManagementController extends Controller
{
    public function dataView(Request $request)
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }

        $clientLists = ClientMain::with([
            'clientDetail',
            'clientCompany',
            'clientRcdPackage',
            'clientPaymentBalance' => function ($query) {
                $query->where('payment_iteration', 1);
            }
        ])
        ->where('client_status', '!=', 5)
        ->get();

        $dataArray1 = $clientLists->map(function ($client) {
            $paymentAmount = $paymentDate = $paymentRemarks = null;
            if ($client->client_payment == 0 && $client->clientPaymentBalance->isNotEmpty()) {
                $paymentBalance = $client->clientPaymentBalance->first();
                $paymentAmount = $paymentBalance->payment_amount;
                $paymentDate = $paymentBalance->payment_date;
                $paymentRemarks = $paymentBalance->payment_remarks;
            }
            return [
                'client_id' => $client->id,
                'prospect_id' => $client->prospect_detail_id??'',
                'full_name' => $client->clientDetail->full_name??'',
                'ic_number' => $client->clientDetail->ic_number??'',
                'contact_number' => $client->clientDetail->contact_number??'',
                'company' => $client->clientCompany->company_name?? '',
                'business_industry' => $client->clientCompany->niche_market??'',
                'brand_name' => $client->clientCompany->brand_name??'',
                'ssm_no' => $client->clientCompany->roc_number??'',
                'city' => $client->clientCompany->address_city??'',
                'state' => $client->clientCompany->address_state??'',
                'country' => $client->clientCompany->address_country??'',
                'postcode' => $client->clientCompany->address_postcode??'',
                'address_detail1' => $client->clientCompany->address_line1??'',
                'address_detail2' => $client->clientCompany->address_line2??'',
                'url_soc_twitter' => $client->clientCompany->company_x??'',
                'url_soc_fb' => $client->clientCompany->company_facebook??'',
                'url_soc_instagram' => $client->clientCompany->company_instagram??'',
                'url_soc_tiktok' => $client->clientCompany->company_tiktok??'',
                'package' => $client->clientRcdPackage->current_package??'',
                'period_intake' => $client->clientRcdPackage->date_subscribe??'',
                'period_end' => $client->clientRcdPackage->date_end??'',
                'client_status' => strval($client->client_status)??'',
                'sales_person' => $client->closed_by??'',
                'payment_status' => $client->client_payment??'',
                'payment_amount' => $client->clientRcdPackage->package_amount??'',
                'shirt_size' => $client->clientDetail->tshirt_size??'',
                'position' => $client->clientDetail->current_position??'',
                'deposit_remarks' => $paymentRemarks??'',
                'deposit_date' => $paymentDate??'',
                'deposit_amount' => $paymentAmount??'',
            ];
        });
       
        $listUsers = UserMain::with(['userDetail', 'userAccess'])
            ->whereHas('userAccess', function ($query) {
                $query->where('seller_status', 1);
            })
            ->where('client_detail_id', 0)
            ->get();
         
        $dataArray2 = $listUsers->map(function ($user) {
            return [
                "usr_main_id" => $user->id,
                "first_name" => $user->userDetail->usr_fname,
                "last_name" => $user->userDetail->usr_lname,
            ];
        });
        $listParticipants = ProspectDetail::orderBy('full_name')->get();

        $dataArray3 = $listParticipants->map(function ($participant) {
            return [
                "id" => $participant->id,
                "full_name" => $participant->full_name,
                "contact_number" => $participant->contact_number,
                "niche_market" => $participant->niche_market ??'',
                "brand_name" => $participant->brand_name,
            ];
        });

        $listPackages = PreferencePackage::where('pack_status', 0)->orderBy('pack_name')->get();

        $dataArray4 = $listPackages->map(function ($package) {
            return [
                "id" => $package->id,
                "pack_img_icon" => 'https://s3-asset-system.s3.ap-southeast-1.amazonaws.com/hb-crm/image_client_detail/' . $package->pack_img_icon,
                "pack_img_banner" => 'https://s3-asset-system.s3.ap-southeast-1.amazonaws.com/hb-crm/image_client_detail/' . $package->pack_img_banner,
                "pack_name" => $package->pack_name,
                "pack_price" => $package->pack_price,
                "pack_detail" => $package->pack_detail,
                "pack_intake_start" => $package->pack_intake_start,
                "pack_intake_end" => $package->pack_intake_end,
                "pack_class_quo" => $package->pack_class_quo,
                "pack_status" =>strval($package->pack_status),
            ];
        });

        return response()->json([
            'message' => 'success',
            'data' => $dataArray1,
            'seller' => $dataArray2,
            'participant' => $dataArray3,
            'package' => $dataArray4
        ], 200);
    }
    public function dataAdd(Request $request)
{
    $authorizationHeader = $request->header('Authorization');
    $token = str_replace('Bearer ', '', $authorizationHeader);
    $checkToken = UserToken::where('usr_token', $token)->first();
   
    if (!$checkToken) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 404);
    }

    $data = $request->all();

    $data['participantId'] = $request->input('participantId', null);

    $data['personal_detail']['full_name'] = $request->input('personal_detail.full_name', null);
    $data['personal_detail']['ic_number'] = $request->input('personal_detail.ic_number', null);
    $data['personal_detail']['contact_number'] = $request->input('personal_detail.contact_number', null);

    $data['company']['company_name'] = $request->input('company.company_name', null);
    $data['company']['position'] = $request->input('company.position', null);
    $data['company']['business_industry'] = $request->input('company.business_industry', null);
    $data['company']['brand_name'] = $request->input('company.brand_name', null);
    $data['company']['ssm_no'] = $request->input('company.ssm_no', null);
    $data['company']['address_detail'] = $request->input('company.address_detail', null);
    $data['company']['postcode'] = $request->input('company.postcode', null);
    $data['company']['state'] = $request->input('company.state', null);
    $data['company']['city'] = $request->input('company.city', null);
    $data['company']['country'] = "119"; // Assuming 'country' always has the same value
    $data['company']['url_soc_twitter'] = $request->input('company.url_soc_twitter', null);
    $data['company']['url_soc_fb'] = $request->input('company.url_soc_fb', null);
    $data['company']['url_soc_instagram'] = $request->input('company.url_soc_instagram', null);
    $data['company']['url_soc_tiktok'] = $request->input('company.url_soc_tiktok', null);

    $data['package']['package_name'] = $request->input('package.package_name', null);
    $data['package']['period_intake'] = $request->input('package.period_intake', null);
    $data['package']['period_end'] = $request->input('package.period_end', null);

    $data['package']['client_status'] = $request->input('package.client_status', null);
    $data['package']['sales_person'] = $request->input('package.sales_person', null);
    $data['package']['payment_status'] = $request->input('package.payment_status', null);
    $data['package']['payment_amount'] = $request->input('package.payment_amount', null);
    $data['package']['shirt_size'] = $request->input('package.shirt_size', null);

    $data['package']['deposit_amount'] = $request->input('package.deposit_amount', null);
    $data['package']['deposit_date'] = $request->input('package.deposit_date', null);
    $data['package']['deposit_remarks'] = $request->input('package.deposit_remarks', null);

    // Insert data into the database
    $clientMain = new ClientMain();
    $clientMain->client_status = $data['package']['client_status'];
    $clientMain->client_payment = $data['package']['payment_status'];
    $clientMain->prospect_detail_id = $data['participantId'];
    $clientMain->closed_by = $data['package']['sales_person'];
    $clientMain->save();

    $clientMainId = $clientMain->id;

    if ($data['package']['payment_status'] == "0") {
        $clientPaymentBalance = new ClientPaymentBalance();
        $clientPaymentBalance->client_main_id = $clientMainId;
        $clientPaymentBalance->payment_amount = $data['package']['deposit_amount'];
        $clientPaymentBalance->payment_iteration = '1';
        $clientPaymentBalance->payment_date = $data['package']['deposit_date'];
        $clientPaymentBalance->payment_remarks = $data['package']['deposit_remarks'];
        $clientPaymentBalance->save();
    }

    $clientDetail = new ClientDetail();
    $clientDetail->client_main_id = $clientMainId;
    $clientDetail->full_name = $data['personal_detail']['full_name'];
    $clientDetail->ic_number = $data['personal_detail']['ic_number'];
    $clientDetail->contact_number = $data['personal_detail']['contact_number'];
    $clientDetail->current_position = $data['company']['position'];
    $clientDetail->tshirt_size = $data['package']['shirt_size'];
    $clientDetail->save();

    $clientCompany = new ClientCompany();
    $clientCompany->client_main_id = $clientMainId;
    $clientCompany->company_name = $data['company']['company_name']??'';
    $clientCompany->roc_number = $data['company']['ssm_no']??'';
    $clientCompany->brand_name = $data['company']['brand_name']??'';
    $clientCompany->niche_market = $data['company']['business_industry']??'';
    $clientCompany->address_line1 = $data['company']['address_detail']??'';
    $clientCompany->address_line2 = '';
    $clientCompany->address_postcode = $data['company']['postcode']??'';
    $clientCompany->address_state = $data['company']['state']??'';
    $clientCompany->address_city = $data['company']['city']??'';
    $clientCompany->address_country = $data['company']['country']??'';
    $clientCompany->company_facebook = $data['company']['url_soc_fb']??'';
    $clientCompany->company_instagram = $data['company']['url_soc_instagram']??'';
    $clientCompany->company_tiktok = $data['company']['url_soc_tiktok']??'';
    $clientCompany->company_x = $data['company']['url_soc_twitter']??'';
    $clientCompany->save();

    $currentDatetime = now()->format('Y-m-d H:i:s');
    $dataArray1[$currentDatetime] = [
        "date_subscribe" => $data['package']['period_intake'],
        "date_end" => $data['package']['period_end'],
        "current_package" => $data['package']['package_name'],
        "package_amount" => $data['package']['payment_amount'],
    ];

    $clientRcdPackage = new ClientRcdPackage();
    $clientRcdPackage->client_main_id = $clientMainId;
    $clientRcdPackage->date_subscribe = $data['package']['period_intake'];
    $clientRcdPackage->date_end = $data['package']['period_end'];
    $clientRcdPackage->current_package = $data['package']['package_name'];
    $clientRcdPackage->package_amount = $data['package']['payment_amount'];
    $clientRcdPackage->history_package = json_encode($dataArray1);
    $clientRcdPackage->save();

    // Send success response
    return response()->json(['message' => 'success'], 200);
}
public function dataEdit(Request $request)
{
    $authorizationHeader = $request->header('Authorization');
    $token = str_replace('Bearer ', '', $authorizationHeader);
    $checkToken = UserToken::where('usr_token', $token)->first();
   
    if (!$checkToken) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 404);
    }

    $clientId = $request->input('clientId');
    $participantId = $request->input('participantId');
    $package = $request->input('package');
    $personalDetail = $request->input('personal_detail');
    $company = $request->input('company');

    $clientMain = ClientMain::findOrFail($clientId);
    $clientMain->client_status = $package['client_status'];
    $clientMain->client_payment = $package['payment_status'];
    $clientMain->prospect_detail_id = $participantId;
    $clientMain->closed_by = $package['sales_person'];
    $clientMain->save();

    $clientDetail = ClientDetail::where('client_main_id', $clientId)->firstOrFail();
    $clientDetail->full_name = $personalDetail['full_name'];
    $clientDetail->ic_number = $personalDetail['ic_number'];
    $clientDetail->contact_number = $personalDetail['contact_number'];
    $clientDetail->current_position = $company['position'];
    $clientDetail->tshirt_size = $package['shirt_size'];
    $clientDetail->save();

    $clientCompany = ClientCompany::where('client_main_id', $clientId)->firstOrFail();
    $clientCompany->company_name = $company['company_name'];
    $clientCompany->roc_number = $company['ssm_no'];
    $clientCompany->brand_name = $company['brand_name'];
    $clientCompany->niche_market = $company['business_industry'];
    $clientCompany->address_line1 = $company['address_detail'];
    $clientCompany->address_postcode = $company['postcode'];
    $clientCompany->address_state = $company['state'];
    $clientCompany->address_city = $company['city'];
    $clientCompany->address_country = '119';
    $clientCompany->company_facebook = $company['url_soc_fb'];
    $clientCompany->company_instagram = $company['url_soc_instagram'];
    $clientCompany->company_tiktok = $company['url_soc_tiktok'];
    $clientCompany->company_x = $company['url_soc_twitter'];
    $clientCompany->save();

   
    $clientRcdPackage = ClientRcdPackage::where('client_main_id', $clientId)->firstOrFail();
    $clientRcdPackage->date_subscribe = $package['period_intake'];
    $clientRcdPackage->date_end = $package['period_end'];
    $clientRcdPackage->current_package = $package['package_name'];
    $clientRcdPackage->package_amount = $package['payment_amount'];

    $historyPackage = json_decode($clientRcdPackage->history_package, true);
    $currentDatetime = now()->format('Y-m-d H:i:s');
    $historyPackage[$currentDatetime] = [
        "date_subscribe" => $package['period_intake'],
        "date_end" => $package['period_end'],
        "current_package" => $package['package_name'],
        "package_amount" => $package['payment_amount'],
    ];
    $clientRcdPackage->history_package = json_encode($historyPackage);

    $clientRcdPackage->save();

    // Send success response
    return response()->json(['message' => 'success'], 200);
}
public function dataDelete(Request $request)
{
    $authorizationHeader = $request->header('Authorization');
    $token = str_replace('Bearer ', '', $authorizationHeader);
    $checkToken = UserToken::where('usr_token', $token)->first();
   
    if (!$checkToken) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 404);
    }
    $clientId = $request->input('clientId');

    ClientDetail::where('client_main_id', $clientId)->delete();
    ClientPaymentBalance::where('client_main_id', $clientId)->delete();
    ClientCompany::where('client_main_id', $clientId)->delete();
    ClientRcdPackage::where('client_main_id', $clientId)->delete();
    ClientMain::where('id', $clientId)->delete();

    return response()->json(['message' => 'success'], 200);
}
public function dataUpdateStatus(Request $request)
{
    $authorizationHeader = $request->header('Authorization');
    $token = str_replace('Bearer ', '', $authorizationHeader);
    $checkToken = UserToken::where('usr_token', $token)->first();
   
    if (!$checkToken) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 404);
    }
    $clientId = $request->input('client_id');
    $clientStatus = $request->input('client_status');

    ClientMain::where('id', $clientId)->update(['client_status' => $clientStatus]);

    return response()->json(['message' => 'success'], 200);
}


    public function dashboardView(Request $request)
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $clientId = $request->client_id;
        $currentYear = date('Y');
        
        // Fetch package data
        $packages = PreferencePackage::where('pack_status', 0)->orderBy('pack_name')->get();
        $data_array2 = $packages->map(function ($package) {
            return [
                "id" => $package->id,
                "pack_img_icon" => 'https://s3-asset-system.s3.ap-southeast-1.amazonaws.com/hb-crm/image_client_detail/' . $package->pack_img_icon,
                "pack_img_banner" => 'https://s3-asset-system.s3.ap-southeast-1.amazonaws.com/hb-crm/image_client_detail/' . $package->pack_img_banner,
                "pack_name" => $package->pack_name,
                "pack_price" => $package->pack_price,
                "pack_detail" => $package->pack_detail,
                "pack_intake_start" => $package->pack_intake_start,
                "pack_intake_end" => $package->pack_intake_end,
                "pack_class_quo" => $package->pack_class_quo,
                "pack_status" => $package->pack_status,
            ];
        });

        // Fetch client package details
        $clientPackage = ClientRcdPackage::where('client_main_id', $clientId)->first();
        $clientPackageDetail = PreferencePackage::find($clientPackage->current_package);

        // Fetch client details
        $clientDetail = ClientDetail::where('client_main_id', $clientId)->first();

        // Fetch client company details
        $clientCompany = ClientCompany::where('client_main_id', $clientId)->first();

        // Fetch client sales records
        $clientRecords = ClientRcdSales::where('client_main_id', $clientId)
            ->whereYear('record_date', $currentYear)
            ->get();

        $clientRecordSalesTotal = $clientRecords->sum('sales_record');
        $clientRecordReserveTotal = $clientRecords->sum('cash_reserve');

        // Fetch client's latest reserve
        $clientLatestReserve = ClientRcdSales::where('client_main_id', $clientId)
            ->orderBy('id', 'asc')
            ->first();

        $clientTotalReserve = $clientLatestReserve ? $clientLatestReserve->cash_reserve : 0;

        // Fetch client's total achievement
        $clientTotalAchievement = ClientRcdSuccessStory::where('client_main_id', $clientId)->count();

        // Fetch client payment details
        $clientPayment = ClientMain::find($clientId);
        $depositRecord = null;
        if ($clientPayment && $clientPayment->client_payment == 0) {
            $depositRecord = ClientPaymentBalance::where('client_main_id', $clientId)
                ->orderBy('payment_iteration', 'asc')
                ->get();
        }

        return response()->json([
            'message' => 'success',
            'package' => $data_array2,
            'data' => [
                'current_package' => $clientPackageDetail->pack_name,
                'package_amount' => $clientPackage->package_amount,
                'package_banner_img' => 'https://s3-asset-system.s3.ap-southeast-1.amazonaws.com/hb-crm/image_client_detail/' . $clientPackageDetail->pack_img_banner,
                'history_package' => $clientPackage->history_package,
                'profile_pic' => 'https://s3-asset-system.s3.ap-southeast-1.amazonaws.com/hb-crm/image_client_detail/' . $clientDetail->profile_pic,
                'full_name' => $clientDetail->full_name,
                'ic_number' => $clientDetail->ic_number,
                'contact_number' => $clientDetail->contact_number,
                'current_position' => $clientDetail->current_position,
                'company_name' => $clientCompany->company_name,
                'roc_number' => $clientCompany->roc_number,
                'brand_name' => $clientCompany->brand_name,
                'niche_market' => $clientCompany->niche_market,
                'address_detail' => $clientCompany->address_line1,
                'address_postcode' => $clientCompany->address_postcode,
                'address_state' => $clientCompany->address_state,
                'address_city' => $clientCompany->address_city,
                'company_facebook' => $clientCompany->company_facebook,
                'company_instagram' => $clientCompany->company_instagram,
                'company_tiktok' => $clientCompany->company_tiktok,
                'company_x' => $clientCompany->company_x,
                'sales_record' => $clientRecordSalesTotal,
                'reserve_record' => $clientRecordReserveTotal,
                'total_reserved' => $clientTotalReserve,
                'total_achievement' => $clientTotalAchievement,
                'payment_type' => $clientPayment->client_payment,
                'payment_record' => $depositRecord,
            ]
        ], 200);
    }

    public function salesView(Request $request)
{
    $authorizationHeader = $request->header('Authorization');
    $token = str_replace('Bearer ', '', $authorizationHeader);
    $checkToken = UserToken::where('usr_token', $token)->first();
   
    if (!$checkToken) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 404);
    }
    $clientId = $request->input('client_id');

    $clientSalesRecords = ClientRcdSales::where('client_main_id', $clientId)
                                        ->orderBy('record_date', 'ASC')
                                        ->get();
    $result = [];

    foreach ($clientSalesRecords as $record) {
        $date = date_create_from_format('Y-m-d', $record->record_date);
        $year = date_format($date, 'Y');
        $month = date_format($date, 'M');

        $monthSalesEntry = [
            'table_id' => $record->id,
            'month' => $month,
            'sales' => $record->sales_record ?? 0,
            'cash_reserve' => $record->cash_reserve ?? 0,
        ];

        if (!isset($result[$year])) {
            $result[$year] = [];
        }

        if (!isset($result[$year][$month])) {
            $result[$year][$month] = [];
        }

        $result[$year][$month][] = $monthSalesEntry;
    }

    return response()->json(['message' => 'success', 'data' => $result], 200);
}
    public function salesAdd(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $clientId = $request->input('client_id');
        $currentYear = Carbon::now()->year;
        $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];

        $clientRecordYears = DB::table('client_rcd_sales')
            ->select('id')
            ->where('client_main_id', $clientId)
            ->whereYear('record_date', $currentYear)
            ->first();

        if (!$clientRecordYears) {
            foreach ($months as $month) {
                DB::table('client_rcd_sales')->insert([
                    'client_main_id' => $clientId,
                    'record_date' => "$currentYear-$month-01",
                    'sales_record' => '0.00',
                    'cash_reserve' => '0.00',
                ]);
            }
        } else {
            $clientLastYear = DB::table('client_rcd_sales')
                ->select(DB::raw('YEAR(record_date) as year'))
                ->where('client_main_id', $clientId)
                ->groupBy(DB::raw('YEAR(record_date)'))
                ->orderBy(DB::raw('YEAR(record_date)'), 'asc')
                ->first();

            $oldYear = $clientLastYear->year;
            $newYear = $oldYear - 1;

            foreach ($months as $month) {
                DB::table('client_rcd_sales')->insert([
                    'client_main_id' => $clientId,
                    'record_date' => "$newYear-$month-01",
                    'sales_record' => '0.00',
                    'cash_reserve' => '0.00',
                ]);
            }
        }

        return response()->json(['message' => 'success', 'data' => $request->all()], 200);
    }
    public function salesEdit(Request $request)
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $data = $request->input('monthSales');

        foreach ($data as $client_record_month) {
            ClientRcdSales::where('id', $client_record_month[0]['table_id'])
                ->update([
                    'sales_record' => $client_record_month[0]['sales'],
                    'cash_reserve' => $client_record_month[0]['cash_reserve']
                ]);
        }

        return response()->json(['message' => 'success'], 200);
    }
    public function salesDelete(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $clientId = $request->input('clientId');
        $year = $request->input('year');

        if (!is_numeric($clientId) || !is_numeric($year)) {
            return response()->json(['message' => 'Invalid input'], 400);
        }

        DB::table('client_rcd_sales')
            ->where('client_main_id', $clientId)
            ->whereYear('record_date', $year)
            ->delete();

        return response()->json(['message' => 'success'], 200);
    }
    public function successView(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $clientId = $request->input('client_id');

        $clientSuccessRecords = DB::table('client_rcd_success_story')
            ->where('client_main_id', $clientId)
            ->orderBy('success_date', 'desc')
            ->get();

        $data_array = [];
        foreach ($clientSuccessRecords as $record) {
            $successDate = Carbon::parse($record->success_date);

            $data_array[] = [
                'story_id' => $record->id,
                'date' => $successDate->format('Y-m-d'),
                'date_display' => $successDate->format('d/m/Y'),
                'title' => $record->success_title,
                'details' => $record->success_detail,
            ];
        }

        return response()->json([
            'message' => 'success',
            'data' => $data_array,
        ], 200);
    }
    public function successAdd(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $clientId = $request->input('client_id');
        $date = $request->input('date');
        $title = $request->input('title');
        $details = $request->input('details');

        DB::table('client_rcd_success_story')->insert([
            'client_main_id' => $clientId,
            'success_date' => $date,
            'success_title' => $title,
            'success_detail' => $details,
        ]);

        return response()->json(['message' => 'success'], 200);
    }

    public function successEdit(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $storyId = $request->input('story_id');
        $date = $request->input('date');
        $title = $request->input('title');
        $details = $request->input('details');

        DB::table('client_rcd_success_story')
            ->where('id', $storyId)
            ->update([
                'success_date' => $date,
                'success_title' => $title,
                'success_detail' => $details,
            ]);

        return response()->json(['message' => 'success'], 200);
    }
    public function successDelete(Request $request): JsonResponse
{
    $authorizationHeader = $request->header('Authorization');
    $token = str_replace('Bearer ', '', $authorizationHeader);
    $checkToken = UserToken::where('usr_token', $token)->first();
   
    if (!$checkToken) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 404);
    }
    $storyId = $request->input('story_id');

    $successStory = DB::table('client_rcd_success_story')->where('id', $storyId)->first();
    
    if (!$successStory) {
        return response()->json(['message' => 'Success story not found'], 404);
    }

    DB::table('client_rcd_success_story')->where('id', $storyId)->delete();

    return response()->json(['message' => 'success'], 200);
}

    public function progressView(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $clientId = $request->input('client_id');

        $clientProgressRecords = DB::table('client_rcd_progress')
            ->where('client_main_id', $clientId)
            ->orderBy('progress_date', 'desc')
            ->get();

        $data_array = [];
        foreach ($clientProgressRecords as $record) {
            $progressDate = Carbon::parse($record->progress_date);

            $data_array[] = [
                'progress_id' => $record->id,
                'role' => $record->usr_access_id,
                'date' => $progressDate->format('Y-m-d'),
                'date_display' => $progressDate->format('d/m/Y'),
                'speaker' => $record->speaker_pic,
                'current_issues' => $record->current_issue,
                'solution' => $record->current_solution,
            ];
        }

        return response()->json([
            'message' => 'success',
            'data' => $data_array,
        ], 200);
    }
    public function progressAdd(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $clientId = $request->input('client_id');
        $role = $request->input('role');
        $date = $request->input('date');
        $speaker = $request->input('speaker');
        $currentIssues = $request->input('current_issues');
        $solution = $request->input('solution');

        DB::table('client_rcd_progress')->insert([
            'client_main_id' => $clientId,
            'usr_access_id' => $role,
            'progress_date' => $date,
            'speaker_pic' => $speaker,
            'current_issue' => $currentIssues,
            'current_solution' => $solution,
        ]);

        return response()->json(['message' => 'success'], 200);
    }

    public function progressEdit(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $progressId = $request->input('progress_id');
        $role = $request->input('role');
        $date = $request->input('date');
        $speaker = $request->input('speaker');
        $currentIssues = $request->input('current_issues');
        $solution = $request->input('solution');

        DB::table('client_rcd_progress')
            ->where('id', $progressId)
            ->update([
                'usr_access_id' => $role,
                'progress_date' => $date,
                'speaker_pic' => $speaker,
                'current_issue' => $currentIssues,
                'current_solution' => $solution,
            ]);

        return response()->json(['message' => 'success'], 200);
    }
    public function progressDelete(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
        
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }

        $progressId = $request->input('progress_id');
        $progress = DB::table('client_rcd_progress')->where('id', $progressId)->first();
        
        if (!$progress) {
            return response()->json(['message' => 'Progress record not found'], 404);
        }

        DB::table('client_rcd_progress')->where('id', $progressId)->delete();

        return response()->json(['message' => 'success'], 200);
    }

    public function attendanceView(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $clientId = $request->input('client_id');

        $classLists = DB::table('preference_class')->orderBy('id', 'asc')->get();

        $data_array = [];
        foreach ($classLists as $classList) {
            $attendanceRecord = DB::table('client_rcd_attendance')
                ->where('client_main_id', $clientId)
                ->where('class_id', $classList->id)
                ->first();

            if ($attendanceRecord) {
                $status = 'Attend';
            } else {
                $status = 'Absent';
            }

            $data_array[] = [
                'class_name' => $classList->class_name,
                'date_display' => date('d/m/Y', strtotime($classList->class_date_start)),
                'status' => $status,
            ];
        }

        return response()->json([
            'message' => 'success',
            'data' => $data_array,
        ], 200);
    }
    public function btmView(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $clientId = $request->input('client_id');

        $clientBtmRecords = DB::table('client_rcd_btm')
            ->where('client_main_id', $clientId)
            ->first();

        if ($clientBtmRecords) {
            $plotRadar = [
                $clientBtmRecords->btm_hr,
                $clientBtmRecords->btm_leader_culture,
                $clientBtmRecords->btm_marketing_branding,
                $clientBtmRecords->btm_dig_marketing,
                $clientBtmRecords->btm_sales_cust,
                $clientBtmRecords->btm_rnd,
                $clientBtmRecords->btm_ops_proces,
                $clientBtmRecords->btm_acc_finance
            ];
            $dataBtm = [
                'btm_hr' => $clientBtmRecords->btm_hr,
                'btm_leader_culture' => $clientBtmRecords->btm_leader_culture,
                'btm_marketing_branding' => $clientBtmRecords->btm_marketing_branding,
                'btm_dig_marketing' => $clientBtmRecords->btm_dig_marketing,
                'btm_sales_cust' => $clientBtmRecords->btm_sales_cust,
                'btm_rnd' => $clientBtmRecords->btm_rnd,
                'btm_ops_proces' => $clientBtmRecords->btm_ops_proces,
                'btm_acc_finance' => $clientBtmRecords->btm_acc_finance
            ];
        } else {
            $dataBtm = [];
            $plotRadar = [];
        }

        return response()->json([
            'message' => 'success',
            'plot_radar' => $plotRadar,
            'data' => $dataBtm
        ], 200);
    }
    public function btmEdit(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $clientId = $request->input('client_id');

        $clientBtmRecords = DB::table('client_rcd_btm')
            ->where('client_main_id', $clientId)
            ->first();

        if ($clientBtmRecords) {
            $updateData = [
                'btm_hr' => $request->input('humanResources'),
                'btm_leader_culture' => $request->input('leadershipCulture'),
                'btm_marketing_branding' => $request->input('marketingBranding'),
                'btm_dig_marketing' => $request->input('digitalMarketing'),
                'btm_sales_cust' => $request->input('customerRelation'),
                'btm_rnd' => $request->input('researchDev'),
                'btm_acc_finance' => $request->input('accountFinance'),
                'btm_ops_proces' => $request->input('operationProcess'),
            ];

            DB::table('client_rcd_btm')
                ->where('client_main_id', $clientId)
                ->update($updateData);
        } else {
            // Handle case when no BTM record exists for the client
            // You can create a new record or handle it according to your application's logic
        }

        return response()->json(['message' => 'success'], 200);
    }
    public function photoView(Request $request): JsonResponse
    {
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }
        $clientId = $request->input('client_id');

        $clientPhotoRecords = DB::table('client_rcd_photo')
            ->where('client_main_id', $clientId)
            ->get();

        $photoCategories = $clientPhotoRecords->pluck('photo_category')->unique()->toArray();

        $data_array2 = [];
        foreach ($photoCategories as $category) {
            $data_array1 = [];

            foreach ($clientPhotoRecords as $clientPhotoRecord) {
                if ($clientPhotoRecord->photo_category == $category) {
                    $data_array1[] = [
                        "image_id" => $clientPhotoRecord->id,
                        "image_path" => 'https://s3-asset-system.s3.ap-southeast-1.amazonaws.com/hb-crm/image_client_detail/'.$clientPhotoRecord->img_path,
                    ];
                }
            }

            $data_array2[] = [
                "photo_category" => $category,
                "image" => $data_array1
            ];
        }

        return response()->json(['message' => 'success', 'data' => $data_array2], 200);
    }
    public function photoAdd(Request $request): JsonResponse
    {
        
        $authorizationHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $checkToken = UserToken::where('usr_token', $token)->first();
       
        if (!$checkToken) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 404);
        }

        $clientId = $request->input('clientId');
        $category = $request->input('category');
        $newCategory = $request->input('new_category');
        // dd($request->hasfile('photo'));
        if ( $request->file('photo')[0]['file']->isValid()) {
            $file = $request->file('photo')[0]['file'];
            $filename = 'photo_client_profile-' . date('Y-m-d-H-i-s') . '.' . $file->getClientOriginalExtension();
            // Storage::putFileAS(env('AWS_BUCKET_PATH_CLIENT_DETAILS_ADD'), $file, $filename);
            Storage::disk('s3')->put($filename,file_get_contents($file));
            // $this->uploadToS3($file, $filename);
          
        } else {
            return response()->json(['message' => 'No photo file provided'], 400);
        }

        if ($category === 'addCategory') {
            $category = $newCategory;
        }
        CLientRcdPhoto::create([
            'client_main_id' => $clientId,
            'photo_category' => $category,
            'img_path' => $filename,
        ]);

        return response()->json(['message' => 'success'], 200);
    }

    // private function uploadToS3($file, $filename)
    // {
    //     $s3 = new S3Client([
    //         'version' => 'latest',
    //         'region' => config('filesystems.disks.s3.region'),
    //         'credentials' => [
    //             'key' => config('filesystems.disks.s3.key'),
    //             'secret' => config('filesystems.disks.s3.secret'),
    //         ],
    //     ]);

    //     try {
    //         $result = $s3->putObject([
    //             'Bucket' => config('filesystems.disks.s3.bucket'),
    //             'Key' => env('AWS_BUCKET_PATH_CLIENT_DETAILS_ADD').$filename,
    //             'SourceFile' =>  $file->getFilename(),
    //         ]);

    //         // Log success or handle if needed
    //     } catch (\Exception $e) {
    //         // Log or handle error
    //         return response()->json(['message' => 'Error uploading file to S3'], 500);
    //     }
    // }


    
}
