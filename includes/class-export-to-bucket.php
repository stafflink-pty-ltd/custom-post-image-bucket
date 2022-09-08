<?php

namespace cpib;
/**
 * Undocumented class
 */
class bucket {

	private $s3;
    private $options;
	private $envtype;
	private $folder;

	function __construct() {

		require ABSPATH . 'vendor/autoload.php';

        if (!file_exists( ABSPATH . '/tmp')) {
			mkdir( ABSPATH .'/tmp');
		}
		// check the environment so we don't overwrite images on staging/prod etc.
		$this->envtype = wp_get_environment_type();
		$this->folder = ( $this->envtype == 'production' ) ? 'images' : 'dev';
        $this->options = get_option('cpib_options');
		$this->s3 = new \Aws\S3\S3Client([
			'region'  => 'ap-south-1',
			'version' => 'latest',
			'credentials' => [
				'key'    => $this->options['cpib_bucket_access_key'],
				'secret' => $this->options['cpib_secret_key'],
			],
			'endpoint' => 'https://ap-south-1.linodeobjects.com/'
		]);
	}

	// Upload images to the chosen bucket
    public function upload( $images ) {
		error_log('uploading images...', 0);

		$json_images = [];

		foreach( $images as $image ) {

			if( empty( $image ) ) { continue; }

            $stream_options = [
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
				],
			];  

			$filename = basename( $image['image_url'] );
			$local_path = ABSPATH .'/tmp/tmpfile' . $filename;
			$file_contents = file_get_contents( $image['image_url'], false, stream_context_create($stream_options) );
			fopen( $local_path, "w" ) or die( "Error: Unable to open file." );
			file_put_contents( $local_path, $file_contents );

			$result = $this->s3->putObject([
				'Bucket' => $this->options['cpib_bucket_name'],
				'Key'    => $this->folder . '/' . $image['post_type']. '/' . $image['property_unique_id']. '/' . $filename,
				'SourceFile' => $local_path,
				'ACL' => 'public-read'
			]);

			if( $result['@metadata']['effectiveUri'] ) {
				$url = $result['@metadata']['effectiveUri'];
				$json_images[$image['post_id']][] = $url;
				//error_log( print_r('image uploaded: ' . $url, true), 0);
			}
		}
		error_log('images uploaded.', 0);
		return $json_images;
	}
    
	// List all of the images in a folder.
	public function list( $property_id, $post_type ) {

		$exists = $this->s3->listObjects([
			'Bucket' => $this->options['cpib_bucket_name'],
			'Prefix' => $this->folder . '/' . $post_type . '/' . $property_id
		]);

		$found_keys = [];
		if( is_array( $exists['Contents'] ) ) {
			foreach ($exists['Contents'] as $key ) {
				$found_keys[] = $key['Key'];
			}
			return $found_keys;
		} else {
			return false;
		}
	}

	public function delete( $images ) {

		foreach ( $images as $image ) {

			$this->s3->deleteObjects([
				'Bucket'  => $this->options['cpib_bucket_name'],
				'Delete' => [
					'Objects' => [
						[
							'Key' => $image
						]
					]
				]
			]);
		}
		return true;
	}

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
