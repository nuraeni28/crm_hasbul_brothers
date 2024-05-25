<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMain extends Model
{
    use HasFactory;

    protected $table = 'usr_main';
    protected $fillable = ['usr_acc_status', 'usr_login_id', 'usr_access_id', 'usr_detail_id', 'client_detail_id', 'usr_acc_appear'];

   /**
     * Get the user detail associated with the user main.
     */
    public function userDetail()
    {
        return $this->hasOne(UserDetail::class,  'id');
    }

    /**
     * Get the user access associated with the user main.
     */
    public function userAccess()
    {
        return $this->hasOne(UserAccess::class,  'id');
    }
    public function userLogin()
    {
        return $this->hasOne(User::class, 'id');
    }
}
