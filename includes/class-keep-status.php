<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_Keep_Status' ) ) {

	class CWG_Instock_Keep_Status {

		public function __construct() {
			add_action( 'transition_post_status', array( $this, 'keep_subscribed_status' ), 10, 3 );
		}

		public function keep_subscribed_status( $new, $old, $post ) {
			$post_type = $post->post_type;
			if ( 'cwginstocknotifier' == $post_type ) {
				$options = get_option( 'cwginstocksettings' );
				$keep_status_subscribed = isset( $options['keep_status_subscribed'] ) && '1' == $options['keep_status_subscribed'] ? true : false;
				if ( $keep_status_subscribed ) {
					if ( 'cwg_mailsent' == $new && 'cwg_subscribed' == $old ) {
						$obj = new CWG_Instock_API();
						$obj->subscriber_subscribed( $post->ID );
					}
				}
			}
		}

	}

	new CWG_Instock_Keep_Status();
}
