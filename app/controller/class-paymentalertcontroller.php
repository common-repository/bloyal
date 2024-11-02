<?php

/**

 * @file

 * alert pop up call in cart page.

 * @var type $var Implements PaymentAlertController.

 */



defined( 'ABSPATH' ) || die( 'No script!' );



require_once BLOYAL_DIR . '/app/controller/class-bloyalcontroller.php';

require_once BLOYAL_DIR .'/app/controller/bloyal_snippets_controller.php';

require_once BLOYAL_DIR . '/app/controller/bloyal_snippets_logger_controller.php';

require_once BLOYAL_DIR . '/app/controller/class-bloyalstoredpaymentmethod.php';



if ( ! class_exists( 'BLOYAL_PaymentAlertController' ) ) {



/**

 * PaymentAlertController Class Doc Comment

 *

 * @category Class

 * @package  PaymentAlertController

 * @author   bLoyal-Inc 

 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License

 * @link     https://bloyal.com/

 *

 */

  class BLOYAL_PaymentAlertController {



         private $bloyalControllerObj;

         private $snippets_controller_obj;



		function __construct() {

           add_action( 'wp_ajax_get_stored_payment_method', array( $this, 'ajax_stored_payment_on_update') );

           add_action( 'wp_ajax_nopriv_get_stored_payment_method', array( $this, 'ajax_stored_payment_on_update' ) );

           $this->bloyalControllerObj       = new BloyalController();

           $this->snippets_controller_obj   = new BloyalSnippetsController();

		}



    /**

     * This function is used to ajax

     *

     * @return json array

     */

    function ajax_stored_payment_on_update() {

      try {

        $is_logged_in_user = is_user_logged_in();

         $access_key        = get_option('bloyal_access_key');

          if(!empty($access_key)){

              $user_details      = $this->snippets_controller_obj->get_new_customer_details_from_wbdb();

              $email_id          = $user_details['Customer']['EmailAddress'];

              $user_id           = $user_details['Customer']['ExternalId'];

              $session_key       = $this->get_session_key( $user_id, $email_id );       

              $obj_payment       = new Bloyal_Stored_Payment_Gateway();

              $alertHtml         = $obj_payment->payment_web_snippets_callback();

              if ( ! empty( $alertHtml ) ) {

                $alertsData = array(

                  'title'         => $alertValue->Category,

                  'html'          => $alertHtml,

                  'is_logged_in'  => $is_logged_in_user,

                  'session_key'   => $session_key,

                );

              } 

			  wp_send_json( json_encode( $alertsData ) );

              die;

          } 

        else{

            $alertsData = array(

              'title'         => '',

              'html'          => 'Access key is not generated',

              'is_logged_in'  => $is_logged_in_user,

              'session_key'   => '',

            );

           wp_send_json( json_encode( $alertsData ) );

            die;

        }

      }

    

      catch ( Exception $ex ) {

        bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );

      }

    }



    /**

     * Generate session key using

     *

     * @param void

     *

     * @return string

     */

    public function get_session_key( $user_id, $email_id ) {

      try {

          $customer_session = "";

          $customer_details = $this->resolve_customer_in_director( $user_id, $email_id );

          if(!empty( $customer_details)) {

              if ($customer_details->status == "success") {

                  $customer_session = $customer_details->data->SessionKey;

              }

          }

          return $customer_session;

      } catch ( Exception $e ) {

           $this->log( __FUNCTION__, 'Error in save multiple shipping addresses Reason: ' . $e->getMessage() );

           return $e->getMessage();

      }

    }



  /**

   * This function is used to resolve customer in director account

   *

   * @param integer $external_id

   * @param string  $customer_email

   * @return json array

   */

    function resolve_customer_in_director( $external_id, $customer_email ) {

      try {

          $action = 'resolvedcustomersession?EmailAddress=' .urlencode( $customer_email ) . '&ExternalId=' . $external_id;

          $result = $this->bloyalControllerObj->send_curl_request( '', $action, 'loyaltyengine', 0 );

          return $result;

      } catch ( Exception $e ) {

        $this->log( __FUNCTION__, 'Error in resolving customer. Reason: ' . $e->getMessage() );

        bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );

        return $e->getMessage();

      }

    }

	}

}

