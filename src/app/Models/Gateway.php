<?php

namespace App\Models;

use App\Enums\Common\Status;
use App\Enums\StatusEnum;
use App\Enums\System\ChannelTypeEnum;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Gateway extends Model
{
    use HasFactory, Notifiable, Filterable;

    protected $fillable = [
        'user_id', 'uid', 'channel', 'type', 'name', 'address', 'meta_data', 'status',
        'is_default', 'per_message_min_delay', 'per_message_max_delay', 'delay_after_count','bulk_contact_limit','delay_after_duration', 'reset_after_count',
    ];

    protected $casts = [
        'channel' => ChannelTypeEnum::class, 
        'meta_data' => 'array',
        'status' => Status::class,
        'is_default' => 'boolean',
        'per_message_delay' => 'float',
        'delay_after_count' => 'integer',
        'delay_after_duration' => 'float',
        'reset_after_count' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($gateway) {
            $gateway->uid = str_unique();
        });
    }

    public function scopeActive($query): Builder
    {
        return $query->where('status', Status::ACTIVE->value);
    }

    public function scopeInactive($query): Builder
    {
        return $query->where('status', Status::INACTIVE->value);
    }

    public function scopeMail($query)
    {
        return $query->whereNotNull('id');
    }
    public function scopeSms($query)
    {
        return $query->whereNotNull('id');
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class, "cloud_id");
    }
}