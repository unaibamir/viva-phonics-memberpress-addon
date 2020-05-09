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
		$mepr_options 			= MeprOptions::fetch();
		$this->setting 			= $mepr_options;
		$this->gateways 		= array( 'stripe', 'paypal' );

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
		$products 		= $product_query->get_posts();
		if( empty( $products ) ) {
			return;
		}		

		foreach ($products as $product) {
			$product_id 		= $product->ID;
			$mept_product		= new MeprProduct( $product_id );

			$this->generateStripePlan( $product, $mept_product );

		}

	}


	public function generateStripePlan( $product, $mept_product ) {
		$product_id 		= $mept_product->get_stripe_product_id($this->get_meta_gateway_id());
	}


	public function stripe_is_test_mode() {

	}


	/**
	 * Get the gateway ID for storing Stripe object IDs
	 *
	 * Object IDs in test mode do not exist in live mode, so we need to differentiate.
	 *
	 * @return string
	 */
	private function get_meta_gateway_id() {
		$key = $this->id;

		if($this->stripe_is_test_mode()) {
			$key .= '_test';
		}

		return $key;
	}
}
