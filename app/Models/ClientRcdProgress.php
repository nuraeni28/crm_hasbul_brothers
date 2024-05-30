<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRcdProgress extends Model
{
    use HasFactory;

    protected $table = 'client_rcd_progress';

    protected $fillable = [
        'client_main_id',
        'usr_access_id',
        'progress_date',
        'speaker_pic',
        'current_issue',
        'current_solution',
    ];
}
