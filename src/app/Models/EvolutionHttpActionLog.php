<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvolutionHttpActionLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'request_headers' => 'array',
        'request_body'    => 'array',
        'context_meta'    => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($row) {
            $row->uid = str_unique();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(EvolutionWhatsappTemplate::class, 'template_id');
    }
}


