<?php

namespace App\Yantrana\Components\Scheduling;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Yantrana\Base\BaseEngine;
use Illuminate\Support\Facades\DB;
use App\Yantrana\Components\Scheduling\Models\ScheduledEventModel;
use App\Yantrana\Components\Scheduling\Models\ScheduledMessageModel;
use App\Yantrana\Components\Scheduling\Models\EventReminderConfigModel;
use App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine;
use App\Yantrana\Components\WhatsAppService\Repositories\WhatsAppTemplateRepository;
use App\Yantrana\Components\Contact\Repositories\ContactRepository;

class SchedulingEngine extends BaseEngine
{
    protected $whatsAppServiceEngine;
    protected $whatsAppTemplateRepository;
    protected $contactRepository;

    public function __construct(
        WhatsAppServiceEngine $whatsAppServiceEngine,
        WhatsAppTemplateRepository $whatsAppTemplateRepository,
        ContactRepository $contactRepository
    ) {
        $this->whatsAppServiceEngine = $whatsAppServiceEngine;
        $this->whatsAppTemplateRepository = $whatsAppTemplateRepository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * Schedule an event with auto-generated reminders
     */
    public function scheduleEvent(array $data, $vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();

        $eventType = $data['event_type'];
        $contactPhone = $data['contact_phone'];
        $eventAt = Carbon::parse($data['event_at'], $data['timezone'] ?? 'UTC');
        $metadata = $data['metadata'] ?? [];

        DB::beginTransaction();
        try {
            // Create the event
            $event = new ScheduledEventModel();
            $event->_uid = Str::uuid();
            $event->event_type = $eventType;
            $event->contact_phone = $contactPhone;
            $event->contact_name = $data['contact_name'] ?? null;
            $event->contacts__id = $data['contacts__id'] ?? null;
            $event->vendors__id = $vendorId;
            $event->event_at = $eventAt;
            $event->timezone = $data['timezone'] ?? 'UTC';
            $event->status = ScheduledEventModel::STATUS_ACTIVE;
            $event->__data = $metadata;
            $event->save();

            // Get reminder configs for this event type
            $reminderConfigs = EventReminderConfigModel::active()
                ->forEventType($eventType)
                ->forVendor($vendorId)
                ->orderBy('reminder_offset_minutes')
                ->get();

            $scheduledMessages = [];

            foreach ($reminderConfigs as $config) {
                $sendAt = $eventAt->copy()->addMinutes($config->reminder_offset_minutes);

                // Skip if send time is in the past
                if ($sendAt->isPast()) {
                    continue;
                }

                $message = $this->createScheduledMessage([
                    'scheduled_events__id' => $event->_id,
                    'message_type' => ScheduledMessageModel::TYPE_TEMPLATE,
                    'contact_phone' => $contactPhone,
                    'contact_name' => $data['contact_name'] ?? null,
                    'contacts__id' => $data['contacts__id'] ?? null,
                    'vendors__id' => $vendorId,
                    'send_at' => $sendAt,
                    'timezone' => $data['timezone'] ?? 'UTC',
                    'template_name' => $config->template_name,
                    'template_language' => $config->template_language,
                    'template_params' => array_merge($metadata, [
                        'event_time' => $eventAt->format('h:i A'),
                        'event_date' => $eventAt->format('M d, Y'),
                    ]),
                ]);

                $scheduledMessages[] = $message;
            }

            DB::commit();

            return $this->engineSuccessResponse([
                'event' => $event,
                'scheduled_messages' => $scheduledMessages,
            ], __tr('Event scheduled successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->engineFailedResponse([], $e->getMessage());
        }
    }

    /**
     * Schedule a single message (without event)
     */
    public function scheduleMessage(array $data, $vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();

        $sendAt = Carbon::parse($data['send_at'], $data['timezone'] ?? 'UTC');

        if ($sendAt->isPast()) {
            return $this->engineFailedResponse([], __tr('Cannot schedule message in the past'));
        }

        $message = $this->createScheduledMessage([
            'scheduled_events__id' => null,
            'message_type' => $data['message_type'] ?? ScheduledMessageModel::TYPE_TEMPLATE,
            'contact_phone' => $data['contact_phone'],
            'contact_name' => $data['contact_name'] ?? null,
            'contacts__id' => $data['contacts__id'] ?? null,
            'vendors__id' => $vendorId,
            'send_at' => $sendAt,
            'timezone' => $data['timezone'] ?? 'UTC',
            'template_name' => $data['template_name'] ?? null,
            'template_language' => $data['template_language'] ?? 'en',
            'template_params' => $data['template_params'] ?? [],
            'message_content' => $data['message_content'] ?? null,
            'media_data' => $data['media_data'] ?? null,
        ]);

        return $this->engineSuccessResponse([
            'scheduled_message' => $message,
        ], __tr('Message scheduled successfully'));
    }

    /**
     * Schedule bulk messages
     */
    public function scheduleBulkMessages(array $data, $vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();
        $contacts = $data['contacts'];
        $sendAt = Carbon::parse($data['send_at'], $data['timezone'] ?? 'UTC');

        if ($sendAt->isPast()) {
            return $this->engineFailedResponse([], __tr('Cannot schedule message in the past'));
        }

        $scheduledMessages = [];

        DB::beginTransaction();
        try {
            foreach ($contacts as $contact) {
                $message = $this->createScheduledMessage([
                    'scheduled_events__id' => null,
                    'message_type' => $data['message_type'] ?? ScheduledMessageModel::TYPE_TEMPLATE,
                    'contact_phone' => $contact['phone'],
                    'contact_name' => $contact['name'] ?? null,
                    'contacts__id' => $contact['contacts__id'] ?? null,
                    'vendors__id' => $vendorId,
                    'send_at' => $sendAt,
                    'timezone' => $data['timezone'] ?? 'UTC',
                    'template_name' => $data['template_name'] ?? null,
                    'template_language' => $data['template_language'] ?? 'en',
                    'template_params' => array_merge($data['template_params'] ?? [], $contact['params'] ?? []),
                    'message_content' => $data['message_content'] ?? null,
                ]);
                $scheduledMessages[] = $message;
            }

            DB::commit();

            return $this->engineSuccessResponse([
                'scheduled_count' => count($scheduledMessages),
                'send_at' => $sendAt->toDateTimeString(),
            ], __tr('Messages scheduled successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->engineFailedResponse([], $e->getMessage());
        }
    }

    /**
     * Create a scheduled message record
     */
    protected function createScheduledMessage(array $data): ScheduledMessageModel
    {
        $message = new ScheduledMessageModel();
        $message->_uid = Str::uuid();
        $message->scheduled_events__id = $data['scheduled_events__id'];
        $message->message_type = $data['message_type'];
        $message->contact_phone = $data['contact_phone'];
        $message->contact_name = $data['contact_name'];
        $message->contacts__id = $data['contacts__id'];
        $message->vendors__id = $data['vendors__id'];
        $message->send_at = $data['send_at'];
        $message->timezone = $data['timezone'];
        $message->status = ScheduledMessageModel::STATUS_SCHEDULED;
        $message->template_name = $data['template_name'];
        $message->template_language = $data['template_language'];
        $message->__data = [
            'template_params' => $data['template_params'] ?? [],
            'message_content' => $data['message_content'] ?? null,
            'media_data' => $data['media_data'] ?? null,
        ];
        $message->save();

        return $message;
    }

    /**
     * Process scheduled messages (called by cron)
     */
    public function processScheduledMessages($limit = 50)
    {
        $messages = ScheduledMessageModel::readyToSend()
            ->orderBy('send_at')
            ->limit($limit)
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($messages as $message) {
            // Mark as processing to prevent duplicate sends
            $message->status = ScheduledMessageModel::STATUS_SENT;
            $message->save();

            try {
                $result = $this->sendMessage($message);

                if ($result->success()) {
                    $message->__data = array_merge($message->__data ?? [], [
                        'send_response' => $result->data(),
                        'sent_at' => now()->toDateTimeString(),
                    ]);
                    $message->save();
                    $processed++;
                } else {
                    throw new \Exception($result->message());
                }
            } catch (\Exception $e) {
                $message->retries = ($message->retries ?? 0) + 1;

                if ($message->retries >= 3) {
                    $message->status = ScheduledMessageModel::STATUS_FAILED;
                } else {
                    $message->status = ScheduledMessageModel::STATUS_SCHEDULED;
                    $message->send_at = now()->addMinutes(5);
                }

                $message->__data = array_merge($message->__data ?? [], [
                    'error_message' => $e->getMessage(),
                    'last_error_at' => now()->toDateTimeString(),
                ]);
                $message->save();
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => $messages->count(),
        ];
    }

    /**
     * Send a message based on its type
     */
    protected function sendMessage(ScheduledMessageModel $message)
    {
        $vendorId = $message->vendors__id;
        $data = $message->__data ?? [];

        switch ($message->message_type) {
            case ScheduledMessageModel::TYPE_TEMPLATE:
                return $this->sendTemplateMessage($message, $vendorId);

            case ScheduledMessageModel::TYPE_TEXT:
                return $this->sendTextMessage($message, $vendorId);

            case ScheduledMessageModel::TYPE_MEDIA:
                return $this->sendMediaMessage($message, $vendorId);

            default:
                return $this->engineFailedResponse([], __tr('Unknown message type'));
        }
    }

    /**
     * Send template message
     */
    protected function sendTemplateMessage(ScheduledMessageModel $message, $vendorId)
    {
        $template = $this->whatsAppTemplateRepository->fetchIt([
            'template_name' => $message->template_name,
            'language' => $message->template_language,
            'vendors__id' => $vendorId,
        ]);

        if (!$template) {
            return $this->engineFailedResponse([], __tr('Template not found'));
        }

        $contact = null;
        if ($message->contacts__id) {
            $contact = $this->contactRepository->fetchIt($message->contacts__id);
        }

        if (!$contact) {
            $contact = $this->contactRepository->fetchIt([
                'wa_id' => $message->contact_phone,
                'vendors__id' => $vendorId,
            ]);
        }

        if (!$contact) {
            // Create a mock contact object for sending
            $contact = (object) [
                '_id' => null,
                '_uid' => null,
                'first_name' => $message->contact_name,
                'whatsappNumber' => $message->contact_phone,
                'wa_id' => $message->contact_phone,
            ];
        }

        $templateParams = $message->__data['template_params'] ?? [];

        // Build request-like object
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'template_name' => $message->template_name,
            'template_language' => $message->template_language,
            'template_uid' => $template->_uid,
        ]);

        // Map template params to field_ format
        foreach ($templateParams as $key => $value) {
            $request->merge(["field_{$key}" => $value]);
        }

        return $this->whatsAppServiceEngine->sendTemplateMessageProcess(
            $request,
            $contact,
            false,
            null,
            $vendorId,
            $template
        );
    }

    /**
     * Send text message
     */
    protected function sendTextMessage(ScheduledMessageModel $message, $vendorId)
    {
        $messageContent = $message->__data['message_content'] ?? '';

        if (empty($messageContent)) {
            return $this->engineFailedResponse([], __tr('Message content is empty'));
        }

        $request = new \Illuminate\Http\Request();
        $request->merge([
            'phone_number' => $message->contact_phone,
            'message' => $messageContent,
        ]);

        return $this->whatsAppServiceEngine->processSendMessageForApi($request, $vendorId);
    }

    /**
     * Send media message
     */
    protected function sendMediaMessage(ScheduledMessageModel $message, $vendorId)
    {
        $mediaData = $message->__data['media_data'] ?? [];

        if (empty($mediaData)) {
            return $this->engineFailedResponse([], __tr('Media data is empty'));
        }

        $request = new \Illuminate\Http\Request();
        $request->merge([
            'phone_number' => $message->contact_phone,
            'media_type' => $mediaData['type'] ?? 'image',
            'media_url' => $mediaData['url'] ?? '',
            'caption' => $mediaData['caption'] ?? '',
        ]);

        return $this->whatsAppServiceEngine->processSendMediaMessageForApi($request, $vendorId);
    }

    /**
     * Cancel a scheduled event and its messages
     */
    public function cancelEvent($eventUid, $vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();

        $event = ScheduledEventModel::where('_uid', $eventUid)
            ->forVendor($vendorId)
            ->first();

        if (!$event) {
            return $this->engineFailedResponse([], __tr('Event not found'));
        }

        DB::beginTransaction();
        try {
            $event->status = ScheduledEventModel::STATUS_CANCELLED;
            $event->save();

            ScheduledMessageModel::where('scheduled_events__id', $event->_id)
                ->where('status', ScheduledMessageModel::STATUS_SCHEDULED)
                ->update(['status' => ScheduledMessageModel::STATUS_CANCELLED]);

            DB::commit();

            return $this->engineSuccessResponse([], __tr('Event cancelled successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->engineFailedResponse([], $e->getMessage());
        }
    }

    /**
     * Cancel a single scheduled message
     */
    public function cancelMessage($messageUid, $vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();

        $message = ScheduledMessageModel::where('_uid', $messageUid)
            ->forVendor($vendorId)
            ->where('status', ScheduledMessageModel::STATUS_SCHEDULED)
            ->first();

        if (!$message) {
            return $this->engineFailedResponse([], __tr('Message not found or already processed'));
        }

        $message->status = ScheduledMessageModel::STATUS_CANCELLED;
        $message->save();

        return $this->engineSuccessResponse([], __tr('Message cancelled successfully'));
    }

    /**
     * Get scheduled messages list
     */
    public function getScheduledMessages($vendorId = null, $status = null)
    {
        $vendorId = $vendorId ?: getVendorId();

        $query = ScheduledMessageModel::forVendor($vendorId)
            ->orderBy('send_at', 'asc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->customTableOptions([
            'searchable' => ['contact_phone', 'contact_name', 'template_name'],
        ]);
    }

    /**
     * Get scheduled events list
     */
    public function getScheduledEvents($vendorId = null, $status = null)
    {
        $vendorId = $vendorId ?: getVendorId();

        $query = ScheduledEventModel::forVendor($vendorId)
            ->with('scheduledMessages')
            ->orderBy('event_at', 'asc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->customTableOptions([
            'searchable' => ['contact_phone', 'contact_name', 'event_type'],
        ]);
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats($vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();

        $stats = ScheduledMessageModel::forVendor($vendorId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'scheduled' => $stats[ScheduledMessageModel::STATUS_SCHEDULED] ?? 0,
            'sent' => $stats[ScheduledMessageModel::STATUS_SENT] ?? 0,
            'failed' => $stats[ScheduledMessageModel::STATUS_FAILED] ?? 0,
            'cancelled' => $stats[ScheduledMessageModel::STATUS_CANCELLED] ?? 0,
            'total' => array_sum($stats),
        ];
    }

    /**
     * Create or update event reminder config
     */
    public function saveReminderConfig(array $data, $vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();

        $config = EventReminderConfigModel::updateOrCreate(
            [
                'event_type' => $data['event_type'],
                'reminder_offset_minutes' => $data['reminder_offset_minutes'],
                'vendors__id' => $vendorId,
            ],
            [
                '_uid' => $data['_uid'] ?? Str::uuid(),
                'template_name' => $data['template_name'],
                'template_language' => $data['template_language'] ?? 'en',
                'is_active' => $data['is_active'] ?? true,
                '__data' => $data['__data'] ?? [],
            ]
        );

        return $this->engineSuccessResponse(['config' => $config], __tr('Reminder config saved successfully'));
    }

    /**
     * Get reminder configs for event type
     */
    public function getReminderConfigs($eventType = null, $vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();

        $query = EventReminderConfigModel::forVendor($vendorId);

        if ($eventType) {
            $query->forEventType($eventType);
        }

        return $query->orderBy('event_type')
            ->orderBy('reminder_offset_minutes')
            ->get();
    }

    /**
     * Delete reminder config
     */
    public function deleteReminderConfig($configUid, $vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();

        $deleted = EventReminderConfigModel::where('_uid', $configUid)
            ->forVendor($vendorId)
            ->delete();

        if ($deleted) {
            return $this->engineSuccessResponse([], __tr('Reminder config deleted successfully'));
        }

        return $this->engineFailedResponse([], __tr('Reminder config not found'));
    }
}
