<?php

namespace cpib;
/**
 * Undocumented class
 */
class bucket {

	private $s3;

	function __construct() {
		require ABSPATH . 'vendor/autoload.php';

		$this->s3 = new Aws\S3\S3Client([
			'region'  => 'ap-south-1',
			'version' => 'latest',
			'credentials' => [
				'key'    => get_option('cpib_bucket_access_key'),
				'secret' => get_option('cpib_bucket_secret_key'),
			],
			'endpoint' => 'https://ap-south-1.linodeobjects.com/'
		]);

		if (!file_exists( ABSPATH . '/tmp/tmpfile')) {
			mkdir( ABSPATH .'/tmp/tmpfile');
		}
	}

	// public function list( $property_id ) {
	// 	//_el('listing images...');

	// 	$exists = $this->s3->listObjects([
	// 		'Bucket' => get_option('cpib_bucket_name'),
	// 		'Prefix' => 'images/rental/'. $property_id
	// 	]);

	// 	$found_keys = [];
	// 	if( is_array( $exists['Contents'] ) ) {
	// 		foreach ($exists['Contents'] as $key ) {
	// 			$found_keys[] = $key['Key'];
	// 		}
	// 		//_el('found images...');
	// 		return $found_keys;
	// 	} else {
	// 		//_el('no images found to delete.');
	// 		return false;
	// 	}
	// }

	public function upload( $xml_node ) {

		$json_images = [];

		foreach( $xml_node['images']['img'] as $image ) {

			$image_url = (string) $image['url'];

			if( empty( $image_url ) ) { continue; }

			$filename = basename( $image_url );
			$local_path = ABSPATH .'/tmp/tmpfile' . $filename;
			$file_contents = file_get_contents( $image_url );
			$unique_id = (string) $xml_node['uniqueID'];
			fopen( $local_path, "w" ) or die( "Error: Unable to open file." );
			file_put_contents( $local_path, $file_contents );

			$result = $this->s3->putObject([
				'Bucket' => LINODE_BUCKET,
				'Key'    => 'images/rental/' . $unique_id. '/' . $filename,
				'SourceFile' => $local_path,
				'ACL' => 'public-read'
			]);

		//	_el('uploaded ' . $filename);

			if( $result['@metadata']['effectiveUri'] ) {
				$url = $result['@metadata']['effectiveUri'];
				$json_images[] = $url;
			}

		}
		return $json_images;


	}

	// public function delete( $images ) {
	// //	_el('deleting images...');

	// 	foreach ( $images as $image ) {

	// 		$this->s3->deleteObjects([
	// 			'Bucket'  => LINODE_BUCKET,
	// 			'Delete' => [
	// 				'Objects' => [
	// 					[
	// 						'Key' => $image
	// 					]
	// 				]
	// 			]
	// 		]);

	// 		//_el('deleted ' . $image);
	// 	}

	// 	return true;
	// }

}

// /**
//  * Undocumented function
//  *
//  * @param [type] $is_update
//  * @param [type] $articleData
//  * @param [type] $xml_node
//  * @param [type] $post_id
//  * @return void
//  */
// function remove_then_upload_images_to_bucket($xml_node, $post_id) {
// 	// Ignore posts that aren't rentals or updates.
// 	if( !in_array(get_post_type( $post_id ), ['rental'] ) ) return;

// 	$unique_id = (string) $xml_node->uniqueID;

// 	if( empty( $unique_id ) ) {
// 		$unique_id = $xml_node['uniqueID'];
// 		if( empty($unique_id) ) return false;
// 	} else {
// 		return false;
// 	}

// 	//_el('unique ID: ' . $unique_id);

// 	$linode = new linode_bucket();

// 	$current_images = $linode->list($unique_id);


// 	if( is_array($current_images) ) {
// 		$linode->delete( $current_images );
// 	}
// 	$json_images = $linode->upload( $xml_node );
// 	foreach( $json_images as $image ) {
// 		$row = array(
// 			'image' => $image,
// 		);
// 		add_row('image_repeater', $row, $post_id );

// 	}

// 	// return $update;
// 	return true;

// }
