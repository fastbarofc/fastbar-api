<?php

namespace App\Http\Resources;

use App\Models\Qualification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\this;

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
            'has_login'      => $this->has_login,
            'active'         => $this->active,
            'user_id'        => $this->user_id,
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

        ];

        return $data;
    }
}
