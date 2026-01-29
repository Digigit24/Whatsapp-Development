<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Yantrana\Components\Auth\Controllers\ApiUserController;
use App\Yantrana\Components\Contact\Controllers\ContactController;
use App\Yantrana\Components\Campaign\Controllers\CampaignController;
use App\Yantrana\Components\WhatsAppService\Controllers\WhatsAppServiceController;
use App\Yantrana\Components\WhatsAppService\Controllers\WhatsAppTemplateController;
use App\Yantrana\Components\Media\Controllers\MediaController;
use App\Yantrana\Components\User\Controllers\UserController;

use App\Yantrana\Components\{
    Auth\Controllers\AuthController
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// external apis
// base url
Route::get('/', function () {
    return 'api endpoint';
})->name('api.base_url');

Route::group([
    'middleware' => 'api.vendor.authenticate',
    'prefix' => '{vendorUid}/',
], function () {
    Route::post('/contact/send-message', [
        WhatsAppServiceController::class,
        'apiSendChatMessage',
    ])->name('api.vendor.chat_message.send.process');
    // send media message
    Route::post('/contact/send-media-message', [
        WhatsAppServiceController::class,
        'apiSendMediaChatMessage',
    ])->name('api.vendor.chat_message_media.send.process');
    // send media message
    Route::post('/contact/send-template-message', [
        WhatsAppServiceController::class,
        'apiSendTemplateChatMessage',
    ])->name('api.vendor.chat_template_message.send.process');
    // send interactive message
    Route::post('/contact/send-interactive-message', [
        WhatsAppServiceController::class,
        'apiSendInteractiveChatMessage',
    ])->name('api.vendor.chat_message_interactive.send.process');
    // create new contact
    Route::post('/contact/create', [
        ContactController::class,
        'apiProcessContactCreate',
    ])->name('api.vendor.contact.create.process');
    // update contact
    Route::post('/contact/update/{phoneNumber}', [
        ContactController::class,
        'apiProcessContactUpdate',
    ])->name('api.vendor.contact.update.process');
    // assign team member
    Route::post('/contact/assign-team-member', [
        ContactController::class,
        'apiAssignTeamMemberToContact',
    ])->name('api.vendor.contact.assign_member.update.process');

    // Template APIs
    // Get all templates for vendor
    Route::get('/templates', [
        WhatsAppTemplateController::class,
        'apiGetTemplates',
    ])->name('api.vendor.templates.read');
    // Get single template by UID
    Route::get('/templates/{templateUid}', [
        WhatsAppTemplateController::class,
        'apiGetTemplate',
    ])->name('api.vendor.template.read');
    // Create new template
    Route::post('/templates', [
        WhatsAppTemplateController::class,
        'apiCreateTemplate',
    ])->name('api.vendor.template.create');
    // Update template
    Route::put('/templates/{templateUid}', [
        WhatsAppTemplateController::class,
        'apiUpdateTemplate',
    ])->name('api.vendor.template.update');
    // Delete template
    Route::delete('/templates/{templateUid}', [
        WhatsAppTemplateController::class,
        'apiDeleteTemplate',
    ])->name('api.vendor.template.delete');
    // Sync templates from Meta
    Route::post('/templates/sync', [
        WhatsAppTemplateController::class,
        'apiSyncTemplates',
    ])->name('api.vendor.templates.sync');

    // Campaign APIs
    // Get all campaigns
    Route::get('/campaigns', [
        CampaignController::class,
        'apiGetCampaigns',
    ])->name('api.vendor.campaigns.read');
    // Create/Schedule campaign
    Route::post('/campaigns', [
        CampaignController::class,
        'apiCreateCampaign',
    ])->name('api.vendor.campaign.create');
    // Get single campaign
    Route::get('/campaigns/{campaignUid}', [
        CampaignController::class,
        'apiGetCampaign',
    ])->name('api.vendor.campaign.read');
    // Get campaign status/stats
    Route::get('/campaigns/{campaignUid}/status', [
        CampaignController::class,
        'apiGetCampaignStatus',
    ])->name('api.vendor.campaign.status');
    // Delete campaign
    Route::delete('/campaigns/{campaignUid}', [
        CampaignController::class,
        'apiDeleteCampaign',
    ])->name('api.vendor.campaign.delete');
    // Archive campaign
    Route::post('/campaigns/{campaignUid}/archive', [
        CampaignController::class,
        'apiArchiveCampaign',
    ])->name('api.vendor.campaign.archive');
    // Unarchive campaign
    Route::post('/campaigns/{campaignUid}/unarchive', [
        CampaignController::class,
        'apiUnarchiveCampaign',
    ])->name('api.vendor.campaign.unarchive');

    // Contact APIs
    // Get all contacts
    Route::get('/contacts', [
        ContactController::class,
        'apiGetContacts',
    ])->name('api.vendor.contacts.read');
    // Import contacts
    Route::post('/contacts/import', [
        ContactController::class,
        'apiImportContacts',
    ])->name('api.vendor.contacts.import');
    // Get single contact
    Route::get('/contacts/{contactUid}', [
        ContactController::class,
        'apiGetContact',
    ])->name('api.vendor.contact.read');
    // Delete contact
    Route::delete('/contacts/{contactUid}', [
        ContactController::class,
        'apiDeleteContact',
    ])->name('api.vendor.contact.delete');

    // Label APIs
    // Get all labels
    Route::get('/labels', [
        ContactController::class,
        'apiGetLabels',
    ])->name('api.vendor.labels.read');
    // Create label
    Route::post('/labels', [
        ContactController::class,
        'apiCreateLabel',
    ])->name('api.vendor.label.create');
    // Update label
    Route::put('/labels/{labelUid}', [
        ContactController::class,
        'apiUpdateLabel',
    ])->name('api.vendor.label.update');
    // Delete label
    Route::delete('/labels/{labelUid}', [
        ContactController::class,
        'apiDeleteLabel',
    ])->name('api.vendor.label.delete');

    // Contact Group APIs
    // Get all contact groups
    Route::get('/contact-groups', [
        ContactController::class,
        'apiGetContactGroups',
    ])->name('api.vendor.contact_groups.read');
    // Create contact group
    Route::post('/contact-groups', [
        ContactController::class,
        'apiCreateContactGroup',
    ])->name('api.vendor.contact_group.create');
    // Update contact group
    Route::put('/contact-groups/{groupUid}', [
        ContactController::class,
        'apiUpdateContactGroup',
    ])->name('api.vendor.contact_group.update');
    // Delete contact group
    Route::delete('/contact-groups/{groupUid}', [
        ContactController::class,
        'apiDeleteContactGroup',
    ])->name('api.vendor.contact_group.delete');
    // Add contacts to group
    Route::post('/contact-groups/{groupUid}/contacts', [
        ContactController::class,
        'apiAddContactsToGroup',
    ])->name('api.vendor.contact_group.add_contacts');
    // Remove contacts from group
    Route::delete('/contact-groups/{groupUid}/contacts', [
        ContactController::class,
        'apiRemoveContactsFromGroup',
    ])->name('api.vendor.contact_group.remove_contacts');

    // Chat APIs
    // Get chat contacts (inbox view)
    Route::get('/chat/contacts', [
        WhatsAppServiceController::class,
        'apiGetChatContacts',
    ])->name('api.vendor.chat.contacts');
    // Get unread message count
    Route::get('/chat/unread-count', [
        WhatsAppServiceController::class,
        'apiGetUnreadCount',
    ])->name('api.vendor.chat.unread_count');
    // Get team members for assignment
    Route::get('/chat/team-members', [
        WhatsAppServiceController::class,
        'apiGetTeamMembers',
    ])->name('api.vendor.chat.team_members');
    // Get messages for contact
    Route::get('/contacts/{contactUid}/messages', [
        WhatsAppServiceController::class,
        'apiGetContactMessages',
    ])->name('api.vendor.contact.messages.read');
    // Send text message to contact
    Route::post('/contacts/{contactUid}/messages', [
        WhatsAppServiceController::class,
        'apiSendMessage',
    ])->name('api.vendor.contact.messages.send');
    // Send media message to contact
    Route::post('/contacts/{contactUid}/messages/media', [
        WhatsAppServiceController::class,
        'apiSendMediaMessage',
    ])->name('api.vendor.contact.messages.send_media');
    // Send template message to contact
    Route::post('/contacts/{contactUid}/messages/template', [
        WhatsAppServiceController::class,
        'apiSendTemplateMessage',
    ])->name('api.vendor.contact.messages.send_template');
    // Mark messages as read
    Route::post('/contacts/{contactUid}/messages/read', [
        WhatsAppServiceController::class,
        'apiMarkAsRead',
    ])->name('api.vendor.contact.messages.mark_read');
    // Clear chat history
    Route::delete('/contacts/{contactUid}/messages', [
        WhatsAppServiceController::class,
        'apiClearChatHistory',
    ])->name('api.vendor.contact.messages.clear');
    // Assign user to contact
    Route::post('/contacts/{contactUid}/assign-user', [
        WhatsAppServiceController::class,
        'apiAssignUser',
    ])->name('api.vendor.contact.assign_user');
    // Assign labels to contact
    Route::post('/contacts/{contactUid}/assign-labels', [
        WhatsAppServiceController::class,
        'apiAssignLabels',
    ])->name('api.vendor.contact.assign_labels');
    // Update contact notes
    Route::put('/contacts/{contactUid}/notes', [
        WhatsAppServiceController::class,
        'apiUpdateNotes',
    ])->name('api.vendor.contact.notes.update');

    // Message Log APIs
    // Get message log
    Route::get('/message-log', [
        WhatsAppServiceController::class,
        'apiGetMessageLog',
    ])->name('api.vendor.message_log.read');
    // Get single message
    Route::get('/messages/{messageUid}', [
        WhatsAppServiceController::class,
        'apiGetMessage',
    ])->name('api.vendor.message.read');
});

// Mobile app apis
Route::group(['middleware' => 'guest'], function () {

     //vendor registration
    Route::post('/register/vendor', [
        AuthController::class,
        'register',
    ])->name('api.auth.register.process');
     //vendor activation
    Route::post('/register/vendor/activation', [
        AuthController::class,
        'activationRequiredRegister',
    ])->name('api.activation_required.auth.register.process');

    Route::group([
        'prefix' => 'user',
    ], function () {
        // login process
        Route::post('/login-process', [
            ApiUserController::class,
            'loginProcess'
        ])->name('api.user.login.process');

        // User Registration prepare data
        Route::get('/prepare-sign-up', [
            ApiUserController::class,
            'prepareSignUp'
        ])->name('api.user.sign_up.prepare');

        // User Registration
        Route::post('/process-sign-up', [
            ApiUserController::class,
            'processSignUp'
        ])->name('api.user.sign_up.process');
       
    });
});
// vendor authenticated routes
Route::group([
    'middleware' => 'app_api.vendor.authenticate',
], function () {
    // broadcast private channel check
    Broadcast::routes([]);

    /*
    Media Component Routes Start from here
    ------------------------------------------------------------------- */
    Route::group([
        'prefix' => 'media',
    ], function () {
        // Temp Upload
        Route::post('/upload-temp-media/{uploadItem?}', [
            MediaController::class,
            'uploadTempMedia',
        ])->name('api.media.upload_temp_media');
    });

    Route::group([
        'prefix' => 'vendor/',
    ], function () {
        //unread chat count
        Route::get('/whatsapp/chat/unread-count', [
            WhatsAppServiceController::class,
            'unreadCount',
        ])->name('app_api.vendor.chat_message.read.unread_count');
        // get contacts data
        Route::get('/contact/contacts-data/{contactUid?}', [
            WhatsAppServiceController::class,
            'getContactsData',
        ])->name('app_api.vendor.contacts.data.read');
        // contact chat data
        Route::get('/whatsapp/contact/chat/{contactUid?}', [
            WhatsAppServiceController::class,
            'chatView',
        ])->name('app_api.vendor.chat_message.contact.view');
        // contact chat data via append, prepend etc
        Route::get('/whatsapp/contact/chat-data/{contactUid}/{way?}', [
            WhatsAppServiceController::class,
            'getContactChatData',
        ])->name('app_api.vendor.chat_message.data.read');
        Route::post('/whatsapp/contact/chat/send', [
            WhatsAppServiceController::class,
            'sendChatMessage',
        ])->name('app_api.vendor.chat_message.send.process');

        // Contact get labels and team members data
        Route::get('/whatsapp/contact/chat-box-data/{contactUid}', [
            ContactController::class,
            'getLabelsForApi',
        ])->name('app_api.chat.box.base.data');
            // Contact get the data
        Route::get('/contacts/{contactIdOrUid}/get-update-data', [
            ContactController::class,
            'updateContactData',
        ])->name('app_api.vendor.contact.read.update.data');
        //media type api
        Route::get('/whatsapp/contact/chat/prepare-send-media/{mediaType?}', [
            WhatsAppServiceController::class,
            'prepareSendMediaUploader',
        ])->name('app_api.vendor.chat_message_media.upload.prepare');
        Route::post('/whatsapp/contact/chat/send-media', [
            WhatsAppServiceController::class,
            'sendChatMessageMedia',
        ])->name('app_api.vendor.chat_message_media.send.process');
        Route::post('/whatsapp/contact/chat/update-notes', [
            ContactController::class,
            'updateNotes',
        ])->name('app_api.vendor.chat.update_notes.process');
        Route::post('/whatsapp/contact/chat/assign-user', [
            ContactController::class,
            'assignChatUser',
        ])->name('app_api.vendor.chat.assign_user.process');
        Route::post('/whatsapp/contact/chat/assign-labels', [
            ContactController::class,
            'assignContactLabels',
        ])->name('app_api.vendor.chat.assign_labels.process');
        //clear chat history
        Route::post('/whatsapp/contact/chat/clear-history/{contactUid}', [
            WhatsAppServiceController::class,
            'clearChatHistory',
        ])->name('app_api.vendor.chat_message.delete.process');
            //create whatsapp contact label
         Route::post('/whatsapp/contact/create-label', [
            ContactController::class,
            'createLabel',
        ])->name('app_api.vendor.chat.label.create.write');
        //whatsapp contact edit lable
            Route::post('/whatsapp/contact/chat/edit-label', [
            ContactController::class,
            'updateLabel',
        ])->name('app_api.vendor.chat.label.update.write');
            //whatsapp contact delete lable
            Route::post('/whatsapp/contact/chat/delete-label/{labelUid}', [
            ContactController::class,
            'deleteLabelProcess',
        ])->name('app_api.vendor.chat.label.delete.write');
           
    });
    // logout
    Route::post('/user/logout', [
        ApiUserController::class,
        'logout'
    ])->name('api.user.logout');
    Route::post('/update-password', [
        AuthController::class,
        'updatePassword',
    ])->name('api.auth.password.update.process');
  
    // profile update request
    Route::post('/user/profile-update', [
        UserController::class,
        'updateProfile',
    ])->name('api.user.profile.update');
   
    // Account Activation
    Route::get('/{userUid}/account-activation', [
        AuthController::class,
        'accountActivation',
    ])->name('api.user.account.activation');
});
