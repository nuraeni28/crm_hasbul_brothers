<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreferenceSchedule extends Model
{
    protected $table = 'preference_schedule';
    
    protected $fillable = [
        'schedule_status',
        'event_time_start',
        'event_time_end',
        'event_title',
        'speaker_id',
        'client_main_id',
        'event_location',
        'event_detail',
    ];

    // Define the relationships with other models if any
    public function speaker()
    {
        return $this->belongsTo(UserMain::class, 'speaker_id');
    }

    public function client()
    {
        return $this->belongsTo(ClientMain::class, 'client_main_id');
    }
}
