<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_Webhook' ) ) {

	class CWG_Instock_Webhook {

		public function __construct() {
			add_filter( 'woocommerce_webhook_topic_hooks', array( $this, 'add_topic' ) );
			add_filter( 'woocommerce_webhook_topics', array( $this, 'title_topics' ) );
			add_filter( 'woocommerce_valid_webhook_events', array( $this, 'valid_topics' ) );
			add_filter( 'woocommerce_valid_webhook_resources', array( $this, 'add_values_to_resources' ) );
			add_filter( 'woocommerce_webhook_payload', array( $this, 'resource_payload' ), 10, 4 );
			add_action( 'transition_post_status', array( $this, 'resource_upon_update' ), 10, 3 );
		}

		public function add_topic( $topics ) {
			$new_topic = array(
				'subscriber.created' => array(
					'cwginstock_after_insert_subscriber',
				),
				'subscriber.updated' => array(
					'cwginstock_subscriber_updated',
				),
				'subscriber.backinstock' => array(
					'cwginstock_subscriber_instock',
				),
			);
			return array_merge( $topics, $new_topic );
		}

		public function valid_topics( $events ) {
			$events[] = 'backinstock';
			return $events;
		}

		public function add_values_to_resources( $resources ) {
			$new_events = array(
				'subscriber',
			);
			return array_merge( $resources, $new_events );
		}

		public function title_topics( $topics ) {
			$new_topics = array(
				'subscriber.created' => 'Subscriber Created',
				'subscriber.updated' => 'Subscriber upon Status Change',
				'subscriber.backinstock' => 'Subscriber upon Mailsent Status (Back In Stock)',
			);

			return array_merge( $topics, $new_topics );
		}

		public function resource_payload( $payload, $resource, $resource_id, $webhook_id ) {
			if ( 'subscriber' == $resource ) {
				$variation_id = get_post_meta( $resource_id, 'cwginstock_variation_id', true );
				$product_id = get_post_meta( $resource_id, 'cwginstock_product_id', true );
				$subscriber_name = get_post_meta( $resource_id, 'cwginstock_subscriber_name', true );
				$subscriber_email = get_post_meta( $resource_id, 'cwginstock_subscriber_email', true );
				$subscriber_phone = get_post_meta( $resource_id, 'cwginstock_subscriber_phone', true );
				$quantity = get_post_meta( $resource_id, 'cwginstock_custom_quantity', true );
				$post_date = get_the_date( '', $resource_id );
				$post_status = str_replace( 'cwg_', '', get_post_status( $resource_id ) );
				$pid = get_post_meta( $resource_id, 'cwginstock_pid', true );
				$api = new CWG_Instock_API();
				$sku = $api->get_product_sku( $resource_id );
				$product_name = $api->display_product_name( $resource_id );
				$only_product_name = $api->display_only_product_name( $resource_id );
				$product_link = $api->display_product_link( $resource_id );
				$product_image = $api->get_product_image( $resource_id );
				/**
				 * Filter for payload data
				 *
				 * @since 1.0.0
				 */
				$subscriber_data = apply_filters(
					''
					. '',
					array(
						'name' => $subscriber_name,
						'email' => $subscriber_email,
						'phone' => $subscriber_phone,
						'quantity' => $quantity,
						'product_id' => $product_id,
						'variation_id' => $variation_id,
						'sku' => $sku,
						'product_name' => $product_name,
						'only_product_name' => $only_product_name,
						'product_link' => $product_link,
						'product_image' => $product_image,
						'status' => $post_status,
						'subscribed_date' => $post_date,
					),
					$resource_id,
					$webhook_id
				);
				$payload = $subscriber_data;
			}
			return $payload;
		}

		public function resource_upon_update( $new, $old, $post ) {
			// post type
			$post_type = $post->post_type;
			if ( 'cwginstocknotifier' == $post_type ) {
				/**
				 * Action for subscriber update
				 *
				 * @since 1.0.0
				 */
				do_action( 'cwginstock_subscriber_updated', $post->ID );

				if ( ( 'cwg_subscribed' == $old && 'cwg_mailsent' == $new ) || ( 'cwg_doubleoptin' == $old && 'cwg_mailsent' == $new ) ) {
					/**
					 * Action for fire only when the subscription status move from subscribed to mail sent
					 *
					 * @since 5.2.2
					 */
					do_action( 'cwginstock_subscriber_instock', $post->ID );
				}
			}
		}

	}

	new CWG_Instock_Webhook();
}
