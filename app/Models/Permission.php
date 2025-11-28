<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    //origin juste nom
    protected $fillable = ['nom','slug',
        'description'];

    public function users()
    {
        return $this->belongsToMany(User::class,'permission_user')->withTimestamps();;
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
