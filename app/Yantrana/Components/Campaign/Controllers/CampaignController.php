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
}
