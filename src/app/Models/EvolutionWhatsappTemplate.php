<?php

namespace App\Models;

use App\Enums\Common\Status;
use App\Enums\System\EvolutionWhatsappTemplateTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvolutionWhatsappTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid', 'user_id', 'name', 'type', 'payload', 'row_actions', 'status',
    ];

    protected $casts = [
        'payload' => 'array',
        'row_actions' => 'array',
        'status'  => Status::class,
        'type'    => EvolutionWhatsappTemplateTypeEnum::class,
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
}


