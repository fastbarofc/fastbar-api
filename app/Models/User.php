<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\Passport;
use Spatie\Permission\Traits\HasRoles;
use \OwenIt\Auditing\Auditable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class User extends Authenticatable implements AuditableInterface
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles, Auditable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
        'is_employee',
        'custom_permissions',
    ];

    protected static $sortableFields = [
        'name',
        'email',
        'last_seen_at',
    ];

    protected static $filterableFields = [
        'name',
        'email',
        'is_employee',
        'active',
    ];

    protected static $searchableFields = [
        'name',
        'email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $auditInclude = ['name'];

    public function tokens()
    {
        return $this->hasMany(Passport::tokenModel(), 'user_id')->orderBy('created_at', 'desc');
    }

    public function client() {
        return $this->hasOne(Client::class);
    }

    /**
     * Permissões que valem para a pessoa: as do perfil, ou as personalizadas
     * quando alguém ajustou as permissões só dela (Gestão de usuários).
     */
    public function effectivePermissions()
    {
        return $this->custom_permissions ? $this->getDirectPermissions() : $this->getPermissionsViaRoles();
    }

    /** Marca quando a pessoa usou o sistema pela última vez (no máximo a cada 5 minutos). */
    public function touchLastSeen(): void
    {
        if ($this->last_seen_at && now()->diffInMinutes($this->last_seen_at) < 5) {
            return;
        }

        // Atualiza direto no banco para não gerar registro no histórico de alterações.
        // Nunca deixa a requisição falhar por causa disso (ex.: migration ainda não rodou no servidor).
        try {
            static::whereKey($this->id)->update(['last_seen_at' => now()]);
            $this->last_seen_at = now();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function isAdmin() {
        // Reconhece pelo slug: o nome exibido do perfil é "Administrador".
        return $this->roles->contains(fn ($role) => $role->slug === 'admin' || in_array($role->name, ['Admin', 'Administrador'], true));
    }

    public function collaborator() {
        return $this->hasOne(Collaborator::class);
    }
}
