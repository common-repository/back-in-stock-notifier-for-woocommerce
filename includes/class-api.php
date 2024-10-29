<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_API' ) ) {

	class CWG_Instock_API {

		private $product_id;
		private $variation_id;
		private $subscriber_email;
		private $user_id;
		private $language;

		public function __construct( $product_id = 0, $variation_id = 0, $user_email = '', $user_id = 0, $language = 'en_US' ) {
			$this->product_id = $product_id;
			$this->variation_id = $variation_id;
			$this->subscriber_email = $user_email;
			$this->user_id = $user_id;
			$this->language = $language;
		}

		public function get_list_of_subscribers( $relation = 'OR' ) {
			$args = array(
				'post_type' => 'cwginstocknotifier',
				'fields' => 'ids',
				'posts_per_page' => -1,
				'post_status' => 'cwg_subscribed',
				'order' => 'ASC',
			);
			$meta_query = array(
				'relation' => $relation,
				array(
					'key' => 'cwginstock_product_id',
					'value' => ( $this->product_id > '0' || $this->product_id ) ? $this->product_id : 'no_data_found',
				),
				array(
					'key' => 'cwginstock_variation_id',
					'value' => ( 'AND' == $relation || $this->variation_id > '0' || $this->variation_id > 0 ) ? $this->variation_id : 'no_data_found',
				),
			);
			/**
			 * Filter to modify the meta query used to retrieve the list of subscribers
			 *
			 * @since 1.0.0
			 */
			$args['meta_query'] = apply_filters( 'cwginstock_metaquery', $meta_query );
			$get_posts = get_posts( $args );

			return $get_posts;
		}

		public function insert_subscriber( $status = 'cwg_subscribed' ) {
			$args = array(
				'post_title' => $this->subscriber_email,
				'post_type' => 'cwginstocknotifier',
				'post_status' => $status,
			);

			$id = wp_insert_post( $args );
			if ( ! is_wp_error( $id ) ) {
				return $id;
			} else {
				return false;
			}
		}

		public function insert_data( $id ) {
			$default_data = array(
				'cwginstock_product_id' => $this->product_id,
				'cwginstock_variation_id' => $this->variation_id,
				'cwginstock_subscriber_email' => $this->subscriber_email,
				'cwginstock_user_id' => $this->user_id,
				'cwginstock_language' => $this->language,
				'cwginstock_pid' => $this->variation_id > '0' || $this->variation_id > 0 ? $this->variation_id : $this->product_id,
			);
			foreach ( $default_data as $key => $value ) {
				update_post_meta( $id, $key, $value );
			}
		}

		public function insert_custom_data( $id, $post_data, $custom_datas ) {
			if ( is_array( $custom_datas ) && ! empty( $custom_datas ) ) {
				foreach ( $custom_datas as $each_data_key ) {
					if ( isset( $post_data[ $each_data_key ] ) && ! empty( $post_data[ $each_data_key ] ) ) {
						update_post_meta( $id, 'cwginstock_' . $each_data_key, wc_clean( $post_data[ $each_data_key ] ) );
					}
				}
			}
		}

		public function is_already_subscribed( $status = array( 'cwg_subscribed' ) ) {
			$args = array(
				'post_type' => 'cwginstocknotifier',
				'fields' => 'ids',
				'posts_per_page' => -1,
				'post_status' => $status,
			);
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key' => 'cwginstock_pid',
					'value' => $this->variation_id > '0' || $this->variation_id > 0 ? $this->variation_id : $this->product_id,
				),
				array(
					'key' => 'cwginstock_subscriber_email',
					'value' => $this->subscriber_email,
				),
			);
			$args['meta_query'] = $meta_query;
			$get_posts = get_posts( $args );
			return $get_posts;
		}

		public function is_already_doubleoptin() {
			$args = array(
				'post_type' => 'cwginstocknotifier',
				'fields' => 'ids',
				'posts_per_page' => -1,
				'post_status' => 'cwg_doubleoptin',
			);
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key' => 'cwginstock_pid',
					'value' => $this->variation_id > '0' || $this->variation_id > 0 ? $this->variation_id : $this->product_id,
				),
				array(
					'key' => 'cwginstock_subscriber_email',
					'value' => $this->subscriber_email,
				),
			);
			$args['meta_query'] = $meta_query;
			$get_posts = get_posts( $args );
			return $get_posts;
		}

		public function update_subscriber( $subscriber_id, $status ) {
			$args = array(
				'ID' => $subscriber_id,
				'post_type' => 'cwginstocknotifier',
				'post_status' => $status,
			);
			$id = wp_update_post( $args );
			return $id;
		}

		public function subscriber_subscribed( $subscribe_id ) {
			$args = array(
				'ID' => $subscribe_id,
				'post_type' => 'cwginstocknotifier',
				'post_status' => 'cwg_subscribed',
			);
			$id = wp_update_post( $args );
			return $id;
		}

		public function subscriber_unsubscribed( $subscribe_id ) {
			$args = array(
				'ID' => $subscribe_id,
				'post_type' => 'cwginstocknotifier',
				'post_status' => 'cwg_unsubscribed',
			);
			$id = wp_update_post( $args );
			return $id;
		}

		public function mail_sent_status( $subscribe_id ) {
			$args = array(
				'ID' => $subscribe_id,
				'post_type' => 'cwginstocknotifier',
				'post_status' => 'cwg_mailsent',
			);
			$id = wp_update_post( $args );
			return $id;
		}

		public function mail_not_sent_status( $subscribe_id ) {
			$args = array(
				'ID' => $subscribe_id,
				'post_type' => 'cwginstocknotifier',
				'post_status' => 'cwg_mailnotsent',
			);
			$id = wp_update_post( $args );
			return $id;
		}

		public function display_product_name( $id ) {

			$pid = get_post_meta( $id, 'cwginstock_pid', true );

			$formatted_name = '';
			if ( $pid ) {
				$obj = wc_get_product( $pid );
				if ( $obj ) {
					$formatted_name = $obj->get_formatted_name();
				}
			}
			return $formatted_name;
		}

		public function display_product_link( $id ) {
			$permalink = '';
			if ( ! get_post_meta( $id, 'cwginstock_bypass_pid', true ) ) {
				$pid = get_post_meta( $id, 'cwginstock_pid', true );
			} else {
				$pid = get_post_meta( $id, 'cwginstock_bypass_pid', true );
			}
			$product = wc_get_product( $pid );
			if ( $product ) {
				$permalink = $product->get_permalink();
			}
			return $permalink;
		}

		public function display_only_product_name( $id ) {
			$name = '';
			if ( ! get_post_meta( $id, 'cwginstock_bypass_pid', true ) ) {
				$pid = get_post_meta( $id, 'cwginstock_pid', true );
			} else {
				$pid = get_post_meta( $id, 'cwginstock_bypass_pid', true );
			}

			$product = wc_get_product( $pid );
			if ( $product ) {
				$name = $product->get_name();
			}
			return $name;
		}

		public function get_product_sku( $id ) {
			$sku = '';
			if ( ! get_post_meta( $id, 'cwginstock_bypass_pid', true ) ) {
				$pid = get_post_meta( $id, 'cwginstock_pid', true );
			} else {
				$pid = get_post_meta( $id, 'cwginstock_bypass_pid', true );
			}

			$product = wc_get_product( $pid );
			if ( $product ) {
				$sku = $product->get_sku();
			}
			return $sku;
		}

		public function get_product_price( $id ) {
			$price = '';
			if ( ! get_post_meta( $id, 'cwginstock_bypass_id', true ) ) {
				$pid = get_post_meta( $id, 'cwginstock_pid', true );
			} else {
				$pid = get_post_meta( $id, 'cwginstock_bypass_pid', true );
			}
			$product = wc_get_product( $pid );
			if ( $product ) {
				$price = $product->get_price();
				$price = wc_price( $price );
			}
			return $price;
		}

		public function get_product_image( $id, $size = 'woocommerce_thumbnail' ) {
			$image = '';
			if ( ! get_post_meta( $id, 'cwginstock_bypass_pid', true ) ) {
				$pid = get_post_meta( $id, 'cwginstock_pid', true );
			} else {
				$pid = get_post_meta( $id, 'cwginstock_bypass_pid', true );
			}

			$product = wc_get_product( $pid );
			if ( $product ) {
				$image = $product->get_image( $size );
			}
			return $image;
		}

		public function get_subscriber_name( $subscriber_id ) {
			$subscriber_name = get_post_meta( $subscriber_id, 'cwginstock_subscriber_name', true );
			$email = get_post_meta( $subscriber_id, 'cwginstock_subscriber_email', true );
			if ( '' == $subscriber_name ) {
				$get_user_id = get_post_meta( $subscriber_id, 'cwginstock_user_id', true );
				if ( $get_user_id > 0 ) {
					$subscriber_name = $this->get_name( $get_user_id );
				} else {
					$subscriber_name = $this->get_name_by_email( $email );
				}
			}
			return $subscriber_name;
		}

		public function get_subscriber_firstname( $subscriber_id ) {
			$subscriber_name = get_post_meta( $subscriber_id, 'cwginstock_subscriber_name', true );
			$email = get_post_meta( $subscriber_id, 'cwginstock_subscriber_email', true );

			if ( '' == $subscriber_name ) {
				$get_user_id = get_post_meta( $subscriber_id, 'cwginstock_user_id', true );
				if ( $get_user_id > 0 ) {
					$subscriber_name = $this->get_name( $get_user_id, 'first' );
				} else {
					$subscriber_name = $this->get_name_by_email( $email, 'first' );
				}
			} else {
				$get_firstname = explode( ' ', $subscriber_name );
				$get_firstname = is_array( $get_firstname ) && isset( $get_firstname[0] ) ? $get_firstname[0] : $subscriber_name;
				$subscriber_name = $get_firstname;
			}
			return $subscriber_name;
		}

		public function get_subscriber_lastname( $subscriber_id ) {
			$subscriber_name = get_post_meta( $subscriber_id, 'cwginstock_subscriber_name', true );
			$email = get_post_meta( $subscriber_id, 'cwginstock_subscriber_email', true );

			if ( '' == $subscriber_name ) {
				$get_user_id = get_post_meta( $subscriber_id, 'cwginstock_user_id', true );
				if ( $get_user_id > 0 ) {
					$subscriber_name = $this->get_name( $get_user_id, 'last' );
				} else {
					$subscriber_name = $this->get_name_by_email( $email, 'last' );
				}
			} else {
				$get_lastname = explode( ' ', $subscriber_name );
				$get_lastname = is_array( $get_lastname ) && isset( $get_lastname[1] ) ? $get_lastname[1] : $subscriber_name;
				$subscriber_name = $get_lastname;
			}
			return $subscriber_name;
		}

		public function get_subscriber_phone( $subscriber_id ) {
			$subscriber_phone = get_post_meta( $subscriber_id, 'cwginstock_subscriber_phone', true );
			return $subscriber_phone;
		}

		public function get_match_based_on_prefix_suffix( $string, $prefix = '{', $suffix = '}' ) {
			$prefix = preg_quote( $prefix );
			$suffix = preg_quote( $suffix );
			if ( preg_match_all( "!$prefix(.*?)$suffix!", $string, $matches ) ) {
				return $matches[1];
			}
			return array();
		}

		public function get_cart_link( $id ) {
			if ( ! get_post_meta( $id, 'cwginstock_bypass_pid', true ) ) {
				$pid = get_post_meta( $id, 'cwginstock_pid', true );
			} else {
				$pid = get_post_meta( $id, 'cwginstock_bypass_pid', true );
			}
			$url = '';
			if ( $pid ) {
				$object = wc_get_product( $pid );
				if ( $object ) {
					$url = $object->add_to_cart_url();
					if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
						$get_permalink = $object->get_permalink();
						if ( $object->is_type( 'variation' ) ) {
							$get_parent_id = $object->get_parent_id();
							$query_arg = array(
								'variation_id' => $pid,
								'add-to-cart' => $get_parent_id,
							);
						} else {
							$query_arg = array( 'add-to-cart' => $pid );
						}
						// generate a URL
						$url = esc_url_raw( add_query_arg( $query_arg, $get_permalink ) );
					}
				}
			}
			/**
			 * Filter for cart link
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'cwginstock_cart_link', $url, $pid, $id );
		}

		public function is_user_exists() {
			$get_user = get_user_by( 'email', $this->subscriber_email );
			if ( $get_user ) {
				return true;
			} else {
				return false;
			}
		}

		public function post_data_validation( $post ) {
			if ( isset( $post['dataobj']['phone_field_error'] ) ) {
				unset( $post['dataobj']['phone_field_error'] );
			}

			$post_data = array();
			if ( is_array( $post ) && ! empty( $post ) ) {
				foreach ( $post as $key => $value ) {
					if ( is_array( $value ) && ! empty( $value ) ) {
						foreach ( $value as $newkey => $newvalue ) {
							$post_data[ $key ][ $newkey ] = $this->format_field( $newkey, $newvalue );
						}
					} else {
						$post_data[ $key ] = $this->format_field( $key, $value );
					}
				}
			}
			return $post_data;
		}

		public function format_field( $key, $value ) {
			if ( ! is_string( $value ) ) {
				return '';
			}
			$list_of_fields = array(
				'product_id' => intval( sanitize_text_field( $value ) ),
				'variation_id' => intval( sanitize_text_field( $value ) ),
				'user_id' => intval( sanitize_text_field( $value ) ),
				'user_email' => sanitize_email( $value ),
			);
			if ( isset( $list_of_fields[ $key ] ) ) {
				return $list_of_fields[ $key ];
			} else {
				return sanitize_text_field( $value );
			}
		}

		public function get_user_email( $user_id ) {
			// user email
			if ( $user_id > 0 || $user_id > '0' ) {
				$get_user = get_user_by( 'id', $user_id );
				if ( $get_user ) {
					return $get_user->user_email;
				}
			}
			return '';
		}

		public function get_name( $user_id, $full = 'both' ) {
			// get name of user
			if ( $user_id > 0 || $user_id > '0' ) {
				$get_user = get_user_by( 'id', $user_id );
				if ( $get_user ) {
					if ( 'both' == $full ) {
						$full_name = $get_user->first_name . ' ' . $get_user->last_name;
					} elseif ( 'first' == $full ) {
						$full_name = $get_user->first_name;
					} else {
						// lastname
						$full_name = $get_user->last_name;
					}
					return $full_name;
				}
			}
			return '';
		}

		public function get_name_by_email( $email, $full = 'both' ) {
			// get name by email
			if ( $email ) {
				$get_user = get_user_by( 'email', $email );
				if ( $get_user ) {
					if ( 'both' == $full ) {
						$full_name = $get_user->first_name . ' ' . $get_user->last_name;
					} elseif ( 'first' == $full ) {
						$full_name = $get_user->first_name;
					} else {
						// lastname
						$full_name = $get_user->last_name;
					}
					return $full_name;
				}
			}
			return '';
		}

		public function get_subscribers_count( $product_id, $status = 'any' ) {
			$args = array(
				'post_type' => 'cwginstocknotifier',
				'post_status' => $status,
				'meta_query' => array(
					array(
						'key' => 'cwginstock_product_id',
						'value' => array( $product_id ),
						'compare' => 'IN',
					),
				),
				'numberposts' => -1,
			);
			$query = get_posts( $args );
			return count( $query );
		}

		public function get_meta_values( $key = '', $type = 'post', $status = 'cwg_subscribed' ) {
			global $wpdb;
			if ( empty( $key ) ) {
				return;
			}
			$meta_value = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_status = %s AND p.post_type = %s", $key, $status, $type ) );
			return $meta_value;
		}

		public function sanitize_text_field( $value ) {
			return sanitize_text_field( $value );
		}

		public function sanitize_textarea_field( $value ) {
			$value = wp_kses(
				$value,
				array(
					'a' => array(
						'href' => array(),
						'title' => array(),
						'class' => array(),
						'id' => array(),
						'style' => array(),
						'target' => array(),
					),
					'br' => array(),
					'em' => array(),
					'strong' => array(
						'class' => array(),
						'id' => array(),
						'style' => array(),
					),
					'h1' => array(
						'class' => array(),
						'id' => array(),
						'style' => array(),
					),
					'h2' => array(
						'class' => array(),
						'id' => array(),
						'style' => array(),
					),
					'h3' => array(
						'class' => array(),
						'id' => array(),
						'style' => array(),
					),
					'h4' => array(
						'class' => array(),
						'id' => array(),
						'style' => array(),
					),
					'h5' => array(
						'class' => array(),
						'id' => array(),
						'style' => array(),
					),
					'h6' => array(
						'class' => array(),
						'id' => array(),
						'style' => array(),
					),
					'img' => array(
						'class' => array(),
						'id' => array(),
						'style' => array(),
						'src' => array(),
						'alt' => array(),
						'height' => array(),
						'width' => array(),
					),
					'label' => array(
						'for' => array(),
					),
					'ul' => array(
						'id' => array(),
						'class' => array(),
						'style' => array(),
					),
					'li' => array(
						'id' => array(),
						'class' => array(),
						'style' => array(),
					),
					'ol' => array(
						'id' => array(),
						'class' => array(),
						'style' => array(),
					),
					'p' => array(
						'id' => array(),
						'class' => array(),
						'style' => array(),
					),
					'b' => array(
						'id' => array(),
						'class' => array(),
						'style' => array(),
					),
					'table' => array(
						'align' => array(),
						'bgcolor' => array(),
						'border' => array(),
						'cellpadding' => array(),
						'cellspacing' => array(),
						'class' => array(),
						'dir' => array(),
						'frame' => array(),
						'id' => array(),
						'rules' => array(),
						'style' => array(),
						'width' => array(),
					),
					'td' => array(
						'abbr' => array(),
						'align' => array(),
						'bgcolor' => array(),
						'class' => array(),
						'colspan' => array(),
						'dir' => array(),
						'height' => array(),
						'id' => array(),
						'lang' => array(),
						'rowspan' => array(),
						'scope' => array(),
						'style' => array(),
						'valign' => array(),
						'width' => array(),
					),
					'th' => array(
						'abbr' => array(),
						'align' => array(),
						'background' => array(),
						'bgcolor' => array(),
						'class' => array(),
						'colspan' => array(),
						'dir' => array(),
						'height' => array(),
						'id' => array(),
						'lang' => array(),
						'scope' => array(),
						'style' => array(),
						'valign' => array(),
						'width' => array(),
					),
					'tr' => array(
						'align' => array(),
						'bgcolor' => array(),
						'class' => array(),
						'dir' => array(),
						'id' => array(),
						'style' => array(),
						'valign' => array(),
					),
					'div' => array(
						'id' => array(),
						'class' => array(),
						'style' => array(),
					),
				)
			);
			return $value;
		}

	}

}
