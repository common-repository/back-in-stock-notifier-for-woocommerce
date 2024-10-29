<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_Ajax' ) ) {

	class CWG_Instock_Ajax {

		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
			add_action( 'wp_ajax_cwginstock_product_subscribe', array( $this, 'perform_ajax_subscription' ) );
			add_action( 'wp_ajax_nopriv_cwginstock_product_subscribe', array( $this, 'perform_ajax_subscription' ) );
			add_action( 'cwginstock_ajax_data', array( $this, 'perform_action_on_ajax_data' ), 10, 2 );
			add_action( 'cwginstock_after_insert_subscriber', array( $this, 'perform_action_after_insertion' ), 10, 2 );
			add_action( 'wp_ajax_woocommerce_json_search_tags', array( $this, 'json_search_tags' ) );
			add_action( 'wp_ajax_cwg_trigger_popup_ajax', array( $this, 'cwg_ajax_verification' ) );
			add_action( 'wp_ajax_nopriv_cwg_trigger_popup_ajax', array( $this, 'cwg_ajax_verification' ) );
		}

		public function perform_ajax_subscription() {
			$this->ajax_subscription();
		}

		public function ajax_subscription( $perform_security = true, $rest_api = false ) {
			if ( isset( $_POST ) ) {
				$obj = new CWG_Instock_API();
				$array_error = array(
					'msg' => '-1',
					'code' => 'cwg_nonce_verify_failed',
				);
				$post_data = $obj->post_data_validation( $_REQUEST );
				$product_id = $post_data['product_id'];
				$get_option = get_option( 'cwginstocksettings' );
				$check_is_security = isset( $post_data['security'] ) && '' != $post_data['security'] ? 'yes' : 'no';
				if ( 'no' == $check_is_security ) {
					// block ajax request as it may be a bot
					if ( ! $rest_api ) {
						wp_send_json( $array_error, 403 );
					} else {
						return array(
							'msg' => $array_error,
							'status' => 403,
						);
					}
				}

				if ( 'recaptcha' == CWG_Instock_Bot_Protection::get_bot_protection_type() ) {
					$check_is_recaptcha_enabled = isset( $get_option['enable_recaptcha'] ) && '1' == $get_option['enable_recaptcha'] ? '1' : '2';
					$check_recaptcha_server_verify = isset( $get_option['enable_recaptcha_verify'] ) && '1' == $get_option['enable_recaptcha_verify'] ? '1' : '2';
					$check_secret_key = CWG_Instock_Bot_Protection::get_secret_key() != '' ? CWG_Instock_Bot_Protection::get_secret_key() : '2';
					// if it is recaptcha ignore nonce and try verify recaptcha from google(avoid something went wrong error cause because of mainly from cache)
					if ( '2' == $check_is_recaptcha_enabled || ( '1' == $check_is_recaptcha_enabled && ( ! CWG_Instock_Bot_Protection::is_recaptcha_v3() ) && '2' == $check_recaptcha_server_verify ) ) {
						if ( ( $perform_security && ( ! ( check_ajax_referer( 'codewoogeek-product_id-' . $product_id, 'security', false ) ) && ! wp_verify_nonce( $post_data['security'], 'codewoogeek-product_id-' . $product_id ) ) ) ) {
							if ( ! $rest_api ) {
								wp_send_json( $array_error, 403 );
							} else {
								return array(
									'msg' => $array_error,
									'status' => 403,
								);
							}
						}
					} elseif ( '1' == $check_is_recaptcha_enabled && ( ( ! CWG_Instock_Bot_Protection::is_recaptcha_v3() && '1' == $check_recaptcha_server_verify ) || CWG_Instock_Bot_Protection::is_recaptcha_v3() ) && '2' != $check_secret_key ) {
						$verify_gresponse = $this->verify_recaptcha_client_response( $post_data, $get_option );
						if ( is_wp_error( $verify_gresponse ) ) {
							if ( ! $rest_api ) {
								wp_send_json( $array_error, 403 );
							} else {
								return array(
									'msg' => $array_error,
									'status' => 403,
								);
							}
						} else {
							$gresponse_body = json_decode( wp_remote_retrieve_body( $verify_gresponse ) );
							$gresponse_status = $gresponse_body->success;
							if ( ! $gresponse_status ) {
								if ( ! $rest_api ) {
									wp_send_json( $array_error, 403 );
								} else {
									return array(
										'msg' => $array_error,
										'status' => 403,
									);
								}
							}
						}
					}
				} else {
					// turnstile in future any other new bot protection can be listed as else if
					$check_is_turnstile_enabled = CWG_Instock_Bot_Protection::is_turnstile_enabled();
					if ( '1' == $check_is_turnstile_enabled ) {
						$verify_tresponse = $this->verify_turnstile_client_response( $post_data, $get_option );
						if ( is_wp_error( $verify_tresponse ) ) {
							if ( ! $rest_api ) {
								wp_send_json( $array_error, 403 );
							} else {
								return array(
									'msg' => $array_error,
									'status' => 403,
								);
							}
						} else {
							$tresponse_body = json_decode( wp_remote_retrieve_body( $verify_tresponse ) );
							$tresponse_status = $tresponse_body->success;
							if ( ! $tresponse_status ) {
								if ( ! $rest_api ) {
									wp_send_json( $array_error, 403 );
								} else {
									return array(
										'msg' => $array_error,
										'status' => 403,
									);
								}
							}
						}
					}
				}
				/**
				 * Action for success subscription
				 *
				 * @since 1.0.0
				 */
				do_action( 'cwginstock_ajax_data', $post_data, $rest_api );
				$success_msg = __( 'You have successfully subscribed, we will inform you when this product back in stock', 'back-in-stock-notifier-for-woocommerce' );
				$success = isset( $get_option['success_subscription'] ) && $get_option['success_subscription'] ? $get_option['success_subscription'] : $success_msg;
				$success_message = "<div class='cwginstocksuccess' style='color:green;'>$success</div>";
				/**
				 * Filter for HTML success subscription
				 *
				 * @since 1.0.0
				 */
				$success_message = apply_filters( 'cwginstock_success_subscription_html', $success_message, $success, $post_data );
				$array_success = array( 'msg' => $success_message );
				if ( ! $rest_api ) {
					wp_send_json( $array_success, 200 );
				} else {
					return array(
						'msg' => $array_success,
						'status' => 200,
					);
				}
			}
			die();
		}

		public function perform_action_on_ajax_data( $post_data, $rest_api ) {
			$get_email = $post_data['user_email'];
			$get_user_id = $post_data['user_id'];
			$product_id = $post_data['product_id'];
			$variation_id = $post_data['variation_id'];

			$obj = new CWG_Instock_API( $product_id, $variation_id, $get_email, $get_user_id );

			$check_is_already_subscribed = $obj->is_already_subscribed();

			if ( ! $check_is_already_subscribed ) {
				/**
				 * Filter for insert subscriber
				 *
				 * @since 1.0.0
				 */
				if ( apply_filters( 'cwginstocknotifier_insert_subscriber', true, $post_data ) ) {
					$id = $obj->insert_subscriber();
					if ( $id ) {
						$obj->insert_data( $id );
						/**
						 * Filter for insert custom meta data
						 *
						 * @since 1.0.0
						 */
						$custom_datas = apply_filters( 'cwginstocknotifier_insert_custom_meta_data', array( 'subscriber_name', 'subscriber_phone', 'subscriber_phone_meta' ) );
						$obj->insert_custom_data( $id, $post_data, $custom_datas );
						$get_count = $obj->get_subscribers_count( $product_id, 'cwg_subscribed' );
						update_post_meta( $product_id, 'cwg_total_subscribers', $get_count );
						/**
						 * Actions after a subscriber is inserted
						 *
						 * @since 1.0.0
						 */
						do_action( 'cwginstock_after_insert_subscriber', $id, $post_data );
						// logger
						$logger = new CWG_Instock_Logger( 'success', "Subscriber #$get_email successfully subscribed - #$id" );
						$logger->record_log();
					}
				} else {
					/**
					 * Double opt-in
					 *
					 * @since 1.9
					 */
					do_action( 'cwginstocknotifier_double_optin', $post_data );
				}
			} else {
				$get_option = get_option( 'cwginstocksettings' );
				$already_sub_msg = __( 'Seems like you have already subscribed to this product', 'back-in-stock-notifier-for-woocommerce' );
				$error = isset( $get_option['already_subscribed'] ) && $get_option['already_subscribed'] ? $get_option['already_subscribed'] : $already_sub_msg;
				$raw_error = $error;
				$error = "<div class='cwginstockerror' style='color:red;'>$error</div>";
				/**
				 * Filter for HTML error subscription
				 *
				 * @since 1.0.0
				 */
				$error = apply_filters( 'cwginstock_error_subscription_html', $error, $raw_error, $post_data );
				$error_msg = array( 'msg' => $error );

				if ( ! $rest_api ) {
					wp_send_json( $error_msg, 200 );
				} else {
					echo json_encode( $error_msg );
					die();
				}
			}
		}

		// perform some action after insertion of subscriber

		public function perform_action_after_insertion( $id, $post_data ) {
			// send mail
			// settings data
			$option = get_option( 'cwginstocksettings' );
			$is_enabled = isset( $option['enable_success_sub_mail'] ) ? $option['enable_success_sub_mail'] : 0;
			$get_email = $post_data['user_email'];
			if ( '1' == $is_enabled || 1 == $is_enabled ) {
				$mailer = new CWG_Instock_Subscription( $id );
				$mailer->send();
				$logger = new CWG_Instock_Logger( 'success', "Mail sent to #$get_email for successful subscription - #$id" );
				$logger->record_log();
			}
		}

		private function verify_recaptcha_client_response( $post, $options ) {
			$verify_url = 'https://www.google.com/recaptcha/api/siteverify';
			$site_key = CWG_Instock_Bot_Protection::get_secret_key();
			$gresponse = $post['security'];
			$args = array(
				'body' => array(
					'secret' => $site_key,
					'response' => $gresponse,
				),
			);
			$response = wp_remote_post( $verify_url, $args );
			return $response;
		}

		private function verify_turnstile_client_response( $post, $options ) {
			$verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
			$secret_key = CWG_Instock_Bot_Protection::get_turnstile_secret_key();
			$tresponse = $post['security'];
			$args = array(
				'body' => array(
					'secret' => $secret_key,
					'response' => $tresponse,
				),
			);
			$response = wp_remote_post( $verify_url, $args );
			return $response;
		}

		public static function json_search_tags() {
			ob_start();

			check_ajax_referer( 'search-tags', 'security' );

			if ( ! current_user_can( 'edit_products' ) ) {
				wp_die( -1 );
			}

			$search_text = isset( $_GET['term'] ) ? wc_clean( wp_unslash( $_GET['term'] ) ) : '';

			if ( ! $search_text ) {
				wp_die();
			}

			$found_tags = array();
			$args = array(
				'taxonomy' => array( 'product_tag' ),
				'orderby' => 'id',
				'order' => 'ASC',
				'hide_empty' => true,
				'fields' => 'all',
				'name__like' => $search_text,
			);

			$terms = get_terms( $args );

			if ( $terms ) {
				foreach ( $terms as $term ) {
					$term->formatted_name = '';
					$term->formatted_name .= $term->name . ' (' . $term->count . ')';
					$found_tags[ $term->term_id ] = $term;
				}
			}
			/**
			 * Filter for search tags
			 *
			 * @since 1.0.0
			 */
			wp_send_json( apply_filters( 'woocommerce_json_search_found_tags', $found_tags ) );
		}

		public function register_rest_route() {
			register_rest_route(
				'back-in-stock/v1/subscriber',
				'/create/',
				array(
					'methods' => 'POST',
					'callback' => array( $this, 'ajax_submission_mode' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				)
			);
		}

		public function permission_callback( $request ) {
			if ( ! $request->get_header( 'x_wp_nonce' ) ) {
				return false;
			}
			return true;
		}

		public function ajax_submission_mode() {
			$request = $this->ajax_subscription( false, true );
			if ( isset( $request['msg'] ) && isset( $request['status'] ) ) {
				return new WP_REST_Response( $request['msg'], $request['status'] );
			}
		}

		public function cwg_ajax_verification() {
			if ( ! isset( $_POST['security'] ) || ( isset( $_POST['security'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['security'] ), 'cwg_trigger_popup_ajax' ) ) ) {
				if ( '1' == CWG_Instock_Bot_Protection::is_recaptcha_enabled() && CWG_Instock_Bot_Protection::is_recaptcha_v3() ) {
					$verify_gresponse = $this->verify_recaptcha_client_response( $_REQUEST, false );
					if ( is_wp_error( $verify_gresponse ) ) {
						esc_html_e( 'Unable to verify details, please try again after some time', 'back-in-stock-notifier-for-woocommerce' );
					} else {
						$gresponse_body = json_decode( wp_remote_retrieve_body( $verify_gresponse ) );
						$gresponse_status = $gresponse_body->success;
						if ( ! $gresponse_status ) {
							esc_html_e( 'Unable to verify details, please try again after some time', 'back-in-stock-notifier-for-woocommerce' );
						} else {
							echo do_shortcode( $this->subscribe_form_shortcode( $_POST ) );
						}
					}
				} else {
					esc_html_e( 'Unable to verify details, please try again after some time', 'back-in-stock-notifier-for-woocommerce' );
				}
			} else {
				echo do_shortcode( $this->subscribe_form_shortcode( $_POST ) );
			}
			die();
		}

		private function subscribe_form_shortcode( $post ) {

			$product_id = (int) ( isset( $post['product_id'] ) ? sanitize_text_field( $post['product_id'] ) : '' );
			$variation_id = (int) ( isset( $post['variation_id'] ) ? sanitize_text_field( $post['variation_id'] ) : '' );
			$shortcode = "[cwginstock_subscribe_form product_id='" . $product_id . "' variation_id='" . $variation_id . "']";
			return $shortcode;
		}

	}

	new CWG_Instock_Ajax();
}
