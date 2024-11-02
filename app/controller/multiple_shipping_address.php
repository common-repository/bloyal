<?php
require_once BLOYAL_DIR . '/app/controller/class-bloyalcontroller.php';
require_once BLOYAL_DIR . '/app/controller/bloyal_logger_service.php';

if ( ! class_exists( 'BLOYAL_MultipleShippingAddressController' ) ) {

	class BLOYAL_MultipleShippingAddressController {

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

		public function save_multiple_shipping_addresses( $order_id ) {
			try {
				$current_user             = wp_get_current_user();
				$loggedin_customer_id     = isset( $current_user->ID ) && ( $current_user->ID ) ? $current_user->ID : '';
				$loggedin_customer_email  = isset( $current_user->user_email ) && ( $current_user->user_email ) ? $current_user->user_email : '';
				$customFields             = array();
				$order                    = wc_get_order( $order_id );
				$order_data               = $order->get_data();
				$shipping_birth_date      = WC()->session->get( 'shipping_birth_date' );
				$bloyal_shipping_phone    = WC()->session->get( 'bloyal_shipping_phone' );
				$bloyal_shipping_email    = WC()->session->get( 'bloyal_shipping_email' );
				$customerUid              = $this->bloyalControllerObj->bloyal_fetch_customer_uid( $loggedin_customer_id, $loggedin_customer_email );
				$loyalty_engine_url       = get_option( 'loyalty_engine_api_url' );
				$bloyal_access_key        = get_option( 'bloyal_access_key' );
				$Device_Code              = get_option( 'Device_Code' );
				$cart_data                = WC()->session->get( 'bloyal_cart_data');
				$current_shipping_uid     = WC()->session->get( 'current_shipping_address_uid' );
				$storeCode                = WC()->session->get( 'session_store_code' );
				$multiple_address_request = array(
					"ValidateAddress"     => false,
					"CustomerUid"          => $customerUid,
					"ShippingAddressUid"   => '',
					"Title"                => '',
					"IsPrimary"            => true,
					"FirstName"            => $order_data['shipping']['first_name'],
					"LastName"             => $order_data['shipping']['last_name'],
					"Company"              => $order_data['shipping']['company'],
					"Address" => array (
						"Address1"    =>  $order_data['shipping']['address_1'],
						"Address2"   => $order_data['shipping']['address_2'],
						"City"       => $order_data['shipping']['city'],
						"State"      => $order_data['shipping']['state'],
						"PostalCode" => $order_data['shipping']['postcode'],
						"Country"    => $order_data['shipping']['country']
					),
					"EmailAddress"  => $bloyal_shipping_email,
					"Phone1"        => $bloyal_shipping_phone,
					"Phone2"        => '',
					"MobilePhone"   => '',
					"FaxNumber"     => '',
					"BirthDate"     => $shipping_birth_date,
					"Instructions"  => '',
					"DeviceUid"     => '',
					"StoreCode"     => $storeCode,
					"DeviceCode"    => $Device_Code,
					"CashierUid"    => $cart_data->Cart->CashierUid,
					"CashierCode"   => '',
					"CashierExternalId" => '',
					"Uid"             => $current_shipping_uid,
					"ReferenceNumber" =>  '',
					"SystemUid"       => '',
					"ConnectorUid"    => ''
				);
				$action                  = 'customers/commands/saveshippingaddress';
				$post_url                = $loyalty_engine_url . '/api/v4/'. $bloyal_access_key.'/'. $action ;
				bLoyalLoggerService::write_custom_log( "\n\r==========Save Shipping Action ============ \r\n" . $action, 0 );
				bLoyalLoggerService::write_custom_log( "\n\r==========Save Shipping Request ============ \r\n" . json_encode( $multiple_address_request ), 1 );

				$result = $this->bloyalControllerObj->send_curl_request( $multiple_address_request, $action, 'loyaltyengine', 1 );
				bLoyalLoggerService::write_custom_log( "\n\r==========Save Shipping Response ============ \r\n" . json_encode( $result ), 1 );				
				return $result;
			} catch ( Exception $e ) {
				$this->log( __FUNCTION__, 'Error in save multiple shipping addresses Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		/**
		 * Function to call the bLoyal aprrove cart API
		 *
		 * @param integer $order_id
		 *
		 * @return boolean
		 */

		public function get_multiple_shipping_addresses( $customer_uid, $customer_code ) {
			try {

				$action                   = 'customers/shippingaddresses?customerUid='.$customer_uid.'&customerCode='.$customer_code;
				bLoyalLoggerService::write_custom_log( "\n\r==========Get Shipping Addresss Action ============ \r\n" . $action, 1 );
				$result = $this->bloyalControllerObj->send_curl_request( '', $action, 'loyaltyengine', 0 );

				bLoyalLoggerService::write_custom_log( "\n\r==========Get Shipping Addresss Response ============ \r\n" . json_encode( $result ), 1 );

				return $result;
			} catch ( Exception $e ) {
				$this->log( __FUNCTION__, 'Error in get multiple shipping addresses Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
			
		}
		  
	}
}

