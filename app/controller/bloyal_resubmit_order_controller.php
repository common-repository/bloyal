<?php
/**
 * Resubmit Order Controller class for bLoyal plugin.
 *
 * @package bLoyal
 */
require_once BLOYAL_DIR . '/app/controller/class-bloyalcontroller.php';
defined( 'ABSPATH' ) || die( 'No script!' );

if ( ! class_exists( 'BLOYAL_ResubmitOrderController' ) ) {
	/**
	 * Class to work with ResubmitOrderController.
	 */
	class BLOYAL_ResubmitOrderController {
		/**
		 * Initialize bloyal_controller_obj.
		 *
		 * @var mixed
		 */
		private $bloyal_controller_obj;
		/**
		 * Define __construct.
		 *
		 * @return void
		 */
		public function __construct() {
			$this->bloyal_controller_obj = new BloyalController();
		}
		
		/**
		 * Initialize make_resubmit_request.
		 *
		 * @param  mixed $order_id as parameter.
		 * @return json string.
		 */
		public function make_resubmit_request( $order_id ) {
			try {
				global $wpdb;
				$order                       = new WC_Order( $order_id );
				$db_table_name               = $wpdb->prefix . 'postmeta';
				$bloyalOrderLevelDiscount    = 0;
				$bloyalCouponsAmount         = 0;
				$bloyal_order_level_discount = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $db_table_name WHERE post_id = %d AND meta_key = %s", $order_id, '_bloyal_order_level_discount_total' ) );
				if ( ! empty( $bloyal_order_level_discount ) ) {
					$bloyalOrderLevelDiscount = $bloyal_order_level_discount[0]->meta_value;
				}
				$db_table_name     = $wpdb->prefix . 'woocommerce_order_items';
				$guest_checkout    = false;
				$is_guest_checkout = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_name  FROM $db_table_name WHERE order_id = %d AND order_item_type = %s", $order_id, $order_id ) );
				if ( ! empty( $is_guest_checkout ) ) {
					$guest_checkout = true;
				}
				$bloyal_applied_coupons = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_name, order_item_id FROM $db_table_name WHERE order_id = %d AND order_item_type = %s", $order_id, 'coupon' ) );
				$bloyalOrderItemName    = '';
				if ( ! empty( $bloyal_applied_coupons ) ) {
					$bloyalOrderItemName = $bloyal_applied_coupons[0]->order_item_name;
					if ( isset( $bloyal_applied_coupons[0]->order_item_id ) ) {
						$db_table_name         = $wpdb->prefix . 'woocommerce_order_itemmeta';
						$bloyal_coupons_amount = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $db_table_name WHERE order_item_id = %d AND meta_key = %s ", $bloyal_applied_coupons[0]->order_item_id, 'discount_amount' ) );
						if ( ! empty( $bloyal_coupons_amount ) ) {
							$bloyalCouponsAmount = $bloyal_coupons_amount[0]->meta_value;
						}
					}
				}
				$customer_data             = array();
				$Shipping_data             = array();
				$lines_data                = array();
				$payment_data              = array();
				$customer_data             = $this->makebLoyalCartCustomer( $order_id );
				$Shipping_data             = $this->makebLoyalCartShipping( $order_id );
				$lines_data                = $this->makebLoyalCartLines( $order_id );
				$count_line_items          = count( $lines_data );
				$line_level_total_discount = 0;
				for ( $count = 0; $count < $count_line_items; $count++ ) {
					$line_total_discount       = $lines_data[ $count ]['DiscountDetails'][ $count ]['Amount'];
					$line_total_discount       = $line_total_discount * $lines_data[ $count ]['Quantity'];
					$line_level_total_discount = $line_level_total_discount + $line_total_discount;
				}
				$payment_data        = $this->makebLoyalCartPayment( $order_id );
				$orderDiscount       = array(
					'DiscountRuleUid'  => '00000000-0000-0000-0000-000000000000',
					'DiscountRuleCode' => null,
					'CouponUid'        => '00000000-0000-0000-0000-000000000000',
					'CouponCode'       => $bloyalOrderItemName,
					'ReasonUid'        => '00000000-0000-0000-0000-000000000000',
					'ReasonCode'       => null,
					'External'         => false,
					'Amount'           => $bloyalOrderLevelDiscount + $bloyalCouponsAmount,
					'Name'             => null,
				);
				$discountDetails[]   = array(
					'Amount'            => $line_level_total_discount,
					'ExternallyApplied' => false,
					'RuleUid'           => '00000000-0000-0000-0000-000000000000',
					'RuleCode'          => null,
					'ReasonUid'         => '00000000-0000-0000-0000-000000000000',
					'ReasonCode'        => null,
				);
				$customFields        = array();
				$entity              = array(
					'Status'               => 'completed',
					'Title'                => null,
					'StoreUid'             => '00000000-0000-0000-0000-000000000000',
					'StoreCode'            => null,
					'StoreExternalId'      => null,
					'DeviceUid'            => '00000000-0000-0000-0000-000000000000',
					'DeviceCode'           => null,
					'CashierUid'           => '00000000-0000-0000-0000-000000000000',
					'CashierCode'          => null,
					'ChannelUid'           => '00000000-0000-0000-0000-000000000000',
					'ChannelCode'          => null,
					'SourceExternalId'     => null,
					'CartUid'              => '00000000-0000-0000-0000-000000000000',
					'CartExternalId'       => $order_id,
					'CartSourceExternalId' => $order_id,
					'Comment'              => null,
					'OrderDiscount'        => $orderDiscount,
					'DiscountDetails'      => $discountDetails,
					'Tip'                  => 0,
					'Customer'             => is_user_logged_in() ? $customer_data : null,
					'GuestCheckout'        => $guest_checkout,
					'ReferenceNumber'      => $order_id,
					'Holding'              => true,
					'Shipments'            => $Shipping_data,
					'Lines'                => $lines_data,
					'Payments'             => $payment_data,
					'Code'                 => null,
					'Id'                   => 0,
					'Uid'                  => '00000000-0000-0000-0000-000000000000',
					'ExternalId'           => $order_id,
					'Created'              => null,
					'CreatedLocal'         => null,
					'Updated'              => null,
					'UpdatedLocal'         => null,
					'Revision'             => 0,
					'CustomFields'         => $customFields,
				);
				$submitCartRequest[] = array(
					'EntityUid'  => '00000000-0000-0000-0000-000000000000',
					'ChangeType' => 'Modified',
					'Entity'     => $entity,
				);
				bLoyalLoggerService::write_custom_log( "Resubmit Cart Request \r\n " . wp_json_encode( $submitCartRequest ) . "\r\n ===================\r\n", 1 );
				$result = $this->bloyal_controller_obj->send_curl_request( $submitCartRequest, 'SalesOrders/Changes', 'grid', 1 );

				if ( $result->status == 'success' || $result->message == 'Cart cannot be submitted.  Cart already exists in Captured state.' ) {
					$order->add_order_note( 'Order successfully submitted to bLoyal order engine', 0, true );
				} else {
					$order->add_order_note( $result->message, 0, true );
				}
				bLoyalLoggerService::write_custom_log( "Resubmit Cart Response \r\n" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
				$response = wp_json_encode(
					array(
						'resubmit_message' => $result->message,
					)
				);
				return $response;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}

		function makebLoyalCartCustomer( $order_id ) {
			try {
				global $wpdb;
				$db_table_name     = $wpdb->prefix . 'woocommerce_order_items';
				$guest_checkout    = false;
				$is_guest_checkout = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_name  FROM $db_table_name WHERE order_id = %d AND order_item_type = %s", $order_id, 'isGuest' ) );
				if ( ! empty( $is_guest_checkout ) ) {
					$guest_checkout = true;
				}
				$order             = new WC_Order( $order_id );
				$order_data        = $order->get_data();
				$clubMemberships   = array(
					'ClubUid'        => '00000000-0000-0000-0000-000000000000',
					'Status'         => 'Undefined',
					'ExpirationDate' => null,
				);
				$externalIds       = array(
					'SystemUid'  => '00000000-0000-0000-0000-000000000000',
					'ExternalId' => null,
					'Verified'   => true,
				);
				$arrBillingDetails = $order_data['billing'];
				$customer_data     = array(
					'PartitionId'               => 0,
					'PartitionCode'             => null,
					'FirstName'                 => isset( $arrBillingDetails['first_name'] ) ? $arrBillingDetails['first_name'] : '',
					'LastName'                  => isset( $arrBillingDetails['last_name'] ) ? $arrBillingDetails['last_name'] : '',
					'CompanyName'               => isset( $arrBillingDetails['company'] ) ? $arrBillingDetails['company'] : '',
					'Address1'                  => isset( $arrBillingDetails['address_1'] ) ? $arrBillingDetails['address_1'] : '',
					'Address2'                  => isset( $arrBillingDetails['address_2'] ) ? $arrBillingDetails['address_2'] : '',
					'City'                      => isset( $arrBillingDetails['city'] ) ? $arrBillingDetails['city'] : '',
					'State'                     => isset( $arrBillingDetails['state'] ) ? $arrBillingDetails['state'] : '',
					'PostalCode'                => isset( $arrBillingDetails['postcode'] ) ? $arrBillingDetails['postcode'] : '',
					'Country'                   => isset( $arrBillingDetails['country'] ) ? $arrBillingDetails['country'] : '',
					'EmailAddress'              => isset( $arrBillingDetails['email'] ) ? $arrBillingDetails['email'] : '',
					'EmailAddress2'             => null,
					'Phone1'                    => isset( $arrBillingDetails['phone'] ) ? $arrBillingDetails['phone'] : '',
					'Phone2'                    => isset( $arrBillingDetails['phone'] ) ? $arrBillingDetails['phone'] : '',
					'MobilePhone'               => isset( $arrBillingDetails['phone'] ) ? $arrBillingDetails['phone'] : '',
					'FaxNumber'                 => null,
					'FirstName2'                => null,
					'LastName2'                 => null,
					'SignupChannelUid'          => '00000000-0000-0000-0000-000000000000',
					'SignupStoreUid'            => '00000000-0000-0000-0000-000000000000',
					'SignupStoreCode'           => null,
					'SignupStoreExternalId'     => null,
					'PriceLevelUid'             => '00000000-0000-0000-0000-000000000000',
					'PriceLevelCode'            => null,
					'FacebookId'                => null,
					'MobileDeviceId'            => null,
					'TwitterId'                 => null,
					'LoyaltyRedemptionDisabled' => true,
					'LoyaltyAccrualDisabled'    => true,
					'CustomerTypeUid'           => '00000000-0000-0000-0000-000000000000',
					'CustomerTypeCode'          => null,
					'NoEmail'                   => true,
					'NoTextMessages'            => true,
					'Verified'                  => true,
					'TaxExempt'                 => false,
					'AllowEditAtPOS'            => true,
					'WebAccount'                => true,
					'StateCode'                 => isset( $arrBillingDetails['state'] ) ? $arrBillingDetails['state'] : '',
					'StateName'                 => isset( $arrBillingDetails['state'] ) ? $arrBillingDetails['state'] : '',
					'CountryCode'               => isset( $arrBillingDetails['country'] ) ? $arrBillingDetails['country'] : '',
					'CountryName'               => isset( $arrBillingDetails['country'] ) ? $arrBillingDetails['country'] : '',
					'TransactionCount'          => 0,
					'TotalSales'                => 0,
					'CurrentBalance'            => 0,
					'CreditLimit'               => 0,
					'GuestCustomer'             => $guest_checkout,
					'SourceSystemUid'           => '00000000-0000-0000-0000-000000000000',
					'BirthDate'                 => array(),
					'CustomCode1'               => null,
					'CustomCode2'               => null,
					'CustomCode3'               => null,
					'CustomCode4'               => null,
					'CustomCode5'               => null,
					'CustomCode6'               => null,
					'CustomCode7'               => null,
					'CustomCode8'               => null,
					'LoyaltyCardNumbers'        => array(),
					'ProgramMembershipUids'     => array(),
					'GroupMembershipUids'       => array(),
					'ClubMemberships'           => $clubMemberships,
					'LoyaltyAccountUids'        => array(),
					'ExternalIds'               => $externalIds,
					'Code'                      => null,
					'Id'                        => 0,
					'Uid'                       => '00000000-0000-0000-0000-000000000000',
					'ExternalId'                => null,
					'Created'                   => null,
					'CreatedLocal'              => null,
					'Updated'                   => null,
					'UpdatedLocal'              => null,
					'Revision'                  => 0,
					'CustomFields'              => array(),
				);
				return $customer_data;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}
		
		/**
		 * Initalize makebLoyalCartShipping.
		 *
		 * @param  mixed $order_id
		 * @return void
		 */
		function makebLoyalCartShipping( $order_id ) {
			try {

				global $wpdb;
				$shipping_discount     = 0;
				$order                 = new WC_Order( $order_id );
				$order_data            = $order->get_data();
				$billing               = $order_data['billing'];
				$arrShippingDetails    = $order_data['shipping'];
				$shipping_charge       = $order_data['shipping_total'];
				$shipping_tax_total    = $order_data['shipping_tax'];
				$shipping_service_code = 'unmapped';
				$shipping_carrier_code = 'unmapped';
				$db_table_name         = $wpdb->prefix . 'woocommerce_order_items';
				$shipping_carrier_data = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_name  FROM $db_table_name WHERE order_id = %d AND order_item_type = %s", $order_id, 'shipping_carriercode' ) );
				if ( ! empty( $shipping_carrier_data ) ) {
					$shippingCarrierCode   = $shipping_carrier_data[0]->order_item_name;
					$shipping_carrier_code = ( $shippingCarrierCode != '' && $shippingCarrierCode != null && $shippingCarrierCode != '0' ) ? $shippingCarrierCode : 'unmapped';
				}
				$shipping_service_data = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_name  FROM $db_table_name WHERE order_id = %d AND order_item_type = %s", $order_id, 'shipping_servicecode' ) );
				if ( ! empty( $shipping_service_data ) ) {
					$shippingServCode      = $shipping_service_data[0]->order_item_name;
					$shipping_service_code = ( $shippingServCode != '' && $shippingServCode != null && $shippingServCode != '0' ) ? $shippingServCode : 'unmapped';
				}
				$db_table_name           = $wpdb->prefix . 'postmeta';
				$shipping_discnt_details = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $db_table_name WHERE post_id = %d AND meta_key = %s", $order_id, '_bloyal_order_shipment_discount_total' ) );
				if ( ! empty( $shipping_discnt_details ) ) {
					$shipping_discount = $shipping_discnt_details[0]->meta_value;
					$shipping_charge   = $shipping_charge + $shipping_discount;
				}

				if ( strcasecmp( $billing['first_name'], ( isset( $arrShippingDetails['first_name'] ) && $arrShippingDetails['first_name'] != '' ) ? $arrShippingDetails['first_name'] : $billing['first_name'] ) ||
				   strcasecmp( $billing['last_name'], ( isset( $arrShippingDetails['last_name'] ) && $arrShippingDetails['last_name'] != '' ) ? $arrShippingDetails['last_name'] : $billing['last_name'] ) ||
				   strcasecmp( $billing['company'], ( isset( $arrShippingDetails['company'] ) && $arrShippingDetails['company'] != '' ) ? $arrShippingDetails['company'] : $billing['company'] ) ||
				   strcasecmp( $billing['address_1'], ( isset( $arrShippingDetails['address_1'] ) && $arrShippingDetails['address_1'] != '' ) ? $arrShippingDetails['address_1'] : $billing['address_1'] ) ||
				   strcasecmp( $billing['address_2'], ( isset( $arrShippingDetails['address_2'] ) && $arrShippingDetails['address_2'] != '' ) ? $arrShippingDetails['address_2'] : $billing['address_2'] ) ||
				   strcasecmp( $billing['city'], ( isset( $arrShippingDetails['city'] ) && $arrShippingDetails['city'] != '' ) ? $arrShippingDetails['city'] : $billing['city'] ) ||
				   strcasecmp( $billing['state'], ( isset( $arrShippingDetails['state'] ) && $arrShippingDetails['state'] != '' ) ? $arrShippingDetails['state'] : $billing['state'] ) ||
				   strcasecmp( $billing['postcode'], ( isset( $arrShippingDetails['postcode'] ) && $arrShippingDetails['postcode'] != '' ) ? $arrShippingDetails['postcode'] : $billing['postcode'] ) ||
				   strcasecmp( $billing['country'], ( isset( $arrShippingDetails['country'] ) && $arrShippingDetails['country'] != '' ) ? $arrShippingDetails['country'] : $billing['country'] ) ) {
					$giftComment  = isset( $order_data['customer_note'] ) ? $order_data['customer_note'] : '';
					$instructions = null;
					$giftPackage  = true;
				} else {
					$instructions = isset( $order_data['customer_note'] ) ? $order_data['customer_note'] : '';
					$giftComment  = null;
					$giftPackage  = false;
				}

				$discountDetails[] = array(
					'Amount'            => $shipping_discount,
					'ExternallyApplied' => true,
					'RuleUid'           => '00000000-0000-0000-0000-000000000000',
					'RuleCode'          => null,
					'ReasonUid'         => '00000000-0000-0000-0000-000000000000',
					'ReasonCode'        => null,
				);

				$taxDetails[] = array(
					'ClassCode' => null,
					'Code'      => null,
					'Rate'      => null,
					'Amount'    => number_format(floor($shipping_tax_total*100)/100, 2),
				);

				$customFields = array();

				$recipient = array(
					'Id'           => 0,
					'Number'       => null,
					'FirstName'    => ( isset( $arrShippingDetails['first_name'] ) && $arrShippingDetails['first_name'] != '' ) ? $arrShippingDetails['first_name'] : $billing['first_name'],
					'LastName'     => ( isset( $arrShippingDetails['last_name'] ) && $arrShippingDetails['last_name'] != '' ) ? $arrShippingDetails['last_name'] : $billing['last_name'],
					'NickName'     => null,
					'CompanyName'  => ( isset( $arrShippingDetails['company'] ) && $arrShippingDetails['company'] != '' ) ? $arrShippingDetails['company'] : $billing['company'],
					'BirthDate'    => null,
					'Address1'     => ( isset( $arrShippingDetails['address_1'] ) && $arrShippingDetails['address_1'] != '' ) ? $arrShippingDetails['address_1'] : $billing['address_1'],
					'Address2'     => ( isset( $arrShippingDetails['address_2'] ) && $arrShippingDetails['address_2'] != '' ) ? $arrShippingDetails['address_2'] : $billing['address_2'],
					'City'         => ( isset( $arrShippingDetails['city'] ) && $arrShippingDetails['city'] != '' ) ? $arrShippingDetails['city'] : $billing['city'],
					'StateCode'    => ( isset( $arrShippingDetails['state'] ) && $arrShippingDetails['state'] != '' ) ? $arrShippingDetails['state'] : $billing['state'],
					'StateName'    => ( isset( $arrShippingDetails['state'] ) && $arrShippingDetails['state'] != '' ) ? $arrShippingDetails['state'] : $billing['state'],
					'State'        => ( isset( $arrShippingDetails['state'] ) && $arrShippingDetails['state'] != '' ) ? $arrShippingDetails['state'] : $billing['state'],
					'ZipCode'      => ( isset( $arrShippingDetails['postcode'] ) && $arrShippingDetails['postcode'] != '' ) ? $arrShippingDetails['postcode'] : $billing['postcode'],
					'PostalCode'   => ( isset( $arrShippingDetails['postcode'] ) && $arrShippingDetails['postcode'] != '' ) ? $arrShippingDetails['postcode'] : $billing['postcode'],
					'CountryCode'  => ( isset( $arrShippingDetails['country'] ) && $arrShippingDetails['country'] != '' ) ? $arrShippingDetails['country'] : $billing['country'],
					'CountryName'  => ( isset( $arrShippingDetails['country'] ) && $arrShippingDetails['country'] != '' ) ? $arrShippingDetails['country'] : $billing['country'],
					'Country'      => ( isset( $arrShippingDetails['country'] ) && $arrShippingDetails['country'] != '' ) ? $arrShippingDetails['country'] : $billing['country'],
					'Phone1'       => null,
					'Phone2'       => null,
					'MobilePhone'  => null,
					'FaxNumber'    => null,
					'EmailAddress' => null,
				);

				$shipping_data[] = array(
					'Uid'                         => '00000000-0000-0000-0000-000000000000',
					'Number'                      => null,
					'ExternalId'                  => null,
					'InventoryLocationUid'        => '00000000-0000-0000-0000-000000000000',
					'InventoryLocationCode'       => null,
					'InventoryLocationExternalId' => null,
					'ShipmentType'                => 'ShipTo',
					'Status'                      => 'Pending',
					'TrackingNumber'              => null,
					'CarrierCode'                 => $shipping_carrier_code,
					'ServiceUid'                  => '00000000-0000-0000-0000-000000000000',
					'ServiceCode'                 => $shipping_service_code,
					'FulfillmentHouse'            => null,
					'Charge'                      => $shipping_charge, // $order->shipping_total,
					'Recipient'                   => $recipient,
					'GiftPackage'                 => $giftPackage,
					'EstimatedShipDate'           => null,
					'ShippedDate'                 => null,
					'Discount'                    => $shipping_discount,
					'ExternallyAppliedDiscount'   => true,
					'DiscountReasonUid'           => '00000000-0000-0000-0000-000000000000',
					'DiscountReasonCode'          => null,
					'CouponUid'                   => '00000000-0000-0000-0000-000000000000',
					'CouponCode'                  => null,
					'DiscountDetails'             => $discountDetails,
					'TaxDetails'                  => $taxDetails,
					'ExternalShippingCalculation' => true,
					'ExternalTaxCalculation'      => true,
					'Title'                       => null,
					'GiftComment'                 => $giftComment,
					'Instructions'                => $instructions,
					'CustomFields'                => $customFields,
				);
				return $shipping_data;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}
		
		/**
		 * Initialize makebLoyalCartLines.
		 *
		 * @param  mixed $order_id
		 * @return void
		 */
		function makebLoyalCartLines( $order_id ) {
			try {
					global $wpdb;
					$db_table_name               = $wpdb->prefix . 'postmeta';
					$bloyal_order_level_discount = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $db_table_name WHERE post_id = %d AND meta_key = %s", $order_id, '_bloyal_order_level_discount_total' ) );
					$order                       = new WC_Order( $order_id );
					$db_table_name               = $wpdb->prefix . 'woocommerce_order_items';
					$order_data_product          = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_id, order_item_name FROM $db_table_name WHERE order_id = %d AND order_item_type = %s ", $order_id, 'line_item' ) );
					$count_order_product         = count( $order_data_product );
					$order_data_tax_code         = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_name FROM $db_table_name WHERE order_id = %d AND order_item_type = %s", $order_id, 'tax' ) );
				for ( $product_count = 0; $product_count < $count_order_product; $product_count++ ) {
					$db_table_name              = $wpdb->prefix . 'woocommerce_order_itemmeta';
					$order_item_id              = $order_data_product[ $product_count ]->order_item_id;
					$order_data_product_details = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $db_table_name WHERE order_item_id =  %d", $order_item_id ) );
					$count_attribute            = count( $order_data_product_details );
					$product_quantity           = 0;
					for ( $attribute_count = 0; $attribute_count < $count_attribute; $attribute_count++ ) {
						if ( $order_data_product_details[ $attribute_count ]->meta_key == '_qty' ) {
							$product_quantity = $order_data_product_details[ $attribute_count ]->meta_value;
						}
						if ( $order_data_product_details[ $attribute_count ]->meta_key == '_product_id' ) {
							$product_id = $order_data_product_details[ $attribute_count ]->meta_value;
						}
						if ( $order_data_product_details[ $attribute_count ]->meta_key == '_variation_id' ) {
							$variation_id = $order_data_product_details[ $attribute_count ]->meta_value;
						}
						if ( $order_data_product_details[ $attribute_count ]->meta_key == '_line_tax' ) {
							$line_tax = $order_data_product_details[ $attribute_count ]->meta_value;
						}
					}
					$line_level_discount_query = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $db_table_name WHERE meta_key = %s AND order_item_id = %d ", '_bloyal_item_level_discount', $order_item_id  ) );
					if ( ! empty( $line_level_discount_query ) ) {
						$line_level_discount = number_format( $line_level_discount_query[0]->meta_value, 2, '.', '' );
						$line_level_discount = $line_level_discount / $product_quantity;
					}

					$order_level_discount_query = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $db_table_name WHERE meta_key = %s AND order_item_id = %d ", '_bloyal_order_level_discount', $order_item_id ) );
					$items                      = $order->get_items();
					if ( $variation_id != '0' && $variation_id != null ) {
						$product            = new WC_Product_Variation( $variation_id );
						$product_sku        = $product->get_sku();
						$product_sale_price = $product->get_sale_price();
						$product_price      = $product->get_regular_price();
					} else {
						$product            = new WC_Product( $product_id );
						$product_sku        = $product->get_sku();
						$product_sale_price = $product->get_sale_price();
						$product_price      = $product->get_regular_price();
					}
					$querystr_get_order_method_id = "SELECT meta_value FROM $db_table_name WHERE meta_key = %s AND order_item_id = %d  ";
					$order_data_method_id         = $wpdb->get_results( $wpdb->prepare( $querystr_get_order_method_id, 'method_id', 2 ) );
					$order_data_method_id         = explode( ':', $order_data_method_id[0]->meta_value );
					$tax_code                     = '';
					if ( ! empty( $order_data_tax_code ) ) {
						$tax_code = $order_data_tax_code[0]->order_item_name;
					}
					$order_discount = 0;
					if ( ! empty( $order_level_discount_query ) ) {
						$order_discount = number_format( $order_level_discount_query[0]->meta_value, 2, '.', '' );
						$order_discount = $order_discount / $product_quantity;
					}
					$tax_details_perproduct[] = array(
						'ClassCode' => '',
						'Code'      => $tax_code,
						'Rate'      => null,
						'Amount'    => $line_tax,
					);
					$discountDetails[]        = array(

						'Amount'            => $line_level_discount,
						'ExternallyApplied' => true,
						'RuleUid'           => '00000000-0000-0000-0000-000000000000',
						'RuleCode'          => null,
						'ReasonUid'         => '00000000-0000-0000-0000-000000000000',
						'ReasonCode'        => null,
					);
					$taxDetails[]             = array(

						'ClassCode' => null,
						'Code'      => $tax_code,
						'Rate'      => null,
						'Amount'    => number_format(floor($line_tax*100)/100, 2),
					);
					$customFields             = array();
					$line_data[]              = array(
						'Uid'                       => '00000000-0000-0000-0000-000000000000',
						'ProductUid'                => '00000000-0000-0000-0000-000000000000',
						'ProductCode'               => $product_sku,
						'ProductName'               => $order_data_product[ $product_count ]->order_item_name,
						'ProductExternalId'         => $order_data_product_details[0]->meta_value,
						'Number'                    => null,
						'LineExternalId'            => null,
						'ShipmentUid'               => '00000000-0000-0000-0000-000000000000',
						'ShipmentNumber'            => null,
						'Description'               => null,
						'Comment'                   => null,
						'SalesRepUid'               => '00000000-0000-0000-0000-000000000000',
						'SalesRepCode'              => null,
						'SalesRepExternalId'        => null,
						'Price'                     => $product_price,
						'Quantity'                  => $product_quantity,
						'SalePrice'                 => $product_sale_price,
						'ExternallyAppliedPricing'  => true,
						'PricingReasonUid'          => '00000000-0000-0000-0000-000000000000',
						'PricingReasonCode'         => null,
						'PricingRuleUid'            => '00000000-0000-0000-0000-000000000000',
						'PricingRuleCode'           => null,
						'Discount'                  => $line_level_discount,
						'ExternallyAppliedDiscount' => true,
						'DiscountReasonUid'         => '00000000-0000-0000-0000-000000000000',
						'DiscountReasonCode'        => 'string',
						'CouponUid'                 => '00000000-0000-0000-0000-000000000000',
						'CouponCode'                => null,
						'OrderDiscount'             => $order_discount,
						'ExternallyAppliedTax'      => true,
						'DiscountDetails'           => $discountDetails,
						'TaxDetails'                => $taxDetails,
						'CustomFields'              => $customFields,
					);
					unset( $taxDetails );
					unset( $discountDetails );
				}
					return $line_data;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}
		
		/**
		 * Initialize makebLoyalCartPayment.
		 *
		 * @param  mixed $order_id
		 * @return void
		 */
		function makebLoyalCartPayment( $order_id ) {
			try {
				global $wpdb;
				$customFields    = array();
				$db_table_name   = $wpdb->prefix . 'postmeta';
				$order           = new WC_Order( $order_id );
				$paymentMethod   = $order->get_payment_method();
				$orderTotal      = $order->get_total();
				$transactionCode = $order->get_transaction_id();
				$authCode        = '';
				$authCodeData    = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $db_table_name WHERE post_id = %d AND meta_key = %s ", $order_id, '_wc_usa_epay_credit_card_authorization_code' ) );
				if ( ! empty( $authCodeData ) ) {
					$authCode = $authCodeData[0]->meta_value;
				}
				$payment_data[] = array(

					'PaymentType'       => 'Undefined',
					'TenderCode'        => $paymentMethod,
					'Uid'               => '00000000-0000-0000-0000-000000000000',
					'External'          => true,
					'Amount'            => $orderTotal,
					'AuthorizationCode' => $authCode,
					'TransactionCode'   => $transactionCode,
					'PaymentToken'      => null,
					'Title'             => null,
					'PaymentMethodUid'  => '00000000-0000-0000-0000-000000000000',
					'AmountRefunded'    => 0,
					'AmountCaptured'    => 0,
					'AmountApproved'    => 0,
					'CancelTime'        => null,
					'ApproveTime'       => null,
					'CaptureTime'       => null,
					'CustomFields'      => $customFields,
				);

				return $payment_data;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}
	}
}
