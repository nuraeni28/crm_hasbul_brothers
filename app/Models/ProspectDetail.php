<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProspectDetail extends Model
{
    use HasFactory;

    protected $table = 'prospect_detail';

    protected $fillable = ['event_id', 'full_name', 'contact_number', 'niche_market', 'sales_avg', 'prospect_status'];
}
