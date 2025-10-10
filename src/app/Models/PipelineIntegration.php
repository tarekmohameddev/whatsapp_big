<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineIntegration extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uid = $model->uid ?: str_unique();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(PipelineIntegrationRule::class, 'integration_id');
    }
}


