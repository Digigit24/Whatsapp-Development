<?php

namespace App\Yantrana\Components\Scheduling\Models;

use App\Yantrana\Base\BaseModel;

class EventReminderConfigModel extends BaseModel
{
    protected $table = 'event_reminder_configs';

    protected $casts = [
        '__data' => 'array',
        'is_active' => 'boolean',
        'reminder_offset_minutes' => 'integer',
    ];

    protected $fillable = [
        '_uid',
        'event_type',
        'reminder_offset_minutes',
        'template_name',
        'template_language',
        'vendors__id',
        'is_active',
        '__data',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where(function ($q) use ($vendorId) {
            $q->where('vendors__id', $vendorId)
              ->orWhereNull('vendors__id');
        });
    }
}
