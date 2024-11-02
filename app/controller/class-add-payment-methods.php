<?php
require_once BLOYAL_DIR . '/app/controller/class-bloyalcontroller.php';

if ( ! class_exists( 'AddPaymentMethods' ) ) {

	class AddPaymentMethods {

		  private $bloyalControllerObj;

		public function __construct() {
			 $this->bloyalControllerObj = new BloyalController();
		}

		/**
		 * Function to call the bLoyal aprrove cart API
		 *
		 * @param integer $order_id
		 *
		 * @return boolean
		 */
		public function save_payment_methods( $user_id, $email_id ) {
			try {
				  $session_key  = $this->get_session_key( $user_id, $email_id );
				  $snippet_code = get_option( 'payment_snippets_codes' );
				if ( empty( $snippet_code ) || $snippet_code == false ) {

					bLoyalLoggerService::write_custom_log( "\r\n ========== payment_snippets_codes ============\r\n" . $snippet_code );
				}
				  $device_code = get_option( 'device_code' );
				  $action      = $device_code . '/' . $snippet_code . '/CustomerSnippets/PaymentMethods?sessionKey=' . $session_key->data->SessionKey;
				  $result      = $this->bloyalControllerObj->send_curl_request( '', $action, 'web_snippets_api_url', 0 );
				  return $result;
			} catch ( Exception $e ) {
				 $this->log( __FUNCTION__, 'Error in save multiple shipping addresses Reason: ' . $e->getMessage() );
				 return $e->getMessage();
			}
		}

		  /**
		   * Generate session key using
		   *
		   * @param void
		   *
		   * @return string
		   */
		public function get_session_key( $user_id, $email_id ) {
			try {

				  $customer_details = $this->resolve_customer_in_director( $user_id, $email_id );
				  $device_code      = get_option( 'device_code' );
				  $action           = $device_code . '/CustomerSessions';
				  $customer_data    = array(
					  'Uid'               => $customer_details->data->Uid,
					  'ExternalId'        => $user_id,
					  'Code'              => $customer_details->data->Code,
					  'LoyaltyCardNumber' => $customer_details->data->LoyaltyCardNumber,
					  'EmailAddress'      => $email_id,
					  'MobilePhone'       => $customer_details->data->MobilePhone,
					  'RegisterdCard'     => null,
					  'MobileDeviceId'    => $customer_details->data->MobileDeviceId,
				  );
				  $result           = $this->bloyalControllerObj->send_curl_request( $customer_data, $action, 'websnippets', 1 );
				  return $result;
			} catch ( Exception $e ) {
				 $this->log( __FUNCTION__, 'Error in save multiple shipping addresses Reason: ' . $e->getMessage() );
				 return $e->getMessage();
			}
		}

		  /**
		   * This function is used to resolve customer in director account
		   *
		   * @param integer $external_id
		   * @param string  $customer_email
		   * @return json array
		   */
		function resolve_customer_in_director( $external_id, $customer_email ) {
			try {
				  $action = 'resolvedcustomers?EmailAddress=' . $customer_email . '&ExternalId=' . $external_id;
				  $result = $this->bloyalControllerObj->send_curl_request( '', $action, 'loyaltyengine', 0 );
				  return $result;
			} catch ( Exception $e ) {
				 $this->log( __FUNCTION__, 'Error in resolving customer. Reason: ' . $e->getMessage() );
				 bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				 return $e->getMessage();
			}
		}
	}
}
