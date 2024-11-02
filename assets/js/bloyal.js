jQuery(document).ready(function($) {

	jQuery("#bloyal_refresh_cached_data").click(function () {

		$("#save_configuration_success").hide();

		$("#refresh_configuration_success").hide();

		$("#refresh_configuration_error").hide();

        jQuery("#loading").show();

        var data = {

            action: 'refresh_cached_data'

        };

        jQuery.post(ajaxurl, data, function(response) {

            $response_data = JSON.parse(response);

            if($response_data['status'] == 'success'){

                jQuery("#loading").hide();

                location.href = location.href;

              	$("#refresh_configuration_success").show();

            }

            else{

            	jQuery("#loading").hide();

            	$("#refresh_configuration_error").text($response_data['errorMessage']);

            	$("#refresh_configuration_error").show();

            }

        });



    });



	$.post(ajaxurl, { action: 'get_bloyal_configuration_details' }, function(response) {



		$response_data = JSON.parse(response);

		if($response_data['use_order_engine'] == 'true'){

			$('#useorderengine').prop('checked', true);

			} else {

			$('#useorderengine').prop('checked', false);

		}

		if($response_data['applied_shipping_charges'] == 'true'){

			$('#appliedshippingcharges').prop('checked', true);

			} else {

			$('#appliedshippingcharges').prop('checked', false);

		}

		if($response_data['applied_taxes'] == 'true'){

			$('#appliedtaxes').prop('checked', true);

			} else {

			$('#appliedtaxes').prop('checked', false);

		}

		if($response_data['domain_url']) {

			$('#domainurl').val($response_data['domain_url']);

		}

		if( $response_data['domain_name']) {

			$('#domainname').val($response_data['domain_name']);

		}

		if($response_data['api_key']) {

			$('#apikey').val($response_data['api_key']);

		}

		if($response_data['access_key']) {

			$('#access_key_row').show();

			$('#accesskey').val($response_data['access_key']);

			$('#adv_accesskey').val($response_data['access_key']);

			//if access key is available show remove button--\

			if($response_data['access_key'] === ""){

				$('#removeaccesskey').hide();

			} else {

				$('#removeaccesskey').show();

				

			}	

			//end

		}

		if($response_data['bloyal_click_and_collect_status']) {

			$('#bLoyal_bloyal_click_and_collect_status').val($response_data['bloyal_click_and_collect_status']);

		}



		if($response_data['bloyal_click_collect_label']) {

			$('#bloyal_bloyal_click_collect_label').val($response_data['bloyal_click_collect_label']);

		}



		if($response_data['click_collect_error']) {

			$('#bloyal_click_collect_error_title').val($response_data['click_collect_error']);

		}



		if($response_data['bloyal_apply_full_balance_giftcard']){

			$("#apply_full_balance_giftcard").val($response_data['bloyal_apply_full_balance_giftcard']);

			if($response_data['bloyal_apply_full_balance_giftcard'] == "true" ){

				$('#giftcard_apply_in_increment_of_section').hide();

			}

		}

		if($response_data['bloyal_apply_full_balance_loyalty'] ){

			$("#apply_full_balance_loyalty").val($response_data['bloyal_apply_full_balance_loyalty']);

			if($response_data['bloyal_apply_full_balance_loyalty'] == "true" ){

				$('#loyalty_apply_in_increment_of_section').hide();

			}

		}



		if($response_data['bloyal_apply_in_increment_of_giftcard'] ){

			$("#apply_in_increment_of_giftcard").val($response_data['bloyal_apply_in_increment_of_giftcard']);

		}



		if($response_data['bloyal_apply_in_increment_of_loyalty'] ){

			$("#apply_in_increment_of_loyalty").val($response_data['bloyal_apply_in_increment_of_loyalty']);

		}





		if($response_data['rowcount']) {

			var rowcount = $response_data['rowcount'];

			var Newrowcnt = $('#getcount').val();

			for(count = 0; count < rowcount; count++){

				$('#shippingcarrier'+count).val($response_data['shipping_carrier'][count]);

				var selectedopt = $response_data['shipping_carrier'][count];

				if( document.getElementById("arr"+selectedopt)){

					var arrData     =   document.getElementById("arr"+selectedopt).value;

					var arrData     =   arrData.split(",");

					jQuery(".shippingservice"+count).html("");

					for(i=0;i<arrData.length;i++){

						if(i<(arrData.length-1)){

							jQuery(".shippingservice"+count).append('<option value='+arrData[i]+'>'+arrData[i]+'</option>');

						}

					}

					$('#shippingservice'+count).val($response_data['shipping_service'][count]);

				}

			}



		}



		if($response_data['bloyal_pickup_row_count']) {

			var rowcount 			= $response_data['bloyal_pickup_row_count'];

			var Newrowcnt 			= $('#getpickupcount').val();

			for(count = 0; count < Newrowcnt; count++){

				var response = $response_data['bloyal_shipping_pickup'];

				var key 	= document.getElementById('count_'+count).value;

				if(response[key]){

					$('#'+key).val(response[key]);

				}

			}

		}

		if($response_data['bloyal_tender_payments_mapping']){

			$.each($response_data['bloyal_tender_payments_mapping'], function( key, value ) {

				$('#'+key+'paymentTendor').trigger('change');

			});

		}



		if($response_data['gift_card_tender_code']) {

			$('#giftcardtender').val($response_data['gift_card_tender_code']);

		}

		if($response_data['bloyal_snippet_code']) {

			$('#bloyal_snippet_code').val($response_data['bloyal_snippet_code']);

		}



		if($response_data['bloyal_snippet_informational_code']) {

			$('#bloyal_snippet_informational_code').val($response_data['bloyal_snippet_informational_code']);

		}

		if($response_data['bloyal_snippet_confirmation_code']) {

			$('#bloyal_snippet_confirmation_code').val($response_data['bloyal_snippet_confirmation_code']);

		}

		if($response_data['bloyal_snippet_problem_code']) {

			$('#bloyal_snippet_problem_code').val($response_data['bloyal_snippet_problem_code']);

		}



		if($response_data['loyalty_dollars_tender_code']) {

			$('#loyaltydollarstender').val($response_data['loyalty_dollars_tender_code']);

		}

		if($response_data['on_account_tender_code']) {

			$('#onaccounttender').val($response_data['on_account_tender_code']);

		}

		if($response_data['custom_grid_api_url_name']) {

			$('#customgridapiurl').val($response_data['custom_grid_api_url_name']);

		}

		if($response_data['custom_loyaltyengine_api_url_name']) {

			$('#customloyaltyengineapiurl').val($response_data['custom_loyaltyengine_api_url_name']);

		}

		if($response_data['custom_orderengine_api_url_name']) {

			$('#customorderengineapiurl').val($response_data['custom_orderengine_api_url_name']);

		}

		if($response_data['custompayment_api_url_name']) {

			$('#custompaymentapiurl').val($response_data['custompayment_api_url_name']);

		}

		if($response_data['custom_logging_api_url_name']) {

			$('#customloggingapiurl').val($response_data['custom_logging_api_url_name']);

		}

		if($response_data['is_custom_api_url_used'] == 'true') {

			$('#bloyal_custom_url').val($response_data['is_custom_api_url_used']);

			jQuery("#bloyal_custom_url").prop('checked', true);

			if (typeof show_advance_settings !== 'undefined') {

				document.getElementById("show_advance_settings").style.display = "block";

			}

			if (typeof bloyal_custom_url_display !== 'undefined') {

				document.getElementById("bloyal_custom_url_display").style.display = "block";

			}

		}

		else{

			jQuery("#bloyal_custom_url").prop('checked', false);

			jQuery("#bloyal_standard_url").prop('checked', true);

			if(document.getElementById("bloyal_custom_url_display"))

			document.getElementById("bloyal_custom_url_display").style.display = "none";

		}

		if($response_data['use_order_engine'] == 'true'){

			jQuery("#appliedshippingcharges").prop('disabled', false);

			jQuery("#appliedtaxes").prop('disabled', false);

		}

		else{

			jQuery("#appliedshippingcharges").prop('checked', false);

			jQuery("#appliedtaxes").prop('checked', false);

			jQuery("#appliedshippingcharges").prop('disabled', true);

			jQuery("#appliedtaxes").prop('disabled', true);

		}

		if($response_data['bloyal_display_DOB'] == 'true'){

			jQuery("#isDisplayDOB").prop('checked', true);

		}else{

			jQuery("#isRequiredDOB").prop('disabled', true);

		}

		if($response_data['bloyal_required_DOB'] == 'true'){

			jQuery("#isRequiredDOB").prop('checked', true);

		}

		if($response_data['bloyal_display_Phone'] == 'true'){

			jQuery("#isDisplayPhone").prop('checked', true);

		}else{

			jQuery("#isRequiredPhone").prop('disabled', true);

		}

		if($response_data['bloyal_required_Phone'] == 'true'){

			jQuery("#isRequiredPhone").prop('checked', true);

		}

		if($response_data['bloyal_display_Email'] == 'true'){

			jQuery("#isDisplayEmail").prop('checked', true);

		}else{

			jQuery("#isRequiredEmail").prop('disabled', true);

		}

		if($response_data['bloyal_display_order_comments'] == 'true'){

			jQuery("#isDisplayOrderComments").prop('checked', true);

		}

		if($response_data['bloyal_required_Email'] == 'true'){

			jQuery("#isRequiredEmail").prop('checked', true);

		}

		if($response_data['bloyal_display_address_Book'] == 'true'){

            jQuery("#isDisplayAddressBook").prop('checked', true);

        }

        if($response_data['loyalty_block'] == 'true'){

			jQuery("#loyaltyblock").prop('checked', true);

		}

		if($response_data['bloyal_log_enable_disable'] ){

			$("#log_id").val($response_data['bloyal_log_enable_disable']);

			if($response_data['bloyal_log_enable_disable'] == 'true'){

				jQuery("#log_id").prop('checked', true);

			}else{

				jQuery("#log_id").prop('checked', false);

			}

		}

	});



	$('#isDisplayDOB').click(function(){

		if ($(this).is(':checked')) {

            jQuery("#isRequiredDOB").prop('disabled', false);

        }else{

        	jQuery("#isRequiredDOB").prop('disabled', true);

        	jQuery("#isRequiredDOB").prop('checked', false);

        }

	});



	$('#isDisplayPhone').click(function(){

		if ($(this).is(':checked')) {

            jQuery("#isRequiredPhone").prop('disabled', false);

        }else{

        	jQuery("#isRequiredPhone").prop('disabled', true);

        	jQuery("#isRequiredPhone").prop('checked', false);

        }

	});



	$('#isDisplayEmail').click(function(){

		if ($(this).is(':checked')) {

            jQuery("#isRequiredEmail").prop('disabled', false);

        }else{

        	jQuery("#isRequiredEmail").prop('disabled', true);

        	jQuery("#isRequiredEmail").prop('checked', false);

        }

	});



	function get_access_key(domainName, apikey, domainurl, is_custom_api_url_used, custom_grid_api_url) {

		var accesskeyverification = document.getElementById('accesskeyverification');

		if (typeof accesskeyverification !== 'undefined' && accesskeyverification !== null ) {

		    document.getElementById("accesskeyverification").value = 0;

	    }

		verifyAccessKey();

		var data = {

			action: 'get_bloyal_access_key_by_apikey',

			post_domain_name: domainName,

			post_api_key: apikey,

			post_domain_url: domainurl,

			post_custom_api_url_used: is_custom_api_url_used,

			post_custom_grid_api_url: custom_grid_api_url

		};



		$.post(ajaxurl, data, function(response) {

			if(response != null || response !=''){

				$response_data = JSON.parse(response);

				

				if( $response_data['is_access_key_available'] == true ) {

					var bloyal_urls = JSON.parse($response_data['bloyal_urls']);

					show_hide_access_key_configuration_data()

					$("#getAccessKeyGenerated").show();

					$("#access_key_row").show();

					$('#accesskey').val($response_data['access_key']);

					$('#adv_accesskey').val($response_data['access_key']);

					$('#domainurl').val(bloyal_urls['DirectorUrl']);

					$('#customgridapiurl').val(bloyal_urls['GridApiUrl']);

					$('#customloyaltyengineapiurl').val(bloyal_urls['LoyaltyEngineApiUrl']);

					$('#customorderengineapiurl').val(bloyal_urls['OrderEngineApiUrl']);

					$('#custompaymentapiurl').val(bloyal_urls['PaymentApiUrl']);

					$('#customloggingapiurl').val(bloyal_urls['LoggingApiUrl']);

				}else if( $response_data['is_key_in_use'] == true ){

					show_hide_access_key_configuration_data();

					$("#key_in_use").show();

				}else {

					show_hide_access_key_configuration_data();

					$("#access_key_row").hide();

					$("#accessKeyFail").hide();

					if($response_data['error_msg_api'] != null){

						$('#invalid_data').text($response_data['error_msg_api']);

						

					}else{

						$('#invalid_data').text("Client not valid for this service cluster.");

						

					}

					$("#invalid_data").show();

					

				}	

			}else{

				show_hide_access_key_configuration_data();

				$('#invalid_data').text("Something went wrong please contact to administartor.");

			}

		});

	};

	function show_hide_access_key_configuration_data(){

		$("#accessKeySuccess").hide();

		$("#getAccessKeyLoader").hide();

		$("#getAccessKeyGenerated").hide();

		$("#invalid_data").hide();

		$("#loading").hide();

	}



    function test_access_key(domainName, apikey, domainurl, accesskey, is_custom_api_url_used, custom_grid_api_url) {

		var data = {

			action: 'test_bloyal_access_key_by_apikey',

			post_domain_name: domainName,

			post_api_key: apikey,

			post_domain_url: domainurl,

			post_access_key: accesskey,

			post_custom_api_url_used: is_custom_api_url_used,

			post_custom_grid_api_url: custom_grid_api_url

		};

		$.post(ajaxurl, data, function(response) {

			$response_data = JSON.parse(response);

			if($response_data['is_access_key_available'] == true){

				$("#accessKeyFail").hide();

				$("#accessKeySuccess").show();

				hide_show_test_access_configuration(1);

			}else if ($response_data['wrong_configuration'] == true) {

				$('#accessKeyFail').text($response_data['wrong_configuration_error_msg']);

				$("#accessKeyFail").show();

				hide_show_test_access_configuration(0);

			}

			else{

				if($response_data['error_msg_api'] != null){

					$('#accessKeyFail').text($response_data['error_msg_api']);

				}else{

					$('#accessKeyFail').text("Access key is not generated. Please click Lock button to generate access key.");

				}

				$("#accessKeyFail").show();

				$("#accessKeySuccess").hide();

				hide_show_test_access_configuration(0);

			}

		});

	};

	function hide_show_test_access_configuration(data){

		$('#loading_for_test').hide();

		$('#getAccessKeyLoader').hide();

		$('#getAccessKeyGenerated').hide();

		$('#key_in_use').hide();

		$('#invalid_data').hide();

		var elem = document.getElementById('accesskeyverification');

		if(typeof elem !== 'undefined' && elem !== null) {

		  elem.value = data;

		}

		

		

		verifyAccessKey();

	}

	function verifyAccessKey() {



		var data = {

			action: 'save_bloyal_accesskeyverification_data',

			post_access_key_verification: $('#accesskeyverification').val(),

			post_bloyal_custom_url: $("#bloyal_custom_url").is(':checked')

		};

		$.post(ajaxurl, data, function(response) {

			$response_data = JSON.parse(response);

		});

	}

	function validateDomainName(domainName) {

		if ( domainName == '' ) {

			$('#domainRequired').removeClass('displaynone');

			return false;

			} else {

			$('#domainRequired').addClass('displaynone');

			return true;

		}

	}



	function validateApiKey(apikey) {

		if ( apikey == '' ) {

			$('#apiKeyRequired').removeClass('displaynone');

			return false;

			} else {

			$('#apiKeyRequired').addClass('displaynone');

			return true;

		}

	}



	$('#getaccesskey').click(function(){

		$("#refresh_configuration_success").hide();

		$("#refresh_configuration_error").hide();

		$('#loading').show();

		$("#accessKeySuccess").hide();

		$("#invalid_data").hide();

		$("#getAccessKeyGenerated").hide();

		$("#key_in_use").hide();

		$("#accessKeyFail").hide();

		$("#getAccessKeyLoader").show();

		$("#save_configuration_success").hide();

		$("#save_configuration_fail").hide();

		var isEmpty = false;

		var domainName = $('#domainname').val();

		var apikey = $('#apikey').val();

		var domainurl = $('#domainurl').val();

		var is_custom_api_url_used = $("#bloyal_custom_url").is(':checked');

		var custom_grid_api_url = $("#customgridapiurl").val();





		if( !validateDomainName(domainName) ){

			isEmpty = true;

		}



		if( !validateApiKey(apikey) ){

			isEmpty = true;

		}



		if( isEmpty ){

			$("#getAccessKeyLoader").hide();

			return false;

			} else {

			get_access_key(domainName, apikey, domainurl, is_custom_api_url_used, custom_grid_api_url);

		}

	});



	

	$('#testaccesskey').click(function(){

		$("#refresh_configuration_success").hide();

		$("#refresh_configuration_error").hide();

	    $('#loading_for_test').show();

		var isEmpty = false;

		var domainName = $('#domainname').val();

		var apikey = $('#apikey').val();

		var domainurl = $('#domainurl').val();

		var accesskey = $('#accesskey').val();

		var is_custom_api_url_used = $("#bloyal_custom_url").is(':checked');

		var custom_grid_api_url = $("#customgridapiurl").val();



		if( !validateDomainName(domainName) ){

			isEmpty = true;

			$('#loading_for_test').hide();

		}



		if( !validateApiKey(apikey) ){

			isEmpty = true;

			$('#loading_for_test').hide();

		}



		if( isEmpty ){

			$("#getAccessKeyLoader").hide();

			$("#accessKeySuccess").hide();

			$('#loading_for_test').hide();

			return false;

			} else {



			test_access_key(domainName, apikey, domainurl, accesskey, is_custom_api_url_used, custom_grid_api_url);

		}

	});



	function save_configuration(domainName, apikey, domainurl, access_key, adv_access_key, tenderPayments){

        var bloyal_custom_url = $("#bloyal_custom_url").is(':checked');

		var custom_grid_api_url = $('#customgridapiurl').val();

		var data = {

			action: 'check_access_key_before_save',

			post_domain_name: domainName,

			post_api_key: apikey,

			post_domain_url: domainurl,

			post_access_key: access_key,

			post_adv_access_key: adv_access_key,

			post_custom_api_url_used: bloyal_custom_url,

			post_custom_grid_api_url: custom_grid_api_url,

		};

		var Contaxt_response =  $.post(ajaxurl, data, function(response) {

			console.log(response);

			$access_key_status = JSON.parse(response);

			status = $access_key_status['is_access_key_available'];

			console.log($access_key_status);

		})

		.done(function() {

			var data = {

				action: 'save_bloyal_configuration_data',

				post_domain_name: $('#domainname').val(),

				post_api_key: $('#apikey').val(),

				post_domain_url: $('#domainurl').val(),

				post_access_key: $('#accesskey').val(),

				post_adv_access_key: $('#adv_accesskey').val(),

				post_gift_card_tender: $('#giftcardtender').val(),

				post_loyalty_dollars_tender: $('#loyaltydollarstender').val(),

				post_on_account_tender: $('#onaccounttender').val(),

				post_custom_grid_api_url: $('#customgridapiurl').val(),

				post_custom_loyaltyengine_api_url: $('#customloyaltyengineapiurl').val(),

				post_custom_orderengine_api_url: $('#customorderengineapiurl').val(),

				post_custompayment_api_url: $('#custompaymentapiurl').val(),

				post_custom_logging_api_url: $('#customloggingapiurl').val(),

				post_bloyal_custom_url: $("#bloyal_custom_url").is(':checked'),

				post_bloyal_snippet_code: $('#bloyal_snippet_code').val(),

				post_bloyal_snippet_informational_code: $('#bloyal_snippet_informational_code').val(),

				post_bloyal_snippet_confirmation_code: $('#bloyal_snippet_confirmation_code').val(),

				post_bloyal_snippet_problem_code: $('#bloyal_snippet_problem_code').val(),

				post_tender_payments_mapping : tenderPayments,

				post_pickup_row_count: $('#getpickupcount').val(),

				post_bloyal_loyalty_block: $('#loyaltyblock').is(':checked'),

				post_bloyal_apply_full_balance_loyalty: $('#apply_full_balance_loyalty').val(),

				post_bloyal_apply_full_balance_giftcard: $('#apply_full_balance_giftcard').val(),

				post_bloyal_apply_in_increment_of_loyalty: $('#apply_in_increment_of_loyalty').val(),

				post_bloyal_apply_in_increment_of_giftcard: $('#apply_in_increment_of_giftcard').val(),

				post_bloyal_enable_disable: $('#log_id').val()

			};

			if($('#accesskey').val() == '' ){

				$("#save_configuration_fail").text("Access key is not generated. Please click Lock button to generate Access Key.");

				$("#save_configuration_fail").show();

				$('#loading').hide();

			}

			if($('#accesskey').val() ){

				$.post(ajaxurl, data, function(response) {

					$response_data = JSON.parse(response);

					$("#save_configuration_success").show();

					$('#loading').hide();

					location.reload();

				});

			}

			if(adv_access_key  !== '') {

				$('#removeaccesskey').show();

			}

			else{

				$('#loading').hide();

			}

		});

	}



	function save_configuration_order_processing(Newarr1, Newarr2, Newarr3, Newarr4){

		var data = {

			action: 'save_bloyal_configuration_data_order_processing',

			post_use_order_engine: $("#useorderengine").is(':checked'),

			post_applied_shipping_charges: $("#appliedshippingcharges").is(':checked'),

			post_applied_taxes: $("#appliedtaxes").is(':checked'),

			post_row_count: $('#getcount').val(),

			post_shipping1: Newarr1,

			post_shipping2: Newarr2,

			post_shipping_method: Newarr3,

			post_bloyal_display_DOB: $('#isDisplayDOB').is(':checked'),

			post_bloyal_required_DOB: $('#isRequiredDOB').is(':checked'),

			post_bloyal_display_Phone: $('#isDisplayPhone').is(':checked'),

			post_bloyal_required_Phone: $('#isRequiredPhone').is(':checked'),

			post_bloyal_display_Email: $('#isDisplayEmail').is(':checked'),

			post_bloyal_required_Email: $('#isRequiredEmail').is(':checked'),

			post_bloyal_display_order_comments: $('#isDisplayOrderComments').is(':checked'),

			post_bloyal_display_address_Book: $('#isDisplayAddressBook').is(':checked'),

			post_shipping_pickup: Newarr4,

			post_pickup_row_count: $('#getpickupcount').val(),

		};

		$.post(ajaxurl, data, function(response) {

			$response_data = JSON.parse(response);

			$("#save_configuration_success").show();

			$('#loading').hide();

		});

	}

	

	var tenderPaymentsMappingValue ={};

	//Push all the payment tender mapping value to array object.

	$(".tenderPayments").on('change',function(){

		tenderPaymentsMappingValue[$(this).attr('name')] =  $(this).val();

	});

	$('#saveaccesskey').click(function(){

		$("#refresh_configuration_success").hide();

		$("#refresh_configuration_error").hide();

		tenderPayments = tenderPaymentsMappingValue;

		$('#loading').show();

		$("#save_configuration_success").hide();

		$("#save_configuration_fail").hide();

		var isEmpty = false;

		var domainName = $('#domainname').val();

		var apikey = $('#apikey').val();

		var domainurl = $('#domainurl').val();

        if( !validateDomainName(domainName) ){

			$('#loading').hide();

			isEmpty = true;

		}



		if( !validateApiKey(apikey) ){

			$('#loading').hide();

			isEmpty = true;

		}



		if( isEmpty ){

			$("#getAccessKeyLoader").hide();

			$('#loading').hide();

			return false;

		}

        var access_key = $('#accesskey').val();

        var adv_access_key = $('#adv_accesskey').val();

        save_configuration(domainName, apikey, domainurl, access_key, adv_access_key, tenderPayments);

      	return false;

	});



	$('#saveorderprocessing').click(function(){

		$("#refresh_configuration_success").hide();

		$("#refresh_configuration_error").hide();

		$('#loading').show();

		$("#save_configuration_success").hide();

		$("#save_configuration_fail").hide();



		var rowcount = document.getElementById('getcount').value;

		element = document.getElementById('getpickupcount');

		if(element != null){

			var rowcount_pickup = document.getElementById('getpickupcount').value;

			var objJson = new Object();

			var Newarr1 = new Array();

			var Newarr2 = new Array();

			var Newarr3 = new Array();

			var Newarr4 = {};

			for(count=0;count<rowcount;count++){

				var shippingcarrier = "shippingcarrier"+count;

				var shippingservice = "shippingservice"+count;

				element = document.getElementById('getpickupcount');

				if(element != null){

					objJson.shippingcarrier = document.getElementById('shippingcarrier'+count).value;

					objJson.shippingservice = jQuery(".shippingservice"+count).val();

					Newarr1.push(document.getElementById('shippingcarrier'+count).value);

					Newarr2.push(jQuery(".shippingservice"+count).val());

				}

			}

		}

		if(element != null){

			for(count=0;count<rowcount;count++){

				var shipping_method_name = "shippingmethodname"+count;

				objJson.shipping_method_name = document.getElementById('shippingmethodname'+count).value;

				Newarr3.push(document.getElementById('shippingmethodname'+count).value);

			}

		}

		for(count=0; count < rowcount_pickup; count++){

			var local_pick_title 	= document.getElementById('count_'+count).value;

			var selected_inventory 	= document.getElementById(local_pick_title).value;

			Newarr4[local_pick_title] = selected_inventory;

		}

        save_configuration_order_processing(Newarr1, Newarr2, Newarr3, Newarr4);

      	return false;

	});



	//end of new save function

	$('#domainname').on('keyup blur', function(e){

		var domainName = $('#domainname').val();



		if( !validateDomainName(domainName) ){

			return false;

		}

	});



	$('#apikey').on('keyup blur', function(){

		var apikey = $('#apikey').val();



		if( !validateApiKey(apikey) ){

			return false;

		}

	});



	//provisioning activate click event for generate provisioning snippetUrl

	$('#activatedevice').click(function() {

		$("#getAccessKeyLoader").show();

		$('#loading').show();

		var data = {

			 'action': 'bloyal_activate_provisioning_snippets_url',  // your action name

		};

		$.post(ajaxurl, data, function(response) {

			var results = JSON.parse(response);

			console.log(results);

			if( results.data.Status === 'Pending') {

				var provision_session_key = results.data.ProvisionSessionKey;

				var return_url = (location.href)+'&provision_session_key='+provision_session_key;

				var Provision_snippet_url =  (results.data.ProvisionSnippetUrl)+'/&returnUrl='+return_url;

				$("#getAccessKeyLoader").hide();

				location.replace(Provision_snippet_url);

				$('#loading').hide();

			}

		});

	});

	//finish to save provisioning success response

	provisioningActivate();

	function provisioningActivate() {

		$('#loading').show();

		var data = {

			'action': 'bloyal_activate_provisioning_device',  // your action name

			post_access_key_verification: $('#accesskeyverification').val(),

			post_bloyal_custom_url: $("#bloyal_custom_url").is(':checked')

		};

		$.post(ajaxurl, data, function(response) {

			var results = JSON.parse(response);

			console.log(results);

			if(results.Status == 'Succeeded'){

				$(".provisioning_popup").hide();

				jQuery("#backgroundDisable").removeClass("parentDisable");

				$("#getAccessKeyGenerated").show();

				if(results.AccessKey) {

					$('#access_key_row').show();

					$('#accesskey').val(results.AccessKey);

					$('#adv_accesskey').val(results.AccessKey);

				}

				if(results.LoginDomain) {

					$('#domainname').val(results.LoginDomain);

				}

				if(results.ApiKey) {

					$('#apikey').val(results.ApiKey);

				}

				$('#loading').hide();

			}else {

				$("#getAccessKeyGenerated").hide();

				$('#loading').hide();

			}

		});

	}



	//save click and collect configuration settings

	$('#saveclickcollectsettings').click(function(){

		$("#refresh_configuration_success").hide();

		$("#refresh_configuration_error").hide();

		$('#loading').show();

		$("#save_configuration_success").hide();

		$("#save_configuration_fail").hide();

		var isEmpty = false;

		var clickCollectStatus =  $('select[name=bLoyal_bloyal_click_and_collect_status] option').filter(':selected').val();

		var clickCollectLabel = $('#bloyal_bloyal_click_collect_label').val();

		var clickCollectError = $('#bloyal_click_collect_error_title').val();

        if( !validateDomainName(clickCollectLabel) ){

			$('#loading').hide();

			isEmpty = true;

		}



		if( !validateApiKey(clickCollectError) ){

			$('#loading').hide();

			isEmpty = true;

		}



		if( isEmpty ){

			$("#getAccessKeyLoader").hide();

			$('#loading').hide();

			return false;

		}

        save_click_collect_configuration(clickCollectStatus, clickCollectLabel, clickCollectError);

      	return false;

	});



	function save_click_collect_configuration(clickCollectStatus, clickCollectLabel, clickCollectError){

		var data = {

			action: 'bloyal_save_bloyal_click_collect_configuration_data',

			post_click_collect_status: clickCollectStatus,

			post_bloyal_click_collect_label: clickCollectLabel,

			post_click_collect_error: clickCollectError,

		};

		 $.post(ajaxurl, data, function(response) {

			access_key_status = JSON.parse(response);

		})

		.done(function() {

			var data = {

				action: 'bloyal_save_bloyal_click_collect_configuration_data',

				post_click_collect_status: $('#bLoyal_bloyal_click_and_collect_status').val(),

				post_bloyal_click_collect_label: $('#bloyal_bloyal_click_collect_label').val(),

				post_click_collect_error: $('#bloyal_click_collect_error_title').val(),

			};

			if(access_key_status.save_success === true ){

				$.post(ajaxurl, data, function(response) {

					$response_data = JSON.parse(response);

					$("#save_configuration_success").show();

					$('#loading').hide();

				});

			}

			else{

				$("#save_configuration_fail").show();

				$('#loading').hide();

			}

		});

	}

});

function clearFields(){

	document.getElementById("accesskey").value = "";

}



function check_shipping_carrier(OptValue) {

	if(jQuery("#shippingcarrier"+OptValue).val()=="0"){

		jQuery(".shippingservice"+OptValue).html("");

		jQuery(".shippingservice"+OptValue).append('<option value="0">Select</option>');

	}

	else{

		jQuery(".shippingservice"+OptValue).prop('disabled', false);

		var selectedopt =   document.getElementById('shippingcarrier'+OptValue).value;

		var arrData     =   document.getElementById("arr"+selectedopt).value;

		var arrData     =   arrData.split(",");

		jQuery(".shippingservice"+OptValue).html("");

		jQuery(".shippingservice"+OptValue).append('<option value="0">Select</option>');

		for(i=0;i<arrData.length;i++){

			if(i<(arrData.length-1)){

				jQuery(".shippingservice"+OptValue).append('<option value='+arrData[i]+'>'+arrData[i]+'</option>');

			}

		}

	}

}

function check_bloyal_order_processing(){



	if(jQuery("#useorderengine").is(':checked')){

		jQuery("#appliedshippingcharges").prop('disabled', false);

		jQuery("#appliedtaxes").prop('disabled', false);

	}

	else{

		jQuery("#appliedshippingcharges").prop('checked', false);

		jQuery("#appliedtaxes").prop('checked', false);

		jQuery("#appliedshippingcharges").prop('disabled', true);

		jQuery("#appliedtaxes").prop('disabled', true);

	}

}

function resubmit_order_to_bloyal($order_id){

	jQuery('#loading_resubmit').show();

	var data = {

		action: 'resubmit_bloyal_order_data',

		post_order_id: $order_id

	};

	jQuery.post(ajaxurl, data, function(response) {

		$response_data = JSON.parse(response);

		if($response_data['resubmit_message'] != null){

			jQuery('#resubmit').text($response_data['resubmit_message']);

			jQuery("#resubmit").show();

			jQuery('#loading_resubmit').hide();

			location.href = location.href;

		}

		jQuery('#loading_resubmit').hide();

		location.href = location.href;

	})

	.fail(function() {

		jQuery('#loading_resubmit').hide();

	});

}



jQuery(function() {

	jQuery('.provisioning_popup').show();

	jQuery('#id_cancel_provisioning').click(function(){

	jQuery('.provisioning_popup').hide();

	jQuery("#backgroundDisable").removeClass("parentDisable");

		return false;

	});

});

//show popup , if access key is blank-->  Anushka

jQuery(function() {

	jQuery('#removeaccesskey').click(function() {

		jQuery('#loading').show();

		var data = {

			action: 'bloyal_remove_bloyal_accesskey',

			post_adv_access_key: ''

		};

		jQuery.post(ajaxurl, data, function(response) {

			var response_data = JSON.parse(response);

			if(response_data.status === "success") {

				jQuery('#loading').hide();

				location.reload(true);

			}

		});

	});

});



function applyIncrementOf(applyFullBalanceNalue)

{

    if(applyFullBalanceNalue.value == 'false'){

    	jQuery('#giftcard_apply_in_increment_of_section').show();

    }else {

    	jQuery('#giftcard_apply_in_increment_of_section').hide();

    }

}



function applyIncrementOfLoyalty(applyFullBalanceNalue)

{

    if(applyFullBalanceNalue.value == 'false'){

    	jQuery('#loyalty_apply_in_increment_of_section').show();

    }else {

    	jQuery('#loyalty_apply_in_increment_of_section').hide();

    }

}



//scroll to top after click om remove access key button

function scrollToTop() {

	window.scrollTo(0, 0);

}



//bLoayal log downlaod for admin.

jQuery(function() {

	jQuery('#download').click(function(e) {

		e.preventDefault();

		jQuery('#loading').show();

		var data = {

			action: 'bloyal_log_download',

		};

		jQuery.post(ajaxurl, data, function(response) {



			jQuery('#loading').hide();

			// Start file download.



			if(response == 0) {

				alert('Log file not available !');

			}else{

				download("bLoyal_log_file.txt",response);

			}

			

		});

	});

});



function download(filename, text) {

  var element = document.createElement('a');

  element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));

  element.setAttribute('download', filename);

  element.style.display = 'none';

  document.body.appendChild(element);

  element.click();

  document.body.removeChild(element);

}



//bLoayal log delete for admin.

jQuery(function() {

	jQuery('#remove').click(function(e) {

		e.preventDefault();

		if (confirm('Are you sure you want to delete this log?')) {

		    jQuery('#loading').show();

			var data = {

				action: 'bloyal_log_delete',

			};

			jQuery.post(ajaxurl, data, function(response) {

				jQuery('#loading').hide();

	          	if(response.log_status == true) {	

	          		alert(response.message);

	          	}else{

					alert(response.message);

	          	}

			});

		}

	});

});