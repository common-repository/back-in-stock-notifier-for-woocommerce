<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('CWG_Instock_Premium_Extensions')) {

	class CWG_Instock_Premium_Extensions {

		public function __construct() {
			add_action('admin_menu', array($this, 'add_settings_menu'), 999);
		}

		public function add_settings_menu() {
			add_submenu_page('edit.php?post_type=cwginstocknotifier', __('Add-ons', 'back-in-stock-notifier-for-woocommerce'), __('Add-ons', 'back-in-stock-notifier-for-woocommerce'), 'manage_woocommerce', 'cwg-instock-extensions', array($this, 'manage_settings'));
		}

		public function manage_settings() {
			$this->display_as_html();
		}

		public function display_as_html() {
			/**
			 * Filter for add-on list
			 *
			 * @since 1.0.0
			 */
			$array_of_extensions = apply_filters(
					'cwginstock_addon_list_with_link',
					array(
						'Bundle Add-ons - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier-bundle-add-ons/',
						'WPML - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/wpml/',
						'Unsubscribe - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/unsubscribe/',
						'Double Opt-In - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/doubleoptin/',
						'Ban Email Domains and Email Addresses - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/ban-emails/',
						'Export CSV - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/export-csv/',
						'Custom CSS - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/custom-css/',
						'Mailchimp - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/mailchimp/',
						'Track Sales - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/track-sales/',
						'Import CSV - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/import-csv/',
						'Edit Subscribers - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/edit-subscribers/',
						'PolyLang - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/polylang/',
						'Twilio SMS - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/add-ons/back-in-stock-notifier/twilio-sms/',
						'More Addons coming soon' => '',
					)
			);
			?>
			<div class="wrap cwg-addon-wrap">



				<h1>Add-ons for Back In Stock Notifier</h1>
				<p>
					We have created a few add-ons below that enhance the core product with extended functionality.
				</p>

				<?php
				$i = 1;
				foreach ($array_of_extensions as $name => $url) {
					$final_url = '' != $url ? $url : 'http://codewoogeek.online/product-category/back-in-stock-notifier/';
					?>

					<div class="cwg-section">
						<a href="<?php echo esc_url_raw($final_url); ?>" target="__blank">
							<span style="width: 200px;height: 200px;position: absolute;">
								<span class="cwg-addon-title">
									<?php esc_html_e($name); ?>
								</span>
								<?php if ('' != $url) { ?>
									<?php
									if (1 == $i) {
										$price_tag = '$39.99';
									} else {
										$price_tag = '$9.99';
									}
									?>
									<span class="pricetag">
										<?php echo do_shortcode($price_tag); ?>
									</span>
									<span class="cwg-addon-bottom" style="font-weight:bold;">

										<?php
										if (1 == $i) {
											$text = 'Single Site for $39.99';
										} else {
											$text = 'Single Site for $9.99';
										}
										echo do_shortcode(get_submit_button($text));
										?>
									</span>
								<?php } ?>
							</span>
						</a>
					</div>
					<?php
					$i++;
				}
				?>
				<div class="clear"></div>
				<h3>Additional Advantages of our Add-ons:</h3>

				<p>- <strong>Effortless Integration</strong>: Seamlessly integrate these add-ons into your existing system with
					ease.</p>

				<p>- <strong>Enhanced Features</strong>: Enjoy a range of additional features and functionalities that elevate the
					performance of the core product.</p>

				<p>- <strong>Dedicated Assistance</strong>: Access our dedicated support team for any queries or assistance you
					might need.</p>

				<p>- <strong>Regular Updates</strong>: Stay up-to-date with regular updates that keep your add-ons in sync with the
					latest industry trends.</p>

				<p>- <strong>Cost-Effective</strong>: These add-ons are available for an exclusive limited-time price of just $9.99
					each, providing incredible value.</p>

				<p>- <strong>Unmatched Convenience</strong>: Say goodbye to recurring payments with our one-time fee structure.</p>

				<p>- <strong>Satisfaction Guaranteed</strong>: We are confident in the quality and performance of our add-ons, so
					your satisfaction is guaranteed.</p>

				<p>- <strong>Flexible Options</strong>: Choose individual add-ons or opt for our bundle to tailor your experience to
					your exact needs.</p>


				<div class="clear"></div>
			</div>
			<?php
		}

	}

	new CWG_Instock_Premium_Extensions();
}
