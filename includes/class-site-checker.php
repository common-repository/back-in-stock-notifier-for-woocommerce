<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Site_Checker' ) ) {

	class CWG_Site_Checker {

		public function __construct() {
			add_filter( 'cwginstock_stop_email', array( $this, 'stop_send_email_for_staging' ), 10, 3 );
		}
		public function is_staging_site( $site_url ) {
			$options = get_option( 'cwginstocksettings' );
			$is_enabled = isset( $options['stop_sending_email_staging'] ) && '1' == $options['stop_sending_email_staging'] ? true : false;
			$staging_domains_set = isset( $options['staging_domains'] ) && ( '' != $options['staging_domains'] ) ? true : false;
			if ( $is_enabled && $staging_domains_set ) {
				$array_of_staging = explode( ',', $options['staging_domains'] );
				foreach ( $array_of_staging as $keyword ) {
					if ( stripos( $site_url, $keyword ) !== false ) {
						return true;
					}
				}
			}
			return false;
		}

		public function stop_send_email_for_staging( $bool_value, $subscriber_id, $product_obj ) {
			$site_url = home_url();
			if ( $this->is_staging_site( $site_url ) ) {
				$logger = new CWG_Instock_Logger( 'info', "Email notifications have been stopped for this subscriber (ID: -#$subscriber_id) due to the staging environment. Please check our 'Troubleshoot Settings (Experimental)' section." );
				$logger->record_log();
				$bool_value = true;
			}
			return $bool_value;
		}

	}

	new CWG_Site_Checker();
}
