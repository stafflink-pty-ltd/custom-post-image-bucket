<?php

function cpib_import_images( $post_id, $xml_node, $is_update ) {
    error_log('saved the post', 0);

    $options = explode( ',', get_option('cpib_options')['cpib_importer_ids'] );

    $import_id = ( isset( $_GET['id'] ) ? $_GET['id'] : ( isset( $_GET['import_id'] ) ? $_GET['import_id'] : 'new' ) );
    
    if( !in_array( $import_id, $options ) ) { return false; }

    $post_type = 'property';

    if( empty( (string) $xml_node->uniqueID ) ) {
        error_log('could not get unique property ID. cancelling.', 0);
        return false;
	}

    $queue = new cpib\sync\queue();
    $bucket = new cpib\bucket();

    foreach( $xml_node->objects->img as $image ) {
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

    error_log(print_r($json_images, true), 0);

    foreach( $json_images as $key => $value ) {
        //key is a string post id
        error_log(print_r('$key: ' . $key, true), 0);
        error_log(print_r($value, true), 0);
        foreach( $value as $image_url ) {
            $row = [ 'image' => $image_url ];
            $add_row = add_row('image_repeater', $row, $key);
        }
    }

    // Update post with image URL's from bucket.
    // foreach( $json_images as $image_post_id => $new_url ) {

    //     foreach( $new_url as $updated ) {

    //         $row = array(
    //             'image' => $updated,
    //         );
            
    //         $add_row = add_row('image_repeater', $row, [$image_post_id] );
    //         error_log(print_r($add_row, true), 0);
    //     }
    // }

    // delete entries from the db.
    foreach( $pending_rows as $row ) {
        $queue->delete($row['id']);
    }

}
add_action( 'pmxi_saved_post', 'cpib_import_images', 10, 3 );

// function after_xml_import( $import_id, $import ) {

//     $options = explode( ',', get_option('cpib_options')['cpib_importer_ids'] );
//     if( in_array( $import_id, $options ) ) {

//         $data = new cpib\sync\queue();
//         $bucket = new cpib\bucket();

//         $pending_rows = $data->get_pending('pending');

//         $json_images = $bucket->upload( $pending_rows );
//         error_log(print_r($json_images, true), 0);

//         foreach( $json_images as $property_id ) {
            
//             add_row('image_repeater', $row, $post_id );
//         }
//     }
// }
// add_action( 'pmxi_after_xml_import', 'after_xml_import', 10, 2 );

// /**
//  * Uploads rental images to Linode Object Storage.
//  *
//  * @param [type] $images
//  * @param [type] $post_id
//  * @return void
//  */
// function update_images_in_bucket( $is_update, $articleData, $xml_node, $post_id ) {
// 	// _el('pmxi_is_images_to_update triggered...');
// 	remove_then_upload_images_to_bucket( $xml_node, $post_id );
// }
// add_action( 'pmxi_is_images_to_update', 'update_images_in_bucket', 10, 4 );

// /**
//  * Undocumented function
//  *
//  * @param [type] $post_id
//  * @return void
//  */
// function upload_images_for_new_post_to_bucket($post_id, $xml_node, $is_update ) {
// 	// $xml_node->uniqueID
// 	// _el('pmxi_saved_post triggered...');
// 	remove_then_upload_images_to_bucket( $xml_node, $post_id );
// }
// add_action( 'pmxi_saved_post', 'upload_images_for_new_post_to_bucket', 10, 3 );

function before_delete_post( $post_id, $import ) {

    error_log('wp all import deleted a post.', 0);

}

add_action('pmxi_before_delete_post', 'before_delete_post', 10, 2);