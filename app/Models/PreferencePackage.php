<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreferencePackage extends Model
{
    use HasFactory;

    protected $table = 'preference_package';

    protected $fillable = ['pack_img_icon', 'pack_img_banner', 'pack_name', 'pack_price', 'pack_detail', 'pack_intake_start', 'pack_intake_end', 'pack_class_quo', 'pack_status'];

    
}
