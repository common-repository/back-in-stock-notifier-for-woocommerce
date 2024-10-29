<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Copy_Mailer' ) ) {

	class CWG_Copy_Mailer {

		private $api;

		public function __construct() {
			add_action( 'cwginstock_register_settings', array( $this, 'register_settings' ) );
			add_action( 'cwginstock_settings_default', array( $this, 'default_values' ) );
			$this->api = new CWG_Instock_API();
		}

		public function register_settings() {
			register_setting( 'cwginstocknotifier_settings', 'cwginstock_imail_settings', array( $this, 'sanitize_data' ) );
			add_settings_field( 'cwg_instock_copy_subscription_subject', __( 'Copy Subscription Mail Subject', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'copy_subscription_mail_subject' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );
			add_settings_field( 'cwg_instock_copy_subscription_message', __( 'Copy Subscription Mail Message', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'copy_subscription_mail_message' ), 'cwginstocknotifier_settings', 'cwginstock_section_mail' );
		}

		public function copy_subscription_mail_subject() {
			$options = get_option( 'cwginstock_imail_settings' );
			$copy_sub_subject = isset( $options['copy_sub_subject'] ) ? $options['copy_sub_subject'] : __( '{subscriber_name} has subscribed to {product_name}', 'back-in-stock-notifier-for-woocommerce' );
			?>
			<input type='text' style='width: 400px;' name='cwginstock_imail_settings[copy_sub_subject]'
				value="<?php echo esc_attr( $this->api->sanitize_text_field( $copy_sub_subject ) ); ?>" />
			<?php
		}

		public function copy_subscription_mail_message() {
			$options = get_option( 'cwginstock_imail_settings' );
			$copy_sub_message = isset( $options['copy_sub_message'] ) ? $options['copy_sub_message'] : __( 'Subscription Info:<br> {subscriber_name} <br> {subscriber_email} <br> {product_name}', 'back-in-stock-notifier-for-woocommerce' );
			?>
			<textarea rows="5" cols="50"
				name="cwginstock_imail_settings[copy_sub_message]"><?php echo esc_textarea( $this->api->sanitize_textarea_field( $copy_sub_message ) ); ?></textarea>
			<?php
		}

		public function default_values() {
			$get_option = (array) get_option( 'cwginstock_imail_settings', array() );
			$get_option['copy_sub_subject'] = '{subscriber_name} has subscribed to {product_name}';
			$get_option['copy_sub_message'] = 'Subscription Info:<br> {subscriber_name} <br> {subscriber_email} <br> {product_name}';
			add_option( 'cwginstock_imail_settings', $get_option );
		}

		public function sanitize_data( $input ) {
			$textarea_field = array( 'copy_sub_message' );
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

	new CWG_Copy_Mailer();
}
