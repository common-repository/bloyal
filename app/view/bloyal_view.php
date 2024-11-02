<?php
require_once BLOYAL_DIR . '/app/controller/bloyal_inventory_locations.php';
if ( ! class_exists( 'BloyalView' ) ) {
	/**
	 * View class to output any HTML used at bLoyal
	 **/
	class BloyalView {
		function bloyal_get_shipping_methods_and_tenders( $action, $domain_name, $domain_url ) {
			$response = get_option( 'bloyal_' . $action );
			if ( ! empty( $response ) ) {
				
				return json_decode( $response );
			} else {
				$access_key             = get_option( 'bloyal_access_key' );
				$is_post                = 0;
				$is_custom_api_url_used = get_option( 'is_bloyal_custom_api_url' );
				if ( $is_custom_api_url_used == 'true' ) {
					$custom_grid_api_url_name = get_option( 'bloyal_custom_grid_api_url' );
					if ( $custom_grid_api_url_name ) {
						$post_url = $custom_grid_api_url_name . '/api/v4/' . $access_key . '/' . $action;
					} else {
						$bloyalController = new BloyalController();
						$bloyal_urls      = $bloyalController->bloyal_get_service_urls( $domain_name, $domain_url, $is_custom_api_url_used );
						$post_url         = $bloyal_urls->GridApiUrl . '/api/v4/' . $access_key . '/' . $action;
					}
				} else {
					$shipping_carrier_url = get_option( 'grid_api_url' );
					$post_url             = $shipping_carrier_url . '/api/v4/' . $access_key . '/' . $action;
				}
				if ( $post_url ) {
					if ( $is_post == 2 ) {
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
							'method'  => 'GET',
							'timeout' => 45,
						);
						$response = wp_remote_get( $post_url, $args );
					}
					$response_status = wp_remote_retrieve_response_code( $response );
					$response        = wp_remote_retrieve_body( $response );
					$result          = json_decode( $response );
					if ( is_wp_error( $result ) ) {
						$error = $response->get_error_message();
						return $error;
					} else {
						bLoyalLoggerService::write_custom_log( "Tenders Response \r\n" . json_encode( $response ) . "\r\n ======================\r\n", 1 );
						update_option( 'bloyal_' . $action, json_encode( $result ) );
						return $result;
					}
				}
			}
		}
		
		 /**
         * Function to fetch all service urls API by bLoyal
         *
         * @return api result
         */
		function bloyal_get_all_service_urls( $domain_name, $domain_url ) {
			try {
				if ( $domain_url ) {
					$post_url = $domain_url . '/api/v4/serviceurls/' . $domain_name;
				} else {
					$post_url = 'https://domain.bloyal.com/api/v4/serviceurls/' . $domain_name;
				}
				$args = array(
					'headers' => array(
						'Content-Type' => 'application/json; charset=utf-8',
					),
					'method'  => 'GET',
					'timeout' => 45,
				);
				$response        = wp_remote_get( $post_url, $args );
				$response_status = wp_remote_retrieve_response_code( $response );
			    $response        = wp_remote_retrieve_body( $response );
				$response       = json_decode( $response );
				return $response;
			} catch ( Exception $e ) {
				$this->log( __FUNCTION__, 'Error in getting service urls. Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}

		/** 
		 * Outputs HTML page for bloyal configuration section
		 *
		 * @return void
		 */

		function bloyal_render_config_submenu_page() {
			$this->provisioning_popup_html();
			$bloyalController = new BloyalController();
			$snippet_code     = $bloyalController->fetch_bloyal_devices_and_snippet_code();
			?>
			<html>
				<body class="key-body" id="key-body">
					<h2>bLoyal Configuration Setting</h2>
					<table class="config-table">
						<tr>
							<td>
								Company<i class="redfont">*</i>
							</td>
							<td>
								<input type="text" name="domainname" id="domainname" onclick="clearFields();" size="30" value="<?php echo esc_attr( isset( $_POST['domainname'] ) ?  sanitize_text_field( $_POST['domainname'] ) : '' ); ?>">
								<span class="redfont displaynone" id="domainRequired">Please enter valid Company</span>
							</td>
						</tr>
						<tr>
							<td>
								Device API Key<i class="redfont">*</i>
							</td>
							<td>
								<input type="text" name="apikey" id="apikey" onclick="clearFields();" size="30" value="<?php echo esc_attr( isset( $_POST['apikey'] ) ?  sanitize_text_field( $_POST['apikey'] ) : '' ); ?>">
								<span class="redfont displaynone" id="apiKeyRequired">Please enter valid Device API Key</span>
							</td>
						</tr>
						<tr>
							<td>
								<input type="submit" class="button button-primary" value="Lock" name="getaccesskey" id="getaccesskey">
								<input type="submit" class="button button-primary" value="Test" name="testaccesskey" id="testaccesskey">
								<div id="loading" class="loading" style="width: 100%;height: 100%;top: 0;left: 0;position: fixed;display: block;opacity: 0.7;background-color: #fff;z-index: 999998;text-align: center;display:none;" hidden> <p><img style="position: absolute;top: 300px;left: 700px;z-index: 999999;" id="loading-image" src="<?php echo esc_url(BLOYAL_URL.'assets/images/puff.svg'); ?>"/>Please Wait</p> </div>
								<div id="loading_for_test" class="loadingas" style="width: 100%;height: 100%;top: 0;left: 0;position: fixed;display: block;opacity: 0.7;background-color: #fff;z-index: 999998;text-align: center;display:none;" hidden> <p><img style="position: absolute;top: 300px;left: 700px;z-index: 999999;" id="loading-image" src="<?php echo esc_url(BLOYAL_URL.'assets/images/puff.svg'); ?>"/>Please Wait</p> </div>
							</td>
							<td>
								<span style="color:red;" id= "key_in_use"></span>
								<span style="display: none;" id= "getAccessKeyLoader">Generating access key...</span>
								<span style="display: none; color:green;" id= "getAccessKeyGenerated">Access key is generated successfully.</span>
								<span style="display: none; color:green;" id= "accessKeySuccess">Access key verification completed successfully.</span>
								<span style="color:red;" id= "accessKeyFail"></span>
								<span style="color:red;" id= "invalid_data"></span>
							</td>
						</tr>
						<tr id="access_key_row" style="display:none;">
							<td style="display:none;">
								Access Key
							</td>
							<td style="display:none;">
								<input type="password" name="accesskey" id="accesskey" size="80" value="">
							</td>
						</tr>
						<tr>
							<td>
								<h2>Cart Display Settings</h2>
							</td>	
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="loyaltyblock" id="loyaltyblock"> 
								Auto-expand Loyalty and Discounts in the Cart
							</td>
						</tr>
						<tr>
							<td>
								<h2>Payment Tender Mappings</h2>
							</td>	
						</tr>	
						<tr>
							<td>WooCommerce Payment Method</td>
							<td>bLoyal Payment Method</td>
						</tr>
						<?php
							$availableGateways        = WC()->payment_gateways->get_available_payment_gateways();
							$bLoyalTenderResponse     = '';
							$domain_name              = get_option( 'bloyal_domain_name' );
							$domain_url               = get_option( 'bloyal_domain_url' );
							$bLoyalTenderResponse     = $this->bloyal_get_shipping_methods_and_tenders( 'Tenders', $domain_name, $domain_url );
							$bLoyalTenderMappingValue = get_option( 'bloyal_tender_payments_mapping' );
						if ( $bLoyalTenderResponse != '' ) {
							foreach ( $availableGateways as $gateWayKey => $gateWayValue ) {
								?>
										<tr>
											<td class="col-sm-5">
												<?php echo esc_attr( $gateWayValue->title ); ?>
											</td>
											<td class="col-sm-5">
												
												<select class="col-sm-5 tenderPayments" name=<?php echo esc_attr($gateWayValue->id); ?>  id=<?php echo esc_attr($gateWayValue->id) . 'paymentTendor'; ?>>
												<option value=""> Select </option>		 
													<?php
													foreach ( $bLoyalTenderResponse->data as $key => $value ) {
														$isSelected = '';
														if ( isset( $bLoyalTenderMappingValue[ $gateWayValue->id ] ) && $bLoyalTenderMappingValue[ $gateWayValue->id ] == $value->Code ) {
															$isSelected = 'selected="selected"';
														}
														?>
														<option value="<?php echo esc_attr($value->Code); ?>"<?php echo esc_attr($isSelected); ?> > <?php echo esc_html($value->Code); ?></option>	
													<?php } ?>  
												</select>
											</td>
										</tr>
										<?php
							}
						}
						?>
						<tr>
							<td><h2>Loyalty Dollar</h2></td>
						</tr>
						<tr>
							<td>Apply Full Balance</td>
							<td>	
								<select class="col-sm-5" name="apply_full_balance_loyalty"  id="apply_full_balance_loyalty" onchange="applyIncrementOfLoyalty(this);">
									<option value="true"> Yes </option>
									<option value="false"> No </option>
								</select>	
							</td>
						</tr>
						<tr id="loyalty_apply_in_increment_of_section">
							<td>Apply In Increment Of</td>
							<td>	
								<input type="text" name="apply_in_increment_of_loyalty" id="apply_in_increment_of_loyalty" value="10">
							</td>
						</tr>
						<tr>
							<td><h2>Gift Card</h2></td>
						</tr>
						<tr>
							<td>Apply Full Balance</td>
							<td>	
								<select class="col-sm-5" name="apply_full_balance_giftcard"  id="apply_full_balance_giftcard" onchange="applyIncrementOf(this);">
									<option value="true"> Yes </option>
									<option value="false"> No </option>
								</select>	
							</td>
						</tr>
						<tr id="giftcard_apply_in_increment_of_section">
							<td>Apply In Increment Of</td>
							<td>	
								<input type="text" name="apply_in_increment_of_giftcard" id="apply_in_increment_of_giftcard" value="10">	
							</td>
						</tr>
					</tr>
					<tr>
						<td style="display: none;">
							On Account Tender Code
						</td>
						<td style="display: none;">
							<select name="onaccounttender" id="onaccounttender">
							<?php
							for ( $counter_tender = 0; $counter_tender < $count_tender; $counter_tender++ ) {
								if ( $response->data[ $counter_tender ]->TenderType == 'Account' ) {
									$onaccounttender = $response->data[ $counter_tender ]->Code;
									?>
										<option value=<?php echo esc_attr($onaccounttender); ?> ><?php echo esc_html($response->data[ $counter_tender ]->Code); ?></option>
									<?php
								}
							}
							?>
							</select>
						</td>
					</tr>
					<script type="text/javascript">
						function showAdvanceSettings(){
							var x = document.getElementById("show_advance_settings");
							if(x.style.display === "none") {
								x.style.display = "block";
								document.getElementById("bloyal_custom_url_display").style.display = "block";
								} else {
								x.style.display = "none";
								document.getElementById("bloyal_custom_url_display").style.display = "none";
							}
						}
						function checkAdvanceOptions() {
							if (document.getElementById('bloyal_custom_url').checked) {
								document.getElementById("bloyal_custom_url_display").style.display = "block";
								document.getElementById("bloyal_standard_url_display").style.display = "none";
							}
							if (document.getElementById('bloyal_standard_url').checked) {
								document.getElementById("bloyal_custom_url_display").style.display = "none";
								document.getElementById("bloyal_standard_url_display").style.display = "block";
							}
						}
					</script>
					</tr>
					<!-- Start bLoyal Log settings-->
                     <tr>
							<td><h2>bLoyal API Log Setting</h2></td>
						</tr>
						<tr>
							<td>Log Enable</td>
							<td>	
								<select class="col-sm-5" name="log_enable_disable"   id="log_id" 	>
									<option value="true"> Yes </option>
									<option value="false" selected="selected"> No </option>
								</select>	
							</td>
						</tr>
						<tr id="log_download">
							<td>
								<span>Click here </span>
								<a href="#" id="download">to download bLoyal API's Request/Response log</a>
							</td>
							
						</tr>
						<tr id="log_download">
							<td>
								<span>Click here </span>
								<a href="#" id="remove">to clean bLoyal API's Request/Response log</a>
							</td>
						</tr>
					<!-- End bLoyal Log settings-->
					<tr>
						<td>
							<input type="button" class="button button-primary" value="Advance Settings" onclick="showAdvanceSettings();" name="showAdvanceSetting" id="showAdvanceSetting">
						</td>
					</tr>
					<tr>
						<td>
							<div id="show_advance_settings" style="display:none">
								<input type="radio" name="bloyal_advance_url" onchange="javascript:checkAdvanceOptions();"  value="bloyal_standard" id="bloyal_standard_url"> Standard<br>
							<input type="radio" name="bloyal_advance_url" onchange ="javascript:checkAdvanceOptions();" value="bloyal_custom" id="bloyal_custom_url"> Custom </td>
						</div>
					</td>
				</tr>
				<?php
				$domain_name = get_option( 'bloyal_domain_name' );
				$domain_url  = get_option( 'bloyal_domain_url' );
				$response    = $this->bloyal_get_all_service_urls( $domain_name, $domain_url );
				?>
			</table><br>
			<table>
				<tr>
					<td>
						<div id="bloyal_standard_url_display" style="display:none;">
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<div id="bloyal_custom_url_display" style="display:none">
							Custom Domain URL
						<input type="text" name="domainurl" id="domainurl" size="30" value="<?php echo esc_attr( isset( $_POST['domainurl'] ) ? sanitize_url( $_POST['domainurl'] ) : '' ); ?>" style="margin-left: 127px;">
						<?php echo '<br/>'; ?>
							GridApi Url
							<input type="text" name="customgridapiurl" id="customgridapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['customgridapiurl'] ) ? sanitize_url( $_POST['customgridapiurl'] ) : ''); ?>" style="margin-left: 183px;">
						<?php echo '<br/>'; ?>
							LoyaltyEngineApi Url
							<input type="text" name="customloyaltyengineapiurl" id="customloyaltyengineapiurl" size="30" value="<?php echo esc_attr( isset( $_POST['customloyaltyengineapiurl'] ) ? sanitize_url( $_POST['customloyaltyengineapiurl'] ) : '' ); ?>" style="margin-left: 128px;">
						<?php echo '<br/>'; ?>
							OrderEngineApi Url
							<input type="text" name="customorderengineapiurl" id="customorderengineapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['customorderengineapiurl'] ) ? sanitize_url( $_POST['customorderengineapiurl'] ) : ''); ?>" style="margin-left: 134px;">
						<?php echo '<br/>'; ?>
							PaymentApi Url
							<input type="text" name="custompaymentapiurl" id="custompaymentapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['custompaymentapiurl'] ) ? sanitize_url( $_POST['custompaymentapiurl'] ) : ''); ?>" style="margin-left: 157px;">
						<?php echo '<br/>'; ?>
							LoggingApi Url
							<input type="text" name="customloggingapiurl" id="customloggingapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['customloggingapiurl'] ) ? sanitize_url( $_POST['customloggingapiurl'] ) : '');?>" style="margin-left: 160px;">
						<?php echo '<br/>'; ?>
							Access Key
							<input type="password" name="adv_accesskey" id="adv_accesskey" size="30" value="<?php echo esc_attr( isset( $_POST['customloggingapiurl'] ) ? sanitize_url( $_POST['customloggingapiurl'] ) : '');?>" style="margin-left: 184px;">
							<input type="submit" class="button button-warning removeaccesskey" value="Remove access key" name="removeaccesskey" id="removeaccesskey" onclick="scrollToTop()" style="display:none;" >
						<?php echo '<br/>'; ?>
						</div>
						<tr id="save_access_key">
							<td>
								<input type="submit" class="button button-primary saccesskey" value="Save Settings" name="saveaccesskey" id="saveaccesskey">
								<input type="submit" class="button button-primary" value="Refresh Configuration" name="bloyal_refresh_cached_data" id="bloyal_refresh_cached_data">
							</td>
						</tr>
					</table>
					<span style="color:red;display:none;" id= "save_configuration_fail">Access key is not generated. Please click Lock button to generate Access Key.</span>
					<span style="color:green;font-size:20px;display:none;" id= "save_configuration_success">bLoyal configuration settings saved successfully.</span>
					<span style="color:green;font-size:20px;display:none;" id= "refresh_configuration_success">bLoyal configuration settings refreshed successfully.</span>
					<span style="color:red;font-size:20px;display:none;" id= "refresh_configuration_error">Could not refresh at the moment, please try again later.</span>
				</body>
			</html>
			<?php
		}
		function bloyal_render_config_submenu_page_order_processing() {
			$this->provisioning_popup_html();
			$bloyalController = new BloyalController();
			$snippet_code     = $bloyalController->fetch_bloyal_devices_and_snippet_code();
			?>
			<html>
			
				<body>
					<div id="loading" class="loading" style="width: 100%;height: 100%;top: 0;left: 0;position: fixed;display: block;opacity: 0.7;background-color: #fff;z-index: 999998;text-align: center;display:none;" hidden> <p><img style="position: absolute;top: 300px;left: 700px;z-index: 999999;" id="loading-image" src="<?php echo esc_url(BLOYAL_URL.'assets/images/puff.svg'); ?>"/>Please Wait</p> </div>
					<div id="loading_for_test" class="loadingas" style="width: 100%;height: 100%;top: 0;left: 0;position: fixed;display: block;opacity: 0.7;background-color: #fff;z-index: 999998;text-align: center;display:none;" hidden> <p><img style="position: absolute;top: 300px;left: 700px;z-index: 999999;" id="loading-image" src="<?php echo esc_url(BLOYAL_URL.'assets/images/puff.svg'); ?>"/>Please Wait</p> </div>
					<h2>bLoyal Order Processing Configuration</h2>
					<table class="config-table">
					<tr id="shipping_method_block_title">
						<td>
							<h2>Shipping Method Mapping</h2>
						</tr>
						<tr id="shipping_method_block_heading">
							<td>
								WooCommerce Method
							</td>
							<td id="bloyalcarrier">
								bLoyal carrier
							<?php echo str_repeat( '&nbsp;', 19 ); ?>
								bLoyal Service
							</td>
						</tr>
					<?php
						global $wpdb;
						$db_table_name                 = $wpdb->prefix . 'woocommerce_shipping_zone_methods';
						$querystr                      = "select method_id from $db_table_name where is_enabled = %d and method_id IN ('%s','%s')";
						$active_shipping_method        = $wpdb->get_results( $wpdb->prepare( $querystr, 1, 'free_shipping','flat_rate' ) );
						$count                         = count( $active_shipping_method );
						$querystr                      = "select method_id, instance_id from $db_table_name where is_enabled = %d and method_id IN ('%s')";
						$active_pickup_shipping_method = $wpdb->get_results( $wpdb->prepare( $querystr, 1, 'local_pickup' ) );
						$count_pickup                  = count( $active_pickup_shipping_method );
					if ( $count == 0 ) {
						echo ' <style> #bloyalcarrier { display: none ; } </style>';
					}
					if ( $count == 0 && $count_pickup == 0 ) {
						echo ' <style> #shipping_method_block { display: none ; } </style>';
						echo ' <style> #shipping_method_block_title { display: none ; } </style>';
						echo ' <style> #shipping_method_block_heading { display: none ; } </style>';
					}
						$action                 = 'ShippingCarriers';
						$domain_name            = get_option( 'bloyal_domain_name' );
						$domain_url             = get_option( 'bloyal_domain_url' );
						$count_shipping_carrier = 0;
					if ( $domain_name != '' ) {
						$response = $this->bloyal_get_shipping_methods_and_tenders( $action, $domain_name, $domain_url );
						if(!empty($response)){
							$count_shipping_carrier  = count( $response->data );
							$count_shipping_service  = count( $response->data['0']->Services );
						}
						$access_key_verification = esc_attr(get_option( 'bloyal_access_key_verification' ));
					}
					?>
						<input type="hidden" id="getcount" value="<?php echo esc_attr($count); ?>" name="getcount">
						<input type="hidden" name="accesskeyverification" id="accesskeyverification"  value="<?php echo esc_attr($access_key_verification); ?>">
						<?php
						$db_table_name           = $wpdb->prefix . 'woocommerce_shipping_zone_methods';
						$querystrresult          = "select method_id from $db_table_name where is_enabled = %d";
						$active_shipping_methods = $wpdb->get_results( $wpdb->prepare( $querystrresult, 1) );
						$shipping_methods_count  = count( $active_shipping_methods );
						for ( $counter = 0; $counter < $shipping_methods_count; $counter++ ) {
							if ( ( isset( $active_shipping_method[ $counter ] ) && $active_shipping_method[ $counter ]->method_id == 'local_pickup' ) || ( isset( $active_shipping_method[ $counter ] ) && $active_shipping_method[ $counter ]->method_id == 'free_shipping' ) || ( isset( $active_shipping_method[ $counter ] ) && $active_shipping_method[ $counter ]->method_id == 'flat_rate' ) ) {
								echo '<tr id="shipping_method_block">';
								echo '<td>';
								if ( $active_shipping_method[ $counter ]->method_id == 'local_pickup' ) {
									$method_title = 'Local pickup';
									?>
									<input type="hidden" id="<?php echo 'shippingmethodname' . esc_attr($counter); ?>" value="<?php echo 'method_' . esc_attr( $active_shipping_method[ $counter ]->method_id ); ?>" name="<?php echo 'method_' . esc_attr( $active_shipping_method[ $counter ]->method_id ); ?>">
									<?php
								}
								if ( $active_shipping_method[ $counter ]->method_id == 'free_shipping' ) {
									$method_title = 'Free shipping';
									?>
									<input type="hidden" id="<?php echo 'shippingmethodname' . esc_attr($counter); ?>" value="<?php echo 'method_' . esc_attr($active_shipping_method[ $counter ]->method_id); ?>" name="<?php echo 'method_' . esc_attr($active_shipping_method[ $counter ]->method_id); ?>">
									<?php
								}
								if ( $active_shipping_method[ $counter ]->method_id == 'flat_rate' ) {
									$method_title = ' Flat rate';
									?>
									<input type="hidden" id="<?php echo 'shippingmethodname' . esc_attr($counter); ?>" value="<?php echo 'method_' . esc_attr($active_shipping_method[ $counter ]->method_id); ?>" name="<?php echo 'method_' . esc_attr($active_shipping_method[ $counter ]->method_id); ?>">
									<?php
								}
								echo esc_attr($method_title);
								echo '</td>';
								echo '<td>';
								?>
								<select onchange="check_shipping_carrier(<?php echo esc_attr($counter); ?>)" id="shippingcarrier<?php echo esc_attr( $counter ); ?>">
									<option value="0">Select</option>
								<?php
								for ( $counterinner = 0; $counterinner < $count_shipping_carrier; $counterinner++ ) {
									$shippingcarrier = $response->data[ $counterinner ]->Code;
									?>
										<option value=<?php echo esc_attr($shippingcarrier); ?> ><?php echo esc_html($response->data[ $counterinner ]->Code); ?></option>
										<?php
								}
								?>
								</select>
								<?php
								echo str_repeat( '&nbsp;', 20 );
								?>
								<select id="shippingservice<?php echo esc_attr($counter); ?>" class="shippingservice<?php echo esc_attr($counter); ?>">
									<option value="0">Select</option>
								</select>
								<?php
								echo '</td>';
								echo '</tr>';
							}
						}
						?>
						<?php
						$shipping_service_code = array();
						$arr1                  = array();
						$arr2                  = array();
						$arr3                  = array();
						$arr4                  = array();
						for ( $counterservice = 0; $counterservice < $count_shipping_carrier; $counterservice++ ) {
							$test = '';
							$arr  = 'arr' . $response->data[ $counterservice ]->Code;
							'array name =' . $arr;
							$arrName = $arr;
							$arr     = array();
							for ( $counterserviceinner = 0; $counterserviceinner < $count_shipping_carrier; $counterserviceinner++ ) {
								if ( isset( $response->data[ $counterservice ]->Services[ $counterserviceinner ] ) && strlen( $response->data[ $counterservice ]->Services[ $counterserviceinner ]->Code ) ) {
									$test = $test . $response->data[ $counterservice ]->Services[ $counterserviceinner ]->Code . ',';
								}
							}

							?>
							<input type="hidden" id="<?php echo esc_attr( $arrName ); ?>" value="<?php echo esc_attr( $test ); ?>">
							<?php
							$shipping_carrier = array();
							$shipping_carrier = $response->data[ $counterservice ]->Code;
						}
						?>
						<tr>
							<td>
								<h2>Order Engine Settings</h2>
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="useorderengine" id="useorderengine" onclick="check_bloyal_order_processing();"> Use bLoyal Order Engine
							</td>
						</tr>
						<tr>
							<td style="margin-left:35px; float: left;">
								<input type="checkbox" checked name="appliedshippingcharges" id="appliedshippingcharges"> bLoyal Applied Shipping Charges
							</td>
						</tr>
						<tr>
							<td style="margin-left:36px; float: left;">
								<input type="checkbox" checked name="appliedtaxes" id="appliedtaxes"> bLoyal Applied Taxes
							</td>
						</tr>
						<tr>
							<td>
								<h2>Checkout Form Options</h2>
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="isDisplayDOB" id="isDisplayDOB"> Display Date Of Birth Field On CheckOut Form
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="requiredDOB" id="isRequiredDOB"> 
								Is Date Of Birth Required?
							</td>

						</tr>
						<tr>
							<td>
								<h2>Shipping Address Custom Fields</h2>
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="isDisplayPhone" id="isDisplayPhone"> Display Phone Field On CheckOut Form
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="requiredPhone" id="isRequiredPhone"> 
								Is Phone Required?
							</td>

						</tr>
						<tr>
							<td>
								<input type="checkbox" name="isDisplayEmail" id="isDisplayEmail"> Display Email Field On CheckOut Form
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="requiredEmail" id="isRequiredEmail"> 
								Is Email Required?
							</td>

						</tr>
						<tr>
							<td>
								<input type="checkbox" name="isDisplayOrderComments" id="isDisplayOrderComments"> Display Order Instructions On CheckOut Form
							</td>
						</tr>
						<tr>
							<td>
								<h2>bLoyal Address Book Setting</h2>
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="isDisplayAddressBook" id="isDisplayAddressBook"> Display Address Book On CheckOut Form
							</td>
						</tr>
						<script type="text/javascript">
							function showAdvanceSettings(){
								var x = document.getElementById("show_advance_settings");
								if(x.style.display === "none") {
									x.style.display = "block";
									document.getElementById("bloyal_custom_url_display").style.display = "block";
									} else {
									x.style.display = "none";
									document.getElementById("bloyal_custom_url_display").style.display = "none";
								}
							}
							function checkAdvanceOptions() {
								if (document.getElementById('bloyal_custom_url').checked) {
									document.getElementById("bloyal_custom_url_display").style.display = "block";
									document.getElementById("bloyal_standard_url_display").style.display = "none";
								}
								if (document.getElementById('bloyal_standard_url').checked) {
									document.getElementById("bloyal_custom_url_display").style.display = "none";
									document.getElementById("bloyal_standard_url_display").style.display = "block";
								}
							}
						</script>
					</tr>
					<tr>
						<td>
							<div id="show_advance_settings" style="display:none">
								<input type="radio" name="bloyal_advance_url" onchange="javascript:checkAdvanceOptions();"  value="bloyal_standard" id="bloyal_standard_url"> Standard<br>
							<input type="radio" name="bloyal_advance_url" onchange ="javascript:checkAdvanceOptions();" value="bloyal_custom" id="bloyal_custom_url"> Custom </td>
						</div>
					</td>
				</tr>
				<?php
				$domain_name = get_option( 'bloyal_domain_name' );
				$domain_url  = get_option( 'bloyal_domain_url' );
				$response    = $this->bloyal_get_all_service_urls( $domain_name, $domain_url );
				?>
			</table><br>
			<table>
				<tr>
					<td>
						<div id="bloyal_standard_url_display" style="display:none;">
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<div id="bloyal_custom_url_display" style="display:none">
							Custom Domain URL
							<input type="text" name="domainurl" id="domainurl" size="30" value="<?php echo esc_attr( isset( $_POST['domainurl'] ) ? sanitize_url( $_POST['domainurl'] ) : ''); ?>" style="margin-left: 127px;">
						<?php echo '<br/>'; ?>
							GridApi Url
							<input type="text" name="customgridapiurl" id="customgridapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['customgridapiurl'] ) ? sanitize_url( $_POST['customgridapiurl'] ) : ''); ?>" style="margin-left: 183px;">
						<?php echo '<br/>'; ?>
							LoyaltyEngineApi Url
							<input type="text" name="customloyaltyengineapiurl" id="customloyaltyengineapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['customloyaltyengineapiurl'] ) ? sanitize_url( $_POST['customloyaltyengineapiurl'] ) : ''); ?>" style="margin-left: 128px;">
						<?php echo '<br/>'; ?>
							OrderEngineApi Url
							<input type="text" name="customorderengineapiurl" id="customorderengineapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['customorderengineapiurl'] ) ? sanitize_url( $_POST['customorderengineapiurl'] ) : ''); ?>" style="margin-left: 134px;">
						<?php echo '<br/>'; ?>
							PaymentApi Url
							<input type="text" name="custompaymentapiurl" id="custompaymentapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['custompaymentapiurl'] ) ? sanitize_url( $_POST['custompaymentapiurl'] ) : ''); ?>" style="margin-left: 157px;">
						<?php echo '<br/>'; ?>
							LoggingApi Url
							<input type="text" name="customloggingapiurl" id="customloggingapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['customloggingapiurl'] ) ? sanitize_url( $_POST['customloggingapiurl'] ) : ''); ?>" style="margin-left: 160px;">
						<?php echo '<br/>'; ?>
						</div>
						<tr id="save_access_key">
							<td>
								<input type="submit" class="button button-primary" value="Save Settings" name="saveorderprocessing" id="saveorderprocessing">
								<input type="submit" class="button button-primary" value="Refresh Configuration" name="bloyal_refresh_cached_data" id="bloyal_refresh_cached_data">
							</td>
						</tr>
					</table>
					<span style="color:red;display:none;" id= "save_configuration_fail">Access key is not generated. Please click Lock button to generate Access Key.</span>
					<span style="color:green;font-size:20px;display:none;" id= "save_configuration_success">bLoyal configuration settings saved successfully.</span>
					<span style="color:green;font-size:20px;display:none;" id= "refresh_configuration_success">bLoyal configuration settings refreshed successfully.</span>
					<span style="color:red;font-size:20px;display:none;" id= "refresh_configuration_error">Could not refresh at the moment, please try again later.</span>
				</body>
			</html>
			<?php
		}


		/**
		 * Outputs HTML page for bloyal Click & Collect section
		 *
		 * @return void
		 */

		function bloyal_click_and_collect_config_submenu_page() {
			$this->provisioning_popup_html();
			?>
				<html>
					<body>
						<div id="loading" class="loading" style="width: 100%;height: 100%;top: 0;left: 0;position: fixed;display: block;opacity: 0.7;background-color: #fff;z-index: 999998;text-align: center;display:none;" hidden> <p><img style="position: absolute;top: 300px;left: 700px;z-index: 999999;" id="loading-image" src="<?php echo esc_url(BLOYAL_URL.'assets/images/puff.svg'); ?>"/>Please Wait</p> </div>
						<h2>bLoyal Click & Collect Setting</h2>
						<table class="config-table">
							<tr>
								<td class="col-sm-5">
									Enable :
								</td>
								<td class="col-sm-5">												
									<select class="col-sm-5 clickCollects" name="bLoyal_bloyal_click_and_collect_status" id="bLoyal_bloyal_click_and_collect_status">
										<option value="1"> Yes </option>		 
										<option value="2"> No  </option>	
									</select>
								</td>
							</tr>
							<tr>
								<td class="col-sm-5">
									Title : <i class="redfont">*</i>
								</td>
								<td class="col-sm-5">
									<input type="text" name="bloyal_bloyal_click_collect_label" id="bloyal_bloyal_click_collect_label" size="30" value="<?php echo esc_attr( isset( $_POST['bloyal_bloyal_click_collect_label'] ) ? sanitize_text_field( $_POST['bloyal_bloyal_click_collect_label'] ) : 'Pickup at Store'); ?>">
								</td>
							</tr>
							<tr>
								<td class="col-sm-5">
									Error Title : <i class="redfont">*</i>
								</td>
								<td class="col-sm-5">
									<input type="text" name="bloyal_click_collect_error_title" id="bloyal_click_collect_error_title" size="30" value="<?php echo esc_attr( isset( $_POST['bloyal_click_collect_error_title'] ) ? sanitize_text_field( $_POST['bloyal_click_collect_error_title'] ) : 'No store available for active cart.'); ?>">
								</td>
							</tr>
							<tr id="save_click_collect_settings">
								<td class="col-sm-5"></td>
							<td class="col-sm-5">
								<input type="submit" class="button button-primary" value="Save Settings" name="saveclickcollectsettings" id="saveclickcollectsettings">
							</td>
						</tr>
						</table>
						<span style="color:red;display:none;" id= "save_configuration_fail">Access key is not generated. Please click Lock button to generate Access Key.</span>
						<span style="color:green;font-size:20px;display:none;" id= "save_configuration_success">bLoyal click & collect configuration settings saved successfully.</span>
						<span style="color:green;font-size:20px;display:none;" id= "refresh_configuration_success">bLoyal click & collect configuration settings refreshed successfully.</span>
						<span style="color:red;font-size:20px;display:none;" id= "refresh_configuration_error">Could not refresh at the moment, please try again later.</span>
					</body>
				</html>
			<?php }
		public function provisioning_popup_html() {
			$access_key = get_option( 'bloyal_access_key' );
			if ( empty( $access_key ) ) { ?>
					<div class="parentDisable" id="backgroundDisable"></div>
					<div  class = "provisioning_popup" id = "popup_provise">
						 <center>
							 <h2 class="heading-web-app">Please activate your device</h2>
							<form id="post_provisioning">
								<caption><span class="provisioning-title">Your web store device needs to be activated with bLoyal.  Please click the ‘Activate’ button to create your bLoyal account or associate this device to an existing bLoyal account.</span></caption>
								<table width = "900" height="120" border = "0" cellspacing = "1" cellpadding = "2">				  
									<tr>
										<td>
											<input name = "return_url" type = "hidden" id = "id_return_url"  value="">
											<input name = "domain_name" type = "hidden" id = "id_domain_name"  value="">
										</td>
									</tr>
									<tr>
										<td class="provisioning_btn">
											<input type="button" class="button button-primary" value="Activate Device" name="activatedevice" id="activatedevice">
										</td>
									</tr>
									<tr>
										<td class="provisioning_btn">
											<input class="button button-primary" name = "cancel_provisioning" type = "button" id = "id_cancel_provisioning" value = "Cancel and manually configure your device">
										</td>
									</tr>
								</table>
							</form>
						</center>
					</div>
				<?php
			}
		}
	}
}
?>
