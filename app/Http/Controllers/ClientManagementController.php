<?php

namespace App\Http\Controllers;

use App\Models\ClientMain;
use App\Models\UserMain;
use App\Models\ProspectDetail;
use App\Models\PreferencePackage;
use Illuminate\Http\Request;

class ClientManagementController extends Controller
{
    public function dataView()
    {
        // Fetch client data
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
                'prospect_id' => $client->prospect_detail_id,
                'full_name' => $client->clientDetail->full_name,
                'ic_number' => $client->clientDetail->ic_number,
                'contact_number' => $client->clientDetail->contact_number,
                'company' => $client->clientCompany->company_name,
                'business_industry' => $client->clientCompany->niche_market,
                'brand_name' => $client->clientCompany->brand_name,
                'ssm_no' => $client->clientCompany->roc_number,
                'city' => $client->clientCompany->address_city,
                'state' => $client->clientCompany->address_state,
                'country' => $client->clientCompany->address_country,
                'postcode' => $client->clientCompany->address_postcode,
                'address_detail1' => $client->clientCompany->address_line1,
                'address_detail2' => $client->clientCompany->address_line2,
                'url_soc_twitter' => $client->clientCompany->company_x,
                'url_soc_fb' => $client->clientCompany->company_facebook,
                'url_soc_instagram' => $client->clientCompany->company_instagram,
                'url_soc_tiktok' => $client->clientCompany->company_tiktok,
                'package' => $client->clientRcdPackage->current_package,
                'period_intake' => $client->clientRcdPackage->date_subscribe,
                'period_end' => $client->clientRcdPackage->date_end,
                'client_status' => $client->client_status,
                'sales_person' => $client->closed_by,
                'payment_status' => $client->client_payment,
                'payment_amount' => $client->clientRcdPackage->package_amount,
                'shirt_size' => $client->clientDetail->tshirt_size,
                'position' => $client->clientDetail->current_position,
                'deposit_remarks' => $paymentRemarks,
                'deposit_date' => $paymentDate,
                'deposit_amount' => $paymentAmount,
            ];
        });

        // Fetch seller data
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

        // Fetch participant data
        $listParticipants = ProspectDetail::orderBy('full_name')->get();

        $dataArray3 = $listParticipants->map(function ($participant) {
            return [
                "id" => $participant->id,
                "full_name" => $participant->full_name,
                "contact_number" => $participant->contact_number,
                "niche_market" => $participant->niche_market,
                "brand_name" => $participant->brand_name,
            ];
        });

        // Fetch package data
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
                "pack_status" => $package->pack_status,
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
}
