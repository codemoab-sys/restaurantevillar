<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySummary extends Model
{
    protected $table = 'rest_daily_summaries';
use HasFactory;

    protected $fillable = [
        'reference_date',
        'generation_date',
        'correlativo',
        'identifier',
        'total_documents',
        'total_amount',
        'sunat_status',
        'ticket',
        'sunat_code',
        'sunat_description',
        'xml_path',
        'cdr_path',
        'hash',
        'sent_at',
        'consulted_at',
        'user_id',
    ];

    protected $casts = [
        'reference_date' => 'date',
        'generation_date' => 'date',
        'sent_at' => 'datetime',
        'consulted_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
