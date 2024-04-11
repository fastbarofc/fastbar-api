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

  static function isFieldFilterable($field)
  {
    return in_array($field, self::filterableFields());
  }

  public function scopeFilterByField($query, $field, $value)
  {
    if (!$this->isFieldFilterable($field)) {
      return $query;
    }
    
    if (is_array($value)) {
      return $query->whereIn($field, $value);
    }

    return $query->where($field, $value);
  }
}
