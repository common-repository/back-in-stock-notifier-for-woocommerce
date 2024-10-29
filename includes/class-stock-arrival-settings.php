<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_Estimate_Stock_Settings' ) ) {

	class CWG_Instock_Estimate_Stock_Settings {

		public function __construct() {
			add_action( 'cwginstock_register_settings', array( $this, 'add_settings_field' ), 250 );
		}

		public function add_settings_field() {
			add_settings_section( 'cwginstock_section_est_stock', __( 'Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'est_stock_settings_heading' ), 'cwginstocknotifier_settings' );
			add_settings_field( 'cwg_instock_enable_estimate_stock', __( 'Enable Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'enable_estimate_stock' ), 'cwginstocknotifier_settings', 'cwginstock_section_est_stock' );
			add_settings_field( 'cwg_instock_est_rule_priority', __( 'Rule Priority', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'est_rule_priority' ), 'cwginstocknotifier_settings', 'cwginstock_section_est_stock' );
			add_settings_field( 'cwg_instock_display_message_place', __( 'Display Message Place', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'display_message_place' ), 'cwginstocknotifier_settings', 'cwginstock_section_est_stock' );
		}

		public function est_stock_settings_heading() {
			echo '<p>' . esc_html__( 'Configure the settings for estimating stock arrival.', 'back-in-stock-notifier-for-woocommerce' ) . '</p>';
		}

		public function enable_estimate_stock() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[enable_est_stock_arrival]' <?php checked( isset( $options['enable_est_stock_arrival'] ) ? $options['enable_est_stock_arrival'] : 0, 1 ); ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Select this option to enable Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function est_rule_priority() {
			$options = get_option( 'cwginstocksettings' );
			$current_priority = isset( $options['est_rule_priority'] ) ? $options['est_rule_priority'] : 'first';
			?>
			<label>
				<input type="radio" name="cwginstocksettings[est_rule_priority]" value="first" <?php checked( $current_priority, 'first' ); ?> />
				<?php esc_html_e( 'First Matched Rule', 'back-in-stock-notifier-for-woocommerce' ); ?>
			</label><br />
			<label>
				<input type="radio" name="cwginstocksettings[est_rule_priority]" value="last" <?php checked( $current_priority, 'last' ); ?> />
				<?php esc_html_e( 'Last Matched Rule', 'back-in-stock-notifier-for-woocommerce' ); ?>
			</label>
			<p><i>
					<?php esc_html_e( 'If more than one rule matched for the same product then this rule priority will apply', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function display_message_place() {
			$options = get_option( 'cwginstocksettings' );
			$display_message_place = isset( $options['display_message_place'] ) ? $options['display_message_place'] : '';
			?>
			<select name="cwginstocksettings[display_message_place]">
				<option value="cwg_instock_before_heading" <?php selected( $display_message_place, 'cwg_instock_before_heading' ); ?>>
					<?php esc_html_e( 'Before Heading', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
				<option value="cwg_instock_after_heading" <?php selected( $display_message_place, 'cwg_instock_after_heading' ); ?>>
					<?php esc_html_e( 'After Heading', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
				<option value="cwg_instock_before_input_fields" <?php selected( $display_message_place, 'cwg_instock_before_input_fields' ); ?>>
					<?php esc_html_e( 'Before Input Field', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
				<option value="cwg_instock_after_input_fields" <?php selected( $display_message_place, 'cwg_instock_after_input_fields' ); ?>>
					<?php esc_html_e( 'After Input Field', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
				<option value="cwginstock_before_submit_button" <?php selected( $display_message_place, 'cwginstock_before_submit_button' ); ?>>
					<?php esc_html_e( 'Before Submit Button', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
				<option value="cwginstock_after_submit_button" <?php selected( $display_message_place, 'cwginstock_after_submit_button' ); ?>>
					<?php esc_html_e( 'After Submit Button', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
			</select>
			<p><i>
					<?php esc_html_e( 'Select the place to display the estimate stock arrival message in the subscription form on the frontend', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}


	}

	new CWG_Instock_Estimate_Stock_Settings();
}
