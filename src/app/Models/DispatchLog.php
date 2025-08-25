<?php

namespace App\Models;

use App\Enums\System\ChannelTypeEnum;
use App\Enums\System\CommunicationStatusEnum;
use App\Enums\System\PriorityEnum;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\Notifiable;

class DispatchLog extends Model
{
    use HasFactory, Notifiable, Filterable;

    protected $fillable = [
        'user_id', 'message_id', 'contact_id', 'campaign_id', 'type',
        'gatewayable_id', 'gatewayable_type', 'priority', 'status',
        'response_message', 'meta_data', 'scheduled_at', 'sent_at',
        'processed_at', 'applied_delay', 'retry_count',
    ];

    protected $casts = [
        'type'          => ChannelTypeEnum::class,
        'priority'      => PriorityEnum::class,
        'status'        => CommunicationStatusEnum::class,
        'meta_data'     => 'array',
        'scheduled_at'  => 'datetime',
        'sent_at'       => 'datetime',
        'processed_at'  => 'datetime',
        'applied_delay' => 'float',
        'retry_count'   => 'integer',
    ];

    /**
     * user
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * message
     *
     * @return BelongsTo
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * contact
     *
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * gatewayable
     *
     * @return MorphTo
     */
    public function gatewayable(): MorphTo
    {
        return $this->morphTo();
    }
}