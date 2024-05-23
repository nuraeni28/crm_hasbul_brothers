<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRcdPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_main_id',
        'current_package',
        'date_subscribe',
        'package_amount',
        'date_end',
    ];

    public function clientMain()
    {
        return $this->belongsTo(ClientMain::class);
    }
}
