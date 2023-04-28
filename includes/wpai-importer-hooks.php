<?php

/**
 * Hook into 
 *
 * @param [type] $post_id
 * @param [type] $xml_node
 * @param [type] $is_update
 * @return void
 */
function cpib_import_images( $post_id, $xml_node, $is_update ) {

	// Check if the image should be updated.
	$post_object = get_post( $post_id );
	$update      = epl_wpimport_is_image_to_update( $is_update, (array) $post_object, $xml_node );

	// Exit early if the image should not be updated.
	if ( false === $update ) {
		return false;
	}

	// Get the Import ID's as a string and convert to an array.
	$import_ids = get_option( 'cpib_options' )['cpib_importer_ids'];
	$import_ids = explode( ',', $import_ids );
	$options    = array_map( 'intval', $import_ids );

	// Get the import ID	
	if ( isset( $_GET['id'] ) ) {
		$import_id = $_GET['id'];
	} elseif ( isset( $_GET['import_id'] ) ) {
		$import_id = $_GET['import_id'];
	} else {
		$import_id = 'new';
	}
	
	// If the import ID isn't in the options, exit early.
	if ( ! in_array( (int) $import_id, $options, true ) ) {
		error_log('import_id not in options. Exiting.', 0);
		return false;
	}

	$post_type = 'post';
	$acf_repeater = 'image_repeater';

	// If this doesn't have a property ID, exit.
	if ( empty( (string) $xml_node->uniqueID ) ) {
		return false;
	}

	// Get the post type of the imported record.
	$import = new PMXI_Import_Record();
	$import->getById( $import_id );

	if ( ! $import->isEmpty() ) {
		$post_type = $import->options['custom_type'];
	}

	// Initialise the bucket and the DB qeueue.
	$queue  = new cpib\sync\Queue();
	$bucket = new cpib\Bucket();

	$images = $xml_node->objects->img; // Rex Softare version.
	if ( empty( $images ) ) {
		$images = $xml_node->images->img; // Property ME version.
	}

	// Insert each image into the database for processing.
	foreach ( $images as $image ) {
		if ( ! empty( $image->attributes()->url ) ) {
			$queue->insert(
				$post_id,
				(string) $image->attributes()->id,
				(string) $xml_node->uniqueID,
				(string) $image->attributes()->url,
				(string) $image->attributes()->modTime,
				$post_type
			);
		}
	}

	$pending_rows = $queue->get_pending( 'pending' );
	$json_images  = $bucket->upload( $pending_rows );

	// TODO: update this input to be an option on the options page.
	$exists = is_field_group_exists( 'Image Repeater' );

	if ( $exists ) {
		error_log('repeater field exists, adding there', 0);
		foreach ( $json_images as $key => $value ) {
			foreach ( $value as $image_url ) {
				add_row( $acf_repeater, array( 'image' => $image_url ), $key );
			}
		}
	} else {
		error_log( ' repeater field doesnt exist. Updating cpib_property_images', 0);
		update_post_meta( $post_id, 'cpib_property_images', $json_images );
	}

	// delete entries from the db.
	foreach ( $pending_rows as $row ) {
		$queue->delete( $row['id'] );
	}
}
add_action( 'pmxi_saved_post', 'cpib_import_images', 10, 3 );

/**
 * Hook into the after_xml_import action and delete temporarily downloaded images.
 *
 * @return void
 */
function after_xml_import() {

	// Array of all the files in the tmp directory.
	$files = list_files( WP_CONTENT_DIR . '/cpib-uploads', 2 );

	foreach ( $files as $file ) {
		if ( is_file( $file ) ) {
			wp_delete_file( $file );
		}
	}
}
add_action( 'pmxi_after_xml_import', 'after_xml_import', 10, 2 );

/**
 * Check if an ACF Field has been registered or exists or not.
 *
 * @param string $value title of the field group
 * 
 * Example: $exists = is_field_group_exists( 'Image Repeater' );
 * @return boolean
 */
function is_field_group_exists( $value ) {

	$exists = false;
	$args = array(
		'post_type' => 'acf-field',
		'posts_per_page' => -1
	);
	$field_groups = get_posts( $args );

	if ( $field_groups  ) {
		foreach ( $field_groups as $field_group ) {
			if ( $field_group->post_title == $value ) {
				$exists = true;
			}
		}
	}
	return $exists;
}