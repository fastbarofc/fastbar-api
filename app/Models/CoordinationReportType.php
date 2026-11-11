<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tipo de relatório da coordenação (um formulário do Google Forms). */
class CoordinationReportType extends Model
{
    protected $fillable = ['name', 'form_url', 'sort_order'];
}
