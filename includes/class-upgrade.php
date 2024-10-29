<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('CWG_Instock_Upgrade')) {

	class CWG_Instock_Upgrade {

		public $upgrade_version = '1.0';

		public function __construct() {
			add_action('upgrader_process_complete', array($this, 'trigger_upgrade_instock'), 10, 2);
			add_action('cwg_instock_upgrade', array($this, 'register_schedule'));
			add_action('cwg_sync_instock_data', array($this, 'perform_upgrade'), 99, 1);
			register_activation_hook(CWGINSTOCK_FILE, array($this, 'register_schedule'));
		}

		public function trigger_upgrade_instock( $upgrader_object, $options) {
			$our_plugin = CWGSTOCKPLUGINBASENAME;
			if ('update' == $options['action'] && 'plugin' == $options['type'] && isset($options['plugins'])) {
				foreach ($options['plugins'] as $plugin) {
					if ($plugin == $our_plugin) {
						/**
						 * Trigger update functionality here
						 *
						 * @since 1.0.0
						 */
						do_action('cwg_instock_upgrade');
					}
				}
			}
		}

		public function register_schedule() {
			// upon update to the plugin perform some actions
			$api = new CWG_Instock_API();
			$get_meta_values = $api->get_meta_values('cwginstock_product_id', 'cwginstocknotifier');
			if ($get_meta_values) {
				$chunk_data = array_chunk($get_meta_values, 5);
				foreach ($chunk_data as $each_array) {
					as_schedule_single_action(time(), 'cwg_sync_instock_data', array('pid' => $each_array));
				}
			}
		}

		public function perform_upgrade( $ids) {
			global $wpdb;
			if (is_array($ids) && !empty($ids)) {
				$this->delete_duplicate_metas();
				$obj = new CWG_Instock_API();
				foreach ($ids as $each_id) {
					$get_count = $obj->get_subscribers_count($each_id, 'cwg_subscribed');
					update_post_meta($each_id, 'cwg_total_subscribers', $get_count);
				}
			}
		}

		public function delete_duplicate_metas() {
			global $wpdb;
			$get_option = get_option('cwg_data_upgraded', 0);
			if (0 == $get_option || '0' == $get_option) {
				// do upgrade process here
				$key = 'cwg_total_subscribers';
				$results = $wpdb->get_results($wpdb->prepare("SELECT count(pm.post_id) as cpid FROM {$wpdb->postmeta} pm WHERE pm.meta_key = %s", $key));
				if ($results) {
					$count = $results[0]->cpid;
					if ($count > 0) {
						// delete it to remove the duplication
						$delete_results = $wpdb->get_results($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key= %s", $key));
						update_option('cwg_data_upgraded', '1');
					}
				}
			}
		}

	}

	new CWG_Instock_Upgrade();
}
