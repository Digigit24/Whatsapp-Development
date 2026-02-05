<?php

namespace App\Yantrana\Components\Scheduling\Models;

use App\Yantrana\Base\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ScheduledMessageModel extends BaseModel
{
    protected $table = 'scheduled_messages';

    const STATUS_SCHEDULED = 1;
    const STATUS_SENT = 2;
    const STATUS_FAILED = 3;
    const STATUS_CANCELLED = 4;

    const TYPE_TEMPLATE = 'template';
    const TYPE_TEXT = 'text';
    const TYPE_MEDIA = 'media';
    const TYPE_INTERACTIVE = 'interactive';

    protected $casts = [
        '__data' => 'array',
        'send_at' => 'datetime',
        'status' => 'integer',
        'retries' => 'integer',
    ];

    protected $fillable = [
        '_uid',
        'scheduled_events__id',
        'message_type',
        'contact_phone',
        'contact_name',
        'contacts__id',
        'vendors__id',
        'send_at',
        'timezone',
        'status',
        'retries',
        'template_name',
        'template_language',
        '__data',
    ];

    protected $appends = [
        'error_message',
        'formatted_send_time',
    ];

    public function scheduledEvent()
    {
        return $this->belongsTo(ScheduledEventModel::class, 'scheduled_events__id', '_id');
    }

    protected function errorMessage(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => data_get(json_decode($attributes['__data'] ?? '{}', true), 'error_message')
        );
    }

    protected function formattedSendTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => formatDiffForHumans($attributes['send_at'], null, $attributes['vendors__id'] ?? null)
        );
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
                     ->where('send_at', '<=', now());
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendors__id', $vendorId);
    }
}
