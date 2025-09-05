<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineIntegrationRule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'action' => 'array',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(PipelineIntegration::class, 'integration_id');
    }
}


