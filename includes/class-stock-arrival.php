<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('CWG_Estimate_Stock_Arrival')) {

	class CWG_Estimate_Stock_Arrival {

		public $api;
		public $options;

		public function __construct() {
			add_action('admin_menu', array($this, 'add_submenu_page'), 800);
			add_action('init', array($this, 'register_post_type'));
			add_action('add_meta_boxes', array($this, 'metabox_estimate_stock_arrival'));
			add_action('save_post_cwginstock_arrival', array($this, 'save_estimate_stock_arrival_meta'));
			add_filter('manage_cwginstock_arrival_posts_columns', array($this, 'add_columns'));
			add_filter('parent_file', array($this, 'add_active_menu_cpt'));
			add_action('manage_cwginstock_arrival_posts_custom_column', array($this, 'manage_columns'), 10, 2);
			add_filter('post_row_actions', array($this, 'manage_row_actions'), 10, 2);
			add_action('admin_notices', array($this, 'display_error'));
			$this->api = new CWG_Instock_API();
			$this->options = get_option('cwginstocksettings');
			$enable_est_stock = isset($this->options['enable_est_stock_arrival']) ? $this->options['enable_est_stock_arrival'] : 0;
			$display_hook = isset($this->options['display_message_place']) ? $this->options['display_message_place'] : '';
			if ($enable_est_stock) {
				add_action($display_hook, array($this, 'display_eta_message'), 10, 2);
			}
		}

		public function add_submenu_page() {

			$enable_est_stock = isset($this->options['enable_est_stock_arrival']) ? $this->options['enable_est_stock_arrival'] : 0;
			if ($enable_est_stock && current_user_can('manage_woocommerce')) {
				add_submenu_page(
						'edit.php?post_type=cwginstocknotifier',
						__('Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce'),
						__('Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce'),
						'manage_woocommerce',
						'edit.php?post_type=cwginstock_arrival'
				);
			}
		}

		public function register_post_type() {
			$args = array(
				'labels' => array(
					'name' => __('Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce'),
					'singular_name' => _x('All ESA', 'All ESA', 'back-in-stock-notifier-for-woocommerce'),
					'menu_name' => _x('Estimate Stock Arrival', 'Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce'),
					'name_admin_bar' => _x('Estimate Stock Arrival', 'Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce'),
					'add_new' => _x('Add New', 'add new in menu', 'back-in-stock-notifier-for-woocommerce'),
					'add_new_item' => __('Add New Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce'),
					'new_item' => __('New Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce'),
					'edit_item' => __('Edit Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce'),
					'view_item' => __('View Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce'),
					'all_items' => __('All Data', 'back-in-stock-notifier-for-woocommerce'),
					'search_items' => __('Search Data', 'back-in-stock-notifier-for-woocommerce'),
					'parent_item_colon' => __('Parent:', 'back-in-stock-notifier-for-woocommerce'),
					'not_found' => __('No Data Found', 'back-in-stock-notifier-for-woocommerce'),
					'not_found_in_trash' => __('No Data found in Trash', 'back-in-stock-notifier-for-woocommerce'),
				),
				'public' => false,
				'show_ui' => true,
				'show_in_menu' => false,
				'supports' => array('title'),
			);

			register_post_type('cwginstock_arrival', $args);
		}

		public function add_columns( $columns) {
			$date_column = $columns['date'];
			$columns['products'] = __('Product(s)', 'back-in-stock-notifier-for-woocommerce');
			$columns['est_date'] = __('Estimated Stock Arrival Date', 'back-in-stock-notifier-for-woocommerce');
			$columns['stock_message'] = __('Estimated Stock Arrival Message', 'back-in-stock-notifier-for-woocommerce');
			unset($columns['date']);
			$columns['date'] = $date_column;
			return $columns;
		}

		public function manage_columns( $column, $post_id) {
			switch ($column) {
				case 'products':
					$product_ids = get_post_meta($post_id, 'cwg_product_ids', true);
					$current_v = isset($product_ids) ? $product_ids : '';
					if (is_array($current_v) && !empty($current_v)) {
						foreach ($current_v as $each_id) {
							$product = wc_get_product($each_id);
							if ($product) {
								printf('<pre>%s</pre>', wp_kses_post($product->get_formatted_name()));
							}
						}
					}
					break;
				case 'est_date':
					$stock_date = get_post_meta($post_id, 'cwg_eta_date', true);
					printf('%s', wp_kses_post($stock_date));
					break;
				case 'stock_message':
					$stock_message = get_post_meta($post_id, 'cwg_stock_message', true);
					$stock_message = sanitize_text_field($stock_message);
					printf('%s', wp_kses_post($stock_message));
					break;
			}
		}

		public function manage_row_actions( $actions, $post) {
			if ('cwginstock_arrival' == $post->post_type) {
				unset($actions['inline hide-if-no-js']);
			}
			return $actions;
		}

		public function add_active_menu_cpt( $parent_file) {
			global $submenu_file, $current_screen;
			if ('cwginstock_arrival' == $current_screen->post_type) {
				$submenu_file = 'edit.php?post_type=cwginstock_arrival';
				$parent_file = 'edit.php?post_type=cwginstocknotifier';
			}
			return $parent_file;
		}

		public function metabox_estimate_stock_arrival() {
			add_meta_box(
					'cwg_estimate_stock_arrival',
					__('Estimate Stock Arrival', 'back-in-stock-notifier-for-woocommerce'),
					array($this, 'cwg_estimate_stock_arrival_callback'),
					'cwginstock_arrival',
					'normal',
					'high'
			);
		}

		public function cwg_estimate_stock_arrival_callback( $post) {
			if (!isset($post)) {
				return;
			}

			$product_ids = get_post_meta($post->ID, 'cwg_product_ids', true);
			$estimated_arrival_date = get_post_meta($post->ID, 'cwg_eta_date', true);
			$stock_message = get_post_meta($post->ID, 'cwg_stock_message', true);
			wp_nonce_field('cwg_estimate_stock_arrival_nonce', 'cwg_estimate_stock_arrival_nonce');
			?>

			<table class="form-table">
				<tr>
					<th style="width: 20%;"><label
							for="cwg_product_ids"><?php esc_html_e('Select Product(s)', 'back-in-stock-notifier-for-woocommerce'); ?></label>
					</th>
					<td>
						<select style="width:320px;"
								data-placeholder="<?php esc_html_e('Select Products', 'back-in-stock-notifier-for-woocommerce'); ?>"
								data-allow_clear="true" tabindex="-1" aria-hidden="true" name="cwg_product_ids[]" multiple="multiple"
								class="wc-product-search">
									<?php
									$current_v = isset($product_ids) ? $product_ids : '';
									if (is_array($current_v) && !empty($current_v)) {
										foreach ($current_v as $each_id) {
											$product = wc_get_product($each_id);
											if ($product) {
												printf('<option value="%s"%s>%s</option>', intval($each_id), ' selected="selected"', wp_kses_post($product->get_formatted_name()));
											}
										}
									}
									?>
						</select>
					</td>
				</tr>
				<tr>
					<th style="width: 20%;">
						<label
							for="cwg_eta_date"><?php esc_html_e('Estimated Stock Arrival Date', 'back-in-stock-notifier-for-woocommerce'); ?></label>
					</th>
					<td>
						<input type="date" name="cwg_eta_date" id="estimated_arrival_date"
							   value="<?php echo esc_attr($estimated_arrival_date); ?>"
							   min="<?php echo esc_attr(gmdate('Y-m-d')); ?>" />
					</td>
				</tr>
				<tr>
					<th style="width: 20%;">
						<label
							for="cwg_stock_message"><?php esc_html_e('Estimate Stock Message', 'back-in-stock-notifier-for-woocommerce'); ?></label>
					</th>
					<td>
						<textarea name="cwg_stock_message" id="stock_message" rows="10"
								  cols="45"><?php echo esc_textarea($stock_message); ?></textarea>
						<br><i>HTML Tags supported and Shortcodes are available for {stock_arrival_date}, {product_name},
							{only_product_name}, {days_left}</i>
					</td>

				</tr>
			</table>

			<?php
		}

		public function save_estimate_stock_arrival_meta( $post_id) {

			if (!isset($_POST['cwg_estimate_stock_arrival_nonce']) ||
					!wp_verify_nonce(sanitize_text_field($_POST['cwg_estimate_stock_arrival_nonce']), 'cwg_estimate_stock_arrival_nonce')) {
				return;
			}

			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				return;
			}

			if (!current_user_can('edit_post', $post_id)) {
				return;
			}

			$empty_fields = array();
			$product_ids = isset($_POST['cwg_product_ids']) ? array_map('intval', $_POST['cwg_product_ids']) : '';
			$eta_date = isset($_POST['cwg_eta_date']) ? sanitize_text_field($_POST['cwg_eta_date']) : '';
			$stock_message = isset($_POST['cwg_stock_message']) ? sanitize_text_field($_POST['cwg_stock_message']) : '';

			if (empty($product_ids)) {
				$empty_fields[] = __('Search Products', 'back-in-stock-notifier-for-woocommerce');
			}

			if (empty($eta_date)) {
				$empty_fields[] = __('Estimated Stock Arrival Date', 'back-in-stock-notifier-for-woocommerce');
			}

			if (empty($stock_message)) {
				$empty_fields[] = __('Stock Message', 'back-in-stock-notifier-for-woocommerce');
			}

			if (!empty($empty_fields)) {
				$error_message = 'Error: The following fields are required and must not be empty: ' . implode(', ', $empty_fields);
				set_transient('cwginstock_esta', $error_message, 45);
				remove_action('save_post_cwginstock_arrival', array($this, 'save_estimate_stock_arrival_meta'));

				wp_update_post(
						array(
							'ID' => $post_id,
							'post_status' => 'draft',
						)
				); // Set the post status back to draft
			}

			if (isset($product_ids) && is_array($product_ids)) {
				update_post_meta($post_id, 'cwg_product_ids', array_map('intval', $product_ids));
			}
			if (isset($eta_date)) {
				update_post_meta($post_id, 'cwg_eta_date', $this->api->sanitize_text_field($eta_date));
			}
			if (isset($stock_message)) {
				update_post_meta($post_id, 'cwg_stock_message', $this->api->sanitize_textarea_field($stock_message));
			}
		}

		public function get_eta_by_product_id( $product_id, $match_rule = 'first') {
			global $wpdb;

			$custom_post_type = 'cwginstock_arrival';
			$meta_key = 'cwg_product_ids';

			$results = $wpdb->get_col($wpdb->prepare(
							"
        SELECT p.ID
        FROM {$wpdb->posts} AS p
        INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
        WHERE p.post_type = %s
        AND pm.meta_key = %s
        AND pm.meta_value LIKE %s
        AND p.post_status = 'publish'
        ",
							$custom_post_type,
							$meta_key,
							'%;i:' . intval($product_id) . ';%'
			));

			if (!empty($results)) {
				if ('first' === $match_rule) {
					return $results[0];
				} elseif ('last' === $match_rule) {
					return $results[count($results) - 1];
				}
			}

			return null;
		}

		public function display_eta_message( $product_id, $variation_id) {
			if ($variation_id > 0) {
				$id = $variation_id;
			} else {
				$id = $product_id;
			}
			$product_name = '';
			$only_product_name = '';
			$product_obj = wc_get_product($id);
			if ($product_obj) {
				$product_name = $product_obj->get_formatted_name();
				$only_product_name = $product_obj->get_name();
			}
			$get_priority = isset($this->options['est_rule_priority']) ? $this->options['est_rule_priority'] : 'first';
			$fetch_matched_id = $this->get_eta_by_product_id($id, $get_priority);

			if ($fetch_matched_id) {
				$get_stock_date = get_post_meta($fetch_matched_id, 'cwg_eta_date', true);
				$saved_timestamp = strtotime($get_stock_date);
				$current_timestamp = strtotime(gmdate('Y-m-d'));
				$diff_in_seconds = $saved_timestamp - $current_timestamp;
				$days_left = floor($diff_in_seconds / ( 60 * 60 * 24 ));
				$days_left = $days_left > 1 ? $days_left . ' ' . __('Days', 'back-in-stock-notifier-for-woocommerce') : $days_left . ' ' . __('Day', 'back-in-stock-notifier-for-woocommerce');
				$get_stock_date = gmdate(get_option('date_format'), strtotime($get_stock_date));
				$get_message = get_post_meta($fetch_matched_id, 'cwg_stock_message', true);
				$find_array = array('{stock_arrival_date}', '{product_name}', '{only_product_name}', '{days_left}');
				$replace_array = array($get_stock_date, $product_name, $only_product_name, $days_left);
				$get_message = str_replace($find_array, $replace_array, $get_message);
				echo do_shortcode($get_message);
			}
		}

		public function display_error() {
			// Check if there's a transient for the error
			$error_message = get_transient('cwginstock_esta');
			if ($error_message) {
				// Display the error message in the notice
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
				// Delete the transient to ensure the message only appears once
				delete_transient('cwginstock_esta');
			}
		}

	}

	new CWG_Estimate_Stock_Arrival();
}
