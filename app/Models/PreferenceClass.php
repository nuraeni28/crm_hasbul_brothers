<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreferenceClass extends Model
{
    use HasFactory;

    protected $table = 'preference_class'; // Specify the table if not following convention

    // Define fillable properties or guarded properties if necessary
    protected $fillable = [
        'class_name', 'class_badge', 'class_img', 'class_date_start', 'class_date_end', 
        'class_detail', 'class_cap', 'class_location', 'class_trainer', 'class_status'
    ];
}
