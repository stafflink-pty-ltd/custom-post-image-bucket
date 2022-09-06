<?php

function cpib_import_images( $post_id, $xml_node, $is_update ) {

    $post_type = 'property';

    if( empty( (string) $xml_node->uniqueID ) ) {
        error_log('could not get unique property ID. cancelling.', 0);
        return false;
	}

    $queue = new cpib\sync\queue();

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
}
add_action( 'pmxi_saved_post', 'cpib_import_images', 10, 3 );


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