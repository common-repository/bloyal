<?php
	require_once BLOYAL_DIR . '/app/view/bloyal_snippets_view.php';
	require_once BLOYAL_DIR . '/app/controller/bloyal_snippets_master_settings_controller.php';
	require_once BLOYAL_DIR . '/app/controller/bloyal_snippets_logger_controller.php';
	if ( ! class_exists( 'BloyalSnippetsController' ) ) {

		class BloyalSnippetsController {
			/**
			 * Function to fetch service urls by bloyal
			 *
			 * @return object
			 * @return API result
			 */
			function bloyal_snippets_get_service_urls( $obj ) {
				try {
					//this API use for get the all bLoyal service urls by bLoyal
					$post_url = 'https://domain.bloyal.com/api/v4/serviceurls/' . $obj->domain_name_snippets;
					if ( $obj->is_custom_web_snippet_url_used == 'custom' ) {
						if ( $obj->domain_url_snippets ) {
							//this API use for get the all bLoyal service urls by bLoyal
							$post_url = $obj->domain_url_snippets . '/api/v4/serviceurls/' . $obj->domain_name_snippets;
						}
					}

					$response = $this->send_curl_request( '', $post_url, 'serviceURL', 0 );
					bLoyalSnipetsLoggerService::write_custom_log( "Get Service URLs Response \n\r" . wp_json_encode( $response ) . "\r\n ======================\r\n", 1 );

					if ( ! empty( $response->data ) ) {
						if ( isset( $response->data->DirectorUrl ) ) {
							update_option( 'director_url', $response->data->DirectorUrl );
						}

						if ( isset( $response->data->POSSnippetsUrl ) ) {
							update_option( 'pos_snippets_url', $response->data->POSSnippetsUrl );
						}

						if ( isset( $response->data->WebSnippetsUrl ) ) {
							update_option( 'web_snippets_url', $response->data->WebSnippetsUrl );
						}

						if ( isset( $response->data->MyMobileLoyaltyUrl ) ) {
							update_option( 'my_mobile_loyalty_url', $response->data->MyMobileLoyaltyUrl );
						}

						if ( isset( $response->data->GridApiUrl ) ) {
							update_option( 'grid_api_url_snippet', $response->data->GridApiUrl );
						}

						if ( isset( $response->data->LoyaltyEngineApiUrl ) ) {
							update_option( 'loyalty_engine_api_url_snippet', $response->data->LoyaltyEngineApiUrl );
						}

						if ( isset( $response->data->EngagementEngineApiUrl ) ) {
							update_option( 'engagement_engine_api_url_snippet', $response->data->EngagementEngineApiUrl );
						}

						if ( isset( $response->data->OrderEngineApiUrl ) ) {
							update_option( 'order_engine_api_url_snippet', $response->data->OrderEngineApiUrl );
						}

						if ( isset( $response->data->ServiceProviderGatewayApiUrl ) ) {
							update_option( 'service_provider_gateway_api_url_snippet', $response->data->ServiceProviderGatewayApiUrl );
						}

						if ( isset( $response->data->WebSnippetsApiUrl ) ) {
							update_option( 'web_snippets_api_url_snippet', $response->data->WebSnippetsApiUrl );
						}

						if ( isset( $response->data->PaymentApiUrl ) ) {
							update_option( 'payment_api_url_snippet', $response->data->PaymentApiUrl );
						}

						if ( isset( $response->data->LoggingApiUrl ) ) {
							update_option( 'logging_api_url_snippet', $response->data->LoggingApiUrl );
						}
					}
					return wp_json_encode(
						array(
							'teststatus' => $response->data,
						)
					);

				} catch ( Exception $e ) {
					bLoyalSnipetsLoggerService::write_custom_log( $e->getMessage(), 3 );
					return $e->getMessage();
				}
			}

			public function bloyal_fetch_all_devices() {
				try {
					$strStoreUid = get_option( 'bLoyal_web_snippets_storeUid' );
					$arrDevices  = get_option( 'bLoyal_snippets_devices' );
					$action      = 'Connectors/ContextInfo';
					$result      = $this->send_curl_request( '', $action, 'grid', 0 );
					bLoyalSnipetsLoggerService::write_custom_log( "ContextInfo Response \n\r" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
					update_option( 'bLoyal_web_snippets_storeUid', $result->data->StoreUid );
					$action = '/Stores/' . $result->data->StoreUid . '/Devices';
					$result = $this->send_curl_request( '', $action, 'grid', 0 );
					bLoyalSnipetsLoggerService::write_custom_log( "Get All Devices Response \n\r" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
					if ( ! empty( $result->data ) ) {
						$count = sizeof( $result->data );
						for ( $i = 0; $i <= $count - 1; $i++ ) {
							$arrDevice[] = array(
								'Name' => $result->data[ $i ]->Name,
								'Code' => $result->data[ $i ]->Code,
								'Uid'  => $result->data[ $i ]->Uid,
							);
						}
					}
					update_option( 'bLoyal_snippets_devices', $arrDevice );
					return $arrDevice;
				} catch ( Exception $exception ) {
					bLoyalSnipetsLoggerService::write_custom_log( $exception->getMessage(), 3 );
					return $exception->getMessage();
				}
			}

			/**
			 * Function to send the curl request
			 *
			 * @param array   $request_content
			 * @param string  $action
			 * @param string  $api_type
			 * @param boolean $is_post
			 *
			 * @return api result
			 */

			function send_curl_request( $request_content, $action, $api_type, $is_post = 0 ) {
				try {
					$access_key_snippets   = get_option( 'bloyal_access_key' );
					$domain_name_snippets  = get_option( 'bloyal_domain_name' );
					$content               = wp_json_encode( $request_content );
					$web_snippet_api_radio = 'standard';
					//this API use for bLoyal websnippets_summaries by bLoyal
					if ( $api_type == 'websnippets_summaries' ) {
						$custom_loyalty_engine_api_url = get_option( 'bloyal_custom_loyaltyengine_api_url' );
						$loyalty_engine_api_url        = get_option( 'loyalty_engine_api_url' );
						$post_url                      = $loyalty_engine_api_url . '/api/v4/' . $domain_name_snippets . '/' . ( $action );
						if ( $web_snippet_api_radio == 'custom' ) {
							if ( $custom_loyalty_engine_api_url ) {
								$post_url = $custom_loyalty_engine_api_url . '/api/v4/' . $domain_name_snippets . '/' . ( $action );
							}
						}
					}
					//this API use for bLoyal loyaltyengine by bLoyal
					if ( $api_type == 'loyaltyengine' ) {
						$custom_loyalty_engine_api_url = get_option( 'bloyal_custom_loyaltyengine_api_url' );
						$loyalty_engine_api_url        = get_option( 'loyalty_engine_api_url' );
						$post_url                      = $loyalty_engine_api_url . '/api/v4/' . $access_key_snippets . '/' . ( $action );
						if ( $web_snippet_api_radio == 'custom' ) {
							if ( $custom_loyalty_engine_api_url ) {
								$post_url = $custom_loyalty_engine_api_url . '/api/v4/' . $access_key_snippets . '/' . ( $action );
							}
						}
					}
					//this API use for bLoyal loyaltyengine_snippet by bLoyal
					if ( $api_type == 'loyaltyengine_snippet' ) { // NEW SNIPPETS RETRIEVAL
						$custom_loyalty_engine_api_url = get_option( 'bloyal_custom_loyaltyengine_api_url' );
						$loyalty_engine_api_url        = get_option( 'loyalty_engine_api_url' );
						$post_url                      = $loyalty_engine_api_url . '/api/v4/' . $domain_name_snippets . '/' . ( $action );
						if ( $web_snippet_api_radio == 'custom' ) {
							if ( $custom_loyalty_engine_api_url ) {
								$post_url = $custom_loyalty_engine_api_url . '/api/v4/' . $domain_name_snippets . '/' . ( $action );
							}
						}
					}
					//this API use for bLoyal grid by bLoyal
					if ( $api_type == 'grid' ) {
						$custom_grid_api_url = get_option( 'bloyal_custom_grid_api_url' );
						$grid_api_url        = get_option( 'grid_api_url' );
						$post_url            = $grid_api_url . '/api/v4/' . $access_key_snippets . '/' . $action;
						if ( $web_snippet_api_radio == 'custom' ) {
							if ( $custom_grid_api_url ) {
								$post_url = $custom_grid_api_url . '/api/v4/' . $access_key_snippets . '/' . $action;
							}
						}
						if ( $action == 'Programs?accessKey=' ) {
							$post_url = $grid_api_url . '/api/' . $action . $access_key_snippets;
						}
					}
					//this API use for bLoyal websnippet by bLoyal
					if ( $api_type == 'websnippet' ) {
						$custom_web_snippet_api_url = get_option( 'web_snippets_api_url' );
						$web_snippet_api_url        = get_option( 'web_snippets_api_url' );
						$post_url                   = $web_snippet_api_url . '/api/v4/' . $domain_name_snippets . '/' . $action;
						if ( $web_snippet_api_radio == 'custom' ) {
							if ( $custom_web_snippet_api_url ) {
								$post_url = $custom_web_snippet_api_url . '/api/v4/' . $domain_name_snippets . '/' . $action;
							}
						}
					}
					//this API use for bLoyal websnippet_api by bLoyal
					if ( $api_type == 'websnippet_api' ) {
						$custom_web_snippet_api_url = get_option( 'web_snippets_api_url' );
						$web_snippet_api_url        = get_option( 'web_snippets_api_url' );
						$post_url                   = $web_snippet_api_url . '/api/v4/' . $access_key_snippets . '/' . $domain_name_snippets . '/' . $action;
						if ( $web_snippet_api_radio == 'custom' ) {
							if ( $custom_web_snippet_api_url ) {
								$post_url = $custom_web_snippet_api_url . '/api/v4/' . $access_key_snippets . '/' . $domain_name_snippets . '/' . $action;
							}
						}
					}
					//this API use for bLoyal websnippethtml by bLoyal
					if ( $api_type == 'websnippethtml' ) {
						$custom_web_snippet_api_url = get_option( 'web_snippets_api_url' );
						$web_snippet_api_url        = get_option( 'web_snippets_api_url' );
						$post_url                   = $web_snippet_api_url . '/api/v4/' . $domain_name_snippets . '/' . $action;
						if ( $web_snippet_api_radio == 'custom' ) {
							if ( $custom_web_snippet_api_url ) {
								$post_url = $custom_web_snippet_api_url . '/api/v4/' . $domain_name_snippets . '/' . $action;
							}
						}
					}
					if ( $api_type == 'serviceURL' ) {
						$post_url = $action;
					}
					if ( $post_url ) {
						bLoyalSnipetsLoggerService::write_custom_log( 'API URL' . $post_url, 1 );
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
								// 'body'    => $content,
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
							if ( $api_type == 'websnippethtml' ) {
								$obj_response = json_decode( $response );
								return $obj_response;
							} else {
								$obj_response = json_decode( $response );
								return $obj_response;
							}
						}
					}
				} catch ( Exception $e ) {
					bLoyalSnipetsLoggerService::write_custom_log( $e->getMessage(), 3 );
					return $e->getMessage();
				}
			}


			/**
			 * This function is used to fetch all snippets associated with device
			 *
			 * @param string $device_code which is device code
			 *
			 * @return json_object
			 */

			public function bloyal_fetch_snippets_wrt_device( $device_code ) {
				try {
					$access_key_snippets        = get_option( 'bloyal_access_key' );
					$snippets_list              = array();
					$arrSavedDeviceSnippetsList = array();
					// NEW SNIPPETS FETCH FUNCTIONALITY TO REPLACE ORIGINAL
					$new_snippets_action = $device_code . '/websnippetprofiles/summaries';
					$result              = $this->send_curl_request( '', $new_snippets_action, 'websnippets_summaries', 0 );
					bLoyalSnipetsLoggerService::write_custom_log( "Snippet Profiles Response \n\r" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
					if ( ! empty( $result ) && ! empty( $result->data ) ) {
						$result = $result->data;
						foreach ( $result as $key => $snippet ) {
							$ckb_code                     = 'ckb_' . $snippet->Code . '_' . $device_code;
							$use_original_css             = get_option( $ckb_code );
							$shortcode_name               = '[bloyal_' . $snippet->Code . '_' . $device_code . ']';
							$new_entry                    = $snippet->Type;
							if(!empty($snippet->Clubs)) {
								foreach($snippet->Clubs as $club) {
									$shortcode_name = '[bloyal_' . $snippet->Code . '_' . $device_code .'_'.$club->Code. ']';

									$snippets_list[]              = array(
										'snippet_code'      => $snippet->Code,
										'snippet_title'     => $snippet->Name,
										'snippet_shortcode' => $shortcode_name,
										'ckb_code'          => 'ckb_' . $snippet->Code . '_' . $device_code.'_'.$club->Code,
										'snippet_type'      => $snippet->Type,
										'use_original_css'  => $use_original_css,
									);
									$arrSavedDeviceSnippetsList[] = array(
										'snippet_shortcode' => 'bloyal_' . $snippet->Code . '_' . $device_code.'_'.$club->Code,
										'snippet_type'      => $snippet->Type,
									);
								}
							}else {
								$snippets_list[]              = array(
									'snippet_code'      => $snippet->Code,
									'snippet_title'     => $snippet->Name,
									'snippet_shortcode' => $shortcode_name,
									'ckb_code'          => 'ckb_' . $snippet->Code . '_' . $device_code,
									'snippet_type'      => $snippet->Type,
									'use_original_css'  => $use_original_css,
								);
								$arrSavedDeviceSnippetsList[] = array(
									'snippet_shortcode' => 'bloyal_' . $snippet->Code . '_' . $device_code,
									'snippet_type'      => $snippet->Type,
								);
							}
						}
					}
					update_option( 'bloyal_saved_device_snippets_lists', $arrSavedDeviceSnippetsList );
					bLoyalSnipetsLoggerService::write_custom_log( "Snippet Profiles Response \n\r" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
					if ( $result->status == 'error' ) {
						return wp_json_encode(
							array(
								'status'        => 'error',
								'snippets_list' => $result->message,
							)
						);
					}

					return wp_json_encode(
						array(
							'snippets_list' => $snippets_list,
							'access_key' => $access_key_snippets,  
						)
					);
				} catch ( Exception $exception ) {
					bLoyalSnipetsLoggerService::write_custom_log( $exception->getMessage(), 3 );
					return $exception->getMessage();
				}
			}

			/**
			 * This function is used to fetch snippet html
			 *
			 * @param string $snipppet_code which is snippet code
			 * @param string $device_code which is device code
			 * @param string $snippet_type which is type of snippet
			 * @param string $club_code which is club code
			 *
			 * @return json_object
			 */

			public function bloyal_fetch_snippet_html( $atts, $snipppet_code, $device_code, $snippet_type, $club_code ) {
				try {
					$snippets_api_url = get_option( 'web_snippets_api_url' );
					$snippets_src     = str_replace( 'Web', '', $snippets_api_url ) . '/bLoyalSnippetLoader.js';
					//wp_enqueue_script( 'bLoyalSnippetLoader',  $snippets_src, array(), '1.0.0', false);
					global $wpdb;
					$db_table_name          = $wpdb->prefix . 'options';
					$used_login             = 'true';
					$page_id                = get_option( 'woocommerce_myaccount_page_id' );
					$loyalty_engine_api_url = get_option( 'loyalty_engine_api_url' );
					$access_key_snippets    = get_option( 'bloyal_access_key' );
					$domain_name_snippets   = get_option( 'bloyal_domain_name' );
					$is_custom_api_url      = 'standard';
					if ( $used_login == 'true' ) {
						$login_page   = get_permalink( $page_id );
						$redirect_url = explode( '//', $login_page );
						$redirect_url = $redirect_url['0'];
						$current_url  =  sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ).  sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
						$user_details              = $this->get_new_customer_details_from_wbdb();
						$action                    = 'resolvedcustomersession?EmailAddress=' .urlencode( $user_details['Customer']['EmailAddress'] ) . '&ExternalId=' . $user_details['Customer']['ExternalId'];
						$response_resolve_customer = $this->send_curl_request( '', $action, 'loyaltyengine', 0 );
						bLoyalSnipetsLoggerService::write_custom_log( "Resolve Customer Response \n\r" . wp_json_encode( $response_resolve_customer ) . "\r\n ======================\r\n", 1 );
						if ( "success" === $response_resolve_customer->status ) {
							$_SESSION['bloyal_session_key'] = $response_resolve_customer->data->SessionKey;
							if ( $user_details['Customer']['FirstName'] == '' || $user_details['Customer']['FirstName'] == null && $response_resolve_customer->data->Customer->FirstName != '' && $response_resolve_customer->data->Customer->FirstName != null ) {
								update_user_meta( $user_details['Customer']['ExternalId'], 'first_name', $response_resolve_customer->data->Customer->FirstName );
								update_user_meta( $user_details['Customer']['ExternalId'], 'last_name', $response_resolve_customer->data->Customer->LastName );
							}
							if ( $user_details['Customer']['Address1'] == '' || $user_details['Customer']['Address1'] == null && $response_resolve_customer->data->Customer->Address->Address1 != '' && $response_resolve_customer->data->Customer->Address->Address1 != null ) {
								update_user_meta( $user_details['Customer']['ExternalId'], 'billing_address_1', $response_resolve_customer->data->Customer->Address->Address1 );
								update_user_meta( $user_details['Customer']['ExternalId'], 'billing_address_2', $response_resolve_customer->data->Customer->Address->Address2 );
								update_user_meta( $user_details['Customer']['ExternalId'], 'billing_city', $response_resolve_customer->data->Customer->Address->City );
								update_user_meta( $user_details['Customer']['ExternalId'], 'billing_state', $response_resolve_customer->data->Customer->Address->State );
								update_user_meta( $user_details['Customer']['ExternalId'], 'billing_postcode', $response_resolve_customer->data->Customer->Address->PostalCode );
								update_user_meta( $user_details['Customer']['ExternalId'], 'billing_country', $response_resolve_customer->data->Customer->Address->Country );
							}
				
							if ( empty( $response_resolve_customer->data->Customer->ExternalId ) ) {
								$update_customer_request = $this->get_customer_update_request( $response_resolve_customer->data->Customer );
								$action                  = $device_code . '/Customers?sessionKey=' . $response_resolve_customer->data->SessionKey;
								bLoyalSnipetsLoggerService::write_custom_log( "Update Customer Request \n\r" . wp_json_encode( $update_customer_request ) . "\r\n ======================\r\n", 1 );
								$response_update_customer = $this->send_curl_request( $update_customer_request, $action, 'websnippet', 1 );
								bLoyalSnipetsLoggerService::write_custom_log( "Update Customer Response \n\r" . wp_json_encode( $response_update_customer ) . "\r\n ======================\r\n", 1 );
							}
							
							
						} else {
							
							if ( ( $user_details['Customer']['FirstName'] != 'false' || $user_details['Customer']['LastName'] != 'false' ) && $user_details['Customer']['EmailAddress'] != '' ) {
								$action = '/customers/commands/saves';
								bLoyalSnipetsLoggerService::write_custom_log( "Customer Signups Request \n\r" . wp_json_encode( $user_details ) . "\r\n ======================\r\n", 1 );
								$response = $this->send_curl_request( $user_details, $action, 'loyaltyengine', 1 );
								bLoyalSnipetsLoggerService::write_custom_log( "Customer Signups Response \n\r" . wp_json_encode( $response ) . "\r\n ======================\r\n", 1 );
								
								$action                    = 'resolvedcustomersession?EmailAddress=' .urlencode( $user_details['Customer']['EmailAddress'] ) . '&ExternalId=' . $user_details['Customer']['ExternalId'];
								$response_resolve_customer = $this->send_curl_request( '', $action, 'loyaltyengine', 0 );
								bLoyalSnipetsLoggerService::write_custom_log( "Resolve Customer Response \n\r" . wp_json_encode( $response_resolve_customer ) . "\r\n ======================\r\n", 1 );
								$_SESSION['bloyal_session_key'] = $response_resolve_customer->data->SessionKey;
							}
							if ( $response ) {
								if ( $response->status == 'error' ) {	
									echo esc_attr($response->message);
								}
								if ( $response_resolve_customer->status == 'error' ) {
									$_SESSION['bloyal_session_key'] =  null;
									echo esc_attr($response_resolve_customer->message);
								}
							}
						}
					}
					$query_string_variables =  sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
					if ( ! empty( $query_string_variables ) ) {
						$customer_session_key = sanitize_text_field( wp_unslash( $_GET['customerSessionKey'] ) );
					} else {
						$query_string_variables = '';
					}
					$domain_name_snippets = get_option( 'bloyal_domain_name' );
					$login_page   = get_permalink( $page_id );
					$bloyal_snippet_args = array();
					if(!empty($club_code)) {
							$bloyal_snippet_args['ClubCode']   = strtoupper($club_code);
					}
					$bloyal_snippet_args['DeviceCode'] = $device_code;
					$bloyal_snippet_args['LoginUrl']   = $login_page;
					$bloyal_snippet_args['PaymentRedirectToHome']   = true;
					$this->react_web_snippets_callback( $snipppet_code, $bloyal_snippet_args);
					$current_url = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
					if (!empty($_SESSION['bloyal_session_key'])) {
						if (! strpos($current_url, 'bL_sk') && is_user_logged_in()) {
							$bloyal_session_key = isset( $_SESSION['bloyal_session_key'] ) ? sanitize_text_field( $_SESSION['bloyal_session_key'] ) : '';
							echo "<script>window.location.search += '&bL_sk=$bloyal_session_key';</script>";
						}
					}
					$this->update_wordpress_user_details();
				} catch ( Exception $exception ) {
					bLoyalSnipetsLoggerService::write_custom_log( $exception->getMessage(), 3 );
					return $exception->getMessage();
				}
			}

			/**
			 * This function is used to save snippets use custom css checkbox status
			 *
			 * @param string $checked_snippets which is selected snippets
			 * @param string $unchecked_snippets which is unselected snippets
			 *
			 * @return json_object
			 */

			public function bloyal_save_snippets_uss_css_status( $checked_snippets, $unchecked_snippets ) {

				try {

					if ( ! empty( $checked_snippets ) ) {

						foreach ( $checked_snippets as $snippet ) {
							update_option( $snippet, 1 );
						}
					}
					if ( ! empty( $unchecked_snippets ) ) {
						foreach ( $unchecked_snippets as $snippet ) {
							update_option( $snippet, 0 );
						}
					}
					return wp_json_encode(
						array(
							'save_success' => true,
						)
					);
				} catch ( Exception $exception ) {
					bLoyalSnipetsLoggerService::write_custom_log( $exception->getMessage(), 3 );
					return $exception->getMessage();
				}
			}

			/**
			 * This function is used to get the customer details from database
			 *
			 * @return object
			 */

			public function get_new_customer_details_from_wbdb() {
				$current_user           = wp_get_current_user();
				$phone                  = get_user_meta( $current_user->data->ID, 'billing_phone', true );
				$city                   = get_user_meta( $current_user->data->ID, 'billing_city', true );
				$state                  = get_user_meta( $current_user->data->ID, 'billing_state', true );
				$country                = get_user_meta( $current_user->data->ID, 'billing_country', true );
				$postcode               = get_user_meta( $current_user->data->ID, 'billing_postcode', true );
				$billing_address_1      = get_user_meta( $current_user->data->ID, 'billing_address_1', true );
				$billing_address_2      = get_user_meta( $current_user->data->ID, 'billing_address_2', true );
				$loyaltyCardNumbers     = array();
				$ProgramMembershipUids  = array();
				$groupMembershipUids    = array();
				$clubMemberships        = array(
					'ClubUid'        => null,
					'Status'         => null,
					'ExpirationDate' => null,
				);
				$loyaltyAccountUids     = array();
				$externalIds            = array(
					'SystemUid'  => null,
					'ExternalId' => null,
					'Verified'   => null,
				);
				$value                  = array();
				$customFields           = array(
					'Code'      => null,
					'FieldType' => null,
					'Value'     => $value,
				);
				$subscriberGroupsToJoin = array();
				$customer               = array(

					'CustomerBirthDate'         => null,
					'PartitionId'               => null,
					'PartitionCode'             => null,
					'FirstName'                 => $current_user->user_firstname,
					'LastName'                  => $current_user->user_lastname,
					'CompanyName'               => null,
					'Address1'                  => $billing_address_1,
					'Address2'                  => $billing_address_2,
					'City'                      => $city,
					'State'                     => $state,
					'PostalCode'                => $postcode,
					'Country'                   => $country,
					'EmailAddress'              => $current_user->data->user_email,
					'EmailAddress2'             => null,
					'Phone1'                    => $phone,
					'Phone2'                    => null,
					'MobilePhone'               => null,
					'FaxNumber'                 => null,
					'FirstName2'                => null,
					'LastName2'                 => null,
					'SignupChannelUid'          => null,
					'SignupStoreUid'            => null,
					'SignupStoreCode'           => null,
					'SignupStoreExternalId'     => null,
					'PriceLevelUid'             => null,
					'PriceLevelCode'            => null,
					'FacebookId'                => null,
					'MobileDeviceId'            => null,
					'TwitterId'                 => null,
					'LoyaltyRedemptionDisabled' => false,
					'LoyaltyAccrualDisabled'    => false,
					'CustomerTypeUid'           => null,
					'CustomerTypeCode'          => null,
					'NoEmail'                   => false,
					'NoTextMessages'            => false,
					'Verified'                  => false,
					'TaxExempt'                 => false,
					'AllowEditAtPOS'            => true,
					'WebAccount'                => true,
					'StateCode'                 => $state,
					'StateName'                 => $state,
					'CountryCode'               => $country,
					'CountryName'               => $country,
					'TransactionCount'          => 0,
					'TotalSales'                => 0,
					'CurrentBalance'            => 0,
					'CreditLimit'               => 0,
					'GuestCustomer'             => false,
					'SourceSystemUid'           => null,
					'BirthDate'                 => null,
					'CustomCode1'               => null,
					'CustomCode2'               => null,
					'CustomCode3'               => null,
					'CustomCode4'               => null,
					'CustomCode5'               => null,
					'CustomCode6'               => null,
					'CustomCode7'               => null,
					'CustomCode8'               => null,
					'LoyaltyCardNumbers'        => $loyaltyCardNumbers,
					'ProgramMembershipUids'     => $ProgramMembershipUids,
					'GroupMembershipUids'       => $groupMembershipUids,
					'ClubMemberships'           => $clubMemberships,
					'LoyaltyAccountUids'        => $loyaltyAccountUids,
					'ExternalId'                => $current_user->data->ID,
				);
				$user_details           = array( 'Customer' => $customer );
				return $user_details;
			}

			/**
			 * This function is used to generate the customer session
			 *
			 * @param object $request which is customer data
			 * @param string $device_code which is selected device code
			 * @return object customer session data
			 */

			public function get_customer_session( $request, $device_code ) {
				$action        = $device_code . '/CustomerSessions';
				$customer_data = array(
					'Uid'               => $request->data->Uid,
					'ExternalId'        => $request->data->ExternalId,
					'Code'              => $request->data->Code,
					'LoyaltyCardNumber' => $request->data->LoyaltyCardNumber,
					'EmailAddress'      => $request->data->EmailAddress,
					'MobilePhone'       => $request->data->MobilePhone,
					'RegisterdCard'     => null,
					'MobileDeviceId'    => $request->data->MobileDeviceId,
				);
				bLoyalSnipetsLoggerService::write_custom_log( "Customer Sessions Request \n\r" . wp_json_encode( $customer_data ) . "\r\n ======================\r\n", 1 );
				$response = $this->send_curl_request( $customer_data, $action, 'websnippet_api', 1 );
				bLoyalSnipetsLoggerService::write_custom_log( "Customer Sessions Response \n\r" . wp_json_encode( $response ) . "\r\n ======================\r\n", 1 );
				return $response;
			}

			/**
			 * This function is used to generate the customer update request
			*
			* @param object $request which is customer data
			* @return object customer updated data
			*/

			public function get_customer_update_request( $request ) {
				$current_user         = $this->get_new_customer_details_from_wbdb();
				$current_user_details = wp_get_current_user();
				$FirstName            = $request->FirstName;
				$LastName             = $request->LastName;
				$Phone1               = $request->Phone1;
				$Address1             = $request->Address->Address1;
				$Address2             = $request->Address->Address2;
				$City                 = $request->Address->City;
				$State                = $request->Address->State;
				$PostalCode           = $request->Address->PostalCode;
				$Country              = $request->Address->Country;
				if ( ( $request->FirstName == '' || $request->FirstName == null ) && ( $current_user['Customer']['FirstName'] != '' && $current_user['Customer']['FirstName'] != null ) ) {
					$FirstName = $current_user['Customer']['FirstName'];
				}
				if ( ( $request->LastName == '' || $request->LastName == null ) && ( $current_user['Customer']['LastName'] != '' && $current_user['Customer']['LastName'] != null ) ) {
					$LastName = $current_user['Customer']['FirstName'];
				}
				if ( ( $request->Phone1 == '' || $request->Phone1 == null ) && ( get_user_meta( $current_user_details->data->ID, 'billing_phone', true ) != '' && get_user_meta( $current_user_details->data->ID, 'billing_phone', true ) != null ) ) {
					$Phone1 = get_user_meta( $current_user->data->ID, 'billing_phone', true );
				}
				if ( ( $request->Address->Address1 == '' || $request->Address->Address1 == null ) && ( get_user_meta( $current_user->data->ID, 'billing_address_1', true ) != '' && get_user_meta( $current_user->data->ID, 'billing_address_1', true ) != null ) ) {
					$Address1   = get_user_meta( $current_user_details->data->ID, 'billing_address_1', true );
					$Address2   = get_user_meta( $current_user_details->data->ID, 'billing_address_2', true );
					$City       = get_user_meta( $current_user_details->data->ID, 'billing_city', true );
					$State      = get_user_meta( $current_user_details->data->ID, 'billing_state', true );
					$PostalCode = get_user_meta( $current_user_details->data->ID, 'billing_postcode', true );
					$Country    = get_user_meta( $current_user_details->data->ID, 'billing_country', true );
				}
				$request_update_customer_data = array(
					'CustomerBirthDate'         => null,
					'LoyaltyCardNumber'         => null,
					'PartitionId'               => null,
					'PartitionCode'             => null,
					'FirstName'                 => $FirstName,
					'LastName'                  => $LastName,
					'CompanyName'               => null,
					'Address1'                  => $Address1,
					'Address2'                  => $Address2,
					'City'                      => $City,
					'State'                     => $State,
					'PostalCode'                => $PostalCode,
					'Country'                   => $Country,
					'EmailAddress'              => null,
					'EmailAddress2'             => null,
					'Phone1'                    => null,
					'Phone2'                    => null,
					'MobilePhone'               => null,
					'FaxNumber'                 => null,
					'FirstName2'                => null,
					'LastName2'                 => null,
					'SignupChannelUid'          => null,
					'SignupStoreUid'            => null,
					'SignupStoreCode'           => null,
					'SignupStoreExternalId'     => null,
					'PriceLevelUid'             => null,
					'PriceLevelCode'            => null,
					'FacebookId'                => null,
					'MobileDeviceId'            => null,
					'TwitterId'                 => null,
					'LoyaltyRedemptionDisabled' => null,
					'LoyaltyAccrualDisabled'    => null,
					'CustomerTypeUid'           => null,
					'CustomerTypeCode'          => null,
					'NoEmail'                   => null,
					'NoTextMessages'            => null,
					'Verified'                  => null,
					'TaxExempt'                 => null,
					'AllowEditAtPOS'            => null,
					'WebAccount'                => null,
					'StateCode'                 => null,
					'StateName'                 => null,
					'CountryCode'               => null,
					'CountryName'               => null,
					'TransactionCount'          => null,
					'TotalSales'                => null,
					'LastPurchase'              => null,
					'CurrentBalance'            => null,
					'CreditLimit'               => null,
					'GuestCustomer'             => null,
					'SourceSystemUid'           => null,
					'Salutation'                => null,
					'BirthDate'                 => null,
					'CustomCode1'               => null,
					'CustomCode2'               => null,
					'CustomCode3'               => null,
					'CustomCode4'               => null,
					'CustomCode5'               => null,
					'CustomCode6'               => null,
					'CustomCode7'               => null,
					'CustomCode8'               => null,
					'LoyaltyCardNumbers'        => array( null ),
					'ProgramMembershipUids'     => array( null ),
					'GroupMembershipUids'       => array( null ),
					'ClubMemberships'           => array(
						'ClubUid'        => null,
						'Status'         => null,
						'ExpirationDate' => null,
					),
					'LoyaltyAccountUids'        => array( null ),
					'ExternalIds'               => array(

						'SystemUid'  => null,
						'ExternalId' => null,
						'Verified'   => null,
					),
					'Code'                      => null,
					'Id'                        => null,
					'Uid'                       => null,
					'ExternalId'                => $current_user_details->data->ID,
					'Created'                   => null,
					'CreatedLocal'              => null,
					'Updated'                   => null,
					'UpdatedLocal'              => null,
					'Revision'                  => null,
				);
				return $request_update_customer_data;
			}

			function update_wordpress_user_details() {
				$user_details              = $this->get_new_customer_details_from_wbdb();
				$action                    = 'resolvedcustomersession?EmailAddress=' .urlencode( $user_details['Customer']['EmailAddress'] ) . '&ExternalId=' . $user_details['Customer']['ExternalId'];
				$response_resolve_customer = $this->send_curl_request( '', $action, 'loyaltyengine', 0 );
				bLoyalSnipetsLoggerService::write_custom_log( "Resolve Customer Response \n\r" . wp_json_encode( $response_resolve_customer ) . "\r\n ======================\r\n", 1 );
				if ( ! empty( $response_resolve_customer->data->Customer->Uid ) ) {
					if ( $user_details['Customer']['FirstName'] == '' || $user_details['Customer']['FirstName'] == null && $response_resolve_customer->data->Customer->FirstName != '' && $response_resolve_customer->data->Customer->FirstName != null ) {
						update_user_meta( $user_details['Customer']['ExternalId'], 'first_name', $response_resolve_customer->data->Customer->FirstName );
						update_user_meta( $user_details['Customer']['ExternalId'], 'last_name', $response_resolve_customer->data->Customer->LastName );
					}
					if ( $user_details['Customer']['Address1'] == '' || $user_details['Customer']['Address1'] == null && $response_resolve_customer->data->Customer->Address->Address1 != '' && $response_resolve_customer->data->Customer->Address->Address1 != null ) {
						update_user_meta( $user_details['Customer']['ExternalId'], 'billing_address_1', $response_resolve_customer->data->Customer->Address->Address1 );
						update_user_meta( $user_details['Customer']['ExternalId'], 'billing_address_2', $response_resolve_customer->data->Customer->Address->Address2 );
						update_user_meta( $user_details['Customer']['ExternalId'], 'billing_city', $response_resolve_customer->data->Customer->Address->City );
						update_user_meta( $user_details['Customer']['ExternalId'], 'billing_state', $response_resolve_customer->data->Customer->Address->State );
						update_user_meta( $user_details['Customer']['ExternalId'], 'billing_postcode', $response_resolve_customer->data->Customer->Address->PostalCode );
						update_user_meta( $user_details['Customer']['ExternalId'], 'billing_country', $response_resolve_customer->data->Customer->Address->Country );
					}
				}
			}
			
			function react_web_snippets_callback( $snipppet_code, $bloyal_snippet_args) {
				$snippet_domain   = get_option( 'bloyal_domain_name' );
				echo "<div data-bloyal-snippet-code='".esc_attr(strtolower($snipppet_code))."' data-bloyal-login-domain='".esc_attr($snippet_domain)."' data-bloyal-snippet-args='".wp_json_encode($bloyal_snippet_args, TRUE)."' id='root'></div>";
			}
		}
	}
