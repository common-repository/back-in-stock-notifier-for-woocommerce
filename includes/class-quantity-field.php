<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'CWG_Instock_Quantity_Field' ) ) {

	class CWG_Instock_Quantity_Field {

		private $api;

		/**
		 * Summary of __construct
		 */
		public function __construct() {
			add_action( 'cwginstock_register_settings', array( $this, 'add_settings_field' ), 180 );
			add_filter( 'cwginstocknotifier_columns', array( $this, 'add_column' ), 999, 1 );
			add_action( 'cwginstock_custom_columns', array( $this, 'manage_column' ), 10, 2 );
			add_action( 'cwg_instock_after_email_field', array( $this, 'add_quantity_field' ), 6, 2 );
			add_filter( 'cwginstocknotifier_insert_custom_meta_data', array( $this, 'save_quantity_meta' ), 10, 1 );
			add_filter( 'cwginstock_replace_shortcode', array( $this, 'add_quantity_shortcode' ), 10, 2 );
			add_filter( 'cwginstock_cart_link', array( $this, 'add_quantity_url' ), 10, 3 );
			$this->api = new CWG_Instock_API();
		}

		/**
		 * Summary of add_settings_field
		 *
		 * @return void
		 */
		public function add_settings_field() {
			add_settings_section( 'cwg_instock_quantity_field', __( 'Quantity Field Settings', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'section_headings' ), 'cwginstocknotifier_settings' );
			add_settings_field( 'cwg_instock_quantity_enable', __( 'Display Quantity Field in the Front-End Subscription Form', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'enable_quantity_field' ), 'cwginstocknotifier_settings', 'cwg_instock_quantity_field' );
			add_settings_field( 'cwg_instock_quantity_field_placeholder', __( 'Quantity Field Placeholder', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'quantity_field_placeholder' ), 'cwginstocknotifier_settings', 'cwg_instock_quantity_field' );
			// empty quantity
			add_settings_field( 'cwg_instock_error_qntyfield_empty', __( 'Quantity Field Empty Error', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'quantity_field_empty_error' ), 'cwginstocknotifier_settings', 'cwg_instock_quantity_field' );
			add_settings_field( 'cwg_instock_quantity_field_optional', __( 'Quantity Field Optional', 'back-in-stock-notifier-for-woocommerce' ), array( $this, 'quantity_field_optional' ), 'cwginstocknotifier_settings', 'cwg_instock_quantity_field' );
		}

		/**
		 * Summary of section_headings
		 *
		 * @return void
		 */
		public function section_headings() {
			esc_html_e( 'Use this shortcode {cwginstock_quantity} anywhere in your mail settings to display the subscribed quantity of the product in emails', 'back-in-stock-notifier-for-woocommerce' );
		}

		/**
		 * Summary of enable_quantity_field
		 *
		 * @return void
		 */
		public function enable_quantity_field() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' id='enable_quantity_field' name='cwginstocksettings[enable_quantity_field]' <?php isset( $options['enable_quantity_field'] ) ? checked( $options['enable_quantity_field'], 1 ) : ''; ?> value="1" />
			<?php
		}

		/**
		 * Summary of quantity_field_placeholder
		 *
		 * @return void
		 */
		public function quantity_field_placeholder() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='text' class='quantity_field_placeholder' name='cwginstocksettings[quantity_field_placeholder]'
				value="<?php echo wp_kses_post( isset( $options['quantity_field_placeholder'] ) ? $options['quantity_field_placeholder'] : '' ); ?>" />
			<p><i>
					<?php esc_html_e( 'Enter the placeholder for the quantity field that should be displayed in the front-end', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		/**
		 * Summary of quantity_field_empty_error
		 *
		 * @return void
		 */
		public function quantity_field_empty_error() {
			$options = get_option( 'cwginstocksettings' );
			$option_value = isset( $options['empty_quantity_message'] ) ? $options['empty_quantity_message'] : __( 'Quantity cannot be empty', 'back-in-stock-notifier-for-woocommerce' );
			?>
			<input type='text' style='width: 400px;' name='cwginstocksettings[empty_quantity_message]'
				value="<?php echo wp_kses_post( $this->api->sanitize_text_field( $option_value ) ); ?>" />
			<?php
		}

		/**
		 * Summary of quantity_field_optional
		 *
		 * @return void
		 */
		public function quantity_field_optional() {
			$options = get_option( 'cwginstocksettings' );
			?>
			<input type='checkbox' class='quantity_field_optional' name='cwginstocksettings[quantity_field_optional]' <?php isset( $options['quantity_field_optional'] ) ? checked( $options['quantity_field_optional'], 1 ) : ''; ?> value="1" />
			<p><i>
					<?php esc_html_e( 'Enable this option to make quantity field as optional', 'back-in-stock-notifier-for-woocommerce' ); ?>
				</i></p>
			<?php
		}

		/**
		 * Summary of add_column
		 *
		 * @param mixed $columns
		 * @return mixed
		 */
		public function add_column( $columns ) {
			$options = get_option( 'cwginstocksettings' );
			$quantity_enabled = isset( $options['enable_quantity_field'] ) && '1' === $options['enable_quantity_field'];
			if ( $quantity_enabled ) {
				$date_columns = $columns['date'];
				unset( $columns['date'] );
				$columns['quantity'] = __( 'Required Quantity', 'back-in-stock-notifier-for-woocommerce' );
				$columns['date'] = $date_columns;
			}
			return $columns;
		}

		/**
		 * Summary of manage_column
		 *
		 * @param mixed $column
		 * @param mixed $post_id
		 * @return void
		 */
		public function manage_column( $column, $post_id ) {
			if ( 'quantity' == $column ) {
				$custom_quantity = get_post_meta( $post_id, 'cwginstock_custom_quantity', true );
				$custom_quantity = isset( $custom_quantity ) && '' != $custom_quantity ? $custom_quantity : 1;
				esc_html_e( $custom_quantity );
			}
		}

		/**
		 * Summary of is_quantity_field_enabled
		 *
		 * @return bool
		 */
		public function is_quantity_field_enabled() {
			$option = get_option( 'cwginstocksettings' );
			$check_is_enabled = isset( $option['enable_quantity_field'] ) && '1' == $option['enable_quantity_field'] ? true : false;
			return $check_is_enabled;
		}

		/**
		 * Summary of get_placeholder
		 *
		 * @return mixed
		 */
		public function get_placeholder() {
			$option = get_option( 'cwginstocksettings' );
			$get_placeholder = isset( $option['quantity_field_placeholder'] ) && '' != $option['quantity_field_placeholder'] ? $option['quantity_field_placeholder'] : __( 'Quantity', 'back-in-stock-notifier-for-woocommerce' );
			return $get_placeholder;
		}

		/**
		 * Summary of add_quantity_field
		 *
		 * @param mixed $product_id
		 * @param mixed $variation_id
		 * @return void
		 */
		public function add_quantity_field( $product_id, $variation_id ) {
			$check_is_enabled = $this->is_quantity_field_enabled();
			if ( $check_is_enabled ) {
				$placeholder = $this->get_placeholder();
				$quantity_data = '';
				?>
				<p>
					<input type='number' class="add_quantity_field" name='cwginstocksettings[add_quantity_field]'
						style='margin: 0 auto;width: 100% !important;text-align: center;' min="1"
						placeholder="<?php echo do_shortcode( $placeholder ); ?>"
						value='<?php echo wp_kses_post( $quantity_data ); ?>' />
				</p>
				<?php
			}
		}

		/**
		 * Summary of save_quantity_meta
		 *
		 * @param mixed $quantity_meta
		 * @return mixed
		 */
		public function save_quantity_meta( $quantity_meta ) {
			return array_merge( $quantity_meta, array( 'custom_quantity' ) );
		}

		/**
		 * Summary of add_quantity_shortcode
		 *
		 * @param mixed $formatted_content
		 * @param mixed $subscriber_id
		 * @return array|string
		 */
		public function add_quantity_shortcode( $formatted_content, $subscriber_id ) {
			$user_quantity = get_post_meta( $subscriber_id, 'cwginstock_custom_quantity', true );

			$find = '{cwginstock_quantity}';
			$replace = $user_quantity ? $user_quantity : 1;
			$formatted_content = str_replace( $find, $replace, $formatted_content );

			return $formatted_content;
		}

		/**
		 * Summary of add_quantity_url
		 *
		 * @param mixed $cart_url
		 * @param mixed $product_id
		 * @param mixed $subscriber_id
		 * @return mixed
		 */
		public function add_quantity_url( $cart_url, $product_id, $subscriber_id ) {
			$options = get_option( 'cwginstocksettings' );
			$quantity_enabled = isset( $options['enable_quantity_field'] ) && '1' === $options['enable_quantity_field'];

			if ( $quantity_enabled ) {
				$quantity_value = get_post_meta( $subscriber_id, 'cwginstock_custom_quantity', true );
				if ( $quantity_value ) {
					$query_args = array( 'quantity' => $quantity_value );
					$cart_url = esc_url_raw( add_query_arg( $query_args, $cart_url ) );
				}
			}

			return $cart_url;
		}

	}

	new CWG_Instock_Quantity_Field();
}
