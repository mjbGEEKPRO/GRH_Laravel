<?php






namespace App\Models;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable,HasFactory;

    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */

   protected $fillable = [
        'nom',
        'prenom',
        'email',
        'email_pro',
        'statut',
        'telephone',
        'lieu_naissance',
        'date_naissance',
        'password',
        'role_id',
    ];


     protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }


    
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'poste'=> $this->poste,
            'nom'=> $this->nom
        ];
    }

    // public function departement()
    // {
    //     return 
    //     $this->hasOne(Departement::class, 'poste','poste');
    // }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }




        // Méthode pour récupérer toutes les permissions (rôle + directes)
        public function getAllPermissions()
        {
            $rolePermissions = $this->role ? $this->role->permissions : collect([]);
            $directPermissions = $this->permissions;
            
            return $rolePermissions->merge($directPermissions)->unique('id');
        }

        // Vérifier si l'utilisateur a une permission spécifique
        public function hasPermission($permission, $resource = null)
        {
            return $this->getAllPermissions()->contains(function ($perm) use ($permission, $resource) {
                return $perm->name === $permission && 
                    ($resource === null || $perm->resource === $resource);
            });
        }
}


