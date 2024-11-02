<?php
/**
 * Bloyal Cart Controller class.
 *
 * @package bLoyal
 */

if ( ! class_exists( 'BloyalCartController' ) ) {
	/**
	 * Bloyal's BloyalCartController. These can be displayed with the bLoyal package.
	 */
	class BloyalCartController {
		/**
		 * Initialize the wc_cart_object.
		 *
		 * @param WC_Cart $wc_cart_object cart objects pass as parameter.
		 * @param WC_Cart $cart_uid cart uid pass as parameter.
		 * @param WC_Cart $is_bloyal_applied_taxes is bloyal applied taxes pass as parameter.
		 * @param WC_Cart $order_id pass as parameter.
		 * @return wc_cart object return type.
		 */
		public function make_bloyal_cart( $wc_cart_object, $cart_uid, $is_bloyal_applied_taxes, $order_id = 0 ) {
			try {

				$customer_data           = $this->create_customer_data();
				$float_external_discount = $this->get_external_discounts( $wc_cart_object );
				$is_guest                = ( is_user_logged_in() ) ? false : true;
				$bLoyalCouponSession 	 = WC()->session->get( 'bloyal_coupon');
				$bLoyalSessionCartuid    = WC()->session->get( 'bloyal_uid');
				$bloyal_cart_data        = array(
					"CouponCodes"               => [$bLoyalCouponSession[0]['coupon_code']],
					'Uid'                       => $cart_uid == '' ? $bLoyalSessionCartuid['u_id'] : $cart_uid,
					'ExternallyProcessedOrder'  => get_option( 'bloyal_use_order_engine' ) === 'true' ? false : true,
					'GuestCheckout'             => $is_guest,
					'Customer'                  => $customer_data,
					'Lines'                     => $this->create_lines_data( $wc_cart_object->get_cart(), $is_bloyal_applied_taxes ),
				    'Shipments'                 => $this->create_shippment_data( $order_id ),
					'ExternallyAppliedDiscount' => ( $float_external_discount > 0 ) ? true : false,
					'Discount'                  => $float_external_discount,
					'DiscountDetails'           => $this->create_discount_details(),
				);
				return $bloyal_cart_data;
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}

		
		/**
		 * Initialize get External discounts functions with parameter.
		 *
		 * @param array $wc_cart_object pass array parameter.
		 * @return float
		 */
		private function get_external_discounts( $wc_cart_object ) {
			try {
				$discount_coupons        = $wc_cart_object->get_coupon_discount_totals();
				$total_external_discount = 0;
				foreach ( $discount_coupons as $key => $value ) {
					$total_external_discount = $total_external_discount + $value;
				}
				return $total_external_discount;
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}

		/**
		 * Initialize create_customer_data function
		 *
		 * @return array return type array value
		 */
		public function create_customer_data() {
			try {
				$customer_data = array();
				if ( ! empty( $_REQUEST ) ) {
					if ( isset( $_REQUEST['createaccount'] ) && '1' === $_REQUEST['createaccount'] ) {
					
						$customer_data['EmailAddress']           = isset( $_REQUEST['billing_email'] ) ? sanitize_email( wp_unslash( $_REQUEST['billing_email'] ) ) : '';
						$customer_data['FirstName']              = isset( $_REQUEST['billing_first_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_first_name'] ) ) : '';
						$customer_data['LastName']               = isset( $_REQUEST['billing_last_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_last_name'] ) ) : '';
						$customer_data['CompanyName']            = isset( $_REQUEST['billing_company'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_company'] ) ) : '';
						$customer_data['Phone1']                 = isset( $_REQUEST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_phone'] ) ) : '';
						$customer_data['Address']                = array();
						$customer_data['Address']['Address1']    = isset( $_REQUEST['billing_address_1'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_address_1'] ) ) : '';
						$customer_data['Address']['Address2']    = isset( $_REQUEST['billing_address_2'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_address_2'] ) ) : '';
						$customer_data['Address']['City']        = isset( $_REQUEST['billing_city'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_city'] ) ) : '';
						$customer_data['Address']['State']       = isset( $_REQUEST['billing_state'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_state'] ) ) : '';
						$customer_data['Address']['StateCode']   = isset( $_REQUEST['billing_state'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_state'] ) ) : '';
						$customer_data['Address']['PostalCode']  = isset( $_REQUEST['billing_postcode'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_postcode'] ) ) : '';
						$customer_data['Address']['CountryCode'] = isset( $_REQUEST['billing_country'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_country'] ) ) : '';
						$customer_data['Address']['Country']     = isset( $_REQUEST['billing_country'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_country'] ) ) : '';
						$customer_data['BirthDate']				 = isset( $_REQUEST['billing_birth_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_birth_date'] ) ) : '';	
					} else {
						$customer = WC()->session->customer;
						if ( is_user_logged_in() ) {
							$user                              = wp_get_current_user();
							$customer_data['EmailAddress']     = $user->user_email;
							$customer_data['SourceExternalId'] = get_current_user_id();
						} else {
							$customer_data['EmailAddress'] = $customer['email'];
						}

						
						// We have made some changes to store firstname, lastname, phone, and company name in connector.

						$customer_data['FirstName']              = $customer['first_name'] != '' ? $customer['first_name'] : $_REQUEST['billing_first_name'];
						$customer_data['LastName']               = $customer['last_name'] != '' ? $customer['last_name'] : $_REQUEST['billing_last_name'];
						$customer_data['CompanyName']            = $customer['company'] != '' ? $customer['company'] : $_REQUEST['billing_company'];
						$customer_data['Phone1']                 = $customer['phone'] != '' ? $customer['phone'] : $_REQUEST['billing_phone'];
						$customer_data['BirthDate']              = isset( $_REQUEST['billing_birth_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['billing_birth_date'] ) ) : ''; //Added by Chetu Developer 
						$customer_data['Address']                = array();
						$customer_data['Address']['Address1']    = $customer['address_1'];
						$customer_data['Address']['Address2']    = $customer['address_2'];
						$customer_data['Address']['City']        = $customer['city'];
						$customer_data['Address']['State']       = $customer['state'];
						$customer_data['Address']['StateCode']   = $customer['state'];
						$customer_data['Address']['PostalCode']  = $customer['postcode'];
						$customer_data['Address']['CountryCode'] = $customer['country'];
						$customer_data['Address']['Country']     = $customer['country'];
						$request_post_data = $_REQUEST['post_data'];
						if( ! empty($request_post_data))
						{
							parse_str($request_post_data, $params);
							$billingBirthDate = $params['billing_birth_date'];
							$shippingBirthDate = $params['shipping_birth_date'];
							if ( !empty( $billingBirthDate ) ) {
								$billing_birth_date         = sanitize_text_field( wp_unslash($customer['billing_birth_date']) );
								$customer_data['BirthDate'] = $billing_birth_date;
								$user_id = get_current_user_id();
								update_user_meta( $user_id, 'birth_date', $billing_birth_date );
								WC()->session->set( 'guest_user_birth_date', $billing_birth_date );
								WC()->session->set( 'billing_birth_date', $billing_birth_date );
								WC()->session->set( 'bloyal_billing_date_of_birth', $billing_birth_date );
							}
							
							if ( !empty( $shippingBirthDate ) ) {
								$shipping_birth_date         = sanitize_text_field( wp_unslash($customer['shipping_birth_date'] ) );
								$customer_data['BirthDate'] = $shipping_birth_date;
								$user_id = get_current_user_id();
								update_user_meta( $user_id, 'birth_date', $shipping_birth_date );
								WC()->session->set( 'guest_user_birth_date', $shipping_birth_date );
								WC()->session->set( 'shipping_birth_date', $shipping_birth_date );
								WC()->session->set( 'bloyal_shipping_date_of_birth', $shipping_birth_date );
							}
						
							//till here---------------------------------------------------------
							
							
						}
						if( isset( $_REQUEST['shipping_email'] ) ) {
							$shipping_email         = sanitize_email( wp_unslash( $_REQUEST['shipping_email'] ) );
							WC()->session->set( 'bloyal_shipping_email', $shipping_email );
							
						}

						if( isset( $_REQUEST['shipping_phone'] ) ) {
							$shipping_phone         = sanitize_text_field( wp_unslash( $_REQUEST['shipping_phone'] ) );
							WC()->session->set( 'bloyal_shipping_phone', $shipping_phone );
							
						}
						if( isset( $_REQUEST['gift_message_box'] ) ) {
							$shipping_gift_message_box         = sanitize_text_field( wp_unslash( $_REQUEST['gift_message_box'] ) );
							WC()->session->set( 'bloyal_shipping_gift_message', $shipping_gift_message_box );
							
						}
					}
				}
				return $customer_data;
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}

		/**
		 * Initialize create_shippment_data.
		 *
		 * @param  mixed $order_id pass parameter.
		 * @return array return request.
		 */
		public function create_shippment_data( $order_id ) {
			try {
				$obj_shipping_controller = new shippingController();
				// check is bloyal shipping.
				$is_bloyal_shipping = $obj_shipping_controller->isBloyalRate( WC()->session->get( 'chosen_shipping_methods' ) );
				$shipping_request   = $obj_shipping_controller->makeBloyalShipmentRequest( $is_bloyal_shipping );
				return $shipping_request;
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}

		/**
		 * Initialize create_lines_data.
		 *
		 * @param  mixed $lines pass parameter.
		 * @param  mixed $is_bloyal_applied_taxes pass parameter.
		 * @return array return request.
		 */
		public function create_lines_data( $lines, $is_bloyal_applied_taxes ) {
			try {
					WC()->session->set( 'is_virtual_porduct_order', true );
					$line_data    = array();
					$external_tax = 'false';
					if ( 'true' !== $is_bloyal_applied_taxes ) {
						$external_tax = 'true';
					}
					
					foreach ( $lines as $key => $line ) {
						$product_data             = $line['data'];
						$is_external_discount     = false;
						$external_discount_amount = 0;
						$regular_price            = $product_data->get_regular_price();
						$price                    = floatval($product_data->get_price());
						$external_sale_price      = $product_data->get_sale_price();
						if ($external_sale_price != "" ) {
							$external_discount_amount = floatval( $regular_price ) - floatval( $external_sale_price );
							// $is_external_discount     = true;
						} else {
							$regular_price = floatval( $regular_price );
						}
						if ( ! $product_data->is_virtual() ) {
							WC()->session->set( 'is_virtual_porduct_order', false );
						}
						
						$line_data[] = array(
							'ExternalId'                => $key,
							'ProductCode'               => $product_data->get_sku(),
							'ProductName'               => $product_data->get_name(),
							'Quantity'                  => $line['quantity'],
							'FullPrice'                 => $regular_price ?? 0,
							'Price'                     => $external_sale_price ? $price : $regular_price,
							'ExternallyAppliedSalePrice'=> (($external_discount_amount > 0) ? true : false),
							'SalePrice'                 => $external_sale_price ?? 0,
							'ExternallyAppliedDiscount' => $external_sale_price ? false : $is_external_discount,
							'Discount'                  => $external_sale_price ? 0 : $external_discount_amount,
							'DiscountDetails'           => $this->create_discount_details(),
							'Weight'                    => $product_data->get_weight(),
							'TaxExempt'                 => false,
							'ExternallyAppliedTax'      => $external_tax,
							'TaxDetails'                => $this->create_tax_details( $external_tax, $line ),
							'DoNotTax'                  => false,
						);
					}
					
				return $line_data;
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}

		/**
		 * Initialize create_discount_details.
		 *
		 * @param  array $discounts pass parameter.
		 * @return array
		 */
		public function create_discount_details( $discounts = array() ) {
			try {
				$discount_details_data   = array();
				$discount_details_data[] = array(
					'ExternallyApplied' => false,
				);
				return $discount_details_data;
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}

		}

		/**
		 * Initialize create_tax_details.
		 *
		 * @param  array $external_tax pass parameter.
		 * @param  array $line pass parameter.
		 * @return array
		 */
		public function create_tax_details( $external_tax, $line ) {
			try {
				$tax_details_data = array();
				if ( $external_tax) {
					$tax_details_data[] = array(
						'Amount' => isset( $line['line_tax'] ) ? number_format(floor($line['line_tax']*100)/100, 2) : 0,
						'Code'   => '',
						'Rate'   => '',
					);
				} else {
					$tax_details_data[] = array(
						'Code'   => null,
						'Rate'   => null,
						'Amount' => null,
					);
				}
				return $tax_details_data;
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}


		/**
		 * Initialize New Response Request create_split_lines_data.
		 *
		 * @param  mixed $bloyal_cart_object pass parameter.
		 * @return array return request.
		 */
		public function make_bloyal_cart_re_request( $bloyal_cart_object ) {
			try {
				      //New Response Request
					$line_data    = array();
					foreach ($bloyal_cart_object->data->Cart->Lines  as $key => $value) {
							$tax_details = $line->TaxDetails;
						foreach ($value->DiscountDetails as $key => $value1) {
							$ext['ExternallyApplied'] = $value1->ExternallyApplied;
						
						}			
							$line_data[] = array(
								'ExternalId'                => $value->Uid,
								'ProductCode'               => $value->ProductCode,
								'ProductName'               => $value->ProductName,
								'Quantity'                  => $value->Quantity,
								'Price'                     => $value->Price,
								'ExternallyAppliedDiscount' => $value->ExternallyAppliedDiscount,
								'Discount'                  => $value->Discount,
								'DiscountDetails'           => $ext,
								'Weight'                    => $value->Weight,
								'TaxExempt'                 => $value->TaxExempt,
								'ExternallyAppliedTax'      => $value->ExternallyAppliedTax,
								'TaxDetails'                => $value->TaxDetails,
								'DoNotTax'                  => $value->DoNotTax,
							);

					}
					$shipment_data[]= array(
			          'Uid'          				 	 => $bloyal_cart_object->data->Cart->Shipments[0]->Uid,
			          'Number'       				 	 => $bloyal_cart_object->data->Cart->Shipments[0]->Number,
			          'Type'         				 	 => $bloyal_cart_object->data->Cart->Shipments[0]->Type,
			          'AddressUid'  					 => $bloyal_cart_object->data->Cart->Shipments[0]->AddressUid,
			          'AddressExternalId'  		 		 => $bloyal_cart_object->data->Cart->Shipments[0]->AddressExternalId,
			          'Title'  						 	 => $bloyal_cart_object->data->Cart->Shipments[0]->Title,
			          'FirstName'  					 	 => $bloyal_cart_object->data->Cart->Shipments[0]->FirstName,
			          'LastName'  					 	 => $bloyal_cart_object->data->Cart->Shipments[0]->LastName,
			          'CompanyName' 		   	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->CompanyName,
			          'Phone'  						 	 => $bloyal_cart_object->data->Cart->Shipments[0]->Phone,
			          'EmailAddress' 		   	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->EmailAddress,
			          'Address' 				   	 	 => json_decode(json_encode($bloyal_cart_object->data->Cart->Shipments[0]->Address),true),
			          'BirthDate'            	 		 => ($bloyal_cart_object->data->Cart->Shipments[0]->Address->BirthDate) ?? ($bloyal_cart_object->data->Cart->Shipments[0]->Recipient->BirthDate),
			          'CarrierUid'           	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->CarrierUid,
			          'CarrierExternalId'    	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->CarrierExternalId,
			          'CarrierCode'          	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->CarrierCode,
			          'ServiceUid'           	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->ServiceUid,
			          'ServiceCode'          	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->ServiceCode,
			          'CarrierUid'           	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->CarrierUid,
			          'LocationUid'  				 	 => $bloyal_cart_object->data->Cart->Shipments[0]->LocationUid,
			          'LocationExternalId'   	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->LocationExternalId,
			          'ServiceExternalId'    	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->ServiceExternalId,
			          'LocationCode' 	      	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->LocationCode,
			          'FulfillmentHouse'     	 		 => $bloyal_cart_object->data->Cart->Shipments[0]->FulfillmentHouse,
			          'ExternallyAppliedCharge'  		 => $bloyal_cart_object->data->Cart->Shipments[0]->ExternallyAppliedCharge,
			          'Charge'                   		 => $bloyal_cart_object->data->Cart->Shipments[0]->Charge,
			          'Discount'                 		 => $bloyal_cart_object->data->Cart->Shipments[0]->Discount,
			          'DiscountReasonCode'       		 => $bloyal_cart_object->data->Cart->Shipments[0]->DiscountReasonCode,
			          'DiscountReasonName'       		 => $bloyal_cart_object->data->Cart->Shipments[0]->DiscountReasonName,
			          'DiscountDetails'          		 => $bloyal_cart_object->data->Cart->Shipments[0]->DiscountDetails,
			          'GiftPackage'              		 => $bloyal_cart_object->data->Cart->Shipments[0]->GiftPackage,
			          'GiftComment'              		 => $bloyal_cart_object->data->Cart->Shipments[0]->GiftComment,
			          'ShipDate'                 		 => $bloyal_cart_object->data->Cart->Shipments[0]->ShipDate,
			          'Instructions'             		 => $bloyal_cart_object->data->Cart->Shipments[0]->Instructions,
			          'ExternallyAppliedTax'     		 => $bloyal_cart_object->data->Cart->Shipments[0]->ExternallyAppliedTax,
			          'TaxDetails'               		 => $bloyal_cart_object->data->Cart->Shipments[0]->TaxDetails,
			          
					);
					
					$Customer_data_address = array(
			       'Address1'        					=> $bloyal_cart_object->data->Cart->Customer->Address->Address1,
			       'Address2'  		   					=> $bloyal_cart_object->data->Cart->Customer->Address->Address2,
			       'City'              					=> $bloyal_cart_object->data->Cart->Customer->Address->City,
			       'State'             					=> $bloyal_cart_object->data->Cart->Customer->Address->State,
			       'StateCode'         					=> $bloyal_cart_object->data->Cart->Customer->Address->StateCode,
			       'PostalCode'        					=> $bloyal_cart_object->data->Cart->Customer->Address->PostalCode,
			       'CountryCode'       					=> $bloyal_cart_object->data->Cart->Customer->Address->CountryCode,
			       'Country'           					=> $bloyal_cart_object->data->Cart->Customer->Address->Country,
					);

					$Customer_data = array(
			       'EmailAddress'         			   => $bloyal_cart_object->data->Cart->Customer->EmailAddress,
			       'SourceExternalId'     			   => $bloyal_cart_object->data->Cart->SourceExternalId,
			       'FirstName'            			   => $bloyal_cart_object->data->Cart->Customer->FirstName,
			       'LastName'             			   => $bloyal_cart_object->data->Cart->Customer->LastName,
			       'CompanyName'          			   => $bloyal_cart_object->data->Cart->Customer->CompanyName,
			       'EmailAddress'         			   => $bloyal_cart_object->data->Cart->Customer->EmailAddress,
			       'Phone1'               			   => $bloyal_cart_object->data->Cart->Customer->Phone1,
			       'Address'              			   => json_decode(json_encode($Customer_data_address),true),
			       'BirthDate'            	 		   => WC()->session->get( 'bloyal_billing_date_of_birth')   
					);
			   
					$response_data['Cart'] = array(
					'Uid'                        			=>$bloyal_cart_object->data->Cart->Uid,
					'ExternallyProcessedOrder'   			=>$bloyal_cart_object->data->Cart->ExternallyProcessedOrder,
					'GuestCheckout'              			=>$bloyal_cart_object->data->Cart->GuestCheckout,
					'Customer'                   			=>$Customer_data,
					'Lines'                      			=>$line_data,
					'Shipments'                  			=>$shipment_data,
					'ExternallyAppliedDiscount'  			=>$bloyal_cart_object->data->Cart->ExternallyAppliedDiscount,
					'Discount'                   			=>$bloyal_cart_object->data->Cart->Discount,
					'DiscountDetails'            			=>$bloyal_cart_object->data->Cart->DiscountDetails,
				);
				return $response_data;
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}
	}
}
