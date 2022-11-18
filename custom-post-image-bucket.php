<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://stafflink.com.au/
 * @since             1.0.0
 * @package           Custom_Post_Image_Bucket
 *
 * @wordpress-plugin
 * Plugin Name:       Custom Post Image Bucket
 * Plugin URI:        https://stafflink.com.au/
 * Description:       REQUIRES WP ALL IMPORT PRO.
 * Version:           1.0.0
 * Author:            Matthew Neal
 * Author URI:        https://stafflink.com.au/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       custom-post-image-bucket
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}
/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CUSTOM_POST_IMAGE_BUCKET_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-custom-post-image-bucket-activator.php
 */
function activate_custom_post_image_bucket() {
	$plugin = plugin_basename( __FILE__ ); // 'myplugin'

	if ( ! is_plugin_active( 'wp-all-import-pro/wp-all-import-pro.php' ) ) {
		deactivate_plugins( $plugin );
	}

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-post-image-bucket-activator.php';
	Custom_Post_Image_Bucket_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-custom-post-image-bucket-deactivator.php
 */
function deactivate_custom_post_image_bucket() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-post-image-bucket-deactivator.php';
	Custom_Post_Image_Bucket_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_custom_post_image_bucket' );
register_deactivation_hook( __FILE__, 'deactivate_custom_post_image_bucket' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-custom-post-image-bucket.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_custom_post_image_bucket() {

	$plugin = new Custom_Post_Image_Bucket();
	$plugin->run();

}
run_custom_post_image_bucket();
