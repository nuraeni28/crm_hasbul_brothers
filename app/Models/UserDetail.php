<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use HasFactory;
    protected $table = 'usr_detail';

    protected $fillable = ['usr_fname', 'usr_lname', 'usr_birth', 'usr_code_phone', 'usr_no_phone'];

    public function main()
    {
        return $this->hasOne(UserMain::class, 'usr_detail_id');
    }
}
