<?php
 defined('ABSPATH') or die("No access please!");
/* Plugin Name: Flexycode M-PESA Plugin for WooCommerce  
* Plugin URI: https://flexycode.co.ke/
* Description: M-PESA Payment plugin for woocommerce. Call me on +254720108418 or fellojuma@gmail.com if you need any help.
* Version: 1.0.0 
* Author: Demkitech Solutions
* Author URI: https://flexycode.co.ke/
* Licence: GPL2 
*/


add_action('plugins_loaded', 'lipanampesa_payment_gateway_init');

function flexycode_mpesa()
{
	mpesaRequest();
}

// add_shortcode( 'flexycodeMpesa', 'flexycode_mpesa' );

add_action( 'init', function() {
    /** Add a custom path and set a custom query argument. */
    add_rewrite_rule( '^/payment/?([^/]*)/?', 'index.php?payment_action=1', 'top' );
});

//Request payment function start//
add_action( 'init', function() {
    /** Add a custom path and set a custom query argument. */
    add_rewrite_rule( '^/payment/?([^/]*)/?', 'index.php?payment_action=1', 'top' );
} );
add_filter( 'query_vars', function( $query_vars ) {

    /** Make sure WordPress knows about this custom action. */
    $query_vars []= 'payment_action';
    return $query_vars;

} );

add_action( 'wp', function() {
    /** This is an call for our custom action. */
    if ( get_query_var( 'payment_action' ) ) {
        // your code here
		_request_payment();
    }
});

function flexycode_mpesa_scripts()
{
    // Register the script like this for a plugin:
    wp_register_script( 'validate-script', plugins_url( '/js/validate-script.js', __FILE__ ), array( 'jquery','jquery-ui-datepicker' ) );
    
    // For either a plugin or a theme, you can then enqueue the script:
	wp_enqueue_script( 'validate-script', array( 'jquery' ) );
	
	wp_localize_script( 'validate-script', 'the_validate_link', array( 'ajaxurl' => plugins_url( '/flexycode-mpesa-woocommerce/includes/mpesapost.php' ) ) );
}

add_action( 'wp_enqueue_scripts', 'flexycode_mpesa_scripts' );

function installer(){
    include('includes/installer.php');
}
register_activation_hook( __file__, 'installer' );

function lipanampesa_payment_gateway_init(){

    if( !class_exists( 'WC_Payment_Gateway' )) return;
    class WC_Gateway_LipaNaMpesa extends WC_Payment_Gateway {
        
        public function __construct(){		

            session_start();
            $this->id                 = 'lipanampesa';
            $this->icon               = plugin_dir_url(__FILE__) . 'logo.jpg';
            $this->has_fields         = false;
            $this->method_title       = __( 'Lipa Na M-PESA', 'woocommerce' );
            $this->method_description = __( 'Enable customers to make payments to your business shortcode' );


            $this->init_form_fields();

            $this->init_settings();

            $this->title            = $this->get_option( 'title' );
            $this->description      = $this->get_option( 'description' );
            $this->instructions     = $this->get_option( 'instructions', $this->description );
            $this->mer              = $this->get_option( 'mer' ); 
            $_SESSION['token_url']   = $this->get_option( 'token_url' ); 
            $_SESSION['stk_url']   	= $this->get_option( 'stk_url' ); 
            $_SESSION['mpesa_passkey']      			= $this->get_option( 'mpesa_passkey' ); 
            $_SESSION['mpesa_consumer_key']      		= $this->get_option( 'mpesa_consumer_key' ); 
            $_SESSION['mpesa_consumer_secret']   		= $this->get_option( 'mpesa_consumer_secret' );
            $_SESSION['mpesa_shortcode']  			= $this->get_option( 'mpesa_shortcode' ); 

            
            //Save the admin options
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( $this, 'process_admin_options' ) );
            } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
            }

            add_action( 'woocommerce_receipt_lipanampesa', array( $this, 'receipt_page' ));

        }


        public function init_form_fields() {

            $this->form_fields = array(        
                'enabled' => array(        
                    'title'   => __( 'Enable/Disable', 'woocommerce' ),        
                    'type'    => 'checkbox',        
                    'label'   => __( 'Enable Mpesa Payments Gateway', 'woocommerce' ),        
                    'default' => 'yes'        
                ),        
                'title' => array(        
                    'title'       => __( 'Title', 'woocommerce' ),        
                    'type'        => 'text',        
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),        
                    'default'     => __( 'M-PESA', 'woocommerce' ),        
                    'desc_tip'    => true,        
                ),
        
                'description' => array(        
                    'title'       => __( 'Description', 'woocommerce' ),        
                    'type'        => 'textarea',        
                    'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),        
                    'default'     => __( 'Place order and pay using M-PESA.'),        
                    'desc_tip'    => true,        
                ),
        
                'instructions' => array(        
                    'title'       => __( 'Instructions', 'woocommerce' ),        
                    'type'        => 'textarea',        
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),        
                    'default'     => __( 'Place order and pay using M-PESA.', 'woocommerce' ),        
                    'desc_tip'    => true,        
                ),
        
                'mer' => array(        
                    'title'       => __( 'Merchant Name', 'woocommerce' ),        
                    'description' => __( 'Company name', 'woocommerce' ),        
                    'type'        => 'text',        
                    'default'     => __( 'Company Name', 'woocommerce'),        
                    'desc_tip'    => false,        
                ),
        
                'token_url' => array(        
                    'title'       =>  __( 'Credentials Endpoint(Sandbox/Production)', 'woocommerce' ),        
                    'default'     => __( 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials', 'woocommerce'),        
                    'type'        => 'text',        
                ),				
        
                'stk_url' => array(        
                    'title'       =>  __( 'Payments Endpoint(Sandbox/Production)', 'woocommerce' ),        
                    'default'     => __( 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest', 'woocommerce'),        
                    'type'        => 'text',        
                ),
        
                'mpesa_passkey' => array(        
                    'title'       =>  __( 'PassKey', 'woocommerce' ),        
                     'default'     => __( '', 'woocommerce'),        
                    'type'        => 'password',        
                ),
        
                'mpesa_consumer_key' => array(        
                    'title'       =>  __( 'Consumer Key', 'woocommerce' ),        
                     'default'     => __( '', 'woocommerce'),        
                    'type'        => 'password',        
                ),
        
                'mpesa_consumer_secret' => array(        
                    'title'       =>  __( 'Consumer Secret', 'woocommerce' ),        
                     'default'     => __( '', 'woocommerce'),        
                    'type'        => 'password',        
                ),
        
                'mpesa_shortcode' => array(        
                    'title'       =>  __( 'Shortcode', 'woocommerce' ),        
                    'default'     => __( '', 'woocommerce'),        
                    'type'        => 'number',        
                )
        
            );
        
        }

        /**
        * Receipt Page
        **/

        public function receipt_page( $order_id ) {

            echo $this->lipanampesa_generate_iframe( $order_id );

        }

        public function lipanampesa_generate_iframe( $order_id ) {

            global $woocommerce;
            $order = new WC_Order ( $order_id );
        
            $_SESSION['total'] = (int)$order->order_total;
        
            $tel = $order->billing_phone;
            //cleanup the phone number and remove unecessary symbols
        
            $tel = str_replace("-", "", $tel);
            $tel = str_replace( array(' ', '<', '>', '&', '{', '}', '*', "+", '!', '@', '#', "$", '%', '^', '&'), "", $tel );
            $_SESSION['tel'] = "254".substr($tel, -9);
            /**
            * Make the payment here by clicking on pay button and confirm by clicking on complete order button
            */
        
            if ($_GET['transactionType']=='checkout') {
                        
                echo "<h5>Insert a valid phone number to click 'Start Payment' to pay ".$_SESSION['total']." :</h5>";
            
                echo "<br/>";?>

                <style>
                    .startpayment {
                        width: 140px;
                        height: 35px;
                        
                    }
                    .pay_false {
                        color: red;
                        font-size: '16px';
                        font-weight: bold;
                    }
                    .pay_true {
                        color: green;
                        font-size: '16px';
                        font-weight: bold;
                    }
                    #phone_number {
                        width: 200px;
                        height: 35px;
                        border-radius: 5px;
                    }
                </style>
                            
                <input type="hidden" value="<?php echo $_SESSION['total'] ?>" name="amount" class="total_to_pay" id="total_to_pay"/>
                <input type="hidden" value="<?php echo $order->id ?>" name="orderId" class="my_order_id" id="my_order_id"/>
                <input type="hidden" value="<?php echo $order->order_key ?>" name="my_order_key" class="my_order_key" id="my_order_key"/>
                
                <input type="text" value="<?php echo $_SESSION['tel'] ?>" id="phone_number" name="phone_number" />	
            
                <?php echo $_SESSION['response_status']; ?>
            
                <div class="return-message-paybill" id="return-message-paybill"></div>
            
                <button class="startpayment" id="pay_btn">Start Payment</button>	
            
                <?php	
            
                echo "<br/>";
            
            
            
            }
        }

        
        public function process_payment( $order_id ) {

            $order = new WC_Order( $order_id );		

            $_SESSION["orderID"] = $order->id;      		

            // Redirect to checkout/pay page

            $checkout_url = $order->get_checkout_payment_url(true);

            $checkout_edited_url = $checkout_url."&transactionType=checkout";

            return array(

                'result' => 'success',

                'redirect' => add_query_arg('order', $order->id,

                    add_query_arg('key', $order->order_key, $checkout_edited_url))

                ); 
        }


    }
}

function lipanampesa_add_gateway_class( $methods ) {

    $methods[] = 'WC_Gateway_LipaNaMpesa';

    return $methods;
}


if(!add_filter( 'woocommerce_payment_gateways', 'lipanampesa_add_gateway_class' )){

    die;

}