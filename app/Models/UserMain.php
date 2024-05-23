<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMain extends Model
{
    use HasFactory;

    protected $table = 'usr_main';
    protected $fillable = ['usr_acc_status', 'usr_login_id', 'usr_access_id', 'usr_detail_id', 'client_detail_id', 'usr_acc_appear'];

    public function login()
    {
        return $this->belongsTo(User::class, 'usr_login_id');
    }

    public function detail()
    {
        return $this->belongsTo(UserDetail::class, 'usr_detail_id');
    }

    public function access()
    {
        return $this->belongsTo(UserAccess::class, 'usr_access_id');
    }
}
