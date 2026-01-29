<?php
/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2025 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2025, livelyworks
 * @website     https://livelyworks.net
 */

/**
* CampaignController.php - Controller file
*
* This file is part of the Campaign component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Campaign\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequest;
use App\Yantrana\Components\Campaign\CampaignEngine;

class CampaignController extends BaseController
{
    /**
     * @var CampaignEngine - Campaign Engine
     */
    protected $campaignEngine;

    /**
     * Constructor
     *
     * @param  CampaignEngine  $campaignEngine  - Campaign Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(CampaignEngine $campaignEngine)
    {
        $this->campaignEngine = $campaignEngine;
    }

    /**
     * list of Campaign
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showCampaignView()
    {
        validateVendorAccess('manage_campaigns');
        // load the view
        return $this->loadView('campaign.list');
    }

    /**
     * Campaign process delete
     *
     * @param  mix  $campaignUid
     * @return json object
     *---------------------------------------------------------------- */
    public function campaignStatusData($campaignUid, BaseRequest $request)
    {
        validateVendorAccess('manage_campaigns');
        // ask engine to process the request
        $processReaction = $this->campaignEngine->prepareCampaignData($campaignUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * list of Campaign
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareCampaignList($status)
    {
        validateVendorAccess('manage_campaigns');
        // respond with dataTables preparations
        return $this->campaignEngine->prepareCampaignDataTableSource($status);
    }

    /**
     * Campaign process delete
     *
     * @param  mix  $campaignIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processCampaignDelete($campaignIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_campaigns');
        // ask engine to process the request
        $processReaction = $this->campaignEngine->processCampaignDelete($campaignIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
    * Campaign process archive
    *
    * @param  mix  $campaignIdOrUid
    * @return json object
    *---------------------------------------------------------------- */
    public function processCampaignArchive($campaignIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_campaigns');
        // ask engine to process the request
        $processReaction = $this->campaignEngine->processCampaignArchive($campaignIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
    * Campaign process unarchive
    *
    * @param  mix  $campaignIdOrUid
    * @return json object
    *---------------------------------------------------------------- */
    public function processCampaignUnarchive($campaignIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_campaigns');
        // ask engine to process the request
        $processReaction = $this->campaignEngine->processCampaignUnarchive($campaignIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Campaign get update data
     *
     * @param  mix  $campaignIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function updateCampaignData($campaignIdOrUid)
    {
        validateVendorAccess('manage_campaigns');
        $processReaction = $this->campaignEngine->prepareCampaignUpdateData($campaignIdOrUid);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
    * Campaign get status view
    *
    * @param  mix  $campaignIdOrUid
    * @return json object
    *---------------------------------------------------------------- */
    public function campaignStatusView($campaignUid, $pageType = null)
    {
        validateVendorAccess('manage_campaigns');
        $campaignDataResponse = $this->campaignEngine->prepareCampaignData($campaignUid);
        $gotoPage = 'queue';
        if(!$pageType and ($campaignDataResponse->data('campaignStatus') == 'executed') or ($pageType == 'executed')) {
            $gotoPage = 'executed';
        } elseif ($pageType == 'expired') {
            $gotoPage = 'expired';
        }

        $campaignDataResponse->updateData(
            'pageType',
            $gotoPage
        );
        return $this->loadView('whatsapp.campaign-status', $campaignDataResponse->data());
    }

    /**
      * list of campaign queue log
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function campaignQueueLogListView($campaignIdOrUid)
    {
        validateVendorAccess('manage_campaigns');
        // respond with dataTables preparations
        return $this->campaignEngine->prepareCampaignQueueLogList($campaignIdOrUid);
    }

    /**
      * list of executed queue log
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function campaignExecutedLogListView($campaignIdOrUid)
    {
        validateVendorAccess('manage_campaigns');
        // respond with dataTables preparations
        return $this->campaignEngine->prepareCampaignExecutedLogList($campaignIdOrUid);
    }

    /**
      * list of expired queue log
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function campaignExpiredLogListView($campaignIdOrUid)
    {
        validateVendorAccess('manage_campaigns');
        // respond with dataTables preparations
        return $this->campaignEngine->prepareCampaignExpiredLogList($campaignIdOrUid);
    }

    /**
         * campaign Executed report
         *
         * @param string $campaignUid
         * @return file
         */
    public function processCampaignExecutedReportGenerate($campaignUid)
    {
        validateVendorAccess('manage_campaigns');
        return $this->campaignEngine->processGenerateCampaignExecutedReport($campaignUid);
    }

    /**
     * campaign Executed report
     *
     * @param string $campaignUid
     * @return file
     */
    public function processCampaignQueueLogReportGenerate($campaignUid = null)
    {
        validateVendorAccess('manage_campaigns');
        return $this->campaignEngine->processGenerateQueueLogCampaignReport($campaignUid);
    }

    /**
     * campaign Expired report
     *
     * @param string $campaignUid
     * @return file
     */
    public function processCampaignExpiredLogReportGenerate($campaignUid = null)
    {
        validateVendorAccess('manage_campaigns');
        return $this->campaignEngine->processGenerateExpiredLogCampaignReport($campaignUid);
    }

    /**
     * API: Get all campaigns for vendor (External API)
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiGetCampaigns($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');
        $status = request()->get('status', 'active');

        $campaigns = \App\Yantrana\Components\Campaign\Models\CampaignModel::where('vendors__id', $vendorId)
            ->when($status === 'active', fn($q) => $q->where('status', '!=', 5))
            ->when($status === 'archived', fn($q) => $q->where('status', 5))
            ->latest()
            ->get();

        $campaignData = $campaigns->map(function ($campaign) {
            return [
                '_uid' => $campaign->_uid,
                'title' => $campaign->title,
                'template_name' => $campaign->template_name,
                'template_language' => $campaign->template_language,
                'status' => $campaign->status,
                'scheduled_at' => $campaign->scheduled_at,
                'created_at' => $campaign->created_at,
                'total_contacts' => $campaign->__data['total_contacts'] ?? 0,
            ];
        });

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Campaigns fetched successfully'),
        ], $campaignData);
    }

    /**
     * API: Get single campaign (External API)
     *
     * @param string $vendorUid
     * @param string $campaignUid
     * @return json
     */
    public function apiGetCampaign($vendorUid, $campaignUid)
    {
        $vendorId = request()->get('_vendor_id');

        $campaign = \App\Yantrana\Components\Campaign\Models\CampaignModel::where([
            '_uid' => $campaignUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($campaign)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Campaign not found'),
            ]);
        }

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Campaign fetched successfully'),
        ], [
            '_uid' => $campaign->_uid,
            'title' => $campaign->title,
            'template_name' => $campaign->template_name,
            'template_language' => $campaign->template_language,
            'status' => $campaign->status,
            'scheduled_at' => $campaign->scheduled_at,
            'created_at' => $campaign->created_at,
            'timezone' => $campaign->timezone,
            'total_contacts' => $campaign->__data['total_contacts'] ?? 0,
            '__data' => $campaign->__data,
        ]);
    }

    /**
     * API: Get campaign status/stats (External API)
     *
     * @param string $vendorUid
     * @param string $campaignUid
     * @return json
     */
    public function apiGetCampaignStatus($vendorUid, $campaignUid)
    {
        $vendorId = request()->get('_vendor_id');

        $campaign = \App\Yantrana\Components\Campaign\Models\CampaignModel::where([
            '_uid' => $campaignUid,
            'vendors__id' => $vendorId,
        ])->withCount([
            'queueMessages as queue_pending_count' => fn($q) => $q->where('status', 1),
            'queueMessages as queue_failed_count' => fn($q) => $q->where('status', 2),
            'queueMessages as queue_processing_count' => fn($q) => $q->where('status', 3),
            'queueMessages as queue_expired_count' => fn($q) => $q->where('status', 5),
            'messageLog as executed_count',
        ])->first();

        if (__isEmpty($campaign)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Campaign not found'),
            ]);
        }

        $totalContacts = $campaign->__data['total_contacts'] ?? 0;
        $statusText = 'upcoming';
        if (\Carbon\Carbon::parse($campaign->scheduled_at) < now()) {
            if ($campaign->queue_pending_count || $campaign->queue_processing_count) {
                $statusText = 'processing';
            } else {
                $statusText = 'executed';
            }
        }

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Campaign status fetched successfully'),
        ], [
            '_uid' => $campaign->_uid,
            'title' => $campaign->title,
            'status_text' => $statusText,
            'total_contacts' => $totalContacts,
            'queue_pending' => $campaign->queue_pending_count,
            'queue_failed' => $campaign->queue_failed_count,
            'queue_processing' => $campaign->queue_processing_count,
            'queue_expired' => $campaign->queue_expired_count,
            'executed' => $campaign->executed_count,
            'scheduled_at' => $campaign->scheduled_at,
        ]);
    }

    /**
     * API: Delete campaign (External API)
     *
     * @param string $vendorUid
     * @param string $campaignUid
     * @return json
     */
    public function apiDeleteCampaign($vendorUid, $campaignUid)
    {
        $vendorId = request()->get('_vendor_id');

        $campaign = \App\Yantrana\Components\Campaign\Models\CampaignModel::where([
            '_uid' => $campaignUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($campaign)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Campaign not found'),
            ]);
        }

        $processResponse = $this->campaignEngine->processCampaignDelete($campaignUid);

        if ($processResponse->success()) {
            return processExternalApiResponse([
                'result' => 'success',
                'message' => $processResponse->message(),
            ]);
        }

        return processExternalApiResponse([
            'result' => 'failed',
            'message' => $processResponse->message(),
        ]);
    }

    /**
     * API: Archive campaign (External API)
     *
     * @param string $vendorUid
     * @param string $campaignUid
     * @return json
     */
    public function apiArchiveCampaign($vendorUid, $campaignUid)
    {
        $vendorId = request()->get('_vendor_id');

        $campaign = \App\Yantrana\Components\Campaign\Models\CampaignModel::where([
            '_uid' => $campaignUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($campaign)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Campaign not found'),
            ]);
        }

        $processResponse = $this->campaignEngine->processCampaignArchive($campaignUid);

        if ($processResponse->success()) {
            return processExternalApiResponse([
                'result' => 'success',
                'message' => $processResponse->message(),
            ]);
        }

        return processExternalApiResponse([
            'result' => 'failed',
            'message' => $processResponse->message(),
        ]);
    }

    /**
     * API: Unarchive campaign (External API)
     *
     * @param string $vendorUid
     * @param string $campaignUid
     * @return json
     */
    public function apiUnarchiveCampaign($vendorUid, $campaignUid)
    {
        $vendorId = request()->get('_vendor_id');

        $campaign = \App\Yantrana\Components\Campaign\Models\CampaignModel::where([
            '_uid' => $campaignUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($campaign)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Campaign not found'),
            ]);
        }

        $processResponse = $this->campaignEngine->processCampaignUnarchive($campaignUid);

        if ($processResponse->success()) {
            return processExternalApiResponse([
                'result' => 'success',
                'message' => $processResponse->message(),
            ]);
        }

        return processExternalApiResponse([
            'result' => 'failed',
            'message' => $processResponse->message(),
        ]);
    }

    /**
     * API: Create/Schedule campaign (External API)
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiCreateCampaign($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');

        // Validate required fields
        $title = request()->get('title');
        $templateUid = request()->get('template_uid');
        $contactGroup = request()->get('contact_group'); // can be group _id or 'all_contacts'
        $timezone = request()->get('timezone', 'UTC');

        if (empty($title)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Title is required'),
            ]);
        }

        if (empty($templateUid)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Template UID is required'),
            ]);
        }

        if (empty($contactGroup)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact group is required (use group _id or "all_contacts")'),
            ]);
        }

        // Fetch template
        $template = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppTemplateModel::where([
            '_uid' => $templateUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($template)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Template not found'),
            ]);
        }

        // Validate contact group
        $groupContactIds = [];
        $contactGroupData = null;
        $isAllContacts = ($contactGroup === 'all_contacts');

        if (!$isAllContacts) {
            // Try to find group by _id first, then by _uid
            $contactGroupData = \App\Yantrana\Components\Contact\Models\ContactGroupModel::where('vendors__id', $vendorId)
                ->where(function($q) use ($contactGroup) {
                    $q->where('_id', $contactGroup)
                      ->orWhere('_uid', $contactGroup);
                })->first();

            if (__isEmpty($contactGroupData)) {
                return processExternalApiResponse([
                    'result' => 'failed',
                    'message' => __tr('Contact group not found'),
                ]);
            }

            $groupContacts = \App\Yantrana\Components\Contact\Models\GroupContactModel::where('contact_groups__id', $contactGroupData->_id)->get();
            $groupContactIds = $groupContacts->pluck('contacts__id')->toArray();
        }

        // Process schedule time
        $scheduleAt = request()->get('schedule_at');
        if ($scheduleAt) {
            try {
                if (strlen($scheduleAt) == 16) {
                    $scheduleAt = $scheduleAt . ':00';
                }
                $rawTime = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i:s', $scheduleAt, $timezone);
                $scheduleAt = $rawTime->setTimezone('UTC');
            } catch (\Throwable $th) {
                $scheduleAt = now();
            }
        } else {
            $scheduleAt = now();
        }

        // Process expire time
        $expireAt = request()->get('expire_at');
        if ($expireAt) {
            try {
                if (strlen($expireAt) == 16) {
                    $expireAt = $expireAt . ':00';
                }
                $rawTime = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i:s', $expireAt, $timezone);
                $expireAt = $rawTime->setTimezone('UTC')->toDateTimeString();
            } catch (\Throwable $th) {
                $expireAt = null;
            }
        }

        // Get contact labels if provided
        $labelIds = request()->get('contact_labels', []);
        $restrictByTemplateContactLanguage = request()->get('restrict_by_template_language', false);

        // Build contacts query
        $contactsWhereClause = [
            'vendors__id' => $vendorId,
        ];

        $isOnlyForOptedContacts = false;
        if ($template->category == 'MARKETING') {
            $contactsWhereClause['whatsapp_opt_out'] = null;
            $isOnlyForOptedContacts = true;
        }

        if ($restrictByTemplateContactLanguage) {
            $contactsWhereClause['language_code'] = $template->language;
        }

        // Count contacts
        $contactsQuery = \App\Yantrana\Components\Contact\Models\ContactModel::where($contactsWhereClause);

        if (!$isAllContacts && !empty($groupContactIds)) {
            $contactsQuery->whereIn('_id', $groupContactIds);
        }

        if (!empty($labelIds)) {
            $contactsQuery->whereHas('labels', function($q) use ($labelIds) {
                $q->whereIn('labels._id', $labelIds);
            });
        }

        $totalContacts = $contactsQuery->count();

        if ($totalContacts == 0) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('No contacts found matching criteria'),
            ]);
        }

        // Create campaign
        $campaign = \App\Yantrana\Components\Campaign\Models\CampaignModel::create([
            'status' => 1,
            'vendors__id' => $vendorId,
            'users__id' => 1, // API user
            'title' => $title,
            'template_name' => $template->template_name,
            'template_language' => $template->language,
            'whatsapp_templates__id' => $template->_id,
            'scheduled_at' => $scheduleAt,
            'timezone' => $timezone,
            '__data' => [
                'expiry_at' => $expireAt,
                'total_contacts' => $totalContacts,
                'is_for_template_language_only' => $restrictByTemplateContactLanguage,
                'is_for_opted_only_contacts' => $isOnlyForOptedContacts,
                'is_all_contacts' => $isAllContacts,
                'selected_groups' => $isAllContacts ? [] : [
                    $contactGroupData->_uid => [
                        '_id' => $contactGroupData->_id,
                        '_uid' => $contactGroupData->_uid,
                        'title' => $contactGroupData->title,
                        'description' => $contactGroupData->description ?? '',
                        'total_group_contacts' => $totalContacts,
                    ]
                ],
                'template_components' => request()->get('template_components', []),
            ],
        ]);

        if (__isEmpty($campaign)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Failed to create campaign'),
            ]);
        }

        // Queue messages for all contacts
        $contacts = $contactsQuery->get();
        $queueData = [];

        foreach ($contacts as $contact) {
            if (!$contact->wa_id) {
                continue;
            }

            $queueData[] = [
                'vendors__id' => $vendorId,
                'status' => 1, // queue
                'scheduled_at' => $scheduleAt,
                'phone_with_country_code' => $contact->wa_id,
                'campaigns__id' => $campaign->_id,
                'contacts__id' => $contact->_id,
                '__data' => [
                    'expiry_at' => $expireAt,
                    'contact_data' => [
                        '_id' => $contact->_id,
                        '_uid' => $contact->_uid,
                        'first_name' => $contact->first_name,
                        'last_name' => $contact->last_name,
                        'countries__id' => $contact->countries__id,
                    ],
                    'campaign_data' => [
                        'template_name' => $template->template_name,
                        'template_language' => $template->language,
                        'template_components' => request()->get('template_components', []),
                    ]
                ]
            ];
        }

        // Insert queue in chunks
        if (!empty($queueData)) {
            foreach (array_chunk($queueData, 500) as $chunk) {
                \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageQueueModel::insert($chunk);
            }
        }

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Campaign created successfully'),
        ], [
            '_uid' => $campaign->_uid,
            'title' => $campaign->title,
            'template_name' => $campaign->template_name,
            'scheduled_at' => $campaign->scheduled_at,
            'total_contacts' => $totalContacts,
        ]);
    }
}
