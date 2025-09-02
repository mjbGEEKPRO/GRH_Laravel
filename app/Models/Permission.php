<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    //origin juste nom
    protected $fillable = ['name', 'resource', 'display_name'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    // public function teams()
    // {
    //     return $this->belongsToMany(Team::class);
    // }
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
