<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CWG_Instock_Status' ) ) {

	class CWG_Instock_Status {

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_settings_menu' ) );
			add_action( 'admin_head', array( $this, 'hide_notice' ) );
			add_action( 'wp_ajax_cwginstock_test_email', array( $this, 'test_email_callback' ) );
			add_action( 'wp_ajax_cwginstock_backend_ui', array( $this, 'change_backend_ui' ) );
		}

		public function add_settings_menu() {
			add_submenu_page( 'edit.php?post_type=cwginstocknotifier', __( 'Staus', 'back-in-stock-notifier-for-woocommerce' ), __( 'Status', 'back-in-stock-notifier-for-woocommerce' ), 'manage_woocommerce', 'cwg-instock-status', array( $this, 'manage_settings' ) );
		}

		public function manage_settings() {
			?>
			<h1>PLUGIN STATUS</h1>
			<hr>
			<h2>CRON STATUS</h2>
			<hr>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">Cron Status</th>
						<td>
							<?php
							if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
								$message = 'WP_CRON was disabled';
								$status_code = 0;
							} else {
								$message = 'WP_CRON ACTIVE';
								$status_code = 1;
							}
							?>

							<p class="cwginstock_status_<?php echo do_shortcode( $status_code ); ?>">
								<?php echo do_shortcode( $message ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<h2>Settings Configuration</h2>
			<hr>
			<table class="form-table">
				<tbody>
					<?php
					$option = get_option( 'cwginstocksettings' );

					$settings_information = array(
						'checkbox' =>
							array(
								'enable_success_sub_mail' =>
									array(
										'1' => __( 'Success Subscription Mail::Enabled', 'back-in-stock-notfier-for-woocommerce' ),
										'0' => __( 'Success Subscription Mail::Disabled', 'back-in-stock-notifier-for-woocommerce' ),
									),
								'enable_instock_mail' =>
									array(
										'1' => __( 'Back In Stock Email::Enabled', 'back-in-stock-notfier-for-woocommerce' ),
										'0' => __( 'Back in Stock Email::Disabled', 'back-in-stock-notifier-for-woocommerce' ),
									),
								'keep_status_subscribed' =>
									array(
										'1' => __( 'Keep Subscription Entry to Subscribed Status even Instock Email Sent (Unless it is Unsubscribed)::Enabled', 'back-in-stock-notfier-for-woocommerce' ),
										'0' => __( 'Keep Subscription Entry to Subscribed Status even Instock Email Sent (Unless it is Unsubscribed)::Disabled', 'back-in-stock-notifier-for-woocommerce' ),
									),
							),
						'inputfield' =>
							array(
								'success_sub_subject' =>
									array(
										'0' => __( 'Success Subscription Email Subject::missing', 'back-in-stock-notifier-for-woocommerce' ),
										'1' => __( 'Success Subscription Email Subject::present', 'back-in-stock-notifier-for-woocommerce' ),
									),
								'success_sub_message' =>
									array(
										'0' => __( 'Success Subscription Email Message::missing', 'back-in-stock-notifier-for-woocommerce' ),
										'1' => __( 'Success Subscription Email Message::present', 'back-in-stock-notifier-for-woocommerce' ),
									),
								'instock_mail_subject' =>
									array(
										'0' => __( 'Back In stock Email Subject::missing', 'back-in-stock-notifier-for-woocommerce' ),
										'1' => __( 'Back In Stock Email Subject::present', 'back-in-stock-notifier-for-woocommerce' ),
									),
								'instock_mail_message' =>
									array(
										'0' => __( 'Back In Stock Email Message::missing', 'back-in-stock-notifier-for-woocommerce' ),
										'1' => __( 'Back In Stock Email Message::present', 'back-in-stock-notifier-for-woocommerce' ),
									),
							),
					);

					if ( is_array( $settings_information ) && ! empty( $settings_information ) ) {
						foreach ( $settings_information as $key => $value ) {
							if ( is_array( $value ) && ! empty( $value ) ) {
								foreach ( $value as $checkbox_key => $checkbox_data ) {
									$is_enabled = 'checkbox' == $key ? ( isset( $option[ $checkbox_key ] ) ? $option[ $checkbox_key ] : 0 ) : ( isset( $option[ $checkbox_key ] ) && $option[ $checkbox_key ] ? 1 : 0 );
									if ( isset( $checkbox_data[ $is_enabled ] ) ) {
										$split_by_colon = explode( '::', $checkbox_data[ $is_enabled ] );
										$heading = $split_by_colon[0];
										$status_info = $split_by_colon[1];
										?>
										<tr>
											<th scope="row">
												<?php echo do_shortcode( $heading ); ?>
											</th>
											<td class="cwginstock_status_<?php echo do_shortcode( $is_enabled ); ?>">
												<?php echo do_shortcode( strtoupper( $status_info ) ); ?>
											</td>
										</tr>
										<?php
									}
								}
							}
						}
					}
					?>
				</tbody>
			</table>
			<h2>EMAIL STATUS</h2>
			<hr>
			<table class="form-table">
				<tbody>
					<?php
					$nonce = wp_create_nonce( 'cwginstock_test_email' );
					wp_enqueue_script( 'jquery' );
					$saved_info = get_option( 'cwginstock_test_email_status' );
					$detailed_status_msg = '';
					$status = '';
					if ( $saved_info && isset( $saved_info['status'] ) ) {
						$status = $saved_info['status'];
						$status_format = ucwords( $status );
						$last_tested_on = $saved_info['checked_on'];
						$detailed_status_msg = 'failure' == $status ? __( 'Email sending Failed, last tested on:', 'back-in-stock-notifier-for-woocommerce' ) . " $last_tested_on" : __( 'Email sent successfully, last tested on:', 'back-in-stock-notifier-for-woocommerce' ) . " $last_tested_on";
					}
					?>
					<tr>
						<th scope="row">Email Status</th>
						<td>
							<button id="submitForm" data-security="<?php echo do_shortcode( $nonce ); ?>"> Test Email</button>
							<p class="cwginstock_test_email_info <?php echo do_shortcode( $status ); ?>">
								<?php echo do_shortcode( $detailed_status_msg ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>


			<h2>ACTIVE ADD-ON(S)</h2>
			<hr>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">Active Add-on(s)</th>
						<td>
							<?php
							$active_addons = array(
								'CWG_Instock_Mailchimp' => 'Mailchimp Add-on',
								'CWG_Instock_Notifier_Custom_CSS' => 'Custom CSS Add-on',
								'CWG_Instock_Notifier_Unsubscribe' => 'Unsubscribe Add-on',
								'CWG_Instock_Notifier_WPML' => 'WPML Add-on',
								'CWG_Doubleopt_in' => 'Double Opt-In Add-on',
								'CWG_Instock_Notifier_Export_CSV' => 'Export CSV Add-on',
								'CWG_Instock_Notifier_Ban_Emails' => 'Ban Email Add-on',
								'CWG_Instock_Notifier_Track_Sales' => 'Track Sales',
								'CWG_Instock_Import_CSV' => 'Import CSV',
								'CWG_Instock_Notifier_Edit_Subscribers' => 'Edit Subscribers',
								'CWG_Instock_Notifier_Polylang' => 'Polylang',
								'CWG_Bundle_List_Table' => 'Bundle Add-ons',
							);

							if ( is_array( $active_addons ) && ! empty( $active_addons ) ) {
								$i = 1;

								foreach ( $active_addons as $add_on => $add_on_name ) {
									if ( class_exists( $add_on ) ) {
										?>
										<p class="cwginstock_status_1">
											<?php echo do_shortcode( $i . '. ' . $add_on_name ); ?>
										</p>
										<?php
										$i++;
									}
								}
							}
							?>
						</td>
					</tr>

				</tbody>
			</table>
			<hr>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">Is Subscribe Form Template Override/loaded from Theme?</th>
						<td>
							<?php
							$template_dir = 'back-in-stock-notifier-for-woocommerce';
							$template_theme_file = get_stylesheet_directory() . '/' . $template_dir . '/default-form.php';
							if ( file_exists( $template_theme_file ) ) {
								echo "<p style='color:green;'>YES</p>";
							} else {
								echo "<p style='color:red;'>NO</p>";
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>

			<hr>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">Want to change the UI of Backend Admin Settings?</th>
						<td>
							<?php
							$nonceui = wp_create_nonce( 'cwginstock_backend_ui' );
							wp_enqueue_script( 'jquery' );
							?>
							<select name='cwginstock_backend_ui' id='cwginstock_backend_ui'>
								<?php
								$settings_options = array(
									'default_ui' => 'Default UI',
									'tabbed_ui' => 'Tabbed UI',
								);
								$saved_option = get_option( 'cwginstock_backend_ui', 'tabbed_ui' );

								foreach ( $settings_options as $option_key => $option_name ) {
									?>
									<option value='<?php echo esc_html( $option_key ); ?>' <?php echo $saved_option == $option_key ? 'selected=selected' : ''; ?>>
										<?php echo esc_html( $option_name ); ?>
									</option>
									<?php
								}
								?>
							</select>

							<button id="submitFormUI" data-security="<?php echo do_shortcode( $nonceui ); ?>"> Change Settings
								UI</button>
							<p class="cwginstock_settings_change_info">
							</p>

						</td>
					</tr>
				</tbody>
			</table>

			<?php
		}

		public function hide_notice() {
			/**
			 * Filter for add-on list
			 *
			 * @since 1.0.0
			 */
			$active_addons = apply_filters(
				'cwginstock_addon_list',
				array(
					'CWG_Instock_Mailchimp' => 'Mailchimp Add-on',
					'CWG_Instock_Notifier_Custom_CSS' => 'Custom CSS Add-on',
					'CWG_Instock_Notifier_Unsubscribe' => 'Unsubscribe Add-on',
					'CWG_Instock_Notifier_WPML' => 'WPML Add-on',
					'CWG_Doubleopt_in' => 'Double Opt-In Add-on',
					'CWG_Instock_Notifier_Export_CSV' => 'Export CSV Add-on',
					'CWG_Instock_Notifier_Ban_Emails' => 'Ban Email Add-on',
					'CWG_Instock_Notifier_Track_Sales' => 'Track Sales',
					'CWG_Instock_Import_CSV' => 'Import CSV',
					'CWG_Instock_Notifier_Edit_Subscribers' => 'Edit Subscribers',
					'CWG_Instock_Notifier_Polylang' => 'Polylang',
				)
			);
			$is_active = false;

			foreach ( $active_addons as $each_addon_class => $addon_name ) {
				if ( class_exists( $each_addon_class ) ) {
					$is_active = true;
				}
			}
			if ( $is_active ) {
				?>
				<style type='text/css'>
					.cwg_marketing_notice {
						display: none;
					}
				</style>
				<?php
			}
		}

		public function test_email_callback() {
			if ( isset( $_POST ) ) {
				// nonce validation
				if ( isset( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( $_POST['security'] ), 'cwginstock_test_email' ) ) {
					$test_obj = new CWG_Instock_Test_Email();
					$response = $test_obj->send();
					if ( ( $response ) ) {
						// if success
						$status_detail = array(
							'status' => 'success',
							'checked_on' => gmdate( 'Y-m-d h:i:s' ),
						);
					} else {
						$status_detail = array(
							'status' => 'failure',
							'checked_on' => gmdate( 'Y-m-d h:i:s' ),
						);
					}
					update_option( 'cwginstock_test_email_status', $status_detail );

					wp_send_json( $status_detail );
				} else {
					$error = esc_html__( 'Unable to verify details, please try again after some time', 'back-in-stock-notifier-for-woocommerce' );
					wp_send_json_error( $error, '401' );
				}

				die();
			}
		}

		public function change_backend_ui() {
			if ( isset( $_POST ) ) {
				// nonce validation
				if ( isset( $_POST['security'], $_POST['cwginstock_view'] ) && wp_verify_nonce( sanitize_text_field( $_POST['security'] ), 'cwginstock_backend_ui' ) ) {
					update_option( 'cwginstock_backend_ui', sanitize_text_field( $_POST['cwginstock_view'] ) );
					$status_info = array(
						'status' => 'success',
						'message' => __( 'Settings UI changed successfully', 'back-in-stock-notifier-for-woocommerce' ),
					);
					wp_send_json( $status_info );
				} else {
					$error = esc_html__( 'Unable to verify details, please try again after some time', 'back-in-stock-notifier-for-woocommerce' );
					wp_send_json_error( $error, '401' );
				}

				die();
			}
		}

	}

	new CWG_Instock_Status();
}
