jQuery( window ).on(
	"load",
	function() {
		jQuery( "#loading_snippets_list" ).show();
	}
);
jQuery( document ).ready(
	function($) {
		var selectedDeviceCode = '';
		jQuery( "#refreshcachedata" ).click(
			function () {
				jQuery( "#save_css_success" ).hide();
				jQuery( "#loading_snippets_list" ).show();
				var data = {
					action: 'refresh_saved_data'
				};
				jQuery.post(
					ajaxurl,
					data,
					function(response) {
						$response_data = JSON.parse( response );
						if ($response_data['status'] == 'success') {
							fetch_snippets_list( selectedDeviceCode );
						}
					}
				);

			}
		);

		jQuery( "#refreshdevicecodes" ).click(
			function () {
				$( "#loading_snippets" ).show();
				$( "#save_configuration_success" ).hide();
				$( "#save_configuration_fail" ).hide();
				if ($( 'input[name=web_snippet_url]:checked' ).val() == 'custom') {
					if ( $( '#custom_web_snippet_url' ).val() == '' ) {
						$( '#custom_url_required' ).removeClass( 'displaynone' );
						return false;
					} else {
						$( '#custom_url_required' ).addClass( 'displaynone' );
					}
				}
				var isEmpty              = false;
				var domain_name_snippets = $( '#domain_name_snippets' ).val();
				var api_key_snippets     = $( '#api_key_snippets' ).val();
				var domainurl_snippets   = $( '#domainurl_snippets' ).val();
				if ( ! validateDomainNameSnippets( domain_name_snippets ) ) {
					isEmpty = true;
					$( "#loading_snippets" ).hide();
				}

				if ( ! validateApiKeySnippets( api_key_snippets ) ) {
					isEmpty = true;
					$( "#loading_snippets" ).hide();
				}

				if ( isEmpty ) {
					$( "#getAccessKeyLoaderSnippets" ).hide();
					$( "#getAccessKeyGeneratedSnippets" ).hide();
					$( "#loading_snippets" ).hide();
					return false;
				}

				var access_key_snippets            = $( '#accesskeysnippets' ).val();
				var is_custom_web_snippet_url_used = $( 'input[name=web_snippet_url]:checked' ).val();
				var snippetscustomgridapiurl       = $( '#snippetscustomgridapiurl' ).val();
				var data                           = {
					action: 'check_snippets_access_key_before_save',
					post_domain_name_snippets: domain_name_snippets,
					post_api_key_snippets: api_key_snippets,
					post_domainurl_snippets: domainurl_snippets,
					post_access_key_snippets: access_key_snippets,
					post_is_custom_web_snippet_url_used: is_custom_web_snippet_url_used,
					post_snippetscustomgridapiurl: snippetscustomgridapiurl,
				};

				var Contaxt_response_snippets = $.post(
					ajaxurl,
					data,
					function(response) {
						$snippets_access_key_status = JSON.parse( response );
						snippets_status             = $snippets_access_key_status['is_access_key_available'];

					}
				)

				.done(
					function() {
						var data = {
							action: 'save_configuration_data',
							post_domain_name_snippets: $( '#domain_name_snippets' ).val(),
							post_api_key_snippets: $( '#api_key_snippets' ).val(),
							post_domain_url_snippets: $( '#domainurl_snippets' ).val(),
							post_access_key_snippets: $( '#accesskeysnippets' ).val(),
							post_radio_web_snippet_url: $( 'input[name=web_snippet_url]:checked' ).val(),
							post_custom_web_snippet_url: $( '#custom_web_snippet_url' ).val(),
							post_default_device: $( '#default_device_select' ).val(),
							post_use_wordpress_login: $( "#usewordpresslogin" ).is( ':checked' ),
							post_use_bloyal_login: $( "#usebloyallogin" ).is( ':checked' ),
							post_snippetscustomgridapiurl: $( '#snippetscustomgridapiurl' ).val(),
							post_snippetscustomloyaltyengineapiurl: $( '#snippetscustomloyaltyengineapiurl' ).val(),
							post_snippetcustomwebsnippetapiurl: $( '#snippetcustomwebsnippetapiurl' ).val(),
							post_snippetcustomwebsnippethtmlapiurl: $( '#snippetcustomwebsnippethtmlapiurl' ).val(),
							post_bloyal_enable_disable: $( '#log_id' ).val(),
							post_page : $( '#page_id' ).val()

						};

						if ($( "#usewordpresslogin" ).is( ':checked' ) == true) {
							$( '#usewordpresslogin' ).prop( 'checked', true );
							$( '#usebloyallogin' ).prop( 'checked', false );
						} else {
							$( '#usebloyallogin' ).prop( 'checked', true );
							$( '#usewordpresslogin' ).prop( 'checked', false );
						}

						if ($( '#accesskeysnippets' ).val() == '' ) {
							$( "#loading_snippets" ).hide();
							location.href = location.href;
							$( "#refreshdevicesmsg" ).show();
						}

						if ($( '#accesskeysnippets' ).val() ) {
							$.post(
								ajaxurl,
								data,
								function(response) {
									$response_data = JSON.parse( response );
									$( "#loading_snippets" ).hide();
									$( "#refreshdevicesmsg" ).show();
								}
							);
						} else {
							$( "#loading_snippets" ).hide();
							$( "#refreshdevicesmsg" ).show();
						}
					}
				);
			}
		);

		$( "#custom_web_snippet_url" ).attr( 'readonly','readonly' );
		if (document.getElementById( "snippets_custom_url_display" )) {
			document.getElementById( "snippets_custom_url_display" ).style.display = "none";
		}
		$.post(
			ajaxurl,
			{ action: 'get_configuration_details' },
			function(response) {
				// console.log(response);
				$response_data    = JSON.parse( response );
				// console.log($response_data);
				selectedDeviceCode = $response_data['default_device'];
				if ($response_data['domain_url_snippets']) {
					$( '#domainurl_snippets' ).val( $response_data['domain_url_snippets'] );
				}
				if ( $response_data['domain_name_snippets']) {
					$( '#domain_name_snippets' ).val( $response_data['domain_name_snippets'] );
				}
				if ($response_data['api_key_snippets']) {
					$( '#api_key_snippets' ).val( $response_data['api_key_snippets'] );
				}
				if ($response_data['access_key_snippets']) {
					$( '#access_key_row_snippets' ).show();
					$( "#default_device_row" ).show();
					$( '#accesskeysnippets' ).val( $response_data['access_key_snippets'] );
				}
				if ($response_data['web_snippet_api_radio'] == 'custom') {
					$( '#custom' ).attr( 'checked','checked' );
					$( "#custom_web_snippet_url" ).attr( 'readonly',false );
					if (typeof snippets_custom_url_display !== 'undefined') {
						document.getElementById( "snippets_custom_url_display" ).style.display = "block";
					}
					if (typeof show_advance_settings_snippets !== 'undefined') {
						document.getElementById( "show_advance_settings_snippets" ).style.display = "block";
					}

				} else {
					if (typeof show_advance_settings_snippets !== 'undefined') {
						document.getElementById( "snippets_custom_url_display" ).style.display = "none";
					}
				}
				if ($response_data['custom_web_snippet_api_url']) {
					$( '#custom_web_snippet_url' ).val( $response_data['custom_web_snippet_api_url'] );
				}
				if ($response_data['snippets_custom_grid_apiurl']) {
					$( '#snippetscustomgridapiurl' ).val( $response_data['snippets_custom_grid_apiurl'] );
				}
				// new

				if ($response_data['page_id']) {
					$( '#page_id' ).val( $response_data['page_id'] );
				}

				// end new
				if ($response_data['snippets_custom_loyaltyengine_apiurl']) {
					$( '#snippetscustomloyaltyengineapiurl' ).val( $response_data['snippets_custom_loyaltyengine_apiurl'] );
				}
				if ($response_data['snippet_custom_websnippet_apiurl']) {
					$( '#snippetcustomwebsnippetapiurl' ).val( $response_data['snippet_custom_websnippet_apiurl'] );
				}
				if ($response_data['snippet_custom_websnippethtml_apiurl']) {
					$( '#snippetcustomwebsnippethtmlapiurl' ).val( $response_data['snippet_custom_websnippethtml_apiurl'] );
				}
				if ($response_data['web_snippet_api_radio'] == 'standard') {
					$( '#standard' ).attr( 'checked','checked' );
					$( "#custom_web_snippet_url" ).attr( 'readonly','readonly' );
				}
				if ($response_data['use_wordpress_login'] == 'true') {
					$( '#usewordpresslogin' ).attr( 'checked','checked' );
				}
				if ($response_data['use_bloyal_login'] == 'true') {
					$( '#usebloyallogin' ).attr( 'checked','checked' );
				}
				if (typeof $response_data['devices'] !== 'undefined' && $response_data['devices'].length > 0) {
					$.each(
						$response_data['devices'],
						function (i, item) {
							$( '#default_device_select' ).append(
								$(
									'<option>',
									{
										value: item.Code,
										text : item.Name
									}
								)
							);
							$( '#default_device_select_shortcode_page' ).append(
								$(
									'<option>',
									{
										value: item.Code,
										text : item.Name
									}
								)
							);
						}
					);
					$( '#default_device_select' ).val( $response_data['default_device'] );
					$( '#default_device_select_shortcode_page' ).val( $response_data['default_device'] );
						var current_page = window.location.href;
						page_name        = current_page.split( "page=" );
					if (page_name[1] == "bloyal_snippets") {
						fetch_snippets_list( $response_data['default_device'] );
					}
				}
			}
		);

		function get_access_key_snippets(domain_name_snippets, api_key_snippets, domainurl_snippets, is_custom_web_snippet_url_used, snippetscustomgridapiurl) {

			document.getElementById( "snippetAccesskeyVerification" ).value = 0;
			verifySnippetsAccessKey();
			var data = {
				action: 'get_access_key_by_apikey_snippets',
				post_domain_name_snippets: domain_name_snippets,
				post_api_key_snippets: api_key_snippets,
				post_domain_url_snippets: domainurl_snippets,
				post_is_custom_web_snippet_url_used: is_custom_web_snippet_url_used,
				post_snippetscustomgridapiurl: snippetscustomgridapiurl
			};

			$.post(
				ajaxurl,
				data,
				function(response) {
					$response_data = JSON.parse( response.toString() );
					if ( $response_data['is_access_key_available'] == true ) {
						$( "#accessKeySuccessSnippets" ).hide();
						$( "#getAccessKeyLoaderSnippets" ).hide();
						$( "#key_in_use_snippets" ).hide();
						$( "#error_msg_snippets" ).hide();
						$( "#accessKeyFailSnippets" ).hide();
						$( "#getAccessKeyGeneratedSnippets" ).show();
						$( "#invalid_data" ).hide();
						$( "#access_key_row_snippets" ).show();
						$( "#default_device_row" ).show();
						$( "#key_in_use_snippets" ).hide();
						$( '#accesskeysnippets' ).val( $response_data['access_key_snippets'] );
						$( "#loading_snippets" ).hide();
						$( "#refreshdevicesmsg" ).hide();
					} else if ( $response_data['is_key_in_use'] == true ) {
						$( "#getAccessKeyLoaderSnippets" ).hide();
						$( "#getAccessKeyGeneratedSnippets" ).hide();
						$( "#invalid_data" ).hide();
						$( "#error_msg_snippets" ).show();
						$( "#accessKeySuccessSnippets" ).hide();
						$( "#key_in_use_snippets" ).show();
						$( "#loading_snippets" ).hide();
						$( "#refreshdevicesmsg" ).hide();
					} else {
						$( "#getAccessKeyLoaderSnippets" ).hide();
						$( "#getAccessKeyGeneratedSnippets" ).hide();
						$( "#access_key_row_snippets" ).hide();
						$( "#default_device_row" ).hide();
						$( "#accessKeySuccessSnippets" ).hide();
						$( "#key_in_use_snippets" ).hide();
						$( "#error_msg_snippets" ).show();
						if ($response_data['error_msg_snippets'] != null) {
							$( '#invalid_data_snippet' ).text( $response_data['error_msg_snippets'] );
							$( "#loading_snippets" ).hide();
						} else {
							$( '#invalid_data_snippet' ).text( "Client not valid for this service cluster." );
							$( "#loading_snippets" ).hide();
						}

						$( "#invalid_data_snippet" ).show();
						$( "#loading_snippets" ).hide();
						$( "#refreshdevicesmsg" ).hide();
					}
				}
			);
		};

		function test_access_key_snippets(domain_name_snippets, api_key_snippets, domainurl_snippets, access_key_snippets, is_custom_web_snippet_url_used, snippetscustomgridapiurl) {
			var data = {
				action: 'test_access_key_by_apikey_snippets',
				post_domain_name_snippets: domain_name_snippets,
				post_api_key_snippets: api_key_snippets,
				post_domain_url_snippets: domainurl_snippets,
				post_access_key_snippets: access_key_snippets,
				post_is_custom_web_snippet_url_used: is_custom_web_snippet_url_used,
				post_snippetscustomgridapiurl: snippetscustomgridapiurl
			};
			$.post(
				ajaxurl,
				data,
				function(response) {

					$response_data = JSON.parse( response );
					if ( $response_data['is_access_key_available'] == true ) {
						$( "#loading_snippets" ).hide();
						$( "#getAccessKeyGeneratedSnippets" ).hide();
						$( "#key_in_use_snippets" ).hide();
						$( "#invalid_data_snippet" ).hide();
						$( "#accessKeySuccessSnippets" ).show();
						$( "#accessKeyFailSnippets" ).hide();
						$( "#refreshdevicesmsg" ).hide();
						$( "#default_device_row" ).show();
						document.getElementById( "snippetAccesskeyVerification" ).value = 1;
						verifySnippetsAccessKey();
					} else if ($response_data['wrong_configuration_snippets'] == true) {
						$( "#loading_snippets" ).hide();
						$( "#getAccessKeyLoaderSnippets" ).hide();
						$( "#getAccessKeyGeneratedSnippets" ).hide();
						$( "#accessKeySuccessSnippets" ).hide();
						$( '#accessKeyFailSnippets' ).text( $response_data['wrong_configuration_snippets_error_msg'] );
						$( "#invalid_data_snippet" ).hide();
						$( "#refreshdevicesmsg" ).hide();
						$( "#accessKeyFailSnippets" ).show();
						document.getElementById( "snippetAccesskeyVerification" ).value = 0;
						verifySnippetsAccessKey();
					} else {
						$( "#loading_snippets" ).hide();
						$( "#getAccessKeyGeneratedSnippets" ).hide();
						$( "#key_in_use_snippets" ).hide();
						$( "#accessKeyFailSnippets" ).show();
						$( "#accessKeySuccessSnippets" ).hide();
						$( "#refreshdevicesmsg" ).hide();
						if ($response_data['error_msg_snippets_api'] != null) {
							$( "#invalid_data_snippet" ).hide();
							$( '#accessKeyFailSnippets' ).text( $response_data['error_msg_snippets_api'] );
							$( "#loading_snippets" ).hide();
						} else {
							$( "#invalid_data_snippet" ).hide();
							$( '#accessKeyFailSnippets' ).text( "Access key is not generated. Please click Lock button to generate access key." );
							$( "#loading_snippets" ).hide();
						}
						document.getElementById( "snippetAccesskeyVerification" ).value = 0;
						verifySnippetsAccessKey();
					}
				}
			);
		};

		function validateDomainNameSnippets(domain_name_snippets) {
			if ( domain_name_snippets == '' ) {
				$( '#domainRequired' ).removeClass( 'displaynone' );
				return false;
			} else {
				$( '#domainRequired' ).addClass( 'displaynone' );
				return true;
			}
		}

		function validateApiKeySnippets(api_key_snippets) {
			if ( api_key_snippets == '' ) {
				$( '#apiKeyRequired' ).removeClass( 'displaynone' );
				return false;
			} else {
				$( '#apiKeyRequired' ).addClass( 'displaynone' );
				return true;
			}
		}

		$( '#getaccesskeysnippets' ).click(
			function(){
				$( "#loading_snippets" ).show();
				$( "#accessKeySuccessSnippets" ).hide();
				$( "#getAccessKeyGeneratedSnippets" ).hide();
				$( "#key_in_use_snippets" ).hide();
				$( "#error_msg_snippets" ).hide();
				$( "#invalid_data_snippet" ).hide();
				$( "#save_configuration_success" ).hide();
				$( "#save_configuration_fail" ).hide();
				$( "#accessKeyFailSnippets" ).hide();
				$( "#getAccessKeyLoaderSnippets" ).show();
				var isEmpty                        = false;
				var domain_name_snippets           = $( '#domain_name_snippets' ).val();
				var api_key_snippets               = $( '#api_key_snippets' ).val();
				var domainurl_snippets             = $( '#domainurl_snippets' ).val();
				var is_custom_web_snippet_url_used = $( 'input[name=web_snippet_url]:checked' ).val();
				var snippetscustomgridapiurl       = $( '#snippetscustomgridapiurl' ).val();

				if ( ! validateDomainNameSnippets( domain_name_snippets ) ) {
					isEmpty = true;
					$( "#loading_snippets" ).hide();
				}

				if ( ! validateApiKeySnippets( api_key_snippets ) ) {
					isEmpty = true;
					$( "#loading_snippets" ).hide();
				}

				if ( isEmpty ) {
					$( "#getAccessKeyLoaderSnippets" ).hide();
					$( "#getAccessKeyGeneratedSnippets" ).hide();
					$( "#loading_snippets" ).hide();
					return false;
				} else {
					get_access_key_snippets( domain_name_snippets, api_key_snippets, domainurl_snippets, is_custom_web_snippet_url_used, snippetscustomgridapiurl );
				}
			}
		);

		$( '#testaccesskeysnippets' ).click(
			function(){loading_snippets

				$( "#loading_snippets" ).show();
				var isEmpty                        = false;
				var domain_name_snippets           = $( '#domain_name_snippets' ).val();
				var api_key_snippets               = $( '#api_key_snippets' ).val();
				var domainurl_snippets             = $( '#domainurl_snippets' ).val();
				var access_key_snippets            = $( '#accesskeysnippets' ).val();
				var is_custom_web_snippet_url_used = $( 'input[name=web_snippet_url]:checked' ).val();
				var snippetscustomgridapiurl       = $( '#snippetscustomgridapiurl' ).val();

				if ( ! validateDomainNameSnippets( domain_name_snippets ) ) {
					isEmpty = true;
					$( "#loading_snippets" ).hide();
				}

				if ( ! validateApiKeySnippets( api_key_snippets ) ) {
					isEmpty = true;
					$( "#loading_snippets" ).hide();
				}

				if ( isEmpty ) {
					$( "#getAccessKeyLoaderSnippets" ).hide();
					$( "#getAccessKeyGeneratedSnippets" ).hide();
					$( "#loading_snippets" ).hide();
					return false;
				} else {
					test_access_key_snippets( domain_name_snippets, api_key_snippets, domainurl_snippets, access_key_snippets, is_custom_web_snippet_url_used, snippetscustomgridapiurl );
				}
			}
		);

		$( '#saveaccesskeysnippets' ).click(
			function(){

				$( "#loading_snippets" ).show();
				$( "#save_configuration_success" ).hide();
				$( "#save_configuration_fail" ).hide();
				if ($( 'input[name=web_snippet_url]:checked' ).val() == 'custom') {
					if ( $( '#custom_web_snippet_url' ).val() == '' ) {
						$( '#custom_url_required' ).removeClass( 'displaynone' );
						return false;
					} else {
						$( '#custom_url_required' ).addClass( 'displaynone' );
					}
				}
				var isEmpty              = false;
				var domain_name_snippets = $( '#domain_name_snippets' ).val();
				var api_key_snippets     = $( '#api_key_snippets' ).val();
				var domainurl_snippets   = $( '#domainurl_snippets' ).val();
				if ( ! validateDomainNameSnippets( domain_name_snippets ) ) {
					isEmpty = true;
					$( "#loading_snippets" ).hide();
				}

				if ( ! validateApiKeySnippets( api_key_snippets ) ) {
					isEmpty = true;
					$( "#loading_snippets" ).hide();
				}

				if ( isEmpty ) {
					$( "#getAccessKeyLoaderSnippets" ).hide();
					$( "#getAccessKeyGeneratedSnippets" ).hide();
					$( "#loading_snippets" ).hide();
					return false;
				}

				var access_key_snippets            = $( '#accesskeysnippets' ).val();
				var is_custom_web_snippet_url_used = $( 'input[name=web_snippet_url]:checked' ).val();
				var snippetscustomgridapiurl       = $( '#snippetscustomgridapiurl' ).val();
				var data                           = {
					action: 'check_snippets_access_key_before_save',
					post_domain_name_snippets: domain_name_snippets,
					post_api_key_snippets: api_key_snippets,
					post_domainurl_snippets: domainurl_snippets,
					post_access_key_snippets: access_key_snippets,
					post_is_custom_web_snippet_url_used: is_custom_web_snippet_url_used,
					post_snippetscustomgridapiurl: snippetscustomgridapiurl,
				};

				var Contaxt_response_snippets = $.post(
					ajaxurl,
					data,
					function(response) {
						$snippets_access_key_status = JSON.parse( response );
						snippets_status             = $snippets_access_key_status['is_access_key_available'];

					}
				)

				.done(
					function() {
						var data = {
							action: 'save_configuration_data',
							post_domain_name_snippets: $( '#domain_name_snippets' ).val(),
							post_api_key_snippets: $( '#api_key_snippets' ).val(),
							post_domain_url_snippets: $( '#domainurl_snippets' ).val(),
							post_access_key_snippets: $( '#accesskeysnippets' ).val(),
							post_radio_web_snippet_url: $( 'input[name=web_snippet_url]:checked' ).val(),
							post_custom_web_snippet_url: $( '#custom_web_snippet_url' ).val(),
							post_default_device: $( '#default_device_select' ).val(),
							post_use_wordpress_login: $( "#usewordpresslogin" ).is( ':checked' ),
							post_use_bloyal_login: $( "#usebloyallogin" ).is( ':checked' ),
							post_snippetscustomgridapiurl: $( '#snippetscustomgridapiurl' ).val(),
							post_snippetscustomloyaltyengineapiurl: $( '#snippetscustomloyaltyengineapiurl' ).val(),
							post_snippetcustomwebsnippetapiurl: $( '#snippetcustomwebsnippetapiurl' ).val(),
							post_snippetcustomwebsnippethtmlapiurl: $( '#snippetcustomwebsnippethtmlapiurl' ).val(),
							post_page : $( '#page_id' ).val()

						};
						$( "#refreshdevicesmsg" ).hide();
						if ($( "#usewordpresslogin" ).is( ':checked' ) == true) {
							$( '#usewordpresslogin' ).prop( 'checked', true );
							$( '#usebloyallogin' ).prop( 'checked', false );
						} else {
							$( '#usebloyallogin' ).prop( 'checked', true );
							$( '#usewordpresslogin' ).prop( 'checked', false );
						}

						if ($( '#accesskeysnippets' ).val() == '' ) {
							$( "#save_configuration_fail" ).text( "Access key is not generated. Please click Lock button to generate Access Key." );
							$( "#save_configuration_fail" ).show();
							$( "#loading_snippets" ).hide();
						}

						if ($( '#accesskeysnippets' ).val() ) {
							$.post(
								ajaxurl,
								data,
								function(response) {
									$response_data = JSON.parse( response );
									$( "#key_in_use_snippets" ).hide();
									$( "#save_configuration_success" ).show();
									$( "#loading_snippets" ).hide();
								}
							);
						} else {
							$( "#save_configuration_fail" ).show();
							$( "#loading_snippets" ).hide();
						}
					}
				);
			}
		);

		$( '#domain_name_snippets' ).on(
			'keyup blur',
			function(e){
				var domain_name_snippets = $( '#domain_name_snippets' ).val();
				if ( ! validateDomainNameSnippets( domain_name_snippets ) ) {
					return false;
				}
			}
		);

		$( '#api_key_snippets' ).on(
			'keyup blur',
			function(){
				var api_key_snippets = $( '#api_key_snippets' ).val();
				if ( ! validateApiKeySnippets( api_key_snippets ) ) {
					return false;
				}
			}
		);

		$( '#custom_web_snippet_url' ).on(
			'keyup blur',
			function(){
				if ( $( '#custom_web_snippet_url' ).val() == '' ) {
					$( '#custom_url_required' ).removeClass( 'displaynone' );
					return false;
				} else {
					$( '#custom_url_required' ).addClass( 'displaynone' );
				}
			}
		);

		var web_snippet_url_radio = $( "input:radio[name=web_snippet_url]" );
		web_snippet_url_radio.on(
			"change",
			function() {
				$( "#custom_web_snippet_url" ).removeAttr( 'readonly' );
				if ($( this ).val() == 'standard') {
					$( '#custom_url_required' ).addClass( 'displaynone' );
					$( "#custom_web_snippet_url" ).attr( 'readonly','readonly' );
				}
			}
		);

		$( '#default_device_select_shortcode_page' ).on(
			'change',
			function(){
				var selected_device = $( '#default_device_select_shortcode_page option:selected' ).val();
				fetch_snippets_list( selected_device );
			}
		);

		function fetch_snippets_list(selected_device){
			$( "#loading_snippets_list" ).show();
			var data = {
				action: 'fetch_snippets_associated_with_device',
				post_selected_device: selected_device
			};
			$.post(
				ajaxurl,
				data,
				function(response) {

					$response_data = JSON.parse( response );
					console.log($response_data);
					$( "#snippets_error" ).hide();
					if ($response_data['status'] == 'error') {
						$( '#snippets_error' ).text( $response_data['snippets_list'] );
						$( "#loading_snippets_list" ).hide();
						$( "#snippets_error" ).show();
						return;
					}
					if('' === $response_data['access_key']){
						$( "#loading_snippets_list" ).hide();
						$( '#snippets_error' ).text('Device not activated. Please activate your Device.' );
						$( "#snippets_error" ).show();
						return;
					}
					if ($response_data['snippets_list']) {
						$( '#snippets_details_table td' ).parent().remove();
						$.each(
							$response_data['snippets_list'],
							function (i, item) {

								var snippet_shortcode_length = item.snippet_shortcode.length;
								var snippet_shortcode_id     = item.snippet_shortcode.slice( 1,snippet_shortcode_length - 1 );
								var snippet_shortcode_id     = item.snippet_shortcode;

								$( '#snippets_details_table tr:last' )
								.after(
									'<tr><td><input type="checkbox" class="ckb" id="' + item.ckb_code + '" /></td>' +
									'<td>' + item.snippet_title + '</td>' +
									'<td>' + item.snippet_code + '</td>' +
									'<td><input type = "text" id ="' + snippet_shortcode_id + '" value ="' + snippet_shortcode_id + '" readonly size = "50" ></td>' +
									// '<td>' + '<input id="'+snippet_shortcode_id+'" type="button" class= "button button-primary" value="Copy Shortcode" onclick="copyShortcode('+this.id+')">' + '</td>' +
									'<td>' + '<button id="' + snippet_shortcode_id + '" class= "button button-primary" value="' + snippet_shortcode_id + '" onClick="copyShortcode(this.id)">Copy</button>' + '</td>' +
									'</tr>'
								);
								$( "#ckb_all_snippets" ).prop( "checked",false );
								if (item.use_original_css == 1) {
									$( '#' + item.ckb_code ).prop( 'checked', true );
									// $("#ckb_all_snippets").prop("checked",true);
								} else {
									// $("#ckb_all_snippets").prop("checked",false);
									$( '#' + item.ckb_code ).prop( 'checked', false );
								}
								$( "#loading_snippets_list" ).hide();
							}
						);
					}
				}
			);
		}

		$( "#ckb_all_snippets" ).click(
			function () {
				$( ".ckb" ).prop( 'checked', $( this ).prop( 'checked' ) );
			}
		);

		$( ".ckb" ).change(
			function(){
				if ( ! $( this ).prop( "checked" )) {
					$( "#ckb_all_snippets" ).prop( "checked",false );
				}
			}
		);

		$( '#saveSnippetsUseCSSStatus' ).click(
			function(){
				jQuery( "#save_css_success" ).hide();
				jQuery( "#loading_snippets_list" ).show();
				checkedSnippets   = [];
				uncheckedSnippets = [];
				$( ".ckb" ).each(
					function() {
						if ($( this ).is( ":checked" )) {
							checkedSnippets.push( $( this ).attr( "id" ) );
						} else {
							uncheckedSnippets.push( $( this ).attr( "id" ) );
						}
					}
				);
				var data = {
					action: 'save_snippets_uss_css_status',
					post_checked_snippets: checkedSnippets,
					post_unchecked_snippets: uncheckedSnippets
				};
				$.post(
					ajaxurl,
					data,
					function(response) {
						$response_data = JSON.parse( response );
						jQuery( "#loading_snippets_list" ).hide();
						jQuery( "#save_css_success" ).show();
					}
				);
			}
		);
	}
);


function clearFieldsSnippets(){
	document.getElementById( "accesskeysnippets" ).value = "";
}

function verifySnippetsAccessKey() {
	var data = {
		action: 'save_snippets_accesskeyverification_data',
		post_access_key_verification_snippets: jQuery( '#snippetAccesskeyVerification' ).val(),
		post_custom_web_snippet_url_used: jQuery( 'input[name=web_snippet_url]:checked' ).val()
	};
	jQuery.post(
		ajaxurl,
		data,
		function(response) {
			$response_data = JSON.parse( response );
		}
	);
}

function copyShortcode(buttonID) {

	var copyText = document.getElementById( buttonID );
	copyText.select();
	document.execCommand( "Copy" );
}
