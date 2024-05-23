<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{
    use HasFactory;

    protected $table = 'usr_outh_token';
    protected $fillable = ['usr_token', 'usr_main_id', 'expired_at'];
    public $timestamps = false;
}
