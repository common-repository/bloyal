<?php

defined( 'ABSPATH' ) || die( 'No script!' );

require_once BLOYAL_DIR . '/app/controller/class-bloyalcontroller.php';

if ( ! class_exists( 'ShippingController' ) ) {

	class ShippingController {

		/**
		 * @param wcShippinRates
		 * @return array
		 */
		private $wcShippinRates;
		/**
		 * Function to apply the Shipping Charge.
		 *
		 * @param array $package
		 * @return $shippingRate
		 */

		public function calculateShipping( $package ) {
			$shipping_request = $this->makeShippingRequest();
			$bloyal_obj       = new BloyalController();
			$response_decode  = $bloyal_obj->send_curl_request( $shipping_request, '/shippingoptions/commands/calculate', 'loyaltyengine', 1 );
			$response         = json_encode( $response_decode );
			bLoyalLoggerService::write_custom_log( "\n\r==========Calculate Shipping Request ============ \r\n" . json_encode( $shipping_request ), 1 );
			
			bLoyalLoggerService::write_custom_log( "\n\r==========Calculate Shipping Response ============ \r\n" . $response, 1 );
			$shippingRate = $this->makeShippingRate( $response );
			return $shippingRate;
		}

		/**
		 * Function to make the Shipping Request data.
		 *
		 * @param array $cart_request
		 * @return shipping_request
		 */

		public function makeShippingRequest() {
			$products                 = WC()->cart->get_cart();
			$customerDetails          = WC()->session->customer;
			$product_data             = array();
			$shipping_packages_data   = array();
			$floatTotalProductsWeight = 0;
			$bloyal_cart_data = WC()->session->get( 'bloyal_cart_data');

			// code to add decimal digits in weight value
			if ( ! empty( $products ) ) {
				foreach ( $products as $key => $product ) {
					$product_data = $product['data'];
					if ( ! empty( $product_data ) ) {
						$productQuantity          = $product['quantity'];
						$product_weight_value = $product_data->get_weight() ? $product_data->get_weight() : 0;
						$weightCalculation = ($product_weight_value * $productQuantity);
						$weight_value_multiply_qty += floatval($weightCalculation);	
					}
				}
			}
			if ( isset( $bloyal_cart_data->Cart->Lines ) ) {
				$price = 0;
				foreach ( $bloyal_cart_data->Cart->Lines as $line ) {
						$bloyalproductQuantity            = $line->Quantity;
						$price                     += $line->SalePrice * $bloyalproductQuantity;
				}
			}
			
			//wc-ajax update_order_review request code for checkout address
			if(isset($_REQUEST['wc-ajax']) && ($_REQUEST['wc-ajax'] == "update_order_review")) {
				if ( $_REQUEST['s_state'] == '' || $_REQUEST['s_state'] == null ) {
					$base_state_name = wc_get_base_location();
					$state_name      = $base_state_name['state'];
					$country_name    = $base_state_name['country'];
				} else {
					$state_name   = $_REQUEST['s_state'];
					$country_name = $_REQUEST['s_country'];
				}
				$shipping_request = array(
					"Shipments" => array(
						array(
						'Address'      => array(
							"City"          =>$_REQUEST['s_city'],
							"State"  		=>$state_name,
							"PostalCode"    =>$_REQUEST['s_postcode'],
							"Country"		=>$country_name
						),
						"Price" => $price,
						"Weight" => array(
							'Value' => $weight_value_multiply_qty,
							'Unit'  => 'Pounds'
							)
						)
					)
				);
			}else {
				
				bLoyalLoggerService::write_custom_log( "\n\r========== Shipping Request Data ============ \r\n" . json_encode( $_REQUEST ), 1 );
				
			    $shipping_request = array(
					"Shipments" => array(
						array(
							"Address" => array(
							"City"          =>$customerDetails['shipping_city'],

							"State"  		=>(($_REQUEST['shipping_state'] != '' || $_REQUEST['calc_shipping_state'] != '') ? ($_REQUEST['shipping_state'] ?? $_REQUEST['calc_shipping_state']) : $customerDetails['shipping_state']),


							"PostalCode"    =>(($_REQUEST['shipping_postcode'] != '' || $_REQUEST['calc_shipping_postcode'] != '') ? ($_REQUEST['shipping_postcode'] ?? $_REQUEST['calc_shipping_postcode']) : $customerDetails['shipping_postcode']),

							
							"Country"		=>(($_REQUEST['shipping_country'] != '' || $_REQUEST['calc_shipping_country'] != '') ? ($_REQUEST['shipping_country'] ?? $_REQUEST['calc_shipping_country']) : $customerDetails['shipping_country'])

							),
							"Price" => $price,
							"Weight" => array(
								'Value' => $weight_value_multiply_qty,
								'Unit'  => 'Pounds'
								)
							
						)
						)	
					);
					
			}
			return $shipping_request;
		}

		/**
		 * Function to Make the shipping rate
		 *
		 * @param array $shipping_responce.
		 * @return shipping_rate
		 */

		 public function makeShippingRate( $shipping_response ) {
			$shippingsData = json_decode( $shipping_response );
			$shippingsData = $shippingsData->data;
			$shippingRates = array();
			foreach ( $shippingsData as $shipping ) {
				// updated code by chetu - start
				$shippingRatesData = $shipping->Rates;
				foreach($shippingRatesData as $shippingRatesRes) {
					$shippingRates [] = array(
						'id'        => $shippingRatesRes->ServiceUid,
						'label'     => $shippingRatesRes->CarrierName . '(' . $shippingRatesRes->ServiceName . ')',
						'cost'      => $shippingRatesRes->Amount,
						'taxes'     => false,
						'meta_data' => array(
							'bloyal_meta_data' => array(
								'ServiceUid'  => $shippingRatesRes->ServiceUid,
								'CarrierCode' => $shippingRatesRes->CarrierCode,
								'CarrierName' => $shippingRatesRes->CarrierName,
								'ServiceCode' => $shippingRatesRes->ServiceCode,
								'ServiceName' => $shippingRatesRes->ServiceName,
								'Amount'      => $shippingRatesRes->Amount,
								'RateToken'   => $shippingRatesRes->CarrierUid,
							),
						),
					);
				}
				// updated code by chetu - end
				return $shippingRates;
			}
		}

		/**
		 * Function to check is_bloyal_rate
		 *
		 * @param $rate
		 * @return $is_bloyal_rate
		 */

		public function isBloyalRate( $rate ) {
			$this->wcShippinRates = WC()->session->get( 'custom_bloyal_shipping_rates' );
			$isFound              = false;
			if ( ! empty( $this->wcShippinRates ) ) {
				foreach ( $this->wcShippinRates as $key => $value ) {
					if ( $value['id'] == $rate[0] ) {
						$isFound = $key;
					}
				}
			}
			return $isFound;
		}

		/**
		 * Function to Make bloyal Shipments
		 *
		 * @param $bloyalCustomRate
		 * @param $order_id
		 * @return $shipments  array
		 */

		public function makeBloyalShipmentRequest( $bloyalCustomRate ) {
			
			$shippingCharge           = 0;
			$taxDetails               = array();
			$isBloyalTaxEnabled       = get_option( 'bloyal_applied_taxes' );
			$externally_applied_tax   = $isBloyalTaxEnabled == 'false' ? true : false;
			$float_shipping_total_tax = WC()->session->get( 'applied_shipping_taxes' );
			$is_virtual_porduct_order = WC()->session->get( 'is_virtual_porduct_order' );
			$isbloyal_shipping_charges = get_option( 'bloyal_applied_shipping_charges' );
			$externally_applied_charge = false;
			if ( $isbloyal_shipping_charges == 'false' ) {
				$externally_applied_charge = true;
			}
			$select_shipping_name = '';
			if ( is_integer( $bloyalCustomRate ) ) {
				$wcShippingRateMetaData = $this->wcShippinRates[ $bloyalCustomRate ];
			} else {
				
				$shippingCharge               = WC()->session->cart_totals['shipping_total'];
				$shippingCharge               = (int) $shippingCharge;
				$active_shipping_method_name  = get_option( 'bloyal_shipping_method_name' );
				$arrShippingServiceData       = explode( ':', WC()->session->get( 'chosen_shipping_methods' )[0] );
				$select_shipping_method       = 'method_' . $arrShippingServiceData[0];
				$select_shipping_name         = $arrShippingServiceData[0];
				$bloyal_shipping_carrier_code = null;
				$bloyal_shipping_service_code = $arrShippingServiceData[0];	

				if ( $select_shipping_name == 'free_shipping' || $select_shipping_name == 'flat_rate' || $select_shipping_name == 'local_pickup' ) {
					// $isFound = array_search( $select_shipping_method, $active_shipping_method_name );				
					if ( is_integer( $isFound ) ) {
						$shipCarrierCode              = get_option( 'bloyal_shipping_carrier' )[ $isFound ];
						$shipServiceCode              = get_option( 'bloyal_shipping_service' )[ $isFound ];
						$bloyal_shipping_carrier_code = ( $shipCarrierCode != '' && $shipCarrierCode != null && $shipCarrierCode != '0' ) ? $shipCarrierCode : null;
						$bloyal_shipping_service_code = ( $shipServiceCode != '' && $shipServiceCode != null && $shipServiceCode != '0' ) ? $shipServiceCode : $arrShippingServiceData[0];
					}
				
					$bloyal_shipping_service_code = $select_shipping_name == 'bloyal_pickup_store' ? null : $bloyal_shipping_service_code;
				}

				$wcShippingRateMetaData['meta_data']['bloyal_meta_data'] = array(
					'CarrierCode' => $bloyal_shipping_carrier_code,
					'ServiceCode' => $bloyal_shipping_service_code,
					'Amount'      => $shippingCharge,
				);
			}

		    if ( $externally_applied_tax ) {
				$taxDetails[] = array(
					'Amount'    => is_array( $float_shipping_total_tax ) ? array_sum( $float_shipping_total_tax ) : 0,
					'ClassCode' => '',
					'Rate'      => '',
				);
			}
			
			$customer        = WC()->session->customer;
			$guest_user_data = WC()->session->get( 'guest_user_data' );
			
			$order_notes     = $ship_phone = $ship_email = $ship_birth_date = '';
			if ( $select_shipping_name == 'bloyal_pickup_store' ) {
				$selected_shipping         = WC()->session->get( 'chosen_shipping_methods' );
				$pickup_inventory_location = get_option( 'bloyal_shipping_pickup' );
				$shipping_instance_id      = explode( ':', WC()->session->get( 'chosen_shipping_methods' )[0] );
				$saved_shippinf_options    = get_option( 'bloyal_shipping_pickup' );
				if(!empty($saved_shippinf_options)){
					$pickup_location       = $saved_shippinf_options[ 'woocommerce_local_pickup_' . $shipping_instance_id[1] . '_settings' ];
				}else{
                    $pickup_location       =  '';
				}						
				$location_code             = WC()->session->get( 'session_store_code' );
				$location_uid              = WC()->session->get( 'session_store_code' );
				if ( $pickup_location == 'Default' ) {
					$location_code = null;
					$location_uid  = '00000000-0000-0000-0000-000000000000';
				}
				$shippingFirstname           = null;
				$shippingLastname            = null;
				$shippingCompanyName         = null;
				$shippingAddress_Address1    = null;
				$shippingAddress_Address2    = null;
				$shippingAddress_City        = null;
				$shippingAddress_StateCode   = null;
				$shippingAddress_StateName   = null;
				$shippingAddress_PostatlCode = null;
				$shippingAddress_CountryCode = null;
				$shippingAddress_CountryName = null;
				$ship_phone                  = null;
				$ship_email                  = null;
				$ship_birth_date             = null;
			}
		    //wc-ajax update_order_review request code for checkout address
			if(isset($_REQUEST['wc-ajax']) && ($_REQUEST['wc-ajax'] == "update_order_review")) {
				$request_post_data = $_REQUEST['post_data'];
				if( ! empty($request_post_data)) {
					parse_str($request_post_data, $params);
					$shipping_birth_date      = isset($params['shipping_birth_date'] ) && ( $params['shipping_birth_date'] ) ? sanitize_text_field( $params['shipping_birth_date'] ) : null;

					if ( ! empty( $shipping_birth_date ) ) {
						WC()->session->set( 'shipping_birth_date', $shipping_birth_date );
						WC()->session->set( 'bloyal_shipping_date_of_birth', $shipping_birth_date );
					}

					$billing_birth_date      = isset( $params['billing_birth_date'] ) && ( $params['billing_birth_date'] ) ? sanitize_text_field( $_REQUEST['billing_birth_date'] ) : null;
					if ( !empty( $billing_birth_date ) ) {
						WC()->session->set( 'billing_birth_date', $billing_birth_date );
						WC()->session->set( 'bloyal_billing_date_of_birth', $billing_birth_date );
					}

					$shipping_phone = isset( $params['shipping_phone'] ) && ( $params['shipping_phone'] ) ? sanitize_text_field( $params['shipping_phone'] ) : null;

					if ( ! empty( $shipping_phone ) ) {
						WC()->session->set( 'shipping_phone', $shipping_phone );
					}
					if ( isset( $params['billing_first_name'] ) ) {
						$billing_first_name = sanitize_text_field($params['billing_first_name']);
						$billing_last_name  = sanitize_text_field($params['billing_last_name']);
						$billing_address1   = sanitize_text_field($params['billing_address_1']);
						$billing_address2   = sanitize_text_field($params['billing_address_2']);
						$billing_city       = sanitize_text_field($params['billing_city']);
						$billing_state      = sanitize_text_field($params['billing_state']);
						$billing_postcode   = sanitize_text_field($params['billing_postcode']);
						$billing_country    = sanitize_text_field($params['billing_country']);
						$billing_company    = sanitize_text_field($params['billing_company']);
						$billing_phone      = sanitize_text_field($params['billing_phone']);
						$billing_email      = sanitize_text_field($params['billing_email']);
						if ( isset( $params['ship_to_different_address'] ) && $params['ship_to_different_address'] ) {
							$ship_first_name = sanitize_text_field($params['shipping_first_name']);
							$ship_last_name  = sanitize_text_field($params['shipping_last_name']);
							$ship_address_1  = sanitize_text_field($params['shipping_address_1']);
							$ship_address_2  = sanitize_text_field($params['shipping_address_2']);
							$ship_city       = sanitize_text_field($params['shipping_city']);
							$ship_state      = sanitize_text_field($params['shipping_state']);
							$ship_postcode   = sanitize_text_field($params['shipping_postcode']);
							$ship_country    = sanitize_text_field($params['shipping_country']);
							$ship_company    = sanitize_text_field($params['shipping_company']);
							$ship_phone      = sanitize_text_field($params['shipping_phone']);
							$ship_email      = sanitize_text_field($params['shipping_email']);
							$ship_birth_date = sanitize_text_field($params['shipping_birth_date']);
						} else {
							$ship_first_name = $billing_first_name;
							$ship_last_name  = $billing_last_name;
							$ship_address_1  = $billing_address1;
							$ship_address_2  = $billing_address2;
							$ship_city       = $billing_city;
							$ship_state      = $billing_state;
							$ship_postcode   = $billing_postcode;
							$ship_country    = $billing_country;
							$ship_company    = $billing_company;
							$ship_phone      = $billing_phone;
							$ship_email      = $billing_email;
							$ship_birth_date = sanitize_text_field($params['billing_birth_date']);;
						}
						$order_notes = isset( $params['order_comments'] ) ? sanitize_text_field( $params['order_comments'] ) : '';
					} else {
						
						$billing_name       = $guest_user_data['billing'];
						$billing_first_name = $billing_name['first_name'] ? $billing_name['first_name'] : $customer['first_name'];
						$billing_last_name  = $billing_name['last_name'] ? $billing_name['last_name'] : $customer['last_name'];
						$billing_address1   = $billing_name['address_1'] ? $billing_name['address_1'] : $customer['address_1'];
						$billing_city       = $billing_name['city'] ? $billing_name['city'] : $customer['city'];
						$billing_state      = $billing_name['state'] ? $billing_name['state'] : $customer['state'];
						$billing_postcode   = $billing_name['postcode'] ? $billing_name['postcode'] : $customer['postcode'];
						$billing_country    = $billing_name['country'] ? $billing_name['country'] : $customer['country'];
						$shipping_details   = $guest_user_data['shipping'];
						$ship_first_name    = $shipping_details['first_name'];
						$ship_last_name     = $shipping_details['last_name'];
						$ship_address_1     = $shipping_details['address_1'];
						$ship_address_2     = $shipping_details['address_2'];
						$ship_city          = $shipping_details['city'];
						$ship_state         = $shipping_details['state'];
						$ship_postcode      = $shipping_details['postcode'];
						$ship_country       = $shipping_details['country'];
						$ship_company       = $shipping_details['company'];
						if ( ! $ship_first_name ) {
							$ship_first_name = $customer['shipping_first_name'] ? $customer['shipping_first_name'] : $billing_name['first_name'];
						}
						if ( ! $ship_last_name ) {
							$ship_last_name = $customer['shipping_last_name'] ? $customer['shipping_last_name'] : $billing_name['last_name'];
						}
						if ( ! $ship_address_1 ) {
							$ship_address_1 = $customer['shipping_address_1'] ? $customer['shipping_address_1'] : $billing_name['address_1'];
						}
						if ( ! $ship_address_2 ) {
							$ship_address_2 = $customer['shipping_address_2'] ? $customer['shipping_address_2'] : $billing_name['address_2'];
						}
						if ( ! $ship_city ) {
							$ship_city = $customer['shipping_city'] ? $customer['shipping_city'] : $billing_name['city'];
						}
						if ( ! $ship_state ) {
							$ship_state = $customer['shipping_state'] ? $customer['shipping_state'] : $billing_name['state'];
						}
						if ( ! $ship_postcode ) {
							$ship_postcode = $customer['shipping_postcode'] ? $customer['shipping_postcode'] : $billing_name['postcode'];
						}
						if ( ! $ship_country ) {
							$ship_country = $customer['shipping_country'] ? $customer['shipping_country'] : $billing_name['country'];
						}
						if ( ! $ship_company ) {
							$ship_company = $customer['shipping_company'] ? $customer['shipping_company'] : $billing_name['company'];
						}
					}
					$gift_message_box    = isset( $params['gift_message_box'] ) ? sanitize_text_field( $params['gift_message_box'] ) : '';
					$gift_order_checkbox = isset( $params['gift_order_checkbox'] ) ? sanitize_text_field( $params['gift_order_checkbox'] ) : '';
					$giftPackage         = false;
					$instructions        = $giftComment = null;
					if ( $gift_order_checkbox ) {
						$giftPackage = true;
						$giftComment = $gift_message_box;
					}
					$billing_instructions        = isset( $params['order_comments2'] ) ? sanitize_text_field( $params['order_comments2'] ) : '';
					$shipping_instructions       = isset( $params['order_comments'] ) ? sanitize_text_field( $params['order_comments'] ) : '';
					$instructions                = $billing_instructions . ' ' . $shipping_instructions;
					$shippingFirstname           = $ship_first_name;
					$shippingLastname            = $ship_last_name;
					$shippingCompanyName         = $ship_company;
					$shippingAddress_Address1    = $ship_address_1;
					$shippingAddress_Address2    = $ship_address_2;
					$shippingAddress_City        = $ship_city;
					$shippingAddress_StateCode   = $ship_state;
					$shippingAddress_StateName   = $ship_state;
					$shippingAddress_PostatlCode = $ship_postcode;
					$shippingAddress_CountryCode = $ship_country;
					$shippingAddress_CountryName = $ship_country;
					$location_code               = '';
					$location_uid                = '00000000-0000-0000-0000-000000000000';
				}
			} else {
				$shipping_birth_date      = isset( $_REQUEST['shipping_birth_date'] ) && ( $_REQUEST['shipping_birth_date'] ) ? sanitize_text_field( $_REQUEST['shipping_birth_date'] ) : null;
				if ( ! empty( $shipping_birth_date ) ) {
					WC()->session->set( 'shipping_birth_date', $shipping_birth_date );
					WC()->session->set( 'bloyal_shipping_date_of_birth', $shipping_birth_date );
				}
                 
				//  code to store birth date in session to use this dob in customer data (start)
				$billing_birth_date      = isset( $_REQUEST['billing_birth_date'] ) && ( $_REQUEST['billing_birth_date'] ) ? sanitize_text_field( $_REQUEST['billing_birth_date'] ) : null;
				
				if ( !empty( $billing_birth_date ) ) {
					WC()->session->set( 'billing_birth_date', $billing_birth_date );
					WC()->session->set( 'bloyal_billing_date_of_birth', $billing_birth_date );
				}
				//  code to store birth date in session to use this dob in customer data (end)
				
				$shipping_phone = isset( $_REQUEST['shipping_phone'] ) && ( $_REQUEST['shipping_phone'] ) ? sanitize_text_field( $_REQUEST['shipping_phone'] ) : null;
				if ( ! empty( $shipping_birth_date ) ) {
					WC()->session->set( 'shipping_phone', $shipping_phone );
				}
				
				if ( ! empty( $_REQUEST ) && isset( $_REQUEST['billing_first_name'] ) ) {
					$billing_first_name = sanitize_text_field($_REQUEST['billing_first_name']);
					$billing_last_name  = sanitize_text_field($_REQUEST['billing_last_name']);
					$billing_address1   = sanitize_text_field($_REQUEST['billing_address_1']);
					$billing_address2   = sanitize_text_field($_REQUEST['billing_address_2']);
					$billing_city       = sanitize_text_field($_REQUEST['billing_city']);
					$billing_state      = sanitize_text_field($_REQUEST['billing_state']);
					$billing_postcode   = sanitize_text_field($_REQUEST['billing_postcode']);
					$billing_country    = sanitize_text_field($_REQUEST['billing_country']);
					$billing_company    = sanitize_text_field($_REQUEST['billing_company']);
					$billing_phone      = sanitize_text_field($_REQUEST['billing_phone']);
					$billing_email      = sanitize_text_field($_REQUEST['billing_email']);
					if ( isset( $_REQUEST['ship_to_different_address'] ) && $_REQUEST['ship_to_different_address'] ) {
						$ship_first_name = sanitize_text_field($_REQUEST['shipping_first_name']);
						$ship_last_name  = sanitize_text_field($_REQUEST['shipping_last_name']);
						$ship_address_1  = sanitize_text_field($_REQUEST['shipping_address_1']);
						$ship_address_2  = sanitize_text_field($_REQUEST['shipping_address_2']);
						$ship_city       = sanitize_text_field($_REQUEST['shipping_city']);
						$ship_state      = sanitize_text_field($_REQUEST['shipping_state']);
						$ship_postcode   = sanitize_text_field($_REQUEST['shipping_postcode']);
						$ship_country    = sanitize_text_field($_REQUEST['shipping_country']);
						$ship_company    = sanitize_text_field($_REQUEST['shipping_company']);
						$ship_phone      = sanitize_text_field($_REQUEST['shipping_phone']);
						$ship_email      = sanitize_text_field($_REQUEST['shipping_email']);
						$ship_birth_date = sanitize_text_field($_REQUEST['shipping_birth_date']);
					} else {
						$ship_first_name = $billing_first_name;
						$ship_last_name  = $billing_last_name;
						$ship_address_1  = $billing_address1;
						$ship_address_2  = $billing_address2;
						$ship_city       = $billing_city;
						$ship_state      = $billing_state;
						$ship_postcode   = $billing_postcode;
						$ship_country    = $billing_country;
						$ship_company    = $billing_company;
						$ship_phone      = $billing_phone;
						$ship_email      = $billing_email;
						$ship_birth_date = sanitize_text_field($_REQUEST['billing_birth_date']);;
					}
					$order_notes = isset( $_REQUEST['order_comments'] ) ? sanitize_text_field( $_REQUEST['order_comments'] ) : '';
				} else {
				
					$billing_name       = $guest_user_data['billing'];
					$billing_first_name = $billing_name['first_name'] ? $billing_name['first_name'] : $customer['first_name'];
					$billing_last_name  = $billing_name['last_name'] ? $billing_name['last_name'] : $customer['last_name'];
					$billing_address1   = $billing_name['address_1'] ? $billing_name['address_1'] : $customer['address_1'];
					$billing_city       = $billing_name['city'] ? $billing_name['city'] : $customer['city'];
					$billing_state      = $billing_name['state'] ? $billing_name['state'] : $customer['state'];
					$billing_postcode   = $billing_name['postcode'] ? $billing_name['postcode'] : $customer['postcode'];
					$billing_country    = $billing_name['country'] ? $billing_name['country'] : $customer['country'];
					$shipping_details   = $guest_user_data['shipping'];
					$ship_first_name    = $shipping_details['first_name'];
					$ship_last_name     = $shipping_details['last_name'];
					$ship_address_1     = $shipping_details['address_1'];
					$ship_address_2     = $shipping_details['address_2'];
					$ship_city          = $shipping_details['city'];
					$ship_state         = $shipping_details['state'];
					$ship_postcode      = $shipping_details['postcode'];
					$ship_country       = $shipping_details['country'];
					$ship_company       = $shipping_details['company'];
					if ( ! $ship_first_name ) {
						$ship_first_name = $customer['shipping_first_name'] ? $customer['shipping_first_name'] : $billing_name['first_name'];
					}
					if ( ! $ship_last_name ) {
						$ship_last_name = $customer['shipping_last_name'] ? $customer['shipping_last_name'] : $billing_name['last_name'];
					}
					if ( ! $ship_address_1 ) {
						$ship_address_1 = $customer['shipping_address_1'] ? $customer['shipping_address_1'] : $billing_name['address_1'];
					}
					if ( ! $ship_address_2 ) {
						$ship_address_2 = $customer['shipping_address_2'] ? $customer['shipping_address_2'] : $billing_name['address_2'];
					}
					if ( ! $ship_city ) {
						$ship_city = $customer['shipping_city'] ? $customer['shipping_city'] : $billing_name['city'];
					}
					if ( ! $ship_state ) {
						$ship_state = $customer['shipping_state'] ? $customer['shipping_state'] : $billing_name['state'];
					}
					if ( ! $ship_postcode ) {
						$ship_postcode = $customer['shipping_postcode'] ? $customer['shipping_postcode'] : $billing_name['postcode'];
					}
					if ( ! $ship_country ) {
						$ship_country = $customer['shipping_country'] ? $customer['shipping_country'] : $billing_name['country'];
					}
					if ( ! $ship_company ) {
						$ship_company = $customer['shipping_company'] ? $customer['shipping_company'] : $billing_name['company'];
					}
				}
				$gift_message_box    = isset( $_REQUEST['gift_message_box'] ) ? sanitize_text_field( $_REQUEST['gift_message_box'] ) : '';
				$gift_order_checkbox = isset( $_REQUEST['gift_order_checkbox'] ) ? sanitize_text_field( $_REQUEST['gift_order_checkbox'] ) : '';
				$shipping_birth_date = isset( $_REQUEST['shipping_birth_date'] ) && ( $_REQUEST['shipping_birth_date'] ) ? sanitize_text_field( $_REQUEST['shipping_birth_date'] ) : null;
				$giftPackage         = false;
				$instructions        = $giftComment = null;
				if ( $gift_order_checkbox ) {
					$giftPackage = true;
					$giftComment = $gift_message_box;
				}
				$billing_instructions        = isset( $_REQUEST['order_comments2'] ) ? sanitize_text_field( $_REQUEST['order_comments2'] ) : '';
				$shipping_instructions       = isset( $_REQUEST['order_comments'] ) ? sanitize_text_field( $_REQUEST['order_comments'] ) : '';
				$instructions                = $billing_instructions . ' ' . $shipping_instructions;
				$shippingFirstname           = $ship_first_name;
				$shippingLastname            = $ship_last_name;
				$shippingCompanyName         = $ship_company;
				$shippingAddress_Address1    = $ship_address_1;
				$shippingAddress_Address2    = $ship_address_2;
				$shippingAddress_City        = $ship_city;
				$shippingAddress_StateCode   = $ship_state;
				$shippingAddress_StateName   = $ship_state;
				$shippingAddress_PostatlCode = $ship_postcode;
				$shippingAddress_CountryCode = $ship_country;
				$shippingAddress_CountryName = $ship_country;
				$location_code               = '';
				$location_uid                = '00000000-0000-0000-0000-000000000000';
			}

			$shipments[] = array(
				'Uid'                       => null,
				'Number'                    => null,
				'Type'                      => ( $select_shipping_name == 'bloyal_pickup_store' ? 'Pickup' : 'Shipment' ),
				'AddressUid'                => null,
				'AddressExternalId'         => null,
				'Title'                     => null,
				'FirstName'                 => $shippingFirstname,
				'LastName'                  => $shippingLastname,
				'CompanyName'               => $shippingCompanyName,
				'Phone'                     => $ship_phone,
				'EmailAddress'              => $ship_email,
				'Address'                   => array(
					'Address1'    => $shippingAddress_Address1,
					'Address2'    => $shippingAddress_Address2,
					'City'        => $shippingAddress_City,
					'StateCode'   => $shippingAddress_StateCode,
					'StateName'   => $shippingAddress_StateName,
					'PostalCode'  => $shippingAddress_PostatlCode,
					'CountryCode' => $shippingAddress_CountryCode,
					'CountryName' => $shippingAddress_CountryName,
				),
				'BirthDate'                 => $ship_birth_date,
				'CarrierUid'                => null,
				'CarrierExternalId'         => null,
				'CarrierCode'               => $is_virtual_porduct_order ? null : $wcShippingRateMetaData['meta_data']['bloyal_meta_data']['CarrierCode'],
				'ServiceUid'                => $is_virtual_porduct_order ? '00000000-0000-0000-0000-000000000000' : $wcShippingRateMetaData['meta_data']['bloyal_meta_data']['ServiceUid'],
				'ServiceCode'               => $is_virtual_porduct_order ? null : $wcShippingRateMetaData['meta_data']['bloyal_meta_data']['ServiceCode'],
				'LocationUid'               => $location_uid,
				'LocationExternalId'        => null,
				'ServiceExternalId'         => null,
				'LocationCode'              => $location_code,
				'FulfillmentHouse'          => null,
				'ExternallyAppliedCharge'   => $externally_applied_charge,
				'Charge'                    => $is_virtual_porduct_order ? 0 : $shippingCharge,
				'ExternallyAppliedDiscount' => false,
				'Discount'                  => null,
				'DiscountReasonCode'        => null,
				'DiscountReasonName'        => null,
				'DiscountDetails'           => array(
					'Amount'            => null,
					'RuleUid'           => null,
					'ReasonUid'         => null,
					'ReasonCode'        => null,
					'Reason'            => null,
					'CouponUid'         => null,
					'CouponCode'        => null,
					'ExternallyApplied' => null,
				),
				'GiftPackage'               => $giftPackage,
				'GiftComment'               => $giftComment,
				'ShipDate'                  => null,
				'Instructions'              => $instructions,
				'ExternallyAppliedTax'      => $externally_applied_tax,
				'TaxDetails'                => $taxDetails,
			);
			
			return $shipments;
		}

		/**
		 * Function to modify woocom shipping tax amount as per shipping discount from bLoyal
		 *
		 * @param array $shippingCostAndDiscount
		 * @return $shipments  array
		 */

		public function change_wooc_shipping_tax_amount( $shippingCostAndDiscount ) {
			$arr_shipping_taxes        = array();
			$arrApplied_shipping_taxes = WC_Tax::get_shipping_tax_rates();
			$float_shipping_discount   = $shippingCostAndDiscount['shipping_discount'];
			$float_shipping_cost       = $shippingCostAndDiscount['shipping_original_cost'];
			$float_final_shipping_cost = $float_shipping_cost - $float_shipping_discount;
			
			WC()->session->set( 'applied_shipping_taxes', null );
			if ( $float_shipping_cost > 0 ) {
				foreach ( $arrApplied_shipping_taxes as $Key => $tax ) {
					$float_shipping_tax_rate    = $tax['rate'];
					$arr_shipping_taxes[ $Key ] = round( ( $float_final_shipping_cost * $float_shipping_tax_rate ) / 100, 2 );
				}
				WC()->session->set( 'applied_shipping_taxes', $arr_shipping_taxes );
				add_filter( 'woocommerce_shipping_rate_taxes', 'bloyal_shipping_tax_rate', 10, 2 );
			}
		}
	}
}
