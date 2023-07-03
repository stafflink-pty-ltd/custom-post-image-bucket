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

	$floorpans = $xml_node->objects->floorplan;
	
	if ( empty( $images ) ) {
		$images = $xml_node->images->img; // Property ME version.
	}

	foreach($floorpans as $floorplan) {
		if ( ! empty( $floorplan->attributes()->url ) ) {
			$queue->insert(
				$post_id,
				(string) $floorplan->attributes()->id,
				(string) $xml_node->uniqueID,
				(string) $floorplan->attributes()->url,
				(string) $floorplan->attributes()->modTime,
				$post_type,
				'floorplan'
			);
		}
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
				$post_type,
				'image'
			);
		}
	}


	$pending_image_rows = $queue->get_pending( 'pending', 'image');
	$pending_floorplan_rows = $queue->get_pending( 'pending', 'floorplan' );
	
	//merge arrays so that they are processed together.
	$pending_rows = array_merge( $pending_image_rows, $pending_floorplan_rows );

	// Download images to local and Upload the images to the bucket.
	$json_images  = $bucket->upload( $pending_rows );
	// TODO: update this input to be an option on the options page.
	$exists = is_field_group_exists( 'Image Repeater' );

	if ( $exists ) {
		error_log('repeater field exists, adding there', 0);
		foreach ( $json_images as $key => $value ) {
			foreach ( $value as $type => $image_url ) {
				if ( $type == 'floorplan' ) {
					for ($i = 0; $i < count($image_url); $i++) {
						$meta_key = ($i == 0) ? 'property_floorplan' : 'property_floorplan_' . ($i + 1);
						update_post_meta($post_id, $meta_key, $image_url[$i]);
					}
				}else {
					foreach ( $image_url as $image ) {
						add_row( $acf_repeater, array( 'image' => $image ), $post_id );
					}
				}
			}
		}
	} else {
		error_log( ' repeater field doesnt exist. Updating cpib_property_images', 0);
		$image_lists = array();
		foreach ( $json_images as $key => $value ) {
			foreach ( $value as $type => $image_url ) {
				if ( $type == 'floorplan' ) {
					var_dump($image_url);
					var_dump(count($image_url));
					for ($i = 0; $i < count($image_url); $i++) {
						$meta_key = ($i == 0) ? 'property_floorplan' : 'property_floorplan_' . ($i + 1);
						var_dump($meta_key);
						update_post_meta($post_id, $meta_key, $image_url[$i]);
					}
				}else {
					foreach ( $image_url as $image ) {
						$image_lists[$post_id][] = $image;
					}
				}
			}
		}
		update_post_meta( $post_id, 'cpib_property_images', $image_lists );
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
	$files = scandir( WP_CONTENT_DIR . '/uploads/cpib-uploads');

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
	// issue - does not show acf field group added via php acf_add_local_field_group()
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