<?php

namespace App\Yantrana\Components\Scheduling\Models;

use App\Yantrana\Base\BaseModel;

class ScheduledEventModel extends BaseModel
{
    protected $table = 'scheduled_events';

    const STATUS_ACTIVE = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_COMPLETED = 3;

    protected $casts = [
        '__data' => 'array',
        'event_at' => 'datetime',
        'status' => 'integer',
    ];

    protected $fillable = [
        '_uid',
        'event_type',
        'contact_phone',
        'contact_name',
        'contacts__id',
        'vendors__id',
        'event_at',
        'timezone',
        'status',
        '__data',
    ];

    public function scheduledMessages()
    {
        return $this->hasMany(ScheduledMessageModel::class, 'scheduled_events__id', '_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendors__id', $vendorId);
    }
}
