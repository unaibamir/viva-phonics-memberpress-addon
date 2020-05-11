<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://unaibamir.com
 * @since      1.0.0
 *
 * @package    Viva_Phonics_Memberpress_Addon
 * @subpackage Viva_Phonics_Memberpress_Addon/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Viva_Phonics_Memberpress_Addon
 * @subpackage Viva_Phonics_Memberpress_Addon/admin
 * @author     Unaib Amir <unaibamiraziz@gmail.com>
 */
class Viva_Phonics_Memberpress_Addon_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	private $setting;
	private $gateways;

	protected $secret_key;
	protected $publishable_key;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name 		= $plugin_name;
		$this->version 			= $version;
		$this->settings			= MeprOptions::fetch();
		$this->stripe_settings  = false;

		foreach( $this->settings->integrations as $integration) {
			if( isset($integration['gateway']) && $integration['gateway'] == 'MeprStripeGateway') {
				$this->stripe_settings		= $integration;
				break;
			}
		}

		if ( $this->stripe_is_test_mode() ) {

			$this->secret_key      = isset( $this->stripe_settings["api_keys"]["test"]["secret"] ) ? trim( $this->stripe_settings["api_keys"]["test"]["secret"] ) : '';
			$this->publishable_key = isset( $this->stripe_settings["api_keys"]["test"]["public"] ) ? trim( $this->stripe_settings["api_keys"]["test"]["public"] ) : '';

		} else {

			$this->secret_key      = isset( $this->stripe_settings["api_keys"]["live"]["secret"] ) ? trim( $this->stripe_settings["api_keys"]["live"]["secret"] ) : '';
			$this->publishable_key = isset( $this->stripe_settings["api_keys"]["live"]["public"] ) ? trim( $this->stripe_settings["api_keys"]["live"]["public"] ) : '';

		}

		\Stripe\Stripe::setApiKey( $this->secret_key );

		\Stripe\Stripe::setApiVersion( '2018-02-06' );

		$this->mepr_product = false;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Viva_Phonics_Memberpress_Addon_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Viva_Phonics_Memberpress_Addon_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/viva-phonics-memberpress-addon-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Viva_Phonics_Memberpress_Addon_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Viva_Phonics_Memberpress_Addon_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/viva-phonics-memberpress-addon-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function display_fields( $product ) {

		// Instantiate helper for use in view template
    	$helper = new MPCA_Admin_Helper();

    	$sub_account_price = get_post_meta( $product->ID, 'mpca_sub_account_price', true);

    	?>

		<div id="" class="mepr-sub-box mepr_corporate_advanced_options_box sub-account-price">
			<div class="mepr-arrow mepr-gray mepr-up mepr-sub-box-arrow"></div>
			<label for="mpca_sub_account_price"><?php _e('Sub Account Price', 'memberpress-corporate') ?></label>
				<?php MeprAppHelper::info_tooltip('sub-account-price', __('Sub Account Price', 'memberpress-corporate'), __('The price for sub-accounts for this membership. The currency is default.', 'memberpress-corporate')); ?>

			<input id="mpca_sub_account_price" type="number" name="mpca_sub_account_price" min="0" value="<?php echo $sub_account_price ?>">
		</div>


		<?php
	}

	public function save_meta( $product ) {
		if(isset($_POST['mpca_sub_account_price'])) {
			update_post_meta( $product->ID, 'mpca_sub_account_price', $_POST['mpca_sub_account_price'] );
		}
	}


	public function regenerated_stripe_plans() {
		global $wpdb;

		if( !isset($_GET["regenerated_stripe_plans"]) ) {
			return;
		}

		$args = array(
	
			// Type & Status Parameters
			'post_type'   			=> 'memberpressproduct',
			'post_status' 			=> 'publish',
	
			// Order & Orderby Parameters
			'order' 				=> 'DESC',
			'orderby' 				=> 'date',
	
			// Pagination Parameters
			'posts_per_page' 		=> -1,
			'meta_query'			=> array(
				array(
					'key'		=>	'mpca_is_corporate_product',
					'value' 	=>	1
				),
				array(
					'key'		=>	'_mepr_product_period_type',
					'value'		=>	'lifetime',
					'compare'	=>	'!='
				),
			)
		);
		
		$product_query 	= new WP_Query( $args );

		$products 		= $product_query->posts;
		if( empty( $products ) ) {
			return;
		}		

		foreach ($products as $product) {
			$product_id 		= $product->ID;
			$mepr_product		= new MeprProduct( $product_id );
			$this->mepr_product = $mepr_product;

			$this->generateStripePlan( $product, $mepr_product );

		}

	}


	public function generateStripePlan( $product, $mepr_product ) {

		$product_id 			= $product->ID;
		$prouduct_details 		= $mepr_product->get_values();

		$product_price 			= $prouduct_details["price"];
		$product_amount 		= MeprUtils::is_zero_decimal_currency() ? MeprUtils::format_float($product_price, 0) : MeprUtils::format_float(($product_price * 100), 0);
		
		$sub_account_price 		= get_post_meta( $product->ID, 'mpca_sub_account_price', true);
		$sub_account_amount 	= MeprUtils::is_zero_decimal_currency() ? MeprUtils::format_float($sub_account_price, 0) : MeprUtils::format_float(($sub_account_price * 100), 0);

		$this->mepr_product 	= $mepr_product;
		
		$stripe_product_id 		= $this->get_stripe_product_id( $this->get_meta_gateway_id() );
		$stripe_plan_id 		= $this->get_stripe_plan_id( $this->get_meta_gateway_id(), $product_amount, $mepr_product );
		

		if( $prouduct_details["period_type"] == "months" ) {
			$interval = "month";
		} elseif( $prouduct_details["period_type"] == "weeks" ) {
			$interval = "week";
		} elseif( $prouduct_details["period_type"] == "years" ) {
			$interval = "year";
		} elseif( $prouduct_details["period_type"] == "days" ) {
			$interval = "day";
		}
		
		$interval_count 		= $prouduct_details["period"];

		$create_plan 			= true;
		$s_plan 				= true;

		
		try {
			$s_plan 			= \Stripe\Plan::retrieve($stripe_plan_id);
			$s_product 			= \Stripe\Product::retrieve($s_plan->product);

			$s_plan->delete();
			
			$s_product->delete();

		} catch ( Exception $e ) {}

		if( $create_plan && $s_plan ) {

			$s_product = \Stripe\Product::create( array(
				'id'					=>	$stripe_product_id,
				'name' 					=> 	$product->post_title,
				'type' 					=> 	'service',
				'statement_descriptor' 	=> 	$this->get_statement_descriptor( $mepr_product ),
			) );

			$plan = \Stripe\Plan::create( array(
				"interval"       => $interval,
				"interval_count" => $interval_count,
				"currency"       => $this->settings->currency_code,
				"id"             => $s_plan->id,
				"product"        => $s_product->id,
				"billing_scheme" => "tiered",
				"tiers_mode"     => "graduated",
				"tiers"          => array(
					array(
						"flat_amount"   =>  $product_amount,
						"unit_amount"   =>  0,
						"up_to"         =>  1,
					),
					array(
						"flat_amount"   =>  0,
						"unit_amount"   =>  $sub_account_amount,
						"up_to"         =>  "inf"
					)
				)
			));

		}


	}


	public function create_membership_stripe_product_plan( $membership ) {
		$membership 			= $membership;
		$plan_id 				= $this->get_plan_id( $membership );
	}


	public function stripe_is_test_mode() {
		return isset($this->stripe_settings["test_mode"]) && $this->stripe_settings["test_mode"] == "on" ? true : false ;
	}

	/**
	 * Get the Stripe Product ID
	 *
	 * If the Stripe Product does not exist for the given product, one will be created.
	 *
	 * @param  MeprProduct         $prd The MemberPress product
	 * @return string                   The Stripe Product ID
	 * @throws MeprHttpException        If there was an HTTP error connecting to Stripe
	 * @throws MeprRemoteException      If there was an invalid or error response from Stripe
	 */
	public function get_product_id( MeprProduct $prd ) {
		$product_id = $prd->get_stripe_product_id( $this->get_meta_gateway_id() );

		if(!is_string($product_id) || strpos($product_id, 'prod_') !== 0) {
			$product = $this->create_product($prd);
			$prd->set_stripe_product_id($this->get_meta_gateway_id(), $product->id);
			$product_id = $product->id;
		}

		return $product_id;
	}


	/**
	 * Create a Stripe Product
	 *
	 * @param  MeprProduct         $prd The MemberPress product
	 * @return stdClass                 The Stripe Product data
	 * @throws MeprHttpException        If there was an HTTP error connecting to Stripe
	 * @throws MeprRemoteException      If there was an invalid or error response from Stripe
	 */
	public function create_product( MeprProduct $prd ) {

		$args = MeprHooks::apply_filters('mepr_stripe_create_product_args', [
			'name' => $prd->post_title,
			'type' => 'service',
			'statement_descriptor' => $this->get_statement_descriptor($prd),
		], $prd);

		// Prevent a Stripe error with an empty statement_descriptor
		if(empty($args['statement_descriptor'])) {
			unset($args['statement_descriptor']);
		}

		//$product = (object) $this->send_stripe_request('products', $args, 'post');
		$product = \Stripe\Product::create( $args );

		return $product;
	}

	/**
	 * Get the Stripe Product ID
	 *
	 * @param  string       $gateway_id The gateway ID
	 * @return string|false
	 */
	public function get_stripe_product_id($gateway_id) {
		return get_post_meta($this->mepr_product->ID, '_mepr_stripe_product_id_' . $gateway_id, true);
	}

	/**
	* Set the Stripe Product ID
	*
	* @param string $gateway_id The gateway ID
	* @param string $product_id The Stripe Product ID
	*/
	public function set_stripe_product_id($gateway_id, $product_id) {
		update_post_meta($this->mepr_product->ID, '_mepr_stripe_product_id_' . $gateway_id, $product_id);
	}


	/**
	 * Get the Stripe Plan ID
	 *
	 * If the Stripe Plan does not exist for the given subscription, one will be created.
	 *
	 * @param  MeprProduct         $prd The MemberPress product
	 * @return string                   The Stripe Plan ID
	 * @throws MeprHttpException        If there was an HTTP error connecting to Stripe
	 * @throws MeprRemoteException      If there was an invalid or error response from Stripe
	 */
	public function get_plan_id( MeprProduct $prd ) {

		$prouduct_details 	= $prd->get_values();
		$product_price 		= $prouduct_details["price"];
		
		// Handle zero decimal currencies in Stripe
		$amount 			= MeprUtils::is_zero_decimal_currency() ? MeprUtils::format_float($product_price, 0) : MeprUtils::format_float(($product_price * 100), 0);

		$plan_id 			= $this->get_stripe_plan_id( $this->get_meta_gateway_id(), $amount, $prd );

		if( !is_string($plan_id) || strpos($plan_id, 'plan_') !== 0 ) {
			$plan = $this->create_plan( $this->get_product_id( $prd ), $prd, $amount );
			$this->set_stripe_plan_id( $this->get_meta_gateway_id(), $amount, $plan->id, $prd );
			$plan_id = $plan->id;
		}

		return $plan_id;
	}


	/**
	 * Create a Stripe Plan
	 *
	 * @param  string              $stripe_product_id The Stripe Product ID
	 * @param  MeprSubscription    $sub        The MemberPress subscription
	 * @param  string              $amount     The payment amount (excluding tax)
	 * @return stdClass                        The Stripe Plan data
	 * @throws MeprHttpException               If there was an HTTP error connecting to Stripe
	 * @throws MeprRemoteException             If there was an invalid or error response from Stripe
	 */
	public function create_plan($stripe_product_id, MeprProduct $prd, $amount) {

		$mepr_options = $this->settings;

		if($prd->period_type == 'months') {
			$interval = 'month';
		} elseif($prd->period_type == 'years') {
			$interval = 'year';
		} elseif($prd->period_type == 'weeks') {
			$interval = 'week';
		}

		$sub_account_price 		= get_post_meta( $prd->ID, 'mpca_sub_account_price', true) ?: $amount ;
		$sub_account_amount 	= MeprUtils::is_zero_decimal_currency() ? MeprUtils::format_float($sub_account_price, 0) : MeprUtils::format_float(($sub_account_price * 100), 0);

		$args = MeprHooks::apply_filters('mepr_stripe_create_plan_args', [
			'interval' 			=> $interval,
			'interval_count' 	=> $prd->period,
			'currency' 			=> $mepr_options->currency_code,
			'product' 			=> $stripe_product_id,
			'billing_scheme' 	=> 'tiered',
			'tiers_mode'     	=> 'graduated',
			'tiers'          	=> array(
				array(
					'flat_amount'   =>  $amount,
					'unit_amount'   =>  0,
					'up_to'         =>  1,
				),
				array(
					'flat_amount'   =>  0,
					'unit_amount'   =>  $sub_account_amount,
					'up_to'         =>  'inf'
				)
			)
		], $prd);

		// Prevent a Stripe error if the user is using the pre-1.6.0 method of setting the statement_descriptor
		if(isset($args['statement_descriptor'])) {
			unset($args['statement_descriptor']);
		}

		// Prevent a Stripe error if the 'product' value is modified by hook
		if(!isset($args['product']) || is_array($args['product'])) {
			$args['product'] = $product_id;
		}

		// Don't enclose this in try/catch ... we want any errors to bubble up
		//$plan = (object) $this->send_stripe_request('plans', $args, 'post');

		$plan = \Stripe\Plan::create( $args );

		return $plan;
	}

	/**
	* Get the Stripe Plan ID for this product's current payment terms
	*
	* @param  string       $gateway_id The gateway ID
	* @param  string       $amount     The payment amount (excluding tax)
	* @return string|false
	*/
	public function get_stripe_plan_id($gateway_id, $amount, $product) {
		$meta_key = sprintf('_mepr_stripe_plan_id_%s_%s', $gateway_id, $this->terms_hash( $amount, $product ));
		
		return get_post_meta($this->mepr_product->ID, $meta_key, true);
	}

	/**
	 * Set the Stripe Plan ID for this product's current payment terms
	 *
	 * @param string $gateway_id The gateway ID
	 * @param string $amount     The payment amount (excluding tax)
	 * @param string $plan_id    The Stripe Plan ID
	 */
	public function set_stripe_plan_id($gateway_id, $amount, $plan_id, MeprProduct $prd) {
		$meta_key = sprintf('_mepr_stripe_plan_id_%s_%s', $gateway_id, $this->terms_hash( $amount, $prd ));
		update_post_meta( $prd->ID, $meta_key, $plan_id );
	}


	/**
	 * Get the hash of the payment terms
	 *
	 * If this hash changes then a different Stripe Plan will be created.
	 *
	 * @param  string $amount
	 * @return string
	 */
	private function terms_hash($amount, MeprProduct $product) {
		//$mepr_options = MeprOptions::fetch();
		
		$prouduct_details 		= $product->get_values();

		$terms = [
			'currency' => $this->settings->currency_code,
			'amount' => $amount,
			'period' => $prouduct_details["period"],
			'period_type' => $prouduct_details["period_type"]
		];

		return md5(serialize($terms));
	}


	/**
	 * Get the gateway ID for storing Stripe object IDs
	 *
	 * Object IDs in test mode do not exist in live mode, so we need to differentiate.
	 *
	 * @return string
	 */
	private function get_meta_gateway_id() {
		
		$key = $this->stripe_settings["id"];
		
		if($this->stripe_is_test_mode()) {
			$key .= '_test';
		}
		
		return $key;
	}

	/**
	* Delete the Stripe Product ID
	*
	* @param string $gateway_id The gateway ID
	* @param string $product_id The Stripe Product ID
	*/
	public static function delete_stripe_product_id($gateway_id, $product_id) {
		if(is_string($product_id) && $product_id !== '') {
			delete_metadata('post', null, '_mepr_stripe_product_id_' . $gateway_id, $product_id, true);
		}
	}

	/**
	* Delete the Stripe Plan ID
	*
	* @param string $gateway_id The gateway ID
	* @param string $plan_id    The Stripe Plan ID
	*/
	public static function delete_stripe_plan_id($gateway_id, $plan_id) {
		if(!is_string($plan_id) || $plan_id === '') {
			return;
		}

		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_value = %s",
			$wpdb->esc_like('_mepr_stripe_plan_id_' . $gateway_id) . '%',
			$plan_id
		);

		$meta_ids = $wpdb->get_col($query);

		if(is_array($meta_ids) && count($meta_ids)) {
			foreach($meta_ids as $meta_id) {
				delete_metadata_by_mid('post', $meta_id);
			}
		}
	}

	/**
	 * Get the statement descriptor
	 *
	 * @param  MeprProduct $product The MemberPress product
	 * @return string               The statement descriptor
	 */
	private function get_statement_descriptor(MeprProduct $product) {
		$descriptor = get_option('blogname');

		if(empty($descriptor)) {
			parse_url(get_option('siteurl'), PHP_URL_HOST);
		}

		$descriptor = MeprHooks::apply_filters('mepr_stripe_statement_descriptor', $descriptor, $product);

		return $this->sanitize_statement_descriptor($descriptor);
	}


	/**
	 * Sanitize the statement descriptor
	 *
	 * Removes invalid chars and limits the length.
	 *
	 * @param  string $statement_descriptor
	 * @return string
	 */
	private function sanitize_statement_descriptor($statement_descriptor) {
		$statement_descriptor = str_replace(array("'", '"', '<', '>', '$', '®', '*', '\\', '&lt;', '&gt;', '&#039;', '&quot;'), '', $statement_descriptor);
		$statement_descriptor = trim(substr($statement_descriptor, 0, 22));

		return $statement_descriptor;
	}


	/**
	 * Get the Stripe Customer ID
	 *
	 * @param  string       $gateway_id The gateway ID
	 * @return string|false
	 */
	public function get_stripe_customer_id($user_id, $gateway_id) {
		$mepr_options = MeprOptions::fetch();
		$meta_key = sprintf('_mepr_stripe_customer_id_%s_%s', $gateway_id, $mepr_options->currency_code);

		return get_user_meta($user_id, $meta_key, true);
	}


	public function num_sub_accounts_used( $user_id ) {
		global $wpdb;
		$mepr_db = MeprDb::fetch();

		$q = $wpdb->prepare(
			"
			SELECT COUNT(DISTINCT user_id)
			FROM {$wpdb->usermeta}
			WHERE meta_key=%s
			AND meta_value=%s
			",
			'mpca_corporate_account_id',
			$user_id
		);

		return $wpdb->get_var($q);
	}


	public function stripe_charge_parent_member( $event ) {

		$transaction 	= $event->get_data();
		$user 			= $transaction->user();
		$product 		= $transaction->product();
		$mepr_options 	= MeprOptions::fetch();
		

		$corporate_account_id 	= get_user_meta( $user->ID, 'mpca_corporate_account_id', true);
		$corporate_account 		= MPCA_Corporate_Account::get_one( $corporate_account_id );
		$parent_user_id 		= $corporate_account->user_id;
		$parent_user 			= new MeprUser( $parent_user_id );
		$parent_sub_id 			= $corporate_account->obj_id;
		$parent_sub  			= new MeprSubscription( $parent_sub_id );
		$parent_obj_type 		= $corporate_account->obj_type;
		$stripe_customer_id 	= $this->get_stripe_customer_id( $parent_user_id, $this->get_meta_gateway_id() );
		$intent_options 		= array();
		$sub_account_price 		= get_post_meta( $product->ID, 'mpca_sub_account_price', true) ?: $amount ;
		$sub_account_amount 	= MeprUtils::is_zero_decimal_currency() ? MeprUtils::format_float($sub_account_price, 0) : MeprUtils::format_float(($sub_account_price * 100), 0);

		$customer               = \Stripe\Customer::retrieve($stripe_customer_id);

		$intent_args = array(
			'customer'    			=> $stripe_customer_id,
			"description"   		=> "Additional Member Cost - User Name: ".$user->display_name." - Group Leader: ".$parent_user->display_name." ",
			'amount'              	=> $sub_account_amount,
			'confirmation_method' 	=> 'automatic',
			'confirm'             	=> true,
			'off_session'		  	=> true,
			'currency'            	=> strtolower( $mepr_options->currency_code ),
			'payment_method' 	  	=> $customer->invoice_settings->default_payment_method,
			'payment_method_types' 	=> ['card'],
			"metadata"      		=>  array(
                "email"             =>  $user->user_email,
                "user_id"           =>  $user->ID,
                "group_owner_id"    =>  $parent_user->ID,
                "group_owner_name"  =>  $parent_user->display_name,
                "membership_id"     =>  $product->ID,
                "membership"        =>  $product->post_title,
            )
		);

		$intent_options['idempotency_key'] = md5( json_encode( $intent_args ) );
		$intent_options['stripe_account']  = $this->stripe_settings["service_account_id"];

		$intent 		= \Stripe\PaymentIntent::create( $intent_args, $intent_options );

		$plan_id 		= $this->get_plan_id( $product );


		$s_subscription = \Stripe\Subscription::retrieve($parent_sub->subscr_id);
		$s_subscription->prorate = false;
		$s_subscription->items = array(
			array(
				"id"		=> 	$s_subscription->items->data[0]->id,
				"plan"		=>	$plan_id,
				"quantity"	=>	$this->num_sub_accounts_used( $corporate_account->id ),
			)
		);
		$s_subscription->save();
		unset($s_subscription);

	}


} // Viva_Phonics_Memberpress_Addon_Admin ends