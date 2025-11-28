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
    private $connectionId;
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
        'compte',
        'role_id',
        'situation_famille',
        'autoLogout',
    ];


     protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }


    public function setConnectionId($connectionId)
    {
        return [
            $this->connectionId = $connectionId,
        ];
    }
    
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'poste'=> $this->role,
            'nom'=> $this->nom,
            'connection_id'=> $this->connectionId,
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
        return $this->belongsToMany(ProjectTeam::class, 'team_members', 'team_id', 'user_id')
                    ->withPivot('role_in_team')
                    ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

 
    // Vérifier si l'utilisateur a une permission
    public function hasPermission($permission)
    {
        return $this->permissions()
            ->where('nom', $permission)
            ->orWhere('slug', $permission)
            ->exists();
    }
    
    // Vérifier si l'utilisateur a toutes les permissions
    public function hasAllPermissions(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
    
    // Vérifier si l'utilisateur a au moins une des permissions
    public function hasAnyPermission(array $permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }


         public function preferences()
    {
        return $this->hasOne(UserPreference::class);
    }

    // Créer automatiquement les préférences et les permissions par défaut lors de la création d'un utilisateur
    protected static function boot()
    {
        parent::boot();
        // initialiser les préférences lors de la création du users
        
        static::created(function ($user) {
            UserPreference::create([
                'user_id' => $user->id,
                'notif_email' => true,
                'notif_task_reminders' => true,
                'notif_project_updates' => true,
                'notif_deadline_alerts' => true,
                'language' => 'fr',
                'auto_logout' => 60,
            ]);
        });

        // attribuer les permissions par defaut
         // Quand un user est créé, attribuer automatiquement les permissions
        static::created(function ($user) {
            $user->assignDefaultPermissions();
        });


    }


     /**
     * 🎯 Attribuer automatiquement les permissions par défaut
     */
    public function assignDefaultPermissions()
    {
        $defaultPermissions = $this->getDefaultPermissionsByDepartment();
        
        if (!empty($defaultPermissions)) {
            $permissions = Permission::whereIn('nom', $defaultPermissions)->pluck('id');
            $this->permissions()->syncWithoutDetaching($permissions);
        }
    }

    /**
     * 📋 Définir les permissions selon le département/poste
     */
    private function getDefaultPermissionsByDepartment()
    {
        // Mapper selon votre structure (adapter à vos départements)
        $permissionMap = [
            'Administration' => [
                'admin.access',
                'admin.dashboard',
                'admin.settings',
                'users.view',
                'projects.view',
                'projects.create',
                'projects.delete',
                'permissions.view',
            ],
            
            'Ressources Humaines' => [
                'employees.view',
                'employees.create',
                'leaves.approve',
                'attendance.view',
            ],
            
            // Permissions par défaut pour tous
            'default' => [
                'my.projects',
                'my.tasks',
            ],
        ];

        // Chercher par département (adapter selon votre structure)
        $departement = $this->role->departement->nom ?? 'default';
        
        return $permissionMap[$departement] ?? $permissionMap['default'];
    }

}


