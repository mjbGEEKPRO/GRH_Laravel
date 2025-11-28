<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ProjectTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'nom',
        'departement',
        'description'
    ];

    // Relations
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
                    ->withPivot('role_in_team')
                    ->withTimestamps();
    }
}