<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://stafflink.com.au/
 * @since      1.0.0
 *
 * @package    Custom_Post_Image_Bucket
 * @subpackage Custom_Post_Image_Bucket/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Custom_Post_Image_Bucket
 * @subpackage Custom_Post_Image_Bucket/includes
 * @author     Matthew Neal <matt.neal@stafflink.com.au>
 */
class Custom_Post_Image_Bucket_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'custom-post-image-bucket',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
