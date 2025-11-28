<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'projet_id',
        'assigne_a_user_id',
        'date_echeance',
        'priorite',
        'statut',
        'created_by'
    ];

    protected $casts = [
        'date_echeance' => 'date'
    ];

    // Relations
    public function project()
    {
        return $this->belongsTo(Project::class, 'projet_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigne_a_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accesseurs
    public function getIsOverdueAttribute()
    {
        return $this->date_echeance->isPast() && $this->statut !== 'Terminé';
    }

    public function getDaysUntilDeadlineAttribute()
    {
        return now()->diffInDays($this->date_echeance, false);
    }

    public function getPriorityColorAttribute()
    {
        return match($this->priorite) {
            'Haute' => 'red',
            'Normale' => 'blue',
            'Basse' => 'green',
            default => 'gray'
        };
    }
}
