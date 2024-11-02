jQuery( document ).ready(function() {

    //check the alert is available
    var obj_alert = jQuery.parseJSON( alertArray );
    if(obj_alert.length === 0) {
        return false;
    }

    var obj_options = jQuery.parseJSON( optionsArray );
    var bloyal_domain_name = obj_options.bloyal_domain_name;
    var bloyal_device_code = obj_options.bloyal_device_code;
    var alert_value = obj_alert[0];
    var snippet_args = alert_value.SnippetArgs;
    const snippet_args_1 = { "CashierCode":"", "OnSnippetComplete": "EgiftSnippetComplete" };
    const final_snippet_args = Object.assign( snippet_args, snippet_args_1 );
    var snippet_code = alert_value.SnippetCode;
    var snippets_div = "<style>.ui-dialog { z-index: 9999 !important ;min-width: 40% !important;top: 30% !important;left: 35% !important;}  .ui-dialog .ui-dialog-content { overflow: initial !important; }</style><div data-bloyal-snippet-code='" + snippet_code + "' data-bloyal-login-domain='" + bloyal_domain_name + "' data-bloyal-device-code='" + bloyal_device_code + "' data-bloyal-snippet-args='" + JSON.stringify( final_snippet_args ) + "' id='root'></div><script> function EgiftSnippetComplete() { console.log('The Snippet is done'); jQuery.ajax( { type: 'POST', url: httpAdminUrl, data: { action: 'get_bloyal_cart_items' }, success:function(responseData){ location.reload(); } } ); } </script>";
     
    setTimeout(
        function(){
            ajaxToGetAlerts();
        },
        300
    );

    //this code use calculate cart and approve cart alerts
    function ajaxToGetAlerts() {
        jQuery('body').append('<div id="dialog"></div>');
        (function($) {
            $( "#dialog" ).dialog();
            $('body').css('overflow', 'unset');
            $('#dialog').append(snippets_div);
            $( "body" ).blur();
            $('.woocommerce').css({'pointer-events':'none','opacity':'0.6'});
            $('.ui-dialog-titlebar-close').click( function(){
                $('body').css('overflow', 'scroll');
                $('.woocommerce').css({'pointer-events':'auto','opacity':'1'});
            });
        })(jQuery);
    }
});

