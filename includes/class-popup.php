<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'exit' );
}
if ( ! class_exists( 'CWG_Sweetalert_Popup' ) ) {

	class CWG_Sweetalert_Popup {

		public function __construct() {
			add_filter( 'cwginstock_display_subscribe_form', array( $this, 'hide_subscribe_form_variation' ), 10, 3 );
			add_action( 'cwginstock_custom_form', array( $this, 'display_custom_button' ), 10, 2 );
		}

		public function hide_subscribe_form_variation( $bool, $product, $variation ) {
			$options = get_option( 'cwginstocksettings' );
			$chosen_mode = isset( $options['mode'] ) && '2' == $options['mode'] ? false : true;
			return $chosen_mode;
		}

		public function display_custom_button( $product, $variation ) {
			$nonce = wp_create_nonce( 'cwg_trigger_popup_ajax' );
			$get_option = get_option( 'cwginstocksettings' );
			$button_label = isset( $get_option['button_label'] ) && '' != $get_option['button_label'] ? $get_option['button_label'] : __( 'Subscribe Now', 'back-in-stock-notifier-for-woocommerce' );
			$instock_api = new CWG_Instock_API();
			$additional_class_name = isset( $get_option['btn_class'] ) && '' != $get_option['btn_class'] ? str_replace( ',', ' ', $get_option['btn_class'] ) : '';
			/**
			 * Filter for popup button label
			 *
			 * @since 4.0.1
			 */
			$popup_button_label_filter = apply_filters( 'cwginstock_popup_btn_label', $button_label, $product, $variation );
			?>
			<input type="submit" data-security="<?php echo do_shortcode( $nonce ); ?>"
				data-variation_id="<?php echo do_shortcode( $variation ? $variation->get_id() : '' ); ?>"
				data-product_id="<?php echo do_shortcode( $product ? $product->get_id() : '' ); ?>"
				class="cwg_popup_submit <?php echo do_shortcode( $additional_class_name ); ?>"
				value="<?php echo do_shortcode( $instock_api->sanitize_text_field( $popup_button_label_filter ) ); ?>" />
			<?php
		}

	}

	$instock_popup = new CWG_Sweetalert_Popup();
}
