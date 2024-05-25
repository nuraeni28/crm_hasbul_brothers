<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRcdSales extends Model
{
    use HasFactory;

    protected $table = 'client_rcd_sales';

    protected $fillable = [
        'sales_record',
        'cash_reserve',
        'record_date'
    ];
}
