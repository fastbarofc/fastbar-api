<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

trait Sortable
{
    public static function sortableFields()
    {
        return !empty(self::$sortableFields) ? self::$sortableFields : [];
    }

    public static function isFieldSortable($field)
    {
        return in_array($field, self::sortableFields());
    }

    public function scopeOrderByField(Builder $query, $field, $direction = 'asc')
    {
        if (!self::isFieldSortable($field)) {
            return $query;
        }


        switch ($field) {
            case 'city_name':
            case 'addresses.city.name':
                return $this->orderByCity($query, $direction);

            case 'state_name':
            case 'addresses.state.name':
                return $this->orderByState($query, $direction);

            case 'sector.name':
                return $this->orderBySector($query, $direction);

            case 'sectorOffice.name':
                return $this->orderBySectorOffice($query, $direction);

            default:
                $table = $this->getSortableTable();
                return $query->orderBy("$table.$field", $direction);
        }
    }

    private function orderByCity(Builder $query, $direction)
    {
        return $query->join('addresses', function ($join) {
            $join->on('clients.id', '=', 'addresses.addressable_id')
                 ->where('addresses.addressable_type', '=', 'App\\Models\\Client');
        })
        ->join('cities', 'addresses.city_id', '=', 'cities.id')
        ->orderBy('cities.name', $direction)
        ->select('clients.*');
    }

    private function orderByState(Builder $query, $direction)
    {
        return $query->join('addresses', function ($join) {
            $join->on('clients.id', '=', 'addresses.addressable_id')
                 ->where('addresses.addressable_type', '=', 'App\\Models\\Client');
        })
        ->join('states', 'addresses.state_id', '=', 'states.id')
        ->orderBy('states.name', $direction)
        ->select('clients.*');
    }

    private function orderBySector(Builder $query, $direction)
    {
        return $query->join('sector_offices', 'collaborators.sector_office_id', '=', 'sector_offices.id')
            ->join('sectors', 'sector_offices.sector_id', '=', 'sectors.id')
            ->orderBy('sectors.name', $direction)
            ->select('collaborators.*');
    }

    private function orderBySectorOffice(Builder $query, $direction)
    {
        return $query->join('sector_offices', 'collaborators.sector_office_id', '=', 'sector_offices.id')
            ->orderBy('sector_offices.name', $direction)
            ->select('collaborators.*');
    }

    private function getSortableTable()
    {
        $modelClass = static::class;
        if ($modelClass === 'App\\Models\\Client') {
            return 'clients';
        }
        if ($modelClass === 'App\\Models\\Collaborator') {
            return 'collaborators';
        }
        return '';
    }
}
