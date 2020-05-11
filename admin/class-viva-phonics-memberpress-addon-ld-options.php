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
class Viva_Phonics_Memberpress_Addon_LD_Tweaks {

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

	}

	public function subscription_create_ld_group( $event ) {
		
		$subscription 	= $event->get_data();
		$user 			= $subscription->user();
		$product 		= $subscription->product();

		$ld_group_name 	= $user->display_name;


		$postarr = array(
			"post_type"		=>	"groups",
			"post_title"	=>	$ld_group_name .' ('. $product->post_title.')',
			"post_content"	=>	'',
			"post_status"	=>	"publish",
			"meta_input"	=> array(
				"group_leader_id"	=>	$user->ID,
			)
		);

		$ld_group_id = wp_insert_post( $postarr, true );

		if( !is_wp_error( $ld_group_id ) ) {
			learndash_set_groups_administrators( $ld_group_id , (array) $user->ID );
		}

		$ld_courses 		= get_post_meta( $product->ID, '_learndash_memberpress_courses', true );

		if ( !empty( $ld_courses ) ) {
			foreach ( $ld_courses as $course_id ) {
				ld_update_course_group_access( $course_id, $ld_group_id, false );
			}
		}
	}


	public function subscription_add_member_ld_group( $event ) {
		
		$transaction 	= $event->get_data();
		$user 			= $transaction->user();
		

		$corporate_account_id 	= get_user_meta( $user->ID, 'mpca_corporate_account_id', true);
		$corporate_account 		= MPCA_Corporate_Account::get_one( $corporate_account_id );
		$parent_user_id 		= $corporate_account->user_id;
		$parent_sub_id 			= $corporate_account->obj_id;
		$parent_obj_type 		= $corporate_account->obj_type;

		$args = array(
			'post_type'   		=> 'groups',
			'post_status' 		=> 'publish',
			'posts_per_page'	=> 1,
			'meta_key'       	=> 'group_leader_id',
			'meta_value' 		=> $parent_user_id,
		);
		
		$query = new WP_Query( $args );

		if( $query->found_posts > 0 ) {
			$ld_group_id = $query->posts[0]->ID;
			ld_update_group_access( $user->ID, $ld_group_id, false );
		}
	}


} // Viva_Phonics_Memberpress_Addon_LD_Tweaks ends