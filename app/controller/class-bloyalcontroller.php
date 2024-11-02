<?php
/**
 * Bloyal Controller class for bLoyal plugin.
 *
 * @package bLoyal
 */

require_once BLOYAL_DIR . '/app/view/bloyal_view.php';
require_once BLOYAL_DIR . '/app/controller/shipping_controller.php';
require_once BLOYAL_DIR . '/app/controller/class-alertpopupcontroller.php';
if ( ! class_exists( 'BloyalController' ) ) {
	/**
	 * Class to work with BloyalController.
	 */
	class BloyalController {

		/**
		 * Private variable obj_shipping_controller declarae.
		 *
		 * @var obj_shipping_controller
		 * @return instance of ShippingController()
		 */
		private $obj_shipping_controller;

		/**
		 * Private variable obj_alert declarae.
		 *
		 * @var obj_alert
		 * @return instance of AlertPopUpController()
		 */
		private $obj_alert;

		/**
		 * __construct
		 * class constructor will set the needed filter and action hooks
		 */
		public function __construct() {

			$this->obj_shipping_controller      = new ShippingController();
			$this->obj_alert                    = new BLOYAL_AlertPopUpController();
			$this->loyalty_engine_url           = get_option( 'loyalty_engine_api_url' );
			$this->access_key                   = get_option( 'bloyal_access_key' );
			$this->is_custom_api_url_used       = get_option( 'is_bloyal_custom_api_url' );
			$this->grid_api_url                 = get_option( 'grid_api_url' );
			$this->custom_grid_api_url_name     = get_option( 'bloyal_custom_grid_api_url' );
			$this->domain_name                  = get_option( 'bloyal_domain_name' );
			$this->web_snippet_api_url          = get_option( 'web_snippets_api_url' );
			$this->order_engine_api_url         = get_option( 'order_engine_api_url' );
			$this->custom_loyaltyengine_api_url = get_option( 'bloyal_custom_loyaltyengine_api_url' );

		}
		/**
		 * This function is used to get configuration details which are saved in wpdb.
		 *
		 * @return json_object
		 */
		public function bloyal_get_configuration_details_from_wpdb() {
			try {
				return wp_json_encode(
					array(
						'domain_name'                      				=> get_option( 'bloyal_domain_name' ),
						'api_key'                          				=> get_option( 'bloyal_api_key' ),
						'domain_url'                       				=> get_option( 'bloyal_domain_url' ),
						'access_key'                       				=> get_option( 'bloyal_access_key' ),
						'gift_card_tender_code'            				=> get_option( 'bloyal_gift_card_tender_code' ),
						'loyalty_dollars_tender_code'      				=> get_option( 'bloyal_loyalty_dollars_tender_code' ),
						'on_account_tender_code'           				=> get_option( 'bloyal_on_account_tender_code' ),
						'use_order_engine'                 				=> get_option( 'bloyal_use_order_engine' ),
						'applied_shipping_charges'         				=> get_option( 'bloyal_applied_shipping_charges' ),
						'applied_taxes'                    				=> get_option( 'bloyal_applied_taxes' ),
						'rowcount'                         				=> get_option( 'rowcount' ),
						'shipping_service'                 				=> get_option( 'bloyal_shipping_service' ),
						'shipping_carrier'                 				=> get_option( 'bloyal_shipping_carrier' ),
						'shipping_method_name'             				=> get_option( 'bloyal_shipping_method_name' ),
						'custom_grid_api_url_name'         				=> get_option( 'bloyal_custom_grid_api_url' ),
						'custom_loyaltyengine_api_url_name'				=> get_option( 'bloyal_custom_loyaltyengine_api_url' ),
						'custom_orderengine_api_url_name'  				=> get_option( 'bloyal_custom_orderengine_api_url' ),
						'custompayment_api_url_name'       				=> get_option( 'bloyal_custompayment_api_url' ),
						'custom_logging_api_url_name'      				=> get_option( 'bloyal_custom_logging_api_url' ),
						'is_custom_api_url_used'           				=> get_option( 'is_bloyal_custom_api_url' ),
						'bloyal_snippet_code'              				=> get_option( 'bloyal_snippet_code' ),
						'bloyal_snippet_informational_code'				=> get_option( 'bloyal_snippet_informational_code' ),
						'bloyal_snippet_confirmation_code' 				=> get_option( 'bloyal_snippet_confirmation_code' ),
						'bloyal_snippet_problem_code'      				=> get_option( 'bloyal_snippet_problem_code' ),
						'bloyal_display_DOB'               				=> get_option( 'bloyal_display_DOB' ),
						'bloyal_required_DOB'              				=> get_option( 'bloyal_required_DOB' ),
						'bloyal_display_Phone'             				=> get_option( 'bloyal_display_Phone' ),
						'bloyal_required_Phone'            				=> get_option( 'bloyal_required_Phone' ),
						'bloyal_display_Email'             				=> get_option( 'bloyal_display_Email' ),
						'bloyal_required_Email'            				=> get_option( 'bloyal_required_Email' ),
						'bloyal_display_order_comments'    				=> get_option( 'bloyal_display_order_comments' ),
						'loyalty_block'                    				=> get_option( 'loyalty_block' ),
						'bloyal_display_address_Book'      				=> get_option( 'bloyal_display_address_Book' ),
						'bloyal_tender_payments_mapping'   				=> get_option( 'bloyal_tender_payments_mapping' ),
						'bloyal_shipping_pickup'           				=> get_option( 'bloyal_shipping_pickup' ),
						'bloyal_pickup_row_count'          				=> get_option( 'bloyal_pickup_row_count' ),
						'bloyal_click_and_collect_status'         		=> get_option( 'bloyal_click_and_collect_status' ),
						'bloyal_click_collect_label'              		=> get_option( 'bloyal_click_collect_label' ),
						'click_collect_error'              				=> get_option( 'click_collect_error' ),
						'bloyal_apply_full_balance_giftcard' 			=> get_option( 'bloyal_apply_full_balance_giftcard' ),
						'bloyal_apply_full_balance_loyalty' 			=> get_option( 'bloyal_apply_full_balance_loyalty' ),
						'bloyal_apply_in_increment_of_giftcard' 		=> get_option( 'bloyal_apply_in_increment_of_giftcard' ),
						'bloyal_apply_in_increment_of_loyalty' 			=> get_option( 'bloyal_apply_in_increment_of_loyalty' ),
						'bloyal_log_enable_disable'            			=> get_option( 'bloyal_log_enable_disable' ),
					)
				);
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}

		/**
		 * Initialize bloyal_get_accesskeyverification_details_from_wpdb
		 *
		 * @return string
		 */
		public function bloyal_get_accesskeyverification_details_from_wpdb() {
			try {
				return wp_json_encode(
					array(
						'access_key_verification' => get_option( 'bloyal_access_key_verification' ),
						'custom_api_url_used'     => get_option( 'is_custom_api_url_used' ),
					)
				);
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}

		/**
		 * This function is used to get access key.
		 *
		 * @param  string $domain_name pass as parameter.
		 * @param  string $api_key pass as parameter.
		 * @param  string $domain_url pass as parameter.
		 * @param  string $is_bloyal_custom_url_used pass as parameter.
		 * @param  string $custom_grid_api_url pass as parameter.
		 * @return json_object
		 */
		public function bloyal_get_access_key_curl( $domain_name, $api_key, $domain_url, $is_bloyal_custom_url_used, $custom_grid_api_url ) {

			try {
				$bloyal_urls            = $this->bloyal_get_service_urls( $domain_name, $domain_url, $is_bloyal_custom_url_used );
				$str_content            = '{"LoginDomain":"' . $domain_name . '","ApiKey":"' . $api_key . '","ConnectorKey":"94EC0683-BCA6-42EF-B172-86D11D4B1E56"}';
				//bLoyal GridApiUrl for get the bLoyal Access Key by bLoyal.
				$get_access_key_api_url = $bloyal_urls->GridApiUrl . '/api/v4/KeyDispenser';
				if ( 'true' === $is_bloyal_custom_url_used && ! empty( $custom_grid_api_url ) ) {
					$get_access_key_api_url = $custom_grid_api_url . '/api/v4/KeyDispenser';
				}
				bLoyalLoggerService::write_custom_log( "Get Access Key Request \r\n" . $str_content . "\r\n ======================\r\n", 1 );
				bLoyalLoggerService::write_custom_log( "Get Access Key URL \r\n" . $get_access_key_api_url . "\r\n ======================\r\n", 1 );
				$args = array(
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => $str_content,
					'method'  => 'POST',
					'timeout' => 45,
				);
				$response        = wp_remote_get( $get_access_key_api_url, $args );
				$response_status = wp_remote_retrieve_response_code( $response );
				$content         = wp_remote_retrieve_body( $response );
				bLoyalLoggerService::write_custom_log( "Get Access Key Response \r\n" . wp_json_encode( $content ) . "\r\n ======================\r\n", 1 );
				$is_key_available = false;
				$result = json_decode( $content, true );
				if ( is_array( $result ) && ! is_wp_error( $result ) ) {
					$api_response = json_decode( $content );
					if ( ! empty( $api_response->data ) ) {
						$is_key_available = true;
					} elseif ( 'Key already in use.' === $api_response->message ) {
						$is_key_in_use = true;
					}
				}
				if ( 'error' === $api_response->status ) {
					if ( ! empty( $api_response->message ) ) {
						$response = wp_json_encode(
							array(
								'error_msg_api' => $api_response->message,
							)
						);
						return $response;
					}
				}
				if ( true === $is_key_available ) {
					$response = wp_json_encode(
						array(
							'is_access_key_available' => true,
							'bloyal_urls'             => wp_json_encode($bloyal_urls, true),
							'access_key'              => $api_response->data,
						)
					);
				} elseif ( true === $is_key_in_use ) {
					$response = wp_json_encode(
						array(
							'is_key_in_use' => true,
							'error_msg_'    => $api_response->message,
						)
					);
				} else {
					$response = wp_json_encode(
						array(
							'is_access_key_available' => false,
						)
					);
				}
				$snippet_code = $this->fetch_bloyal_devices_and_snippet_code();
				return $response;
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}

		/**
		 * Initialize fetch_bloyal_devices_and_snippet_code
		 *
		 * @return array
		 */
		public function fetch_bloyal_devices_and_snippet_code() {
			try {
				$snippet_code = get_option( 'bloyal_snippets_codes' );
				if ( ! empty( $snippet_code ) ) {

					return json_decode( $snippet_code, true );
				} else {
					$action = 'Connectors/ContextInfo';
					bLoyalLoggerService::write_custom_log( "Get Devices Request URL \r\n" . $action . "\r\n ======================\r\n", 1 );
					$result = $this->send_curl_request( '', $action, 'grid', 0 );
					bLoyalLoggerService::write_custom_log( "Get Devices Response \r\n" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
					//print_r($result);
					update_option( 'Device_Code', $result->data->DeviceCode );
					$device_code = $result->data->DeviceCode;
					$action      = $device_code . '/SnippetProfiles';
					bLoyalLoggerService::write_custom_log( "Get Snippets Request URL \r\n" . $action . "\r\n ======================\r\n", 1 );
					$snippet_code         = array(
						'all'           => array(),
						'informational' => array(),
						'confirmation'  => array(),
						'problem'       => array(),
					);
					$result               = $this->send_curl_request( '', $action, 'web_snippets_api_url', 0 );
					$payment_snippet_code = '';
					bLoyalLoggerService::write_custom_log( "Get Snippets Response \r\n" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
					if ( ! empty( $result->data ) ) {
						foreach ( $result->data as $key => $snippet ) {
							if ( 'Alert' === $snippet->SnippetType ) {
								$snippet_settings = json_decode( $snippet->SnippetSettings );
								$snippet_code[ strtolower( $snippet_settings->AlertType ) ][] = $snippet->Code;
							}
							if ( 'PaymentMethod' === $snippet->SnippetType ) {
								$payment_snippet_code = $snippet->Code;
								update_option( 'payment_snippets_codes', $payment_snippet_code );
							}
						}
					}
					$snippet_code['informational'] = array_merge( $snippet_code['informational'], $snippet_code['all'] );
					$snippet_code['confirmation']  = array_merge( $snippet_code['confirmation'], $snippet_code['all'] );
					$snippet_code['problem']       = array_merge( $snippet_code['problem'], $snippet_code['all'] );
					update_option( 'bloyal_snippets_codes', wp_json_encode( $snippet_code ) );
					return $snippet_code;
				}
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}

		}

		/**
		 * This function is used to test access key.
		 *
		 * @param  string $domain_name as parameter.
		 * @param  string $api_key as parameter.
		 * @param  string $domain_url as parameter.
		 * @param  string $access_key as parameter.
		 * @param  string $custom_api_url_used as parameter.
		 * @param  string $custom_grid_api_url as parameter.
		 * @return json_object
		 */
		public function bloyal_test_access_key_curl( $domain_name, $api_key, $domain_url, $access_key, $custom_api_url_used, $custom_grid_api_url, $custom_payment_api_url ) {
			try {
				if(!empty($access_key)) { 
					$bloyal_urls             = $this->bloyal_get_service_urls( $domain_name, $domain_url, $custom_api_url_used );
					//bLoyal GridApiUrl use for get the ContextInfo by bLoyal.
					$test_access_key_api_url = $bloyal_urls->GridApiUrl . '/api/v4/' . $access_key . '/Connectors/ContextInfo';
					if ( 'true' === $custom_api_url_used ) {
						if ( ! empty( $custom_grid_api_url ) ) {
							$test_access_key_api_url = $custom_grid_api_url . '/api/v4/' . $access_key . '/Connectors/ContextInfo';
						}
					}
					bLoyalLoggerService::write_custom_log( "Test Access Key URL \r\n" . $test_access_key_api_url . "\r\n ======================\r\n", 1 );
					$args = array(
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'method'  => 'GET',
						'timeout' => 45,
					);
					$response        = wp_remote_get( $test_access_key_api_url, $args );
					$response_status = wp_remote_retrieve_response_code( $response );
					$content         = wp_remote_retrieve_body( $response );
					bLoyalLoggerService::write_custom_log( "Test Access Key Response \r\n" . wp_json_encode( $content ) . "\r\n ======================\r\n", 1 );
					$is_key_available = false;
					$result           = json_decode( $content, true );
					if ( is_array( $result ) && ! is_wp_error( $result ) ) {
						$api_response = json_decode( $content );
						if ( ! empty( $api_response->data ) ) {
							$is_key_available = true;
						} elseif ( 'Key already in use.' === $api_response->message ) {
							$is_key_in_use = true;
						}
						if ( 'error' === $api_response->status ) {
							if ( ! empty( $api_response->message ) ) {
								$response = wp_json_encode(
									array(
										'error_msg_api' => $api_response->message,
									)
								);
								return $response;
							}
						}

						if ( true === $is_key_available ) {
							if ( ( 0 === strcasecmp( $api_response->data->KeyType, 'Device' ) ) && ( strcasecmp( $api_response->data->LoginDomain, 0 === $domain_name ) ) ) {
								$response = wp_json_encode(
									array (
										'is_access_key_available' => true,
										'access_key'              => $api_response->data,
									)
								);
							} else {
								if ( ( strcasecmp( $api_response->data->KeyType, 'Device' ) != 0 ) && strcasecmp( $api_response->data->LoginDomain, $domain_name != 0 ) ) {
									$response = wp_json_encode(
										array(
											'wrong_configuration' => true,
											'wrong_configuration_error_msg' => 'Key Type and Company name are incorrect.',
										)
									);
								}
								if ( ( strcasecmp( $api_response->data->KeyType, 'Device' ) != 0 ) && strcasecmp( $api_response->data->LoginDomain, $domain_name == 0 ) ) {
									$response = wp_json_encode(
										array(
											'wrong_configuration' => true,
											'wrong_configuration_error_msg' => 'Key Type is incorrect.',
										)
									);
								}
								if ( ( strcasecmp( $api_response->data->KeyType, 'Device' ) == 0 ) && strcasecmp( $api_response->data->LoginDomain, $domain_name != 0 ) ) {
									$response = wp_json_encode(
										array(
											'wrong_configuration' => true,
											'wrong_configuration_error_msg' => 'Company name is incorrect.',
										)
									);
								}
							}
						} elseif ( $is_key_in_use == true ) {
							$response = wp_json_encode(
								array(
									'is_key_in_use' => true,
									'error_msg_'    => $api_response->message,
								)
							);
						} else {
							$response = wp_json_encode(
								array(
									'is_access_key_available' => false,
								)
							);
						}
					}
			    } else {
					$response = wp_json_encode(
						array(
							'is_access_key_available' => false,
						)
					);
				}
				return $response;
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}

		/**
		 * This function is used to save bloyal configuration settings data.
		 *
		 * @param Array $configuration_setting as parameter.
		 * @return json_object
		 */
		public function bloyal_save_configuration_data_wpdb() {
			try {
				update_option( 'loyalty_block', isset( $_POST['post_bloyal_loyalty_block'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_loyalty_block'] ) ) : '' );
				update_option( 'bloyal_domain_name', isset( $_POST['post_domain_name'] ) ? sanitize_text_field( wp_unslash( $_POST['post_domain_name'] ) ) : '' );
				update_option( 'bloyal_api_key', isset( $_POST['post_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['post_api_key'] ) ) : '' );
				update_option( 'bloyal_domain_url', isset( $_POST['post_domain_url'] ) ? sanitize_url( wp_unslash( $_POST['post_domain_url'] ) ) : '' );
				update_option( 'bloyal_access_key', isset( $_POST['post_access_key'] ) ? sanitize_text_field( wp_unslash( $_POST['post_access_key'] ) ) : '' );
				if(empty($_POST['post_adv_access_key'])) {
					update_option( 'bloyal_access_key', isset( $_POST['post_adv_access_key'] ) ? sanitize_text_field( wp_unslash( $_POST['post_adv_access_key'] ) ) : '' );
				}
				update_option( 'bloyal_on_account_tender_code', isset( $_POST['post_on_account_tender'] ) ? sanitize_text_field( wp_unslash( $_POST['post_on_account_tender'] ) ) : '' );
				update_option( 'bloyal_custom_grid_api_url', isset( $_POST['post_custom_grid_api_url'] ) ? sanitize_url( wp_unslash( $_POST['post_custom_grid_api_url'] ) ) : '' );
				update_option( 'bloyal_custom_loyaltyengine_api_url', isset( $_POST['post_custom_loyaltyengine_api_url'] ) ? sanitize_url( wp_unslash( $_POST['post_custom_loyaltyengine_api_url'] ) ) : '' );
				update_option( 'bloyal_custom_orderengine_api_url', isset( $_POST['post_custom_orderengine_api_url'] ) ? sanitize_url( wp_unslash( $_POST['post_custom_orderengine_api_url'] ) ) : '' );
				update_option( 'bloyal_custompayment_api_url', isset( $_POST['post_custompayment_api_url'] ) ? sanitize_url( wp_unslash( $_POST['post_custompayment_api_url'] ) ) : '' );
				update_option( 'bloyal_custom_logging_api_url', isset( $_POST['post_custom_logging_api_url'] ) ? sanitize_url( wp_unslash( $_POST['post_custom_logging_api_url'] ) ) : '' );
				update_option( 'is_bloyal_custom_api_url', isset( $_POST['post_bloyal_custom_url'] ) ? sanitize_url( wp_unslash(  $_POST['post_bloyal_custom_url'] ) ) : '' );
				update_option( 'bloyal_snippet_code', isset( $_POST['post_bloyal_snippet_code'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_snippet_code'] ) ) : '' );
				update_option( 'bloyal_snippet_informational_code', isset( $_POST['post_bloyal_snippet_informational_code'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_snippet_informational_code'] ) ) : '' );
				update_option( 'bloyal_snippet_confirmation_code', isset( $_POST['post_bloyal_snippet_confirmation_code'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_snippet_confirmation_code'] ) ) : '' );
				update_option( 'bloyal_snippet_problem_code', isset( $_POST['post_bloyal_snippet_problem_code'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_snippet_problem_code'] ) ) : '' );
				update_option( 'bloyal_tender_payments_mapping', isset( $_POST['post_tender_payments_mapping'] ) ? sanitize_post( wp_unslash( $_POST['post_tender_payments_mapping'] ) ) : '' );
				update_option( 'bloyal_apply_full_balance_loyalty', isset( $_POST['post_bloyal_apply_full_balance_loyalty'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_apply_full_balance_loyalty'] ) ) : '' );
				update_option( 'bloyal_apply_full_balance_giftcard', isset( $_POST['post_bloyal_apply_full_balance_giftcard'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_apply_full_balance_giftcard'] ) ) : '' );
				update_option( 'bloyal_apply_in_increment_of_giftcard', isset( $_POST['post_bloyal_apply_in_increment_of_giftcard'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_apply_in_increment_of_giftcard'] ) ) : '' );
				update_option( 'bloyal_apply_in_increment_of_loyalty', isset( $_POST['post_bloyal_apply_in_increment_of_loyalty'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_apply_in_increment_of_loyalty'] ) ) : '' );
				update_option( 'bloyal_log_enable_disable', isset( $_POST['post_bloyal_enable_disable'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_enable_disable'] ) ) : '' );

				return wp_json_encode(
					array(
						'save_success'      => true,
						'access_key_status' => get_option( 'bloyal_access_key_verification' ),
					)
				);
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}

		/**
		 * Initialize bloyal_save_configuration_data_wpdb_order_processing.
		 *
		 * @param mixed $configuration_setting as parameter.
		 * @return json_string
		 */
		public function bloyal_save_configuration_data_wpdb_order_processing() {
			try {
				update_option( 'bloyal_use_order_engine', isset( $_POST['post_use_order_engine'] ) ? sanitize_text_field( wp_unslash( $_POST['post_use_order_engine'] ) ) : '' );
				update_option( 'bloyal_applied_shipping_charges', isset( $_POST['post_applied_shipping_charges'] ) ? sanitize_text_field( wp_unslash( $_POST['post_applied_shipping_charges'] ) ) : '' );
				update_option( 'bloyal_applied_taxes', isset( $_POST['post_applied_taxes'] ) ? sanitize_text_field( wp_unslash( $_POST['post_applied_taxes'] ) ) : '' );
				update_option( 'rowcount', isset( $_POST['post_row_count'] ) ? sanitize_text_field( wp_unslash( $_POST['post_row_count'] ) ) : '' );
				update_option( 'bloyal_shipping_carrier', isset( $_POST['post_shipping1'] ) ? sanitize_text_field( wp_unslash( $_POST['post_shipping1'] ) ) : '' );
				update_option( 'bloyal_shipping_service', isset( $_POST['post_shipping2'] ) ? sanitize_text_field( wp_unslash( $_POST['post_shipping2'] ) ) : '' );
				update_option( 'bloyal_shipping_method_name', isset( $_POST['post_shipping_method'] ) ? sanitize_text_field( wp_unslash(  $_POST['post_shipping_method'] ) ) : '' );
				update_option( 'bloyal_display_DOB', isset( $_POST['post_bloyal_display_DOB'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_display_DOB'] ) ) : '' );
				update_option( 'bloyal_required_DOB', isset( $_POST['post_bloyal_required_DOB'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_required_DOB'] ) ) : '' );
				update_option( 'bloyal_display_Phone', isset( $_POST['post_bloyal_display_Phone'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_display_Phone'] ) ) : '' );
				update_option( 'bloyal_required_Phone', isset( $_POST['post_bloyal_required_Phone'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_required_Phone'] ) ) : '' );
				update_option( 'bloyal_display_Email', isset( $_POST['post_bloyal_display_Email'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_display_Email'] ) ) : '' );
				update_option( 'bloyal_required_Email', isset( $_POST['post_bloyal_required_Email'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_required_Email'] ) ) : '' );
				update_option( 'bloyal_display_order_comments', isset( $_POST['post_bloyal_display_order_comments'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_display_order_comments'] ) ) : '' );
				update_option( 'bloyal_display_address_Book', isset( $_POST['post_bloyal_display_address_Book'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_display_address_Book'] ) ) : '' );
				update_option( 'bloyal_shipping_pickup', isset( $_POST['post_shipping_pickup'] ) ? sanitize_text_field( wp_unslash( $_POST['post_shipping_pickup'] ) ) : '' );
				update_option( 'bloyal_pickup_row_count', isset( $_POST['post_pickup_row_count'] ) ? sanitize_text_field( wp_unslash( $_POST['post_pickup_row_count'] ) ) : '' );

				return wp_json_encode(
					array(
						'save_success'      => true,
						'access_key_status' => get_option( 'bloyal_access_key_verification' ),
					)
				);
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}
		
		/**
		 * Initialize bloyal_save_accesskeyverification_data_wpdb.
		 *
		 * @param  mixed $access_key_verification as parameter.
		 * @param  mixed $is_custom_bloyal_url as parameter.
		 * @return json_string
		 */
		public function bloyal_save_accesskeyverification_data_wpdb( $access_key_verification, $is_custom_bloyal_url ) {
			try {
				update_option( 'bloyal_access_key_verification', $access_key_verification );
				update_option( 'is_bloyal_custom_api_url', $is_custom_bloyal_url );

				return wp_json_encode(
					array(
						'save_success'       => true,
						'access_key_status'  => get_option( 'bloyal_access_key_verification' ),
						'is_custom_url_used' => get_option( 'is_bloyal_custom_api_url' ),
					)
				);

			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}

		/**
		 * Function to validate bLoyal coupon
		 *
		 * @param string $coupon_code
		 * @return boolean
		 */
		public function bloyal_validate_coupon( $coupon_code ) {
			try {
				wc_clear_notices();			
				if(!empty(WC()->session->get( 'bloyal_coupon' ))){
					$aar_applied_coupon = WC()->session->get( 'bloyal_coupon' );
					if ( count( $aar_applied_coupon ) > 0 ) {
						foreach ( $aar_applied_coupon as $coupon_data ) {
							if ( strtolower( $coupon_data['coupon_code'] ) == strtolower( $coupon_code ) ) {
								$msg = "Coupon '" . $coupon_code . "' already applied.";
								wc_add_notice( $msg, 'error' );
								return;
							}
						}
					}
			    }
				$args        = array(
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'asc',
					'post_type'      => 'shop_coupon',
					'post_title'     => $coupon_code,
					'post_status'    => 'publish',
				);
				$woo_coupon  = 0;
				$coupon_data = get_posts( $args );
				WC()->session->set( 'woo_coupon_code', null );
				if ( ! empty( $coupon_data ) ) {
					foreach ( $coupon_data as $data ) {
						if ( strcasecmp( $data->post_title, $coupon_code ) == 0 ) {
							$woo_coupon = 1;
							if ( ! in_array( $coupon_code, WC()->cart->get_applied_coupons() ) ) {
								WC()->session->set( 'woo_coupon_code', $coupon_code );
							}
						}
					}
				}
				$cart_uid = $this->get_uid();
				if ( $cart_uid && ! $woo_coupon && $coupon_code ) {
					$action = 'carts/coupons?cartUid=' . $cart_uid . '&code=' . rawurlencode( $coupon_code );
					bLoyalLoggerService::write_custom_log( "Apply Coupon Request URL \r\n" . $action . "\r\n ======================\r\n", 1 );
					$result = $this->send_curl_request( '', $action, 'loyaltyengine', 1 );
					bLoyalLoggerService::write_custom_log( "Apply Coupon Response \r\n" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
					$success = 0;
					if ( isset( $result->data->Code ) && isset( $result->status ) ) {
						if ( strcasecmp( $result->data->Code, $coupon_code ) == 0 && $result->status == 'success' ) {
							WC()->session->set( 'bloyal_coupon_validate', 'true' );
							$success                = 1;
							$bloyal_coupons         = array();
							$session_bloyal_coupons = WC()->session->get( 'bloyal_coupon' );
							if ( ! empty( $session_bloyal_coupons ) ) {
								$bloyal_coupons = $session_bloyal_coupons;
							}
							WC()->session->set( 'session_bloyal_coupon_code', $coupon_code );
							$temp_coupon_array = array(
								'coupon_code' => $coupon_code,
								'cart_uid'    => $cart_uid,
								'flag'        => false,
							);
							$is_coupon_exist   = false;
							foreach ( $bloyal_coupons as $bloyal_coupon ) {
								if ( isset( $bloyal_coupon['coupon_code'] ) && strcasecmp( $bloyal_coupon['coupon_code'], $coupon_code ) == 0 ) {
									$is_coupon_exist = true;
								}
							}
							if ( ! $is_coupon_exist ) {
								array_push( $bloyal_coupons, $temp_coupon_array );
							}
							WC()->session->set( 'bloyal_coupon', $bloyal_coupons );
						}
					}
					wc_clear_notices();
					if ( ! $success ) {
						$msg = 'Coupon ' . $coupon_code . ' does not exist!';
						wc_add_notice( $msg, 'error' );
					} else {
						echo '<p hidden>' . esc_html__('Success', 'text-domain') . '</p>';
					}
				}
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}

		public function get_woocommerce_total_applied_discount() {
			try {
				$woocommerce_coupons = WC()->cart->get_applied_coupons();
				$woo_total_discount  = 0;
				foreach ( $woocommerce_coupons as $woo_coupon ) {
					$coupon_data        = new WC_Coupon( $woo_coupon );
					$coupon_amount      = WC()->cart->get_coupon_discount_amount( $woo_coupon );
					$woo_total_discount = $woo_total_discount + $coupon_amount;
				}
				return $woo_total_discount;
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
			}
		}

		/**
		 * Function to call the bLoyal order refund process
		 *
		 * @param integer $order_id
		 * @param integer $refund_id
		 *
		 * @return boolean
		 */
		public function bloyal_order_refunded( $order_id, $refund_id ) {
			try {
				$order                        = wc_get_order( $order_id );
				$refund                       = wc_get_order( $refund_id );
				$line_items                   = array();
				$is_external_discount_applied = false;
				foreach ( $order->get_items() as $item_id => $item_obj ) {
					$product     = $item_obj->get_product();
					$external_id = $item_obj->get_product_id();
					$product_obj = new WC_Product( $external_id );
					$quantity    = $item_obj->get_quantity();
					foreach ( $refund->get_items( 'line_item' ) as $refund_item_id => $refund_item_obj ) {
						if ( $item_obj->get_product_id() == $refund_item_obj->get_product_id() ) {
							$quantity = $refund_item_obj->get_quantity();
						}
					}
					$sale_price_discount  = wc_get_order_item_meta( $item_id, '_bloyal_sale_price_discount' );
					$item_price           = $product_obj->get_regular_price();
					$order_level_discount = wc_get_order_item_meta( $item_id, '_bloyal_order_level_discount' );
					$item_level_discount  = wc_get_order_item_meta( $item_id, '_bloyal_item_level_discount' );
					$external_discount    = wc_get_order_item_meta( $item_id, '_bloyal_external_discount' );

					if ( $external_discount > 0 ) {
						$is_external_discount_applied = true;
					}

					$line_items[] = array(
						'ExternalId'    => $item_obj->get_product_id(),
						'ProductCode'   => is_object( $product ) ? $product->get_sku() : null,
						'ProductName'   => $item_obj->get_name(),
						'Quantity'      => ( $quantity < 0 ) ? $quantity : ( -1 * $quantity ),
						'Price'         => $item_price,
						'Weight'        => $product->get_weight() ? wc_format_decimal( $product->get_weight(), 2 ) : null,
						'SalePrice'     => $item_price - $sale_price_discount,
						'Discount'      => $item_level_discount,
						'OrderDiscount' => ( $external_discount > 0 ) ? $external_discount : $order_level_discount,
					);
				}
				$is_guest    = true;
				$customer_id = $order->get_user_id();
				if ( $customer_id ) {
					$is_guest = false;
				}
				$customer      = new WC_Customer( $customer_id );
				$customer_data = array(
					'FirstName'    => $customer->get_first_name(),
					'LastName'     => $customer->get_last_name(),
					'CompanyName'  => $customer->get_billing_company(),
					'Phone1'       => $customer->get_billing_phone(),
					'MobilePhone'  => $customer->get_billing_phone(),
					'Address'      => array(
						'Address1'   => $customer->get_billing_address_1(),
						'Address2'   => $customer->get_billing_address_2(),
						'City'       => $customer->get_billing_city(),
						'State'      => $customer->get_billing_state(),
						'PostalCode' => $customer->get_billing_postcode(),
						'Country'    => $customer->get_billing_country(),
					),
					'EmailAddress' => $customer->get_email(),
					'ExternalId'   => (string) $customer_id,
					'CreatedLocal' => $this->format_datetime( $customer->get_date_created() ? $customer->get_date_created()->getTimestamp() : 0 ),
				);

				$external_discount_total = get_post_meta( $order_id, '_bloyal_extenal_discount_total', true );
				$order_discount_total    = get_post_meta( $order_id, '_bloyal_order_level_discount_total', true );
				$bloyal_applied_coupons  = get_post_meta( $order_id, '_bloyal_applied_coupons', true );

				$cart_details              = array(
					'GuestCheckout'             => $is_guest,
					'Uid'                       => '',
					'Customer'                  => $customer_data,
					'Lines'                     => $line_items,
					'ExternallyAppliedDiscount' => $is_external_discount_applied,
					'Discount'                  => $is_external_discount_applied ? $external_discount_total : $order_discount_total,
				);
				$cart_request_to_calculate = array(
					'CouponCodes' => $bloyal_applied_coupons,
					'Cart'        => $cart_details,
					'StoreCode'   => '',
					'DeviceCode'  => '',
				);
				bLoyalLoggerService::write_custom_log( "Calculate Cart Refund Request \r\n " . wp_json_encode( $cart_request_to_calculate ) . "\r\n ===================\r\n", 1 );
				$calculate_cart_result = $this->send_curl_request( $cart_request_to_calculate, 'carts/commands/calculates', 'loyaltyengine', 1 );
				bLoyalLoggerService::write_custom_log( "Calculate Cart Refund Response \r\n " . wp_json_encode( $calculate_cart_result ) . "\r\n ===================\r\n", 1 );

				if ( isset( $calculate_cart_result->status ) && $calculate_cart_result->status == 'success' ) {
					$u_id         = isset( $calculate_cart_result->data->Cart->Uid ) ? $calculate_cart_result->data->Cart->Uid : '';
					$cart_request = array(
						'CartUid'         => $u_id,
						'ReferenceNumber' => $order_id,
					);
					bLoyalLoggerService::write_custom_log( "Approve Cart Refund Request \r\n " . wp_json_encode( $cart_request ) . "\r\n ===================\r\n", 1 );
					$approve_result = $this->send_curl_request( $cart_request, 'carts/commands/approve', 'loyaltyengine', 1 );
					bLoyalLoggerService::write_custom_log( "Approve Cart Refund Response \r\n " . wp_json_encode( $approve_result ) . "\r\n ===================\r\n", 1 );
					if ( isset( $approve_result->status ) && $approve_result->status == 'success' ) {
						$cart_request['CartSourceExternalId'] = 'R-' . $order_id;
						bLoyalLoggerService::write_custom_log( "Commit Cart Refund Request \r\n " . wp_json_encode( $cart_request ) . "\r\n ===================\r\n", 1 );
						$commit_result = $this->send_curl_request( $cart_request, 'carts/commands/commit', 'loyaltyengine', 1 );
						bLoyalLoggerService::write_custom_log( "Commit Cart Refund Response \r\n " . wp_json_encode( $commit_result ) . "\r\n ===================\r\n", 1 );
						return $commit_result;
					}
				}
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in order refund. Reason: ' . $exception->getMessage() );
				return $exception->getMessage();
			}
		}

		/**
		 * Function to get the bLoyal Uid
		 *
		 * @param null
		 *
		 * @return uid
		 */
		function get_uid() {
			try {
				$woo_session_id = WC()->session->get_customer_id();
				if ( $woo_session_id ) {
					$session = WC()->session->get( 'bloyal_uid' );
					if ( ! empty( $session ) ) {
						if ( ! empty( $session['session_id'] ) ) {
							if ( $woo_session_id == $session['session_id'] ) {
								return $session['u_id'];
							}
						}
					}
				}
				return '';
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in getting uid. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function getSessionData() {
			try {
				$bloyalSession = WC()->session->get( 'bloyal_shipping_rate_cost' );
				if ( ! empty( $bloyalSession ) ) {
					$woo_session_id    = WC()->session->get_customer_id();
					$bloyal_session_id = isset( $bloyalSession['session_id'] ) ? $bloyalSession['session_id'] : '';
					if ( ( $woo_session_id == $bloyal_session_id ) ) {
						return $bloyalSession;
					}
				}
				return '';
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
			}
		}

		/**
		 * Function to send the curl request for all bLoyal APIs
		 *
		 * @param array   $cart_request
		 * @param string  $action
		 * @param string  $api_type
		 * @param boolean $is_post
		 *
		 * @return api result
		 */
		function send_curl_request( $request_content, $action, $api_type, $is_post = 0 ) {
			try {
				$content = wp_json_encode( $request_content );
				switch ( $api_type ) {
					case 'loyaltyengine': {
						$post_url = $this->loyalty_engine_url . '/api/v4/' . $this->access_key . '/' . ( $action );
						if ( isset( $this->is_custom_api_url_used ) && $this->is_custom_api_url_used == 'true' ) {
							if ( isset( $this->custom_loyaltyengine_api_url ) && ! empty( $this->custom_loyaltyengine_api_url ) ) {
								$post_url = $this->custom_loyaltyengine_api_url . '/api/v4/' . $this->access_key . '/' . $action;
							}
						}
						break;
					}
					case 'grid': {
						$post_url = $this->grid_api_url . '/api/v4/' . $this->access_key . '/' . $action;
						if ( isset( $this->is_custom_api_url_used ) && $this->is_custom_api_url_used == 'true' ) {
							if ( isset( $this->custom_grid_api_url_name ) && ! empty( $this->custom_grid_api_url_name ) ) {
								$post_url = $this->custom_grid_api_url_name . '/api/v4/' . $this->access_key . '/' . $action;
							}
						}
						break;
					}
					case 'web_snippets_api_url': {
						$post_url = $this->web_snippet_api_url . '/api/v4/' . $this->domain_name . '/' . $action;
						break;
					}
					case 'websnippets': {
						$post_url = $this->web_snippet_api_url . '/api/v4/' . $this->access_key . '/' . $this->domain_name . '/' . $action;
						break;
					}
					case 'orderengine': {
						$post_url = $this->order_engine_api_url . '/api/v4/' . $this->access_key . '/shippingrates/commands/calculate';
						break;
					}
					case 'default': {
						$post_url = false;
						break;
					}
				}
				if ( $post_url ) {
					if ( $is_post == 2 ) {
						$method = 'DELETE';
						$args = array(
							'headers' => array(
								'Content-Type' => 'application/json',
							),
							'body'    => $content,
							'method'  => 'DELETE',
							'timeout' => 45,
						);
						$response = wp_remote_request( $post_url, $args );
					}else if( $is_post == 1 ) {
						$method = 'POST';
						$args = array(
							'headers' => array(
								'Content-Type' => 'application/json',
							),
							'body'    => $content,
							'method'  => 'POST',
							'timeout' => 45,
						);
						$response = wp_remote_post( $post_url, $args );
					}else {
						$args = array(
							'headers' => array(
								'Content-Type' => 'application/json',
							),
						    'body'    => '', // $content,
							'method'  => 'GET',
							'timeout' => 45,
						);
						$response = wp_remote_get( $post_url, $args );
					}
					
					$response_status = wp_remote_retrieve_response_code( $response );
					$response         = wp_remote_retrieve_body( $response );
					$result = json_decode( $response, true );
					if ( is_wp_error( $result ) ) {
						$error = $response->get_error_message();
						return $error;
					} else {
						$obj_response = json_decode( $response );
						$bloyalAlerts = array();
						if ( isset( $obj_response->data ) ) {
							if ( $action == 'carts/commands/approve' ) {
								$bloyalAlerts = $obj_response->data->Alerts;
							} else {
								if ( isset( $obj_response->data->LoyaltySummary->Alerts ) ) {
									$bloyalAlerts = $obj_response->data->LoyaltySummary->Alerts;
								}
							}
							if ( ! empty( $bloyalAlerts ) ) {
								$arrAlertData = WC()->session->get( 'bloyal_alerts_data' );
								if ( ! empty( $arrAlertData ) ) {
									foreach ( $arrAlertData as $alertValue ) {
										if ( strtolower( $alertValue->Category ) == 'informational' ) {
											array_push( $bloyalAlerts, $alertValue );
										}
									}
								}
							}
							WC()->session->set( 'bloyal_alerts_data', $bloyalAlerts );
						}
						return $obj_response;
					}
				}
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in curl request. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}

		}

		/**
		 * Function to fetch service urls by bloyal
		 *
		 * @return api result
		 */
		function bloyal_get_service_urls( $domain_name, $domain_url, $is_bloyal_custom_url_used ) {
			try {
				//bLoyal API use for get the all service urls by bLoyal.
				$post_url = 'https://domain.bloyal.com/api/v4/serviceurls/' . $domain_name;
				if ( $is_bloyal_custom_url_used == 'true' ) {
					if ( $domain_url ) {
						$post_url = $domain_url . '/api/v4/serviceurls/' . $domain_name;
					}
				}
				bLoyalLoggerService::write_custom_log( "Get Service URL \r\n " . $post_url . "\r\n ===================\r\n", 1 );
				$args = array(
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => $content,
					'method'  => 'GET',
					'timeout' => 45,
				);
				$response        = wp_remote_get( $post_url, $args );
				$response_status = wp_remote_retrieve_response_code( $response );
				$response        = wp_remote_retrieve_body( $response );
				bLoyalLoggerService::write_custom_log( "Get Service Response \r\n " . wp_json_encode( $response ) . "\r\n ===================\r\n", 1 );
				$result = json_decode( $response, true );
				if ( is_wp_error( $result ) ) {
					$error = $response->get_error_message();
					return $error;
				} else {
                    $response = json_decode($response);
                    if (! empty($response->data)) {
                        if (isset($response->data->DirectorUrl)) {
                            update_option('director_url', $response->data->DirectorUrl);
                        }

                        if (isset($response->data->POSSnippetsUrl)) {
                            update_option('pos_snippets_url', $response->data->POSSnippetsUrl);
                        }

                        if (isset($response->data->WebSnippetsUrl)) {
                            update_option('web_snippets_url', $response->data->WebSnippetsUrl);
                        }

                        if (isset($response->data->MyMobileLoyaltyUrl)) {
                            update_option('my_mobile_loyalty_url', $response->data->MyMobileLoyaltyUrl);
                        }

                        if (isset($response->data->GridApiUrl)) {
                            update_option('grid_api_url', $response->data->GridApiUrl);
                        }

                        if (isset($response->data->LoyaltyEngineApiUrl)) {
                            update_option('loyalty_engine_api_url', $response->data->LoyaltyEngineApiUrl);
                        }

                        if (isset($response->data->EngagementEngineApiUrl)) {
                            update_option('engagement_engine_api_url', $response->data->EngagementEngineApiUrl);
                        }

                        if (isset($response->data->OrderEngineApiUrl)) {
                            update_option('order_engine_api_url', $response->data->OrderEngineApiUrl);
                        }

                        if (isset($response->data->ServiceProviderGatewayApiUrl)) {
                            update_option('service_provider_gateway_api_url', $response->data->ServiceProviderGatewayApiUrl);
                        }

                        if (isset($response->data->WebSnippetsApiUrl)) {
                            update_option('web_snippets_api_url', $response->data->WebSnippetsApiUrl);
                        }

                        if (isset($response->data->PaymentApiUrl)) {
                            update_option('payment_api_url', $response->data->PaymentApiUrl);
                        }

                        if (isset($response->data->LoggingApiUrl)) {
                            update_option('logging_api_url', $response->data->LoggingApiUrl);
                        }
                    }
                }
				return $response->data;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in getting service urls. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}

		}


		public function bloyal_remove_all_bloyal_coupons() {
			try {
				$cart_uid = $this->get_uid();
				$action   = 'carts/coupons/?cartUid=' . $cart_uid;
				bLoyalLoggerService::write_custom_log( "Remove bLoyal Coupon URL \r\n " . $action . "\r\n ===================\r\n", 1 );
				$result = $this->send_curl_request( '', $action, 'loyaltyengine', 2 );
				bLoyalLoggerService::write_custom_log( "Remove bLoyal Coupon Response \r\n " . wp_json_encode( $result ) . "\r\n ===================\r\n", 1 );
				WC()->session->set( 'bloyal_coupon', null );
				wc_add_notice( 'Coupon(s) has been removed.', 'success' );
				header( 'Location: ' . sanitize_text_field( $_SERVER['HTTP_REFERER'] ) );
				exit;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in removing bloyal coupons. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		public function bloyal_remove_all_bloyal_gift_cards() {

			try {
				WC()->session->set( 'bloyal_gift_card', null );
				wc_add_notice( 'Gift cards removed successfully.', 'success' );
				header( 'Location: ' . sanitize_text_field($_SERVER['HTTP_REFERER'] ) );
				exit;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in removing bloyal gift cards. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		public function bloyal_remove_all_bloyal_loyalty_dollars() {
			try {
				WC()->session->set( 'bloyal_loyalty_dollar', null );
				wc_add_notice( 'Loyalty dollars removed successfully.', 'success' );
				header( 'Location: ' . sanitize_text_field( $_SERVER['HTTP_REFERER'] ) );
				exit;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in removing bloyal loyalty dollars. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		public function bloyal_remove_all_on_account_dollars() {
			try {
				WC()->session->set( 'bloyal_on_account', null );
				wc_add_notice( 'On Account dollars removed successfully.', 'success' );
				header( 'Location: ' . sanitize_text_field( $_SERVER['HTTP_REFERER'] ) );
				exit;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in removing bloyal on account dollars. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		public function bloyal_fetch_orders_after_modified_date( $request ) {
			try {
				global $wpdb;
				$modified_date_param = wc_rest_prepare_date_response( $request['modified_date'], false );
				$per_page            = $request['per_page'];
				$offset              = $request['offset'];
				if ( empty( $offset ) ) {
					$offset = 0;
				}
				if ( empty( $per_page ) ) {
					$per_page = 100;
				}
				$status_param = 'wc-' . $request['status'];
				if ( isset( $request['status'] ) ) {
					$querystr = "select ID, post_modified from $wpdb->posts where post_type=%s AND post_modified > %d AND post_status = %d LIMIT " . $per_page . ' OFFSET ' . $offset;
					$orders        = $wpdb->get_results( $wpdb->prepare( $querystr, 'shop_order',  $modified_date_param, $status_param) );
				} else {
					$querystr = "select ID, post_modified from $wpdb->posts where post_type=%s AND post_modified > %d LIMIT " . $per_page . ' OFFSET ' . $offset;
					$orders        = $wpdb->get_results( $wpdb->prepare( $querystr, 'shop_order',  $modified_date_param) );
				}
				$order_details = array();
				foreach ( $orders as $order ) {
					$order_data    = new WC_Order( $order->ID );
					$order_refunds = array();
					foreach ( $order_data->get_refunds() as $refund_id => $refund_obj ) {
						$order_refunds[] = array(
							'id'     => $refund_obj->get_id(),
							'refund' => $refund_obj->get_reason(),
							'total'  => wc_format_decimal( $refund_obj->get_amount() * -1, 2 ),
						);
					}
					$order_line_items = array();
					foreach ( $order_data->get_items() as $item_id => $item_obj ) {
						$product            = $item_obj->get_product();
						$order_line_items[] = array(
							'id'           => $item_id,
							'name'         => $item_obj->get_name(),
							'product_id'   => $item_obj->get_product_id(),
							'variation_id' => $item_obj->get_variation_id(),
							'quantity'     => $item_obj->get_quantity(),
							'tax_class'    => $item_obj->get_tax_class(),
							'subtotal'     => wc_format_decimal( $item_obj->get_subtotal(), 2 ),
							'subtotal_tax' => wc_format_decimal( $item_obj->get_subtotal_tax(), 2 ),
							'total'        => wc_format_decimal( $item_obj->get_total(), 2 ),
							'total_tax'    => wc_format_decimal( $item_obj->get_total_tax(), 2 ),
							'taxes'        => $item_obj->get_taxes(),
							'meta_data'    => $item_obj->get_meta_data(),
							'sku'          => is_object( $product ) ? $product->get_sku() : null,
							'price'        => wc_format_decimal( $order_data->get_item_total( $item_obj, false, false ), 2 ),
						);
					}
					$order_shipping_lines = array();
					foreach ( $order_data->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
						$order_shipping_lines[] = array(
							'id'           => $shipping_item_id,
							'method_id'    => $shipping_item->get_method_id(),
							'method_title' => $shipping_item->get_name(),
							'total'        => wc_format_decimal( $shipping_item->get_total(), 2 ),
						);
					}
					$order_tax_lines = array();
					foreach ( $order_data->get_tax_totals() as $tax_code => $tax ) {
						$order_tax_lines[] = array(
							'code'     => $tax_code,
							'title'    => $tax->label,
							'total'    => wc_format_decimal( $tax->amount, 2 ),
							'compound' => (bool) $tax->is_compound,
						);
					}
					$order_fee_lines = array();
					foreach ( $order_data->get_fees() as $fee_item_id => $fee_item ) {
						$order_fee_lines[] = array(
							'id'        => $fee_item_id,
							'title'     => $fee_item->get_name(),
							'tax_class' => $fee_item->get_tax_class(),
							'total'     => wc_format_decimal( $order_data->get_line_total( $fee_item ), 2 ),
							'total_tax' => wc_format_decimal( $order_data->get_line_tax( $fee_item ), 2 ),
						);
					}
					$order_coupon_lines = array();
					foreach ( $order_data->get_items( 'coupon' ) as $coupon_item_id => $coupon_item ) {
						$order_coupon_lines[] = array(
							'id'     => $coupon_item_id,
							'code'   => $coupon_item->get_code(),
							'amount' => wc_format_decimal( $coupon_item->get_discount(), 2 ),
						);
					}
					$order_data_obj     = wp_json_encode( $order_data->data );
					$decoded_order_data = json_decode( $order_data_obj, true );
					$order_details[]    = array(
						'id'                   => $decoded_order_data['id'],
						'parent_id'            => $decoded_order_data['parent_id'],
						'number'               => $decoded_order_data['number'],
						'order_key'            => $decoded_order_data['order_key'],
						'created_via'          => $decoded_order_data['created_via'],
						'version'              => wc_format_decimal( $decoded_order_data['version'], 2 ),
						'status'               => $order_data->get_status(),
						'currency'             => $order_data->get_currency(),
						'date_created'         => $this->format_datetime( $decoded_order_data['date_created']['date'], false, false ),
						'date_created_gmt'     => $this->format_datetime( $decoded_order_data['date_created']['date'], false, true ),
						'date_modified'        => $this->format_datetime( $decoded_order_data['date_modified']['date'], false, false ),
						'date_modified_gmt'    => $this->format_datetime( $decoded_order_data['date_modified']['date'], false, true ),
						'discount_total'       => wc_format_decimal( $decoded_order_data['discount_total'], 2 ),
						'discount_tax'         => wc_format_decimal( $decoded_order_data['discount_tax'], 2 ),
						'shipping_total'       => wc_format_decimal( $decoded_order_data['shipping_total'], 2 ),
						'shipping_tax'         => wc_format_decimal( $decoded_order_data['shipping_tax'], 2 ),
						'cart_tax'             => wc_format_decimal( $decoded_order_data['cart_tax'], 2 ),
						'total'                => wc_format_decimal( $decoded_order_data['total'], 2 ),
						'total_tax'            => wc_format_decimal( $decoded_order_data['total_tax'], 2 ),
						'prices_include_tax'   => (bool) $decoded_order_data['prices_include_tax'],
						'customer_id'          => $decoded_order_data['customer_id'],
						'customer_ip_address'  => $decoded_order_data['customer_ip_address'],
						'customer_user_agent'  => $decoded_order_data['customer_user_agent'],
						'customer_note'        => $decoded_order_data['customer_note'],
						'billing'              => $decoded_order_data['billing'],
						'shipping'             => $decoded_order_data['shipping'],
						'payment_method'       => $order_data->get_payment_method(),
						'payment_method_title' => $order_data->get_payment_method_title(),
						'transaction_id'       => $decoded_order_data['transaction_id'],
						'date_paid'            => $this->format_datetime( $decoded_order_data['date_paid']['date'], false, false ),
						'date_paid_gmt'        => $this->format_datetime( $decoded_order_data['date_paid']['date'], false, true ),
						'date_completed'       => $this->format_datetime( $decoded_order_data['date_completed']['date'], false, false ),
						'date_completed_gmt'   => $this->format_datetime( $decoded_order_data['date_completed']['date'], false, true ),
						'cart_hash'            => $decoded_order_data['cart_hash'],
						'meta_data'            => $decoded_order_data['meta_data'],
						'line_items'           => $order_line_items,
						'tax_lines'            => $order_tax_lines,
						'shipping_lines'       => $order_shipping_lines,
						'fee_lines'            => $order_fee_lines,
						'coupon_lines'         => $order_coupon_lines,
						'refunds'              => $order_refunds,
					);
				}
				return rest_ensure_response( $order_details );
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in fetching orders. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function bloyal_get_customer_date_created( $customer, $convert_to_gmt = false ) {
			try {
				if ( $convert_to_gmt == true ) {
					$created_date = $this->format_datetime( ( $customer->get_date_created() ? $customer->get_date_created()->getTimestamp() : 0 ), false, true );
				} else {
					$created_date = $this->format_datetime( ( $customer->get_date_created() ? $customer->get_date_created()->getTimestamp() : 0 ), false, false );
				}
				if ( $created_date == '1970-01-01T00:00:00' ) {
					return null;
				} else {
					return $created_date;
				}
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in getting customer creation date. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}

		}

		function bloyal_get_customer_date_modified( $customer, $convert_to_gmt = false ) {
			try {
				if ( $convert_to_gmt == true ) {
					$modified_date = $this->format_datetime( ( $customer->get_date_modified() ? $customer->get_date_modified()->getTimestamp() : 0 ), false, true );
				} else {
					$modified_date = $this->format_datetime( ( $customer->get_date_modified() ? $customer->get_date_modified()->getTimestamp() : 0 ), false, false );
				}
				if ( $modified_date == '1970-01-01T00:00:00' ) {
					return null;
				} else {
					return $modified_date;
				}
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in getting customer modified date. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		public function bloyal_fetch_customers_after_modified_date( $request ) {
			try {
				global $wpdb;
				$param            = wc_rest_prepare_date_response( $request['created_date'], false );
				$meta_capability  = $wpdb->prefix . 'capabilities';
				$querystr         = "SELECT DISTINCT s.ID from (SELECT u.ID FROM $wpdb->users u INNER JOIN $wpdb->usermeta m ON m.user_id = u.ID WHERE m.meta_key =  '%s' AND m.meta_value LIKE '%customer%') s INNER JOIN $wpdb->users p ON p.ID = s.ID WHERE user_registered > '%s'";
				$customers        = $wpdb->get_results( $wpdb->prepare( $querystr, $meta_capability, $param ) );
				$customer_details = array();
				foreach ( $customers as $customer ) {
					$customer           = new WC_Customer( $customer->ID );
					$customer_details[] = array(
						'id'                 => $customer->get_id(),
						'date_created'       => $this->bloyal_get_customer_date_created( $customer, 0 ),
						'date_created_gmt'   => $this->bloyal_get_customer_date_created( $customer, 1 ),
						'date_modified'      => $this->bloyal_get_customer_date_modified( $customer, 0 ),
						'date_modified_gmt'  => $this->bloyal_get_customer_date_modified( $customer, 1 ),
						'email'              => $customer->get_email(),
						'first_name'         => $customer->get_first_name(),
						'last_name'          => $customer->get_last_name(),
						'role'               => $customer->get_role(),
						'username'           => $customer->get_username(),
						'billing'            => array(
							'first_name' => $customer->get_billing_first_name(),
							'last_name'  => $customer->get_billing_last_name(),
							'company'    => $customer->get_billing_company(),
							'address_1'  => $customer->get_billing_address_1(),
							'address_2'  => $customer->get_billing_address_2(),
							'city'       => $customer->get_billing_city(),
							'state'      => $customer->get_billing_state(),
							'postcode'   => $customer->get_billing_postcode(),
							'country'    => $customer->get_billing_country(),
							'email'      => $customer->get_billing_email(),
							'phone'      => $customer->get_billing_phone(),
						),
						'shipping'           => array(
							'first_name' => $customer->get_shipping_first_name(),
							'last_name'  => $customer->get_shipping_last_name(),
							'company'    => $customer->get_shipping_company(),
							'address_1'  => $customer->get_shipping_address_1(),
							'address_2'  => $customer->get_shipping_address_2(),
							'city'       => $customer->get_shipping_city(),
							'state'      => $customer->get_shipping_state(),
							'postcode'   => $customer->get_shipping_postcode(),
							'country'    => $customer->get_shipping_country(),
						),
						'is_paying_customer' => (bool) $customer->get_is_paying_customer(),
						'orders_count'       => (int) $customer->get_order_count(),
						'total_spent'        => wc_format_decimal( $customer->get_total_spent(), 2 ),
						'avatar_url'         => $customer->get_avatar_url(),
						'meta_data'          => $customer->get_meta_data(),
					);
				}
				return rest_ensure_response( $customer_details );
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in fetching customers. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		public function bloyal_fetch_products_after_modified_date( $request ) {
			try {
				global $wpdb;
				$param    = wc_rest_prepare_date_response( $request['modified_date'], false );
				$querystr = "SELECT ID, post_modified FROM $wpdb->posts WHERE post_type=%s AND post_modified > %s";
				$products = $wpdb->get_results( $wpdb->prepare(  $querystr, 'product', $param ) );

				$product_details = array();

				foreach ( $products as $product ) {
					$product              = wc_get_product( $product->ID );
					$product_data         = wp_json_encode( $product->data );
					$decoded_product_data = json_decode( $product_data, false );
					$product_categories   = array();
					$terms                = get_the_terms( $product->get_id(), 'product_cat' );
					$terms                = is_array( $terms ) ? $terms : array( $terms );
					foreach ( $terms as $term ) {
						$product_categories[] = array(
							'id'   => (int) $term->term_id,
							'name' => $term->name,
							'slug' => $term->slug,
						);
					}
					$product_tags = array();
					$terms        = get_the_terms( $product->get_id(), 'product_tag' );
					$terms        = is_array( $terms ) ? $terms : array( $terms );
					foreach ( $terms as $term ) {
						$product_tags[] = array(
							'id'   => (int) $term->term_id,
							'name' => $term->name,
							'slug' => $term->slug,
						);
					}
					$product_variations = array();
					foreach ( $product->get_children() as $child_id ) {

						$variation = wc_get_product( $child_id );

						if ( ! $variation || ! $variation->exists() ) {
							continue;
						}

						$product_variations[] = $variation->get_id();
					}

					$product_details[] = array(
						'id'                    => $product->get_id(),
						'name'                  => $product->get_name(),
						'slug'                  => $product->get_slug(),
						'permalink'             => $product->get_permalink(),
						'date_created'          => $this->format_datetime( $product->get_date_created(), false, false ),
						'date_created_gmt'      => $this->format_datetime( $product->get_date_created(), false, true ),
						'date_modified'         => $this->format_datetime( $product->get_date_modified(), false, false ),
						'date_modified_gmt'     => $this->format_datetime( $product->get_date_modified(), false, true ),
						'type'                  => $product->get_type(),
						'status'                => $product->get_status(),
						'featured'              => $product->is_featured(),
						'catalog_visibility'    => $product->get_catalog_visibility(),
						'description'           => wc_clean( wpautop( do_shortcode( $product->get_description() ) ) ),
						'short_description'     => wc_clean( $product->get_short_description() ),
						'sku'                   => $product->get_sku(),
						'price'                 => wc_format_decimal( $product->get_regular_price(), 2 ),
						'regular_price'         => wc_format_decimal( $product->get_regular_price(), 2 ),
						'sale_price'            => $product->get_sale_price() ? wc_format_decimal( $product->get_sale_price(), 2 ) : null,
						'date_on_sale_from'     => $this->format_datetime( $product->get_date_on_sale_from(), false, false ),
						'date_on_sale_from_gmt' => $this->format_datetime( $product->get_date_on_sale_from(), false, true ),
						'date_on_sale_to'       => $this->format_datetime( $product->get_date_on_sale_to(), false, false ),
						'date_on_sale_to_gmt'   => $this->format_datetime( $product->get_date_on_sale_to(), false, true ),
						'price_html'            => $product->get_price_html(),
						'on_sale'               => (bool) $product->is_on_sale(),
						'purchaseable'          => (bool) $product->is_purchasable(),
						'total_sales'           => $product->get_total_sales(),
						'virtual'               => (bool) $product->is_virtual(),
						'downloadable'          => (bool) $product->is_downloadable(),
						'downloads'             => $this->get_downloads( $product ),
						'download_limit'        => (int) $product->get_download_limit(),
						'download_expiry'       => (int) $product->get_download_expiry(),
						'external_url'          => $product->is_type( 'external' ) ? $product->get_product_url() : '',
						'button_text'           => $product->is_type( 'external' ) ? $product->get_button_text() : '',
						'tax_status'            => $product->get_tax_status(),
						'tax_class'             => $product->get_tax_class(),
						'manage_stock'          => (bool) $product->managing_stock(),
						'stock_quantity'        => (int) $product->get_stock_quantity(),
						'in_stock'              => (bool) $product->is_in_stock(),
						'backorders'            => $product->get_backorders(),
						'backorders_allowed'    => (bool) $product->backorders_allowed(),
						'backordered'           => (bool) $product->is_on_backorder(),
						'sold_individually'     => (bool) $product->is_sold_individually(),
						'weight'                => $product->get_weight() ? wc_format_decimal( $product->get_weight(), 2 ) : null,
						'dimensions'            => array(
							'length' => $product->get_length(),
							'width'  => $product->get_width(),
							'height' => $product->get_height(),
							'unit'   => get_option( 'woocommerce_dimension_unit' ),
						),
						'shipping_required'     => (bool) $product->needs_shipping(),
						'shipping_taxable'      => (bool) $product->is_shipping_taxable(),
						'shipping_class'        => $product->get_shipping_class(),
						'shipping_class_id'     => ( 0 !== $product->get_shipping_class_id() ) ? $product->get_shipping_class_id() : null,
						'reviews_allowed'       => (bool) $product->get_reviews_allowed(),
						'average_rating'        => wc_format_decimal( $product->get_average_rating(), 2 ),
						'rating_count'          => (int) $product->get_rating_count(),
						'related_ids'           => array_map( 'absint', array_values( wc_get_related_products( $product->get_id() ) ) ),
						'upsell_ids'            => array_map( 'absint', $product->get_upsell_ids() ),
						'cross_sell_ids'        => array_map( 'absint', $product->get_cross_sell_ids() ),
						'parent_id'             => (int) $product->get_parent_id(),
						'purchase_note'         => wpautop( do_shortcode( wp_kses_post( $product->get_purchase_note() ) ) ),
						'categories'            => $product_categories,
						'tags'                  => $product_tags,
						'total_sales'           => $product->get_total_sales(),
						'images'                => $this->get_images( $product ),
						'attributes'            => $this->get_attributes( $product ),
						'default_attributes'    => $product->get_default_attributes(),
						'featured_src'          => wp_get_attachment_url( get_post_thumbnail_id( $product->get_id() ) ),
						'download_type'         => 'standard',
						'variations'            => $product_variations,
						'grouped_products'      => array(),
						'menu_order'            => (int) $product->get_menu_order(),
					);
				}
				return rest_ensure_response( $product_details );
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in fetching products. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}

		}

		public function bloyal_update_products( WP_REST_Request $request ) {
			try {
				global $wpdb;
				$parameters_body        = $request->get_body();
				$parameters_url         = $request->get_url_params();
				$parameters_query       = $request->get_query_params();
				$parameters_body        = json_decode( $parameters_body );
				$db_table_name          = $wpdb->prefix . 'postmeta';
				$product_check_id_query = "select post_id from $db_table_name where post_id =%d";
				$products_select_result = $wpdb->get_results( $wpdb->prepare( $product_check_id_query, $parameters_url['id'] ) );
				if ( empty( $products_select_result ) ) {
					return new WP_Error(
						"woocommerce_rest_{$this->post_type}_invalid_id",
						__( 'Invalid ID.', 'woocommerce' ),
						array(
							'status' => 404,
						)
					);
				}

				$db_table_name          = $wpdb->prefix . 'woocommerce_api_keys';
				$user_auth_query        = "select permissions, consumer_secret from $db_table_name where consumer_secret =%s";
				$user_auth_query_result = $wpdb->get_results( $wpdb->prepare( $user_auth_query, $parameters_query['consumer_secret']  ) );
				if ( $user_auth_query_result[0]->permissions == 'read' ) {
					return new WP_Error( 'woocommerce_rest_authentication_error', __( 'The API key provided does not have write permissions.', 'woocommerce' ), array( 'status' => 401 ) );
				}

				if ( $user_auth_query_result[0]->permissions == 'read_write' || $user_auth_query_result[0]->permissions == 'write' ) {
					$db_table_name = $wpdb->prefix . 'postmeta';
					if ( $parameters_body->manage_stock === false ) {
						$manage_stock           = 0;
						$product_update_query   = "update $db_table_name set meta_value = '" . $manage_stock . "' where post_id = %d  AND meta_key = %s";
						$products_update_result = $wpdb->get_results( $wpdb->prepare( $product_update_query, $parameters_url['id'], '_manage_stock' ) );
					}

					if ( $parameters_body->manage_stock === true ) {
						$manage_stock           = 1;
						$product_update_query   = "update $db_table_name set meta_value = '" . $manage_stock . "' where post_id = %d AND meta_key = %s";
						$products_update_result = $wpdb->query( $wpdb->prepare( $product_update_query, $parameters_url['id'], '_manage_stock' ) );
					}

					if ( ! empty( $parameters_body->regular_price ) ) {
						$product_update_query   = "update $db_table_name set meta_value = '" . $parameters_body->regular_price . "' where post_id = %d AND meta_key = %s";
						$products_update_result = $wpdb->query( $wpdb->prepare( $product_update_query, $parameters_url['id'], '_regular_price' ) );
						$product_update_query   = "update $db_table_name set meta_value = '" . $parameters_body->regular_price . "' where post_id = %d AND meta_key = %s";
						$products_update_result = $wpdb->query( $wpdb->prepare( $product_update_query, $parameters_url['id'],  '_price') );
					}

					if ( ! empty( $parameters_body->sku ) ) {
						$product_update_query   = "update $db_table_name set meta_value ='" . $parameters_body->sku . "' where post_id %d = AND meta_key = %s";
						$products_update_result = $wpdb->query( $wpdb->prepare( $product_update_query,  $parameters_url['id'], '_sku' ) );
					}
				} else {
					if ( $user_auth_query_result[0]->permissions != 'read_write' || $user_auth_query_result[0]->permissions != 'write' ) {
						return new WP_Error( 'woocommerce_rest_authentication_error', __( 'Invalid API key.', 'woocommerce' ), array( 'status' => 401 ) );
					}
				}
				if ( $products_update_result == 1 ) {

					$return_value = array(
						'message' => 'Product updated successfully.',
						'data'    => array(
							'status' => 200,
						),
					);
					return $return_value;
				}
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in fetching products. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}

		}

		public function bloyal_fetch_product_by_sku( $request ) {
			try {
				global $wpdb;
				$db_table_posts    = $wpdb->prefix . 'posts';
				$db_table_postmeta = $wpdb->prefix . 'postmeta';
				$param             = $request['sku'];
				$querystr          = "SELECT wp.ID, post_modified, post_id
							 FROM $db_table_posts wp
							 INNER JOIN $db_table_postmeta wpm ON wp.id=wpm.post_id
							 WHERE post_type=%s AND `meta_key` = %s and `meta_value` = %s";

				$products = $wpdb->get_results( $wpdb->prepare(  $querystr, 'product', '_sku', $param ) );
				if ( empty( $products ) ) {

					$querystr = "SELECT wp.post_parent
							 	FROM $db_table_posts wp
							 	INNER JOIN $db_table_postmeta wpm ON wp.id=wpm.post_id
							 	WHERE post_type=%s AND `meta_key` = %s and `meta_value` = %s";

					$products_ID = $wpdb->get_results( $wpdb->prepare( $querystr, 'product_variation', '_sku',  $param ) );
					if ( ! empty( $products_ID ) ) {
						$querystr = "SELECT wp.ID, post_modified, post_id
								 	FROM $db_table_posts wp
								 	INNER JOIN $db_table_postmeta wpm ON wp.id=wpm.post_id
								 	WHERE post_type=%s AND `meta_key` = %s and `post_id` = %d";

						$products = $wpdb->get_results( $wpdb->prepare( $querystr, 'product', '_sku', $products_ID[0]->post_parent ) );
					}
				}
				$product_details = array();
				if ( ! empty( $products ) ) {
					foreach ( $products as $product ) {
						$product              = wc_get_product( $product->ID );
						$product_data         = wp_json_encode( $product->data );
						$decoded_product_data = json_decode( $product_data, false );
						$product_categories   = array();
						$terms                = get_the_terms( $product->get_id(), 'product_cat' );
						$terms                = is_array( $terms ) ? $terms : array( $terms );
						foreach ( $terms as $term ) {
							$product_categories[] = array(
								'id'   => (int) $term->term_id,
								'name' => $term->name,
								'slug' => $term->slug,
							);
						}
						$product_tags = array();
						$terms        = get_the_terms( $product->get_id(), 'product_tag' );
						$terms        = is_array( $terms ) ? $terms : array( $terms );
						foreach ( $terms as $term ) {
							$product_tags[] = array(
								'id'   => (int) $term->term_id,
								'name' => $term->name,
								'slug' => $term->slug,
							);
						}
						$product_variations = array();
						foreach ( $product->get_children() as $child_id ) {

							$variation = wc_get_product( $child_id );

							if ( ! $variation || ! $variation->exists() ) {
								continue;
							}

							$product_variations[] = $variation->get_id();
						}
						$product_details[] = array(
							'id'                    => $product->get_id(),
							'name'                  => $product->get_name(),
							'slug'                  => $product->get_slug(),
							'permalink'             => $product->get_permalink(),
							'date_created'          => $this->format_datetime( $product->get_date_created(), false, false ),
							'date_created_gmt'      => $this->format_datetime( $product->get_date_created(), false, true ),
							'date_modified'         => $this->format_datetime( $product->get_date_modified(), false, false ),
							'date_modified_gmt'     => $this->format_datetime( $product->get_date_modified(), false, true ),
							'type'                  => $product->get_type(),
							'status'                => $product->get_status(),
							'featured'              => $product->is_featured(),
							'catalog_visibility'    => $product->get_catalog_visibility(),
							'description'           => wc_clean( wpautop( do_shortcode( $product->get_description() ) ) ),
							'short_description'     => wc_clean( $product->get_short_description() ),
							'sku'                   => $product->get_sku(),
							'price'                 => wc_format_decimal( $product->get_regular_price(), 2 ),
							'regular_price'         => wc_format_decimal( $product->get_regular_price(), 2 ),
							'sale_price'            => $product->get_sale_price() ? wc_format_decimal( $product->get_sale_price(), 2 ) : null,
							'date_on_sale_from'     => $this->format_datetime( $product->get_date_on_sale_from(), false, false ),
							'date_on_sale_from_gmt' => $this->format_datetime( $product->get_date_on_sale_from(), false, true ),
							'date_on_sale_to'       => $this->format_datetime( $product->get_date_on_sale_to(), false, false ),
							'date_on_sale_to_gmt'   => $this->format_datetime( $product->get_date_on_sale_to(), false, true ),
							'price_html'            => $product->get_price_html(),
							'on_sale'               => (bool) $product->is_on_sale(),
							'purchaseable'          => (bool) $product->is_purchasable(),
							'total_sales'           => $product->get_total_sales(),
							'virtual'               => (bool) $product->is_virtual(),
							'downloadable'          => (bool) $product->is_downloadable(),
							'downloads'             => $this->get_downloads( $product ),
							'download_limit'        => (int) $product->get_download_limit(),
							'download_expiry'       => (int) $product->get_download_expiry(),
							'external_url'          => $product->is_type( 'external' ) ? $product->get_product_url() : '',
							'button_text'           => $product->is_type( 'external' ) ? $product->get_button_text() : '',
							'tax_status'            => $product->get_tax_status(),
							'tax_class'             => $product->get_tax_class(),
							'manage_stock'          => (bool) $product->managing_stock(),
							'stock_quantity'        => (int) $product->get_stock_quantity(),
							'in_stock'              => (bool) $product->is_in_stock(),
							'backorders'            => $product->get_backorders(),
							'backorders_allowed'    => (bool) $product->backorders_allowed(),
							'backordered'           => (bool) $product->is_on_backorder(),
							'sold_individually'     => (bool) $product->is_sold_individually(),
							'weight'                => $product->get_weight() ? wc_format_decimal( $product->get_weight(), 2 ) : null,
							'dimensions'            => array(
								'length' => $product->get_length(),
								'width'  => $product->get_width(),
								'height' => $product->get_height(),
								'unit'   => get_option( 'woocommerce_dimension_unit' ),
							),
							'shipping_required'     => (bool) $product->needs_shipping(),
							'shipping_taxable'      => (bool) $product->is_shipping_taxable(),
							'shipping_class'        => $product->get_shipping_class(),
							'shipping_class_id'     => ( 0 !== $product->get_shipping_class_id() ) ? $product->get_shipping_class_id() : null,
							'reviews_allowed'       => (bool) $product->get_reviews_allowed(),
							'average_rating'        => wc_format_decimal( $product->get_average_rating(), 2 ),
							'rating_count'          => (int) $product->get_rating_count(),
							'related_ids'           => array_map( 'absint', array_values( wc_get_related_products( $product->get_id() ) ) ),
							'upsell_ids'            => array_map( 'absint', $product->get_upsell_ids() ),
							'cross_sell_ids'        => array_map( 'absint', $product->get_cross_sell_ids() ),
							'parent_id'             => (int) $product->get_parent_id(),
							'purchase_note'         => wpautop( do_shortcode( wp_kses_post( $product->get_purchase_note() ) ) ),
							'categories'            => $product_categories,
							'tags'                  => $product_tags,
							'total_sales'           => $product->get_total_sales(),
							'images'                => $this->get_images( $product ),
							'attributes'            => $this->get_attributes( $product ),
							'default_attributes'    => $product->get_default_attributes(),
							'featured_src'          => wp_get_attachment_url( get_post_thumbnail_id( $product->get_id() ) ),
							'download_type'         => 'standard',
							'variations'            => $product_variations,
							'grouped_products'      => array(),
							'menu_order'            => (int) $product->get_menu_order(),
						);
					}
				}
				return rest_ensure_response( $product_details );

			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in fetching products. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function get_attributes( $product ) {
			try {
				$attributes = array();

				if ( $product->is_type( 'variation' ) ) {
					foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
						$attributes[] = array(
							'name'   => wc_attribute_label( str_replace( 'attribute_', '', $attribute_name ) ),
							'slug'   => str_replace( 'attribute_', '', str_replace( 'pa_', '', $attribute_name ) ),
							'option' => $attribute,
						);
					}
				} else {
					foreach ( $product->get_attributes() as $attribute ) {
						$attributes[] = array(
							'name'      => wc_attribute_label( $attribute['name'] ),
							'slug'      => str_replace( 'pa_', '', $attribute['name'] ),
							'position'  => (int) $attribute['position'],
							'visible'   => (bool) $attribute['is_visible'],
							'variation' => (bool) $attribute['is_variation'],
							'options'   => $this->get_attribute_options( $product->get_id(), $attribute ),
						);
					}
				}
				return $attributes;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in getting product attributes. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function get_attribute_options( $product_id, $attribute ) {
			try {
				if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
					return wc_get_product_terms( $product_id, $attribute['name'], array( 'fields' => 'names' ) );
				} elseif ( isset( $attribute['value'] ) ) {
					return array_map( 'trim', explode( '|', $attribute['value'] ) );
				}
				return array();
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in getting product attribute options. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function get_downloads( $product ) {
			try {
				$downloads = array();
				if ( $product->is_downloadable() ) {
					foreach ( $product->get_downloads() as $file_id => $file ) {
						$downloads[] = array(
							'id'   => $file_id,
							'name' => $file['name'],
							'file' => $file['file'],
						);
					}
				}
				return $downloads;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in getting product downloads. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function format_datetime( $timestamp, $convert_to_utc = false, $convert_to_gmt = false ) {
			try {
				if ( $convert_to_gmt ) {
					if ( is_numeric( $timestamp ) ) {
						$timestamp = date( 'Y-m-d H:i:s', $timestamp );
					}

					$timestamp = get_gmt_from_date( $timestamp );
				}

				if ( $convert_to_utc ) {
					$timezone = new DateTimeZone( wc_timezone_string() );
				} else {
					$timezone = new DateTimeZone( 'UTC' );
				}

				try {
					if ( is_numeric( $timestamp ) ) {
						$date = new DateTime( "@{$timestamp}" );
					} else {
						$date = new DateTime( $timestamp, $timezone );
					}
					if ( $convert_to_utc ) {
						$date->modify( -1 * $date->getOffset() . ' seconds' );
					}
				} catch ( Exception $e ) {
					$date = new DateTime( '@0' );
				}
				return $date->format( 'Y-m-d\TH:i:s' );
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in formatting date-time. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function get_images( $product ) {
			try {
				$images        = $attachment_ids = array();
				$product_image = $product->get_image_id();
				if ( ! empty( $product_image ) ) {
					$attachment_ids[] = $product_image;
				}
				$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );
				foreach ( $attachment_ids as $position => $attachment_id ) {
					$attachment_post = get_post( $attachment_id );
					if ( is_null( $attachment_post ) ) {
						continue;
					}
					$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
					if ( ! is_array( $attachment ) ) {
						continue;
					}
					$images[] = array(
						'id'         => (int) $attachment_id,
						'created_at' => $this->format_datetime( $attachment_post->post_date_gmt ),
						'updated_at' => $this->format_datetime( $attachment_post->post_modified_gmt ),
						'src'        => current( $attachment ),
						'title'      => get_the_title( $attachment_id ),
						'alt'        => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
						'position'   => (int) $position,
					);
				}
				if ( empty( $images ) ) {
					$images[] = array(
						'id'         => 0,
						'created_at' => $this->format_datetime( time() ),
						'updated_at' => $this->format_datetime( time() ),
						'src'        => wc_placeholder_img_src(),
						'title'      => __( 'Placeholder', 'woocommerce' ),
						'alt'        => __( 'Placeholder', 'woocommerce' ),
						'position'   => 0,
					);
				}
				return $images;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in getting product images. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		/**
		 * Function to set api version
		 *
		 * @param $api_url string
		 * @param $api_version string
		 * @return string
		 */
		public function set_api_version( $api_url = '', $api_version = '' ) {
			if ( ! strstr( $api_url, $api_version ) ) {
				$api_url = $api_url . '/' . $api_version . '/';
			}
			return $api_url;
		}

		function bloyal_get_api_url( $bloyal_service = '' ) {
			try {
				if ( isset( $this->is_custom_api_url_used ) && $this->is_custom_api_url_used == 'true' ) {
					$api_url = get_option( 'bloyal_custompayment_api_url' );
					if ( ! empty( $api_url ) ) {
						$api_url = $api_url;
					} else {
						$api_url = get_option( 'payment_api_url' );
					}
				} else {
					$api_url = get_option( 'payment_api_url' );
				}
				if ( ! isset( $api_url ) && $api_url == '' ) {
					$api_url = 'https://ws.bloyal.com';
				}
				switch ( $bloyal_service ) {
					case 'loyalty':
						$api_url  = $this->set_api_version( $api_url, 'ws30' );
						$api_url .= 'LoyaltyProcessing.svc?wsdl';
						break;
					case 'payment':
						$api_url  = $this->set_api_version( $api_url, 'ws30' );
						$api_url .= 'PaymentProcessing.svc?wsdl';
						break;
					case 'loyaltyengine':
						$api_url  = $this->set_api_version( $api_url, 'ws35' );
						$api_url .= 'LoyaltyEngine.svc?wsdl';
						break;
					case 'paymentengine':
						$api_url  = $this->set_api_version( $api_url, 'ws35' );
						$api_url .= 'PaymentEngine.svc?wsdl';
						break;
					case 'snippet':
						$api_url  = $this->set_api_version( $api_url, 'ws35' );
						$api_url .= 'LoyaltyEngine.svc?wsdl';
						break;
					case 'orderengine':
						$api_url  = $this->set_api_version( $api_url, 'ws35' );
						$api_url .= 'OrderEngine.svc?wsdl';
						break;
					default:
						$api_url  = $this->set_api_version( $api_url, 'ws30' );
						$api_url .= 'OrderProcessing.svc?wsdl';
						break;
				}
				bLoyalLoggerService::write_custom_log( "Soap Client URL \r\n " . $api_url . "\r\n ===================\r\n", 1 );
				$soap_obj = new SoapClient( $api_url, array( 'trace' => 1 ) );
				return $soap_obj;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in getting api url. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function bloyal_fetch_customer_uid( $external_id, $customer_email ) {
			try {
				$action = 'resolvedcustomers?EmailAddress=' . $customer_email . '&ExternalId=' . $external_id;
				$result = $this->send_curl_request( '', $action, 'loyaltyengine', 0 );
				bLoyalLoggerService::write_custom_log( "Resolve Customer Call \r\n" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
				return $result->data->Uid;
			} catch ( Exception $e ) {
				$this->log( __FUNCTION__, 'Error in fetching customer uid. Reason: ' . $e->getMessage() );
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				return $e->getMessage();
			}
		}

		function bloyal_refund_gift_card( $order_id, $amount ) {
			try {

				$gift_card_number = get_post_meta( $order_id, '_gift_card_number', true );
				$gift_card_amount = get_post_meta( $order_id, '_gift_card_amount', true );
				$transaction_id   = get_post_meta( $order_id, '_gift_card_transaction_id', true );
				if ( $transaction_id ) {
					$request_content = array(
						'deviceAccessKey' => $this->access_key,
						'storeCode'       => '',
						'deviceCode'      => '',
						'request'         => array(
							'Amount'                => $amount,
							'CardNumber'            => $gift_card_number,
							'TransactionCode'       => $transaction_id,
							'TransactionExternalId' => null,
						),
					);
					$api_url         = $this->bloyal_get_api_url( 'paymentengine' );
					$result          = $api_url->CardRefund( $request_content );
					if ( isset( $result->CardRefundResult->TransactionCode ) && $result->CardRefundResult->TransactionCode != '' ) {
						return $result->CardRefundResult->TransactionCode;
					}
					return false;
				}
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in refund gift card. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function bloyal_get_loyalty_dollar_balance() {
			try {
				$customer_uid = '';
				if ( is_user_logged_in() ) {
					$user         = wp_get_current_user();
					$customer_uid = $this->bloyal_fetch_customer_uid( $user->ID, $user->user_email );
				} else {
					$response = wp_json_encode(
						array(
							'status'    => 'error',
							'error_msg' => 'Please login to check your loyalty dollar balance.',
						)
					);
					return $response;
				}
				$loyalty_dollars_tender_code = get_option( 'bloyal_loyalty_dollars_tender_code' );
				$request_content             = array(
					'deviceAccessKey' => $this->access_key,
					'storeCode'       => '',
					'deviceCode'      => '',
					'request'         => array(
						'CardNumber'            => '',
						'CardPin'               => '',
						'CustomerUid'           => $customer_uid,
						'TenderCode'            => $loyalty_dollars_tender_code,
						'Swiped'                => false,
						'TransactionExternalId' => '',
						'TransactionToken'      => '',
					),
				);
				$api_url                     = $this->bloyal_get_api_url( 'paymentengine' );
				$result                      = $api_url->GetCardBalance( $request_content );
				$loyalty_balance             = $result->GetCardBalanceResult->CurrentBalance;
				$loyalty_balance             = floor( $loyalty_balance * 100 ) / 100;
				$cart_total                  = WC()->cart->total;
				if ( $loyalty_balance <= $cart_total ) {
					$max_amount = $loyalty_balance;
				} else {
					$max_amount = $cart_total;
				}
				if ( $loyalty_balance ) {
					$response = wp_json_encode(
						array(
							'status'          => 'success',
							'loyalty_balance' => 'Your loyalty dollar balance is $' . $loyalty_balance,
							'balance'         => $loyalty_balance,
							'max_amount'      => $max_amount,
						)
					);
				}
				if ( $loyalty_dollars_tender_code != '' && $loyalty_balance == 0 ) {
					$response = wp_json_encode(
						array(
							'status'    => 'error',
							'error_msg' => 'You do not have loyalty dollar balance.',
						)
					);
				}
				if ( $loyalty_dollars_tender_code == '' && $loyalty_balance == 0 ) {
					$response = wp_json_encode(
						array(
							'status'    => 'error',
							'error_msg' => 'Could not check at the moment, please try again later.',
						)
					);
				}
				return $response;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in fetch loyalty dollars balance. Reason: ' . $e->getMessage() );
				$response = wp_json_encode(
					array(
						'status'    => 'error',
						'error_msg' => 'Could not check at the moment, please try again later.',
					)
				);
				return $response;
			}
		}

		function bloyal_redeem_loyalty_dollar( $order_id, $amount ) {
			try {
				$use_order_engine            = get_option( 'bloyal_use_order_engine' );
				$loyalty_dollars_tender_code = get_option( 'bloyal_loyalty_dollars_tender_code' );
				$user                        = wp_get_current_user();
				if ( ! empty( $user ) ) {
					$customer_uid = $this->bloyal_fetch_customer_uid( $user->ID, $user->user_email );
				}
				$request_content = array(
					'deviceAccessKey' => $this->access_key,
					'storeCode'       => '',
					'deviceCode'      => '',
					'request'         => array(
						'Amount'                => ( $use_order_engine == 'true' ) ? -$amount : $amount,
						'CustomerUid'           => $customer_uid,
						'TenderCode'            => $loyalty_dollars_tender_code,
						'Swiped'                => false,
						'TransactionExternalId' => $order_id,
					),
				);
				$api_url         = $this->bloyal_get_api_url( 'paymentengine' );

				if ( $use_order_engine == 'true' ) {
					$result = $api_url->CardAuthorize( $request_content );
				} else {
					$result = $api_url->CardRedeem( $request_content );
				}
				if ( isset( $result->CardRedeemResult->TransactionCode ) && $result->CardRedeemResult->TransactionCode != '' ) {
					return $result->CardRedeemResult->TransactionCode;
				}
				return false;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in redeem loyalty dollar. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function bloyal_refund_loyalty_dollars( $order_id, $amount ) {
			try {
				$loyalty_dollars_amount = get_post_meta( $order_id, '_loyalty_dollars_amount', true );
				$transaction_id         = get_post_meta( $order_id, '_loyalty_dollars_transaction_id', true );

				if ( $transaction_id ) {
					$request_content = array(
						'deviceAccessKey' => $this->access_key,
						'storeCode'       => '',
						'deviceCode'      => '',
						'request'         => array(
							'Amount'          => $amount,
							'TransactionCode' => $transaction_id,
						),
					);
					$api_url         = $this->bloyal_get_api_url( 'paymentengine' );
					$result          = $api_url->CardRefund( $request_content );
					if ( isset( $result->CardRefundResult->TransactionCode ) && $result->CardRefundResult->TransactionCode != '' ) {
						return $result->CardRefundResult->TransactionCode;
					}
					return false;
				}
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in refund loyalty dollars. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

        function bloyal_refund_stored_payment( $order_id, $amount ) {
			try {
				
				$transaction_id         = get_post_meta( $order_id, '_stored_payment_transaction_id', true );
				if ( $transaction_id ) {
					$request_content = array(
						'deviceAccessKey' => $this->access_key,
						'storeCode'       => '',
						'deviceCode'      => '',
						'request'         => array(
							'Amount'          => $amount,
							'TransactionCode' => $transaction_id,
						),
					);
					$api_url         = $this->bloyal_get_api_url( 'paymentengine' );
					$result          = $api_url->CardRefund( $request_content );
					if ( isset( $result->CardRefundResult->TransactionCode ) && $result->CardRefundResult->TransactionCode != '' ) {
						return $result->CardRefundResult->TransactionCode;
					}
					return false;
				}
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in refund loyalty dollars. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}
		 
		function bloyal_get_on_account_balance() {
			try {
				$customer_uid = '';
				if ( is_user_logged_in() ) {
					$user         = wp_get_current_user();
					$customer_uid = $this->bloyal_fetch_customer_uid( $user->ID, $user->user_email );
				} else {
					$response = wp_json_encode(
						array(
							'status'    => 'error',
							'error_msg' => 'Please login to check your on account balance.',
						)
					);
					return $response;
				}
				$on_account_tender_code = get_option( 'bloyal_on_account_tender_code' );
				$request_content        = array(
					'deviceAccessKey' => $this->access_key,
					'storeCode'       => '',
					'deviceCode'      => '',
					'request'         => array(
						'CardNumber'            => '',
						'CardPin'               => '',
						'CustomerUid'           => $customer_uid,
						'TenderCode'            => $on_account_tender_code,
						'Swiped'                => false,
						'TransactionExternalId' => '',
						'TransactionToken'      => '',
					),
				);
				$api_url                = $this->bloyal_get_api_url( 'paymentengine' );
				$result                 = $api_url->GetCardBalance( $request_content );
				$on_account_balance     = $result->GetCardBalanceResult->CurrentBalance;
				$on_account_balance     = floor( $on_account_balance * 100 ) / 100;
				$cart_total             = WC()->cart->total;
				if ( $on_account_balance <= $cart_total ) {
					$max_amount = $on_account_balance;
				} else {
					$max_amount = $cart_total;
				}

				if ( $on_account_balance ) {
					$response = wp_json_encode(
						array(
							'status'          => 'success',
							'loyalty_balance' => 'Your on account balance is $' . $on_account_balance,
							'balance'         => $on_account_balance,
							'max_amount'      => $max_amount,
						)
					);
				}
				if ( $on_account_tender_code != '' && $on_account_balance == 0 ) {
					$response = wp_json_encode(
						array(
							'status'    => 'error',
							'error_msg' => 'You do not have on account balance.',
						)
					);
				}
				if ( $on_account_tender_code == '' && $on_account_balance == 0 ) {
					$response = wp_json_encode(
						array(
							'status'    => 'error',
							'error_msg' => 'Could not check at the moment, please try again later.',
						)
					);
				}
				return $response;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in fetch on account balance. Reason: ' . $e->getMessage() );
				$response = wp_json_encode(
					array(
						'status'    => 'error',
						'error_msg' => 'Could not check at the moment, please try again later.',
					)
				);
				return $response;
			}
		}

		function bloyal_redeem_on_account( $order_id, $amount ) {
			try {
				$access_key             = get_option( 'bloyal_access_key' );
				$on_account_tender_code = get_option( 'bloyal_on_account_tender_code' );
				$user                   = wp_get_current_user();
				if ( ! empty( $user ) ) {
					$customer_uid = $this->bloyal_fetch_customer_uid( $user->ID, $user->user_email );
				}
				$request_content = array(
					'deviceAccessKey' => $access_key,
					'storeCode'       => '',
					'deviceCode'      => '',
					'request'         => array(
						'Amount'                => $amount,
						'CustomerUid'           => $customer_uid,
						'TenderCode'            => $on_account_tender_code,
						'Swiped'                => false,
						'TransactionExternalId' => $order_id,
					),
				);
				$api_url         = $this->bloyal_get_api_url( 'paymentengine' );
				$result          = $api_url->CardRedeem( $request_content );
				if ( isset( $result->CardRedeemResult->TransactionCode ) && $result->CardRedeemResult->TransactionCode != '' ) {
					return $result->CardRedeemResult->TransactionCode;
				}
				return false;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in redeem on account. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function bloyal_refund_on_account_dollars( $order_id, $amount ) {
			try {
				$access_key        = get_option( 'bloyal_access_key' );
				$on_account_amount = get_post_meta( $order_id, '_on_account_amount', true );
				$transaction_id    = get_post_meta( $order_id, '_on_account_transaction_id', true );

				if ( $transaction_id ) {
					$request_content = array(
						'deviceAccessKey' => $access_key,
						'storeCode'       => '',
						'deviceCode'      => '',
						'request'         => array(
							'Amount'          => $amount,
							'TransactionCode' => $transaction_id,
						),
					);
					$api_url         = $this->bloyal_get_api_url( 'paymentengine' );
					$result          = $api_url->CardRefund( $request_content );
					if ( isset( $result->CardRefundResult->TransactionCode ) && $result->CardRefundResult->TransactionCode != '' ) {
						return $result->CardRefundResult->TransactionCode;
					}
					return false;
				}
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in refund on account dollars. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		function bloyal_after_cart_item_quantity_update() {
			WC()->session->set( 'bloyal_gift_card', null );
			WC()->session->set( 'bloyal_loyalty_dollar', null );
			WC()->session->set( 'bloyal_on_account', null );
		}

		function bloyal_cart_item_removed() {
			try {
				if ( WC()->cart->get_cart_contents_count() == 0 ) {
					$cart_uid = $this->get_uid();
					$action   = 'carts/coupons/?cartUid=' . $cart_uid;
					bLoyalLoggerService::write_custom_log( "Cart Items Removed URL \r\n " . wp_json_encode( $action ) . "\r\n ===================\r\n", 1 );
					$result = $this->send_curl_request( '', $action, 'loyaltyengine', 2 );
					bLoyalLoggerService::write_custom_log( "Cart Items Response \r\n " . wp_json_encode( $result ) . "\r\n ===================\r\n", 1 );
					WC()->session->set( 'bloyal_coupon', null );
					WC()->cart->remove_coupons();
				}
				WC()->session->set( 'bloyal_gift_card', null );
				WC()->session->set( 'bloyal_loyalty_dollar', null );
				WC()->session->set( 'bloyal_on_account', null );
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in removing bloyal coupons. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}

		}

		function bloyal_fetch_discount_summary() {			
			try {
				$bloyal_cart_data = WC()->session->get( 'bloyal_cart_data' );
				$currency_code    = get_option( 'woocommerce_currency' );
				$currency_symbol  = get_woocommerce_currency_symbol( $currency_code );
				$arrLineDiscount  = array();
				$temp_html        = '';
				foreach ( $bloyal_cart_data->Cart->Lines as $line ) {
					$reason_name            = $line->DiscountReasonName;
					$sale_price_reason_name = isset( $line->SalePriceReasonName ) == false ? $line->SalePriceReasonCode : $line->SalePriceReasonName;
					$discount               = $line->Discount * $line->Quantity;
					if ( $discount > 0  ) {
						if ( isset( $arrLineDiscount[ $line->ProductName ] ) ) {
							$arrLineDiscount[ $line->ProductName ]['discount']            = $arrLineDiscount[ $line->ProductName ]['discount'] + $discount;
						
						} else {
							$arrLineDiscount[ $line->ProductName ] = array(
								'product_name'        => $line->ProductName,
								'sale_reason_name'    => $sale_price_reason_name,
								'currency'            => $currency_symbol,
								'discount'            => $discount,
								'reason_name'         => $reason_name,
							);
						}
					}
				}
				$is_discounts_applied = false;
				$loyalty_accrued      = false;
				if ( isset( $bloyal_cart_data->Cart->Lines ) ) {
					$isLableDisplayed     = false;
					$arrDisplayedProducts = array();
					foreach ( $bloyal_cart_data->Cart->Lines as $line ) {
						if ( ! empty( $arrLineDiscount ) ) {
							if ( ! isset( $arrDisplayedProducts[ $line->ProductName ] ) && isset( $arrLineDiscount[ $line->ProductName ] ) ) {
								$is_product_level_discount = true;
								$is_discounts_applied      = true;
								if ( ! $isLableDisplayed ) {
									$temp_html       .= '<label style="font-size: 16px;"><b>' . __( 'Product Level Discount', 'woo' ) . '</b></label> <ul>';
									$isLableDisplayed = true;
								}
								$temp_html      .= '<li><label style="font-size: 14px;">Product Name: ' . $line->ProductName . '</label>';
								$temp_html      .= '<table>';
								$currency_symbol = $arrLineDiscount[ $line->ProductName ]['currency'];
								if ( $arrLineDiscount[ $line->ProductName ]['discount'] > 0 ) {
									$temp_html .= '<tr><td width="60%" style="padding: 5px; font-size: 13px;">' . $arrLineDiscount[ $line->ProductName ]['reason_name'] . '</td><td style="padding: 5px; font-size: 13px;">' . '-' . $currency_symbol . number_format( $arrLineDiscount[ $line->ProductName ]['discount'], 2, '.', '' ) . '</td></tr>';
								}
								if ( $arrLineDiscount[ $line->ProductName ]['sale_price_discount'] > 0 ) {
									$temp_html .= '<tr><td width="60%" style="padding: 5px; font-size: 13px;">' . $arrLineDiscount[ $line->ProductName ]['sale_reason_name'] . '</td><td style="padding: 5px; font-size: 13px;">' . '-' . $currency_symbol . number_format( $arrLineDiscount[ $line->ProductName ]['sale_price_discount'], 2, '.', '' ) . '</td></tr>';
								}
								$temp_html                                 .= '</table></li>';
								$arrDisplayedProducts[ $line->ProductName ] = true;
							}
						}
					}
					$temp_html .= '</ul>';
				}
				if ( isset( $bloyal_cart_data->Cart->ExternallyAppliedDiscount ) && ! ( $bloyal_cart_data->Cart->ExternallyAppliedDiscount == true ) ) {
					if ( $bloyal_cart_data->Cart->Discount > 0 ) {
						$is_discounts_applied = true;
						$temp_html           .= '<label style="font-size: 16px;"><b>' . __( 'Order Level Discount', 'woo' ) . '</b></label>';
						$temp_html           .= '<ul>';
						$temp_html           .= '<table>';
						if ( isset( $bloyal_cart_data->Cart->DiscountReasonName ) == false ) {
							$DiscountReasonName = $bloyal_cart_data->Cart->DiscountReasonCode;
						} else {
							$DiscountReasonName = $bloyal_cart_data->Cart->DiscountReasonName;
						}
						if ( ! isset( $bloyal_cart_data->Cart->DiscountReasonCode ) || trim( $bloyal_cart_data->Cart->DiscountReasonCode ) === '' ) {
							$temp_html .= '<tr><td width="60%" style="padding: 5px; font-size: 13px;">-</td><td style="padding: 5px; font-size: 13px;">' . '-' . $currency_symbol . number_format( $bloyal_cart_data->Cart->Discount, 2, '.', '' ) . '</td></tr>';
						} else {
							$temp_html .= '<tr><td width="60%" style="padding: 5px; font-size: 13px;">' . $DiscountReasonName . '</td><td style="padding: 5px; font-size: 13px;">' . '-' . $currency_symbol . number_format( $bloyal_cart_data->Cart->Discount, 2, '.', '' ) . '</td></tr>';
						}
						$temp_html .= '</table>';
						$temp_html .= '</ul>';
					}
				}
				$temp_html .= '';
				$temp_html .= '</ul>';
				if ( ! $is_discounts_applied && ! $loyalty_accrued && ! $bloyal_cart_data->Cart->Shipments[0]->Discount ) {
					$temp_html  = '';
					$temp_html .= '<ul>';
					$temp_html .= __( 'None applied to the cart', 'woo' );
					$temp_html .= '</ul>';
				}

				if ( isset( $bloyal_cart_data->Cart->Shipments ) ) {
					foreach ( $bloyal_cart_data->Cart->Shipments as $Shipments ) {
						foreach ( $Shipments->DiscountDetails as $key => $shipment ) {
							$temp_html .= '<label style="font-size: 16px;"><b>' . __( 'Shipping Level Discount', 'woo' ) . '</b></label>';
							$temp_html .= '<ul>';
							$temp_html .= '<table>';
							$temp_html .= '<tr><td width="60%" style="padding: 5px; font-size: 13px;">' . $shipment->Reason . '</td><td style="padding: 5px; font-size: 13px;">' . '-' . $currency_symbol . number_format( $shipment->Amount, 2, '.', '' ) . '</td></tr>';
							$temp_html .= '</table></li>';
							$temp_html .= '</ul>';
						}
					}
				}
				if ( $bloyal_cart_data->LoyaltySummary->LoyaltyPointsAccrued > 0 || $bloyal_cart_data->LoyaltySummary->LoyaltyCurrencyAccrued > 0 ) {
					$loyalty_accrued = true;
					$temp_html      .= '<label style="font-size: 16px;"><b>' . __( 'Loyalty Accrued', 'woo' ) . '</b></label>';
					$temp_html      .= '<ul>';
					$temp_html      .= '<table>';
					$temp_html      .= '<tr><th style="padding: 5px; font-size: 13px;">' . __( 'Loyalty Class Code', 'woo' ) . '</th><th style="padding: 5px; font-size: 13px;">' . __( 'Loyalty Amount', 'woo' ) . '</th></tr>';
					if ( $bloyal_cart_data->LoyaltySummary->LoyaltyPointsAccrued > 0 ) {
						$temp_html .= '<tr><td width="60%" style="padding: 5px; font-size: 13px;">' . 'Loyalty Points' . '</td><td style="padding: 5px; font-size: 13px;">' . $bloyal_cart_data->LoyaltySummary->LoyaltyPointsAccrued . '</td></tr>';
					}
					if ( $bloyal_cart_data->LoyaltySummary->LoyaltyCurrencyAccrued > 0 ) {
						$temp_html .= '<tr><td style="padding: 5px; font-size: 13px;">' . 'Loyalty Currency' . '</td><td style="padding: 5px; font-size: 13px;">' . $currency_symbol . $bloyal_cart_data->LoyaltySummary->LoyaltyCurrencyAccrued . '</td></tr>';
					}
					$temp_html .= '</table></ul>';
				}
				$session_bloyal_coupons = WC()->session->get( 'bloyal_coupon' );
				if ( ! empty( $session_bloyal_coupons ) ) {
					$temp_html               .= '<label style="font-size: 16px;"><b>' . __( 'Coupons Applied', 'woo' ) . '</b></label>';
					$is_bloyal_coupon_applied = false;
					$temp_html               .= '<ul>';
					foreach ( $session_bloyal_coupons as $applied_coupon ) {
						$coupon_code              = $applied_coupon['coupon_code'];
						$is_discounts_applied     = true;
						$is_bloyal_coupon_applied = true;
						$temp_html               .= '<li style="font-size: 14px;">';
						$temp_html               .= $coupon_code;
						$temp_html               .= '</li>';
					}
					if ( ! $is_bloyal_coupon_applied ) {
						$temp_html .= __( 'Coupons not applied to the cart.', 'woo' );
					}
					$temp_html .= '</ul>';
				}
				$response = wp_json_encode(
					array(
						'msg' => $temp_html,
					)
				);
				return $response;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				$this->log( __FUNCTION__, 'Error in fetching discount summary. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}
		
		/**
		 * Initialize log.
		 *
		 * @param  string $context as parameter.
		 * @param  string $message as parameter.
		 * @return void
		 */
		public function log( $context, $message ) {
			if ( empty( $this->log ) ) {
				$this->log = new WC_Logger();
			}

			$this->log->add( 'bloyal', $context . ' - ' . $message );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( $context . ' - ' . $message );
			}
		}
		
		/**
		 * Initialize verify_is_woocommerce_customer.
		 *
		 * @param  object $customer_obj as parameter.
		 * @return boolean
		 */
		public function verify_is_woocommerce_customer( $customer_obj ) {
			try {
				global $wpdb;
				$customer_external_id = $customer_obj->ExternalId;

				$id_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d", $customer_external_id ) );
				if ( $id_count == 1 ) {
					$this->bloyal_customer_auto_login( $customer_external_id );
					return true;
				} else {
					$email       = $customer_obj->EmailAddress;
					$email_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->users WHERE user_email = %s", $email ) );
					if ( $email_count == 1 ) {
						$user_by_email = get_user_by( 'email', $email );
						$this->bloyal_customer_auto_login( $user_by_email->ID );
						return true;
					} else {
						$customer_id = $this->create_woocommerce_customer( $customer_obj );
						$this->bloyal_customer_auto_login( $customer_id );
						return false;
					}
				}
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}
		
		/**
		 * Initialize bloyal_customer_auto_login.
		 *
		 * @param  int $user_id as parameter.
		 * @return void
		 */
		public function bloyal_customer_auto_login( $user_id ) {
			$user_info = get_userdata( $user_id );
			$username  = $user_info->user_login;

			if ( $user = get_user_by( 'login', $username ) ) {
				clean_user_cache( $user->ID );
				wp_clear_auth_cookie();
				wp_set_current_user( $user->ID );
				wp_set_auth_cookie( $user->ID, true, false );
				update_user_caches( $user );
			}
		}
		
		/**
		 * Initialize create_woocommerce_customer.
		 *
		 * @param  array $customer_obj as parameter.
		 * @return json_object
		 */
		public function create_woocommerce_customer( $customer_obj ) {
			try {
				$email    = $customer_obj->EmailAddress;
				$username = $customer_obj->EmailAddress;
				$password = $customer_obj->ExternalId;
				$user_id  = wc_create_new_customer( $email, $username, $password );
				update_user_meta( $user_id, 'billing_first_name', $customer_obj->FirstName );
				update_user_meta( $user_id, 'billing_last_name', $customer_obj->LastName );
				update_user_meta( $user_id, 'billing_company', $customer_obj->CompanyName );
				update_user_meta( $user_id, 'billing_address_1', $customer_obj->Address->Address1 );
				update_user_meta( $user_id, 'billing_address_2', $customer_obj->Address->Address2 );
				update_user_meta( $user_id, 'billing_city', $customer_obj->Address->City );
				update_user_meta( $user_id, 'billing_postcode', $customer_obj->Address->PostalCode );
				update_user_meta( $user_id, 'billing_country', $customer_obj->Address->Country );
				update_user_meta( $user_id, 'billing_state', $customer_obj->Address->StateName );
				update_user_meta( $user_id, 'billing_email', $customer_obj->EmailAddress );
				update_user_meta( $user_id, 'billing_phone', $customer_obj->MobilePhone );
				update_user_meta( $user_id, 'first_name', $customer_obj->FirstName );
				update_user_meta( $user_id, 'last_name', $customer_obj->LastName );
				return $user_id;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}
		
		/**
		 * Initialize bloyal_view_cart_in_woocommerce
		 *
		 * @param  array $request as parameter
		 * @return void
		 */
		public function bloyal_view_cart_in_woocommerce( $request ) {
			try {

				WC()->cart->empty_cart();
				$action = 'carts?CartUid=' . $request;
				WC()->session->set( 'third_party_cardId', $request );
				$result = $this->send_curl_request( '', $action, 'loyaltyengine', 0 );
				bLoyalLoggerService::write_custom_log( "View Cart Response \r\n" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
				$this->verify_is_woocommerce_customer( $result->data->Cart->Customer );
				if ( isset( $result->data->Cart->Lines ) ) {
					$bloyal_unexisted_woo_products = array();
					foreach ( $result->data->Cart->Lines as $line ) {
						$external_id = $line->ExternalId;
						if ( ! empty( $external_id ) ) {
							$product_post      = get_post( $external_id );
							$external_quantity = $line->Quantity;
							WC()->cart->add_to_cart( $external_id, $line->Quantity );
						} elseif ( ! empty( $line->ProductCode ) ) {
							$product_code      = $line->ProductCode;
							$external_id       = wc_get_product_id_by_sku( $product_code );
							$product_post      = get_post( $external_id );
							$external_quantity = $line->Quantity;
							WC()->cart->add_to_cart( $external_id, $line->Quantity );
						} else {
							$temp_unexisted_woo_products = array(
								'product_code' => $line->ProductCode,
								'product_name' => $line->ProductName,
							);
							array_push( $bloyal_unexisted_woo_products, $temp_unexisted_woo_products );
						}
					}
					WC()->session->set( 'bloyal_unexisted_woo_products', $bloyal_unexisted_woo_products );
				}
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}

		}

		/**
		 * This function is used to save bloyal click & collect configuration settings data.
		 *
		 * @param Array $configuration_setting as parameter.
		 * @return json_object
		 */
		public function bloyal_save_click_collect_configuration_data_wpdb() {
			try {
				update_option( 'bloyal_click_and_collect_status', isset( $_POST['post_click_collect_status'] ) ? sanitize_text_field( wp_unslash( $_POST['post_click_collect_status'] ) ) : '' );
				update_option( 'bloyal_click_collect_label', isset( $_POST['post_bloyal_click_collect_label'] ) ? sanitize_text_field( wp_unslash( $_POST['post_bloyal_click_collect_label'] ) ) : '' );
				update_option( 'click_collect_error', isset( $_POST['post_click_collect_error'] ) ? sanitize_text_field( wp_unslash( $_POST['post_click_collect_error'] ) ) : '' );

				return wp_json_encode(
					array(
						'save_success'      => true,
						'access_key_status' => get_option( 'bloyal_access_key_verification' ),
					)
				);
			} catch ( Exception $exception ) {
				bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}
	}
}
