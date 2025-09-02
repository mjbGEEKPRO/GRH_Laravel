<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{      public $timestamps=false;

    protected $fillable = ['nom'];

    

    public function postes(){
        return $this->hasMany(Role::class);
    }
  
}
