<?php

namespace App\Traits;

use App\Models\FisicalPerson;
use App\Models\Person;
use App\Models\LegalPerson;

trait IsPerson
{

    public static function bootIsPerson()
    {
        static::creating(function ($model)
        {
            $data = request()->all();
            
            $type = !empty($data['cpf']) ? Person::TYPE_FISICAL : Person::TYPE_LEGAL;

            $person = Person::create([
                'type' => $type,
                'email' => $data['email'],
                'phone_1' => $data['phone_1'],
                'phone_2' => $data['phone_2']
            ]);

            if($type == Person::TYPE_FISICAL) {
                $fisicalPerson = FisicalPerson::create([
                    'person_id' => $person->id,
                    'name' => $data['name'],
                    'nickname' => $data['nickname'],
                    'birth_date' => $data['birth_date'],
                    'cpf' => $data['cpf'],
                    'rg' => $data['rg']
                ]);
                $model->fisical_person_id = $fisicalPerson->id;
            } 

            if($type == Person::TYPE_LEGAL) {
                $legalPerson = LegalPerson::create([
                    'person_id' => $person->id,
                    'company_name' => $data['company_name'],
                    'fantasy_name' => $data['fantasy_name'],
                    'cnpj' => $data['cnpj'],
                    'ie' => $data['ie'],
                    'im' => $data['im']
                ]);
    
                $model->legal_person_id = $legalPerson->id;
            }            
        });

        static::updating(function ($model)
        {
            $data = request()->all();
            
            $type = !empty($data['cpf']) ? Person::TYPE_FISICAL : Person::TYPE_LEGAL;

            $personId = '';

            if($type == Person::TYPE_FISICAL) {
                $fisicalPerson = FisicalPerson::where('id', $model->fisical_person_id)->first();
                $fisicalPerson->update([
                    'name' => $data['name'],
                    'nickname' => $data['nickname'],
                    'birth_date' => $data['birth_date'],
                    'cpf' => $data['cpf'],
                    'rg' => $data['rg']
                ]);
                $personId = $fisicalPerson->person_id;
            } 

            if($type == Person::TYPE_LEGAL) {
                $legalPerson = LegalPerson::where('id', $model->fisical_person_id)->first();
                $legalPerson->update([
                    'company_name' => $data['company_name'],
                    'fantasy_name' => $data['fantasy_name'],
                    'cnpj' => $data['cnpj'],
                    'ie' => $data['ie'],
                    'im' => $data['im']
                ]);
                $personId = $legalPerson->person_id;
            }

            $person = Person::where('id', $personId)->first();

            $person->update([
                'email' => $data['email'],
                'phone_1' => $data['phone_1'],
                'phone_2' => $data['phone_2']
            ]);
        });

        static::deleting(function ($model)
        {
            $personId = '';

            if($model->person->type == Person::TYPE_FISICAL) {
                $fisicalPerson = FisicalPerson::where('id', $model->fisical_person_id)->first();
                $fisicalPerson->delete();
                $personId = $fisicalPerson->person_id;
            } 

            if($model->person->type == Person::TYPE_LEGAL) {
                $legalPerson = LegalPerson::where('id', $model->fisical_person_id)->first();
                $legalPerson->delete();
                $personId = $legalPerson->person_id;
            }

            $person = Person::where('id', $personId)->first();

            $person->delete();
        });
    }

    public function getNameAttribute()
    {
        if($this->person && $this->person->type == Person::TYPE_FISICAL) {
            return $this->person->fisicalPerson->name;
        }
    }

    public function getCpfAttribute()
    {
        if($this->person && $this->person->type == Person::TYPE_FISICAL) {
            return $this->person->fisicalPerson->cpf;
        }
    }

    public function getRgAttribute()
    {
        if($this->person && $this->person->type == Person::TYPE_FISICAL) {
            return $this->person->fisicalPerson->rg;
        }
    }

    public function getBirthDateAttribute()
    {
        if($this->person && $this->person->type == Person::TYPE_FISICAL) {
            return $this->person->fisicalPerson->birth_date;
        }
    }

    public function getCompanyNameAttribute()
    {
        if($this->person && $this->person->type == Person::TYPE_LEGAL) {
            return $this->person->legalPerson->company_name;
        }
    }

    public function getFantasyNameAttribute()
    {
        if($this->person && $this->person->type == Person::TYPE_LEGAL) {
            return $this->person->legalPerson->fantasy_name;
        }
    }

    public function getCnpjAttribute()
    {
        if($this->person && $this->person->type == Person::TYPE_LEGAL) {
            return $this->person->legalPerson->cnpj;
        }
    }

    public function getIeAttribute()
    {
        if($this->person && $this->person->type == Person::TYPE_LEGAL) {
            return $this->person->legalPerson->ie;
        }
    }

    public function getImAttribute()
    {
        if($this->person && $this->person->type == Person::TYPE_LEGAL) {
            return $this->person->legalPerson->im;
        }
    }

    public function getPhone1Attribute()
    {
        return !empty($this->person) ? $this->person->phone_1 : null;
    }

    public function getPhone2Attribute()
    {
        return !empty($this->person) ? $this->person->phone_2 : null;
    }
}