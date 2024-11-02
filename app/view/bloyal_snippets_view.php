<?php

if ( ! class_exists( 'BloyalSnippetsView' ) ) {
	require_once BLOYAL_DIR . '/app/view/bloyal_view.php';
	/**
	 * View class to output any HTML used at bLoyal
	 **/
	class BloyalSnippetsView {
		/**
		 * Outputs HTML page for bloyal snippets configuration section
		 *
		 * @return void
		 */

		function bloyal_snippets_render_config_submenu_page() {

			?>
			<html>
				<body>
					<h2>bLoyal Web Snippets Configuration Setting</h2>
					<table class="config-table">
						<tr>
							<td>
								Company<i class="redfont">*</i>
							</td>
							<td>

								<input type="text" name="domain_name_snippets" id="domain_name_snippets" onclick="clearFieldsSnippets();" size="30" value="<?php echo esc_attr( isset( $_POST['domain_name_snippets'] ) ? sanitize_text_field( $_POST['domain_name_snippets'] ) : ''); ?>">

								<span class="redfont displaynone" id="domainRequired">Please enter valid Company</span>
							</td>
						</tr>

						<tr>
							<td>
								Store API Key<i class="redfont">*</i>
							</td>

							<td>

								<input type="text" name="api_key_snippets" id="api_key_snippets" onclick="clearFieldsSnippets();" size="30" value="<?php echo esc_attr( isset( $_POST['api_key_snippets'] ) ? sanitize_text_field( $_POST['api_key_snippets'] ): ''); ?>">

								<span class="redfont displaynone" id="apiKeyRequired">Please enter valid Store API Key</span>
							</td>
						</tr>

						<tr>
							<td>

								<input type="submit" class="button button-primary" value="Lock" name="getaccesskeysnippets" id="getaccesskeysnippets">
								<input type="submit" class="button button-primary" value="Test" name="testaccesskeysnippets" id="testaccesskeysnippets">
								<div id="loading_snippets" class="loading_snippets" style="width: 100%;height: 100%;top: 0;left: 0;position: fixed;display: block;opacity: 0.7;background-color: #fff;z-index: 999998;text-align: center;display:none;" hidden> <p><img style="position: absolute;top: 300px;left: 700px;z-index: 999999;" id="loading-image" src="<?php echo esc_url(BLOYAL_URL.'assets/images/puff.svg'); ?>"/>Please Wait</p> </div>
							</td>

							<td>
								<span style="display: none;" id= "getAccessKeyLoaderSnippets">Generating access key...</span>
								<i style="color:red;font-size:20px;font-family:calibri;display:none;" id= "key_in_use_snippets"></i>
								<span style="display: none; color:green;" id= "getAccessKeyGeneratedSnippets">Access key is generated successfully.</span>
								<span style="display: none; color:green;" id= "accessKeySuccessSnippets">Access key verification completed successfully.</span>
								<span style="color:red;" id= "accessKeyFailSnippets"></span>
								<span style="color:red;" id= "invalid_data_snippet"></span>
							</td>
						</tr>
						<?php
							$access_key_verification_snippets = get_option( 'bloyal_access_key_verification_snippets' );
						if ( empty( $access_key_verification_snippets ) ) {
							$access_key_verification_snippets = 0;
						}
						?>
						<input type="hidden" name="snippetAccesskeyVerification" id="snippetAccesskeyVerification"  value="<?php echo esc_attr( $access_key_verification_snippets ); ?>">
						<tr id="access_key_row_snippets" style="display:none;">
							<td style="display:none;">
								Access Key
							</td>
							<td style="display:none;">
								<input type="password" name="accesskeysnippets" id="accesskeysnippets" size="80" value="">
							</td>
						</tr>
						<tr id="default_device_row" style="display:none;">
							<td>
								Select Default Device:
							</td>
							<td>
								<select id="default_device_select"></select>
							</td>
							<td>
								<input type="submit" class="button button-primary refreshdevices" value="Refresh" name="refreshdevicecodes" id="refreshdevicecodes" style="margin-top:13%;">
							</td>

						</tr>
						<tr>
							<td>
							   <span style="display: none; color:green;" id= "refreshdevicesmsg">Devices refreshed successfully.</span>
							</td>
						</tr>
						<tr>
							<td>
								<h2>Customer Authentication Method</h2>
							</td>
						</tr>
						<tr>
							<td>
								<input type="radio" name="customer_authentication" value="usewordpresslogin" id="usewordpresslogin" checked> Use WordPress Account Login<br>
							</td>
						<tr>
						</tr>
							<td>
								<select name="page_id" id="page_id" style="width:130px;">
									<option value="0">WordPress Default Login</option>

								<?php

								$total       = get_pages();
								$total_pages = count( $total );
								$page_count  = 0;
								for ( $page_count = 0; $page_count < $total_pages - 1; $page_count++ ) {
									?>
									<option value="<?php echo esc_attr( $total[ $page_count ]->ID ); ?>"><?php echo esc_html( $total[ $page_count ]->post_title ); ?></option>
									<?php
								}
								?>
								<br>
							</select>
							</td>
						</tr>
						<tr>
							<td>
								<input type="radio" name="customer_authentication" value="usebloyallogin" id="usebloyallogin"> Use bLoyal Account Login
							</td>
						</tr>
						<tr>
							<td>
								<h2>	</h2>
							</td>
						</tr>
						<tr>
							<td>
								<input type="button" class="button button-primary" value="Advance Settings" onclick="showAdvanceSettingsSnippets();" name="showAdvanceSettingsSnippets" id="showAdvanceSettingsSnippets">
							</td>
						</tr>
						<tr>
							<td>
								<div id="show_advance_settings_snippets" style="display:none">
								  <input type="radio" name="web_snippet_url" value="standard" id="standard"
								onchange="javascript:checkAdvanceOptionsSnippets();" checked> Standard<br>
								  <input type="radio" name="web_snippet_url" onchange="javascript:checkAdvanceOptionsSnippets();" value="custom" id="custom"> Custom </td>
							</td>
						</tr>
						<script type="text/javascript">
							function showAdvanceSettingsSnippets(){
								var x = document.getElementById("show_advance_settings_snippets");
								if(x.style.display === "none") {
									x.style.display = "block";
									if (typeof snippets_custom_url_display !== 'undefined') {
										document.getElementById("snippets_custom_url_display").style.display = "block";
									}
								} else {
									x.style.display = "none";
									if (typeof snippets_custom_url_display !== 'undefined') {
										document.getElementById("snippets_custom_url_display").style.display = "none";
									}
								}
								if (typeof standard !== 'undefined') {
									var ischecked = document.getElementById("standard").checked;
									if(ischecked == true){
										if (typeof snippets_custom_url_display !== 'undefined') {
											document.getElementById("snippets_custom_url_display").style.display = "none";
										}
									}
								}
							}
							function checkAdvanceOptionsSnippets() {
								if (document.getElementById('custom').checked) {
									document.getElementById("snippets_custom_url_display").style.display = "block";
									document.getElementById("snippets_standard_url_display").style.display = "none";
								}
								if (document.getElementById('standard').checked) {
									document.getElementById("snippets_custom_url_display").style.display = "none";
									document.getElementById("snippets_standard_url_display").style.display = "block";
								}
							}
						</script>
					</table><br>
					<table>
						<tr>
							<td>
								<div id="snippets_standard_url_display"> </div>
							</td>
						</tr>
						<tr>
							<td>
								<div id="snippets_custom_url_display"  style="display:block;">

									<table>
										<tr>
											<td>
												Custom Domain URL
											</td>
											<td>
												<input type="text" name="domainurl_snippets" id="domainurl_snippets" size="30" value="<?php echo esc_attr( isset( $_POST['domainurl_snippets'] ) ? sanitize_text_field( $_POST['domainurl_snippets'] ) : ''); ?>">
											</td>
										</tr>
										<tr>
											<td>GridApi Url</td>
											<td>
												<input type="text" name="snippetscustomgridapiurl" id="snippetscustomgridapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['snippetscustomgridapiurl'] ) ? sanitize_text_field( $_POST['snippetscustomgridapiurl'] ) : ''); ?>" >
											</td>
										</tr>
										<tr>
											<td>LoyaltyEngineApi Url</td>
											<td>
												<input type="text" name="snippetscustomloyaltyengineapiurl" id="snippetscustomloyaltyengineapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['snippetscustomloyaltyengineapiurl'] ) ? sanitize_text_field( $_POST['snippetscustomloyaltyengineapiurl'] ) : ''); ?>" >
											</td>
										</tr>
										<tr>
											<td>Websnippet Url</td>
											<td>
											<input type="text" name="snippetcustomwebsnippetapiurl" id="snippetcustomwebsnippetapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['snippetcustomwebsnippetapiurl'] ) ? sanitize_text_field( $_POST['snippetcustomwebsnippetapiurl'] ) : ''); ?>" >
										</tr>
										<tr>
											<td>WebsnippetHTML Url</td>
											<td>
												<input type="text" name="snippetcustomwebsnippethtmlapiurl" id="snippetcustomwebsnippethtmlapiurl"  size="30" value="<?php echo esc_attr( isset( $_POST['snippetcustomwebsnippethtmlapiurl'] ) ? sanitize_text_field( $_POST['snippetcustomwebsnippethtmlapiurl'] ) : ''); ?>" >
											</td>
										</tr>
									</table>
								</div>

							<tr id="save_access_key">
								<td>
									<input type="submit" class="button button-primary" value="Save Settings" name="saveaccesskeysnippets" id="saveaccesskeysnippets">
								</td>
							</tr>
						</table>
						<i style="color:red;font-size:20px;font-family:calibri;display:none;" id= "save_configuration_fail">Failed to save bLoyal snippets configuration settings, Please retry.</i>
						<i style="color:green;font-size:20px;font-family:calibri;display:none;" id= "save_configuration_success">bLoyal Web snippets configuration settings saved successfully.</i>
				</body>
			</html>
			<?php
		}

		/**
		 * Outputs HTML page for bloyal snippets detail section
		 *
		 * @return void
		 */

		function bloyal_snippets_render_snippets_detail_submenu_page() {
			$bloyal_view_obj = new BloyalView();
			$bloyal_view_obj->provisioning_popup_html();
			?>
			<html>
				<body>
					<h2>Snippet Codes and Shortcodes</h2>
					<table id="snippets_details_table">
						<thead>
							<tr>
								<th><input type="checkbox" id="ckb_all_snippets" />&nbsp;Use original css</th>
								<th>Snippet Type</th>
								<th>Snippet Code</th>
								<th>Shortcode</th>
								<th>Copy Shortcode</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
					<br />
					<div style="color:red;" id= "snippets_error"></div>
					<div id="loading_snippets_list" class="loading_snippets_list" style="width: 100%;height: 100%;top: 0;left: 0;position: fixed;display: block;opacity: 0.7;background-color: #fff;z-index: 999998;text-align: center;display:none;" hidden> <p><img style="position: absolute;top: 300px;left: 700px;z-index: 999999;" id="loading-image" src="<?php echo esc_url(BLOYAL_URL.'assets/images/puff.svg'); ?>"/>Please Wait</p> </div>
					<input type="submit" class="button button-primary" value="Save" name="saveSnippetsUseCSSStatus" id="saveSnippetsUseCSSStatus">
					<input type="submit" class="button button-primary" value="Refresh Snippets List" name="refreshcachedata" id="refreshcachedata">
					<div>
						<i style="color:green;font-size:20px;font-family:calibri;display:none;" id="save_css_success">bLoyal Web snippets settings saved successfully.</i>
					</div>    
				</body>
			</html>
			<?php
		}
	}
}
