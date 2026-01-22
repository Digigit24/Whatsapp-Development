<div class="row">
    <div class="col-sm-12 col-md-8 col-lg-6 lw-template-structure-form" x-data="{ 
        carouselTemplateData: [],
        contactDataMaps: [],
        maxCardLimit:10,
        currentCardCount:2,
        loadPlugin: function() {
            window.lwPluginsInit();
        }
    }">
        {{-- Cards Start Here --}}
        <fieldset>
            <legend>{{ __tr('Cards') }}</legend>
            <template x-if="carouselTemplateData[1] && carouselTemplateData[1]['cards'] && carouselTemplateData[1]['cards'].length > 0">
                <template x-for="(card, index) in carouselTemplateData[1]['cards']" :key="index">
                    <fieldset>
                        <legend><span x-text="index + 1"></span></legend>
                        <!-- <template x-for="(component, subIndex) in card?.components" :key="subIndex"> -->
                            <div>
                                <div x-show="card?.components[0].type == 'HEADER'">
                                    <h3><u>{{ __tr('Media Header') }}</u></h3>
                                    <input x-bind:id="'lwUploadMediaType'+index" type="hidden" :value="card?.components[0].format" x-bind:name="`carousel_templates[${index}][uploaded_media_file_type]`" />
                                    <!-- Image Uploader -->
                                    <div x-show="card?.components[0].format == 'IMAGE'">
                                        <div class="form-group col-md-4 col-sm-12">
                                            <label x-bind:for="'lwHeaderImageFilepond'+index">{{ __tr('Select Image') }}</label>
                                            <input x-bind:id="'lwHeaderImageFilepond'+index" type="file" data-allow-revert="true"
                                                data-label-idle="{{ __tr('Select Image') }}" class="lw-file-uploader" data-instant-upload="true"
                                                data-action="<?= route('media.upload_temp_media', 'whatsapp_image') ?>" data-allowed-media='{{ getMediaRestriction('whatsapp_image') }}'
                                                x-bind:data-file-input-element="'#lwUploadMedia'+index" data-lw-plugin="lwUploader">
                                        </div>
                                    </div>
                                    <!-- /Image Uploader -->
                                    <!-- Video Uploader -->
                                    <div x-show="card?.components[0].format == 'VIDEO'">
                                        <div class="form-group col-md-4 col-sm-12">
                                            <label x-bind:for="'lwHeaderVideoFilepond'+index">{{ __tr('Select Video') }}</label>
                                            <input x-bind:id="'lwHeaderVideoFilepond'+index" type="file" data-allow-revert="true"
                                                data-label-idle="{{ __tr('Select Video') }}" class="lw-file-uploader" data-instant-upload="true"
                                                data-action="<?= route('media.upload_temp_media', 'whatsapp_video') ?>" data-allowed-media='{{ getMediaRestriction('whatsapp_video') }}'
                                                x-bind:data-file-input-element="'#lwUploadMedia'+index" data-lw-plugin="lwUploader">
                                        </div>
                                    </div>
                                    <!-- Video Uploader -->
                                    <input x-bind:id="'lwUploadMedia'+index" type="hidden" value="" x-bind:name="`carousel_templates[${index}][uploaded_media_file_name]`" />
                                </div>
                                <hr>
                                <!-- Body Start Here -->
                                <div x-show="card?.components[1].type == 'BODY'">
                                    <h3><u>{{ __tr('Body Text') }}</u></h3>
                                    <template x-if="card?.components[1]['example'] && card?.components[1]['example']['body_text'] && card?.components[1]['example']['body_text'][0].length > 0">
                                        <div class="row">
                                            <template x-for="(variable, varIndex) in card?.components[1].example.body_text[0]" :key="varIndex">
                                                <div class="col-md-12 col-lg-6 card border-0">
                                                    <x-lw.input-field  placeholder="{{  __tr('Choose or Write you own') }}" type="selectize" data-lw-plugin="lwSelectize" x-bind:id="'lwBodyVarSelect'+index+varIndex"
                                                        x-bind:name="`carousel_templates[${index}][body_example_fields][${varIndex}]`" data-form-group-class="" data-selected=" " label="{{ __tr('Assign content for variable') }}"  data-create="true">
                                                        <x-slot name="selectOptions">
                                                            <option value="">{{ __tr('Select or Type your own') }}</option>
                                                            <optgroup label="{{ __tr('Assign from User Contact Details') }}">
                                                                <template x-for="(contact, contactIndex) in contactDataMaps" :key="contactIndex">
                                                                    <option :value="contactIndex" x-text="contact"></option>
                                                                </template>
                                                            </optgroup>
                                                            <optgroup label="{{ __tr('or custom values') }}">
                                                                <option disabled="">{{  __tr('type to use custom value') }}</option>
                                                            </optgroup>
                                                        </x-slot>
                                                    </x-lw.input-field>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <hr>
                                <!-- /Body End Here -->
                                <!-- Buttons Start Here -->
                                <div x-show="card?.components[2].type == 'BUTTONS'">
                                    <h3><u>{{ __tr('Buttons') }}</u></h3>
                                    <template x-for="(button, buttonIndex) in card?.components[2].buttons" :key="buttonIndex">
                                        <div>
                                            <input type="hidden" :value="button.type" x-bind:name="`carousel_templates[${index}][button_type][${buttonIndex}]`">
                                            <div x-show="button.type == 'QUICK_REPLY'">
                                                <x-lw.input-field x-bind:id="'lwQuickReplyPayload'+index"
                                                    type="text" 
                                                    data-form-group-class=""
                                                    :label="__tr('Quick Reply Button Payload')"
                                                    x-model="carouselTemplateData[index].quick_reply_button_payload"
                                                    x-bind:name="`carousel_templates[${index}][quick_reply_button_payload]`">
                                                </x-lw.input-field>
                                            </div>
                                            <div x-show="button.example">
                                                <div class="row">
                                                    <div class="col-md-12 col-lg-6 card border-0">
                                                        <x-lw.input-field  placeholder="{{  __tr('Choose or Write you own') }}" type="selectize" data-lw-plugin="lwSelectize" x-bind:id="'lwFieldSelectizeField'+index"
                                                            x-bind:name="`carousel_templates[${index}][button_example_field]`" data-form-group-class="" data-selected=" " label="{{ __tr('Assign content for variable') }}"  data-create="true">
                                                            <x-slot name="selectOptions">
                                                                <option value="">{{ __tr('Select or Type your own') }}</option>
                                                                <optgroup label="{{ __tr('Assign from User Contact Details') }}">
                                                                    <template x-for="(contact, contactIndex) in contactDataMaps" :key="contactIndex">
                                                                        <option :value="contactIndex" x-text="contact"></option>
                                                                    </template>
                                                                </optgroup>
                                                                <optgroup label="{{ __tr('or custom values') }}">
                                                                    <option disabled="">{{  __tr('type to use custom value') }}</option>
                                                                </optgroup>
                                                            </x-slot>
                                                        </x-lw.input-field>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <!-- Buttons End Here -->
                            </div>
                        <!-- </template> -->
                    </fieldset>
                </template>
            </template>
        </fieldset>
        {{-- /Cards Start Here --}}
    </div>
</div>

@push('appScripts')
<script>
    (function($) {
    'use strict';
    })(jQuery);
</script>
@endpush