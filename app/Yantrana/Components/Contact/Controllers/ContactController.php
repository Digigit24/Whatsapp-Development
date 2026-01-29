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
 * ContactController.php - Controller file
 *
 * This file is part of the Contact component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Controllers;

use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseRequest;
use Illuminate\Support\Facades\Gate;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use Illuminate\Database\Query\Builder;
use App\Yantrana\Components\Contact\ContactEngine;

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
     * list of Contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showContactView($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        $contactsRequiredEngineResponse = $this->contactEngine->prepareContactRequiredData($groupUid);

        // load the view
        return $this->loadView('contact.list', $contactsRequiredEngineResponse->data());
    }

    /**
     * list of Contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareContactList($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        // respond with dataTables preparations
        return $this->contactEngine->prepareContactDataTableSource($groupUid);
    }

    /**
     * Contact process delete
     *
     * @param  mix  $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactDelete($contactIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactDelete($contactIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Contact process remove from group
     *
     * @param  mix  $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactRemoveFromGroup($contactIdOrUid, $groupUid, BaseRequest $request)
    {

        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactRemove($contactIdOrUid, $groupUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Selected Contacts delete process
     *
     * @param  BaseRequest  $request
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function selectedContactsDelete(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');

        // restrict demo user
        if (isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $request->validate([
            'selected_contacts' => 'required|array'
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processSelectedContactsDelete($request);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Process Delete All Contacts
     *
     * @param  BaseRequest  $request
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function deleteAllContact(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');

        // restrict demo user
        if (isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        // ask engine to process the request
        $processReaction = $this->contactEngine->processDeleteAllContact($request);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Selected Contacts delete process
     *
     * @param  BaseRequest  $request
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function assignGroupsToSelectedContacts(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        $request->validate([
            'selected_contacts' => 'required|array',
            'selected_groups' => 'required|array'
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processAssignGroupsToSelectedContacts($request);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Contact create process
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactCreate(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            'language_code' => 'nullable|alpha_dash',
            "phone_number" => [
                'required',
                'numeric',
                'min_digits:9',
                'max_digits:20',
                'min:1',
                'doesnt_start_with:+,0',
                Rule::unique('contacts', 'wa_id')->where(fn(Builder $query) => $query->where('vendors__id', getVendorId()))
            ],
            'email' => 'nullable|email',
        ]);

        if (str_starts_with($request->get('phone_number'), '0') or str_starts_with($request->get('phone_number'), '+')) {
            return $this->processResponse(2, __tr('Mobile number should be numeric value without prefixing 0 or +'));
        }

        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactCreate($request->all());

        // get back with response
        return $this->processResponse($processReaction);
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
     * Contact process update
     *
     * @param  mix @param  mix $contactIdOrUid
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactUpdate(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            'contactIdOrUid' => 'required',
            'email' => 'nullable|email',
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactUpdate($request->get('contactIdOrUid'), $request->all());

        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Toggle AI Bot for COntact
     *
     * @param  int|string $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function toggleAiBot(BaseRequest $request, $contactIdOrUid)
    {
        validateVendorAccess('messaging');
        // ask engine to process the request
        $processReaction = $this->contactEngine->processToggleAiBot($contactIdOrUid);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Export Contacts
     *
     * @param string $exportType
     * @return file
     */
    public function exportContacts($exportType = null, $fileType = null)
    {

        validateVendorAccess('manage_contacts');
        if ($fileType == 'csv') {
            return $this->contactEngine->processExportCSVContacts($exportType);
        }
        return $this->contactEngine->processExportContacts($exportType);
    }

    /**
     * Import Contacts
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function importContacts(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_contacts');
        // restrict demo user
        if (isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $request->validate([
            'document_name' => 'required'
        ]);
        return $this->processResponse(
            $this->contactEngine->processImportContacts($request),
            [],
            [],
            true
        );
    }

    /**
     * Import Contacts
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function abortImportContacts(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_contacts');
        // restrict demo user
        if (isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        return $this->processResponse(
            $this->contactEngine->processAbortImportContacts($request),
            [],
            [],
            true
        );
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
     * Get all the labels
     *
     * @param [type] $contactUid
     * @return void
     */
    public function getLabels($contactUid)
    {
        validateVendorAccess('messaging');
        $processReaction = $this->contactEngine->getLabelsData($contactUid);
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
     * Block contact
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function processContactBlock(BaseRequestTwo $request, $contactIdOrUid)
    {
        validateVendorAccess('messaging');
        $request->merge([
            'contactIdOrUid' => $request->contactIdOrUid
        ]);
        $request->validate([
            'contactIdOrUid' => [
                'required',
                'uuid',
            ],
        ]);

        $processReaction = $this->contactEngine->processBlockContact($contactIdOrUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Unblock contact
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function processContactUnblock(BaseRequestTwo $request, $contactIdOrUid)
    {
        validateVendorAccess('messaging');
        $request->merge([
            'contactIdOrUid' => $request->contactIdOrUid
        ]);
        $request->validate([
            'contactIdOrUid' => [
                'required',
                'uuid',
            ],
        ]);

        $processReaction = $this->contactEngine->processUnblockContact($contactIdOrUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
    * Prepare team member list data
    *
    * @return  json object
    *---------------------------------------------------------------- */

    public function prepareTeamMemberList($contactIdOrUid)
    {
        validateVendorAccess('administrative');
        // ask engine to process the request
        $processReaction = $this->contactEngine->prepareTeamMemberListData($contactIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * API: Get all contacts for vendor (External API)
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiGetContacts($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');
        $limit = request()->get('limit', 100);
        $page = request()->get('page', 1);
        $search = request()->get('search');

        $query = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('wa_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest();

        $total = $query->count();
        $contacts = $query->skip(($page - 1) * $limit)->take($limit)->get();

        $contactData = $contacts->map(function ($contact) {
            return [
                '_uid' => $contact->_uid,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'phone_number' => $contact->wa_id,
                'email' => $contact->email,
                'language_code' => $contact->language_code,
                'created_at' => $contact->created_at,
            ];
        });

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Contacts fetched successfully'),
        ], [
            'contacts' => $contactData,
            'total' => $total,
            'page' => (int) $page,
            'limit' => (int) $limit,
            'total_pages' => ceil($total / $limit),
        ]);
    }

    /**
     * API: Get single contact (External API)
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiGetContact($vendorUid, $contactUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ])->with(['country', 'groups', 'labels'])->first();

        if (__isEmpty($contact)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact not found'),
            ]);
        }

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Contact fetched successfully'),
        ], [
            '_uid' => $contact->_uid,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'phone_number' => $contact->wa_id,
            'email' => $contact->email,
            'language_code' => $contact->language_code,
            'country' => $contact->country?->name,
            'created_at' => $contact->created_at,
            'groups' => $contact->groups->pluck('title', '_uid'),
            'labels' => $contact->labels->pluck('title', '_uid'),
            '__data' => $contact->__data,
        ]);
    }

    /**
     * API: Delete contact (External API)
     *
     * @param string $vendorUid
     * @param string $contactUid
     * @return json
     */
    public function apiDeleteContact($vendorUid, $contactUid)
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

        $processResponse = $this->contactEngine->processContactDelete($contactUid);

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
     * API: Get all labels (External API)
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiGetLabels($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');

        $labels = \App\Yantrana\Components\Contact\Models\LabelModel::where('vendors__id', $vendorId)->get();

        $labelData = $labels->map(function ($label) {
            return [
                '_uid' => $label->_uid,
                'title' => $label->title,
                'text_color' => $label->text_color,
                'bg_color' => $label->bg_color,
            ];
        });

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Labels fetched successfully'),
        ], $labelData);
    }

    /**
     * API: Get all contact groups (External API)
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiGetContactGroups($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');

        $groups = \App\Yantrana\Components\Contact\Models\ContactGroupModel::where('vendors__id', $vendorId)
            ->withCount('contacts')
            ->get();

        $groupData = $groups->map(function ($group) {
            return [
                '_uid' => $group->_uid,
                'title' => $group->title,
                'description' => $group->description,
                'contacts_count' => $group->contacts_count,
            ];
        });

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Contact groups fetched successfully'),
        ], $groupData);
    }

    /**
     * API: Create label (External API)
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiCreateLabel($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');

        $title = request()->get('title');
        $textColor = request()->get('text_color', '#ffffff');
        $bgColor = request()->get('bg_color', '#6c757d');

        if (empty($title)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Title is required'),
            ]);
        }

        $label = \App\Yantrana\Components\Contact\Models\LabelModel::create([
            'vendors__id' => $vendorId,
            'title' => $title,
            'text_color' => $textColor,
            'bg_color' => $bgColor,
            'status' => 1,
        ]);

        if ($label) {
            return processExternalApiResponse([
                'result' => 'success',
                'message' => __tr('Label created successfully'),
            ], [
                '_uid' => $label->_uid,
                'title' => $label->title,
                'text_color' => $label->text_color,
                'bg_color' => $label->bg_color,
            ]);
        }

        return processExternalApiResponse([
            'result' => 'failed',
            'message' => __tr('Failed to create label'),
        ]);
    }

    /**
     * API: Update label (External API)
     *
     * @param string $vendorUid
     * @param string $labelUid
     * @return json
     */
    public function apiUpdateLabel($vendorUid, $labelUid)
    {
        $vendorId = request()->get('_vendor_id');

        $label = \App\Yantrana\Components\Contact\Models\LabelModel::where([
            '_uid' => $labelUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($label)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Label not found'),
            ]);
        }

        $updateData = [];
        if (request()->has('title')) {
            $updateData['title'] = request()->get('title');
        }
        if (request()->has('text_color')) {
            $updateData['text_color'] = request()->get('text_color');
        }
        if (request()->has('bg_color')) {
            $updateData['bg_color'] = request()->get('bg_color');
        }

        if (empty($updateData)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Nothing to update'),
            ]);
        }

        $label->update($updateData);

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Label updated successfully'),
        ], [
            '_uid' => $label->_uid,
            'title' => $label->title,
            'text_color' => $label->text_color,
            'bg_color' => $label->bg_color,
        ]);
    }

    /**
     * API: Delete label (External API)
     *
     * @param string $vendorUid
     * @param string $labelUid
     * @return json
     */
    public function apiDeleteLabel($vendorUid, $labelUid)
    {
        $vendorId = request()->get('_vendor_id');

        $label = \App\Yantrana\Components\Contact\Models\LabelModel::where([
            '_uid' => $labelUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($label)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Label not found'),
            ]);
        }

        if ($label->delete()) {
            return processExternalApiResponse([
                'result' => 'success',
                'message' => __tr('Label deleted successfully'),
            ]);
        }

        return processExternalApiResponse([
            'result' => 'failed',
            'message' => __tr('Failed to delete label'),
        ]);
    }

    /**
     * API: Create contact group (External API)
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiCreateContactGroup($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');

        $title = request()->get('title');
        $description = request()->get('description', '');

        if (empty($title)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Title is required'),
            ]);
        }

        $group = \App\Yantrana\Components\Contact\Models\ContactGroupModel::create([
            'vendors__id' => $vendorId,
            'title' => $title,
            'description' => $description,
            'status' => 1,
        ]);

        if ($group) {
            return processExternalApiResponse([
                'result' => 'success',
                'message' => __tr('Contact group created successfully'),
            ], [
                '_uid' => $group->_uid,
                'title' => $group->title,
                'description' => $group->description,
            ]);
        }

        return processExternalApiResponse([
            'result' => 'failed',
            'message' => __tr('Failed to create contact group'),
        ]);
    }

    /**
     * API: Update contact group (External API)
     *
     * @param string $vendorUid
     * @param string $groupUid
     * @return json
     */
    public function apiUpdateContactGroup($vendorUid, $groupUid)
    {
        $vendorId = request()->get('_vendor_id');

        $group = \App\Yantrana\Components\Contact\Models\ContactGroupModel::where([
            '_uid' => $groupUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($group)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact group not found'),
            ]);
        }

        $updateData = [];
        if (request()->has('title')) {
            $updateData['title'] = request()->get('title');
        }
        if (request()->has('description')) {
            $updateData['description'] = request()->get('description');
        }

        if (empty($updateData)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Nothing to update'),
            ]);
        }

        $group->update($updateData);

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Contact group updated successfully'),
        ], [
            '_uid' => $group->_uid,
            'title' => $group->title,
            'description' => $group->description,
        ]);
    }

    /**
     * API: Delete contact group (External API)
     *
     * @param string $vendorUid
     * @param string $groupUid
     * @return json
     */
    public function apiDeleteContactGroup($vendorUid, $groupUid)
    {
        $vendorId = request()->get('_vendor_id');

        $group = \App\Yantrana\Components\Contact\Models\ContactGroupModel::where([
            '_uid' => $groupUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($group)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact group not found'),
            ]);
        }

        if ($group->delete()) {
            return processExternalApiResponse([
                'result' => 'success',
                'message' => __tr('Contact group deleted successfully'),
            ]);
        }

        return processExternalApiResponse([
            'result' => 'failed',
            'message' => __tr('Failed to delete contact group'),
        ]);
    }

    /**
     * API: Add contacts to group (External API)
     *
     * @param string $vendorUid
     * @param string $groupUid
     * @return json
     */
    public function apiAddContactsToGroup($vendorUid, $groupUid)
    {
        $vendorId = request()->get('_vendor_id');

        $group = \App\Yantrana\Components\Contact\Models\ContactGroupModel::where([
            '_uid' => $groupUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($group)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact group not found'),
            ]);
        }

        $contactUids = request()->get('contact_uids', []);
        if (empty($contactUids)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact UIDs are required'),
            ]);
        }

        $contacts = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->whereIn('_uid', $contactUids)
            ->get();

        $addedCount = 0;
        foreach ($contacts as $contact) {
            $exists = \App\Yantrana\Components\Contact\Models\GroupContactModel::where([
                'contact_groups__id' => $group->_id,
                'contacts__id' => $contact->_id,
            ])->exists();

            if (!$exists) {
                \App\Yantrana\Components\Contact\Models\GroupContactModel::create([
                    'contact_groups__id' => $group->_id,
                    'contacts__id' => $contact->_id,
                ]);
                $addedCount++;
            }
        }

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('__count__ contacts added to group', ['__count__' => $addedCount]),
        ], [
            'added_count' => $addedCount,
        ]);
    }

    /**
     * API: Remove contacts from group (External API)
     *
     * @param string $vendorUid
     * @param string $groupUid
     * @return json
     */
    public function apiRemoveContactsFromGroup($vendorUid, $groupUid)
    {
        $vendorId = request()->get('_vendor_id');

        $group = \App\Yantrana\Components\Contact\Models\ContactGroupModel::where([
            '_uid' => $groupUid,
            'vendors__id' => $vendorId,
        ])->first();

        if (__isEmpty($group)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact group not found'),
            ]);
        }

        $contactUids = request()->get('contact_uids', []);
        if (empty($contactUids)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contact UIDs are required'),
            ]);
        }

        $contacts = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->whereIn('_uid', $contactUids)
            ->get();

        $removedCount = \App\Yantrana\Components\Contact\Models\GroupContactModel::where('contact_groups__id', $group->_id)
            ->whereIn('contacts__id', $contacts->pluck('_id'))
            ->delete();

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('__count__ contacts removed from group', ['__count__' => $removedCount]),
        ], [
            'removed_count' => $removedCount,
        ]);
    }

    /**
     * API: Import contacts (External API)
     *
     * @param string $vendorUid
     * @return json
     */
    public function apiImportContacts($vendorUid)
    {
        $vendorId = request()->get('_vendor_id');

        $contacts = request()->get('contacts', []);
        if (empty($contacts)) {
            return processExternalApiResponse([
                'result' => 'failed',
                'message' => __tr('Contacts array is required'),
            ]);
        }

        $groupUids = request()->get('group_uids', []);
        $groupIds = [];
        if (!empty($groupUids)) {
            $groups = \App\Yantrana\Components\Contact\Models\ContactGroupModel::where('vendors__id', $vendorId)
                ->whereIn('_uid', $groupUids)
                ->get();
            $groupIds = $groups->pluck('_id')->toArray();
        }

        $createdCount = 0;
        $updatedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($contacts as $index => $contactData) {
            $phoneNumber = $contactData['phone_number'] ?? null;

            if (empty($phoneNumber)) {
                $failedCount++;
                $errors[] = "Row $index: Phone number is required";
                continue;
            }

            // Clean phone number
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

            // Check if contact exists
            $existingContact = \App\Yantrana\Components\Contact\Models\ContactModel::where([
                'vendors__id' => $vendorId,
                'wa_id' => $phoneNumber,
            ])->first();

            if ($existingContact) {
                // Update existing contact
                $updateData = [];
                if (!empty($contactData['first_name'])) {
                    $updateData['first_name'] = $contactData['first_name'];
                }
                if (!empty($contactData['last_name'])) {
                    $updateData['last_name'] = $contactData['last_name'];
                }
                if (!empty($contactData['email'])) {
                    $updateData['email'] = $contactData['email'];
                }
                if (!empty($contactData['language_code'])) {
                    $updateData['language_code'] = $contactData['language_code'];
                }

                if (!empty($updateData)) {
                    $existingContact->update($updateData);
                }

                // Add to groups if not already
                if (!empty($groupIds)) {
                    foreach ($groupIds as $groupId) {
                        $exists = \App\Yantrana\Components\Contact\Models\GroupContactModel::where([
                            'contact_groups__id' => $groupId,
                            'contacts__id' => $existingContact->_id,
                        ])->exists();

                        if (!$exists) {
                            \App\Yantrana\Components\Contact\Models\GroupContactModel::create([
                                'contact_groups__id' => $groupId,
                                'contacts__id' => $existingContact->_id,
                            ]);
                        }
                    }
                }

                $updatedCount++;
            } else {
                // Create new contact
                $newContact = \App\Yantrana\Components\Contact\Models\ContactModel::create([
                    'vendors__id' => $vendorId,
                    'wa_id' => $phoneNumber,
                    'first_name' => $contactData['first_name'] ?? '',
                    'last_name' => $contactData['last_name'] ?? '',
                    'email' => $contactData['email'] ?? null,
                    'language_code' => $contactData['language_code'] ?? null,
                    'countries__id' => $contactData['country_id'] ?? null,
                ]);

                if ($newContact) {
                    // Add to groups
                    if (!empty($groupIds)) {
                        foreach ($groupIds as $groupId) {
                            \App\Yantrana\Components\Contact\Models\GroupContactModel::create([
                                'contact_groups__id' => $groupId,
                                'contacts__id' => $newContact->_id,
                            ]);
                        }
                    }
                    $createdCount++;
                } else {
                    $failedCount++;
                    $errors[] = "Row $index: Failed to create contact";
                }
            }
        }

        return processExternalApiResponse([
            'result' => 'success',
            'message' => __tr('Import completed: __created__ created, __updated__ updated, __failed__ failed', [
                '__created__' => $createdCount,
                '__updated__' => $updatedCount,
                '__failed__' => $failedCount,
            ]),
        ], [
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ]);
    }
}
