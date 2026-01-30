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
 * TenantWebhookController.php - Controller file
 *
 * This file provides APIs for React SaaS to manage tenant webhooks
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\Vendor\Repositories\VendorRepository;
use App\Yantrana\Components\Vendor\VendorSettingsEngine;

class TenantWebhookController extends BaseController
{
    /**
     * @var VendorRepository - Vendor Repository
     */
    protected $vendorRepository;

    /**
     * @var VendorSettingsEngine - VendorSettings Engine
     */
    protected $vendorSettingsEngine;

    /**
     * Constructor
     *
     * @param VendorRepository $vendorRepository
     * @param VendorSettingsEngine $vendorSettingsEngine
     * @return void
     */
    public function __construct(
        VendorRepository $vendorRepository,
        VendorSettingsEngine $vendorSettingsEngine
    ) {
        $this->vendorRepository = $vendorRepository;
        $this->vendorSettingsEngine = $vendorSettingsEngine;
    }

    /**
     * Generate Webhook URL for Tenant
     *
     * @param string $vendorUid
     * @return json
     */
    public function generateWebhookUrl($vendorUid)
    {
        // Find vendor by UID
        $vendorId = getPublicVendorId($vendorUid);

        if (!$vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found'
            ], 404);
        }

        // Generate webhook URL with query parameter
        $webhookUrl = url('/api/whatsapp-webhook') . '?tenant_id=' . $vendorUid;

        // Generate verify token (sha1 of vendorUid - matches existing logic)
        $verifyToken = sha1($vendorUid);

        // Get vendor settings to check webhook status
        $vendorSettings = getVendorSettings('whatsapp_cloud_api_setup', null, null, $vendorUid);
        $webhookVerifiedAt = $vendorSettings['webhook_verified_at'] ?? null;

        return response()->json([
            'success' => true,
            'data' => [
                'webhook_url' => $webhookUrl,
                'verify_token' => $verifyToken,
                'tenant_id' => $vendorUid,
                'is_verified' => !empty($webhookVerifiedAt),
                'verified_at' => $webhookVerifiedAt,
                'last_webhook_received_at' => $vendorSettings['last_webhook_received_at'] ?? null,
                'callback_url' => $webhookUrl,
            ],
            'message' => 'Webhook URL generated successfully'
        ], 200);
    }

    /**
     * Get Webhook Status for Tenant
     *
     * @param string $vendorUid
     * @return json
     */
    public function getWebhookStatus($vendorUid)
    {
        // Find vendor by UID
        $vendorId = getPublicVendorId($vendorUid);

        if (!$vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found'
            ], 404);
        }

        // Get webhook settings
        $vendorSettings = getVendorSettings('whatsapp_cloud_api_setup', null, null, $vendorUid);

        $webhookVerifiedAt = $vendorSettings['webhook_verified_at'] ?? null;
        $webhookMessagesFieldVerifiedAt = $vendorSettings['webhook_messages_field_verified_at'] ?? null;

        // Determine webhook status
        $status = 'inactive';
        if ($webhookVerifiedAt && $webhookMessagesFieldVerifiedAt) {
            $status = 'active';
        } elseif ($webhookVerifiedAt) {
            $status = 'verified_but_inactive';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tenant_id' => $vendorUid,
                'status' => $status,
                'is_verified' => !empty($webhookVerifiedAt),
                'is_messages_field_verified' => !empty($webhookMessagesFieldVerifiedAt),
                'verified_at' => $webhookVerifiedAt,
                'messages_field_verified_at' => $webhookMessagesFieldVerifiedAt,
                'last_webhook_received_at' => $vendorSettings['last_webhook_received_at'] ?? null,
                'webhook_url' => url('/api/whatsapp-webhook') . '?tenant_id=' . $vendorUid,
            ],
            'message' => 'Webhook status retrieved successfully'
        ], 200);
    }

    /**
     * Verify Webhook Connection (Test)
     *
     * @param string $vendorUid
     * @return json
     */
    public function verifyWebhook($vendorUid)
    {
        // Find vendor by UID
        $vendorId = getPublicVendorId($vendorUid);

        if (!$vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found'
            ], 404);
        }

        // Get webhook settings
        $vendorSettings = getVendorSettings('whatsapp_cloud_api_setup', null, null, $vendorUid);
        $webhookVerifiedAt = $vendorSettings['webhook_verified_at'] ?? null;

        if ($webhookVerifiedAt) {
            return response()->json([
                'success' => true,
                'data' => [
                    'tenant_id' => $vendorUid,
                    'is_verified' => true,
                    'verified_at' => $webhookVerifiedAt,
                ],
                'message' => 'Webhook is verified and active'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'data' => [
                'tenant_id' => $vendorUid,
                'is_verified' => false,
            ],
            'message' => 'Webhook is not verified yet. Please verify using Meta Business Manager.'
        ], 200);
    }
}
