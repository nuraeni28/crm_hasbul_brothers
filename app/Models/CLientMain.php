<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientMain extends Model
{
    use HasFactory;

    protected $fillable = [
        'closed_by',
        'client_status',
        'client_payment',
        'prospect_detail_id',
    ];

    public function clientDetail()
    {
        return $this->hasOne(ClientDetail::class);
    }

    public function clientCompany()
    {
        return $this->hasOne(ClientCompany::class);
    }

    public function clientRcdPackage()
    {
        return $this->hasOne(ClientRcdPackage::class);
    }

    public function clientPaymentBalance()
    {
        return $this->hasMany(ClientPaymentBalance::class);
    }
}
