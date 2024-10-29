<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWG_Background_Task extends WP_Async_Task {

	protected $action = 'cwginstock_trigger_status';

	/**
	 * Prepare data for the asynchronous request
	 *
	 * @throws Exception If for any reason the request should not happen
	 *
	 * @param array $data An array of data sent to the hook
	 *
	 * @return array
	 */
	protected function prepare_data( $data ) {
		$product_id = $data[0];
		$stockstatus = $data[1];
		$get_post_type = get_post_type( $product_id );
		if ( 'product' !== $get_post_type ) {
			throw new Exception( 'We only want async tasks for products' );
		}
		return array(
			'product_id' => $product_id,
			'stock_status' => $stockstatus,
			'object' => $data[3],
		);
	}

	/**
	 * Run the async task action
	 */
	protected function run_action() {

	}

}
