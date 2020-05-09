<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://unaibamir.com
 * @since      1.0.0
 *
 * @package    Viva_Phonics_Memberpress_Addon
 * @subpackage Viva_Phonics_Memberpress_Addon/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Viva_Phonics_Memberpress_Addon
 * @subpackage Viva_Phonics_Memberpress_Addon/includes
 * @author     Unaib Amir <unaibamiraziz@gmail.com>
 */
class Viva_Phonics_Memberpress_Addon_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'viva-phonics-memberpress-addon',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
