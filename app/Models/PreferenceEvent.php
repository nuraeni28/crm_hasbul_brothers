<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreferenceEvent extends Model
{
    use HasFactory;
    protected $table = 'preference_event';

    protected $fillable = ['event_name', 'sales_funnel', 'req_name', 'req_email', 'req_no_phone', 'req_bus_niche', 'req_comp_name', 'req_comp_brand', 'event_status'];
}
