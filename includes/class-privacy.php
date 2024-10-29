<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_Notifier_Privacy' ) ) {

	class CWG_Instock_Notifier_Privacy {

		public function __construct() {
			add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_instock_notifier_exporter' ) );
			add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_instock_notifier_eraser' ) );
		}

		public function register_instock_notifier_exporter( $exporters ) {
			$exporters['back-in-stock-notifier'] = array(
				'exporter_friendly_name' => _x( 'Back In Stock Notifier', 'GDPR Privacy', 'back-in-stock-notifier-for-woocommerce' ),
				'callback' => array( $this, 'back_in_stock_notifier_exporter' ),
			);
			return $exporters;
		}

		public function register_instock_notifier_eraser( $erasers ) {
			$erasers['back-in-stock-notifier'] = array(
				'eraser_friendly_name' => _x( 'Back In Stock Notifier', 'GDPR Privacy', 'back-in-stock-notifier-for-woocommerce' ),
				'callback' => array( $this, 'back_in_stock_notifier_eraser' ),
			);
			return $erasers;
		}

		public function back_in_stock_notifier_exporter( $email_address, $page = 1 ) {
			$number = 500;
			$page = (int) $page;
			$to_be_export = array();
			$args = array(
				'post_type' => 'cwginstocknotifier',
				'paged' => $page,
				'posts_per_page' => $number,
				'post_status' => 'any',
				's' => $email_address,
			);
			$get_posts = get_posts( $args );
			if ( $get_posts ) {
				foreach ( $get_posts as $each_post ) {
					$data_to_export = $this->data_to_export( $each_post );
					$to_be_export[] = array(
						'group_id' => 'back_in_stock_notifier',
						'group_label' => _x( 'Back In Stock Notifier Lists', 'GDPR privacy', 'back-in-stock-notifier-for-woocommerce' ),
						'item_id' => 'back_in_stock_notifier-' . $each_post->ID,
						'data' => $data_to_export,
					);
				}
			}
			$done = count( $get_posts ) < $number;
			return array(
				'data' => $to_be_export,
				'done' => $done,
			);
		}

		public function back_in_stock_notifier_eraser( $email_address, $page = 1 ) {
			$posts_per_page = 500;
			$page = (int) $page;
			$items_removed = false;
			$items_retained = false;

			$args = array(
				'post_type' => 'cwginstocknotifier',
				'paged' => $page,
				'posts_per_page' => $posts_per_page,
				'post_status' => 'any',
				's' => $email_address,
			);
			$get_posts = get_posts( $args );
			if ( $get_posts ) {
				foreach ( $get_posts as $each_post ) {
					$anon_email = wp_privacy_anonymize_data( 'email', get_post_meta( $each_post->ID, 'cwginstock_subscriber_email', true ) );
					update_post_meta( $each_post->ID, 'cwginstock_subscriber_email', $anon_email );
					update_post_meta( $each_post->ID, 'cwginstock_subscriber_personal_data_deleted', 'yes' );
					$items_removed = true;
				}
			}
			$done = count( $get_posts ) < $posts_per_page;

			return array(
				'items_removed' => $items_removed,
				'items_retained' => $items_retained,
				'messages' => array(),
				'done' => $done,
			);
		}

		public function update_data( $id, $title ) {
			$args = array(
				'ID' => $id,
				'post_title' => $title,
			);
			wp_update_post( $args );
		}

		public function data_to_export( $each_post ) {
			$to_be_export = array();
			$to_be_export[] = array(
				'name' => 'Created',
				'value' => $each_post->post_date,
			);
			$to_be_export[] = array(
				'name' => 'Email',
				'value' => $each_post->post_title,
			);

			$to_be_export[] = array(
				'name' => 'Status',
				'value' => get_post_status( $each_post->ID ),
			);
			$obj = new CWG_Instock_API();
			$product_name = $obj->display_product_name( $each_post->ID );
			$product_id = get_post_meta( $each_post->ID, 'cwginstock_product_id', true );
			$variation_id = get_post_meta( $each_post->ID, 'cwginstock_variation_id', true );
			$intvariation = intval( $variation_id );

			$to_be_export[] = array(
				'name' => 'Product ID',
				'value' => $product_id,
			);

			$to_be_export[] = array(
				'name' => 'Variation ID',
				'value' => $intvariation,
			);

			$to_be_export[] = array(
				'name' => 'Product Name',
				'value' => $product_name,
			);

			return $to_be_export;
		}

	}

	new CWG_Instock_Notifier_Privacy();
}
