<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'budget',
        'date_fin_prevue',
        'statut',
        'departements',
        'created_by'
    ];

    protected $casts = [
        'date_fin_prevue' => 'date',
        'budget' => 'decimal:2',
        'departements' => 'array'
    ];

    // Relations
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'projet_id');
    }

    public function teams()
    {
        return $this->hasMany(ProjectTeam::class);
    }

    // Accesseurs
    public function getBudgetFormattedAttribute()
    {
        return number_format($this->budget, 0, ',', ' ') . ' FCFA';
    }

    public function getProgressPercentageAttribute()
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) return 0;
        
        $completedTasks = $this->tasks()->where('statut', 'Terminé')->count();
        return round(($completedTasks / $totalTasks) * 100);
    }

    public function getIsOverdueAttribute()
    {
        return $this->date_fin_prevue->isPast() && $this->statut !== 'Terminé';
    }
}
