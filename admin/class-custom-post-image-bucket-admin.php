<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://stafflink.com.au/
 * @since      1.0.0
 *
 * @package    Custom_Post_Image_Bucket
 * @subpackage Custom_Post_Image_Bucket/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Custom_Post_Image_Bucket
 * @subpackage Custom_Post_Image_Bucket/admin
 * @author     Matthew Neal <matt.neal@stafflink.com.au>
 */
class Custom_Post_Image_Bucket_Admin {

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

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

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
		 * defined in Custom_Post_Image_Bucket_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Custom_Post_Image_Bucket_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/custom-post-image-bucket-admin.css', array(), $this->version, 'all' );

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
		 * defined in Custom_Post_Image_Bucket_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Custom_Post_Image_Bucket_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/custom-post-image-bucket-admin.js', array( 'jquery' ), $this->version, false );

	}

}

/**
 * custom option and settings
 */
function Custom_Post_Image_Bucket__settings_init() {

    register_setting( 'cpib', 'cpib_options' );

    add_settings_section(
        'cpib_section_developers',
        __( 'Add your Bucket details here.', 'cpib' ), 'cpib_section_developers_callback',
        'cpib'
    );

    add_settings_field(
        'cpib_bucket_access_key',
        'Bucket Access Key',
        'cpib_bucket_access_key_callback',
        'cpib',
        'cpib_section_developers',
        array(
            'label_for'         => 'cpib_bucket_access_key',
            'class'             => 'cpib_row'
        )
    );

	add_settings_field(
        'cpib_secret_key',
        'Bucket Secret Key',
        'cpib_secret_key_callback',
        'cpib',
        'cpib_section_developers',
        array(
            'label_for'         => 'cpib_secret_key',
            'class'             => 'cpib_row'
        )
    );

	add_settings_field(
        'cpib_bucket_name',
        'Bucket Name',
        'cpib_bucket_name_callback',
        'cpib',
        'cpib_section_developers',
        array(
            'label_for'         => 'cpib_bucket_name',
            'class'             => 'cpib_row'
        )
    );
	
}
add_action( 'admin_init', 'Custom_Post_Image_Bucket__settings_init' );

/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function cpib_section_developers_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><a href="https://www.linode.com/docs/products/storage/object-storage/get-started/" target="_blank">Don't a key yet? Click here.</a></p>
    <?php
}

function cpib_bucket_access_key_callback( $args ) {
    $option = get_option( 'cpib_options' );
    ?>
	<input 
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		type="text" 
		class="regular-text" 
		name="cpib_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		size="50" 
		value="<?php echo $option[$args['label_for']]; ?>">
    <?php
}

function cpib_secret_key_callback( $args ) {
    $option = get_option( 'cpib_options' );
    ?>
	<input 
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		type="text" 
		class="regular-text" 
		name="cpib_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		size="50" 
		value="<?php echo $option[$args['label_for']]; ?>">
    <?php
}

function cpib_bucket_name_callback( $args ) {
    $option = get_option( 'cpib_options' );
    ?>
	<input 
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		type="text" 
		class="regular-text" 
		name="cpib_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		size="50" 
		value="<?php echo $option[$args['label_for']]; ?>">
    <?php
}

function cpib_options_page() {
    add_submenu_page(
		'options-general.php',
        'Custom Post Image Bucket Settings',
        'CPIB Bucket Settings',
        'manage_options',
        'cpib',
        'cpib_options_page_html'
    );
}
/**
 * Register our cpib_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'cpib_options_page' );

function cpib_options_page_html() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'cpib_messages', 'cpib_message', __( 'Settings Saved', 'cpib' ), 'updated' );
    }

    settings_errors( 'cpib_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'cpib' );
            do_settings_sections( 'cpib' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}