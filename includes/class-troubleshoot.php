<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_Troubleshoot' ) ) {

	class CWG_Instock_Troubleshoot {

		public function __construct() {
			add_action( 'cwginstock_register_settings', array( $this, 'add_settings_field' ), 999 );
		}

		public function add_settings_field() {
			add_settings_section( 'cwginstock_section_troubleshoot', __( 'Troubleshoot Settings (Experimental)', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'troubleshoot_settings_heading' ), 'cwginstocknotifier_settings' );
			add_settings_field( 'cwg_instock_subscriptionform_submission', __( 'Frontend Subscribe Form Submission via', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'submit_subscriptionform_via' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_enable_troubleshoot', __( 'Enable if Subscribe Form Layout Problem/Input Field Overlap', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'enable_troubleshoot' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_enable_button_troubleshoot', __( 'Additional Class Name for Subscribe Button(seperated by commas)', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'enable_button_for_class' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_hide_subscribecount', __( 'Hide Subscriber Count(Admin Side)', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'hide_subscribercount' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_stock_updade_from_thirdparty', __( 'Enable this option if you have updated the stock from a third-party inventory plugin', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'update_stock_third_party' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_remove_view_subscriber_count', __( 'Remove View Subscribers Link in Product List Table(Admin Dashboard -> Products)', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'remove_view_subscriber_count_producttable' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_trigger_mail_any_variation', __( 'Trigger mail to variable product subscribers when any other variation of that product is back in stock', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'trigger_any_variation_variable_backinstock' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_override_form_from_theme', __( 'Force load Template from Plugin - This option ignores the template override from theme', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'load_template_from_plugin' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_enable_cache_buster', __( 'Enable Cache Buster', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'cache_buster' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_show_subscribers_count_column', __( 'Show Subscribers Count', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'show_subscribers_count_column' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_stop_email_staging', __( 'Enable Stop Sending Email from staging site environment', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'stop_sending_email_staging' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_stop_email_staging_domain', __( 'Enter your staging site starting domain', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'stop_sending_email_staging_domain' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
			add_settings_field( 'cwg_instock_disable_prefill_data', __( 'Disable Prefilled Data for Logged-in Users', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'stop_prefilled_data' ), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot' );
		}

		public function troubleshoot_settings_heading() {
			$troubleshoot_heading = __( 'If frontend Subscribe Form layout breaks/input field overlap? then enable below checkbox option to troubleshoot this issue. If it is not work out then please open a support ticket with us https://codewoogeek.online', 'back-in-stock-notifier-for-woocommerce' );
			echo do_shortcode( $troubleshoot_heading );
		}

		public function submit_subscriptionform_via() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<select name="cwginstocksettings[ajax_submission_via]">
				<option value="wordpress_ajax_default" <?php echo isset( $options['ajax_submission_via'] ) && 'wordpress_ajax_default' == $options['ajax_submission_via'] ? 'selected=selected' : ''; ?>>
					<?php esc_html_e( 'Wordpress AJAX(default)', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
				<option value="wordpress_rest_api_route" <?php echo isset( $options['ajax_submission_via'] ) && 'wordpress_rest_api_route' == $options['ajax_submission_via'] ? 'selected=selected' : ''; ?>>
					<?php esc_html_e( 'Wordpress REST API Route', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</option>
			</select>
			<?php
		}

		public function enable_troubleshoot() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[enable_troubleshoot]' <?php isset( $options['enable_troubleshoot'] ) ? checked( $options['enable_troubleshoot'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Select this option only if the subscribe form layout breaks in frontend(experimental)', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function enable_button_for_class() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<textarea rows='15' cols='50'
				name='cwginstocksettings[btn_class]'><?php echo wp_kses_post( isset( $options['btn_class'] ) ? $options['btn_class'] : '' ); ?></textarea>
			<?php
		}

		public function hide_subscribercount() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[hide_subscribercount]' <?php isset( $options['hide_subscribercount'] ) ? checked( $options['hide_subscribercount'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Select this option to hide subscriber count appeared in the admin menu', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function update_stock_third_party() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[update_stock_third_party]' <?php isset( $options['update_stock_third_party'] ) ? checked( $options['update_stock_third_party'], 1 ) : ''; ?> value="1" />
			<?php
		}

		public function remove_view_subscriber_count_producttable() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[remove_view_subscriber_count]' <?php isset( $options['remove_view_subscriber_count'] ) ? checked( $options['remove_view_subscriber_count'], 1 ) : ''; ?>
				value="1" />
			<?php
		}

		public function trigger_any_variation_variable_backinstock() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[variable_any_variation_backinstock]' <?php isset( $options['variable_any_variation_backinstock'] ) ? checked( $options['variable_any_variation_backinstock'], 1 ) : ''; ?> value="1" />
			<?php
		}

		public function load_template_from_plugin() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[template_from_plugin]' <?php isset( $options['template_from_plugin'] ) ? checked( $options['template_from_plugin'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Select this option to ignore the loading of subscribe form template from theme', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function cache_buster() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[cache_buster]' <?php isset( $options['cache_buster'] ) ? checked( $options['cache_buster'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Select this option to add a cache buster to the "add-to-cart" link in the in-stock email', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function show_subscribers_count_column() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[show_subscribers_count_column]' <?php isset( $options['show_subscribers_count_column'] ) ? checked( $options['show_subscribers_count_column'], 1 ) : ''; ?>
				value="1" />
			<p><i>
					<?php esc_html_e( 'Select this option to show "Subscribers Count" column in product list table (Admin Dashboard>Products) ', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		public function stop_sending_email_staging() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' class='stop_sending_email_staging' name='cwginstocksettings[stop_sending_email_staging]' <?php isset( $options['stop_sending_email_staging'] ) ? checked( $options['stop_sending_email_staging'], 1 ) : ''; ?>
				value="1" />
			<p><i><?php esc_html_e( 'Enable this option to disable email notifications when sites are accessed on staging domains that mirror the production site. This prevents unnecessary alerts from a testing environment, ensuring the production email flow remains unaffected', 'back-in-stock-notifier-for-woocommerce' ); ?></i>
			</p>
			<?php
		}

		public function stop_sending_email_staging_domain() {
			$options = get_option( 'cwginstocksettings' );
			$staging_domains = isset( $options['staging_domains'] ) ? $options['staging_domains'] : '';
			?>
			<textarea class="staging_domains" name="cwginstocksettings[staging_domains]" rows="4"
				cols="50"><?php echo esc_html( $staging_domains ); ?></textarea>
			<p><i><?php esc_html_e( 'Enter your starting domain(s) of the staging environment. This option is comma-separated (e.g., "dev.", "test.", "staging.", "demo", etc.)', 'back-in-stock-notifier-for-woocommerce' ); ?></i>
			</p>
			<?php
		}

		public function stop_prefilled_data() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' name='cwginstocksettings[disable_prefill_data]' <?php isset( $options['disable_prefill_data'] ) ? checked( $options['disable_prefill_data'], 1 ) : ''; ?> value="1" />
			<p><i><?php esc_html_e( 'Enable this option to prevent the subscription form from auto-filling the name and email fields for logged-in users.', 'back-in-stock-notifier-for-woocommerce' ); ?></i>
			</p>
			<?php
		}

	}

	new CWG_Instock_Troubleshoot();
}
