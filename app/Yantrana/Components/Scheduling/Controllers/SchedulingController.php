<?php

namespace App\Yantrana\Components\Scheduling\Controllers;

use Illuminate\Http\Request;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\Scheduling\SchedulingEngine;
use App\Yantrana\Components\Scheduling\Models\ScheduledMessageModel;
use App\Yantrana\Components\Scheduling\Models\ScheduledEventModel;

class SchedulingController extends BaseController
{
    protected $schedulingEngine;

    public function __construct(SchedulingEngine $schedulingEngine)
    {
        $this->schedulingEngine = $schedulingEngine;
    }

    /**
     * Schedule an event with auto-generated reminders
     * POST /api/{vendorUid}/events/schedule
     */
    public function apiScheduleEvent(Request $request)
    {
        $request->validate([
            'event_type' => 'required|string|max:100',
            'contact_phone' => 'required|string|max:20',
            'event_at' => 'required|date',
            'contact_name' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:50',
            'metadata' => 'nullable|array',
        ]);

        $vendorId = getVendorId();

        $result = $this->schedulingEngine->scheduleEvent($request->all(), $vendorId);

        return $this->processResponse($result, [], [], true);
    }

    /**
     * Schedule a single message
     * POST /api/{vendorUid}/messages/schedule
     */
    public function apiScheduleMessage(Request $request)
    {
        $request->validate([
            'contact_phone' => 'required|string|max:20',
            'send_at' => 'required|date|after:now',
            'message_type' => 'required|in:template,text,media,interactive',
            'contact_name' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:50',
            'template_name' => 'required_if:message_type,template|string',
            'template_language' => 'nullable|string|max:10',
            'template_params' => 'nullable|array',
            'message_content' => 'required_if:message_type,text|string',
            'media_data' => 'required_if:message_type,media|array',
        ]);

        $vendorId = getVendorId();

        $result = $this->schedulingEngine->scheduleMessage($request->all(), $vendorId);

        return $this->processResponse($result, [], [], true);
    }

    /**
     * Schedule bulk messages
     * POST /api/{vendorUid}/messages/schedule-bulk
     */
    public function apiScheduleBulkMessages(Request $request)
    {
        $request->validate([
            'contacts' => 'required|array|min:1',
            'contacts.*.phone' => 'required|string',
            'send_at' => 'required|date|after:now',
            'message_type' => 'required|in:template,text',
            'template_name' => 'required_if:message_type,template|string',
            'template_language' => 'nullable|string|max:10',
            'template_params' => 'nullable|array',
            'message_content' => 'required_if:message_type,text|string',
            'timezone' => 'nullable|string|max:50',
        ]);

        $vendorId = getVendorId();

        $result = $this->schedulingEngine->scheduleBulkMessages($request->all(), $vendorId);

        return $this->processResponse($result, [], [], true);
    }

    /**
     * Get scheduled messages
     * GET /api/{vendorUid}/messages/scheduled
     */
    public function apiGetScheduledMessages(Request $request)
    {
        $vendorId = getVendorId();
        $status = $request->get('status');

        $statusMap = [
            'scheduled' => ScheduledMessageModel::STATUS_SCHEDULED,
            'sent' => ScheduledMessageModel::STATUS_SENT,
            'failed' => ScheduledMessageModel::STATUS_FAILED,
            'cancelled' => ScheduledMessageModel::STATUS_CANCELLED,
        ];

        $statusValue = isset($statusMap[$status]) ? $statusMap[$status] : null;

        $messages = $this->schedulingEngine->getScheduledMessages($vendorId, $statusValue);

        return $this->processResponse(1, [], [
            'data' => $messages,
        ], true);
    }

    /**
     * Get scheduled events
     * GET /api/{vendorUid}/events/scheduled
     */
    public function apiGetScheduledEvents(Request $request)
    {
        $vendorId = getVendorId();
        $status = $request->get('status');

        $statusMap = [
            'active' => ScheduledEventModel::STATUS_ACTIVE,
            'cancelled' => ScheduledEventModel::STATUS_CANCELLED,
            'completed' => ScheduledEventModel::STATUS_COMPLETED,
        ];

        $statusValue = isset($statusMap[$status]) ? $statusMap[$status] : null;

        $events = $this->schedulingEngine->getScheduledEvents($vendorId, $statusValue);

        return $this->processResponse(1, [], [
            'data' => $events,
        ], true);
    }

    /**
     * Get single scheduled message
     * GET /api/{vendorUid}/messages/scheduled/{messageUid}
     */
    public function apiGetScheduledMessage($vendorUid, $messageUid)
    {
        $vendorId = getVendorId();

        $message = ScheduledMessageModel::where('_uid', $messageUid)
            ->forVendor($vendorId)
            ->first();

        if (!$message) {
            return $this->processResponse(2, [], [
                'message' => __tr('Scheduled message not found'),
            ], true);
        }

        return $this->processResponse(1, [], [
            'data' => $message,
        ], true);
    }

    /**
     * Cancel scheduled event
     * DELETE /api/{vendorUid}/events/{eventUid}
     */
    public function apiCancelEvent($vendorUid, $eventUid)
    {
        $vendorId = getVendorId();

        $result = $this->schedulingEngine->cancelEvent($eventUid, $vendorId);

        return $this->processResponse($result, [], [], true);
    }

    /**
     * Cancel scheduled message
     * DELETE /api/{vendorUid}/messages/scheduled/{messageUid}
     */
    public function apiCancelMessage($vendorUid, $messageUid)
    {
        $vendorId = getVendorId();

        $result = $this->schedulingEngine->cancelMessage($messageUid, $vendorId);

        return $this->processResponse($result, [], [], true);
    }

    /**
     * Get queue statistics
     * GET /api/{vendorUid}/scheduling/stats
     */
    public function apiGetQueueStats()
    {
        $vendorId = getVendorId();

        $stats = $this->schedulingEngine->getQueueStats($vendorId);

        return $this->processResponse(1, [], [
            'data' => $stats,
        ], true);
    }

    /**
     * Get scheduler health
     * GET /api/{vendorUid}/scheduling/health
     */
    public function apiGetHealth()
    {
        $lastProcessed = ScheduledMessageModel::where('status', ScheduledMessageModel::STATUS_SENT)
            ->orderBy('updated_at', 'desc')
            ->first();

        $pendingCount = ScheduledMessageModel::where('status', ScheduledMessageModel::STATUS_SCHEDULED)
            ->where('send_at', '<=', now())
            ->count();

        $health = [
            'status' => $pendingCount > 100 ? 'degraded' : 'healthy',
            'last_processed_at' => $lastProcessed ? $lastProcessed->updated_at->toDateTimeString() : null,
            'pending_overdue' => $pendingCount,
        ];

        return $this->processResponse(1, [], [
            'data' => $health,
        ], true);
    }

    /**
     * Save reminder config
     * POST /api/{vendorUid}/scheduling/reminder-configs
     */
    public function apiSaveReminderConfig(Request $request)
    {
        $request->validate([
            'event_type' => 'required|string|max:100',
            'reminder_offset_minutes' => 'required|integer',
            'template_name' => 'required|string|max:255',
            'template_language' => 'nullable|string|max:10',
            'is_active' => 'nullable|boolean',
        ]);

        $vendorId = getVendorId();

        $result = $this->schedulingEngine->saveReminderConfig($request->all(), $vendorId);

        return $this->processResponse($result, [], [], true);
    }

    /**
     * Get reminder configs
     * GET /api/{vendorUid}/scheduling/reminder-configs
     */
    public function apiGetReminderConfigs(Request $request)
    {
        $vendorId = getVendorId();
        $eventType = $request->get('event_type');

        $configs = $this->schedulingEngine->getReminderConfigs($eventType, $vendorId);

        return $this->processResponse(1, [], [
            'data' => $configs,
        ], true);
    }

    /**
     * Delete reminder config
     * DELETE /api/{vendorUid}/scheduling/reminder-configs/{configUid}
     */
    public function apiDeleteReminderConfig($vendorUid, $configUid)
    {
        $vendorId = getVendorId();

        $result = $this->schedulingEngine->deleteReminderConfig($configUid, $vendorId);

        return $this->processResponse($result, [], [], true);
    }
}
