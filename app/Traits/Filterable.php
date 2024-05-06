<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait  Filterable
{


  public static function filterableFields()
  {

    return !empty(self::$filterableFields) ? self::$filterableFields : [];
  }

  public static function searchableFields()
  {
    return !empty(self::$searchableFields) ? self::$searchableFields : [];
  }

  static function isFieldFilterable($field)
  {
    return in_array($field, self::filterableFields());
  }

  public function scopeFilterByField($query, $field, $value, $operator = '=', $isDate = false)
  {
    if (!$this->isFieldFilterable($field) && $field != 'search') {
      return $query;
    }
    
    if (is_array($value)) {
      return $query->whereIn($field, $value);
    }

    if ($operator == 'like') {
      return $query->where($field, 'like', "%$value%");
    }

    if ($isDate) {
      return $query->whereDate($field, $operator, $value);
    }

    if($field == 'search') {
      return $query->where(function($query) use ($field, $value) {
        foreach(self::searchableFields() as $searchableField) {
          $query->orWhere($searchableField, 'like', "%$value%");
        }
      });
    }

    return $query->where($field, $value);
  }
}
