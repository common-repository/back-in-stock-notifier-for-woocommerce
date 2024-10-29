<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Template' ) ) {

	class CWG_Template {

		private $default_dir = 'back-in-stock-notifier-for-woocommerce/';
		private $template_name;
		private $args;
		private $template_path;
		private $default_path;

		public function __construct( $template_name, $args = '', $template_path = '', $default_path = '' ) {
			$this->template_name = $template_name;
			$this->args = $args;
			$this->template_path = $template_path;
			$this->default_path = $default_path;
		}

		private function locate_template() {

			if ( ! $this->template_path ) {
				$template_path = $this->default_dir;
			}
			// Set plugin template path
			if ( ! $this->default_path ) {
				$default_path = CWGINSTOCK_PLUGINDIR . 'templates/'; // Path to the template folder
			}

			// Search template file in theme folder.
			$template = locate_template(
				array(
					$template_path . $this->template_name,
					$this->template_name,
				)
			);

			// Get plugins template file.
			if ( ! $template ) {
				$template = $default_path . $this->template_name;
			}
			/**
			 * Filter for locate template
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'cwginstock_locate_template', $template, $this->template_name, $template_path, $default_path, $this->args );
		}

		public function get_template() {

			$template_file = $this->locate_template();
			if ( is_array( $this->args ) && isset( $this->args ) ) {
				extract( $this->args );
			}

			if ( ! file_exists( $template_file ) ) {
				_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> Unable to find specific template', esc_html( $template_file ) ), '1.0.0' );
				return;
			}
			include $template_file;
		}

	}

}
