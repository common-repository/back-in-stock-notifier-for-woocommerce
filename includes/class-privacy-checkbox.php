<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_Privacy_Checkbox' ) ) {

	class CWG_Instock_Privacy_Checkbox {

		private $api;

		public function __construct() {
			$settings = get_option( 'cwginstock_iagree_settings' );
			$is_enable = isset( $settings['enable_iagree'] ) ? $settings['enable_iagree'] : false;
			// register settings
			add_action( 'cwginstock_register_settings', array( $this, 'register_settings' ), 210 );
			add_action( 'cwginstock_settings_default', array( $this, 'default_values' ) );
			if ( $is_enable ) {
				add_action( 'cwg_instock_after_email_field', array( $this, 'show_iagree_frontend' ), 20, 2 );
				add_filter( 'cwginstock_localization_array', array( $this, 'add_localize_data' ), 20 );
			}
			$this->api = new CWG_Instock_API();
		}

		public function register_settings() {
			register_setting( 'cwginstocknotifier_settings', 'cwginstock_iagree_settings', array( $this, 'sanitize_data' ) );
			add_settings_section( 'cwginstock_section_iagree', __( 'I Agree Checkbox in Subscribe Form', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'iagree_settings_heading' ), 'cwginstocknotifier_settings' );
			add_settings_field( 'cwg_instock_enable_iagree', __( 'Enable I Agree in Subscribe Form', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'enable_iagree' ), 'cwginstocknotifier_settings', 'cwginstock_section_iagree' );
			add_settings_field( 'cwg_instock_iagree_message', __( 'Text for I Agree - this will appear next to the checkbox frontend', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'iagree_text' ), 'cwginstocknotifier_settings', 'cwginstock_section_iagree' );
			add_settings_field( 'cwg_instock_iagree_error', __( 'I Agree Error Message', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'iagree_error' ), 'cwginstocknotifier_settings', 'cwginstock_section_iagree' );
		}

		public function iagree_settings_heading() {
			esc_html_e( 'I Agree Checkbox(GDPR Compliance) in Subscribe Form Frontend', 'back-in-stock-notifier-for-woocommerce' );
		}

		public function enable_iagree() {
			$options = get_option( 'cwginstock_iagree_settings' );
			?>
			<input type='checkbox' name='cwginstock_iagree_settings[enable_iagree]' <?php isset( $options['enable_iagree'] ) ? checked( $options['enable_iagree'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Select this option to enable I Agree Checkbox in Subscribe Form(Ex: I Agree to the terms and privacy policy)', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function iagree_text() {
			$options = get_option( 'cwginstock_iagree_settings' );
			?>
			<textarea rows="5" cols="50"
				name="cwginstock_iagree_settings[iagree_text]"><?php echo do_shortcode( $this->api->sanitize_textarea_field( $options['iagree_text'] ) ); ?></textarea>
			<?php
		}

		public function iagree_error() {
			$options = get_option( 'cwginstock_iagree_settings' );
			?>
			<textarea rows="5" cols="50"
				name="cwginstock_iagree_settings[iagree_error]"><?php echo do_shortcode( $this->api->sanitize_textarea_field( $options['iagree_error'] ) ); ?></textarea>
			<?php
		}

		public function show_iagree_frontend( $product_id, $variation_id ) {
			$get_options = get_option( 'cwginstock_iagree_settings' );
			$is_enable = isset( $get_options['enable_iagree'] ) ? $get_options['enable_iagree'] : false;
			$get_text = isset( $get_options['iagree_text'] ) && '' != $get_options['iagree_text'] ? $get_options['iagree_text'] : false;
			if ( $is_enable && $get_text ) {
				?>
				<div class="cwg_iagree_checkbox"> <label for="cwg_iagree_checkbox_input"> <input type="checkbox"
							id="cwg_iagree_checkbox_input" class="cwg_iagree_checkbox_input" value="1" name="cwg_iagree_checkbox_input">
						<?php echo do_shortcode( $get_text ); ?>
					</label> </div>
				<?php
			}
		}

		public function default_values() {
			// delete_option('cwginstock_iagree_settings');
			$get_option = get_option( 'cwginstock_iagree_settings', array() );
			$privacy_url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '#';
			$default_text = "I Agree to the <a href='#'>terms</a> and <a href='$privacy_url'>privacy policy</a>";
			$get_option['iagree_text'] = $default_text;
			$get_option['iagree_error'] = 'Please accept our terms and privacy policy';
			add_option( 'cwginstock_iagree_settings', $get_option );
		}

		public function add_localize_data( $already_loaded ) {
			$get_option = get_option( 'cwginstock_iagree_settings' );
			$already_loaded['is_iagree_enable'] = isset( $get_option['enable_iagree'] ) ? '1' : '2';
			$already_loaded['iagree_error'] = $get_option['iagree_error'];
			return $already_loaded;
		}

		public function sanitize_data( $input ) {
			$textarea_field = array( 'iagree_error', 'iagree_text' );
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

	$instock_privacy = new CWG_Instock_Privacy_Checkbox();
}
