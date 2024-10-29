"use strict";
jQuery(
    function ($) {
        function getEnhancedSelectFormatString() {
            return {
                'language': {
                    errorLoading: function () {
                        // Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
                        return wc_enhanced_select_params.i18n_searching;
                    },
                    inputTooLong: function (args) {
                        var overChars = args.input.length - args.maximum;

                        if (1 === overChars) {
                            return wc_enhanced_select_params.i18n_input_too_long_1;
                        }

                        return wc_enhanced_select_params.i18n_input_too_long_n.replace('%qty%', overChars);
                    },
                    inputTooShort: function (args) {
                        var remainingChars = args.minimum - args.input.length;

                        if (1 === remainingChars) {
                            return wc_enhanced_select_params.i18n_input_too_short_1;
                        }

                        return wc_enhanced_select_params.i18n_input_too_short_n.replace('%qty%', remainingChars);
                    },
                    loadingMore: function () {
                        return wc_enhanced_select_params.i18n_load_more;
                    },
                    maximumSelected: function (args) {
                        if (args.maximum === 1) {
                            return wc_enhanced_select_params.i18n_selection_too_long_1;
                        }

                        return wc_enhanced_select_params.i18n_selection_too_long_n.replace('%qty%', args.maximum);
                    },
                    noResults: function () {
                        return wc_enhanced_select_params.i18n_no_matches;
                    },
                    searching: function () {
                        return wc_enhanced_select_params.i18n_searching;
                    }
                }
            };
        }
        jQuery(document.body).on(
            'wc-enhanced-select-init',
            function () {
                // Ajax tag search boxes
                jQuery(':input.wc-tag-search').filter(':not(.enhanced)').each(
                    function () {
                        var select2_args = jQuery.extend(
                            {
                                allowClear: jQuery(this).data('allow_clear') ? true : false,
                                placeholder: jQuery(this).data('placeholder'),
                                minimumInputLength: jQuery(this).data('minimum_input_length') ? jQuery(this).data('minimum_input_length') : 3,
                                escapeMarkup: function (m) {
                                    return m;
                                },
                                ajax: {
                                    url: wc_enhanced_select_params.ajax_url,
                                    dataType: 'json',
                                    delay: 250,
                                    data: function (params) {
                                        return {
                                            term: params.term,
                                            action: 'woocommerce_json_search_tags',
                                            security: cwg_enhanced_selected_params.search_tags_nonce
                                        };
                                    },
                                    processResults: function (data) {
                                        var terms = [];
                                        if (data) {
                                            jQuery.each(
                                                data,
                                                function (id, term) {
                                                    terms.push(
                                                        {
                                                            id: term.slug,
                                                            text: term.formatted_name
                                                        }
                                                    );
                                                }
                                            );
                                        }
                                        return {
                                            results: terms
                                        };
                                    },
                                    cache: true
                                }
                            },
                            getEnhancedSelectFormatString()
                        );

                        jQuery(this).selectWoo(select2_args).addClass('enhanced');
                    }
                );
            }
        ).trigger('wc-enhanced-select-init');
        // Phone field check
        var show_hide_phone_field = {
            init: function () {
                var ele = '.show_phone_field';
                show_hide_phone_field.show_hide(ele);
                jQuery(document).on(
                    'click',
                    '.show_phone_field',
                    function () {
                        var element = this;
                        show_hide_phone_field.show_hide(element);
                    }
                );

            },
            show_hide: function (element) {
                if (jQuery(element).is(':checked')) {
                    jQuery('.phone_field_optional').parent().parent().show();
                    jQuery('.cwg_default_country').parent().parent().show();
                    jQuery('.hide_country_placeholder').parent().parent().show();
                } else {
                    jQuery('.phone_field_optional').parent().parent().hide();
                    jQuery('.cwg_default_country').parent().parent().hide();
                    jQuery('.hide_country_placeholder').parent().parent().hide();
                }
            }
        }
        show_hide_phone_field.init();
        //show or hide guest msg
        var show_hide_guest_msg = {
            init: function () {
                var ele = '.hide_form_guests';
                show_hide_guest_msg.show_hide(ele);
                jQuery(document).on(
                    'click',
                    '.hide_form_guests',
                    function () {
                        var element = this;
                        show_hide_guest_msg.show_hide(element);
                    }
                );

            },
            show_hide: function (element) {
                if (jQuery(element).is(':checked')) {
                    jQuery('.hide_form_for_guest_msg').parent().parent().show();
                } else {
                    jQuery('.hide_form_for_guest_msg').parent().parent().hide();
                }
            }
        }
        show_hide_guest_msg.init();

        var stop_sending_email_staging = {
            init: function () {
                var ele = '.stop_sending_email_staging';
                stop_sending_email_staging.show_hide(ele);
                jQuery(document).on(
                    'click',
                    '.stop_sending_email_staging',
                    function () {
                        var element = this;
                        stop_sending_email_staging.show_hide(element);
                    }
                );

            },
            show_hide: function (element) {
                if (jQuery(element).is(':checked')) {
                    jQuery('.staging_domains').parent().parent().show();
                } else {
                    jQuery('.staging_domains').parent().parent().hide();
                }
            }
        }
        stop_sending_email_staging.init();

        // show or hide placeholder
        var phone_number_placeholder = {
            init: function () {
                var get_country = jQuery('.cwg_default_country').val();
                phone_number_placeholder.check_placeholder_type(get_country);

                jQuery('.cwg_default_country').on(
                    'change',
                    function () {
                        phone_number_placeholder.check_placeholder_type(jQuery(this).val());
                    }
                );
                jQuery('.cwg_default_country_placeholder').on(
                    'change',
                    function () {
                        var get_country = jQuery('.cwg_default_country').val();
                        phone_number_placeholder.check_custom_placeholder(get_country, jQuery(this).val());
                    }
                );
            },
            check_placeholder_type: function (country_code) {
                if (country_code != '') {
                    jQuery('.cwg_default_country_placeholder').parent().parent().show();
                } else {
                    jQuery('.cwg_default_country_placeholder').parent().parent().hide();
                }
                var type = jQuery('.cwg_default_country_placeholder').val();
                phone_number_placeholder.check_custom_placeholder(country_code, type);
            },
            check_custom_placeholder: function (country_code, type) {
                if (country_code != '') {
                    if (type == 'custom') {
                        jQuery('.cwg_custom_placeholder').parent().parent().show();
                    } else {
                        jQuery('.cwg_custom_placeholder').parent().parent().hide();
                    }
                } else {
                    jQuery('.cwg_custom_placeholder').parent().parent().hide();
                }
            }

        }
        phone_number_placeholder.init();

        // show/hide the bot protection type settings as per the admin selection
        var instock_bot_protection = {
            init: function () {
                var cvalue = jQuery('.cwg_bot_protection_via').val();
                instock_bot_protection.visibility_fields(cvalue);
                /*var rvalue = jQuery('.cwg_instock_recaptcha_version').val();
                 instock_notifier_recaptcha.visibility_fields(rvalue);*/
                jQuery(document).on('change', '.cwg_bot_protection_via', this.show_hide_fields);
            },

            show_hide_fields: function (event) {
                var current_value = jQuery(this).val();
                instock_bot_protection.visibility_fields(current_value);
            },

            visibility_fields: function (current_value) {
                if (current_value == 'turnstile') {
                    instock_notifier_recaptcha.init();
                    instock_bot_protection.recaptcha_wrapper('hide');
                    instock_bot_protection.turnstile_wrapper('show');

                } else {

                    instock_bot_protection.recaptcha_wrapper('show');
                    instock_bot_protection.turnstile_wrapper('hide');
                    instock_notifier_recaptcha.init();
                }
            },
            recaptcha_wrapper: function (display) {
                if (display == 'show') {
                    jQuery('.cwg_google_recaptcha').parent().parent().show();
                } else {
                    jQuery('.cwg_google_recaptcha').parent().parent().hide();
                }
            },
            turnstile_wrapper: function (display) {
                if (display == 'show') {
                    jQuery('.cwg_instock_turnstile').parent().parent().show();
                } else {
                    jQuery('.cwg_instock_turnstile').parent().parent().hide();
                }
            }
        };

        // show/hide recaptcha settings fields as per the version
        var instock_notifier_recaptcha = {
            init: function () {
                var cvalue = jQuery('.cwg_instock_recaptcha_version').val();
                instock_notifier_recaptcha.visibility_fields(cvalue);
                jQuery(document).on('change', '.cwg_instock_recaptcha_version', this.show_hide_fields);
            },

            show_hide_fields: function (event) {
                var current_value = jQuery(this).val();
                instock_notifier_recaptcha.visibility_fields(current_value);
            },
            visibility_fields: function (current_value) {
                if (current_value === 'v3') {
                    instock_notifier_recaptcha.v3_fields('show');
                    instock_notifier_recaptcha.v2_fields('hide');
                } else {
                    instock_notifier_recaptcha.v3_fields('hide');
                    instock_notifier_recaptcha.v2_fields('show');
                }
            },
            v3_fields: function (display) {
                if (display == 'show') {
                    jQuery('.cwg_instock_recaptcha_v3').parent().parent().show();
                } else {
                    jQuery('.cwg_instock_recaptcha_v3').parent().parent().hide();
                }
            },
            v2_fields: function (display) {
                if (display == 'show') {
                    jQuery('.cwg_instock_recaptcha_v2').parent().parent().show();
                } else {
                    jQuery('.cwg_instock_recaptcha_v2').parent().parent().hide();
                }
            },
        };

        jQuery(
            function ($) {
                $("#submitForm").click(
                    function (e) {
                        e.preventDefault();
                        var security = jQuery(this).attr('data-security');
                        jQuery(this).attr('disabled', 'disabled');
                        var current_event = jQuery(this);
                        var data = {
                            action: 'cwginstock_test_email',
                            security: security
                        };
                        $.ajax(
                            {
                                type: 'POST',
                                url: cwg_enhanced_selected_params.ajax_url,
                                data: data,
                                success: function (response) {
                                    if (response.status == 'failure') {
                                        $('.cwginstock_test_email_info').html("Email sending Failed, last tested on:" + response.checked_on).css('color', 'red');
                                    } else {
                                        $('.cwginstock_test_email_info').html("Email sent successfully, last tested on:" + response.checked_on).css('color', 'green');
                                    }
                                    current_event.removeAttr('disabled');
                                },
                                error: function (res) {
                                    $('.cwginstock_test_email_info').html(res.responseJSON.data);
                                    current_event.removeAttr('disabled');
                                },
                            }
                        );
                    }
                );
                $("#submitFormUI").click(
                    function (e) {
                        e.preventDefault();
                        var security = jQuery(this).attr('data-security');
                        jQuery(this).attr('disabled', 'disabled');
                        var backend_view = jQuery('#cwginstock_backend_ui').val();// select the current dropdown
                        var current_event = jQuery(this);
                        var uidata = {
                            action: 'cwginstock_backend_ui',
                            security: security,
                            cwginstock_view: backend_view,
                        };
                        $.ajax(
                            {
                                type: 'POST',
                                url: cwg_enhanced_selected_params.ajax_url,
                                data: uidata,
                                success: function (response) {
                                    $('.cwginstock_settings_change_info').html(response.message).css('color', 'green');
                                    current_event.removeAttr('disabled');
                                },
                                error: function (res) {
                                    $('.cwginstock_settings_change_info').html(res.responseJSON.data);
                                    current_event.removeAttr('disabled');
                                },
                            }
                        );
                    }
                );
            }
        );
        instock_notifier_recaptcha.init();
        instock_bot_protection.init();
    }
);
