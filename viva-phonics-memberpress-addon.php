<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://unaibamir.com
 * @since             1.0.0
 * @package           Viva_Phonics_Memberpress_Addon
 *
 * @wordpress-plugin
 * Plugin Name:       Viva Phonics MemberPress Addon
 * Plugin URI:        http://unaibamir.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Unaib Amir
 * Author URI:        http://unaibamir.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       viva-phonics-memberpress-addon
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require __DIR__ . '/vendor/autoload.php';

if( !function_exists( "dd" ) ) {
    function dd( $data, $exit_data = true) {
        echo '<pre>'.print_r($data, true).'</pre>';
        if($exit_data == false)
            echo '';
        else
            exit;
    }
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'VIVA_PHONICS_MEMBERPRESS_ADDON_VERSION', '1.0.0' );

// Include plugin.php to access is_plugin_active
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-viva-phonics-memberpress-addon-activator.php
 */
function activate_viva_phonics_memberpress_addon() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-viva-phonics-memberpress-addon-activator.php';
	Viva_Phonics_Memberpress_Addon_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-viva-phonics-memberpress-addon-deactivator.php
 */
function deactivate_viva_phonics_memberpress_addon() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-viva-phonics-memberpress-addon-deactivator.php';
	Viva_Phonics_Memberpress_Addon_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_viva_phonics_memberpress_addon' );
register_deactivation_hook( __FILE__, 'deactivate_viva_phonics_memberpress_addon' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-viva-phonics-memberpress-addon.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_viva_phonics_memberpress_addon() {

	if( is_plugin_active('memberpress/memberpress.php') && is_plugin_active('memberpress-corporate/main.php') ) {
		$plugin = new Viva_Phonics_Memberpress_Addon();
		$plugin->run();
	}

}
add_action( 'plugins_loaded', 'run_viva_phonics_memberpress_addon' );
