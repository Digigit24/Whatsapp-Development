# WhatsApp Webhook Centralized API Documentation

## Overview

This implementation provides a centralized webhook system for React SaaS where multiple tenants can manage their WhatsApp webhooks through a single API endpoint with query parameters.

---

## Architecture

### Old Flow (Still Works)
```
Meta → /whatsapp-webhook/{vendorUid} → Controller → Engine → Processing
```

### New Flow (Centralized)
```
Meta → /api/whatsapp-webhook?tenant_id={vendorUid} → Controller → Engine → Processing
```

**Key Features:**
- ✅ Backward compatible (old routes still work)
- ✅ Single centralized webhook URL
- ✅ Tenant isolation via query parameter
- ✅ Real-time activity tracking
- ✅ Superadmin monitoring dashboard support

---

## API Endpoints

### 1. Centralized Webhook Endpoint

**URL:** `https://yourapp.com/api/whatsapp-webhook?tenant_id={vendorUid}`

**Methods:** `GET`, `POST`

**Purpose:** Receives all WhatsApp webhook notifications from Meta for all tenants

**GET Request (Verification):**
```bash
# Meta sends this to verify the webhook
GET /api/whatsapp-webhook?tenant_id=abc-123&hub.mode=subscribe&hub.challenge=test123&hub.verify_token=sha1hash
```

**Response:**
```
test123
```

**POST Request (Message/Status Updates):**
```bash
POST /api/whatsapp-webhook?tenant_id=abc-123
Content-Type: application/json

{
  "object": "whatsapp_business_account",
  "entry": [...]
}
```

**Response:**
```json
{
  "status": "success"
}
```

---

### 2. Tenant Webhook Management APIs

Base URL: `/api/v1/tenants/{vendorUid}/webhook`

#### 2.1 Generate Webhook URL

**Endpoint:** `POST /api/v1/tenants/{vendorUid}/webhook/generate`

**Description:** Generates webhook URL and verify token for a tenant

**Example Request:**
```bash
curl -X POST https://yourapp.com/api/v1/tenants/abc-123/webhook/generate
```

**Response:**
```json
{
  "success": true,
  "data": {
    "webhook_url": "https://yourapp.com/api/whatsapp-webhook?tenant_id=abc-123",
    "verify_token": "a94a8fe5ccb19ba61c4c0873d391e987982fbbd3",
    "tenant_id": "abc-123",
    "is_verified": false,
    "verified_at": null,
    "last_webhook_received_at": null,
    "callback_url": "https://yourapp.com/api/whatsapp-webhook?tenant_id=abc-123"
  },
  "message": "Webhook URL generated successfully"
}
```

#### 2.2 Get Webhook Status

**Endpoint:** `GET /api/v1/tenants/{vendorUid}/webhook/status`

**Description:** Check verification and activity status of tenant webhook

**Example Request:**
```bash
curl -X GET https://yourapp.com/api/v1/tenants/abc-123/webhook/status
```

**Response:**
```json
{
  "success": true,
  "data": {
    "tenant_id": "abc-123",
    "status": "active",
    "is_verified": true,
    "is_messages_field_verified": true,
    "verified_at": "2025-01-22 10:30:00",
    "messages_field_verified_at": "2025-01-22 10:35:00",
    "last_webhook_received_at": "2025-01-22 15:45:30",
    "webhook_url": "https://yourapp.com/api/whatsapp-webhook?tenant_id=abc-123"
  },
  "message": "Webhook status retrieved successfully"
}
```

**Status Values:**
- `active` - Webhook verified and messages field verified
- `verified_but_inactive` - Webhook verified but messages field not verified
- `inactive` - Webhook not verified

#### 2.3 Verify Webhook

**Endpoint:** `POST /api/v1/tenants/{vendorUid}/webhook/verify`

**Description:** Test if webhook is verified and active

**Example Request:**
```bash
curl -X POST https://yourapp.com/api/v1/tenants/abc-123/webhook/verify
```

**Response (Verified):**
```json
{
  "success": true,
  "data": {
    "tenant_id": "abc-123",
    "is_verified": true,
    "verified_at": "2025-01-22 10:30:00"
  },
  "message": "Webhook is verified and active"
}
```

**Response (Not Verified):**
```json
{
  "success": false,
  "data": {
    "tenant_id": "abc-123",
    "is_verified": false
  },
  "message": "Webhook is not verified yet. Please verify using Meta Business Manager."
}
```

---

### 3. Superadmin Monitoring APIs

Base URL: `/api/superadmin/webhooks`

#### 3.1 List All Tenant Webhooks

**Endpoint:** `GET /api/superadmin/webhooks/list`

**Description:** Get list of all tenant webhooks with their status

**Example Request:**
```bash
curl -X GET https://yourapp.com/api/superadmin/webhooks/list
```

**Response:**
```json
{
  "success": true,
  "data": {
    "webhooks": [
      {
        "tenant_id": "abc-123",
        "tenant_name": "Acme Corp",
        "vendor_status": "active",
        "webhook_url": "https://yourapp.com/api/whatsapp-webhook?tenant_id=abc-123",
        "webhook_status": "active",
        "is_verified": true,
        "verified_at": "2025-01-22 10:30:00",
        "messages_field_verified_at": "2025-01-22 10:35:00",
        "last_webhook_received_at": "2025-01-22 15:45:30",
        "phone_number_id": "123456789",
        "business_account_id": "987654321",
        "created_at": "2025-01-15 08:00:00"
      }
    ],
    "total_count": 50,
    "active_count": 35,
    "verified_count": 42,
    "inactive_count": 8
  },
  "message": "All tenant webhooks retrieved successfully"
}
```

#### 3.2 Get Webhook Health Metrics

**Endpoint:** `GET /api/superadmin/webhooks/health`

**Description:** Overall webhook system health and activity metrics

**Example Request:**
```bash
curl -X GET https://yourapp.com/api/superadmin/webhooks/health
```

**Response:**
```json
{
  "success": true,
  "data": {
    "overview": {
      "total_tenants": 50,
      "verified_webhooks": 42,
      "active_webhooks": 35,
      "inactive_webhooks": 8,
      "verification_rate": 84.00
    },
    "activity": {
      "webhooks_received_24h": 1250,
      "queue_pending": 5,
      "queue_processed_today": 980
    },
    "health_status": "healthy"
  },
  "message": "Webhook health metrics retrieved successfully"
}
```

#### 3.3 Test Specific Tenant Webhook

**Endpoint:** `POST /api/superadmin/webhooks/test/{vendorUid}`

**Description:** Get test instructions and verification status for specific tenant

**Example Request:**
```bash
curl -X POST https://yourapp.com/api/superadmin/webhooks/test/abc-123
```

**Response:**
```json
{
  "success": true,
  "data": {
    "tenant_id": "abc-123",
    "webhook_url": "https://yourapp.com/api/whatsapp-webhook?tenant_id=abc-123",
    "verify_token": "a94a8fe5ccb19ba61c4c0873d391e987982fbbd3",
    "test_instructions": [
      "1. Copy the webhook URL and verify token above",
      "2. Go to Meta Business Manager",
      "3. Navigate to WhatsApp > Configuration > Webhook",
      "4. Paste the webhook URL",
      "5. Paste the verify token",
      "6. Click 'Verify and Save'"
    ],
    "verification_status": {
      "is_verified": true,
      "verified_at": "2025-01-22 10:30:00",
      "messages_field_verified": true
    }
  },
  "message": "Webhook test information retrieved"
}
```

#### 3.4 Get Webhook Details

**Endpoint:** `GET /api/superadmin/webhooks/details/{vendorUid}`

**Description:** Get detailed webhook info including recent activity

**Example Request:**
```bash
curl -X GET https://yourapp.com/api/superadmin/webhooks/details/abc-123
```

**Response:**
```json
{
  "success": true,
  "data": {
    "tenant": {
      "id": "abc-123",
      "name": "Acme Corp",
      "status": "active"
    },
    "webhook": {
      "url": "https://yourapp.com/api/whatsapp-webhook?tenant_id=abc-123",
      "verify_token": "a94a8fe5ccb19ba61c4c0873d391e987982fbbd3",
      "is_verified": true,
      "verified_at": "2025-01-22 10:30:00",
      "messages_field_verified_at": "2025-01-22 10:35:00"
    },
    "whatsapp_config": {
      "phone_number_id": "123456789",
      "business_account_id": "987654321"
    },
    "recent_activity": [
      {
        "_uid": "wh-001",
        "payload": {...},
        "created_at": "2025-01-22 15:45:30",
        "processed_at": "2025-01-22 15:45:31"
      }
    ]
  },
  "message": "Webhook details retrieved successfully"
}
```

---

## Testing Guide

### Test 1: Old Webhook Route (Backward Compatibility)

**Test Goal:** Ensure existing tenants' webhooks continue working

**Steps:**
1. Use existing webhook URL: `https://yourapp.com/whatsapp-webhook/abc-123`
2. Send GET request with Meta verification params
3. Send POST request with sample webhook payload
4. Verify messages are processed

**Expected Result:** ✅ Works exactly as before

### Test 2: New Centralized Webhook

**Test Goal:** Verify new query parameter route works

**Steps:**
1. Call API to generate webhook URL
   ```bash
   GET /api/v1/tenants/abc-123/webhook/generate
   ```

2. Copy `webhook_url` and `verify_token` from response

3. Register in Meta Business Manager:
   - Webhook URL: `https://yourapp.com/api/whatsapp-webhook?tenant_id=abc-123`
   - Verify Token: `a94a8fe5ccb19ba61c4c0873d391e987982fbbd3`

4. Click "Verify and Save" in Meta

5. Check status:
   ```bash
   GET /api/v1/tenants/abc-123/webhook/status
   ```

**Expected Result:**
```json
{
  "status": "active",
  "is_verified": true
}
```

### Test 3: Tenant Isolation

**Test Goal:** Verify tenants only see their own data

**Steps:**
1. Send webhook for Tenant A: `?tenant_id=tenant-a`
2. Send webhook for Tenant B: `?tenant_id=tenant-b`
3. Check Tenant A's messages
4. Check Tenant B's messages

**Expected Result:** Each tenant only sees their own messages

### Test 4: Superadmin Monitoring

**Test Goal:** Verify superadmin can monitor all webhooks

**Steps:**
1. Call superadmin list endpoint:
   ```bash
   GET /api/superadmin/webhooks/list
   ```

2. Verify response includes all tenants

3. Check health metrics:
   ```bash
   GET /api/superadmin/webhooks/health
   ```

4. Get specific tenant details:
   ```bash
   GET /api/superadmin/webhooks/details/abc-123
   ```

**Expected Result:** All endpoints return correct aggregated data

### Test 5: Activity Tracking

**Test Goal:** Verify last_webhook_received_at updates

**Steps:**
1. Check current status:
   ```bash
   GET /api/v1/tenants/abc-123/webhook/status
   ```
   Note the `last_webhook_received_at` value

2. Send a test message to the tenant's WhatsApp number

3. Wait for webhook to be received

4. Check status again:
   ```bash
   GET /api/v1/tenants/abc-123/webhook/status
   ```

**Expected Result:** `last_webhook_received_at` should be updated to recent timestamp

---

## React Integration Guide

### Tenant Dashboard Example

```javascript
// Generate webhook URL when tenant sets up WhatsApp
async function setupWebhook(tenantId) {
  const response = await fetch(`/api/v1/tenants/${tenantId}/webhook/generate`, {
    method: 'POST'
  });

  const data = await response.json();

  // Show in UI for user to register in Meta
  return {
    webhookUrl: data.data.webhook_url,
    verifyToken: data.data.verify_token
  };
}

// Poll for webhook status
async function checkWebhookStatus(tenantId) {
  const response = await fetch(`/api/v1/tenants/${tenantId}/webhook/status`);
  const data = await response.json();

  return {
    isActive: data.data.status === 'active',
    lastReceived: data.data.last_webhook_received_at
  };
}
```

### Superadmin Dashboard Example

```javascript
// Get all tenant webhooks
async function getAllWebhooks() {
  const response = await fetch('/api/superadmin/webhooks/list');
  const data = await response.json();

  return {
    webhooks: data.data.webhooks,
    stats: {
      total: data.data.total_count,
      active: data.data.active_count,
      inactive: data.data.inactive_count
    }
  };
}

// Monitor webhook health
async function getWebhookHealth() {
  const response = await fetch('/api/superadmin/webhooks/health');
  const data = await response.json();

  return {
    verificationRate: data.data.overview.verification_rate,
    activity24h: data.data.activity.webhooks_received_24h,
    healthStatus: data.data.health_status
  };
}
```

---

## Error Handling

### Common Errors

**1. Missing tenant_id**
```json
{
  "error": "tenant_id query parameter is required",
  "message": "Please provide tenant_id in the query string"
}
```
**HTTP Status:** 400

**2. Tenant not found**
```json
{
  "success": false,
  "message": "Tenant not found"
}
```
**HTTP Status:** 404

**3. Invalid verify token**
```
Invalid request
```
**HTTP Status:** 403

---

## Deployment Checklist

- [ ] Deploy new code to server
- [ ] Verify old webhook route still works: `/whatsapp-webhook/{vendorUid}`
- [ ] Verify new webhook route works: `/api/whatsapp-webhook?tenant_id={uid}`
- [ ] Test tenant API endpoints
- [ ] Test superadmin API endpoints
- [ ] Update React frontend to use new APIs
- [ ] Document webhook URL format for tenant onboarding
- [ ] Monitor logs for any errors
- [ ] Verify webhook activity tracking works

---

## Technical Details

### Database Schema

**vendor_settings table:**
```
- name: 'whatsapp_cloud_api_setup'
- value: JSON {
    webhook_verified_at: timestamp,
    webhook_messages_field_verified_at: timestamp,
    last_webhook_received_at: timestamp,  // NEW
    phone_number_id: string,
    business_account_id: string
  }
```

**whatsapp_webhook_queue table:**
```
- _uid: string
- payload: JSON
- headers: JSON
- vendors__id: integer
- created_at: timestamp
- processed_at: timestamp
```

### Verify Token Generation

```php
$verifyToken = sha1($vendorUid);
```

This matches the existing implementation for backward compatibility.

---

## Support

For issues or questions:
- Check logs in `storage/logs/laravel.log`
- Verify tenant exists in database
- Confirm WhatsApp Business Account is properly configured
- Test with Meta's webhook testing tool

---

## Version

**Implementation Date:** 2025-01-22
**Laravel Version:** Compatible with existing codebase
**Breaking Changes:** None (fully backward compatible)
