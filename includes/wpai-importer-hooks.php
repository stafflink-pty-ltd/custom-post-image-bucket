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

	$post_object = get_post( $post_id );
	$update      = epl_wpimport_is_image_to_update( $is_update, (array) $post_object, $xml_node );
	$options     = array_map( 'intval', explode( ',', get_option( 'cpib_options' )['cpib_importer_ids'] ) );
	$import_id   = ( isset( $_GET['id'] ) ? $_GET['id'] : ( isset( $_GET['import_id'] ) ? $_GET['import_id'] : 'new' ) );
	$post_type   = 'post';

	// exit early.
	if ( false === $update || ! in_array( (int) $import_id, $options, true ) || empty( (string) $xml_node->uniqueID ) ) {
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

	if ( get_field('image_repeater' ) ) {
		error_log('repeater field exists, adding there', 0);
		foreach ( $json_images as $key => $value ) {
			foreach ( $value as $image_url ) {
				add_row( 'image_repeater', array( 'image' => $image_url ), $key );
			}
		}
	} else {
		update_post_meta( $post_id, 'cpib_property_images', $json_images );
	}

	// delete entries from the db.
	foreach ( $pending_rows as $row ) {
		$queue->delete( $row['id'] );
	}
}
add_action( 'pmxi_saved_post', 'cpib_import_images', 10, 3 );

/**
 * Clean out the tmp file
 */
function after_xml_import() {
	error_log('deleting files', 0);
	$files = list_files( ABSPATH . '/tmp', 2 );
	foreach ( $files as $file ) {
		if ( is_file( $file ) ) {
			wp_delete_file( $file );
		}
	}
}
add_action( 'pmxi_after_xml_import', 'after_xml_import', 10, 2 );
