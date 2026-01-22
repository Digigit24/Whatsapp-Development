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
 * SuperAdminWebhookController.php - Controller file
 *
 * This file provides APIs for Superadmin to monitor all tenant webhooks
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use App\Yantrana\Components\Vendor\Models\VendorSettingsModel;
use Illuminate\Support\Facades\DB;

class SuperAdminWebhookController extends BaseController
{
    /**
     * List All Tenant Webhooks
     *
     * @return json
     */
    public function listAllWebhooks()
    {
        // Get all vendors with their settings
        $vendors = VendorModel::select('vendors._id', 'vendors._uid', 'vendors.title', 'vendors.status', 'vendors.created_at')
            ->leftJoin('vendor_settings', function ($join) {
                $join->on('vendors._id', '=', 'vendor_settings.vendors__id')
                    ->where('vendor_settings.name', '=', 'whatsapp_cloud_api_setup');
            })
            ->selectRaw('vendor_settings.value as whatsapp_settings')
            ->get();

        $webhookList = $vendors->map(function ($vendor) {
            $settings = json_decode($vendor->whatsapp_settings, true) ?? [];

            $webhookVerifiedAt = $settings['webhook_verified_at'] ?? null;
            $webhookMessagesFieldVerifiedAt = $settings['webhook_messages_field_verified_at'] ?? null;

            // Determine status
            $status = 'inactive';
            if ($webhookVerifiedAt && $webhookMessagesFieldVerifiedAt) {
                $status = 'active';
            } elseif ($webhookVerifiedAt) {
                $status = 'verified';
            }

            return [
                'tenant_id' => $vendor->_uid,
                'tenant_name' => $vendor->title,
                'vendor_status' => $vendor->status == 1 ? 'active' : 'inactive',
                'webhook_url' => url('/api/whatsapp-webhook') . '?tenant_id=' . $vendor->_uid,
                'webhook_status' => $status,
                'is_verified' => !empty($webhookVerifiedAt),
                'verified_at' => $webhookVerifiedAt,
                'messages_field_verified_at' => $webhookMessagesFieldVerifiedAt,
                'last_webhook_received_at' => $settings['last_webhook_received_at'] ?? null,
                'phone_number_id' => $settings['phone_number_id'] ?? null,
                'business_account_id' => $settings['business_account_id'] ?? null,
                'created_at' => $vendor->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'webhooks' => $webhookList,
                'total_count' => $webhookList->count(),
                'active_count' => $webhookList->where('webhook_status', 'active')->count(),
                'verified_count' => $webhookList->whereIn('webhook_status', ['active', 'verified'])->count(),
                'inactive_count' => $webhookList->where('webhook_status', 'inactive')->count(),
            ],
            'message' => 'All tenant webhooks retrieved successfully'
        ], 200);
    }

    /**
     * Get Webhook Health & Activity Metrics
     *
     * @return json
     */
    public function getWebhookHealth()
    {
        // Get overall webhook statistics
        $totalVendors = VendorModel::where('status', 1)->count();

        // Get vendors with verified webhooks
        $verifiedWebhooks = VendorSettingsModel::where('name', 'whatsapp_cloud_api_setup')
            ->whereNotNull('value')
            ->get()
            ->filter(function ($setting) {
                $value = json_decode($setting->value, true);
                return !empty($value['webhook_verified_at']);
            })
            ->count();

        // Get vendors with active webhooks (both verified and messages field verified)
        $activeWebhooks = VendorSettingsModel::where('name', 'whatsapp_cloud_api_setup')
            ->whereNotNull('value')
            ->get()
            ->filter(function ($setting) {
                $value = json_decode($setting->value, true);
                return !empty($value['webhook_verified_at']) && !empty($value['webhook_messages_field_verified_at']);
            })
            ->count();

        // Get recent webhook activity (last 24 hours)
        $recentActivity = DB::table('whatsapp_webhook_queue')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Get webhook queue processing stats
        $queueStats = [
            'pending' => DB::table('whatsapp_webhook_queue')
                ->whereNull('processed_at')
                ->count(),
            'processed_today' => DB::table('whatsapp_webhook_queue')
                ->whereNotNull('processed_at')
                ->whereDate('processed_at', today())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_tenants' => $totalVendors,
                    'verified_webhooks' => $verifiedWebhooks,
                    'active_webhooks' => $activeWebhooks,
                    'inactive_webhooks' => $totalVendors - $verifiedWebhooks,
                    'verification_rate' => $totalVendors > 0 ? round(($verifiedWebhooks / $totalVendors) * 100, 2) : 0,
                ],
                'activity' => [
                    'webhooks_received_24h' => $recentActivity,
                    'queue_pending' => $queueStats['pending'],
                    'queue_processed_today' => $queueStats['processed_today'],
                ],
                'health_status' => $verifiedWebhooks > 0 ? 'healthy' : 'needs_attention',
            ],
            'message' => 'Webhook health metrics retrieved successfully'
        ], 200);
    }

    /**
     * Test Webhook for Specific Tenant
     *
     * @param string $vendorUid
     * @return json
     */
    public function testWebhook($vendorUid)
    {
        // Find vendor
        $vendorId = getPublicVendorId($vendorUid);

        if (!$vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found'
            ], 404);
        }

        // Get vendor settings
        $vendorSettings = getVendorSettings('whatsapp_cloud_api_setup', null, null, $vendorUid);

        $webhookUrl = url('/api/whatsapp-webhook') . '?tenant_id=' . $vendorUid;
        $verifyToken = sha1($vendorUid);

        return response()->json([
            'success' => true,
            'data' => [
                'tenant_id' => $vendorUid,
                'webhook_url' => $webhookUrl,
                'verify_token' => $verifyToken,
                'test_instructions' => [
                    '1. Copy the webhook URL and verify token above',
                    '2. Go to Meta Business Manager',
                    '3. Navigate to WhatsApp > Configuration > Webhook',
                    '4. Paste the webhook URL',
                    '5. Paste the verify token',
                    '6. Click "Verify and Save"',
                ],
                'verification_status' => [
                    'is_verified' => !empty($vendorSettings['webhook_verified_at']),
                    'verified_at' => $vendorSettings['webhook_verified_at'] ?? null,
                    'messages_field_verified' => !empty($vendorSettings['webhook_messages_field_verified_at']),
                ],
            ],
            'message' => 'Webhook test information retrieved'
        ], 200);
    }

    /**
     * Get Webhook Details for Specific Tenant
     *
     * @param string $vendorUid
     * @return json
     */
    public function getWebhookDetails($vendorUid)
    {
        // Find vendor
        $vendor = VendorModel::where('_uid', $vendorUid)->first();

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found'
            ], 404);
        }

        // Get webhook settings
        $vendorSettings = getVendorSettings('whatsapp_cloud_api_setup', null, null, $vendorUid);

        // Get recent webhook activity
        $recentWebhooks = DB::table('whatsapp_webhook_queue')
            ->where('vendors__id', $vendor->_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['_uid', 'payload', 'created_at', 'processed_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'tenant' => [
                    'id' => $vendor->_uid,
                    'name' => $vendor->title,
                    'status' => $vendor->status == 1 ? 'active' : 'inactive',
                ],
                'webhook' => [
                    'url' => url('/api/whatsapp-webhook') . '?tenant_id=' . $vendorUid,
                    'verify_token' => sha1($vendorUid),
                    'is_verified' => !empty($vendorSettings['webhook_verified_at']),
                    'verified_at' => $vendorSettings['webhook_verified_at'] ?? null,
                    'messages_field_verified_at' => $vendorSettings['webhook_messages_field_verified_at'] ?? null,
                ],
                'whatsapp_config' => [
                    'phone_number_id' => $vendorSettings['phone_number_id'] ?? null,
                    'business_account_id' => $vendorSettings['business_account_id'] ?? null,
                ],
                'recent_activity' => $recentWebhooks,
            ],
            'message' => 'Webhook details retrieved successfully'
        ], 200);
    }
}
