<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvolutionButtonClick extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'raw_payload' => 'array',
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


