<?php

class CWG_Instock_Test_Email extends CWG_Instock_Mailer {

	protected $slug;
	protected $subscriber_id;
	protected $email;
	protected $get_subject;
	protected $get_message;

	public function __construct( $subscriber_id = 0 ) {
		parent::__construct();
		$this->slug = 'test';
		$this->subscriber_id = $subscriber_id;
		$this->email = get_bloginfo( 'admin_email' );
		/**
		 * Trigger before instock
		 *
		 * @since 1.0.0
		 */
		do_action( 'cwg_instock_before_' . $this->slug . '_mail', $this->email, $this->subscriber_id );
		$this->get_subject = 'Test Email Subject';
		$this->get_message = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
	}

}
