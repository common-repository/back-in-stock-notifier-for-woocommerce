<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_Notifier_Product' ) ) {

	class CWG_Instock_Notifier_Product {

		public function __construct() {
			add_action( 'woocommerce_simple_add_to_cart', array( $this, 'display_in_simple_product' ), 31 );
			add_action( 'woocommerce_subscription_add_to_cart', array( $this, 'display_in_simple_product' ), 31 );
			add_action( 'woocommerce_bundle_add_to_cart', array( $this, 'display_in_simple_product' ), 31 );
			add_action( 'woocommerce_woosb_add_to_cart', array( $this, 'display_in_simple_product' ), 31 );
			add_action( 'woocommerce_composite_add_to_cart', array( $this, 'display_in_simple_product' ), 31 );
			add_action( 'woocommerce_after_variations_form', array( $this, 'display_in_no_variation_product' ) );
			// add_action('woocommerce_grouped_add_to_cart', array($this, 'display_in_simple_product'), 32);
			add_filter( 'woocommerce_available_variation', array( $this, 'display_in_variation' ), 999, 3 );
			// some theme variation disabled by default if it is out of stock so for that workaround solution
			add_filter( 'woocommerce_variation_is_active', array( $this, 'enable_disabled_variation_dropdown' ), 100, 2 );
			// hide out of stock products from catalog is checked bypass to display variation dropdown instead of hide
			add_filter( 'option_woocommerce_hide_out_of_stock_items', array( $this, 'display_out_of_stock_products_in_variable' ), 999 );
			// support for grouped products
			add_filter( 'woocommerce_grouped_product_columns', array( $this, 'add_product_column_grouped' ), 10, 2 );
			add_filter( 'woocommerce_grouped_product_list_column_price', array( $this, 'display_in_grouped_product' ), 10, 2 );
			// shortcode support
			add_shortcode( 'cwginstock_subscribe_form', array( $this, 'subscribe_form_shortcode' ) );
			add_action( 'wpto_column_bottom', array( $this, 'compatible_with_producttable_pro' ), 10, 5 );
			add_action( 'woocommerce_after_shop_loop_item', array( $this, 'display_popup_button_in_catalog_page' ), 999 );
			// WPC Variation Radio Button Plugin Compatible for variation
			add_filter( 'woovr_variation_availability', array( $this, 'display_subscribe_form_in_wpcradiobutton' ), 10, 2 );
			add_action( 'woocommerce_event_ticket_manager_add_to_cart', array( $this, 'display_in_simple_product' ), 31 );
			// template
			add_filter( 'cwginstock_locate_template', array( $this, 'force_template_from_plugin' ), 10, 5 );
			add_filter( 'cwginstock_success_subscription_html', array( $this, 'replace_shortcode_for_message' ), 10, 3 );
			add_filter( 'cwginstock_error_subscription_html', array( $this, 'replace_shortcode_for_message' ), 10, 3 );
		}

		public function display_in_simple_product() {
			global $product;
			/**
			 * Displaying the subscribe box related to a simple product
			 *
			 * @since 1.0.0
			 */
			$display_filter = apply_filters( 'cwginstock_display_subscribe_form', true, $product, array() );
			echo do_shortcode( $this->display_subscribe_box( $product, array(), $display_filter ) ?? '' );
		}

		public function add_product_column_grouped( $columns, $product ) {
			$columns[] = 'cwg_subscribe_form';
			return $columns;
		}

		public function display_in_grouped_product( $value, $child ) {
			/**
			 * Displaying the subscribe box related to a grouped products.
			 *
			 * @since 1.0.0
			 */
			$display_filter = apply_filters( 'cwginstock_display_subscribe_form', true, $child, array() );
			$value = $value . $this->display_subscribe_box( $child, array(), $display_filter );
			return $value;
		}

		public function compatible_with_producttable_pro( $keyword, $table_ID, $settings, $column_settings, $product ) {
			/**
			 * Comptible with producttable pro.
			 *
			 * @since 1.0.0
			 */
			$display_filter = apply_filters( 'cwginstock_display_subscribe_form', true, $product, array() );
			// checks the value of the variable $keyword is equal to the string 'stock" 
			if ( 'stock' == $keyword ) {
				echo do_shortcode( $this->display_subscribe_box( $product, array(), $display_filter ) ?? '' );
			}
		}

		public function display_in_no_variation_product() {
			global $product;
			$product_type = $product->get_type();
			// Get Available variations?
			if ( 'variable' == $product_type ) {
				/**
				 * Filter allows to adjust the threshold at which the variation selection method is switched.
				 *
				 * @since 1.0.0
				 */
				$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
				$get_variations = $get_variations ? $product->get_available_variations() : false;
				// Checks if the variable $get_variations is false, implying that there are no variations available for the product
				if ( ! $get_variations ) {
					/**
					 * Filter for display subscribe form
					 *
					 * @since 1.0.0
					 */
					$display_filter = apply_filters( 'cwginstock_display_subscribe_form', true, $product, array() );
					echo do_shortcode( $this->display_subscribe_box( $product, array(), $display_filter ) ?? '' );
				}
			}
		}

		public function display_subscribe_box( $product, $variation = array(), $display = true ) {
			$get_option = get_option( 'cwginstocksettings' );
			$visibility_backorder = isset( $get_option['show_on_backorders'] ) && '1' == $get_option['show_on_backorders'] ? true : false;
			$stock_status = $variation ? $variation->get_stock_status() : $product->get_stock_status();

			$check_guest_visibility = isset( $get_option['hide_form_guests'] ) && '' != $get_option['hide_form_guests'] && ! is_user_logged_in() ? false : true;
			$check_member_visibility = isset( $get_option['hide_form_members'] ) && '' != $get_option['hide_form_members'] && is_user_logged_in() ? false : true;
			$guest_message = isset( $get_option['hide_form_for_guest_msg'] ) ? $get_option['hide_form_for_guest_msg'] : '';

			$product_id = $product->get_id();
			$variation_class = '';
			if ( $variation ) {
				$variation_id = $variation->get_id();
				$variation_class = "cwginstock-subscribe-form-$variation_id";
			} else {
				$variation_id = 0;
			}

			/**
			 * Default allowed status
			 *
			 * @since 1.0.0
			 */
			$default_stock_status = apply_filters( 'cwg_default_allowed_status', array( 'outofstock' ), $product, $variation );
			if ( ! $check_guest_visibility && ! is_user_logged_in() && 'outofstock' == $stock_status ) {
				if ( ! empty( $guest_message ) ) {
					ob_start();
					echo '<p class="cwg-guest-message">' . do_shortcode( $guest_message ) . '</p>';
					return ob_get_clean();
				}
				return;
			}

			if ( $check_guest_visibility && $check_member_visibility && ( $this->is_viewable( $product_id, $variation_id ) && $this->is_viewable_for_category( $product_id ) ) && $this->visibility_on_regular_or_sale( $product, $variation ) && $this->is_viewable_for_product_tag( $product_id ) ) {
				if ( $display ) {
					if ( ! $variation && ! $product->is_in_stock() || ( ! $variation && in_array( $stock_status, $default_stock_status ) ) || ( ( ! $variation && ( ( $product->managing_stock() && $product->backorders_allowed() && $product->is_on_backorder( 1 ) ) || $product->is_on_backorder( 1 ) ) && $visibility_backorder ) ) ) {
						return $this->html_subscribe_form( $product );
					} elseif ( $variation && ! $variation->is_in_stock() || ( $variation && in_array( $stock_status, $default_stock_status ) ) || ( ( $variation && ( ( $variation->managing_stock() && $variation->backorders_allowed() && $variation->is_on_backorder( 1 ) ) || $variation->is_on_backorder( 1 ) ) && $visibility_backorder ) ) ) {
						return $this->html_subscribe_form( $product, $variation );
					}
				} else {
					ob_start();

					if ( ! $variation && ! $product->is_in_stock() || ( ! $variation && in_array( $stock_status, $default_stock_status ) ) || ( ( ! $variation && ( ( $product->managing_stock() && $product->backorders_allowed() && $product->is_on_backorder( 1 ) ) || $product->is_on_backorder( 1 ) ) && $visibility_backorder ) ) ) {
						/**
						 * 'cwginstock_custom_form' action with two parameters: $product and $variation
						 *
						 * @since 1.0.0
						 */
						do_action( 'cwginstock_custom_form', $product, $variation );
					} elseif ( $variation && ! $variation->is_in_stock() || ( $variation && in_array( $stock_status, $default_stock_status ) ) || ( ( $variation && ( ( $variation->managing_stock() && $variation->backorders_allowed() && $variation->is_on_backorder( 1 ) ) || $variation->is_on_backorder( 1 ) ) && $visibility_backorder ) ) ) {
						/**
						 * 'cwginstock_custom_form' action with two parameters: $product and $variation
						 *
						 * @since 1.0.0
						 */
						do_action( 'cwginstock_custom_form', $product, $variation );
					}
					return ob_get_clean();
				}
			}
		}

		public function html_subscribe_form( $product, $variation = array() ) {
			$get_option = get_option( 'cwginstocksettings' );
			$check_guest_visibility = isset( $get_option['hide_form_guests'] ) && '' != $get_option['hide_form_guests'] && ! is_user_logged_in() ? false : true;
			$check_member_visibility = isset( $get_option['hide_form_members'] ) && '' != $get_option['hide_form_members'] && is_user_logged_in() ? false : true;
			$name_field_visibility = isset( $get_option['hide_name_field'] ) && '' != $get_option['hide_name_field'] ? false : true;
			$phone_field_visibility = isset( $get_option['show_phone_field'] ) && '' != $get_option['show_phone_field'] ? true : false;
			$product_id = $product->get_id();
			$variation_class = '';
			$dynamic_wrapper_class = '';
			if ( $variation ) {
				$variation_id = $variation->get_id();
				$variation_class = "cwginstock-subscribe-form-$variation_id";
				$stock_status = $variation->get_stock_status();
				$stock_quantity = $variation->get_stock_quantity();
			} else {
				$variation_id = 0;
				$stock_status = $product->get_stock_status();
				$stock_quantity = $product->get_stock_quantity();
			}
			$dynamic_wrapper_class = 'cwginstock-' . $stock_quantity . $stock_status;

			// wp_enqueue_script('cwginstock_jquery_validation');
			/**
			 * Before Subscribe form
			 *
			 * @since 1.0.0
			 */
			do_action( 'cwg_instock_before_subscribe_form' );

			$security = wp_create_nonce( 'codewoogeek-product_id-' . $product_id );
			ob_start();
			$name_placeholder = isset( $get_option['name_placeholder'] ) && '' != $get_option['name_placeholder'] ? $get_option['name_placeholder'] : __( 'Your Name', 'back-in-stock-notifier-for-woocommerce' );
			$placeholder = isset( $get_option['form_placeholder'] ) && '' != $get_option['form_placeholder'] ? $get_option['form_placeholder'] : __( 'Your Email Address', 'back-in-stock-notifier-for-woocommerce' );
			$button_label = isset( $get_option['button_label'] ) && '' != $get_option['button_label'] ? $get_option['button_label'] : __( 'Subscribe Now', 'back-in-stock-notifier-for-woocommerce' );
			/**
			 * Filter for sumbit button label
			 *
			 * @since 4.0.1
			 */
			$btn_label_filter = apply_filters( 'cwginstock_submit_btn_label', $button_label, $product, $variation );
			$instock_api = new CWG_Instock_API();
			$disable_prefill_data = isset( $get_option['disable_prefill_data'] ) && '1' == $get_option['disable_prefill_data'];

			$email = ! $disable_prefill_data && is_user_logged_in() ? $instock_api->get_user_email( get_current_user_id() ) : '';
			$subscriber_name = ! $disable_prefill_data && is_user_logged_in() ? $instock_api->get_name( get_current_user_id() ) : '';
			$subscriber_name = trim( $subscriber_name ) != '' ? $subscriber_name : '';
			$subscriber_phone = '';

			$args = array(
				'variation_class' => $variation_class,
				'dynamic_wrapper_class' => $dynamic_wrapper_class,
				'get_option' => $get_option,
				'instock_api' => $instock_api,
				'name_field_visibility' => $name_field_visibility,
				'phone_field_visibility' => $phone_field_visibility,
				'product_id' => $product_id,
				'variation_id' => $variation_id,
				'security' => $security,
				'name_placeholder' => $name_placeholder,
				'placeholder' => $placeholder,
				'subscriber_name' => $subscriber_name,
				'email' => $email,
				'subscriber_phone' => $subscriber_phone,
				'button_label' => $btn_label_filter,
			);
			$template = new CWG_Template( 'default-form.php', $args );
			$template->get_template();

			return ob_get_clean();
		}

		public function display_in_variation( $atts, $product, $variation ) {
			$get_stock = $atts['availability_html'];
			/**
			 * Displaying the subscribe box related to a variable products.
			 *
			 * @since 1.0.0
			 */
			$display_filter = apply_filters( 'cwginstock_display_subscribe_form', true, $product, $variation );
			$atts['availability_html'] = $get_stock . $this->display_subscribe_box( $product, $variation, $display_filter );
			return $atts;
		}

		public function enable_disabled_variation_dropdown( $active, $variation ) {
			$option = get_option( 'cwginstocksettings' );
			$ignore_disabled_variation = isset( $option['ignore_disabled_variation'] ) && '1' == $option['ignore_disabled_variation'] ? true : false;
			if ( ! $ignore_disabled_variation ) {
				// if it is false then enable disabled out of stock variation from theme
				$active = true;
			}
			return $active;
		}

		public function is_viewable( $product_id, $variation_id = 0 ) {
			$option = get_option( 'cwginstocksettings' );
			$selected_products = isset( $option['specific_products'] ) ? $option['specific_products'] : array();
			$product_visibility_mode = isset( $option['specific_products_visibility'] ) ? $option['specific_products_visibility'] : '';
			if ( ( is_array( $selected_products ) && ! empty( $selected_products ) ) && '' != $product_visibility_mode ) {
				if ( $variation_id > 0 ) {
					// $product_visibility_mode 1 is for show and 2 is for hide
					if ( '1' == $product_visibility_mode && ( ! in_array( $variation_id, $selected_products ) && ! in_array( $product_id, $selected_products ) ) ) {
						return false;
					} elseif ( '2' == $product_visibility_mode && ( in_array( $product_id, $selected_products ) || in_array( $variation_id, $selected_products ) ) ) {
						return false;
					}
				} else {
					if ( '1' == $product_visibility_mode && ! in_array( $product_id, $selected_products ) ) {
						return false;
					} elseif ( '2' == $product_visibility_mode && in_array( $product_id, $selected_products ) ) {
						return false;
					}
				}
			}
			return true;
		}

		public function is_viewable_for_category( $product_id ) {
			$option = get_option( 'cwginstocksettings' );
			$selected_categories = isset( $option['specific_categories'] ) ? $option['specific_categories'] : array();
			$categories_visibility_mode = isset( $option['specific_categories_visibility'] ) ? $option['specific_categories_visibility'] : '';

			if ( ( is_array( $selected_categories ) && ! empty( $selected_categories ) ) && '' != $categories_visibility_mode ) {
				$terms = wp_get_post_terms( $product_id, array( 'product_cat' ), array( 'fields' => 'slugs' ) );
				if ( $terms ) {
					// if any value matched with settings then it will return matched values if not it will return only empty value
					$intersect = array_intersect( $terms, $selected_categories );
					// $categories_visibility_mode 1 is for show and 2 is for hide
					if ( '1' == $categories_visibility_mode && empty( $intersect ) ) {
						return false;
					} elseif ( '2' == $categories_visibility_mode && ! empty( $intersect ) ) {
						return false;
					}
				}
			}
			return true;
		}

		public function is_viewable_for_product_tag( $product_id ) {
			$option = get_option( 'cwginstocksettings' );
			$selected_tags = isset( $option['specific_tags'] ) ? $option['specific_tags'] : array();
			$tags_visibility_mode = isset( $option['specific_tags_visibility'] ) ? $option['specific_tags_visibility'] : '';

			if ( ( is_array( $selected_tags ) && ! empty( $selected_tags ) ) && '' != $tags_visibility_mode ) {
				$terms = wp_get_post_terms( $product_id, array( 'product_tag' ), array( 'fields' => 'slugs' ) );
				if ( $terms ) {
					// if any value matched with settings then it will return matched values if not it will return only empty value
					$intersect = array_intersect( $terms, $selected_tags );
					// $categories_visibility_mode 1 is for show and 2 is for hide
					if ( '1' == $tags_visibility_mode && empty( $intersect ) ) {
						return false;
					} elseif ( '2' == $tags_visibility_mode && ! empty( $intersect ) ) {
						return false;
					}
				} elseif ( empty( $terms ) && '1' == $tags_visibility_mode ) {
					// somewhere settings configured and set the visibility to show then hide it in current product
					return false;
				}
			}
			return true;
		}

		public function visibility_on_regular_or_sale( $product, $variation ) {
			$option = get_option( 'cwginstocksettings' );
			$hide_on_regular = isset( $option['hide_on_regular'] ) && '1' == $option['hide_on_regular'] ? true : false;
			$hide_on_sale = isset( $option['hide_on_sale'] ) && '1' == $option['hide_on_sale'] ? true : false;
			$check_is_on_sale = $variation ? $variation->is_on_sale() : $product->is_on_sale();
			$visibility = ( ( $hide_on_regular && ! $check_is_on_sale ) || ( $hide_on_sale && $check_is_on_sale ) ) ? false : true;
			return $visibility;
		}

		public function display_out_of_stock_products_in_variable( $value ) {
			global $wp_query;
			$option = get_option( 'cwginstocksettings' );
			$ignore_wc_visibility = isset( $option['ignore_wc_visibility'] ) && '1' == $option['ignore_wc_visibility'] ? true : false;
			if ( ! class_exists( 'WooCommerce' ) ) {
				// to avoid fatal error is_product conflict with other plugins like boost sales etc
				return $value;
			}
			if ( $wp_query && ! is_admin() && $ignore_wc_visibility && is_product() ) {
				// remove restriction only on single product page and followed by our settings page
				return 'no';
			}
			return $value;
		}

		public function subscribe_form_shortcode( $atts ) {
			ob_start();
			$att = shortcode_atts(
				array(
					'product_id' => '',
					'variation_id' => '',
				),
				$atts
			);
			$product_id = isset( $att['product_id'] ) ? (int) $att['product_id'] : false;
			$variation_id = isset( $att['variation_id'] ) ? (int) $att['variation_id'] : false;

			if ( $variation_id && ( $variation_id > 0 ) ) {
				$product = wc_get_product( $product_id );
				$variation = wc_get_product( $variation_id );
				if ( $product && $variation ) {
					add_filter( 'cwginstock_bypass_recaptcha', array( $this, 'bypass_recaptcha_for_variation' ), 10, 3 );
					echo do_shortcode( $this->display_subscribe_box( $product, $variation ) ?? '' );
				}
			} elseif ( $product_id && ( $product_id > 0 ) ) {
				$product = wc_get_product( $product_id );
				if ( $product ) {
					echo do_shortcode( $this->display_subscribe_box( $product ) ?? '' );
				}
			}
			return ob_get_clean();
		}

		public function bypass_recaptcha_for_variation( $bool, $product_id, $variation_id ) {
			return false;
		}

		public function display_popup_button_in_catalog_page() {
			global $product;
			if ( $product ) {
				$get_option = get_option( 'cwginstocksettings' );
				$visibility_backorder = isset( $get_option['show_on_backorders'] ) && '1' == $get_option['show_on_backorders'] ? true : false;
				$display_popup = isset( $get_option['show_subscribe_button_catalog'] ) && '1' == $get_option['show_subscribe_button_catalog'] ? true : false;
				$id = $product->get_id();
				$product = wc_get_product( $id );
				$variation = array();
				$is_not_variation = $product && $product->is_type( 'variation' ) || $product->is_type( 'variable' ) ? false : true;

				$check_guest_visibility = isset( $get_option['hide_form_guests'] ) && '' != $get_option['hide_form_guests'] && ! is_user_logged_in() ? false : true;
				$check_member_visibility = isset( $get_option['hide_form_members'] ) && '' != $get_option['hide_form_members'] && is_user_logged_in() ? false : true;
				$product_id = $product->get_id();
				$variation_class = '';
				$variation_id = 0;

				if ( $check_guest_visibility && $check_member_visibility && ( $this->is_viewable( $product_id, $variation_id ) && $this->is_viewable_for_category( $product_id ) ) && $this->visibility_on_regular_or_sale( $product, $variation ) && $this->is_viewable_for_product_tag( $product_id ) ) {
					if ( $is_not_variation && $display_popup && ( ! $variation && ! $product->is_in_stock() || ( ( ! $variation && ( ( $product->managing_stock() && $product->backorders_allowed() && $product->is_on_backorder( 1 ) ) || $product->is_on_backorder( 1 ) ) && $visibility_backorder ) ) ) ) {
						/**
						 * Trigger the 'cwginstock_custom_form' action hook to display a custom form for product availability
						 *
						 * @since 1.0.0
						 */
						do_action( 'cwginstock_custom_form', $product, $variation );
					}
				}
			}
		}

		public function display_subscribe_form_in_wpcradiobutton( $stock_html, $variation ) {
			global $product;
			/**
			 * Display the subscribe form based on the specific product and variation.
			 *
			 * @since 1.0.0
			 */
			$display_filter = apply_filters( 'cwginstock_display_subscribe_form', true, $product, $variation );
			$stock_html = $stock_html . $this->display_subscribe_box( $product, $variation, $display_filter );
			return $stock_html;
		}

		public function force_template_from_plugin( $template, $template_name, $template_path, $default_path, $args ) {
			$options = get_option( 'cwginstocksettings' );
			$force_load = isset( $options['template_from_plugin'] ) && '1' == $options['template_from_plugin'] ? true : false;
			if ( $force_load ) {
				$template = $default_path . $template_name;
			}
			return $template;
		}

		public function replace_shortcode_for_message( $message_html, $message, $post_data ) {
			$id = $post_data && isset( $post_data['product_id'] ) && isset( $post_data['variation_id'] ) ? ( $post_data['variation_id'] > 0 ? $post_data['variation_id'] : $post_data['product_id'] ) : false;
			if ( $id ) {
				$obj = wc_get_product( $id );
				if ( $obj ) {
					$product_name = $obj->get_formatted_name();
					$only_product_name = $obj->get_name();
					$find_shortcode = array( '{product_name}', '{only_product_name}' );
					$replce_shortcode = array( $product_name, $only_product_name );
					$message_html = str_replace( $find_shortcode, $replce_shortcode, $message_html );
				}
			}
			return $message_html;
		}

	}

	$instock_product = new CWG_Instock_Notifier_Product();
}
