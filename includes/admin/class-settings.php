<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_Settings' ) ) {

	class CWG_Instock_Settings {

		private $api;

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_settings_menu' ) );
			add_action( 'admin_init', array( $this, 'register_manage_settings' ) );
			add_action( 'admin_init', array( $this, 'default_value' ) );
			$this->api = new CWG_Instock_API();
		}

		public function add_settings_menu() {
			add_submenu_page( 'edit.php?post_type=cwginstocknotifier', __( 'Settings', 'back-in-stock-notifier-for-woocommerce' ), __( 'Settings', 'back-in-stock-notifier-for-woocommerce' ), 'manage_woocommerce', 'cwg-instock-mailer', array( $this, 'manage_settings' ) );
		}

		public function manage_settings() {
			echo "<div class='wrap'>";
			settings_errors();
			?>
			<form action='options.php' method='post' id="cwginstocknotifier_settings">

				<h1>
					<?php esc_html_e( 'Back In Stock Notifier for WooCommerce Settings', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</h1>
				<div class="notice notice-success cwg_marketing_notice">
					<p>
						<strong>Savings and Power Combined</strong>: All Add-ons, One-Time Payment of $39, Zero Monthly Hassles! <a
							href="https://codewoogeek.online/shop/back-in-stock-notifier/bundle-add-ons/"
							target="_blank"><strong>Buy Now Bundle Add-ons!</strong></a>
					</p>
					<p>Make a difference with your support—contribute financially to fuel our progress!</p>
				</div>
				<?php
				settings_fields( 'cwginstocknotifier_settings' );
				/**
				 * Action before the setting section
				 *
				 * @since 1.0.0
				 */
				do_action( 'cwginstocksettings_before_section' );
				// get settings tab
				$settings_ui = get_option( 'cwginstock_backend_ui', 'tabbed_ui' );
				if ( 'tabbed_ui' == $settings_ui ) {
					do_tabbed_settings_sections( 'cwginstocknotifier_settings' );
				} else {
					do_settings_sections( 'cwginstocknotifier_settings' );
				}
				submit_button();
				?>
			</form>
			<?php
			echo '</div>';
		}

		public function register_manage_settings() {
			register_setting( 'cwginstocknotifier_settings', 'cwginstocksettings', array( $this, 'sanitize_data' ) );
			add_settings_section( 'cwginstock_section', __( 'Frontend Form', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'section_heading' ), 'cwginstocknotifier_settings' );
			add_settings_field( 'cwg_frontend_displayform_type', __( 'Frontend Subscribe Form Display Type', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'cwg_frontend_displayform' ), 'cwginstocknotifier_settings', 'cwginstock_section' );
			add_settings_field( 'cwg_instock_form_title', __( 'Title for Subscribe Form', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'form_title' ), 'cwginstocknotifier_settings', 'cwginstock_section' );
			add_settings_field( 'cwg_instock_name_placeholder', __( 'Placeholder for Name Field', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'form_name_placeholder' ), 'cwginstocknotifier_settings', 'cwginstock_section' );

			add_settings_field( 'cwg_instock_form_placeholder', __( 'Placeholder for Email Field', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'form_email_placeholder' ), 'cwginstocknotifier_settings', 'cwginstock_section' );
			add_settings_field( 'cwg_instock_form_button', __( 'Button Label', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'button_label' ), 'cwginstocknotifier_settings', 'cwginstock_section' );

			add_settings_section( 'cwginstock_section_visibility', __( 'Visibility Settings', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'visibility_section_heading' ), 'cwginstocknotifier_settings' );
			add_settings_field( 'cwginstock_hide_name', __( 'Hide Name', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'hide_name_field' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			// phone
			add_settings_field( 'cwginstock_show_phone', __( 'Show Phone', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'show_phone_field' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_show_phone_optional', __( 'Phone field optional', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'phone_field_optional' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_phone_default_country', __( 'Default Country for Phone Field', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'default_country' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_phone_country_placeholder', __( 'Default Country for Phone Field Placeholder', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'default_country_placeholder' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_phone_custom_placeholder', __( 'Custom Placeholder', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'custom_placeholder' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_remove_placeholder', __( 'Hide Country Placeholder', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'hide_placeholder' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );

			add_settings_field( 'cwginstock_visibility_guest', __( 'Hide Subscribe Form for Guests', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'hide_form_for_guest' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_visibility_guest_message', __( 'Message to display when the subscribe form is hidden for non logged-in Users', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'hide_form_for_guest_msg' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			// since version 1.7
			add_settings_field( 'cwginstock_visibility_member', __( 'Hide Subscribe Form for Members', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'hide_form_for_member' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );

			add_settings_field( 'cwginstock_visibility_backorder', __( 'Show Subscribe Form on Backorders', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'show_form_for_backorders' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_visibility_subscribebutton_catalog', __( 'Show Subscribe Now Button in Catalog Page(shop/category)', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'show_subscribe_button_catalog' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_hide_readmore_button', __( 'Hide Read more Button in Catalog Page(shop/category)', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'hide_readmore_button_catalog' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );

			add_settings_field( 'cwginstock_visibility_products', __( 'Show/Hide Subscribe Form for specific products', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'visibility_for_specific_products' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_visibility_categories', __( 'Show/Hide Subscribe Form for specific categories', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'visibility_for_specific_categories' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_visibility_tags', __( 'Show/Hide Subscribe Form for specific tags', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'visibility_for_specific_tags' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );

			add_settings_field( 'cwginstock_visibility_on_regular', __( 'Hide Subscribe Form on Regular Products out of stock', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'visibility_settings_for_product_on_regular' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_visibility_on_sale', __( 'Hide Subscribe Form on Sale Products out of stock', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'visibility_settings_for_product_on_sale' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );

			add_settings_field( 'cwginstock_bypass_disabled_variation', __( "Don't overwrite disabled out of stock variations from theme configuration", 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'disabled_variation_settings_option' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );
			add_settings_field( 'cwginstock_bypass_wc_visibility', __( 'Ignore WooCommerce Out of Stock Visibility Settings for Variation', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'ignore_settings_for_wc_out_of_stock_visibility' ), 'cwginstocknotifier_settings', 'cwginstock_section_visibility' );

			add_settings_section( 'cwginstock_section_error', __( 'Message Settings', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'error_section_heading' ), 'cwginstocknotifier_settings' );
			add_settings_field( 'cwg_instock_sub_success', __( 'Success Subscription Message', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'success_subscription_message' ), 'cwginstocknotifier_settings', 'cwginstock_section_error' );
			add_settings_field( 'cwg_instock_already_exists', __( 'Email Already Subscribed Message', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'email_already_subscribed' ), 'cwginstocknotifier_settings', 'cwginstock_section_error' );

			add_settings_field( 'cwg_instock_error_name_empty', __( 'Name Field Empty Error', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'empty_name_fields' ), 'cwginstocknotifier_settings', 'cwginstock_section_error' );
			add_settings_field( 'cwg_instock_error_email_empty', __( 'Email Field Empty Error', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'empty_email_address' ), 'cwginstocknotifier_settings', 'cwginstock_section_error' );
			add_settings_field( 'cwg_instock_error_email_invalid', __( 'Invalid Email Error', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'invalid_email_address' ), 'cwginstocknotifier_settings', 'cwginstock_section_error' );
			add_settings_field( 'cwg_instock_error_phone_invalid', __( 'Invalid Phone Number Error', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'invalid_phone_number' ), 'cwginstocknotifier_settings', 'cwginstock_section_error' );
			add_settings_field( 'cwg_instock_error_phone_too_short', __( 'Phone Number too short error', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'phone_number_too_short' ), 'cwginstocknotifier_settings', 'cwginstock_section_error' );
			add_settings_field( 'cwg_instock_error_phone_too_long', __( 'Phone Number too long error', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'phone_number_too_long' ), 'cwginstocknotifier_settings', 'cwginstock_section_error' );

			add_settings_section( 'cwginstock_section_mail', __( 'Mail Settings', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'mail_settings_heading' ), 'cwginstocknotifier_settings' );
			add_settings_field( 'cwg_instock_success_subscription_mail', __( 'Enable Success Subscription Mail', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'success_subscription_mail' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );
			add_settings_field( 'cwg_instock_success_subscription_subject', __( 'Success Subscription Mail Subject', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'success_subscription_mail_subject' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );
			add_settings_field( 'cwg_instock_success_subscription_message', __( 'Success Subscription Mail Message', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'success_subscription_mail_message' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );
			add_settings_field( 'cwg_instock_success_subscription_copy', __( 'Additionally Send this Subscription mail as a copy to specific email ids', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'enable_copy_subscription' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );
			add_settings_field( 'cwg_instock_success_subscription_copy_recipients', __( 'Enter Email Ids separated by commas that you want to receive subscription copy mail', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'subscription_copy_recipients' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );

			add_settings_field( 'cwg_instock_mail', __( 'Enable Instock Mail', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'enable_instock_mail' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );
			add_settings_field( 'cwg_instock_mail_product_visibility_status', __( 'Consider Only Published Product Status', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'enable_instock_mail_for_product_status' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );
			add_settings_field( 'cwg_instock_mail_subject', __( 'Instock Mail Subject', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'instock_mail_subject' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );
			add_settings_field( 'cwg_instock_mail_message', __( 'Instock Mail Message', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'instock_mail_message' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );
			add_settings_field( 'cwg_instock_mail_set_minimum_stock_quantity', __( 'Minimum stock quantity threshold value', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'instock_mail_message_set_stock_quantity' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );
			add_settings_field( 'cwg_instock_post_status_subscribed', __( 'Keep Subscription Entry to Subscribed Status even Instock Email Sent (Unless it is Unsubscribed)', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'enable_post_status_subscribed' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );

			add_settings_section( 'cwginstock_section_bgprocess', __( 'Background Process Engine - Advanced Settings', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'background_process_heading' ), 'cwginstocknotifier_settings' );
			add_settings_field( 'cwginstock_bgp_selection', __( 'Background Process Engine', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'bgp_engine' ), 'cwginstocknotifier_settings', 'cwginstock_section_bgprocess' );
			/**
			 * Action to register settings
			 *
			 * @since 1.0.0
			 */
			do_action( 'cwginstock_register_settings' );
		}

		public function section_heading() {
			esc_html_e( 'Customize the Frontend Subscribe Form when Product become out of stock', 'back-in-stock-notifier-for-woocommerce' );
		}

		public function cwg_frontend_displayform() {
			$options = get_option( 'cwginstocksettings' );

			$array_of_modes = array(
				'1' => __( 'Inline Subscribe Form', 'back-in-stock-notifier-for-woocommerce' ),
				'2' => __( 'Pop-Up Subscribe Form', 'back-in-stock-notifier-for-woocommerce' ),
			);
			?>
			<select name="cwginstocksettings[mode]">
				<?php
				if ( is_array( $array_of_modes ) && ! empty( $array_of_modes ) ) {
					foreach ( $array_of_modes as $each_key => $each_value ) {
						$chosen_mode = isset( $options['mode'] ) && $options['mode'] == $each_key ? 'selected=selected' : '';
						?>
						<option value="<?php echo do_shortcode( $each_key ); ?>" <?php echo do_shortcode( $chosen_mode ); ?>>
							<?php echo do_shortcode( $each_value ); ?>
						</option>
						<?php
					}
				}
				?>
			</select>
			<?php
		}

		public function form_title() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[form_title]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $options['form_title'] ) ); ?>" />
			<?php
		}

		public function form_name_placeholder() {
			$options = get_option( 'cwginstocksettings' );
			$option_value = isset( $options['name_placeholder'] ) ? $options['name_placeholder'] : __( 'Your Name', 'back-in-stock-notifier-for-woocommerce' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[name_placeholder]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $option_value ) ); ?>" />
			<?php
		}

		public function form_email_placeholder() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[form_placeholder]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $options['form_placeholder'] ) ); ?>" />
			<?php
		}

		public function button_label() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[button_label]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $options['button_label'] ) ); ?>" />
			<?php
		}

		public function visibility_section_heading() {
			esc_html_e( 'Visibility Settings for Subscriber Form Frontend', 'back-in-stock-notifier-for-woocommerce' );
		}

		public function hide_name_field() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[hide_name_field]' <?php isset( $options['hide_name_field'] ) ? checked( $options['hide_name_field'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Hide name field in Subscribe Form', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function show_phone_field() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' class='show_phone_field' name='cwginstocksettings[show_phone_field]' <?php isset( $options['show_phone_field'] ) ? checked( $options['show_phone_field'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Show phone field in Subscribe Form', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function phone_field_optional() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' class='phone_field_optional' name='cwginstocksettings[phone_field_optional]' <?php isset( $options['phone_field_optional'] ) ? checked( $options['phone_field_optional'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Enable this option to make phone field as optional', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function default_country() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<select name='cwginstocksettings[default_country]' class='cwg_default_country'>
				<option value=''>Select Default Country</option>
				<?php
				$countries_obj = new WC_Countries();
				if ( $countries_obj ) {
					$countries = $countries_obj->__get( 'countries' );
					foreach ( $countries as $each_country => $country_name ) {
						?>
						<option value='<?php esc_html_e( $each_country ); ?>' <?php echo isset( $options['default_country'] ) && $each_country == $options['default_country'] ? 'selected=selected' : ''; ?>>
							<?php esc_html_e( $country_name, 'back-in-stock-notifier-for-woocommerce' ); ?>
						</option>
						<?php
					}
				}
				?>
			</select>
			<?php
		}

		public function default_country_placeholder() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<select class="cwg_default_country_placeholder" name="cwginstocksettings[default_country_placeholder]"
				style='width: 200px;'>
				<option value="default" <?php echo isset( $options['default_country_placeholder'] ) && 'default' == $options['default_country_placeholder'] ? 'selected=selected' : ''; ?>>
					<?php esc_html_e( 'Default/Automatic', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
				<option value="custom" <?php echo isset( $options['default_country_placeholder'] ) && 'custom' == $options['default_country_placeholder'] ? 'selected=selected' : ''; ?>>
					<?php esc_html_e( 'Custom Placeholder', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
			</select>
			<?php
		}

		public function custom_placeholder() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='text' class="cwg_custom_placeholder" style='width: 400px;' name='cwginstocksettings[custom_placeholder]'
				value='<?php echo wp_kses_post( isset( $options['custom_placeholder'] ) ? $options['custom_placeholder'] : '' ); ?>' />
			<?php
		}

		public function hide_placeholder() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' class='hide_country_placeholder' name='cwginstocksettings[hide_country_placeholder]' <?php isset( $options['hide_country_placeholder'] ) ? checked( $options['hide_country_placeholder'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Enable this option to hide the placeholder for the phone field in the front-end subscribe form', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function hide_form_for_guest() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' class='hide_form_guests' name='cwginstocksettings[hide_form_guests]' <?php isset( $options['hide_form_guests'] ) ? checked( $options['hide_form_guests'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Hide Subscribe Form for non logged-in Users', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function hide_form_for_guest_msg() {
			$options = get_option( 'cwginstocksettings' );
			$hide_form_for_guest_msg = isset( $options['hide_form_for_guest_msg'] ) ? $options['hide_form_for_guest_msg'] : '';
			?>
			<textarea class='hide_form_for_guest_msg' name="cwginstocksettings[hide_form_for_guest_msg]"
				style="width:350px;"><?php echo wp_kses_post( $this->api->sanitize_textarea_field( $hide_form_for_guest_msg ) ); ?></textarea>
			<p><i><?php esc_html_e( 'Message to display when the subscribe form is hidden for non logged-in Users', 'back-in-stock-notifier-for-woocommerce' ); ?></i>
			</p>
			<?php
		}

		public function hide_form_for_member() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[hide_form_members]' <?php isset( $options['hide_form_members'] ) ? checked( $options['hide_form_members'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Hide Subscribe Form for logged-in Users', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function show_form_for_backorders() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type="checkbox" name="cwginstocksettings[show_on_backorders]" <?php isset( $options['show_on_backorders'] ) ? checked( $options['show_on_backorders'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Display Subscribe Form for Back Order', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function show_subscribe_button_catalog() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type="checkbox" name="cwginstocksettings[show_subscribe_button_catalog]" <?php isset( $options['show_subscribe_button_catalog'] ) ? checked( $options['show_subscribe_button_catalog'], 1 ) : ''; ?>
				value="1" />
			<p><i>
					<?php esc_html_e( 'Display Subscribe Now Button in Catalog Page', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function hide_readmore_button_catalog() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type="checkbox" name="cwginstocksettings[hide_readmore_button]" <?php isset( $options['hide_readmore_button'] ) ? checked( $options['hide_readmore_button'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Hide Read more button in catalog page(shop/category)', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function visibility_for_specific_products() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<select style="width:320px;"
				data-placeholder="<?php esc_html_e( 'Select Products', 'back-in-stock-notifier-for-woocommerce' ); ?>"
				data-allow_clear="true" tabindex="-1" aria-hidden="true" name="cwginstocksettings[specific_products][]"
				multiple="multiple" class="wc-product-search">
				<?php
				$current_v = isset( $options['specific_products'] ) ? $options['specific_products'] : '';
				if ( is_array( $current_v ) && ! empty( $current_v ) ) {
					foreach ( $current_v as $each_id ) {
						$product = wc_get_product( $each_id );
						if ( $product ) {
							printf( '<option value="%s"%s>%s</option>', intval( $each_id ), ' selected="selected"', wp_kses_post( $product->get_formatted_name() ) );
						}
					}
				}
				?>
			</select>
			<label><input type="radio" name="cwginstocksettings[specific_products_visibility]" <?php isset( $options['specific_products_visibility'] ) ? checked( $options['specific_products_visibility'], 1 ) : ''; ?>
					value="1" />
				<?php esc_html_e( 'Show', 'back-in-stock-notifier-for-woocommerce' ); ?>
			</label>
			<label><input type="radio" name="cwginstocksettings[specific_products_visibility]" <?php isset( $options['specific_products_visibility'] ) ? checked( $options['specific_products_visibility'], 2 ) : ''; ?>
					value="2" />
				<?php esc_html_e( 'Hide', 'back-in-stock-notifier-for-woocommerce' ); ?>
			</label>
			<p><i>
					<?php esc_html_e( 'By Default this field will empty means subscribe form will shown to all out of stock products by default', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function visibility_for_specific_categories() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<select style="width:320px;"
				data-placeholder="<?php esc_html_e( 'Select Categories', 'back-in-stock-notifier-for-woocommerce' ); ?>"
				data-allow_clear="true" name="cwginstocksettings[specific_categories][]" multiple="multiple"
				class="wc-category-search">
				<?php
				$current_v = isset( $options['specific_categories'] ) ? $options['specific_categories'] : '';
				if ( is_array( $current_v ) && ! empty( $current_v ) ) {
					foreach ( $current_v as $each_slug ) {
						$current_category = $each_slug ? get_term_by( 'slug', $each_slug, 'product_cat' ) : false;
						if ( $current_category ) {
							printf( '<option value="%s"%s>%s</option>', esc_attr( $each_slug ), ' selected="selected"', esc_attr( $current_category->name . '(' . $current_category->count . ')' ) );
						}
					}
				}
				?>
			</select>
			<label><input type="radio" name="cwginstocksettings[specific_categories_visibility]" <?php isset( $options['specific_categories_visibility'] ) ? checked( $options['specific_categories_visibility'], 1 ) : ''; ?>
					value="1" />
				<?php esc_html_e( 'Show', 'back-in-stock-notifier-for-woocommerce' ); ?>
			</label>
			<label><input type="radio" name="cwginstocksettings[specific_categories_visibility]" <?php isset( $options['specific_categories_visibility'] ) ? checked( $options['specific_categories_visibility'], 2 ) : ''; ?>
					value="2" />
				<?php esc_html_e( 'Hide', 'back-in-stock-notifier-for-woocommerce' ); ?>
			</label>
			<p><i>
					<?php esc_html_e( 'By Default this field will empty means subscribe form will shown to all out of stock products by default', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function visibility_for_specific_tags() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<select style="width:320px;"
				data-placeholder="<?php esc_html_e( 'Select Product Tags', 'back-in-stock-notifier-for-woocommerce' ); ?>"
				data-allow_clear="true" name="cwginstocksettings[specific_tags][]" multiple="multiple" class="wc-tag-search">
				<?php
				$current_v = isset( $options['specific_tags'] ) ? $options['specific_tags'] : '';
				if ( is_array( $current_v ) && ! empty( $current_v ) ) {
					foreach ( $current_v as $each_slug ) {
						$current_category = $each_slug ? get_term_by( 'slug', $each_slug, 'product_tag' ) : false;
						if ( $current_category ) {
							printf( '<option value="%s"%s>%s</option>', esc_attr( $each_slug ), ' selected="selected"', esc_attr( $current_category->name . '(' . $current_category->count . ')' ) );
						}
					}
				}
				?>
			</select>
			<label><input type="radio" name="cwginstocksettings[specific_tags_visibility]" <?php isset( $options['specific_tags_visibility'] ) ? checked( $options['specific_tags_visibility'], 1 ) : ''; ?>
					value="1" />
				<?php esc_html_e( 'Show', 'back-in-stock-notifier-for-woocommerce' ); ?>
			</label>
			<label><input type="radio" name="cwginstocksettings[specific_tags_visibility]" <?php isset( $options['specific_tags_visibility'] ) ? checked( $options['specific_tags_visibility'], 2 ) : ''; ?>
					value="2" />
				<?php esc_html_e( 'Hide', 'back-in-stock-notifier-for-woocommerce' ); ?>
			</label>
			<p><i>
					<?php esc_html_e( 'By Default this field will empty means subscribe form will shown to all out of stock products by default', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function visibility_settings_for_product_on_sale() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[hide_on_sale]' <?php isset( $options['hide_on_sale'] ) ? checked( $options['hide_on_sale'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Hide Subscribe Form on Sale Products out of stock', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function disabled_variation_settings_option() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<p>
				<?php
				esc_html_e(
					'Some themes disable variation out of stock by default and some by an option, when activate our plugin it overwrite
                theme configuration(disabled variation become selectable), so by enable this option our plugin settings will not
                overwrite theme configuration',
					'back-in-stock-notifier-for-woocommerce'
				);
				?>
			</p>
			<input type='checkbox' name='cwginstocksettings[ignore_disabled_variation]' <?php isset( $options['ignore_disabled_variation'] ) ? checked( $options['ignore_disabled_variation'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Enable this option to not overwrite disabled out of stock variation settings from themes(some themes)', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function ignore_settings_for_wc_out_of_stock_visibility() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<p>
				<?php
				esc_html_e(
					'WooCommerce has an option to hide out of stock products from catalog(WooCommerce->Products->Inventory->Out of stock
                visibililty),when you enable/enabled this option will hide out of stock products from shop page/category page, but
                this also hide out of stock variations from variation dropdown, for that we provide option to ignore that
                woocommerce out of stock visibility settings only for variable products',
					'back-in-stock-notifier-for-woocommerce'
				);
				?>

			</p>
			<input type='checkbox' name='cwginstocksettings[ignore_wc_visibility]' <?php isset( $options['ignore_wc_visibility'] ) ? checked( $options['ignore_wc_visibility'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Enable this option to ignore WooCommerce Out of stock Visibility Settings for Variations', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function visibility_settings_for_product_on_regular() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[hide_on_regular]' <?php isset( $options['hide_on_regular'] ) ? checked( $options['hide_on_regular'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Hide Subscribe Form on Regular Products out of stock', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function error_section_heading() {
			esc_html_e( 'Customize Error Message and its Visibility', 'back-in-stock-notifier-for-woocommerce' );
		}

		public function empty_name_fields() {
			$options = get_option( 'cwginstocksettings' );
			$option_value = isset( $options['empty_name_message'] ) ? $options['empty_name_message'] : __( 'Name cannot be empty', 'back-in-stock-notifier-for-woocommerce' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[empty_name_message]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $option_value ) ); ?>" />
			<?php
		}

		public function empty_email_address() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[empty_error_message]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $options['empty_error_message'] ) ); ?>" />
			<?php
		}

		public function invalid_email_address() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[invalid_email_error]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $options['invalid_email_error'] ) ); ?>" />
			<?php
		}

		public function invalid_phone_number() {
			$options = get_option( 'cwginstocksettings' );
			$invalid_phone_number = isset( $options['invalid_phone_error'] ) ? $options['invalid_phone_error'] : esc_html__( 'Please enter valid Phone Number', 'back-in-stock-notifier-for-woocommerce' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[invalid_phone_error]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $invalid_phone_number ) ); ?>" />
			<?php
		}

		public function phone_number_too_short() {
			$options = get_option( 'cwginstocksettings' );
			$phone_number_too_short = isset( $options['phone_number_too_short'] ) ? $options['phone_number_too_short'] : esc_html__( 'Phone Number too short', 'back-in-stock-notifier-for-woocommerce' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[phone_number_too_short]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $phone_number_too_short ) ); ?>" />
			<?php
		}

		public function phone_number_too_long() {
			$options = get_option( 'cwginstocksettings' );
			$phone_number_too_long = isset( $options['phone_number_too_long'] ) ? $options['phone_number_too_long'] : esc_html__( 'Phone Number too long', 'back-in-stock-notifier-for-woocommerce' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[phone_number_too_long]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $phone_number_too_long ) ); ?>" />
			<?php
		}

		public function mail_settings_heading() {
			esc_html_e( 'Customize Email Message and its corresponding settings', 'back-in-stock-notifier-for-woocommerce' );
			echo '<br> Available Shortcodes to be used for subject and message <br>';
			echo '<strong>{product_name}, {product_id}, {product_link}, {shopname}, {email_id}, {subscriber_email}, {subscriber_name}, {subscriber_firstname}, {subscriber_lastname}, {subscriber_phone}, {cart_link}, {only_product_name}, {only_product_sku}, {product_price}, {product_image}</strong>';
			echo '<br> If you want to show the image with specified size then you can try something like this one <strong>{product_image=thumbnail}</strong>, (you can pass parameter like <strong>thumbnail/medium/large</strong>) it also accept any custom width and height by pass something like this one <strong>{product_image=100x100}</strong> (widthxheight)';
			echo "<br> <strong> When you use {product_link} or {cart_link} make sure you add anchor tag(some email client shows as plain text instead of hyperlink) <pre>&lt;a href='{product_link}'&gt;{product_name}&lt;/a&gt; </pre><pre>&lt;a href='{cart_link}'&gt;{cart_link}&lt;/a&gt;</pre> </strong>";
		}

		public function success_subscription_mail() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[enable_success_sub_mail]' <?php isset( $options['enable_success_sub_mail'] ) ? checked( $options['enable_success_sub_mail'], 1 ) : ''; ?> value="1" />
			<?php
		}

		public function enable_copy_subscription() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type="checkbox" name="cwginstocksettings[enable_copy_subscription]" <?php isset( $options['enable_copy_subscription'] ) ? checked( $options['enable_copy_subscription'], 1 ) : ''; ?> value='1' />
			<?php
			echo esc_html( __( 'For Example: If admin/shop owner want to receive email copy of subcribers then enable this option followed by enter their email ids', 'back-in-stock-notifier-for-woocommerce' ) );
		}

		public function success_subscription_mail_subject() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[success_sub_subject]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $options['success_sub_subject'] ) ); ?>" />
			<?php
		}

		public function success_subscription_mail_message() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<textarea rows="15" cols="50"
				name="cwginstocksettings[success_sub_message]"><?php echo wp_kses_post( $this->api->sanitize_textarea_field( $options['success_sub_message'] ) ); ?></textarea>
			<?php
		}

		public function subscription_copy_recipients() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<textarea rows='15' cols='50'
				name='cwginstocksettings[subscription_copy_recipients]'><?php echo wp_kses_post( isset( $options['subscription_copy_recipients'] ) ? $options['subscription_copy_recipients'] : '' ); ?></textarea>
			<?php
		}

		public function enable_instock_mail() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[enable_instock_mail]' <?php isset( $options['enable_instock_mail'] ) ? checked( $options['enable_instock_mail'], 1 ) : ''; ?> value="1" />
			<?php
		}

		public function enable_instock_mail_for_product_status() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[enable_instock_mail_for_product_status]' <?php isset( $options['enable_instock_mail_for_product_status'] ) ? checked( $options['enable_instock_mail_for_product_status'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'By enable this option, instock email will be send to the published product. Status with private/draft product status will not be considered.', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function instock_mail_subject() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[instock_mail_subject]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $options['instock_mail_subject'] ) ); ?>" />
			<?php
		}

		public function instock_mail_message() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<textarea rows="15" cols="50"
				name="cwginstocksettings[instock_mail_message]"><?php echo wp_kses_post( $this->api->sanitize_textarea_field( $options['instock_mail_message'] ) ); ?></textarea>
			<?php
		}

		public function instock_mail_message_set_stock_quantity() {
			$options = get_option( 'cwginstocksettings' );
			$get_option_value_user = isset( $options['set_stock_quantity_for_instock_mail'] ) && $options['set_stock_quantity_for_instock_mail'] > 0 ? $options['set_stock_quantity_for_instock_mail'] : 0;
			?>
			<input type='number' style='width: 400px;' name='cwginstocksettings[set_stock_quantity_for_instock_mail]'
				value="<?php echo wp_kses_post( $get_option_value_user ); ?>" step="any" />
			<i>
				<p>
					<?php
					esc_html_e(
						'Using this option Instock Email trigger can be controllable, when you manage product stock by quantity. For Ex:
                    If you set 5 in this option, you have to update product stock more than or equal to 5 in product stock quantity
                    in order to trigger instock email',
						'back-in-stock-notifier-for-woocommerce'
					)
					?>
				</p>
			</i>
			<?php
		}

		public function enable_post_status_subscribed() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[keep_status_subscribed]' <?php isset( $options['keep_status_subscribed'] ) ? checked( $options['keep_status_subscribed'], 1 ) : ''; ?> value="1" />
			<span
				style="color: red;display: block; margin-top: 5px;"><?php esc_html_e( 'Not recommended', 'back-in-stock-notifier-for-woocommerce' ); ?></span>

			<i>
				<p><?php esc_html_e( 'Using this option prevents subscribers from having to repeatedly sign up for notifications when a product becomes available in limited quantities after going out of stock. For instance, if there are 100 subscribers signed up for notifications and only 10 units are expected to be restocked at a time, enabling this option ensures that those subscribers won\'t need to re-register for notifications each time the product is restocked', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</p>
				<p><?php esc_html_e( 'Note: Enabling this option repeatedly sends instock notifications whenever a product becomes available until the subscribed product is "purchased" or "unsubscribed."', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</p>
			</i>
			<?php
		}

		public function background_process_heading() {
			esc_html_e( 'Please select background process engine, this is important to send a mail in background by default it is WP Background Process and you can also choose WooCommerce Background Process', 'back-in-stock-notifier-for-woocommerce' );
		}

		public function bgp_engine() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<select name="cwginstocksettings[bgp_engine]" style="width:400px;">
				<option value="wcbgp" <?php echo isset( $options['bgp_engine'] ) && 'wcbgp' == $options['bgp_engine'] ? 'selected=selected' : ''; ?>>
					<?php esc_html_e( 'WooCommerce Background Process', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
				<option value="wpbgp" <?php echo isset( $options['bgp_engine'] ) && 'wpbgp' == $options['bgp_engine'] ? 'selected=selected' : ''; ?>>
					<?php esc_html_e( 'Default Background Process', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
			</select>
			<?php
		}

		public function success_subscription_message() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[success_subscription]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $options['success_subscription'] ) ); ?>" />
			<i>
				<p>
					<?php esc_html_e( 'Supported Shortcodes {product_name}, {only_product_name}', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</p>


			</i>
			<?php
		}

		public function email_already_subscribed() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[already_subscribed]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $options['already_subscribed'] ) ); ?>" />
			<i>
				<p> <?php esc_html_e( 'Supported Shortcodes {product_name}, {only_product_name}', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</p>
			</i>
			<?php
		}

		public function default_value() {
			// delete_option('cwginstocksettings');
			$success_subscribe_message = 'Dear {subscriber_name}, <br/>'
				. 'Thank you for subscribing to the #{product_name}. We will email you once product back in stock';
			$instock_message = 'Hello {subscriber_name}, <br/>'
				. "Thanks for your patience and finally the wait is over! <br/> Your Subscribed Product {product_name} is now back in stock! We only have a limited amount of stock, and this email is not a guarantee you'll get one, so hurry to be one of the lucky shoppers who do <br/> Add this product {product_name} directly to your cart <a href='{cart_link}'>{cart_link}</a>";
			/**
			 * Filter for modifying the array of default values
			 *
			 * @since 1.0.0
			 */
			$data = apply_filters(
				'cwginstock_default_values',
				array(
					'form_title' => 'Email when stock available',
					'name_placeholder' => 'Your Name',
					'form_placeholder' => 'Your Email Address',
					'button_label' => 'Subscribe Now',
					'empty_error_message' => 'Email Address cannot be empty',
					'invalid_email_error' => 'Please enter valid Email Address',
					'enable_success_sub_mail' => '1',
					'success_sub_subject' => 'You subscribed to {product_name} at {shopname}',
					'success_sub_message' => $success_subscribe_message,
					'enable_instock_mail' => '1',
					'instock_mail_subject' => 'Product {product_name} is back in stock',
					'instock_mail_message' => $instock_message,
					'success_subscription' => 'You have successfully subscribed, we will inform you when this product back in stock',
					'already_subscribed' => 'Seems like you have already subscribed to this product',
					'empty_name_message' => 'Name cannot be empty',
					'invalid_phone_error' => 'Please enter valid Phone Number',
					'phone_number_too_short' => 'Phone number is too short',
					'phone_number_too_long' => 'Phone number is too long',
				)
			);

			if ( is_array( $data ) && ! empty( $data ) ) {
				add_option( 'cwginstocksettings', $data );
			}
			$get_data = get_option( 'cwginstocksettings' );

			if ( ! isset( $get_data['specific_categories_visibility'] ) ) {
				$get_data['specific_categories_visibility'] = '1';
				$get_data['specific_products_visibility'] = '1';
				update_option( 'cwginstocksettings', $get_data );
			}

			$get_data = get_option( 'cwginstocksettings' );
			if ( ! isset( $get_data['specific_tags_visibility'] ) ) {
				$get_data['specific_tags_visibility'] = '1';
				update_option( 'cwginstocksettings', $get_data );
			}
			/**
			 * Action related to default settings
			 *
			 * @since 1.0.0
			 */
			do_action( 'cwginstock_settings_default' );
		}

		public function sanitize_data( $input ) {
			/**
			 * Filter for textarea fields
			 *
			 * @since 1.0.0
			 */
			$textarea_field = apply_filters( 'cwg_instock_textarea_fields', array( 'instock_mail_message', 'success_sub_message', 'hide_form_for_guest_msg' ) );
			if ( is_array( $input ) && ! empty( $input ) ) {
				foreach ( $input as $key => $value ) {
					if ( ! is_array( $value ) ) {
						if ( in_array( $key, $textarea_field ) ) {
							$input[ $key ] = $this->api->sanitize_textarea_field( $value );
						} else {
							$input[ $key ] = $this->api->sanitize_text_field( $value );
						}
					}
				}
			}

			return $input;
		}

	}

	new CWG_Instock_Settings();
}
