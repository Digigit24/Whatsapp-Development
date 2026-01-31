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
* WhatsAppServiceController.php - Controller file
*
* This file is part of the WhatsAppService component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Controllers;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use App\Yantrana\Components\Vendor\VendorSettingsEngine;
use App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine;
use App\Yantrana\Components\WhatsAppService\WhatsAppTemplateEngine;


class WhatsAppServiceController extends BaseController
{
    /**
     * @var WhatsAppServiceEngine - WhatsAppService Engine
     */
    protected $whatsAppServiceEngine;

    /**
     * @var VendorSettingsEngine - VendorSettings  Engine
     */
    protected $vendorSettingsEngine;
    /**
     * @var WhatsAppTemplateEngine - WhatsApp TemplateEngine  Engine
     */
    protected $whatsAppTemplateEngine;

    /**
     * Constructor
     *
     * @param  WhatsAppServiceEngine  $whatsAppServiceEngine  - WhatsAppService Engine
     * @param  VendorSettingsEngine  $vendorSettingsEngine  - VendorSettings Engine
     * @param  WhatsAppTemplateEngine  $whatsAppTemplateEngine  - WhatsApp Template Engine
     *
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(
        WhatsAppServiceEngine $whatsAppServiceEngine,
        VendorSettingsEngine $vendorSettingsEngine,
        WhatsAppTemplateEngine $whatsAppTemplateEngine
    ) {
        $this->whatsAppServiceEngine = $whatsAppServiceEngine;
        $this->vendorSettingsEngine = $vendorSettingsEngine;
        $this->whatsAppTemplateEngine = $whatsAppTemplateEngine;
    }

    /**
     * Send Template Message View
     *
     * @param string $contactUid
     * @return view
     */
    public function sendTemplateMessageView($contactUid)
    {
        validateVendorAccess('messaging');
        $sendMessageResponseData = $this->whatsAppServiceEngine->sendMessageData($contactUid);
        // load the view
        return $this->loadView('whatsapp.template-send-message', $sendMessageResponseData->data());
    }

    /**
     * Template Based Send Message Process
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function sendTemplateMessageProcess(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'template_uid' => 'required',
            'contact_uid' => 'required',
        ]);
        $processReaction = $this->whatsAppServiceEngine->processSendMessageForContact($request);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }

        return $this->responseAction(
            $this->processResponse($processReaction),
            $this->redirectTo('vendor.chat_message.contact.view', [
                'contactUid' => $processReaction->data('contactUid'),
            ], [
                $processReaction->message(),
                'success',
            ])
        );
    }

    /**
     * Schedule Campaign
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function scheduleCampaign(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_campaigns');
        $request->validate([
            'template_uid' => 'required',
            'contact_group' => 'required',
            'timezone' => 'required',
            'title' => 'required',
            'contact_labels' => 'array',
            'schedule_at' => 'nullable|date',
            'expire_at' => 'nullable|date|after:schedule_at'
        ]);
        $processReaction = $this->whatsAppServiceEngine->processCampaignCreate($request);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }

        return $this->responseAction(
            $this->processResponse($processReaction),
            $this->redirectTo('vendor.campaign.status.view', [
                'campaignUid' => $processReaction->data('campaignUid'),
            ], [
                $processReaction->message(),
                'success',
            ])
        );
    }

    /**
     * Create new Campaign View
     *
     * @return view
     */
    public function createNewCampaign()
    {
        validateVendorAccess('manage_campaigns');
        $campaignRequiredData = $this->whatsAppServiceEngine->campaignRequiredData();
        // load the view
        return $this->loadView('whatsapp.template-send-message', $campaignRequiredData->data());
    }

    /**
     * Check if has API feature enabled in plan or abort
     *
     * @param int $vendorId
     * @return void
     */
    protected function apiAccessAllowedOrAbort($vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();
        // check the feature limit
        $vendorPlanDetails = vendorPlanDetails('api_access', 0, $vendorId);
        abortIf(!$vendorPlanDetails['is_limit_available'], 401, 'API access is not available in your plan, please upgrade your subscription plan.');
    }

    /**
     * Send Chat Message
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function sendChatMessage(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }
        $request->validate([
            'contact_uid' => 'required',
            'message_body' => 'required',
        ]);

        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }
        return $this->processResponse($processReaction);
    }
    /**
     * Send Chat Message
     *
     * @param BaseRequestTwo $request
     * @since - 2.0.0
     *
     * @return json
     */
    public function apiSendChatMessage(BaseRequestTwo $request, $vendorUid)
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }
        // validate the inputs
        $request->validate([
            'phone_number' => 'required',
            'message_body' => 'required',
        ]);
        // send message
        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request);
        // processed data
        $processedData = $processReaction->data();
        // get back the response
        return $this->processApiResponse($processReaction, [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'contact_uid' => $processedData['contact']['_uid'] ?? null,
            'phone_number' => $processedData['log_message']['contact_wa_id'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
     * Send Chat Media Based Chat Message
     *
     * @param BaseRequestTwo $request
     * @since - 2.0.0
     *
     * @return json
     */
    public function apiSendMediaChatMessage(BaseRequestTwo $request)
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }
        // validate the inputs
        $request->validate([
            'phone_number' => 'required',
            'media_type' => [
                'required',
                Rule::in([
                    'image',
                    'video',
                    'document',
                    'audio',
                ])
            ],
            'media_url' => 'required|url',
        ]);
        // send message
        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request, true);
        // processed data
        $processedData = $processReaction->data();
        // get back the response
        return $this->processApiResponse($processReaction, [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'contact_uid' => $processedData['contact']['_uid'] ?? null,
            'phone_number' => $processedData['log_message']['contact_wa_id'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
     * Send Chat Interactive Based Chat Message
     *
     * @param BaseRequestTwo $request
     * @since - 2.0.0
     *
     * @return json
     */
    public function apiSendInteractiveChatMessage(BaseRequestTwo $request)
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }

        $validations = [
            'phone_number' => 'required'
        ];
        
        if($request->interactive_type == 'cta_url') {
            $validations['cta_url.display_text'] = "required|min:1|max:20";
            $validations['cta_url.url'] = "required";
        } elseif($request->interactive_type == 'list') {
            $validations['list_data'] = 'required|array';
            $validations['list_data.button_text'] = 'required|string';
            $validations['list_data.sections'] = 'required|array';
            $validations['list_data.sections.*.title'] = 'required|string';
            $validations['list_data.sections.*.id'] = 'required|string';
            $validations['list_data.sections.*.rows'] = 'required|array';
            $validations['list_data.sections.*.rows.*.id'] = 'required|string';
            $validations['list_data.sections.*.rows.*.row_id'] = 'required|string';
            $validations['list_data.sections.*.rows.*.title'] = 'required|string';
            $validations['list_data.sections.*.rows.*.description'] = 'required|string';
        } else {
            // must be reply button type
            // at least 1 button is required
            $validations['buttons.1'] = "required|min:1|max:20";
            $validations['buttons.2'] = "nullable|min:1|max:20";
            $validations['buttons.3'] = "nullable|min:1|max:20";
            if(array_filter($request->buttons) != array_unique(array_filter($request->buttons))) {
                return $this->processResponse(3, [
                    3 => __tr('Buttons labels should be unique.')
                ], [], true);
            }
        }
        // if header is not a text then it should be media
        if($request->header_type == 'text') {
            // if header text then its required
            $validations['header_text'] = "required";
        }
        
        // validate the inputs
        $request->validate($validations);
        
        $inputData = $request->all();
        $inputData['messageBody'] = '';
        $inputData['contactUid'] = '';
        // send message
        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($inputData, false, false, [
            'interaction_message_data' => $request->except('from_phone_number_id', 'phone_number', 'contact')
        ]);
        // processed data
        $processedData = $processReaction->data();
        // get back the response
        return $this->processApiResponse($processReaction, [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'contact_uid' => $processedData['contact']['_uid'] ?? null,
            'phone_number' => $processedData['log_message']['contact_wa_id'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
    * Send Template Chat Message
    *
    * @param BaseRequestTwo $request
    * @since - 2.0.0
    *
    * @return json
    */
    public function apiSendTemplateChatMessage(BaseRequestTwo $request, $vendorUid)
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }
        // validate the inputs
        $request->validate([
            'phone_number' => 'required',
            'template_name' => 'required',
            'template_language' => 'required',
            'header_image' => 'sometimes|url',
            'header_video' => 'sometimes|url',
            'header_document' => 'sometimes|url',
        ]);
        // send message
        $processReaction = $this->whatsAppServiceEngine->processSendMessageForContact($request);
        // processed data
        $processedData = $processReaction->data();
        // get back the response
        return $this->processApiResponse($processReaction, [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'contact_uid' => $processedData['contactUid'] ?? null,
            'phone_number' => $processedData['log_message']['contact_wa_id'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
     * Prepare Upload Media for the message
     *
     * @param BaseRequestTwo $request
     * @param string $mediaType
     * @return json
     */
    public function prepareSendMediaUploader(BaseRequestTwo $request, $mediaType = 'image')
    {
        if (! in_array($mediaType, [
            'image',
            'video',
            'audio',
            'document',
        ])) {
            return $this->processResponse(2, [
                __tr('Invalid media type'),
            ]);
        }

        return $this->processResponse(1, [], [
            'uploadTitle' => __tr('Select __mediaType__', [
                '__mediaType__' => $mediaType,
            ]),
            'mediaType' => $mediaType,
        ]);
    }

    /**
     * Send Chat Media Based Chat Message
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function sendChatMessageMedia(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }

        $request->validate([
            'contact_uid' => 'required',
            'media_type' => 'required',
            'uploaded_media_file_name' => 'required',
        ]);

        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request, true);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }

        return $this->processResponse($processReaction);
    }

    /**
     * Load Chat View
     *
     * @param string $contactUid
     * @return view
     */
    public function chatView($contactUid = null)
    {

        validateVendorAccess('messaging');
        if(!isVendorAdmin(getVendorId()) and hasVendorAccess('assigned_chats_only')) {
            request()->merge([
                'assigned' => 'to-me'
            ]);
        }
        $assigned = request()->assigned;
        $chatData = $this->whatsAppServiceEngine->chatData($contactUid, $assigned);
       
        if(request()->ajax()) {
            updateClientModels($chatData->data(), 'append');
            return $this->processResponse(1, [], [
                'currentlyAssignedUserUid' => $chatData->data('currentlyAssignedUserUid'),
            ]);
        }
        // load the view
        return $this->loadView('whatsapp.chat', $chatData->data());
    }

    /**
     * Get the contact chat data
     *
     * @param string $contactUid
     * @return json
     */
    public function getContactChatData($contactUid, $way = 'append')
    {
        validateVendorAccess('messaging');
        $processReaction = $this->whatsAppServiceEngine->contactChatData($contactUid);
        updateClientModels([
            'whatsappMessageLogs' => $processReaction->data('whatsappMessageLogs'),
        ], $way);

        return $this->processResponse($processReaction);
    }

    /**
     * Get the contacts list
     *
     * @param string $contactUid
     * @return void
     */
    public function getContactsData(BaseRequestTwo $request, $contactUid = null)
    {
        validateVendorAccess('messaging');
        $assigned = $request->assigned;
        $processReaction = $this->whatsAppServiceEngine->contactsData($contactUid, $assigned);
        updateClientModels($processReaction->data(), $request->way);

        return $this->processResponse($processReaction);
    }

    /**
     * Clear the user chat history on our system
     *
     * @param BaseRequestTwo $request
     * @param string $contactUid
     * @return void
     */
    public function clearChatHistory(BaseRequestTwo $request, $contactUid)
    {
        validateVendorAccess('messaging');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $processReaction = $this->whatsAppServiceEngine->processClearChatHistory($contactUid);

        return $this->processResponse($processReaction);
    }

    /**
     * Change Template
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function changeTemplate(BaseRequestTwo $request)
    {
        validateVendorAccess([
            'manage_campaigns',
            'messaging',
        ]);
        $request->validate([
            'template_selection' => [
                'required',
                'uuid',
            ],
        ]);
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->processTemplateChange($request->get('template_selection'), $request->get('page_type'));
        if ($processReaction->success()) {
            return $this->responseAction(
                $this->processResponse($processReaction, [], [
                    '_uid' => $request->get('template_selection')
                ]),
                $this->replaceContent($processReaction->data('template'), '#lwTemplateStructureContainer')
            );
        }

        // get back with response
        return $this->processResponse($processReaction);
    }

    /**
     * Run Campaign Schedule mostly using Cron
     *
     * @param BaseRequestTwo $request
     * @param string $token - not in use for now
     * @return json
     */
    public function runCampaignSchedule(BaseRequestTwo $request, $token = '')
    {
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->processCampaignSchedule();
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * WhatsApp Webhook for the notifications from WhatsApp
     *
     * @param BaseRequestTwo $request
     * @param string $vendorUid
     * @return void
     */
    public function webhook(BaseRequestTwo $request, $vendorUid)
    {
        // webhook verification process
        if ($request->isMethod('get')) {
            if ($request->has('hub_challenge') and $request->has('hub_verify_token')) {
                $verifyToken = sha1($vendorUid);
                if ($request->get('hub_verify_token') === $verifyToken) {
                    // if its base webhook call from service
                    if($vendorUid == 'service-whatsapp') {
                        return response($request->get('hub_challenge'));
                    }
                    $vendorId = getPublicVendorId($vendorUid);
                    if (!$vendorId) {
                        return false;
                    }
                    // update configuration for webhook
                    $this->vendorSettingsEngine->updateProcess('whatsapp_cloud_api_setup', [
                        'webhook_verified_at' => now()
                    ], $vendorId);
                    updateModelsViaVendorBroadcast($vendorUid, [
                        'isWebhookVerified' => true
                    ]);
                    return response($request->get('hub_challenge'));
                }
            }
            return response('Invalid request', 403);
        }
        // process the other update requests
        $this->whatsAppServiceEngine->processWebhook($request, $vendorUid);
        return response('done', 200);
    }

    /**
     * Get unread message count for vendor
     *
     * @return json
     */
    public function unreadCount()
    {
        validateVendorAccess([
            'manage_campaigns',
            'messaging',
        ]);
        $processReaction = $this->whatsAppServiceEngine->updateUnreadCount();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Refresh WhatsApp Business Account Health Info
     *
     * @return json
     */
    public function getHealthStatus()
    {
        validateVendorAccess('administrative');
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }

        $processReaction = $this->whatsAppServiceEngine->refreshHealthStatus();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Refresh WhatsApp Business Account Health Info
     *
     * @return json
     */
    public function syncPhoneNumbers()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }

        $processReaction = $this->whatsAppServiceEngine->processSyncPhoneNumbers();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Store the tokens and info
     *
     * @param BaseRequestTwo $request
     * @return array
     */
    public function embeddedSignUpProcess(BaseRequestTwo $request)
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $validations = [
            'request_code' => [
                'required'
            ],
            'waba_id' => [
                'required',
                'numeric'
            ],
        ];
        if(!$request->is_app_onboarding) {
            $validations['phone_number_id'] = [
                 'required',
                'numeric'
            ];
        }
        $request->validate($validations);
        $processReaction = $this->whatsAppServiceEngine->setupWhatsAppEmbeddedSignUpProcess($request);
        if($processReaction->success()) {
            // sync templates
            $this->whatsAppTemplateEngine->processSyncTemplates();
            return $this->processResponse(21, [], [
                'reloadPage' => true
            ], true);
        }
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Disconnect Base Webhook
     *
     * @return json
     */
    public function disconnectWebhook()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $processReaction = $this->whatsAppServiceEngine->processDisconnectWebhook();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Disconnect Base Webhook
     *
     * @return json
     */
    public function disconnectAccount()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $processReaction = $this->whatsAppServiceEngine->processDisconnectAccount();
        if($processReaction->success()) {
            return $this->processResponse(21, [], [
                'reloadPage' => true,
                'show_message' => true,
                'messageType' => 'success',
            ], true);
        }
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Connect Base Webhook
     *
     * @return json
     */
    public function connectWebhook()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $processReaction = $this->whatsAppServiceEngine->processConnectWebhook();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Requeue failed messages for processing
     *
     * @param BaseRequestTwo $request
     * @param string $campaignUid  campaign uid
     * @return json
     */
    public function requeueCampaignFailedMessages(BaseRequestTwo $request, $campaignUid)
    {
        validateVendorAccess('manage_campaigns');
        $processReaction = $this->whatsAppServiceEngine->processRequeueFailedMessages($request, $campaignUid);
        // get back with response
        if ($processReaction->success()) {
            return $this->processResponse($processReaction, [], [
                // reload datatable on success
                'reloadDatatableId' => '#lwCampaignQueueLog'
            ]);
        }
        return $this->processResponse($processReaction);
    }

    /**
     * Get Business Profile
     *
     * @param int $phoneNumberId
     * @return json
     */
    function getBusinessProfile($phoneNumberId) {
        validateVendorAccess('administrative');
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->requestBusinessProfile($phoneNumberId);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Update Business Profile
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    function updateBusinessProfile(BaseRequestTwo $request) {
        validateVendorAccess('administrative');
        $request->validate([
            'address' => [
                'nullable',
                'max:256',
            ],
            'description' => [
                'nullable',
                'max:256',
            ],
            'about' => [
                'nullable',
                'max:139',
            ],
            'about' => [
                'nullable',
                'max:139',
            ],
            'email' => [
                'nullable',
                'email',
                'max:128',
            ],
        ]);
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->requestUpdateBusinessProfile($request);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Get Display Name
     *
     * @param int $phoneNumberId
     * @return json
     */
    function getDisplayName($phoneNumberId) {
        validateVendorAccess('administrative');
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->requestDisplayName($phoneNumberId);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Update Display Name
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    function updateDisplayName(BaseRequestTwo $request) {
        validateVendorAccess('administrative');
        $request->validate([
            'verified_name' => [
                'required',
                'max:256',
            ]
        ]);
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->requestUpdateDisplayName($request);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Update Two Step Verification Plugin
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    function updateTwoStepVerification(BaseRequestTwo $request) {
        validateVendorAccess('administrative');
        $request->validate([
            'pin' => [
                'required',
                'numeric',
                'digits:6',
            ],
        ]);
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->processTwoStepVerification($request);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }



    /**
     *message log view
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showMessageLogView(BaseRequestTwo $request)
    {
        validateVendorAccess('administrative');
        // load the view
        return $this->loadView('whatsapp.message-log-list');
    }

    /**
     * list of message log
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareMessageLogList(BaseRequestTwo $request,$type=null,$startDate=null,$endDate=null)
    {
        validateVendorAccess('administrative');
        if($startDate == 'any') {
            $startDate = null;
        }
        if($endDate == 'any') {
            $endDate = null;
        }
        if( $startDate or  $endDate) {
            //validation works when both dates available.
            if ($startDate) {  
                request()->merge(['msg_start_date' => $startDate]);
            
                request()->validate([
                    "msg_start_date" => "date|before_or_equal:today",
                ], [
                    "msg_start_date.before_or_equal" => "The message start date must be a date before or equal to today."
                ]);
            }
            
            if ($endDate) {  
                request()->merge(['msg_end_date' => $endDate]);
            
                request()->validate([
                    "msg_end_date" => "date|before_or_equal:today",
                ], [
                    "msg_end_date.before_or_equal" => "The message end date must be a date before or equal to today."
                ]);
            }
            
            // If both start and end dates are provided, validate their relation
            if ($startDate and  $endDate) {  
                request()->validate([
                    "msg_end_date" => "after_or_equal:msg_start_date",
                ], [
                    "msg_end_date.after_or_equal" => "The message end date must be a date after or equal to the message start date."
                ]);
            }
        }
        

        // respond with dataTables preparations
        return $this->whatsAppServiceEngine->fetchWhatsappMessageLogData($type,$startDate,$endDate);
    }

     /**
     * Message get update data
     *
     * @param  mix  $messageIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function updateMessageData($messageIdOrUid)
    {
        validateVendorAccess('administrative');
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->prepareMessageUpdateData($messageIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * API: Get chat contacts list (inbox view) - External API
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiGetChatContacts($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');
        $assigned = request()->get('assigned'); // 'to-me', 'unassigned', user_id, or null for all
        $search = request()->get('search');
        $unreadOnly = request()->get('unread_only', false);
        $labelId = request()->get('label_id');
        $page = request()->get('page', 1);
        $limit = request()->get('limit', 20);

        $query = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->has('lastIncomingMessage')
            ->with(['lastMessage', 'lastIncomingMessage', 'labels', 'assignedUser'])
            ->withCount(['unreadMessages']);

        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('wa_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Assignment filter
        if ($assigned === 'to-me') {
            $query->where('assigned_users__id', getUserID());
        } elseif ($assigned === 'unassigned') {
            $query->whereNull('assigned_users__id');
        } elseif (is_numeric($assigned)) {
            $query->where('assigned_users__id', $assigned);
        }

        // Unread only filter
        if ($unreadOnly && $unreadOnly !== 'false') {
            $query->has('unreadMessages');
        }

        // Label filter
        if ($labelId) {
            $query->whereHas('labels', function($q) use ($labelId) {
                $q->where('labels._id', $labelId);
            });
        }

        // Order by latest message using subquery
        $query->orderByDesc(
            \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::select('messaged_at')
                ->whereColumn('contacts__id', 'contacts._id')
                ->orderByDesc('messaged_at')
                ->limit(1)
        );

        $contacts = $query->paginate($limit, ['*'], 'page', $page);

        $contactData = $contacts->map(function ($contact) {
            // Calculate reply window status
            $lastIncoming = $contact->lastIncomingMessage;
            $isReplyWindowOpen = $lastIncoming && $lastIncoming->messaged_at->diffInHours(now()) < 24;
            $replyWindowExpiresAt = $lastIncoming ? $lastIncoming->messaged_at->addHours(24) : null;

            return [
                '_uid' => $contact->_uid,
                '_id' => $contact->_id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'full_name' => $contact->full_name,
                'name_initials' => $contact->name_initials,
                'wa_id' => $contact->wa_id,
                'email' => $contact->email,
                'language_code' => $contact->language_code,
                'country_code' => $contact->countries__id,
                'unread_messages_count' => $contact->unread_messages_count ?? 0,
                'is_blocked' => !empty($contact->wa_blocked_at),
                'wa_blocked_at' => $contact->wa_blocked_at,
                'disable_ai_bot' => (bool) $contact->disable_ai_bot,
                'disable_reply_bot' => (bool) $contact->disable_reply_bot,
                'is_reply_window_open' => $isReplyWindowOpen,
                'reply_window_expires_at' => $replyWindowExpiresAt?->toIso8601String(),
                'reply_window_expires_human' => $replyWindowExpiresAt?->diffForHumans(['parts' => 2, 'join' => true]),
                'last_message' => $contact->lastMessage ? [
                    '_uid' => $contact->lastMessage->_uid,
                    'message' => $contact->lastMessage->message,
                    'is_incoming_message' => (bool) $contact->lastMessage->is_incoming_message,
                    'status' => $contact->lastMessage->status,
                    'messaged_at' => $contact->lastMessage->messaged_at?->toIso8601String(),
                    'formatted_message_ago_time' => $contact->lastMessage->formatted_message_ago_time,
                ] : null,
                'labels' => $contact->labels ? $contact->labels->map(fn($l) => [
                    '_id' => $l->_id,
                    '_uid' => $l->_uid,
                    'title' => $l->title,
                    'bg_color' => $l->bg_color,
                    'text_color' => $l->text_color,
                ])->values() : [],
                'assigned_user' => $contact->assignedUser ? [
                    '_id' => $contact->assignedUser->_id,
                    '_uid' => $contact->assignedUser->_uid,
                    'first_name' => $contact->assignedUser->first_name,
                    'last_name' => $contact->assignedUser->last_name,
                    'full_name' => trim($contact->assignedUser->first_name . ' ' . $contact->assignedUser->last_name),
                ] : null,
            ];
        });

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Chat contacts fetched successfully'),
        ], [
            'contacts' => $contactData,
            'pagination' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
                'has_more' => $contacts->hasMorePages(),
            ]
        ]);
    }

    /**
     * API: Get messages for a contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiGetContactMessages($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');
        $page = request()->get('page', 1);
        $limit = request()->get('limit', 50);
        $markAsRead = request()->get('mark_as_read', true);

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->with(['labels', 'assignedUser', 'lastIncomingMessage'])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        // Mark messages as read (update status from 'received' to 'read')
        if ($markAsRead && $markAsRead !== 'false') {
            \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::where([
                'contacts__id' => $contact->_id,
                'is_incoming_message' => 1,
                'status' => 'received',
            ])->update(['status' => 'read']);
        }

        $messages = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::where('contacts__id', $contact->_id)
            ->orderByDesc('messaged_at')
            ->paginate($limit, ['*'], 'page', $page);

        $messageData = $messages->map(function ($msg) {
            // Compile template message if needed
            $templateMessage = null;
            if (!$msg->message || isset($msg->__data['interaction_message_data'])) {
                $templateMessage = $this->whatsAppServiceEngine->compileMessageForApi($msg->__data);
            }

            return [
                '_uid' => $msg->_uid,
                'wamid' => $msg->wamid,
                'message' => $this->whatsAppServiceEngine->formatWhatsAppTextForApi($msg->message),
                'message_raw' => $msg->message,
                'is_incoming_message' => (bool) $msg->is_incoming_message,
                'is_system_message' => (bool) $msg->is_system_message,
                'status' => $msg->status,
                'messaged_at' => $msg->messaged_at?->toIso8601String(),
                'formatted_message_time' => $msg->formatted_message_time,
                'formatted_message_ago_time' => $msg->formatted_message_ago_time,
                'whatsapp_message_error' => $msg->whatsapp_message_error,
                'replied_to_message_uid' => $msg->replied_to_whatsapp_message_logs__uid ?? null,
                'campaigns__id' => $msg->campaigns__id,
                'template_message' => $templateMessage,
                'message_type' => $msg->__data['message_type'] ?? 'text',
                'media_values' => $msg->__data['media_values'] ?? null,
                'template_data' => $msg->__data['template_data'] ?? null,
                'template_components' => $msg->__data['template_components'] ?? null,
                'interaction_message_data' => $msg->__data['interaction_message_data'] ?? null,
                'is_bot_reply' => $msg->__data['options']['bot_reply'] ?? false,
                'is_ai_bot_reply' => $msg->__data['options']['ai_bot_reply'] ?? false,
            ];
        });

        // Check if reply window is open (received message in last 24 hours)
        $lastIncoming = $contact->lastIncomingMessage;
        $isReplyWindowOpen = $lastIncoming && $lastIncoming->messaged_at->diffInHours(now()) < 24;
        $replyWindowExpiresAt = $lastIncoming ? $lastIncoming->messaged_at->addHours(24) : null;

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Messages fetched successfully'),
        ], [
            'contact' => [
                '_uid' => $contact->_uid,
                '_id' => $contact->_id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'full_name' => $contact->full_name,
                'name_initials' => $contact->name_initials,
                'wa_id' => $contact->wa_id,
                'email' => $contact->email,
                'language_code' => $contact->language_code,
                'is_blocked' => !empty($contact->wa_blocked_at),
                'wa_blocked_at' => $contact->wa_blocked_at,
                'disable_ai_bot' => (bool) $contact->disable_ai_bot,
                'disable_reply_bot' => (bool) $contact->disable_reply_bot,
                'notes' => $contact->__data['contact_notes'] ?? '',
                'labels' => $contact->labels ? $contact->labels->map(fn($l) => [
                    '_id' => $l->_id,
                    '_uid' => $l->_uid,
                    'title' => $l->title,
                    'bg_color' => $l->bg_color,
                    'text_color' => $l->text_color,
                ])->values() : [],
                'assigned_user' => $contact->assignedUser ? [
                    '_id' => $contact->assignedUser->_id,
                    '_uid' => $contact->assignedUser->_uid,
                    'first_name' => $contact->assignedUser->first_name,
                    'last_name' => $contact->assignedUser->last_name,
                    'full_name' => trim($contact->assignedUser->first_name . ' ' . $contact->assignedUser->last_name),
                ] : null,
            ],
            'is_reply_window_open' => $isReplyWindowOpen,
            'reply_window_expires_at' => $replyWindowExpiresAt?->toIso8601String(),
            'reply_window_expires_human' => $replyWindowExpiresAt?->diffForHumans(['parts' => 2, 'join' => true]),
            'messages' => $messageData,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'has_more' => $messages->hasMorePages(),
            ]
        ]);
    }

    /**
     * API: Send text message to contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiSendMessage($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        $messageBody = request()->get('message');
        if (empty($messageBody)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Message body is required'),
            ]);
        }

        // Prepare request for engine
        request()->merge([
            'contact_uid' => $contactUid,
            'message_body' => $messageBody,
        ]);

        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage(request());

        if ($processReaction->failed()) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => $processReaction->message(),
            ]);
        }

        $processedData = $processReaction->data();

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Message sent successfully'),
        ], [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
            'messaged_at' => $processedData['log_message']['messaged_at'] ?? null,
        ]);
    }

    /**
     * API: Send media message to contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiSendMediaMessage($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        $mediaType = request()->get('media_type'); // image, video, document, audio
        $mediaUrl = request()->get('media_url');
        $caption = request()->get('caption', '');

        if (empty($mediaType) || empty($mediaUrl)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Media type and URL are required'),
            ]);
        }

        if (!in_array($mediaType, ['image', 'video', 'document', 'audio'])) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Invalid media type. Use: image, video, document, audio'),
            ]);
        }

        // Prepare request for engine
        request()->merge([
            'contact_uid' => $contactUid,
            'phone_number' => $contact->wa_id,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'caption' => $caption,
        ]);

        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage(request(), true);

        if ($processReaction->failed()) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => $processReaction->message(),
            ]);
        }

        $processedData = $processReaction->data();

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Media message sent successfully'),
        ], [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
     * API: Send template message to contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiSendTemplateMessage($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        $templateUid = request()->get('template_uid');
        $templateComponents = request()->get('template_components', []);

        if (empty($templateUid)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Template UID is required'),
            ]);
        }

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

        // Prepare request for engine
        request()->merge([
            'phone_number' => $contact->wa_id,
            'template_name' => $template->template_name,
            'template_language' => $template->language,
            'template_uid' => $templateUid,
        ]);

        // Merge template components if provided
        if (!empty($templateComponents)) {
            request()->merge($templateComponents);
        }

        $processReaction = $this->whatsAppServiceEngine->processSendMessageForContact(request());

        if ($processReaction->failed()) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => $processReaction->message(),
            ]);
        }

        $processedData = $processReaction->data();

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Template message sent successfully'),
        ], [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
     * API: Get unread message count - External API
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiGetUnreadCount($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');
        $userId = getUserID();

        // Get contact IDs for this vendor
        $contactIds = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->pluck('_id');

        // Total unread = incoming messages with status 'received' (not yet read)
        $totalUnreadCount = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::whereIn('contacts__id', $contactIds)
            ->where([
                'is_incoming_message' => 1,
                'status' => 'received',
            ])->count();

        // Unread contacts count (total)
        $totalUnreadContactsCount = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->whereHas('unreadMessages')->count();

        // My assigned unread count
        $myAssignedContactIds = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->where('assigned_users__id', $userId)
            ->pluck('_id');

        $myAssignedUnreadCount = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::whereIn('contacts__id', $myAssignedContactIds)
            ->where([
                'is_incoming_message' => 1,
                'status' => 'received',
            ])->count();

        // Unassigned unread count
        $unassignedContactIds = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->whereNull('assigned_users__id')
            ->pluck('_id');

        $unassignedUnreadCount = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::whereIn('contacts__id', $unassignedContactIds)
            ->where([
                'is_incoming_message' => 1,
                'status' => 'received',
            ])->count();

        // Get unread counts per team member
        $teamMembers = \App\Models\User::where('vendors__id', $vendorId)
            ->where('status', 1)
            ->get();

        $usersUnreadCounts = [];
        foreach ($teamMembers as $member) {
            $memberContactIds = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
                ->where('assigned_users__id', $member->_id)
                ->pluck('_id');

            $memberUnreadCount = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::whereIn('contacts__id', $memberContactIds)
                ->where([
                    'is_incoming_message' => 1,
                    'status' => 'received',
                ])->count();

            if ($memberUnreadCount > 0) {
                $usersUnreadCounts[$member->_uid] = [
                    'user_uid' => $member->_uid,
                    'user_name' => trim($member->first_name . ' ' . $member->last_name),
                    'unread_count' => $memberUnreadCount,
                ];
            }
        }

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Unread count fetched successfully'),
        ], [
            'total_unread_messages' => $totalUnreadCount,
            'total_unread_contacts' => $totalUnreadContactsCount,
            'my_assigned_unread_count' => $myAssignedUnreadCount,
            'unassigned_unread_count' => $unassignedUnreadCount,
            'users_unread_counts' => $usersUnreadCounts,
        ]);
    }

    /**
     * API: Assign team member to contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiAssignUser($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        $userUid = request()->get('user_uid');

        if ($userUid) {
            $user = \App\Models\User::where('_uid', $userUid)->first();
            if (__isEmpty($user)) {
                return processExternalApiResponse([
                    'result' => 'failed',
                    'message' => __tr('User not found'),
                ]);
            }
            $contact->assigned_users__id = $user->_id;
        } else {
            // Unassign
            $contact->assigned_users__id = null;
        }

        $contact->save();

        return processExternalApiResponse([
            'result' => 'success',
            'message' => $userUid ? __tr('User assigned successfully') : __tr('User unassigned successfully'),
        ], [
            'contact_uid' => $contact->_uid,
            'assigned_user_uid' => $userUid,
        ]);
    }

    /**
     * API: Assign labels to contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiAssignLabels($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        $labelUids = request()->get('label_uids', []);

        // Get label IDs
        $labels = \App\Yantrana\Components\Contact\Models\LabelModel::where('vendors__id', $vendorId)
            ->whereIn('_uid', $labelUids)
            ->get();

        // Sync labels
        $contact->labels()->sync($labels->pluck('_id'));

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Labels assigned successfully'),
        ], [
            'contact_uid' => $contact->_uid,
            'assigned_labels' => $labels->map(fn($l) => ['_uid' => $l->_uid, 'title' => $l->title]),
        ]);
    }

    /**
     * API: Update contact notes - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiUpdateNotes($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        $notes = request()->get('notes', '');

        $contactData = $contact->__data ?? [];
        $contactData['notes'] = $notes;
        $contact->__data = $contactData;
        $contact->save();

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Notes updated successfully'),
        ], [
            'contact_uid' => $contact->_uid,
            'notes' => $notes,
        ]);
    }

    /**
     * API: Clear chat history for contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiClearChatHistory($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        $deletedCount = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::where('contacts__id', $contact->_id)->delete();

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Chat history cleared successfully'),
        ], [
            'contact_uid' => $contact->_uid,
            'deleted_messages' => $deletedCount,
        ]);
    }

    /**
     * API: Get message log - External API
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiGetMessageLog($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');
        $type = request()->get('type'); // 1 = incoming, 0 = outgoing, null = all
        $startDate = request()->get('start_date');
        $endDate = request()->get('end_date');
        $page = request()->get('page', 1);
        $limit = request()->get('limit', 50);

        // Get contact IDs for this vendor
        $contactIds = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->pluck('_id');

        $query = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::whereIn('contacts__id', $contactIds);

        if ($type !== null) {
            $query->where('is_incoming_message', $type);
        }

        if ($startDate) {
            $query->whereDate('messaged_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('messaged_at', '<=', $endDate);
        }

        $messages = $query->orderByDesc('messaged_at')
            ->paginate($limit, ['*'], 'page', $page);

        // Load contacts for the messages
        $messageContactIds = $messages->pluck('contacts__id')->unique();
        $contacts = \App\Yantrana\Components\Contact\Models\ContactModel::whereIn('_id', $messageContactIds)
            ->get()
            ->keyBy('_id');

        $messageData = $messages->map(function ($msg) use ($contacts) {
            $contact = $contacts->get($msg->contacts__id);
            return [
                '_uid' => $msg->_uid,
                'wamid' => $msg->wamid,
                'message' => $msg->message,
                'is_incoming_message' => (bool) $msg->is_incoming_message,
                'status' => $msg->status,
                'messaged_at' => $msg->messaged_at,
                'contact' => $contact ? [
                    '_uid' => $contact->_uid,
                    'full_name' => trim($contact->first_name . ' ' . $contact->last_name),
                    'wa_id' => $contact->wa_id,
                ] : null,
            ];
        });

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Message log fetched successfully'),
        ], [
            'messages' => $messageData,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'has_more' => $messages->hasMorePages(),
            ]
        ]);
    }

    /**
     * API: Get single message details - External API
     *
     * @param string $vendorUid
     * @param string $messageUid
     * @return json
     */
    public function apiGetMessage($vendorUid, $messageUid)
    {
        $vendorId = request()->get('_vendor_id');

        // Get contact IDs for this vendor
        $contactIds = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->pluck('_id');

        $message = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::where('_uid', $messageUid)
            ->whereIn('contacts__id', $contactIds)
            ->first();

        if (__isEmpty($message)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Message not found'),
            ]);
        }

        // Load contact
        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where('_id', $message->contacts__id)->first();

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Message fetched successfully'),
        ], [
            '_uid' => $message->_uid,
            'wamid' => $message->wamid,
            'message' => $message->message,
            'is_incoming_message' => (bool) $message->is_incoming_message,
            'status' => $message->status,
            'messaged_at' => $message->messaged_at,
            '__data' => $message->__data,
            'contact' => $contact ? [
                '_uid' => $contact->_uid,
                'full_name' => trim($contact->first_name . ' ' . $contact->last_name),
                'wa_id' => $contact->wa_id,
            ] : null,
        ]);
    }

    /**
     * API: Get team members for assigning - External API
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiGetTeamMembers($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');

        $users = \App\Models\User::where('vendors__id', $vendorId)
            ->where('status', 1)
            ->get();

        $userData = $users->map(function ($user) {
            return [
                '_uid' => $user->_uid,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => trim($user->first_name . ' ' . $user->last_name),
                'email' => $user->email,
            ];
        });

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Team members fetched successfully'),
        ], $userData);
    }

    /**
     * API: Mark messages as read for contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiMarkAsRead($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        $updatedCount = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::where([
            'contacts__id' => $contact->_id,
            'is_incoming_message' => 1,
            'status' => 'received',
        ])->update(['status' => 'read']);

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Messages marked as read'),
        ], [
            'contact_uid' => $contact->_uid,
            'marked_read' => $updatedCount,
        ]);
    }

    /**
     * API: Get full chat context for contact (contact info + labels + team members + initial messages)
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiGetContactChatContext($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->with(['labels', 'assignedUser', 'lastIncomingMessage', 'customFieldValues.customField'])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        // Get all labels for vendor
        $allLabels = \App\Yantrana\Components\Contact\Models\LabelModel::where('vendors__id', $vendorId)
            ->get()
            ->map(fn($l) => [
                '_id' => $l->_id,
                '_uid' => $l->_uid,
                'title' => $l->title,
                'bg_color' => $l->bg_color,
                'text_color' => $l->text_color,
            ]);

        // Get team members
        $teamMembers = \App\Models\User::where('vendors__id', $vendorId)
            ->where('status', 1)
            ->get()
            ->map(fn($u) => [
                '_id' => $u->_id,
                '_uid' => $u->_uid,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'full_name' => trim($u->first_name . ' ' . $u->last_name),
                'email' => $u->email,
            ]);

        // Reply window status
        $lastIncoming = $contact->lastIncomingMessage;
        $isReplyWindowOpen = $lastIncoming && $lastIncoming->messaged_at->diffInHours(now()) < 24;
        $replyWindowExpiresAt = $lastIncoming ? $lastIncoming->messaged_at->addHours(24) : null;

        // Custom fields
        $customFields = $contact->customFieldValues->map(fn($cfv) => [
            'field_name' => $cfv->customField->input_name ?? null,
            'field_label' => $cfv->customField->input_label ?? null,
            'value' => $cfv->field_value,
        ]);

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Chat context fetched successfully'),
        ], [
            'contact' => [
                '_uid' => $contact->_uid,
                '_id' => $contact->_id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'full_name' => $contact->full_name,
                'name_initials' => $contact->name_initials,
                'wa_id' => $contact->wa_id,
                'email' => $contact->email,
                'language_code' => $contact->language_code,
                'is_blocked' => !empty($contact->wa_blocked_at),
                'wa_blocked_at' => $contact->wa_blocked_at,
                'disable_ai_bot' => (bool) $contact->disable_ai_bot,
                'disable_reply_bot' => (bool) $contact->disable_reply_bot,
                'notes' => $contact->__data['contact_notes'] ?? '',
                'custom_fields' => $customFields,
                'labels' => $contact->labels ? $contact->labels->map(fn($l) => [
                    '_id' => $l->_id,
                    '_uid' => $l->_uid,
                    'title' => $l->title,
                    'bg_color' => $l->bg_color,
                    'text_color' => $l->text_color,
                ])->values() : [],
                'assigned_user' => $contact->assignedUser ? [
                    '_id' => $contact->assignedUser->_id,
                    '_uid' => $contact->assignedUser->_uid,
                    'first_name' => $contact->assignedUser->first_name,
                    'last_name' => $contact->assignedUser->last_name,
                    'full_name' => trim($contact->assignedUser->first_name . ' ' . $contact->assignedUser->last_name),
                ] : null,
            ],
            'is_reply_window_open' => $isReplyWindowOpen,
            'reply_window_expires_at' => $replyWindowExpiresAt?->toIso8601String(),
            'reply_window_expires_human' => $replyWindowExpiresAt?->diffForHumans(['parts' => 2, 'join' => true]),
            'all_labels' => $allLabels,
            'team_members' => $teamMembers,
        ]);
    }

    /**
     * API: Block contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiBlockContact($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        // Check if reply window is open
        $lastIncoming = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel::where([
            'contacts__id' => $contact->_id,
            'is_incoming_message' => 1,
        ])->orderByDesc('messaged_at')->first();

        $isReplyWindowOpen = $lastIncoming && $lastIncoming->messaged_at->diffInHours(now()) < 24;

        if (!$isReplyWindowOpen) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Cannot block contact - reply window is closed'),
            ]);
        }

        $contact->wa_blocked_at = now();
        $contact->save();

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Contact blocked successfully'),
        ], [
            'contact_uid' => $contact->_uid,
            'wa_blocked_at' => $contact->wa_blocked_at->toIso8601String(),
        ]);
    }

    /**
     * API: Unblock contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiUnblockContact($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        $contact->wa_blocked_at = null;
        $contact->save();

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Contact unblocked successfully'),
        ], [
            'contact_uid' => $contact->_uid,
        ]);
    }

    /**
     * API: Update contact bot settings - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiUpdateBotSettings($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        $enableAiBot = request()->get('enable_ai_bot');
        $enableReplyBot = request()->get('enable_reply_bot');

        if ($enableAiBot !== null) {
            $contact->disable_ai_bot = $enableAiBot ? 0 : 1;
        }

        if ($enableReplyBot !== null) {
            $contact->disable_reply_bot = $enableReplyBot ? 0 : 1;
        }

        $contact->save();

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Bot settings updated successfully'),
        ], [
            'contact_uid' => $contact->_uid,
            'disable_ai_bot' => (bool) $contact->disable_ai_bot,
            'disable_reply_bot' => (bool) $contact->disable_reply_bot,
        ]);
    }

    /**
     * API: Get quick bot replies for contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiGetQuickBotReplies($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        // Get active bot replies
        $botReplies = \App\Yantrana\Components\BotReply\Models\BotReplyModel::where([
            'vendors__id' => $vendorId,
            'status' => 1,
        ])->get()->map(fn($br) => [
            '_uid' => $br->_uid,
            'name' => $br->name,
            'trigger_type' => $br->trigger_type,
            'reply_trigger' => $br->reply_trigger,
        ]);

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Quick bot replies fetched successfully'),
        ], [
            'bot_replies' => $botReplies,
        ]);
    }

    /**
     * API: Send quick bot reply to contact - External API
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiSendQuickBotReply($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');
        $botReplyUid = request()->get('bot_reply_uid');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        $botReply = \App\Yantrana\Components\BotReply\Models\BotReplyModel::where([
            '_uid' => $botReplyUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($botReply)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Bot reply not found'),
            ]);
        }

        // Use existing engine method to send the bot reply
        $processReaction = $this->whatsAppServiceEngine->processBotReplyForContact($contact, $botReply);

        if ($processReaction && $processReaction->failed()) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => $processReaction->message(),
            ]);
        }

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Bot reply sent successfully'),
        ], [
            'contact_uid' => $contact->_uid,
            'bot_reply_uid' => $botReply->_uid,
        ]);
    }
}
