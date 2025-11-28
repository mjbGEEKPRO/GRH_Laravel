<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notif_email',
        'notif_task_reminders',
        'notif_project_updates',
        'notif_deadline_alerts',
        'language',
        'auto_logout',
        
    ];

     public function user()
    {
        return $this->belongsTo(User::class);
    }

}
