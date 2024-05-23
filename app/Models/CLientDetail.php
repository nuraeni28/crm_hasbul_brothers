<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_main_id',
        'full_name',
        'ic_number',
        'contact_number',
        'tshirt_size',
        'current_position',
    ];

    public function clientMain()
    {
        return $this->belongsTo(ClientMain::class);
    }
}
