<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait  Sortable {


  public static function sortableFields() {
    
    return !empty(self::$sortableFields) ? self::$sortableFields : [];
  }

  static function isFieldSortable($field) {
    return in_array($field, self::sortableFields());
  }

  public function scopeOrderByField($query, $field, $direction = 'asc')     {         
    
    if ($this->isFieldSortable($field)) {      
      return $query->orderBy($field, $direction);
    }         

    return $query;     
  }
}

