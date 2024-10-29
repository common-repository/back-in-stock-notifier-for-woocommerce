<?php

class CWG_Instock_Mail extends CWG_Instock_Mailer {

	protected $slug;
	protected $subscriber_id;
	protected $email;
	protected $get_subject;
	protected $get_message;

	public function __construct( $subscriber_id ) {
		parent::__construct();
		$this->slug = 'instock';
		$this->subscriber_id = $subscriber_id;
		$this->email = get_post_meta( $subscriber_id, 'cwginstock_subscriber_email', true );
		/**
		 * Action triggers before in-stock.
		 *
		 * @since 1.0.0
		 */
		do_action( 'cwg_instock_before_' . $this->slug . '_mail', $this->email, $this->subscriber_id );
		$option = get_option( 'cwginstocksettings' );
		/**
		 * Filter for modifying the subject.
		 *
		 * @since 1.0.0
		 */
		$this->get_subject = apply_filters( 'cwginstock_raw_subject', $option['instock_mail_subject'], $subscriber_id );
		/**
		 * Filter for modifying the message.
		 *
		 * @since 1.0.0
		 */
		$this->get_message = apply_filters( 'cwginstock_raw_message', nl2br( $option['instock_mail_message'] ), $subscriber_id );
	}

}
