"use strict";
var get_bot_type = cwginstock.get_bot_type;
var recaptcha_enabled = cwginstock.enable_recaptcha;
var is_v3_recaptcha = cwginstock.is_v3_recaptcha;
var recaptcha_site_key = cwginstock.recaptcha_site_key;
// turnstile
var turnstile_enabled = cwginstock.enable_turnstile;
var turnstile_site_key = cwginstock.turnstile_site_key;

var gtoken = '';


var popup_notifier = {
	init: function () {
		jQuery(document).on(
			'click',
			'.cwg_popup_submit',
			function () {
				jQuery.blockUI({ message: null });
				var current = jQuery(this);
				var product_id = current.attr('data-product_id');
				var variation_id = current.attr('data-variation_id');
				var quantity = current.attr('data-quantity');
				var security = current.attr('data-security');

				var data = {
					action: 'cwg_trigger_popup_ajax',
					product_id: product_id,
					variation_id: variation_id,
					quantity: quantity,
					security: security
				};
				if (get_bot_type == 'recaptcha' && recaptcha_enabled == '1' && is_v3_recaptcha == 'yes') {
					popup_notifier.popup_generate_v3_response(this);
				} else {
					popup_notifier.perform_ajax(data);
				}
				return false;
			}
		);
	},
	popup_generate_v3_response: function (currentel) {
		if (get_bot_type == 'recaptcha' && recaptcha_enabled == '1' && is_v3_recaptcha == 'yes') {
			grecaptcha.ready(
				function () {
					grecaptcha.execute(recaptcha_site_key, { action: 'popup_form' }).then(
						function (token) {
							console.log(token);

							var current = jQuery(currentel);
							var product_id = current.attr('data-product_id');
							var variation_id = current.attr('data-variation_id');
							var quantity = current.attr('data-quantity');

							var data = {
								action: 'cwg_trigger_popup_ajax',
								product_id: product_id,
								variation_id: variation_id,
								quantity: quantity,
								security: token
							};
							popup_notifier.perform_ajax(data);
							gtoken = token;
						}
					);
				}
			);
		}
	},
	perform_ajax: function (data) {
		jQuery.ajax(
			{
				type: "post",
				url: cwginstock.default_ajax_url,
				data: data,
				success: function (msg) {
					jQuery.unblockUI();
					Swal.fire(
						{
							html: msg,
							showCloseButton: true,
							showConfirmButton: false,
							willOpen: function () {
								if ('recaptcha' == get_bot_type) {
									if ('1' == recaptcha_enabled) {
										jQuery('.g-recaptcha').before('<div id="cwg-google-recaptcha"></div>');
										jQuery('.g-recaptcha').remove();
									}
								} else {
									if ('1' == turnstile_enabled) {
										turnstile.render(
											'.cf-turnstile',
											{
												sitekey: turnstile_site_key,
												theme: 'light',
												callback: function (token) {
													cwginstock_turnstile_callback(token);
												},
											}
										);
									}
								}
							},
							didOpen: function () {
								jQuery(document).trigger('cwginstock_popup_open_callback');
							},
							willClose: function () {
								jQuery(document).trigger('cwginstock_popup_close_callback');
							},
						}
					);

				},
				error: function (request, status, error) {
					jQuery.unblockUI();
				}
			}
		);
	},
};
popup_notifier.init();


jQuery(document).on(
	'cwginstock_popup_open_callback',
	function () {
		instock_notifier.onloadcallback();
		instock_notifier.initialize_phone();
	}
);

jQuery(document).on(
	'cwginstock_popup_close_callback',
	function () {
		instock_notifier.resetcallback();
	}
);
