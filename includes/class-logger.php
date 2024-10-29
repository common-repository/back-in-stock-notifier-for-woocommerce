<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'CWG_Instock_Logger' ) ) {

	class CWG_Instock_Logger {

		private $status;
		private $message;

		public function __construct( $status = '', $message = '' ) {
			$this->status = $status;
			$this->message = $message;
		}

		private function context_name() {
			$context_name = array( 'source' => CWGINSTOCK_DIRNAME );
			return $context_name;
		}

		public function format_message() {
			$replace = str_replace( '#', '', $this->message );
			$arr = explode( ' ', $replace );
			foreach ( $arr as $key => $val ) {
				if ( preg_match( '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i', $val ) ) {
					$arr_email = explode( '@', $val );
					$first_data = $arr_email[0];
					if ( strlen( $first_data ) > 1 ) {
						$first_character = $first_data[0];
						$last_character = substr( $first_data, -1, '1' );
						$string_length = strlen( $first_data );
						$hidden_character = substr( $first_data, 1, $string_length - 2 );
						$hidden = '';
						if ( strlen( $hidden_character ) > 0 ) {
							for ( $i = 1; $i <= strlen( $hidden_character ); $i++ ) {
								$hidden .= 'x';
							}
						}
						$arr_email[0] = $first_character . $hidden . $last_character;
					} else {
						$arr_email[0] = 'xxxxx';
					}
					$val_new = implode( '@', $arr_email );
					$arr[ $key ] = $val_new;
				}
			}
			$new_msg = implode( ' ', $arr );
			return $new_msg;
		}

		public function message() {
			return $this->format_message();
		}

		public function logger() {
			if ( function_exists( 'wc_get_logger' ) ) {
				return wc_get_logger();
			} else {
				return new WC_Logger();
			}
		}

		public function record_log() {
			$logger = $this->logger();
			$status = $this->status;
			if ( ! function_exists( 'wc_get_logger' ) ) {
				$this->status = '';
			}
			switch ( $this->status ) {
				case 'debug':
					$logger->debug( $this->message(), $this->context_name() );
					break;
				case 'info':
					$logger->info( $this->message(), $this->context_name() );
					break;
				case 'notice':
					$logger->notice( $this->message(), $this->context_name() );
					break;
				case 'warning':
					$logger->warning( $this->message(), $this->context_name() );
					break;
				case 'error':
					$logger->error( $this->message(), $this->context_name() );
					break;
				case 'critical':
					$logger->critical( $this->message(), $this->context_name() );
					break;
				case 'success':
					$logger->log( 'info', $this->message(), $this->context_name() );
					break;
				case 'alert':
					$logger->alert( $this->message(), $this->context_name() );
					break;
				case 'emergency':
					$logger->emergency( $this->message(), $this->context_name() );
					break;
				default:
					if ( function_exists( 'wc_get_logger' ) ) {
						$logger->log( $this->status, $this->message(), $this->context_name() );
					} else {
						$logger->add( 'back-in-stock-notifier', $this->message() . ' ' . $status );
					}
					break;
			}
		}

	}

}
