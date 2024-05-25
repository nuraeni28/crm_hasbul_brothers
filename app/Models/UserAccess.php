<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccess extends Model
{
    use HasFactory;

    protected $table = 'usr_access';

    protected $fillable = ['access_name', 'access_privilege', 'access_status', 'trainer_status', 'seller_status'];
      public function user()
    {
        return $this->belongsTo(UserMain::class, 'id');
    }
}
