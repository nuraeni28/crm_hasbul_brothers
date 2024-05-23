<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPaymentBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_main_id',
        'payment_amount',
        'payment_date',
        'payment_remarks',
        'payment_iteration',
    ];

    public function clientMain()
    {
        return $this->belongsTo(ClientMain::class);
    }
}
