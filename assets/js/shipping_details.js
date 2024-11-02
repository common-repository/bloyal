jQuery.noConflict();
jQuery( document ).ready(
	function($){

		
		$( "#gift_message_box_area" ).hide();
		$( 'input#gift_order_id' ).change(
			function() {
				if (this.checked) {
					$( "#gift_message_box_area" ).show();
				} else {
					$( "#gift_message_box_area" ).hide();
					$( "#gift_message_box_area" ).val( "" );
				}
			}
		);

		var shipping_alt = $( "#shipping_alt" );
		shipping_alt.val( "0" );
		shipping_alt.on(
			"change",
			function () {
				$.post(
					WCMA_Ajax.ajaxurl,
					{
						action               : 'shipping_address_change',
						id                   : $( this ).val(),
						wc_multiple_addresses: WCMA_Ajax.wc_multiple_addresses
					},
					function (response) {
						$( '#shipping_address_1' ).val( response.shipping_address_1 );
						$( '#shipping_address_2' ).val( response.shipping_address_2 );
						$( '#shipping_city' ).val( response.shipping_city );
						$( '#shipping_company' ).val( response.shipping_company );
						$( '#shipping_first_name' ).val( response.shipping_first_name );
						$( '#shipping_last_name' ).val( response.shipping_last_name );
						$( '#shipping_postcode' ).val( response.shipping_postcode );
						$( '#shipping_phone' ).val( response.shipping_phone );
						$( '#shipping_email' ).val( response.shipping_email );
						var dob_ship = response.shipping_birth_date;
						console.log(response);
						console.log(dob_ship);
                        var shipp_dob_format = dob_ship.replace(/(\d\d)\/(\d\d)\/(\d{4})/, "$3-$1-$2");
						$( '#shipping_birth_date' ).val( shipp_dob_format );

						if (response.shipping_country_code != null && response.shipping_country != null) {
							$( "#shipping_country" ).find( "option[value=" + response.shipping_country_code + "]" ).prop( 'selected', true );
							$( "#select2-shipping_country-container" ).text( response.shipping_country );
						}
						if (response.shipping_state_code != null && response.shipping_state != null) {
							$( "#shipping_state" ).find( "option[value=" + response.shipping_state_code + "]" ).prop( 'selected', true );
							$( "#select2-shipping_state-container" ).text( response.shipping_state );
						}
						if (response.shipping_country_code == null && response.shipping_country == null) {
							$( "#shipping_country option[value='']" ).attr( 'selected', true )
							$( "#select2-shipping_country-container" ).text( "" );
						}
						if (response.shipping_state_code == null && response.shipping_state == null) {
							$( "#shipping_state option[value='']" ).attr( 'selected', true )
							$( "#select2-shipping_state-container" ).text( "" );
						}
					}
				);
				return false;
			}
		);


		$( 'input#save_address_checkbox' ).change(
			function() {
				if (this.checked) {

					$.post(
						WCMA_Ajax.ajaxurl,
						{
							action               : 'save_shipping_address',
							save_shipping_addrs  : true,
							wc_multiple_addresses: WCMA_Ajax.wc_multiple_addresses
						},
						function (response) {
							console.log( response );
						}
					);
				} else {
					$.post(
						WCMA_Ajax.ajaxurl,
						{
							action               : 'save_shipping_address',
							save_shipping_addrs  : false,
							wc_multiple_addresses: WCMA_Ajax.wc_multiple_addresses
						},
						function (response) {
							console.log( response );
						}
					);
				}
			}
		);


$(document).on('click','.remove_coupons',function () {

	console.log('The function is hooked up');
	jQuery.ajax({
		type: "POST",
		url: "/wp-admin/admin-ajax.php",
		data: {
		    action: 'bloyal_remove_custom_coupon'
		    // add your parameters here

		},
		success: function (output) {
			location.reload();
		}
	});


});

	}
);
