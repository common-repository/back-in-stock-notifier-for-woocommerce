<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'CWG_Cache_Buster' ) ) {

	class CWG_Cache_Buster {

		public function __construct() {
			add_filter( 'cwginstock_cart_link', array( $this, 'cache_buster_cart_link' ), 999, 3 );
		}

		public function cache_buster_cart_link( $url, $pid, $id ) {
			$options = get_option( 'cwginstocksettings' );
			$check_cache_buster_enable = isset( $options['cache_buster'] ) && '1' == $options['cache_buster'] ? true : false;
			if ( $check_cache_buster_enable ) {
				$rand_num = rand();
				$cart_url_cachebuster = add_query_arg( 'cachebuster', $rand_num, $url );
				return $cart_url_cachebuster;
			} else {
				return $url;
			}
		}

	}

	new CWG_Cache_Buster();
}
