<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreferenceAccess extends Model
{
    use HasFactory;

    // Menentukan tabel yang digunakan oleh model ini
    protected $table = 'preference_access';

    // Menentukan kolom yang dapat diisi secara massal
    protected $fillable = [
        'access_module',
        'access_permission',
        'access_privilege'
    ];

    // Menentukan tipe data untuk created_at dan updated_at sebagai timestamps
    protected $dates = ['created_at', 'updated_at'];
}
