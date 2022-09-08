<?php

function cpib_import_images( $post_id, $xml_node, $is_update ) {

    $post_object = get_post($post_id);

    $update = epl_wpimport_is_image_to_update( $is_update, (array) $post_object, $xml_node );

    if( $update == false ) {
        error_log('Skipping upload, images unchanged...', 0);
        return;
    } else {
        error_log('New images found. Uploading to bucket started.', 0);
    }

    // Get all the options set by this plugin
    $options = explode( ',', get_option('cpib_options')['cpib_importer_ids'] );
    
    // Check that the Importer ID is set in the CPIB settings.
    $import_id = ( isset( $_GET['id'] ) ? $_GET['id'] : ( isset( $_GET['import_id'] ) ? $_GET['import_id'] : 'new' ) );    
    if( !in_array( $import_id, $options ) ) { return false; }

    // Get the post type of the imported record
    $import = new PMXI_Import_Record();
    $import->getById($import_id);
    
    if ( ! $import->isEmpty() ) {
        $post_type = $import->options['custom_type'];
    } else {
        $post_type = 'post';
    }

    // Get the property unique ID
    if( empty( (string) $xml_node->uniqueID ) ) {
        //error_log('could not get unique property ID. cancelling.', 0);
        return false;
	}

    // Initialise the bucket and the DB qeueue
    $queue = new cpib\sync\queue();
    $bucket = new cpib\bucket();

    $images = $xml_node->objects->img; // Rex Softare version
    if( empty($images)) {
        $images = $xml_node->images->img; // Property ME version
    }

    // Insert each image into the database for processing.
    foreach( $images as $image ) {
        if( !empty( $image->attributes()->url ) ) { 
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

    // Get all images marked as "pending"
    $pending_rows = $queue->get_pending('pending');

    // Upload images from DB to bucket.
    $json_images = $bucket->upload( $pending_rows );

    //error_log(print_r($json_images, true), 0);

    foreach( $json_images as $key => $value ) {
        foreach( $value as $image_url ) {
            $row = [ 'image' => $image_url ];
            add_row('image_repeater', $row, $key);
        }
    }

    // delete entries from the db.
    foreach( $pending_rows as $row ) {
        $queue->delete($row['id']);
    }

}
add_action( 'pmxi_saved_post', 'cpib_import_images', 10, 3 );

/**
 * Clean out the tmp file
 */
function after_xml_import() {

    $files = list_files( ABSPATH . '/tmp', 2 );
    foreach ( $files as $file ) {
        if ( is_file( $file ) ) {
            wp_delete_file( $file );
        }
    }
}
add_action( 'pmxi_after_xml_import', 'after_xml_import', 10, 2 );