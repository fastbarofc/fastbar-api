<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Dados do planejamento de um evento: alinhamento com o cliente, cidade e distância. */
class EventPlanning extends Model
{
    protected $fillable = ['event_id', 'city', 'distance_km', 'alignment', 'table_map_file_id', 'updated_by'];

    protected $casts = [
        'alignment' => 'array',
        'distance_km' => 'float',
    ];

    /** Arquivo do mapa de mesas (layout do evento). */
    public function tableMapFile()
    {
        return $this->belongsTo(File::class, 'table_map_file_id');
    }

    /** Campos do alinhamento com o cliente (2.1). */
    public const ALIGNMENT_FIELDS = [
        'client_drinks' => 'Bebida do contratante',
        'sideboards' => 'Aparadores',
        'gift' => 'Brinde',
        'guest_control' => 'Lista ou QR Code',
        'table_map' => 'Mapa de mesas',
        'trucks_time' => 'Horário dos caminhões',
        'notes' => 'Outras perguntas e respostas',
    ];
}
