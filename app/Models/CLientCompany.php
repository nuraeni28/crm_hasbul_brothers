<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCompany extends Model
{
     protected $table = 'client_company';
    use HasFactory;

    protected $fillable = [
        'client_main_id',
        'company_name',
        'niche_market',
        'brand_name',
        'roc_number',
        'address_city',
        'address_state',
        'address_country',
        'address_postcode',
        'address_line1',
        'address_line2',
        'company_x',
        'company_facebook',
        'company_instagram',
        'company_tiktok',
    ];

    public function clientMain()
    {
        return $this->belongsTo(ClientMain::class);
    }
}
