<?php

require_once BLOYAL_DIR . '/app/controller/class-alertpopupcontroller.php';

if ( ! class_exists( 'PromotionClassController' ) ) {

	class PromotionClassController {

		/**
		 * @param WC_Tax instance
		 * @return object
		 */
		private $obj_WC_Tax_Class;

		function __construct() {
			$this->obj_alert               = new BLOYAL_AlertPopUpController();
			$this->arrTenderPaymentMapping = get_option( 'bloyal_tender_payments_mapping' );
			$this->bloyal_use_order_engine = get_option( 'bloyal_use_order_engine' );
		}

		/**
		 * @param object $cart_object
		 * @param object $bloyal_cart_object
		 * @return void
		 */

		public function add_promotions( $cart_object, $bloyal_cart_object ) {
			$line_items = array();
			$external   = $bloyal_cart_object->data->Cart->ExternallyAppliedDiscount;
			if ( ! $external ) {
				$discountCoupons = $cart_object->get_coupon_discount_totals();
				foreach ( $discountCoupons as $key => $value ) {
					WC()->cart->remove_coupons();
					wc_add_notice( 'Coupon(s) has been removed.', 'success' );
				}
			}
			if ( isset( $bloyal_cart_object->data->Cart->Lines ) ) {
				foreach ( $bloyal_cart_object->data->Cart->Lines as $line ) {
					$sku                = strtolower( $line->ProductCode );
					$product_dis        = $order_dis = $qty = 0;
					$orderLevelDiscount = 0;
					if ( isset( $line_items[ $sku ] ) ) {
						$product_dis = $line_items[ $sku ]['product_level'];
						$order_dis   = $line_items[ $sku ]['order_level'];
						$qty         = $line_items[ $sku ]['qty'];
					}
					if ( ! $external ) {
						$orderLevelDiscount = ( ( $line->OrderDiscount * $line->Quantity ) + $order_dis );
					}
					$line_items[ $sku ] = array(
						'sku'           => $sku,
						'qty'           => $line->Quantity + $qty,
						'sale_price'    => $line->SalePrice,
						'product_level' => ( ( $line->Discount * $line->Quantity ) + $product_dis ),
						'order_level'   => $orderLevelDiscount,
					);
				}
				$orderMetaData    = array();
				$lines            = array();
				$externalDiscount = 0;
				$orderDiscount    = 0;
				foreach ( $cart_object->cart_contents as $key => $value ) {
					$itemSKu = strtolower( $value['data']->get_sku() );
					$originalPrice = $value['data']->get_regular_price();
					$externalsalePrice = $value['data']->get_sale_price();
					$total   = 0;
					
					if ( isset( $line_items[ $itemSKu ] ) ) {
						$product_dis = $line_items[ $itemSKu ]['product_level'];
						$order_dis   = $line_items[ $itemSKu ]['order_level'];
						$sale_price  = $line_items[ $itemSKu ]['sale_price'];
						$qty         = $line_items[ $itemSKu ]['qty'];
						if($externalsalePrice != "") {
							$total       = $sale_price - ( ( $product_dis + $order_dis ) / $qty );
						}else {
							$total       = $originalPrice - ( ( $product_dis + $order_dis ) / $qty );
						}
					}
					
					if ( $total || $product_dis || $order_dis ) {
						$finalPrice    = $total;
						$value['data']->set_price( $finalPrice );
						//$value['data']->set_sale_price( $finalPrice );
						add_filter( 'woocommerce_cart_item_price', 'bloyal_change_cart_product_price', 10, 3 );
					}
					$lines[ $value['key'] ]['_bloyal_item_level_discount']  = $product_dis;
					$lines[ $value['key'] ]['_bloyal_order_level_discount'] = $order_dis;
					$lines[ $value['key'] ]['_bloyal_sale_price_discount']  = $sale_price;
				}
				if ( $bloyal_cart_object->data->Cart->ExternallyAppliedDiscount ) {
					$externalDiscount = $bloyal_cart_object->data->Cart->Discount;
				} else {
					$orderDiscount = $bloyal_cart_object->data->Cart->Discount;
				}
				$orderMetaData['_bloyal_extenal_discount_total']        = $externalDiscount;
				$orderMetaData['_bloyal_order_level_discount_total']    = $orderDiscount;
				$orderMetaData['_bloyal_order_shipment_discount_total'] = $bloyal_cart_object->data->Cart->Shipments[0]->Discount;
				$orderMetaData['line_items']                            = $lines;
				WC()->session->set( 'orderMetaData', $orderMetaData );
			}
		}

		/**
		 * Function to call the bLoyal aprrove cart API
		 *
		 * @param integer $order_id
		 *
		 * @return boolean
		 */

		public function bloyal_commit_cart( $order_id, $bloyal_obj ) {
			try {
				$payment_controller         = new PaymentController();
				$loyalty_payment_controller = new LoyaltyDollarPaymentController();
				$cart_uid                   = $bloyal_obj->get_uid();
				$bloyal_cart_data           = WC()->session->get( 'bloyal_cart_data' );
				$bloyal_applied_coupons     = array();
				$temp_payment               = array();
				global $wpdb;
				$third_party_cart_uid = WC()->session->get( 'third_party_cardId' );
				$cart_uid             = ( $third_party_cart_uid != null && $third_party_cart_uid != '' ) ? $third_party_cart_uid : $bloyal_obj->get_uid();
				if ( $cart_uid ) {
					$order                         = new WC_Order( $order_id );
					$db_table_name                 = $wpdb->prefix . 'postmeta';
					$order_id_update_query_cart_ui = "UPDATE $db_table_name SET post_id ='" . $order_id . "' WHERE meta_value = '" . $cart_uid . "' AND meta_key = '_cart_uid'";
					$wpdb->query( $order_id_update_query_cart_ui );
					$TransactionCode = $order->get_transaction_id();
					$querystr        = "select meta_value from $db_table_name where post_id = $order_id AND meta_key = '_wc_usa_epay_credit_card_authorization_code' ";
					$authCodeData    = $wpdb->get_results( $querystr );
					$authCode        = '';
					if ( ! empty( $authCodeData ) ) {
						$authCode = $authCodeData[0]->meta_value;
					}
					if ( isset( $this->arrTenderPaymentMapping[ $order->get_payment_method() ] ) && $this->arrTenderPaymentMapping[ $order->get_payment_method() ] != '0' && $this->arrTenderPaymentMapping[ $order->get_payment_method() ] != '' ) {
						$tenderCode = $this->arrTenderPaymentMapping[ $order->get_payment_method() ];
					} else {
						$tenderCode = $order->get_payment_method();
					}
					$payment_method = $order->get_payment_method();
					if ( $payment_method == 'bloyal_gift_card' ) {
						$TransactionCode = $wpdb->get_results( "SELECT meta_value FROM $db_table_name WHERE post_id = $order_id AND meta_key = '_gift_card_transaction_id' " );
					}
					if ( $payment_method == 'bloyal_loyalty_dollar' ) {
						$TransactionCode = $wpdb->get_results( "SELECT meta_value FROM $db_table_name WHERE post_id = $order_id AND meta_key = '_loyalty_dollars_transaction_id' " );
					}
					if ( $payment_method == 'bloyal_stored_payment_method' ) {
						$TransactionCode = $wpdb->get_results( "SELECT meta_value FROM $db_table_name WHERE post_id = $order_id AND meta_key = '_stored_payment_transaction_id' " );
					}
					if ( is_array( $TransactionCode ) && ! empty( $TransactionCode ) ) {
						$TransactionCode = $TransactionCode[0]->meta_value;
					}
					$payments            = array();
					$payment_difference  = $order->get_total();
					$partial_gift_amount = WC()->session->get( 'partial_gift_amount' );
					if ( is_float( $partial_gift_amount ) && $partial_gift_amount > 0 ) {
						$cardNumber          = WC()->session->get( 'partial_gift_number' );
						$redeem_result       = $payment_controller->redeemGiftCardBalance( $cardNumber, $partial_gift_amount, $order_id );
						$auth_code_gift      = $redeem_result['auth_code'];
						$TransactionCodeGift = $redeem_result['transaction_id'];
						$partial_payment     = array(
							'TenderCode'      => $this->arrTenderPaymentMapping['bloyal_gift_card'],
							'Amount'          => $partial_gift_amount,
							'Captured'        => get_option( 'bloyal_use_order_engine' ) == 'true' ? false : true,
							'AuthCode'        => (string) $auth_code_gift,
							'TransactionCode' => (string) $TransactionCodeGift,
						);
						array_push( $payments, $partial_payment );
						update_post_meta( $order_id, '_bloyal_gift_card_total', $partial_gift_amount, true );
					}
					$partial_loyalty_amount = WC()->session->get( 'partial_loyalty_amount' );
					if ( is_float( $partial_loyalty_amount ) && $partial_loyalty_amount > 0 ) {
						$redeem_result          = $loyalty_payment_controller->redeemLoyaltyDollarBalance( $partial_loyalty_amount, $order_id );
						$TransactionCodeLoyalty = $redeem_result['transaction_id'];
						$auth_code_loyalty      = $redeem_result['auth_code'];
						$partial_payment        = array(
							'TenderCode'      => $this->arrTenderPaymentMapping['bloyal_loyalty_dollar'],
							'Amount'          => $partial_loyalty_amount,
							'Captured'        => get_option( 'bloyal_use_order_engine' ) == 'true' ? false : true,
							'AuthCode'        => (string) $auth_code_loyalty,
							'TransactionCode' => (string) $TransactionCodeLoyalty,
						);
						array_push( $payments, $partial_payment );
						update_post_meta( $order_id, '_bloyal_loyalty_dollar_total', $partial_loyalty_amount, true );
					}
					$temp_payment = array(
						'TenderCode'      => $tenderCode,
						'Amount'          => $payment_difference,
						'Captured'        => get_option( 'bloyal_use_order_engine' ) == 'true' ? false : true,
						'AuthCode'        => (string) $authCode,
						'TransactionCode' => (string) $TransactionCode,
					);
					array_push( $payments, $temp_payment );
					$cart_request        = array(
						'CartUid'         => $cart_uid,
						'CartExternalId'  => (string) $order->get_id(),
						'Payments'        => $payments,
						'ReferenceNumber' => (string) $order_id,
					);
					$commit_cart_request = json_encode( $cart_request );
					bLoyalLoggerService::write_custom_log( "\n\r==========Commit Cart Request ==========\r\n" . $commit_cart_request . "\r\n---------------\r\n", 1 );
					$result               = $bloyal_obj->send_curl_request( $cart_request, 'carts/commands/commit', 'loyaltyengine', 1 );
					$commit_cart_response = json_encode( $result );
					bLoyalLoggerService::write_custom_log( "\n\r==========Commit Cart Response ==========\r\n" . $commit_cart_response . "\r\n###########################################################################\r\n", 1 );
					if ( $result->status == 'success' || $result->message == 'Cart cannot be submitted.  Cart already exists in Captured state.' ) {
						if ( $this->bloyal_use_order_engine == 'true' ) {
							$order->add_order_note( 'Order successfully submitted to bLoyal Order Engine', 0, true );
						}
					} else {
						$order->add_order_note( $result->message, 0, true );
					}
					foreach ( $bloyal_cart_data->LoyaltySummary->AppliedCoupons as $applied_coupon ) {
						if ( $applied_coupon->Redeemed ) {
							array_push( $bloyal_applied_coupons, $applied_coupon->Code );
						}
					}
					$shippingDate         = '';
					$isShippToDiffAddress = WC()->session->get( 'ship_to_different_address' );
					$shippingGiftMessage  = WC()->session->get( 'bloyal_shipping_gift_message' );
					$shippingPhone        = null;
					$shippingEmail        = null;
					if ( $isShippToDiffAddress ) {
						$shippingDate  = WC()->session->get( 'bloyal_shipping_date_of_birth' );
						$shippingPhone = WC()->session->get( 'bloyal_shipping_phone' );
						$shippingEmail = WC()->session->get( 'bloyal_shipping_email' );
					} elseif($bloyal_shipping_services['isGuest'] == 0){
						// this is for showing shippingDate, shippingPhone and shippingEmail in woo admin 12/12/2023
						$shippingDate = WC()->session->get( 'bloyal_shipping_date_of_birth' );
						$shippingPhone = WC()->session->get( 'shipping_phone' );
						$shippingEmail = $bloyal_cart_data->Cart->Shipments[0]->EmailAddress;
					}elseif ( ! WC()->session->get( 'is_virtual_porduct_order' ) ) {
						$shippingDate = WC()->session->get( 'bloyal_billing_date_of_birth' );
					}
					$order = new WC_Order( $order_id );

					if ( $order->has_shipping_method( 'bloyal_pickup_store' ) ) {
						update_post_meta( $order_id, '_bloyal_pickup_location_code', WC()->session->get( 'session_store_code' ), true );
						update_post_meta( $order_id, '_bloyal_pickup_location_address', WC()->session->get( 'session_store_address' ), true );
					}

					if ( $order->has_shipping_method( 'bloyal_pickup_store' ) ) {
						delete_post_meta( $order_id, '_shipping_first_name', '', true );
						delete_post_meta( $order_id, '_shipping_last_name', '', true );
						delete_post_meta( $order_id, '_shipping_company', '', true );
						delete_post_meta( $order_id, '_shipping_address_1', '', true );
						delete_post_meta( $order_id, '_shipping_address_2', '', true );
						delete_post_meta( $order_id, '_shipping_city', '', true );
						delete_post_meta( $order_id, '_shipping_state', '', true );
						delete_post_meta( $order_id, '_shipping_postcode', '', true );
						delete_post_meta( $order_id, '_shipping_country', '', true );
					} else {
						update_post_meta( $order_id, '_bloyal_shipping_date_of_birth', $shippingDate, true );
						update_post_meta( $order_id, '_bloyal_shipping_phone', $shippingPhone, true );
						update_post_meta( $order_id, '_bloyal_shipping_email', $shippingEmail, true );
						update_post_meta( $order_id, '_bloyal_shipping_gift_message', $shippingGiftMessage, true );
					}
					update_post_meta( $order_id, '_bloyal_billing_date_of_birth', WC()->session->get( 'bloyal_billing_date_of_birth' ), true );
					update_post_meta( $order_id, '_bloyal_applied_coupons', $bloyal_applied_coupons, true );
					if ( isset( $bloyal_cart_data->Cart->Shipments[0]->Discount ) ) {
						update_post_meta( $order_id, '_bloyal_order_shipment_discount_total', $bloyal_cart_data->Cart->Shipments[0]->Discount, true );
					}
					$sale_price_discount = array();
					$discount            = array();
					$order_discount      = array();
					foreach ( $order->get_items() as $item_id => $item_obj ) {
						if ( get_post_meta( $item_obj->get_product_id(), '_manage_stock' )[0] == 'yes' ) {
							$time      = current_time( 'mysql' );
							$post_data = array(
								'ID'                => $item_obj->get_product_id(),
								'post_modified'     => $time,
								'post_modified_gmt' => get_gmt_from_date( $time ),
							);
							wp_update_post( $post_data );
						}
						foreach ( $bloyal_cart_data->Cart->Lines as $line ) {
							$product     = $item_obj->get_product();
							$productCode = is_object( $product ) ? $product->get_sku() : null;

							if ( strtolower( $productCode ) == strtolower( $line->ProductCode ) ) {
								$sale_price_discount[ $productCode ] = isset( $sale_price_discount[ $productCode ] ) ? $sale_price_discount[ $productCode ] + ( $line->Price - $line->SalePrice ) : ( $line->Price - $line->SalePrice );
								$discount[ $productCode ]            = isset( $discount[ $productCode ] ) ? $discount[ $productCode ] + ( $line->Discount * $line->Quantity ) : ( $line->Discount * $line->Quantity );
								$order_discount[ $productCode ]      = isset( $order_discount[ $productCode ] ) ? $order_discount[ $productCode ] + ( $line->OrderDiscount * $line->Quantity ) : $line->OrderDiscount * $line->Quantity;
								wc_update_order_item_meta( $item_id, '_bloyal_sale_price_discount', $sale_price_discount[ $productCode ] );
								wc_update_order_item_meta( $item_id, '_bloyal_item_level_discount', $discount[ $productCode ] );

								if ( isset( $bloyal_cart_data->Cart->ExternallyAppliedDiscount ) && $bloyal_cart_data->Cart->ExternallyAppliedDiscount == true ) {
									wc_update_order_item_meta( $item_id, '_bloyal_external_discount', $order_discount[ $productCode ] );
									update_post_meta( $order_id, '_bloyal_extenal_discount_total', $bloyal_cart_data->Cart->Discount, true );
								} else {
									wc_update_order_item_meta( $item_id, '_bloyal_order_level_discount', $order_discount[ $productCode ] );
									update_post_meta( $order_id, '_bloyal_order_level_discount_total', $bloyal_cart_data->Cart->Discount, true );
								}
								wc_update_order_item_meta( $item_id, '_original_price', $line->Price );
							}
						}
					}
				}
				$bloyal_shipping_services = WC()->session->get( 'bloyal_shipping_services' );
				$db_table_name            = $wpdb->prefix . 'woocommerce_order_items';
				$wpdb->insert(
					$db_table_name,
					array(
						'order_item_name' => $bloyal_shipping_services['CarrierCode'] == null ? '' : $bloyal_shipping_services['CarrierCode'],
						'order_item_type' => 'shipping_carriercode',
						'order_id'        => $order_id,
					),
					array(
						'%s',
						'%s',
						'%d',
					)
				);
				$wpdb->insert(
					$db_table_name,
					array(
						'order_item_name' => $bloyal_shipping_services['ServiceCode'] == null ? '' : $bloyal_shipping_services['ServiceCode'],
						'order_item_type' => 'shipping_servicecode',
						'order_id'        => $order_id,
					),
					array(
						'%s',
						'%s',
						'%d',
					)
				);
				$wpdb->insert(
					$db_table_name,
					array(
						'order_item_name' => $bloyal_shipping_services['isGuest'] == null ? '' : $bloyal_shipping_services['isGuest'],
						'order_item_type' => 'isGuest',
						'order_id'        => $order_id,
					),
					array(
						'%s',
						'%s',
						'%d',
					)
				);
				
				WC()->session->set( 'bloyal_alerts_data', null );
				WC()->session->set( 'bloyal_cart_alerts_data', null );
				WC()->session->set( 'bloyal_uid', null );
				WC()->session->set( 'bloyal_coupon', null );
				WC()->session->set( 'bloyal_cart_data', null );
				WC()->session->set( 'bloyal_shipping_cart_data', null );
				WC()->session->set( 'bloyal_shipping_rate_cost', null );
				WC()->session->set( 'guest_user_data', null );
				WC()->session->set( 'bloyal_shipping_services', null );
				WC()->session->set( 'bloyal_info_alerts_data', null );
				WC()->session->set( 'third_party_cardId', null );
				WC()->session->set( 'is_virtual_porduct_order', null );
				WC()->session->set( 'bloyal_billing_date_of_birth', null );
				WC()->session->set( 'bloyal_shipping_date_of_birth', null );
				WC()->session->set( 'ship_to_different_address', null );
				WC()->session->set( 'billing_birth_date', null );
				WC()->session->set( 'shipping_birth_date', null );
				WC()->session->set( 'shipping_phone', null );
				WC()->session->set( 'bloyal_shipping_phone', null );
				WC()->session->set( 'bloyal_shipping_email', null );
				WC()->session->set( 'bloyal_shipping_gift_message', null );
				WC()->session->set( 'check_save_shipping_address', null );
				WC()->session->set( 'current_shipping_address_uid', null );
				WC()->session->set( 'partial_gift_amount', null );
				WC()->session->set( 'partial_gift_number_increment_of', null );
				WC()->session->set( 'partial_loyalty_amount', null );
				WC()->session->set( 'session_store_code', null );
				WC()->session->set( 'session_store_address', null );
				WC()->session->set( 'partial_loyalty_amount_redeem', null );
				WC()->session->set( 'partial_gift_amount_redeem', null );
				WC()->session->set( 'loyalty_balance_check', null );
				WC()->session->set( 'partial_gift_number', null );
				WC()->session->set( 'stored_payment_uid', null);
			} catch ( Exception $e ) {
				$this->log( __FUNCTION__, 'Error in approve/commit cart. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}
	}
}
