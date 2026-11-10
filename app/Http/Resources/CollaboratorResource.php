<?php

namespace App\Http\Resources;

use App\Models\Qualification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollaboratorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id'             => $this->id,
            'name'           => $this->name,
            'nickname'       => $this->nickname,
            'birth_date'     => $this->birth_date,
            'cpf'            => $this->cpf,
            'rg'             => $this->rg,
            'email'          => $this->email,
            'phone_1'        => $this->phone_1,
            'phone_2'        => $this->phone_2,
            'address'        => new AddressResource($this->address),
            'sector'         => $this->sector,
            'sector_id'      => $this->sector_id,
            'sector_office'  => $this->sectorOffice,
            'sector_office_id' => $this->sector_office_id,
            'employment_type' => $this->employment_type ?? 'freelancer',
            'biometric_consent' => (bool) $this->biometric_consent_at,
            'biometric_consent_at' => $this->biometric_consent_at?->toIso8601String(),
            'has_login'      => $this->has_login,
            'active'         => $this->active,
            'user_id'        => $this->user_id,
            'role_id'        => $this->user?->roles?->first()?->id,
            'file_id'=> $this->file_id,
            'file_path'      => $this->file ? asset('storage/' . $this->file->path) : null,
            'qualifications' => Qualification::all()->map(function ($qualification) {
                return [
                    'id'          => $qualification->id,
                    'description' => $qualification->description,
                    'points'      => $this->qualifications?->find($qualification->id)?->pivot?->points ?? 0,
                ];
            }),
            'incidents' => $this->collaboratorIncidents->map(function ($incident) {
                return [
                    'id'          => $incident->id,
                    'description' => $incident->description,
                    'collaborator_id'      => $this->id
                ];
            }),
            'bank_details' => BankDetailResource::collection($this->bankDetails),
            'pix_keys' => PixKeyResource::collection($this->pixKeys),
            'goals' => CollaboratorGoalResource::collection($this->goals),
            'companies' => $this->whenLoaded('companies', fn () => $this->companies->map(fn ($c) => ['id' => $c->id, 'internal_name' => $c->internal_name])),

        ];

        return $data;
    }
}
