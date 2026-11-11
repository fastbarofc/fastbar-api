<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class Bidding extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable;

    public const STATUSES = ['prevista', 'em_andamento', 'ganha', 'perdida', 'cancelada'];

    protected $fillable = [
        'agency',
        'notice_number',
        'opening_date',
        'opening_time',
        'status',
        'observation',
        'summary',
        'notice_file_id',
        'notice_file_name',
    ];

    public function noticeFile()
    {
        return $this->belongsTo(File::class, 'notice_file_id');
    }

    public function comments()
    {
        return $this->hasMany(BiddingComment::class)->latest();
    }
}
