<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_Core' ) ) {

	class CWG_Instock_Core {

		protected $process_mail;

		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'initialize' ) );
			add_action( 'woocommerce_product_set_stock_status', array( $this, 'action_based_on_stock_status' ), 999, 3 );
			add_action( 'woocommerce_variation_set_stock_status', array( $this, 'action_based_on_stock_status' ), 999, 3 );
			add_action( 'cwginstock_trigger_status', array( $this, 'trigger_instock_status' ), 999, 3 );
			add_action( 'cwg_instock_mail_send_as_copy', array( $this, 'send_subscription_copy_to_recipients' ), 10, 3 );
			add_action( 'cwg_instock_mail_sent_success', array( $this, 'recount_subscribers_upon_instock' ), 99 );
			add_action( 'cwg_instock_bulk_status_action', array( $this, 'recount_subscribers_upon_bulk_action' ), 99, 2 );
			add_filter( 'cwginstock_trigger_status_variation', array( $this, 'alter_data_when_sync_variation_stock' ), 99, 2 );
			add_filter( 'cwginstock_replace_shortcode', array( $this, 'replace_shortcode_with_parameters' ), 10, 2 );
			add_action( 'cwginstock_notify_process', array( $this, 'perform_woocommerce_background_process' ), 100, 1 );
			add_filter( 'cwginstock_trigger_status_product', array( $this, 'get_product_bundle_subscribers' ), 10, 2 );
			add_filter( 'cwginstock_trigger_status_variation', array( $this, 'get_product_bundle_subscribers' ), 10, 2 );
			add_filter( 'cwg_before_process_instock_email', array( $this, 'product_visibility_check' ), 10, 3 );
			add_filter( 'cwg_before_process_instock_email', array( $this, 'minimum_stock_quantity_check' ), 999, 3 );
			add_action( 'cwginstock_auto_email_sent', array( $this, 'instock_mail_sent' ), 999, 2 );
		}

		public function initialize() {
			$this->process_mail = new CWG_Instock_Mail_Process();
		}

		public function action_based_on_stock_status( $id, $stockstatus, $obj = '' ) {
			/**
			 * Filter allows processing (returns true) if the stock status is "in-stock".             
			 *
			 * @since 1.0.0
			 */
			if ( apply_filters( 'cwg_before_process_instock_email', true, $id, $stockstatus ) && 'instock' == $stockstatus ) {
				/**
				 * Action based on stock status.
				 *
				 * @since 1.0.0
				 */
				do_action( 'cwginstock_trigger_status', $id, $stockstatus, $obj );
			}
		}

		public function trigger_instock_status( $id, $stockstatus, $obj ) {
			// perform action or trigger dispatch process
			if ( ! $obj ) {
				$obj = wc_get_product( $id );
			}
			if ( $obj && ( $obj->is_type( 'variation' ) || $obj->is_type( 'variable' ) ) ) {
				$main_obj = $obj->is_type( 'variable' ) ? new CWG_Instock_API( $id, 0 ) : new CWG_Instock_API( 0, $id, '', 0 );
				$options = get_option( 'cwginstocksettings' );
				$variable_any_variation_backinstock = isset( $options['variable_any_variation_backinstock'] ) && '1' == $options['variable_any_variation_backinstock'] ? true : false;
				$get_type = 'variation' == $obj->get_type() ? true : false;
				$list_of_subscribers = $main_obj->get_list_of_subscribers();

				if ( $get_type ) {
					if ( $variable_any_variation_backinstock ) {
						$get_parent_id = $obj->get_parent_id();
						$parent_obj = new CWG_Instock_API( $get_parent_id, 0 );
						$parent_subscribers = $parent_obj->get_list_of_subscribers( 'AND' );
						if ( $parent_subscribers ) {
							if ( is_array( $parent_subscribers ) && ! empty( $parent_subscribers ) ) {
								foreach ( $parent_subscribers as $each_entry ) {
									update_post_meta( $each_entry, 'cwginstock_bypass_pid', $id );
								}
							}
							$list_of_subscribers = array_merge( $list_of_subscribers, $parent_subscribers );
						}
					}
				}
				/**
				 * Filter for trigger status variation
				 *
				 * @since 1.0.0
				 */
				$get_posts = apply_filters( 'cwginstock_trigger_status_variation', $list_of_subscribers, $id );
				$this->background_process_core( $get_posts, true, $id );
			} else {
				// for simple
				$main_obj = new CWG_Instock_API( $id, 0 );
				/**
				 * Filter for trigger status product
				 *
				 * @since 1.0.0
				 */
				$get_posts = apply_filters( 'cwginstock_trigger_status_product', $main_obj->get_list_of_subscribers(), $id );
				$this->background_process_core( $get_posts, false, $id );
			}
		}

		public function alter_data_when_sync_variation_stock( $get_data, $id ) {
			$object = wc_get_product( $id );
			if ( $object && ( $object->is_type( 'variable' ) && $object->child_is_in_stock() ) ) {
				$count = $object->get_children(); // if variable product contain one variation some theme has consider as variable product id instead of variation
				$count = count( $count ); // somecase it yields fatal error when call directly in if condition
				if ( $count > 1 ) {
					$get_data = array();
				}
			}
			return $get_data;
		}

		public function send_subscription_copy_to_recipients( $to, $subject, $message ) {
			$get_option = get_option( 'cwginstocksettings' );
			$check_copy_enabled = isset( $get_option['enable_copy_subscription'] ) && '1' == $get_option['enable_copy_subscription'] ? true : false;
			if ( $check_copy_enabled ) {
				$get_recipients = isset( $get_option['subscription_copy_recipients'] ) && ! empty( $get_option['subscription_copy_recipients'] ) ? $get_option['subscription_copy_recipients'] : false;
				if ( $get_recipients ) {
					$explode_data = explode( ',', $get_recipients );
					if ( is_array( $explode_data ) && ! empty( $explode_data ) ) {
						foreach ( $explode_data as $each_mail ) {
							$mailer = WC()->mailer();
							$sendmail = $mailer->send( $each_mail, $subject, $message );
						}
					}
				}
			}
		}

		public function recount_subscribers_upon_instock( $subscriber_id ) {
			$obj = new CWG_Instock_API();
			$get_product_id = get_post_meta( $subscriber_id, 'cwginstock_product_id', true );
			if ( $get_product_id ) {
				$get_count = $obj->get_subscribers_count( $get_product_id, 'cwg_subscribed' );
				update_post_meta( $get_product_id, 'cwg_total_subscribers', $get_count );
			}
		}

		public function recount_subscribers_upon_bulk_action( $subscriber_id, $status ) {
			$this->recount_subscribers_upon_instock( $subscriber_id );
		}

		public function replace_shortcode_with_parameters( $content, $subscriber_id ) {
			// return $content;
			$obj = new CWG_Instock_API();
			$prefix = '{product_image=';
			$suffix = '}';
			$get_the_shortcode_content = $obj->get_match_based_on_prefix_suffix( $content, $prefix, $suffix );
			if ( is_array( $get_the_shortcode_content ) && ! empty( $get_the_shortcode_content ) ) {
				// exits
				foreach ( $get_the_shortcode_content as $each_parameters ) {
					$explode_data = explode( 'x', $each_parameters ); // if param value contain something like widthxheight(ex 300x200)
					$count = count( $explode_data );
					$shortcode = $prefix . $each_parameters . $suffix;
					$each_parameters = $count > 1 ? array( (int) $explode_data[0], (int) $explode_data[1] ) : $each_parameters;
					$replace_shortcode = $obj->get_product_image( $subscriber_id, $each_parameters );
					$content = str_replace( $shortcode, $replace_shortcode, $content );
				}
			}
			return $content;
		}

		public function background_process_core( $get_posts, $is_variation, $id ) {
			$get_bg_engine = get_option( 'cwginstocksettings' );
			$get_bg_engine = isset( $get_bg_engine['bgp_engine'] ) ? $get_bg_engine['bgp_engine'] : 'wcbgp';

			$variation_log = 'Stock found for Variation id #{id} and  Background Process queued for {subscriber_ids}';
			$variation_bg_log = 'Variation id #{id} Instock Background Process started - Total: {total}';
			$product_log = 'Stock found for Product id {id} and Background Process queued for {subscriber_ids}';
			$product_bg_log = 'Product id #{id} Instock Background Process started - Total: {total}';

			if ( $is_variation ) {
				// variation_log
				$queue_log = $variation_log;
				$started_log = $variation_bg_log;
			} else {
				// product_log
				$queue_log = $product_log;
				$started_log = $product_bg_log;
			}

			$queue_log = $this->placeholder_log_replacement( $queue_log, $get_posts, $id );
			$started_log = $this->placeholder_log_replacement( $started_log, $get_posts, $id );

			if ( is_array( $get_posts ) && ! empty( $get_posts ) ) {
				if ( 'wpbgp' == $get_bg_engine ) {
					foreach ( $get_posts as $each_id ) {
						$this->process_mail->push_to_queue( $each_id );
					}
					$this->process_mail->save()->dispatch();
				} else {
					// for woocommerce background process
					$chunk_data = array_chunk( $get_posts, 5 );
					foreach ( $chunk_data as $each_array ) {
						as_schedule_single_action( time(), 'cwginstock_notify_process', array( 'pid' => $each_array ) );
					}
				}
				$logger = new CWG_Instock_Logger( 'info', $queue_log );
				$logger->record_log();
				$total = count( $get_posts );
				$logger = new CWG_Instock_Logger( 'info', $started_log );
				$logger->record_log();
			}
		}

		public function placeholder_log_replacement( $msg, $get_posts, $id ) {
			$find_shortcode = array( '{id}', '{subscriber_ids}', '{total}' );
			$replace_shortcode = array( $id, implode( ',', $get_posts ), count( $get_posts ) );
			$str_replace = str_replace( $find_shortcode, $replace_shortcode, $msg );
			return $str_replace;
		}

		public function perform_woocommerce_background_process( $ids ) {
			if ( is_array( $ids ) && ! empty( $ids ) ) {
				foreach ( $ids as $each_id ) {
					$get_post_status = get_post_status( $each_id );
					if ( ! get_post_meta( $each_id, 'cwginstock_bypass_pid', true ) ) {
						$pid = get_post_meta( $each_id, 'cwginstock_pid', true );
					} else {
						$pid = get_post_meta( $each_id, 'cwginstock_bypass_pid', true );
					}
					$product_obj = wc_get_product( $pid );
					if ( $product_obj && $product_obj->is_in_stock() ) {
						if ( 'cwg_subscribed' == $get_post_status ) {
							$get_email = get_post_meta( $each_id, 'cwginstock_subscriber_email', true );
							$option = get_option( 'cwginstocksettings' );
							$is_enabled = isset( $option['enable_instock_mail'] ) ? $option['enable_instock_mail'] : 0;
							/**
							 * Filter for Stop Email
							 *
							 * @since 5.6.0
							 */
							$stop_send_email = apply_filters( 'cwginstock_stop_email', false, $each_id, $product_obj );
							if ( ! $stop_send_email && ( '1' == $is_enabled || 1 == $is_enabled ) ) {
								$mailer = new CWG_Instock_Mail( $each_id );
								$send_mail = $mailer->send(); // mail sent
								if ( $send_mail ) {
									$api = new CWG_Instock_API();
									$mail_status = $api->mail_sent_status( $each_id ); // update mail sent status
									$logger = new CWG_Instock_Logger( 'info', "Automatic Instock Mail Triggered for ID #$each_id with #$get_email" );
									$logger->record_log();
									/**
									 * Action hook for retrieving the timestamp when the automatic in-stock mail is triggered
									 *
									 * @since 4.0.1
									 */
									do_action( 'cwginstock_auto_email_sent', $each_id, time() );
								} else {
									$api = new CWG_Instock_API();
									$mail_status = $api->mail_not_sent_status( $each_id );
									$logger = new CWG_Instock_Logger( 'error', "Failed to send Automatic Instock Mail for ID #$each_id with #$get_email" );
									$logger->record_log();
								}
							}
						}
					} else {
						$logger = new CWG_Instock_Logger( 'error', 'Seems product has been out of stock, so no point in sending mail to the respective subscriber' );
						$logger->record_log();
					}
				}
			}
		}

		// compatible to woocommerce product bundle plugin
		public function get_product_bundle_subscribers( $subscribers, $product_id ) {
			$product = wc_get_product( $product_id ); // if this

			if ( $product && ! $product->is_type( 'bundle' ) ) { // this product type to be excluded because the below code applicable on other product types
				// now check this product exists in bundle product or not
				if ( function_exists( 'wc_pb_is_bundled_cart_item' ) ) {
					$product_ids = array( $product_id );
					$results = WC_PB_DB::query_bundled_items(
						array(
							'return' => 'id=>bundle_id',
							'product_id' => $product_ids,
						)
					);
					if ( is_array( $results ) && ! empty( $results ) ) {
						// now we matched with bundle data
						foreach ( $results as $each_item_key => $bundle_id ) {
							// bundle id is parent id upon send instock email check the bundle product is instock
							$bundle = wc_get_product( $bundle_id );
							if ( $bundle && $bundle->is_in_stock() ) { // if it is true it check stock for bundled items as well
								// fetch subscribers only when the bundle product items back in stock
								$main_obj = new CWG_Instock_API( $bundle_id, 0 );
								$get_list_of_subscribers = $main_obj->get_list_of_subscribers();
								if ( is_array( $get_list_of_subscribers ) && ! empty( $get_list_of_subscribers ) ) {
									$subscribers = array_merge( $subscribers, $get_list_of_subscribers );
								}
							}
						}
					}
				}
			}

			return $subscribers;
		}

		public function product_visibility_check( $bool, $id, $stockstatus ) {
			$product = wc_get_product( $id );
			$options = get_option( 'cwginstocksettings' );
			$bool = $product && 'publish' != $product->get_status() && isset( $options['enable_instock_mail_for_product_status'] ) && 1 == $options['enable_instock_mail_for_product_status'] ? false : $bool;
			return $bool;
		}

		public function minimum_stock_quantity_check( $bool, $id, $stockstatus ) {
			$product = wc_get_product( $id );
			$options = get_option( 'cwginstocksettings' );
			$min_stock_value = isset( $options['set_stock_quantity_for_instock_mail'] ) && ( '' != $options['set_stock_quantity_for_instock_mail'] || 0 < $options['set_stock_quantity_for_instock_mail'] ) ? true : false;
			$bool = $min_stock_value && $product && $product->managing_stock() && isset( $options['set_stock_quantity_for_instock_mail'] ) && $product->get_stock_quantity() < $options['set_stock_quantity_for_instock_mail'] ? false : $bool;
			return $bool;
		}

		public function instock_mail_sent( $subscriber_id, $action_triggered_time ) {
			update_post_meta( $subscriber_id, 'cwginstock_mail_on', $action_triggered_time );
		}

	}

	$instock_core = new CWG_Instock_Core();
}
