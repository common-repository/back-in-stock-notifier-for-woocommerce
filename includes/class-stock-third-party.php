<?php

if (!defined('ABSPATH')) {
	exit;
}
if (!class_exists(' CWG_Instock_Third_Party_Support')) {

	class CWG_Instock_Third_Party_Support {

		public function __construct() {
			add_action('cwginstock_third_party', array($this, 'retrive_product_ids'));
			add_action('cwg_backward_stock_check', array($this, 'backward_stock_check'));
			register_activation_hook(CWGINSTOCK_FILE, array($this, 'register_schedule'));
		}

		public function retrive_product_ids() {
			$options = get_option('cwginstocksettings');
			$check_stock_status_third_party = isset($options['update_stock_third_party']) && '1' == $options['update_stock_third_party'] ? true : false;
			global $wpdb;
			if ($check_stock_status_third_party) {
				$args = array(
					'post_type' => 'cwginstocknotifier',
					'fields' => 'ids',
					'posts_per_page' => -1,
					'post_status' => 'cwg_subscribed',
				);

				$get_posts = get_posts($args);
				if (is_array($get_posts) && !empty($get_posts)) {
					$format = array_fill(0, count($get_posts), '%d');
					$post_ids = '(' . implode(',', $format) . ')';
					$select_Query = $wpdb->get_col(
							$wpdb->prepare(
									// phpcs:ignore
									sprintf("SELECT meta_value from $wpdb->postmeta where post_id IN %s and meta_key ='cwginstock_pid'", $post_ids),
									$get_posts
							)
					);
					if (is_array($select_Query) && !empty($select_Query)) {
						$array = array_unique($select_Query);
						$chunk = array_chunk($array, 5);
						foreach ($chunk as $each_array) {
							as_schedule_single_action(time(), 'cwg_backward_stock_check', array('pid' => $each_array));
						}
					}
				}
			}
		}

		public function action_based_on_stock_status( $id, $stockstatus, $obj = '') {
			/**
			 * Filter 'cwg_before_process_instock_email' allows processing (returns true) and the stock status is 'instock', the action hook 'cwginstock_trigger_status' is triggered.
			 *
			 * @since 1.0.0
			 */
			if (apply_filters('cwg_before_process_instock_email', true, $id, $stockstatus) && 'instock' == $stockstatus) {
				/**
				 * Action based on stock status.
				 *
				 * @since 1.0.0
				 */
				do_action('cwginstock_trigger_status', $id, $stockstatus, $obj);
			}
		}

		public function backward_stock_check( $ids) {
			if (is_array($ids) && !empty($ids)) {
				foreach ($ids as $key => $value) {
					$product = wc_get_product($value);
					if ($product) {
						$stock_status = $product->get_stock_status();
						if ('instock' == $stock_status) {
							$this->action_based_on_stock_status($value, $stock_status, $product);
						}
					}
				}
			}
		}

		public function register_schedule() {
			if (!as_next_scheduled_action('cwginstock_third_party')) {
				as_schedule_recurring_action(time(), 300, 'cwginstock_third_party');
			}
		}

	}

	new CWG_Instock_Third_Party_Support();
}
