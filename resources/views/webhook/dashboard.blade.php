@extends('layouts.app', ['title' => __tr('Webhook Management')])

@section('content')
@include('users.partials.header', [
'title' => __tr('Webhook Management'),
'description' => __tr('Monitor and manage WhatsApp webhooks for all tenants'),
'class' => 'col-lg-12'
])

<div class="container-fluid mt-md--6">
    <!-- Webhook Health Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">Total Tenants</h5>
                            <span class="h2 font-weight-bold mb-0" id="totalTenants">-</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-info text-white rounded-circle shadow">
                                <i class="fa fa-store"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">Active Webhooks</h5>
                            <span class="h2 font-weight-bold mb-0 text-success" id="activeWebhooks">-</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-success text-white rounded-circle shadow">
                                <i class="fa fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">Verification Rate</h5>
                            <span class="h2 font-weight-bold mb-0" id="verificationRate">-</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-warning text-white rounded-circle shadow">
                                <i class="fa fa-percentage"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">Last 24h Activity</h5>
                            <span class="h2 font-weight-bold mb-0" id="webhooksReceived24h">-</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-red text-white rounded-circle shadow">
                                <i class="fa fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Webhooks List -->
    <div class="row mt-4">
        <div class="col-xl-12">
            <div class="card shadow">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0">{{ __tr('All Tenant Webhooks') }}</h3>
                        </div>
                        <div class="col text-right">
                            <button type="button" class="btn btn-sm btn-primary" onclick="refreshWebhookData()">
                                <i class="fa fa-sync"></i> {{ __tr('Refresh') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center table-flush" id="webhooksTable">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ __tr('Tenant') }}</th>
                                <th>{{ __tr('Webhook URL') }}</th>
                                <th>{{ __tr('Status') }}</th>
                                <th>{{ __tr('Verified At') }}</th>
                                <th>{{ __tr('Last Activity') }}</th>
                                <th>{{ __tr('Phone Number ID') }}</th>
                                <th>{{ __tr('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="webhooksTableBody">
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generate Webhook Modal -->
<div class="modal fade" id="generateWebhookModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __tr('Webhook Configuration') }} - <span id="webhookTenantName"></span></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="generateWebhookContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Webhook Details Modal -->
<div class="modal fade" id="webhookDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __tr('Webhook Details') }}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="webhookDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('footer')
<script>
    // Load webhook health metrics
    function loadWebhookHealth() {
        fetch('/api/superadmin/webhooks/health')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalTenants').textContent = data.data.overview.total_tenants;
                    document.getElementById('activeWebhooks').textContent = data.data.overview.active_webhooks;
                    document.getElementById('verificationRate').textContent = data.data.overview.verification_rate + '%';
                    document.getElementById('webhooksReceived24h').textContent = data.data.activity.webhooks_received_24h;
                }
            })
            .catch(error => console.error('Error loading webhook health:', error));
    }

    // Load all webhooks list
    function loadWebhooksList() {
        fetch('/api/superadmin/webhooks/list')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.getElementById('webhooksTableBody');
                    tbody.innerHTML = '';

                    if (data.data.webhooks.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center">{{ __tr("No webhooks found") }}</td></tr>';
                        return;
                    }

                    data.data.webhooks.forEach(webhook => {
                        const statusBadge = getStatusBadge(webhook.webhook_status);
                        const verifiedAt = webhook.verified_at ? new Date(webhook.verified_at).toLocaleString() : '-';
                        const lastActivity = webhook.last_webhook_received_at ? new Date(webhook.last_webhook_received_at).toLocaleString() : '-';

                        const row = `
                            <tr>
                                <td>
                                    <strong>${webhook.tenant_name}</strong><br>
                                    <small class="text-muted">${webhook.tenant_id}</small>
                                </td>
                                <td>
                                    <small class="text-muted" style="word-break: break-all;">
                                        ${webhook.webhook_url}
                                    </small>
                                </td>
                                <td>${statusBadge}</td>
                                <td><small>${verifiedAt}</small></td>
                                <td><small>${lastActivity}</small></td>
                                <td><small>${webhook.phone_number_id || '-'}</small></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="generateWebhookUrl('${webhook.tenant_id}', '${webhook.tenant_name}')">
                                        <i class="fa fa-link"></i> {{ __tr('Webhook') }}
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="viewWebhookDetails('${webhook.tenant_id}')">
                                        <i class="fa fa-eye"></i> {{ __tr('Details') }}
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading webhooks list:', error);
                document.getElementById('webhooksTableBody').innerHTML = '<tr><td colspan="7" class="text-center text-danger">{{ __tr("Error loading webhooks") }}</td></tr>';
            });
    }

    // Get status badge HTML
    function getStatusBadge(status) {
        const badges = {
            'active': '<span class="badge badge-success">Active</span>',
            'verified': '<span class="badge badge-warning">Verified</span>',
            'inactive': '<span class="badge badge-secondary">Inactive</span>',
            'verified_but_inactive': '<span class="badge badge-warning">Verified</span>'
        };
        return badges[status] || '<span class="badge badge-secondary">Unknown</span>';
    }

    // Generate/View Webhook URL
    function generateWebhookUrl(tenantId, tenantName) {
        $('#generateWebhookModal').modal('show');
        document.getElementById('webhookTenantName').textContent = tenantName;
        document.getElementById('generateWebhookContent').innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `;

        fetch(`/api/v1/tenants/${tenantId}/webhook/generate`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const webhook = data.data;
                    let html = `
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> {{ __tr('Use these credentials to register webhook in Meta Business Manager') }}
                        </div>

                        <div class="form-group">
                            <label class="form-control-label"><strong>{{ __tr('Webhook URL') }}</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="webhookUrl" value="${webhook.webhook_url}" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" onclick="copyToClipboard('webhookUrl')">
                                        <i class="fa fa-copy"></i> {{ __tr('Copy') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label"><strong>{{ __tr('Verify Token') }}</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="verifyToken" value="${webhook.verify_token}" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" onclick="copyToClipboard('verifyToken')">
                                        <i class="fa fa-copy"></i> {{ __tr('Copy') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>{{ __tr('Status') }}:</strong>
                                    ${webhook.is_verified ?
                                        '<span class="badge badge-success">✅ {{ __tr("Verified") }}</span>' :
                                        '<span class="badge badge-warning">⏳ {{ __tr("Not Verified") }}</span>'
                                    }
                                </p>
                                ${webhook.verified_at ?
                                    `<p><strong>{{ __tr('Verified At') }}:</strong><br><small>${new Date(webhook.verified_at).toLocaleString()}</small></p>` :
                                    ''
                                }
                            </div>
                            <div class="col-md-6">
                                ${webhook.last_webhook_received_at ?
                                    `<p><strong>{{ __tr('Last Activity') }}:</strong><br><small>${new Date(webhook.last_webhook_received_at).toLocaleString()}</small></p>` :
                                    '<p><strong>{{ __tr("Last Activity") }}:</strong><br><small class="text-muted">{{ __tr("No activity yet") }}</small></p>'
                                }
                            </div>
                        </div>

                        <hr>

                        <div class="alert alert-success">
                            <h5>{{ __tr('Setup Instructions') }}</h5>
                            <ol class="mb-0">
                                <li>{{ __tr('Copy the Webhook URL and Verify Token above') }}</li>
                                <li>{{ __tr('Go to Meta Business Manager') }}</li>
                                <li>{{ __tr('Navigate to WhatsApp > Configuration > Webhook') }}</li>
                                <li>{{ __tr('Paste the Webhook URL') }}</li>
                                <li>{{ __tr('Paste the Verify Token') }}</li>
                                <li>{{ __tr('Click "Verify and Save"') }}</li>
                                <li>{{ __tr('Subscribe to "messages" webhook field') }}</li>
                            </ol>
                        </div>
                    `;
                    document.getElementById('generateWebhookContent').innerHTML = html;
                } else {
                    document.getElementById('generateWebhookContent').innerHTML = `
                        <div class="alert alert-danger">{{ __tr('Error generating webhook URL') }}</div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error generating webhook URL:', error);
                document.getElementById('generateWebhookContent').innerHTML = `
                    <div class="alert alert-danger">{{ __tr('Error loading webhook information') }}</div>
                `;
            });
    }

    // Copy to clipboard function
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        element.select();
        element.setSelectionRange(0, 99999); // For mobile devices

        try {
            document.execCommand('copy');
            // Show success message
            showNotification('{{ __tr("Copied to clipboard!") }}', 'success');
        } catch (err) {
            console.error('Failed to copy:', err);
            showNotification('{{ __tr("Failed to copy") }}', 'error');
        }
    }

    // Show notification
    function showNotification(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const notification = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', notification);

        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            $('.alert').alert('close');
        }, 3000);
    }

    // View webhook details
    function viewWebhookDetails(tenantId) {
        $('#webhookDetailsModal').modal('show');
        document.getElementById('webhookDetailsContent').innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `;

        fetch(`/api/superadmin/webhooks/details/${tenantId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const details = data.data;
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <h5>{{ __tr('Tenant Information') }}</h5>
                                <p><strong>{{ __tr('Name') }}:</strong> ${details.tenant.name}</p>
                                <p><strong>{{ __tr('ID') }}:</strong> ${details.tenant.id}</p>
                                <p><strong>{{ __tr('Status') }}:</strong>
                                    <span class="badge badge-${details.tenant.status === 'active' ? 'success' : 'secondary'}">
                                        ${details.tenant.status}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5>{{ __tr('Webhook Information') }}</h5>
                                <p><strong>{{ __tr('URL') }}:</strong><br>
                                    <small class="text-muted" style="word-break: break-all;">${details.webhook.url}</small>
                                </p>
                                <p><strong>{{ __tr('Verify Token') }}:</strong><br>
                                    <code>${details.webhook.verify_token}</code>
                                </p>
                                <p><strong>{{ __tr('Verified') }}:</strong>
                                    ${details.webhook.is_verified ? '✅ Yes' : '❌ No'}
                                </p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h5>{{ __tr('WhatsApp Configuration') }}</h5>
                                <p><strong>{{ __tr('Phone Number ID') }}:</strong> ${details.whatsapp_config.phone_number_id || '-'}</p>
                                <p><strong>{{ __tr('Business Account ID') }}:</strong> ${details.whatsapp_config.business_account_id || '-'}</p>
                            </div>
                            <div class="col-md-6">
                                <h5>{{ __tr('Recent Activity') }}</h5>
                                ${details.recent_activity.length > 0 ?
                                    `<p>{{ __tr('Last') }} ${details.recent_activity.length} {{ __tr('webhook(s) received') }}</p>` :
                                    `<p class="text-muted">{{ __tr('No recent activity') }}</p>`
                                }
                            </div>
                        </div>
                    `;
                    document.getElementById('webhookDetailsContent').innerHTML = html;
                } else {
                    document.getElementById('webhookDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading details</div>';
                }
            })
            .catch(error => {
                console.error('Error loading webhook details:', error);
                document.getElementById('webhookDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading details</div>';
            });
    }

    // Refresh all webhook data
    function refreshWebhookData() {
        loadWebhookHealth();
        loadWebhooksList();
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadWebhookHealth();
        loadWebhooksList();

        // Auto-refresh every 30 seconds
        setInterval(refreshWebhookData, 30000);
    });
</script>
@endpush
@endsection
