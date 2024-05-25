<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CLientRcdPhoto extends Model
{
    use HasFactory;
    protected $table = 'client_rcd_photo';
    protected $fillable = [
        'client_main_id',
        'photo_category',
        'img_path',
    ];
}
