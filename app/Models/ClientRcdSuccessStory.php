<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRcdSuccessStory extends Model
{
    use HasFactory;

    protected $table = 'client_rcd_success_story';

    protected $fillable = [
        'client_main_id'
    ];
}
