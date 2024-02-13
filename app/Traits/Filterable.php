<?php

namespace App\Traits;

use Illuminate\Http\Request;

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
        if ($this->isFieldFilterable($field)) {
            return $query->where($field, $value);
        }

        return $query;
    }
}
