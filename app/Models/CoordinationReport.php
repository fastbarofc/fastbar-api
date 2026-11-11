<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Relatório da coordenação de um evento: link do arquivo no Google Drive. */
class CoordinationReport extends Model
{
    protected $fillable = ['event_id', 'coordination_report_type_id', 'drive_url', 'received_at', 'notes', 'user_id'];

    protected $casts = ['received_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
