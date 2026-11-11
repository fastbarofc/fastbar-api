<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Company extends Model
{
    use HasFactory, Sortable, Filterable, SoftDeletes;

    protected $fillable = [
        'internal_name',
        'legal_name',
        'trade_name',
        'type',
        // Nota fiscal (Focus NFe)
        'cnpj',
        'municipal_registration',
        'city_id',
        'tax_regime',
        'nf_environment',
        'nf_service_code',
        'nf_municipal_tax_code',
        'nf_iss_rate',
        'nf_default_description',
    ];

    protected $casts = [
        'type' => 'string',
        'nf_iss_rate' => 'decimal:2',
    ];

    protected static $sortableFields = [
        'id',
        'internal_name',
        'legal_name',
        'trade_name',
        'type',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'internal_name',
        'legal_name',
        'trade_name',
        'type',
    ];

    protected static $searchableFields = [
        'internal_name',
        'legal_name',
        'trade_name',
    ];
    
    /** CNPJ guardado só com os números (a Focus NFe exige assim). */
    public function setCnpjAttribute($value): void
    {
        $this->attributes['cnpj'] = $value ? preg_replace('/\D/', '', (string) $value) : null;
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_companies');
    }

    public function collaborators()
    {
        return $this->belongsToMany(Collaborator::class, 'collaborator_companies');
    }

    /** Empresa padrão dos colaboradores cadastrados sem empresa (Fastbar). */
    public static function defaultId(): ?int
    {
        $company = static::where('internal_name', 'Fastbar')->first() ?? static::orderBy('id')->first();

        return $company?->id;
    }
}
