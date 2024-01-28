<?php

namespace App\Models;

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
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles, Auditable, Sortable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'active'
    ];

    protected static $sortableFields = [
        'name',
        'email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
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

    public function isAdmin() {
        return $this->hasRole('Admin');
    }
}
