<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Gravação (áudio) de uma reunião. O arquivo fica no disco local, fora da pasta pública. */
class AppointmentRecording extends Model
{
    use SoftDeletes;

    public const STATUS_RECORDING = 'recording';
    public const STATUS_DONE = 'done';

    protected $fillable = [
        'appointment_id',
        'path',
        'mime_type',
        'size',
        'duration_seconds',
        'status',
        'created_by',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
