<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'CWG_REST_API_Instock_Notifier' ) ) {

	class CWG_REST_API_Instock_Notifier {

		public $namespace = 'wc-instocknotifier/v3';
		public $post_type = 'cwginstocknotifier';

		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
		}

		public function register_rest_route() {
			register_rest_route(
				$this->namespace,
				'/create_subscriber',
				array(
					'methods' => 'POST',
					'callback' => array( $this, 'create_subscriber' ),
					'permission_callback' => array( $this, 'check_create_permission' ),
				)
			);
			register_rest_route(
				$this->namespace,
				'/get_subscriber/(?P<id>\d+)',
				array(
					'methods' => 'GET',
					'callback' => array( $this, 'get_subscriber' ),
					'permission_callback' => array( $this, 'check_permission' ),
				)
			);
			register_rest_route(
				$this->namespace,
				'/update_subscriber',
				array(
					'methods' => 'POST',
					'callback' => array( $this, 'update_subscriber' ),
					'permission_callback' => array( $this, 'check_create_permission' ),
				)
			);
			register_rest_route(
				$this->namespace,
				'/delete_subscriber/(?P<id>\d+)',
				array(
					'methods' => 'GET',
					'callback' => array( $this, 'delete_subscriber' ),
					'permission_callback' => array( $this, 'check_permission' ),
				)
			);
			register_rest_route(
				$this->namespace,
				'/list_subscriber',
				array(
					'methods' => 'POST',
					'callback' => array( $this, 'list_subscriber' ),
					'permission_callback' => array( $this, 'check_permission' ),
				)
			);
		}

		public function create_subscriber( WP_REST_Request $request ) {
			/*
							   * SAMPLE JSON REQUEST
							   * {
							   * "subscriber_name" : "subscriber name",
								"email" : "xxxxxxx@gmail.com",
								"product_id": "valid product id",
								"variation_id" : "valid variation id",
								"status" : "valid registered status",
								"subscriber_phone" : "+1 2015550123",
								"custom_quantity" : "3"
								} */

			$body = $request->get_body();
			$body_decode_json = json_decode( $body, true );

			// validation
			$check_args = array(
				'subscriber_name' => __( 'Name field required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
				'email' => __( 'Email Address required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
				'product_id' => __( 'Product ID required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
				'status' => __( 'Valid Status required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
				'subscriber_phone' => __( 'Subscriber phone required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
				'custom_quantity' => __( 'Quantity required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
			);
			foreach ( $check_args as $key => $value ) {
				if ( ! isset( $request[ $key ] ) || ( isset( $request[ $key ] ) && '' == $request[ $key ] ) ) {
					return new WP_Error( 'woocommerce_rest_cannot_view', $value, array( 'status' => '403' ) );
				}
			}

			$product_id = $body_decode_json['product_id'];
			$variation_id = isset( $body_decode_json['variation_id'] ) && $body_decode_json['variation_id'] > 0 ? (int) $body_decode_json['variation_id'] : 0;
			$email_id = $body_decode_json['email'];
			$name = $body_decode_json['name'];
			$status = $body_decode_json['status'];

			$is_valid_email = is_email( $email_id ) ? true : false;
			if ( ! $is_valid_email ) {
				return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry invalid email in your given request', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '403' ) );
			}

			$validate_product = $variation_id > 0 ? $variation_id : $product_id;
			$product_obj = wc_get_product( $validate_product );
			if ( $product_obj ) {
				// insert subscriber
				$obj = new CWG_Instock_API( $product_id, $variation_id, $email_id );
				$check_is_already_subscribed = $obj->is_already_subscribed();
				if ( ! $check_is_already_subscribed ) {
					$id = $obj->insert_subscriber( $status );
					if ( $id ) {
						$obj->insert_data( $id );
						$obj->insert_custom_data( $id, $body_decode_json, array( 'subscriber_name', 'subscriber_phone', 'custom_quantity' ) );
						$get_count = $obj->get_subscribers_count( $product_id, 'cwg_subscribed' );
						update_post_meta( $product_id, 'cwg_total_subscribers', $get_count );
						/**
						 * Perform action after inserting the subscriber.
						 *
						 * @since 1.0.0
						 */
						do_action( 'cwginstock_after_insert_subscriber', $id, $body_decode_json );
						update_post_meta( $id, 'cwginstock_created_via', 'rest_api' );
						// logger
						$logger = new CWG_Instock_Logger( 'success', "Subscriber #$email_id successfully created with status #$status via REST API - #$id" );
						$logger->record_log();
						return $this->format_response( get_post( $id ) );
					} else {
						return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Unable to CREATE Subscriber please check your details valid', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '403' ) );
					}
				} else {
					return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Seems like that email id has been already subscribed', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '403' ) );
				}
			} else {
				return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry invalid product/variation id in your given request', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '403' ) );
			}
		}

		public function format_response( $response ) {
			$data = array();
			$default = array(
				'ID' => 'id',
				'post_date' => 'subscribed_date',
				'post_status' => 'status',
			);
			foreach ( $default as $each_field => $new_field ) {
				$data[ $new_field ] = $response->$each_field;
			}

			$data['meta_data'] = get_post_meta( $response->ID );

			return rest_ensure_response( $data );
		}

		public function check_create_permission() {
			if ( ! wc_rest_check_post_permissions( $this->post_type, 'create' ) ) {
				return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
			}
			return true;
		}

		public function get_subscriber( WP_REST_Request $request ) {
			if ( isset( $request['id'] ) ) {
				$data = get_post( $request['id'] );
				if ( ! $data ) {
					return new WP_Error( 'woocommerce_rest_cannot_view', __( 'No Data found for your requested subscriber id', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '404' ) );
				} elseif ( 'cwginstocknotifier' != $data->post_type ) {
					return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Requested ID is not a Subscriber ID', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '404' ) );
				}
				return rest_ensure_response( $this->format_subscriber_data_schema( $data ) );
			} else {
				return new WP_Error( 'woocommerce_rest_cannot_view', __( 'ID not present in your Request, please review your details and try again', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '404' ) );
			}
		}

		public function format_subscriber_data_schema( $response, $multi = false ) {
			$data = array();
			$default = array(
				'ID' => 'id',
				'post_modified' => 'last_modified_date',
				'post_status' => 'status',
			);
			if ( $multi ) {
				foreach ( $response as $each_response ) {
					foreach ( $default as $each_field => $new_field ) {
						$data[][ $new_field ] = $each_response->$each_field;
					}
					$data[]['meta_data'] = get_post_meta( $each_response->ID );
				}
			} else {
				foreach ( $default as $each_field => $new_field ) {
					$data[ $new_field ] = $response->$each_field;
				}
				$data['meta_data'] = get_post_meta( $response->ID );
			}
			return rest_ensure_response( $data );
		}

		public function check_permission() {
			if ( ! wc_rest_check_post_permissions( $this->post_type, 'read' ) ) {
				return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
			}
			return true;
		}

		public function update_subscriber( WP_REST_Request $request ) {
			/*
							   * SAMPLE JSON REQUEST
							   * {
							   * "ID": "",
								"subscriber_name" : "subscriber name",
								"email" : "xxxxxxx@gmail.com",
								"product_id": "valid product id",
								"variation_id" : "valid variation id",
								"status" : "valid registered status",
								"subscriber_phone" : "+1 2015550123",
								"custom_quantity" : "3"
								} */
			$body = $request->get_body();
			$body_decode_json = json_decode( $body, true );

			// validation
			$check_args = array(
				'ID' => __( 'Subscriber ID missing in your request', 'back-in-stock-notifier-for-woocommerce' ),
				'subscriber_name' => __( 'Name field required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
				'email' => __( 'Email Address required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
				'product_id' => __( 'Product ID required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
				'status' => __( 'Valid Status required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
				'subscriber_phone' => __( 'Subscriber phone required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
				'custom_quantity' => __( 'Quantity required to perform your request', 'back-in-stock-notifier-for-woocommerce' ),
			);
			foreach ( $check_args as $key => $value ) {
				if ( ! isset( $request[ $key ] ) || ( isset( $request[ $key ] ) && '' == $request[ $key ] ) ) {
					return new WP_Error( 'woocommerce_rest_cannot_view', $value, array( 'status' => '403' ) );
				}
			}

			$product_id = $body_decode_json['product_id'];
			$variation_id = isset( $body_decode_json['variation_id'] ) && $body_decode_json['variation_id'] > 0 ? (int) $body_decode_json['variation_id'] : 0;
			$email_id = $body_decode_json['email'];
			$name = $body_decode_json['name'];
			$status = $body_decode_json['status'];
			$is_valid_email = is_email( $email_id ) ? true : false;
			if ( ! $is_valid_email ) {
				return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry invalid email in your given request', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '403' ) );
			}
			$validate_product = $variation_id > 0 ? $variation_id : $product_id;
			$product_obj = wc_get_product( $validate_product );
			if ( $product_obj ) {
				// insert subscriber
				$obj = new CWG_Instock_API( $product_id, $variation_id, $email_id );
				$id = $body_decode_json['ID'];
				$id = $obj->update_subscriber( $id, $status );
				if ( $id ) {
					// perform the insertion of data for a subscriber into the database
					$obj->insert_data( $id );
					$obj->insert_custom_data( $id, $body_decode_json, array( 'subscriber_name', 'subscriber_phone', 'custom_quantity' ) );
					$get_count = $obj->get_subscribers_count( $product_id, 'cwg_subscribed' );
					update_post_meta( $product_id, 'cwg_total_subscribers', $get_count );
					/**
					 * Perform action after insertion.
					 *
					 * @since 1.0.0
					 */
					do_action( 'cwginstock_after_insert_subscriber', $id, $body_decode_json );
					update_post_meta( $id, 'cwginstock_created_via', 'rest_api' );
					// logger
					$logger = new CWG_Instock_Logger( 'success', "Subscriber #$email_id successfully updated with status #$status via REST API - #$id" );
					$logger->record_log();
					return $this->format_response( get_post( $id ) );
				} else {
					return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Unable to Update Subscriber please check your details valid', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '403' ) );
				}
			} else {
				return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry invalid product/variation id in your given request', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '403' ) );
			}
		}

		public function delete_subscriber( WP_REST_Request $request ) {
			if ( isset( $request['id'] ) ) {
				$get_post = get_post( $request['id'] );
				if ( $get_post ) {
					$post_type = $get_post->post_type;
					if ( 'cwginstocknotifier' == $post_type ) {
						$data = wp_delete_post( $request['id'], true );
						return rest_ensure_response( array( 'msg' => '#' . $request['id'] . ' Deleted successfully' ) );
					} else {
						return new WP_Error( 'woocommerce_rest_cannot_view', __( 'You cannot delete data other than Subscribers', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '404' ) );
					}
				}
			} else {
				return new WP_Error( 'woocommerce_rest_cannot_view', __( 'ID not present in your Request, please review your details and try again', 'back-in-stock-notifier-for-woocommerce' ), array( 'status' => '404' ) );
			}
		}

		public function list_subscriber( WP_REST_Request $request ) {
			/*
							   * SAMPLE JSON REQUEST
								{
								"p_ids" : array('170','124'),
								"variation_ids" : array('111','1244'),
								"include": "true/false",
								"status" : array('cwg_subscribed','cwg_unsubscribed'),
								}
							   */
			$body = $request->get_body();
			$body_decode_json = json_decode( $body, true );
			$status = isset( $body_decode_json['status'] ) ? $body_decode_json['status'] : 'any';
			$product_id = isset( $body_decode_json['p_ids'] ) ? $body_decode_json['p_ids'] : array();
			$args = array(
				'numberposts' => -1,
				'post_type' => 'cwginstocknotifier',
				'post_status' => $status,
				'meta_query' => array(
					array(
						'key' => 'cwginstock_pid',
						'value' => $product_id,
						'compare' => 'IN',
					),
				),
			);

			$subscriptions = get_posts( $args );
			return rest_ensure_response( $this->format_subscriber_data_schema( $subscriptions, true ) );
		}

	}

	new CWG_REST_API_Instock_Notifier();
}
