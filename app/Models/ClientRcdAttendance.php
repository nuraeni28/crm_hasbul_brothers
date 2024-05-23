<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRcdAttendance extends Model
{
    use HasFactory;

    protected $table = 'client_rcd_attendance';
    protected $fillable = ['client_main_id', 'attend_date', 'class_id'];

    public function client()
    {
        return $this->belongsTo(ClientMain::class, 'client_main_id');
    }
}
