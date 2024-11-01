<?php 
/*
Plugin Name: Skrill Manual Payment Gateway
Plugin URI:  https://zitengine.com
Description: Skrill is money transfer system of International by facilitating money transfer through Online. This plugin depends on woocommerce and will provide an extra payment gateway through skrill in checkout page.
Version:     1.1
Author:      Md Zahedul Hoque
Author URI:  http://facebook.com/zitengine 
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: stb
*/
defined('ABSPATH') or die('Only a foolish person try to access directly to see this white page. :-) ');
define( 'zitengine_skrill__VERSION', '1.1' );
define( 'zitengine_skrill__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
/**
 * Plugin language
 */
add_action( 'init', 'zitengine_skrill_language_setup' );
function zitengine_skrill_language_setup() {
  load_plugin_textdomain( 'stb', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
/**
 * Plugin core start
 * Checked Woocommerce activation
 */
if( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
	
	/**
	 * skrill gateway register
	 */
	add_filter('woocommerce_payment_gateways', 'zitengine_skrill_payment_gateways');
	function zitengine_skrill_payment_gateways( $gateways ){
		$gateways[] = 'zitengine_skrill';
		return $gateways;
	}

	/**
	 * skrill gateway init
	 */
	add_action('plugins_loaded', 'zitengine_skrill_plugin_activation');
	function zitengine_skrill_plugin_activation(){
		
		class zitengine_skrill extends WC_Payment_Gateway {

			public $skrill_email;
			public $number_type;
			public $order_status;
			public $instructions;
			public $skrill_charge;

			public function __construct(){
				$this->id 					= 'zitengine_skrill';
				$this->title 				= $this->get_option('title', 'Skrill P2P Gateway');
				$this->description 			= $this->get_option('description', 'Skrill payment Gateway');
				$this->method_title 		= esc_html__("Skrill", "stb");
				$this->method_description 	= esc_html__("Skrill Payment Gateway Options", "stb" );
				$this->icon 				= plugins_url('images/skrill.png', __FILE__);
				$this->has_fields 			= true;

				$this->zitengine_skrill_options_fields();
				$this->init_settings();
				
				$this->skrill_email = $this->get_option('skrill_email');
				$this->number_type 	= $this->get_option('number_type');
				$this->order_status = $this->get_option('order_status');
				$this->instructions = $this->get_option('instructions');
				$this->skrill_charge = $this->get_option('skrill_charge');

				add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( $this, 'process_admin_options' ) );
	            add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'zitengine_skrill_thankyou_page' ) );
	            add_action( 'woocommerce_email_before_order_table', array( $this, 'zitengine_skrill_email_instructions' ), 10, 3 );
			}


			public function zitengine_skrill_options_fields(){
				$this->form_fields = array(
					'enabled' 	=>	array(
						'title'		=> esc_html__( 'Enable/Disable', "stb" ),
						'type' 		=> 'checkbox',
						'label'		=> esc_html__( 'Skrill Payment', "stb" ),
						'default'	=> 'yes'
					),
					'title' 	=> array(
						'title' 	=> esc_html__( 'Title', "stb" ),
						'type' 		=> 'text',
						'default'	=> esc_html__( 'Skrill', "stb" )
					),
					'description' => array(
						'title'		=> esc_html__( 'Description', "stb" ),
						'type' 		=> 'textarea',
						'default'	=> esc_html__( 'Please complete your Skrill payment at first, then fill up the form below.', "stb" ),
						'desc_tip'    => true
					),
	                'order_status' => array(
	                    'title'       => esc_html__( 'Order Status', "stb" ),
	                    'type'        => 'select',
	                    'class'       => 'wc-enhanced-select',
	                    'description' => esc_html__( 'Choose whether status you wish after checkout.', "stb" ),
	                    'default'     => 'wc-on-hold',
	                    'desc_tip'    => true,
	                    'options'     => wc_get_order_statuses()
	                ),				
					'skrill_email'	=> array(
						'title'			=> esc_html__( 'skrill Email', "stb" ),
						'description' 	=> esc_html__( 'Add a Skrill email ID which will be shown in checkout page', "stb" ),
						'type'			=> 'email',
						'desc_tip'      => true
					),
					'number_type'	=> array(
						'title'			=> esc_html__( 'Skrill Account Type', "stb" ),
						'type'			=> 'select',
						'class'       	=> 'wc-enhanced-select',
						'description' 	=> esc_html__( 'Select Skrill account type', "stb" ),
						'options'	=> array(
							'Personal'	=> esc_html__( 'Personal', "stb" ),
							'Business'	=> esc_html__( 'Business', "stb" )
						),
						'desc_tip'      => true
					),
					'skrill_charge' 	=>	array(
						'title'			=> esc_html__( 'Enable Skrill Charge', "stb" ),
						'type' 			=> 'checkbox',
						'label'			=> esc_html__( 'Add 1% Skrill "Payment" charge to net price', "stb" ),
						'description' 	=> esc_html__( 'If a product price is 1000 then customer have to pay ( 1000 + 10 ) = 1010. Here 10 is Skrill send money charge', "stb" ),
						'default'		=> 'no',
						'desc_tip'    	=> true
					),						
	                'instructions' => array(
	                    'title'       	=> esc_html__( 'Instructions', "stb" ),
	                    'type'        	=> 'textarea',
	                    'description' 	=> esc_html__( 'Instructions that will be added to the thank you page and emails.', "stb" ),
	                    'default'     	=> esc_html__( 'Thanks for purchasing through Skrill. We will check and give you update as soon as possible.', "stb" ),
	                    'desc_tip'    	=> true
	                ),								
				);
			}


			public function payment_fields(){

				global $woocommerce;
				$skrill_charge = ($this->skrill_charge == 'yes') ? esc_html__(' Also note that 1% skrill "Payment" cost will be added with net price. Total amount you need to send us at', "stb" ). ' ' . get_woocommerce_currency_symbol() . $woocommerce->cart->total : '';
				echo wpautop( wptexturize( esc_html__( $this->description, "stb" ) ) . $skrill_charge  );
				echo wpautop( wptexturize( "skrill ".$this->number_type." Email : ".$this->skrill_email ) );

				?>
					<p>
						<label for="skrill_email"><?php esc_html_e( 'skrill Email', "stb" );?></label>
						<input type="email" name="skrill_email" id="skrill_email" placeholder="skrill@youremail.com">
					</p>
					<p>
						<label for="skrill_transaction_id"><?php esc_html_e( 'skrill Transaction ID', "stb" );?></label>
						<input type="text" name="skrill_transaction_id" id="skrill_transaction_id" placeholder="8N7A6D5EE7M">
					</p>
				<?php 
			}
			

			public function process_payment( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );
				
				$status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;
				// Mark as on-hold (we're awaiting the skrill)
				$order->update_status( $status, esc_html__( 'Checkout with skrill payment. ', "stb" ) );

				// Reduce stock levels
				$order->reduce_order_stock();

				// Remove cart
				$woocommerce->cart->empty_cart();

				// Return thankyou redirect
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			}	


	        public function zitengine_skrill_thankyou_page() {
			    $order_id = get_query_var('order-received');
			    $order = new WC_Order( $order_id );
			    if( $order->payment_method == $this->id ){
		            $thankyou = $this->instructions;
		            return $thankyou;		        
			    } else {
			    	return esc_html__( 'Thank you. Your order has been received.', "stb" );
			    }

	        }


	        public function zitengine_skrill_email_instructions( $order, $sent_to_admin, $plain_text = false ) {
			    if( $order->payment_method != $this->id )
			        return;        	
	            if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method ) {
	                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
	            }
	        }

		}

	}

	/**
	 * Add settings page link in plugins
	 */
	add_filter( "plugin_action_links_". plugin_basename(__FILE__), 'zitengine_skrill_settings_link' );
	function zitengine_skrill_settings_link( $links ) {
		
		$settings_links = array();
		$settings_links[] ='<a href="https://www.facebook.com/zitengine/" target="_blank">' . esc_html__( 'Follow US', 'stb' ) . '</a>';
		$settings_links[] ='<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=zitengine_skrill' ) . '">' . esc_html__( 'Settings', 'stb' ) . '</a>';
        
        // add the links to the list of links already there
		foreach($settings_links as $link) {
			array_unshift($links, $link);
		}

		return $links;
	}	

	/**
	 * If skrill charge is activated
	 */
	$skrill_charge = get_option( 'woocommerce_zitengine_skrill_settings' );
	if( $skrill_charge['skrill_charge'] == 'yes' ){

		add_action( 'wp_enqueue_scripts', 'zitengine_skrill_script' );
		function zitengine_skrill_script(){
			wp_enqueue_script( 'stb-script', plugins_url( 'js/scripts.js', __FILE__ ), array('jquery'), '1.0', true );
		}

		add_action( 'woocommerce_cart_calculate_fees', 'zitengine_skrill_charge' );
		function zitengine_skrill_charge(){

		    global $woocommerce;
		    $available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
		    $current_gateway = '';

		    if ( !empty( $available_gateways ) ) {
		        if ( isset( $woocommerce->session->chosen_payment_method ) && isset( $available_gateways[ $woocommerce->session->chosen_payment_method ] ) ) {
		            $current_gateway = $available_gateways[ $woocommerce->session->chosen_payment_method ];
		        } 
		    }
		    
		    if( $current_gateway!='' ){

		        $current_gateway_id = $current_gateway->id;

				if ( is_admin() && ! defined( 'DOING_AJAX' ) )
					return;

				if ( $current_gateway_id =='zitengine_skrill' ) {
					$percentage = 0.01;
					$surcharge = ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage;	
					$woocommerce->cart->add_fee( esc_html__('skrill Charge', 'stb'), $surcharge, true, '' ); 
				}
		       
		    }    	
		    
		}
		
	}

	/**
	 * Empty field validation
	 */
	add_action( 'woocommerce_checkout_process', 'zitengine_skrill_payment_process' );
	function zitengine_skrill_payment_process(){

	    if($_POST['payment_method'] != 'zitengine_skrill')
	        return;

	    $skrill_email = sanitize_email( $_POST['skrill_email'] );
	    $skrill_transaction_id = sanitize_text_field( $_POST['skrill_transaction_id'] );

	    $match_number = isset($skrill_email) ? $skrill_email : '';
	    $match_id = isset($skrill_transaction_id) ? $skrill_transaction_id : '';

        $validate_number = filter_var($match_number, FILTER_VALIDATE_EMAIL);
        $validate_id = preg_match( '/[a-zA-Z0-9]+/',  $match_id );

	    if( !isset($skrill_email) || empty($skrill_email) )
	        wc_add_notice( esc_html__( 'Please add skrill Email ID', 'stb'), 'error' );

		if( !empty($skrill_email) && $validate_number == false )
	        wc_add_notice( esc_html__( 'Email ID not valid', 'stb'), 'error' );

	    if( !isset($skrill_transaction_id) || empty($skrill_transaction_id) )
	        wc_add_notice( esc_html__( 'Please add your skrill transaction ID', 'stb' ), 'error' );

		if( !empty($skrill_transaction_id) && $validate_id == false )
	        wc_add_notice( esc_html__( 'Only number or letter is acceptable', 'stb'), 'error' );

	}

	/**
	 * Update skrill field to database
	 */
	add_action( 'woocommerce_checkout_update_order_meta', 'zitengine_skrill_additional_fields_update' );
	function zitengine_skrill_additional_fields_update( $order_id ){

	    if($_POST['payment_method'] != 'zitengine_skrill' )
	        return;

	    $skrill_email = sanitize_email( $_POST['skrill_email'] );
	    $skrill_transaction_id = sanitize_text_field( $_POST['skrill_transaction_id'] );

		$number = isset($skrill_email) ? $skrill_email : '';
		$transaction = isset($skrill_transaction_id) ? $skrill_transaction_id : '';

		update_post_meta($order_id, '_skrill_email', $number);
		update_post_meta($order_id, '_skrill_transaction', $transaction);

	}

	/**
	 * Admin order page skrill data output
	 */
	add_action('woocommerce_admin_order_data_after_billing_address', 'zitengine_skrill_admin_order_data' );
	function zitengine_skrill_admin_order_data( $order ){
	    
	    if( $order->payment_method != 'zitengine_skrill' )
	        return;

		$number = (get_post_meta($order->id, '_skrill_email', true)) ? get_post_meta($order->id, '_skrill_email', true) : '';
		$transaction = (get_post_meta($order->id, '_skrill_transaction', true)) ? get_post_meta($order->id, '_skrill_transaction', true) : '';

		?>
		<div class="form-field form-field-wide">
			<img src='<?php echo plugins_url("images/skrill.png", __FILE__); ?>' alt="skrill">	
			<table class="wp-list-table widefat fixed striped posts">
				<tbody>
					<tr>
						<th><strong><?php esc_html_e('Skrill Email', 'stb') ;?></strong></th>
						<td>: <?php echo esc_attr( $number );?></td>
					</tr>
					<tr>
						<th><strong><?php esc_html_e('Transaction ID', 'stb') ;?></strong></th>
						<td>: <?php echo esc_attr( $transaction );?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php 
		
	}

	/**
	 * Order review page skrill data output
	 */
	add_action('woocommerce_order_details_after_customer_details', 'zitengine_skrill_additional_info_order_review_fields' );
	function zitengine_skrill_additional_info_order_review_fields( $order ){
	    
	    if( $order->payment_method != 'zitengine_skrill' )
	        return;

		$number = (get_post_meta($order->id, '_skrill_email', true)) ? get_post_meta($order->id, '_skrill_email', true) : '';
		$transaction = (get_post_meta($order->id, '_skrill_transaction', true)) ? get_post_meta($order->id, '_skrill_transaction', true) : '';

		?>
			<tr>
				<th><?php esc_html_e('Skrill Email:', 'stb');?></th>
				<td><?php echo esc_attr( $number );?></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Transaction ID:', 'stb');?></th>
				<td><?php echo esc_attr( $transaction );?></td>
			</tr>
		<?php 
		
	}	

	/**
	 * Register new admin column
	 */
	add_filter( 'manage_edit-shop_order_columns', 'zitengine_skrill_admin_new_column' );
	function zitengine_skrill_admin_new_column($columns){

	    $new_columns = (is_array($columns)) ? $columns : array();
	    unset( $new_columns['order_actions'] );
	    $new_columns['mobile_no'] 	= esc_html__('Send From', 'stb');
	    $new_columns['tran_id'] 	= esc_html__('Tran. ID', 'stb');

	    $new_columns['order_actions'] = $columns['order_actions'];
	    return $new_columns;

	}

	/**
	 * Load data in new column
	 */
	add_action( 'manage_shop_order_posts_custom_column', 'zitengine_skrill_admin_column_value', 2 );
	function zitengine_skrill_admin_column_value($column){

	    global $post;

	    $mobile_no = (get_post_meta($post->ID, '_skrill_email', true)) ? get_post_meta($post->ID, '_skrill_email', true) : '';
	    $tran_id = (get_post_meta($post->ID, '_skrill_transaction', true)) ? get_post_meta($post->ID, '_skrill_transaction', true) : '';

	    if ( $column == 'mobile_no' ) {    
	        echo esc_attr( $mobile_no );
	    }
	    if ( $column == 'tran_id' ) {    
	        echo esc_attr( $tran_id );
	    }
	}

} else {
	/**
	 * Admin Notice
	 */
	add_action( 'admin_notices', 'zitengine_skrill_admin_notice__error' );
	function zitengine_skrill_admin_notice__error() {
	    ?>
	    <div class="notice notice-error">
	        <p><a href="http://wordpress.org/extend/plugins/woocommerce/"><?php esc_html_e( 'Woocommerce', 'stb' ); ?></a> <?php esc_html_e( 'plugin need to active if you wanna use skrill plugin.', 'stb' ); ?></p>
	    </div>
	    <?php
	}
	
	/**
	 * Deactivate Plugin
	 */
	add_action( 'admin_init', 'zitengine_skrill_deactivate' );
	function zitengine_skrill_deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		unset( $_GET['activate'] );
	}
}