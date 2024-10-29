<?php

/*
 *
 * Plugin Name: Back In Stock Notifier for WooCommerce | WooCommerce Waitlist Pro
 * Plugin URI: https://codewoogeek.online/shop/free-plugins/back-in-stock-notifier/
 * Description: Notify subscribed users when products back in stock
 * Version: 5.6.1
 * Author: codewoogeek
 * Requires Plugins: woocommerce
 * Author URI: https://codewoogeek.online
 * Text Domain: back-in-stock-notifier-for-woocommerce
 * Domain Path: /languages
 * WC requires at least: 2.2.0
 * WC tested up to: 9.3.3
 * @package     back-in-stock-notifier-for-woocommerce
 * @author      codewoogeek
 * @copyright   2024 CodeWooGeek, LLC
 * @license     GPL-3.0+
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * @icons used from https://www.flaticon.com/authors/roundicons
 */

if (!defined('ABSPATH')) {
	exit; // avoid direct access to the file
}

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if (isset($_GET['post_type']) && ( ( 'cwginstocknotifier' == $_GET['post_type'] ) || ( 'cwginstock_arrival' == $_GET['post_type'] ) )) {
	require( 'includes/library/WP_Persistent_Notices.php' );
}
require_once 'includes/library/wp-async-request.php';
require_once 'includes/library/wp-background-process.php';
require_once 'includes/library/stevegrunwell/wp-admin-tabbed-settings-pages/wp-admin-tabbed-settings-pages.php';

if (!class_exists('CWG_Instock_Notifier')) {

	class CWG_Instock_Notifier {

		/**
		 * Plugin Version
		 *
		 * @var string Version
		 */
		public $version = '5.6.1';

		/**
		 * Instance variable
		 *
		 * @var object Instance
		 */
		protected static $_instance = null;

		/**
		 * Return Object
		 *
		 * @see CWG_Instock_Notifier()
		 */
		public static function instance() {
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Return error when this function called as it is private method
		 */
		public function __wakeup() {
			return false;
		}

		/**
		 * Return error when this function called as it is a private method, so clonning will be forbidden
		 */
		private function __clone() {
			
		}

		/**
		 * Construct the class
		 */
		public function __construct() {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$this->avoid_header_sent();
			$this->define_constant();
			$this->initialize();
			$this->include_files();
			add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 999);
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
			add_filter('woocommerce_screen_ids', array($this, 'add_screen_ids_to_woocommerce'));
			add_filter('admin_head', array($this, 'remove_help_tab_context'));
			add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
			add_action('before_woocommerce_init', array($this, 'declare_wc_compatibility'));

			register_deactivation_hook(__FILE__, array($this, 'clear_schedule_events'));
		}

		/**
		 * Avoid Header already sent issue
		 */
		public function avoid_header_sent() {
			ob_start();
		}

		/**
		 * Include necessary files to load
		 */
		public function include_files() {
			include( 'includes/class-template.php' );
			include( 'includes/admin/class-post-type.php' );
			include( 'includes/frontend/class-product.php' );
			include( 'includes/class-ajax.php' );
			include( 'includes/class-core.php' );
			include( 'includes/class-api.php' );
			include( 'includes/admin/class-settings.php' );
			include( 'includes/class-logger.php' );
			include( 'includes/class-privacy.php' );
			include( 'includes/admin/class-extra.php' );
			include( 'includes/class-troubleshoot.php' );
			include( 'includes/class-privacy-checkbox.php' );
			include( 'includes/class-upgrade.php' );
			include( 'includes/class-popup.php' );
			include( 'includes/class-webhook.php' );
			include( 'includes/class-rest-api.php' );
			include( 'includes/abstract-mailer.php' );
			include( 'includes/class-subscription-mail.php' );
			include( 'includes/class-instock-mail.php' );
			include( 'includes/admin/class-status.php' );
			include( 'includes/class-test-mail.php' );
			include( 'includes/class-stock-third-party.php' );
			include( 'includes/class-auto-delete.php' );
			include( 'includes/class-cache-buster.php' );
			include( 'includes/class-copy-mailer.php' );
			include( 'includes/class-quantity-field.php' );
			include( 'includes/class-bot-protection.php' );
			include( 'includes/class-keep-status.php' );
			include( 'includes/class-site-checker.php' );
			include( 'includes/class-stock-arrival.php' );
			include( 'includes/class-stock-arrival-settings.php' );
		}

		/**
		 * Summary of initialize
		 *
		 * @return void
		 */
		public function initialize() {
			require_once( 'includes/class-background-mail-process.php' );
		}

		/**
		 * Summary of define_constant
		 *
		 * @return void
		 */
		public function define_constant() {
			$this->define('CWGINSTOCK_PLUGINURL', plugins_url('/', __FILE__));
			$this->define('CWGINSTOCK_DIRNAME', basename(dirname(__FILE__)));
			$this->define('CWGINSTOCK_FILE', __FILE__);
			$this->define('CWGSTOCKPLUGINBASENAME', plugin_basename(__FILE__));
			$this->define('CWGINSTOCK_PLUGINDIR', plugin_dir_path(__FILE__));
			$this->define('CWGINSTOCK_VERSION', $this->version);
		}

		/**
		 * Summary of define
		 *
		 * @param mixed $name
		 * @param mixed $value
		 * @return void
		 */
		private function define( $name, $value) {
			if (!defined($name)) {
				define($name, $value);
			}
		}

		/**
		 * Summary of check_script_is_already_loaded
		 *
		 * @param mixed $handle
		 * @param mixed $list
		 * @return bool
		 */
		public function check_script_is_already_loaded( $handle, $list = 'enqueued') {
			return wp_script_is($handle, $list);
		}

		/**
		 * Summary of enqueue_scripts
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			$check_already_enqueued = $this->check_script_is_already_loaded('jquery-blockui');
			if (!$check_already_enqueued) {
				wp_register_script('jquery-blockui', CWGINSTOCK_PLUGINURL . 'assets/js/jquery.blockUI.js', array('jquery'), $this->version, true);
			}
			wp_register_script('cwginstock_js', CWGINSTOCK_PLUGINURL . 'assets/js/frontend-dev.min.js', array('jquery', 'jquery-blockui'), $this->version, true);
			wp_register_script('sweetalert2', CWGINSTOCK_PLUGINURL . 'assets/js/sweetalert2.min.js', array('jquery', 'jquery-blockui'), $this->version, true);
			wp_register_script('cwginstock_popup', CWGINSTOCK_PLUGINURL . 'assets/js/cwg-popup.min.js', array('jquery', 'jquery-blockui', 'sweetalert2'), $this->version, true);

			wp_register_style('cwginstock_frontend_css', CWGINSTOCK_PLUGINURL . 'assets/css/frontend.min.css', array(), $this->version, false);
			wp_register_style('cwginstock_frontend_guest', CWGINSTOCK_PLUGINURL . 'assets/css/guest.min.css', array(), $this->version, false);
			wp_register_style('cwginstock_bootstrap', CWGINSTOCK_PLUGINURL . 'assets/css/bootstrap.min.css', array(), $this->version, false);
			$get_option = get_option('cwginstocksettings');

			$check_visibility = isset($get_option['hide_form_guests']) && '' != $get_option['hide_form_guests'] && !is_user_logged_in() ? false : true;
			if ($check_visibility) {
				wp_enqueue_script('jquery');
				wp_enqueue_script('jquery-blockui');
				wp_enqueue_style('cwginstock_frontend_css');
				wp_enqueue_style('cwginstock_bootstrap');
				$phone_field_visibility = isset($get_option['show_phone_field']) && '' != $get_option['show_phone_field'] ? true : false;
				if ($phone_field_visibility) {
					wp_enqueue_style('cwginstock_phone_css', CWGINSTOCK_PLUGINURL . 'assets/css/intlTelInput.min.css', array(), $this->version, false);
					wp_enqueue_script('cwginstock_phone_js', CWGINSTOCK_PLUGINURL . 'assets/js/intlTelInput.min.js', array('jquery', 'jquery-blockui'), $this->version, true);
				}
				$phone_field_optional = isset($get_option['phone_field_optional']) && '' != $get_option['phone_field_optional'] ? true : false;
				$quantity_field_optional = isset($get_option['quantity_field_optional']) && '' != $get_option['quantity_field_optional'] ? true : false;
				$hide_country_placeholder = isset($get_option['hide_country_placeholder']) && '' != $get_option['hide_country_placeholder'] ? true : false;
				$get_empty_name = isset($get_option['empty_name_message']) && '' != $get_option['empty_name_message'] ? $get_option['empty_name_message'] : __('Name cannot be empty', 'back-in-stock-notifier-for-woocommerce');
				$get_empty_quantity = isset($get_option['empty_quantity_message']) && '' != $get_option['empty_quantity_message'] ? $get_option['empty_quantity_message'] : __('Quantity cannot be empty', 'back-in-stock-notifier-for-woocommerce');
				$get_empty_msg = isset($get_option['empty_error_message']) && '' != $get_option['empty_error_message'] ? $get_option['empty_error_message'] : __('Email Address cannot be empty', 'back-in-stock-notifier-for-woocommerce');
				$invalid_msg = isset($get_option['invalid_email_error']) && '' != $get_option['invalid_email_error'] ? $get_option['invalid_email_error'] : __('Please Enter Valid Email Address', 'back-in-stock-notifier-for-woocommerce');
				$form_submission_mode = isset($get_option['ajax_submission_via']) && ( 'wordpress_rest_api_route' == $get_option['ajax_submission_via'] ) ? true : false;
				$is_popup = isset($get_option['mode']) && ( '2' == $get_option['mode'] ) ? 'yes' : 'no';
				$empty_phone_message = isset($get_option['empty_phone_message']) ? $get_option['empty_phone_message'] : esc_html__('Phone Number cannot be empty', 'back-in-stock-notifier-for-woocommerce');
				$invalid_phone_number = isset($get_option['invalid_phone_error']) ? $get_option['invalid_phone_error'] : esc_html__('Please enter valid Phone Number', 'back-in-stock-notifier-for-woocommerce');
				$phone_number_too_short = isset($get_option['phone_number_too_short']) ? $get_option['phone_number_too_short'] : esc_html__('Phone Number too short', 'back-in-stock-notifier-for-woocommerce');
				$phone_number_too_long = isset($get_option['phone_number_too_long']) ? $get_option['phone_number_too_long'] : esc_html__('Phone Number too long', 'back-in-stock-notifier-for-woocommerce');
				$default_country_code = isset($get_option['default_country']) ? $get_option['default_country'] : '';
				$custom_country_placeholder = isset($get_option['default_country_placeholder']) ? $get_option['default_country_placeholder'] : 'default';
				$custom_placehoder_value = isset($get_option['custom_placeholder']) ? $get_option['custom_placeholder'] : '';
				/**
				 * Hook cwginstock localization array
				 *
				 * @since 1.0.0
				 */
				$translation_array = apply_filters(
						'cwginstock_localization_array',
						array(
							'ajax_url' => $form_submission_mode ? rest_url() . 'back-in-stock/v1/subscriber/create/' : admin_url('admin-ajax.php'),
							'default_ajax_url' => admin_url('admin-ajax.php'),
							'security' => $form_submission_mode ? wp_create_nonce('wp_rest') : wp_create_nonce('cwg_subscribe_product'),
							'user_id' => get_current_user_id(),
							'security_error' => __('Something went wrong, please try after sometime', 'cwginstocknotifier'),
							'empty_name' => $get_empty_name,
							'empty_quantity' => $get_empty_quantity,
							'empty_email' => $get_empty_msg,
							'invalid_email' => $invalid_msg,
							'is_popup' => $is_popup,
							'phone_field' => $phone_field_visibility ? '1' : '2',
							'phone_field_error' => array($invalid_phone_number, $invalid_phone_number, $phone_number_too_short, $phone_number_too_long, $invalid_phone_number),
							'phone_utils_js' => $phone_field_visibility ? CWGINSTOCK_PLUGINURL . 'assets/js/utils.js' : '',
							'is_phone_field_optional' => $phone_field_optional ? '1' : '2',
							'is_quantity_field_optional' => $quantity_field_optional ? '1' : '2',
							'hide_country_placeholder' => $hide_country_placeholder ? '1' : '2',
							'default_country_code' => $default_country_code,
							'custom_country_placeholder' => '' != $default_country_code && 'default' != $custom_country_placeholder && '' != $custom_placehoder_value ? $custom_placehoder_value : '',
						)
				);
				wp_localize_script('cwginstock_js', 'cwginstock', $translation_array);
				wp_enqueue_script('cwginstock_js');
				wp_enqueue_script('sweetalert2');
				wp_enqueue_script('cwginstock_popup');
				$is_read_more_hide = isset($get_option['hide_readmore_button']) && '' != $get_option['hide_readmore_button'] ? true : false;
				if ($is_read_more_hide) {
					$read_more = '.products .outofstock .button {display: none; }';
					wp_add_inline_style('cwginstock_frontend_css', $read_more);
				}
				$get_bot_type = CWG_Instock_Bot_Protection::get_bot_protection_type();
				if ('recaptcha' == $get_bot_type) {
					// google v3 recaptcha
					$is_v3 = CWG_Instock_Bot_Protection::is_recaptcha_v3() ? 'yes' : 'no';
					$hide_recaptchav3_badge = 'yes' == $is_v3 && isset($get_option['recaptchav3_badge_hide']) && '' != $get_option['recaptchav3_badge_hide'] ? true : false;
					if ($hide_recaptchav3_badge) {
						$hide_badge_css = '.grecaptcha-badge { visibility: hidden !important; }';
						wp_add_inline_style('cwginstock_frontend_css', $hide_badge_css);
					}
				}
			} else {
				wp_enqueue_style('cwginstock_frontend_guest');
			}
		}

		/**
		 * Summary of admin_enqueue_scripts
		 *
		 * @return void
		 */
		public function admin_enqueue_scripts() {
			$screen = get_current_screen();
			if (isset($screen->id) && ( ( 'cwginstocknotifier_page_cwg-instock-mailer' == $screen->id ) || ( 'edit-cwginstocknotifier' == $screen->id ) || ( 'cwginstocknotifier_page_cwg-instock-status' == $screen->id ) || ( 'cwginstocknotifier_page_cwg-instock-extensions' == $screen->id ) )) {
				wp_enqueue_style('cwginstock_admin_css', CWGINSTOCK_PLUGINURL . '/assets/css/admin.css', array(), $this->version);
				wp_register_script('cwginstock_admin_js', CWGINSTOCK_PLUGINURL . '/assets/js/admin.js', array('jquery', 'wc-enhanced-select'), $this->version);
				wp_localize_script(
						'cwginstock_admin_js',
						'cwg_enhanced_selected_params',
						array(
							'search_tags_nonce' => wp_create_nonce('search-tags'),
							'ajax_url' => admin_url('admin-ajax.php'),
						)
				);
				wp_enqueue_script('cwginstock_admin_js');
			}
		}

		public function load_plugin_textdomain() {
			$domain = 'back-in-stock-notifier-for-woocommerce';
			$dir = untrailingslashit(WP_LANG_DIR);
			/**
			 * Filter hook to allow other parts of the code to modify the locale value if needed
			 *
			 * @since 1.0.0
			 */
			$locale = apply_filters('plugin_locale', get_locale(), $domain);
			$exists = load_textdomain($domain, $dir . '/plugins/' . $domain . '-' . $locale . '.mo');
			if ($exists) {
				return $exists;
			} else {
				/**
				 * Loads a plugin's translated strings
				 *
				 * @since 1.0.0
				 */
				load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/languages');
			}
		}

		/**
		 * Summary of declare_wc_compatibility
		 *
		 * @return void
		 */
		public function declare_wc_compatibility() {
			if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
				FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
			}
		}

		/**
		 * Summary of add_screen_ids_to_woocommerce
		 *
		 * @param mixed $screen_ids
		 * @return mixed
		 */
		public function add_screen_ids_to_woocommerce( $screen_ids) {
			/**
			 * Filter hook to modify or add to the array of additional screen IDs
			 *
			 * @since 1.0.0
			 */
			$extend_screen_ids = apply_filters('cwginstock_screen_ids', array('edit-cwginstocknotifier', 'cwginstocknotifier_page_cwg-instock-mailer', 'cwginstock_arrival'));
			$screen_ids = array_merge($screen_ids, $extend_screen_ids);
			return $screen_ids;
		}

		// hide help context tab

		/**
		 * Summary of remove_help_tab_context
		 *
		 * @return void
		 */
		public function remove_help_tab_context() {
			$screen = get_current_screen();
			if ('edit-cwginstocknotifier' == $screen->id || 'cwginstocknotifier_page_cwg-instock-mailer' == $screen->id || 'cwginstock_arrival' == $screen->id) {
				$screen->remove_help_tabs();
			}
		}

		public function clear_schedule_events() {
			$list_of_events = array('cwg_delete_subscribers', 'cwginstock_third_party');
			foreach ($list_of_events as $each_event) {
				if (as_next_scheduled_action($each_event)) {
					as_unschedule_all_actions($each_event);
				}
			}
		}

	}

	/**
	 * Return object
	 *
	 * @since 1.0
	 */
	function CWG_Instock_Notifier() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if (cwg_is_woocommerce_activated()) {
			return CWG_Instock_Notifier::instance();
		}
	}

	if (!function_exists('cwg_is_woocommerce_activated')) {

		/**
		 * Summary of cwg_is_woocommerce_activated
		 *
		 * @return bool
		 */
		function cwg_is_woocommerce_activated() {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if (is_plugin_active('woocommerce/woocommerce.php')) {
				return true;
			} elseif (is_plugin_active_for_network('woocommerce/woocommerce.php')) {
				return true;
			} else {
				return false;
			}
		}

	}

	CWG_Instock_Notifier();
}
