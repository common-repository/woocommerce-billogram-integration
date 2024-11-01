<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Billogram Invoice Payment Gateway
 *
 * Provides a  Billogram Invoice Payment Gateway.
 *
 * @class 		WC_Billogram_Invoice
 * @extends		WC_Payment_Gateway
 * @version		1.0
 * @author 		WooBill
 */
add_action( 'plugins_loaded', 'init_billogram_payment' );
function init_billogram_payment() {
	class WC_Billogram_Invoice extends WC_Payment_Gateway {
		function __construct(){
			$this->id = 'billogram-invoice';
			$this->icon	= '';
			$this->has_fields = false;
			$this->method_title = "Faktura";
			$this->method_description = "Receive an invoice from Billogram in no time! An administrative fee of 15SEK will be charged.";
			
			//Features Supported
			$this->supports = array(
				'refunds',
				'products',
				'subscriptions',
				'subscription_cancellation',
				'subscription_suspension', 
				'subscription_reactivation',	
				'subscription_date_changes',		  
				'subscription_payment_method_change',
				'subscription_amount_changes',
				'multiple_subscriptions'
			);
			
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
	
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
			
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_billogram-invoice', array( $this, 'thankyou_page_billogram' ) );
	
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions_billogram'), 10, 3 );
		}
		
		/**
		 * Initialise Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Billogram Invoice Payment', 'woocommerce' ),
					'default' => 'yes'
				),
				'title' => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'     => __( 'Faktura', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
					'default'     => __( 'Receive an invoice from Billogram in no time! An Administrative fee of 15SEK will be charged.', 'woocommerce' ), //to do make the admin fee a dynamic variable.
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
					'default'     => 'An Billogram Invoice will be send from Billogram, please follow the instructions there!',
					'desc_tip'    => true,
				),
			);
		}
		
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page_billogram() {
			if ( $this->instructions ){
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}
		
		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions_billogram( $order, $sent_to_admin, $plain_text = false ) {
			if ( $this->instructions && ! $sent_to_admin && 'billogram-invoice' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
		
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
	
			$order = wc_get_order( $order_id );
			
			$options = get_option('woocommerce_billogram_general_settings');
			
			if(class_exists('WC_Subscriptions_Order')){
				if(WC_Subscriptions_Order::get_total_initial_payment( $order ) == 0 && WC_Subscriptions_Order::get_sign_up_fee( $order ) == 0){
					$order->update_status( 'processing' );
					//$order->update_status('pending', __( 'Awaiting Billogram Invoice payment', 'woocommerce' ) );
					$order->add_order_note( 'Billogram invoice is not sent, because the subscription has free trial period!' );
				}
			}else{
				// Mark as on-hold (we're awaiting the cheque)
				$order->update_status( 'on-hold' );
				//$order->update_status('pending', __( 'Awaiting Billogram Invoice payment', 'woocommerce' ) );
				$order->add_order_note( 'Pending Payment: Awaiting Billogram Invoice payment' );
			}
			
			if($options['stock-reduction'] == 'checkout'){
				$order->reduce_order_stock(); // Payment is complete so reduce stock levels
			}
			
			
			// Remove cart
			WC()->cart->empty_cart();
	
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
			);
		}
		
		/**
		 * Process the refund and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */		
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			global $wpdb;
			include_once("class-billogram2-api.php");
			//Init API
			$apiInterface = new WCB_API();
			
			logthis('------refund order_id:'.$order_id.' amount:'.$amount.'-----');
			$invoice = $wpdb->get_row("SELECT * FROM wcb_orders WHERE order_id = ".$order_id, ARRAY_A);
			$invoice_id = $invoice['invoice_id'];
			
			if($invoice_id){
				$apiInterface->create_credit_invoice_request($invoice_id, round($amount));
				return true;
			}else{
				return false;
			}
			
		}
	}
}

function billogram_payment_class( $methods ) {
	$methods[] = 'WC_Billogram_Invoice'; 
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'billogram_payment_class' );
?>