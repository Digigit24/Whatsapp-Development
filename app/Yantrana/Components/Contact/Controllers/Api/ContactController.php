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
 * ContactController.php - API Controller file
 *
 * This file is part of the Contact component API.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Controllers\Api;

use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseRequest;
use Illuminate\Support\Facades\Gate;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use Illuminate\Database\Query\Builder;
use App\Yantrana\Components\Contact\ContactEngine;
use App\Yantrana\__Laraware\Core\EngineResponse;

class ContactController extends BaseController
{
    /**
     * @var ContactEngine - Contact Engine
     */
    protected $contactEngine;

    /**
     * Constructor
     *
     * @param  ContactEngine  $contactEngine  - Contact Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(ContactEngine $contactEngine)
    {
        $this->contactEngine = $contactEngine;
    }

    /**
     * Contact create process by API
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function apiProcessContactCreate(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            // 'first_name' => 'required',
            // 'last_name' => 'required',
            // 'country' => 'required',
            'language_code' => 'nullable|alpha_dash',
            "phone_number" => [
                'required',
                'numeric',
                'min_digits:9',
                'min:1',
                'doesnt_start_with:+,0',
                Rule::unique('contacts', 'wa_id')->where(fn(Builder $query) => $query->where('vendors__id', getVendorId()))
            ],
            'email' => 'nullable|email',
        ]);
        abortIf(str_starts_with($request->get('phone_number'), '0') or str_starts_with($request->get('phone_number'), '+'), null, 'phone number should be numeric value without prefixing 0 or +');
        // ask engine to process the request
        $inputData = $request->all();
        $inputData['country'] = getCountryIdByName($inputData['country'] ?? null);
        $processReaction = $this->contactEngine->processContactCreate($inputData);
        $contact = $processReaction->data('contact');
        return $this->processApiResponse($processReaction, [
            'contact_uid' => $contact?->_uid,
            'first_name' => $contact?->first_name,
            'last_name' => $contact?->last_name,
            'phone_number' => $contact?->wa_id,
            'language_code' => $contact?->language_code,
            'country' => $contact?->country?->name,
        ]);
    }

    /**
     * API Contact process update
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function apiProcessContactUpdate(BaseRequest $request, $vendorUid, $phoneNumber)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            'email' => 'nullable|email',
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactUpdate($phoneNumber, $request->all());
        if ($processReaction->success()) {
            $contact = $processReaction->data('contact');
            // get back with response
            return $this->processApiResponse($processReaction, [
                'contact_uid' => $contact?->_uid,
            ]);
        }
        return $this->processApiResponse($processReaction, [
            'contact_uid' => $processReaction->data('contactIdOrUid'),
        ]);
    }

    /**
     * API Assign Team Member to Contact
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function apiAssignTeamMemberToContact(BaseRequest $request, $vendorUid)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            'username_or_email' => 'required',
            "phone_number" => [
                'required',
                'numeric',
                'min_digits:9',
                'min:1',
                'doesnt_start_with:+,0'
            ]
        ]);
        
        // ask engine to process the request
        $processReaction = $this->contactEngine->processAssignChatUser($request);
        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction, $processReaction->data());
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * Contact get update data
     *
     * @param  mix  $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function updateContactData($contactIdOrUid)
    {
        validateVendorAccess([
            'messaging', 
            'assigned_chats_only'
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->prepareContactUpdateData($contactIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Get all the labels api request
     *
     * @param [type] $contactUid
     * @return void
     */
    public function getLabelsForApi($contactUid)
    {
        validateVendorAccess('messaging');
        $processReaction = $this->contactEngine->getLabelsDataForApi($contactUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Contact notes process update
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function updateNotes(BaseRequest $request)
    {
        validateVendorAccess('messaging');
        // process the validation based on the provided rules
        $request->validate([
            'contactIdOrUid' => 'required|uuid',
            // 'contact_notes' => 'nullable',
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processUpdateNotes($request);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Contact process update
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function assignChatUser(BaseRequest $request)
    {
        validateVendorAccess('messaging');
        
        $isBulkAction = data_get($request, 'bulk_action');
        
        if ($isBulkAction == true) {
            // process the validation based on the provided rules
            $request->validate([
                'contactIdOrUid' => 'string',
            ]);
            // ask engine to process the request
            $processReaction = $this->contactEngine->processAssignTeamMemberInBulk($request->all());
        } else {
            // process the validation based on the provided rules
            $request->validate([
                'contactIdOrUid' => 'required|uuid',
            ]);
            // ask engine to process the request
            $processReaction = $this->contactEngine->processAssignChatUser($request);
        }
        
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Assign labels to contact
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function assignContactLabels(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'contactUid' => [
                'required',
                'uuid',
            ],
            'contact_labels' => [
                'nullable',
                'array',
                // 'max:10',
            ],
        ]);
        $processReaction = $this->contactEngine->assignContactLabelsProcess($request);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Create new label for vendor
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function createLabel(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'title' => [
                'required',
                'max:45',
                Rule::unique('labels')->where(fn(Builder $query) => $query->where('vendors__id', getVendorId()))
            ],
            'text_color' => [
                'nullable',
                'string',
                'max:10',
            ],
            'bg_color' => [
                'nullable',
                'string',
                'max:10',
            ],
        ]);
        $processReaction = $this->contactEngine->createLabelProcess($request);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Update label for vendor
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function updateLabel(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'labelUid' => [
                'required',
                'uuid'
            ],
            'title' => [
                'required',
                'max:45',
                Rule::unique('labels')->where(fn(Builder $query) => $query->where('vendors__id', getVendorId()))->ignore($request->labelUid, '_uid')
            ],
            'text_color' => [
                'nullable',
                'string',
                'max:10',
            ],
            'bg_color' => [
                'nullable',
                'string',
                'max:10',
            ],
        ]);
        $processReaction = $this->contactEngine->processUpdateLabel($request);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Delete label
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function deleteLabelProcess(BaseRequestTwo $request, $labelUid)
    {
        validateVendorAccess('messaging');
        $request->merge([
            'labelUid' => $request->labelUid
        ]);
        $request->validate([
            'labelUid' => [
                'required',
                'uuid',
            ],
        ]);
        $processReaction = $this->contactEngine->processDeleteLabel($labelUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * API Get contact details
     *
     * @param string $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function apiGetContactDetails($contactIdOrUid)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->contactEngine->prepareContactUpdateData($contactIdOrUid);
        if ($processReaction->success()) {
            $contact = $processReaction->data('contact');
            return $this->processApiResponse($processReaction, [
                'contact_uid' => $contact?->_uid,
                'first_name' => $contact?->first_name,
                'last_name' => $contact?->last_name,
                'phone_number' => $contact?->wa_id,
                'email' => $contact?->email,
                'language_code' => $contact?->language_code,
                'country' => $contact?->country?->name,
                'notes' => $contact?->contact_notes,
                'labels' => $contact?->labels ?? [],
            ]);
        }
        return $this->processApiResponse($processReaction, []);
    }

    /**
     * API List contacts
     *
     * @param BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function apiListContacts(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // Normalize and validate incoming params to prevent missing keys (e.g., 'columns')
        try {
            // Basic sanitization & sensible defaults for DataTables-like structure
            $input = $request->all();

            $input['start'] = isset($input['start']) ? max(0, (int) $input['start']) : 0;
            $input['length'] = isset($input['length']) ? (int) $input['length'] : 10;
            if ($input['length'] < 1) { $input['length'] = 10; }
            if ($input['length'] > 100) { $input['length'] = 100; }

            // Ensure search structure
            $searchValue = '';
            if (isset($input['search'])) {
                // Accept both string or array with value
                $searchValue = is_array($input['search']) ? (string) ($input['search']['value'] ?? '') : (string) $input['search'];
            }
            $input['search'] = [
                'value' => $searchValue,
                'regex' => (bool) (is_array($request->get('search')) ? ($request->get('search')['regex'] ?? false) : false),
            ];

            // Ensure order structure
            $input['order'] = $input['order'] ?? [
                [ 'column' => 0, 'dir' => 'desc' ]
            ];

            // Ensure columns structure (minimum one default column)
            $input['columns'] = $input['columns'] ?? [
                [
                    'data' => 'created_at',
                    'name' => 'created_at',
                    'searchable' => true,
                    'orderable' => true,
                ],
            ];

            // ask engine to process the request
            $contactsData = $this->contactEngine->prepareContactDataTableSource($input);

            return response()->json([
                'result' => 'success',
                'message' => __tr('Contacts fetched successfully'),
                'data' => $contactsData,
            ]);
        } catch (\Throwable $e) {
            // Proper error response without exposing internals
            return response()->json([
                'result' => 'error',
                'message' => __tr('Failed to fetch contacts'),
                'errors' => [ 'exception' => config('app.debug') ? $e->getMessage() : __tr('Server Error') ]
            ], 500);
        }
    }

    /**
     * API Delete contact
     *
     * @param string $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function apiDeleteContact($contactIdOrUid)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        /** @var EngineResponse $processReaction */
        $processReaction = $this->contactEngine->processContactDelete($contactIdOrUid);
        return $this->processApiResponse($processReaction, [
            'contact_uid' => $contactIdOrUid,
            'deleted' => $processReaction->success()
        ]);
    }

    /**
     * API Block contact
     *
     * @param BaseRequestTwo $request
     * @param string $contactIdOrUid
     * @return json
     */
    public function apiBlockContact(BaseRequestTwo $request, $contactIdOrUid)
    {
        validateVendorAccess('messaging');
        $processReaction = $this->contactEngine->processBlockContact($contactIdOrUid);
        return $this->processApiResponse($processReaction, [
            'contact_uid' => $contactIdOrUid,
            'blocked' => $processReaction->success()
        ]);
    }

    /**
     * API Unblock contact
     *
     * @param BaseRequestTwo $request
     * @param string $contactIdOrUid
     * @return json
     */
    public function apiUnblockContact(BaseRequestTwo $request, $contactIdOrUid)
    {
        validateVendorAccess('messaging');
        $processReaction = $this->contactEngine->processUnblockContact($contactIdOrUid);
        return $this->processApiResponse($processReaction, [
            'contact_uid' => $contactIdOrUid,
            'unblocked' => $processReaction->success()
        ]);
    }

    /**
     * API Bulk delete contacts
     *
     * @param BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function apiBulkDeleteContacts(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        $request->validate([
            'selected_contacts' => 'required|array'
        ]);
        // ask engine to process the request
        /** @var EngineResponse $processReaction */
        $processReaction = $this->contactEngine->processSelectedContactsDelete($request);
        return $this->processApiResponse($processReaction, [
            'deleted_count' => count($request->get('selected_contacts', [])),
            'success' => $processReaction->success()
        ]);
    }

    /**
     * API Toggle AI Bot for Contact
     *
     * @param BaseRequest $request
     * @param string $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function apiToggleAiBot(BaseRequest $request, $contactIdOrUid)
    {
        validateVendorAccess('messaging');
        // ask engine to process the request
        /** @var EngineResponse $processReaction */
        $processReaction = $this->contactEngine->processToggleAiBot($contactIdOrUid);
        // Get fresh contact data after toggle
        $contactData = $this->contactEngine->prepareContactUpdateData($contactIdOrUid);
        $contact = $contactData->data();
        return $this->processApiResponse($processReaction, [
            'contact_uid' => $contactIdOrUid,
            'ai_bot_enabled' => !($contact['disable_ai_bot'] ?? true)
        ]);
    }
}
