<?php
require_once BLOYAL_SNIPPETS_DIR . '/app/view/bloyal_snippets_view.php';
require_once BLOYAL_SNIPPETS_DIR . '/app/controller/bloyal_snippets_controller.php';
require_once BLOYAL_SNIPPETS_DIR . '/app/controller/bloyal_snippets_logger_controller.php';

if ( ! class_exists( 'BloyalSnippetsSettingsController' ) ) {


	class BloyalSnippetsSettingsController {

		/**
		 * This function is used to get configuration details which are saved in wpdb.
		 *
		 * @return json_object
		 */
		public function bloyal_snippets_get_configuration_details_from_wpdb() {
			try {
				$bloyal_snippets_obj               = new BloyalSnippetsController();
				$device_context                    = $bloyal_snippets_obj->send_curl_request( '', 'contextdevices', 'loyaltyengine', 0 );
				$domain_name_snippets              = get_option( 'bloyal_domain_name' );
				$api_key_snippets                  = get_option( 'bloyal_api_key' );
				$domain_url_snippets               = '';
				$access_key_snippets               = get_option( 'bloyal_access_key' );
				$web_snippet_api_radio             = 'standard';
				$custom_web_snippet_api_url        = false;
				$default_device                    = $device_context->data->Code;
				$use_wordpress_login               = 'true';
				$use_bloyal_login                  = 'false';
				$snippetscustomgridapiurl          = '';
				$snippetscustomloyaltyengineapiurl = '';
				$snippetcustomwebsnippetapiurl     = '';   
				$snippetcustomwebsnippethtmlapiurl = '';
				$page_id                           = get_option( 'page_id' );
				$devices                           = array( array() );

				return json_encode(
					array(
						'domain_name_snippets'             => $domain_name_snippets,
						'api_key_snippets'                 => $api_key_snippets,
						'domain_url_snippets'              => $domain_url_snippets,
						'access_key_snippets'              => $access_key_snippets,
						'web_snippet_api_radio'            => $web_snippet_api_radio,
						'custom_web_snippet_api_url'       => $custom_web_snippet_api_url,
						'devices'                          => $devices,
						'default_device'                   => $default_device,
						'use_wordpress_login'              => $use_wordpress_login,
						'use_bloyal_login'                 => $use_bloyal_login,
						'snippets_custom_grid_apiurl'      => $snippetscustomgridapiurl,
						'snippets_custom_loyaltyengine_apiurl' => $snippetscustomloyaltyengineapiurl,
						'snippet_custom_websnippet_apiurl' => $snippetcustomwebsnippetapiurl,  
						'snippet_custom_websnippethtml_apiurl' => $snippetcustomwebsnippethtmlapiurl,
						'page_id'                          => $page_id,
					)
				);
			} catch ( Exception $exception ) {
				bLoyalSnipetsLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}

		/**
		 * This function is used to get access key verification details which are saved in wpdb.
		 *
		 * @return json_object
		 */

		public function bloyal_snippets_get_accesskeyverification_details_from_wpdb() {

			try {

				$access_key_verification_snippets = get_option( 'bloyal_access_key_verification_snippets' );

				return json_encode(
					array(
						'access_key_verification_snippets' => $access_key_verification_snippets,
					)
				);
			} catch ( Exception $exception ) {
				return $exception->getMessage();
			}
		}

		/**
		 * This function is used to get access key.
		 *
		 * @param stdClass $obj
		 *
		 * @return json_object
		 */

		public function bloyal_snippets_get_access_key_curl( $obj ) {
			try {
				$bloyal_snippets_obj = new BloyalSnippetsController();
				$str_content         = '{"LoginDomain":"' . $obj->domain_name_snippets . '","ApiKey":"' . $obj->api_key_snippets . '","ConnectorKey":"57696A0B-D357-4E80-B30B-7DA9DE5C6F67"}';
				$bloyal_snippets_obj->bloyal_snippets_get_service_urls( $obj );
				$grid_api_url = get_option( 'grid_api_url_snippet' );
				if ( $obj->is_custom_web_snippet_url_used == 'custom' ) {
					if ( ! empty( $obj->snippets_custom_gridapi_url ) ) {
						$grid_api_url = $obj->snippets_custom_gridapi_url;
					} else {
						$bloyal_snippets_obj->bloyal_snippets_get_service_urls( $obj );
						$grid_api_url = get_option( 'grid_api_url_snippet' );
					}
				}
				//this API use bLoyal grid KeyDispenser, get the bLoyal access key by bLoyal
				$action = $grid_api_url . '/api/v4/KeyDispenser';
				bLoyalSnipetsLoggerService::write_custom_log( "Get Access Key Request URL \n\r" . $action . "\r\n ======================\r\n", 1 );
				$args = array(
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => $str_content,
					'method'  => 'POST',
					'timeout' => 45,
				);
				$response         = wp_remote_post( $action, $args );
				$response_status  = wp_remote_retrieve_response_code( $response );
				$response         = wp_remote_retrieve_body( $response );
				$is_key_available = false;
				bLoyalSnipetsLoggerService::write_custom_log( "Get Access Key Response \n\r" . $response . "\r\n ======================\r\n", 1 );
				if ( ! empty( $response ) ) {
					$access_key_data = json_decode( $response );
					if ( $access_key_data->status == 'success' ) {
						update_option( 'bloyal_snippets_access_key', $access_key_data->data );
					}
				}
				$action = 'Connectors/ContextInfo';
				$result = $bloyal_snippets_obj->send_curl_request( '', $action, 'grid', 0 );
				bLoyalSnipetsLoggerService::write_custom_log( "ContextInfo Response \n\r" . json_encode( $result ) . "\r\n ======================\r\n", 1 );
				update_option( 'bLoyal_web_snippets_storeUid', $result->data->StoreUid );
				$action = '/Stores/' . $result->data->StoreUid . '/Devices';
				$result = $bloyal_snippets_obj->send_curl_request( '', $action, 'grid', 0 );
				bLoyalSnipetsLoggerService::write_custom_log( "Get All Devices Response \n\r" . json_encode( $result ) . "\r\n ======================\r\n", 1 );
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
				if ( ! $error ) {
					$api_response = json_decode( $content );
					if ( ! empty( $api_response->data ) ) {
						$is_key_available = true;
					} elseif ( $api_response->message === 'Key already in use.' ) {
						$is_key_in_use = true;
					}
				}
				if ( $api_response->status = 'error' ) {
					if ( ! empty( $api_response->message ) ) {
						$response = json_encode(
							array(
								'error_msg_snippets' => $api_response->message,
							)
						);
						return $response;
					}
				}
				if ( $is_key_available == true ) {
					$response = json_encode(
						array(
							'is_access_key_available' => true,
							'access_key_snippets'     => $api_response->data,
						)
					);
				} elseif ( $is_key_in_use == true ) {
					$response = json_encode(
						array(
							'is_key_in_use' => true,
							'error_msg'     => $api_response->message,
						)
					);
				} else {
					$response = json_encode(
						array(
							'is_access_key_available' => false,
						)
					);
				}
				return $response;
			} catch ( Exception $exception ) {
				bLoyalSnipetsLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}

		/**
		 * This function is used to test access key.
		 *
		 * @param stdClass $obj
		 *
		 * @return json_object
		 */

		public function bloyal_snippets_test_access_key( $obj ) {
			try {
				$bloyal_snippets_obj     = new BloyalSnippetsController();
				$snippets_urls           = $bloyal_snippets_obj->bloyal_snippets_get_service_urls( $obj );
				$grid_api_url            = get_option( 'grid_api_url_snippet' );
				//this API use for test the access key using get bLoyal ContextInfo API by bLoyal.
				$test_access_key_api_url = $grid_api_url . '/api/v4/' . $obj->access_key_snippets . '/Connectors/ContextInfo';
				if ( $obj->is_custom_web_snippet_url_used == 'custom' ) {
					if ( $obj->snippets_custom_gridapi_url ) {
						$test_access_key_api_url = $obj->snippets_custom_gridapi_url . '/api/v4/' . $obj->access_key_snippets . '/Connectors/ContextInfo';
					}
				}
				bLoyalSnipetsLoggerService::write_custom_log( "Test Access Key Request URL \n\r" . $test_access_key_api_url . "\r\n ======================\r\n", 1 );
				$args = array(
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'method'  => 'GET',
					'timeout' => 45,
				);
				$response        = wp_remote_get( $test_access_key_api_url, $args );
				$response_status = wp_remote_retrieve_response_code( $response );
			    $response        = wp_remote_retrieve_body( $response );
				bLoyalSnipetsLoggerService::write_custom_log( "Test Access Key Response \n\r" . $response . "\r\n ======================\r\n", 1 );
				$is_key_available = false;
				$result = json_decode( $response, true );
                if (is_wp_error($result)) {
					$error = $response->get_error_message();
					return $error;
                } else {
					$api_response = json_decode( $response );
					if ( ! empty( $api_response->data ) ) {
						$is_key_available = true;
					} elseif ( $api_response->message === 'Key already in use.' ) {
						$is_key_in_use = true;
					}
				}
				if ( $api_response->status = 'error' ) {
					if ( ! empty( $api_response->message ) ) {
						$response = json_encode(
							array(
								'error_msg_snippets_api' => $api_response->message,
							)
						);
						return $response;
					}
				}
				if ( $is_key_available == true ) {
					$domain_name_snippets = get_option( 'bloyal_domain_name' );
					if ( ( strcasecmp( $api_response->data->KeyType, 'Store' ) == 0 ) && ( strcasecmp( $api_response->data->LoginDomain, $domain_name_snippets == 0 ) ) ) {
						$response = json_encode(
							array(
								'is_access_key_available' => true,
								'access_key'              => $api_response->data,
							)
						);
					} else {
						if ( ( strcasecmp( $api_response->data->KeyType, 'Store' ) != 0 ) && ( strcasecmp( $api_response->data->LoginDomain, $domain_name_snippets != 0 ) ) ) {
							$response = json_encode(
								array(
									'wrong_configuration_snippets' => true,
									'wrong_configuration_snippets_error_msg' => 'Key Type and Company name are incorrect.',
								)
							);
						}
						if ( ( strcasecmp( $api_response->data->KeyType, 'Store' ) != 0 ) && ( strcasecmp( $api_response->data->LoginDomain, $domain_name_snippets == 0 ) ) ) {
							$response = json_encode(
								array(
									'wrong_configuration_snippets' => true,
									'wrong_configuration_snippets_error_msg' => 'Key Type is incorrect.',
								)
							);
						}
						if ( ( strcasecmp( $api_response->data->KeyType, 'Store' ) == 0 ) && ( strcasecmp( $api_response->data->LoginDomain, $domain_name_snippets != 0 ) ) ) {
							$response = json_encode(
								array(
									'wrong_configuration_snippets' => true,
									'wrong_configuration_snippets_error_msg' => 'Company name is incorrect.',
								)
							);
						}
					}
				} elseif ( $is_key_in_use == true ) {
					$response = json_encode(
						array(
							'is_key_in_use' => true,
							'error_msg_'    => $api_response->message,
						)
					);
				} else {
					$response = json_encode(
						array(
							'is_access_key_available' => false,
						)
					);
				}
				return $response;
			} catch ( Exception $exception ) {
				bLoyalSnipetsLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}

		/**
		 * This function is used to save bloyal configuration settings data.
		 *
		 * @param stdClass $obj_save_conf
		 *
		 * @return json_object
		 */

		public function bloyal_snippets_save_configuration_data_wpdb( $obj_save_conf ) {
			try {
				update_option( 'bloyal_domain_name', $obj_save_conf->domain_name_snippets );
				update_option( 'bloyal_api_key', $obj_save_conf->api_key_snippets );
				update_option( 'bloyal_snippets_domain_url', $obj_save_conf->domain_url_snippets );
				update_option( 'bloyal_snippets_access_key', $obj_save_conf->access_key_snippets );
				update_option( 'bloyal_snippets_radio_web_snippet_api', $obj_save_conf->radio_web_snippet );
				update_option( 'bloyal_snippets_default_device', $obj_save_conf->default_device );
				update_option( 'bloyal_snippets_use_wordpress_login', $obj_save_conf->post_use_wordpress_login );
				update_option( 'bloyal_snippets_use_bloyal_login', $obj_save_conf->post_use_bloyal_login );
				update_option( 'bloyal_snippets_custom_grid_apiurl', $obj_save_conf->snippetscustomgridapiurl );
				update_option( 'bloyal_snippets_custom_loyaltyengine_apiurl', $obj_save_conf->snippetscustomloyaltyengineapiurl );
				update_option( 'bloyal_snippet_custom_websnippet_apiurl', $obj_save_conf->snippetcustomwebsnippetapiurl );
				update_option( 'bloyal_snippet_custom_websnippethtml_apiurl', $obj_save_conf->snippetcustomwebsnippethtmlapiurl );
				update_option( 'page_id', $obj_save_conf->page_id );
				if ( $obj_save_conf->radio_web_snippet == 'custom' ) {
					update_option( 'bloyal_snippets_custom_web_snippet_api_url', $obj_save_conf->domain_url_snippets );
				}
				return json_encode(
					array(
						'save_success'               => true,
						'snippets_access_key_status' => get_option( 'bloyal_access_key_verification_snippets' ),
					)
				);
			} catch ( Exception $exception ) {
				bLoyalSnipetsLoggerService::write_custom_log( $exception->getMessage(), 3 );
				return $exception->getMessage();
			}
		}

		/**
		 * This function is used to verify access key.
		 */
		/**
		 * This function is used to verify access key.
		 *
		 * @param string $access_key_verification_snippets which is true or false
		 * @param string $custom_web_snippet_url_used which is true or false
		 * @return json_object
		 */

		public function bloyal_snippets_save_accesskeyverification_data_wpdb( $access_key_verification_snippets, $custom_web_snippet_url_used ) {
			try {
				update_option( 'bloyal_access_key_verification_snippets', $access_key_verification_snippets );
				update_option( 'bloyal_snippets_radio_web_snippet_api', $custom_web_snippet_url_used );
				return json_encode(
					array(
						'save_success'                => true,
						'snippets_access_key_status'  => get_option( 'bloyal_access_key_verification_snippets' ),
						'is_custom_snippets_url_used' => get_option( 'bloyal_snippets_radio_web_snippet_api' ),
					)
				);
			} catch ( Exception $exception ) {
				return $exception->getMessage();
			}
		}
	}
}
