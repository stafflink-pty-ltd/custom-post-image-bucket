<?php

namespace cpib;

/**
 * Undocumented class
 */
class Bucket {

	/**
	 * The S3 Bucket object.
	 *
	 * @var object
	 */
	private $s3;
	/**
	 * Options from the DB to configure the plugin.
	 *
	 * @var array
	 */
	private $options;
	/**
	 * Change directory based on environment.
	 *
	 * @var string
	 */
	private $envtype;
	/**
	 * Folder to upload to.
	 *
	 * @var string
	 */
	private $folder;

	/**
	 * Constructor!
	 */
	public function __construct() {

		// Create the temporary folder if it doesn't exist.
		if ( ! file_exists( WP_CONTENT_DIR . '/uploads/cpib-uploads' ) ) {
			mkdir( WP_CONTENT_DIR . '/uploads/cpib-uploads' );
		}
		// check the environment so we don't overwrite images on staging/prod etc.
		$this->folder  = ( 'production' === wp_get_environment_type() ) ? 'images' : 'dev';
		$this->options = get_option( 'cpib_options' );
		$this->s3      = new \Aws\S3\S3Client(
			array(
				'region'      => $this->options['cpib_region'],
				'version'     => 'latest',
				'credentials' => array(
					'key'    => $this->options['cpib_bucket_access_key'],
					'secret' => $this->options['cpib_secret_key'],
				),
				'endpoint'    => $this->options['cpib_bucket_endpoint'],
			)
		);
	}
	/**
	 * Uploads images to AWS S3 and returns an array of uploaded image URLs.
	 *
	 * @param array $images An array containing image details to be uploaded.
	 *
	 * @return array An array of uploaded image URLs.
	 */
	public function upload($images)
	{
		$json_images = array();

		foreach ($images as $image) {
			if (empty($image)) {
				continue;
			}

			$stream_options = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
				),
			);

			$filename = basename($image['image_url']);
			$local_path = WP_CONTENT_DIR . '/uploads/cpib-uploads/' . $filename;
			
			// Fetch image from URL
			$file_contents = file_get_contents($image['image_url'], false, stream_context_create($stream_options));
			
			// Save image locally
			$file_saved = file_put_contents($local_path, $file_contents);
			if ( ! $file_saved ) { 
				error_log('file could not be saved locally, skipping.', 0); 
				continue; 
			}

			$result = $this->s3->putObject(array(
				'Bucket' => $this->options['cpib_bucket_name'],
				'Key' => $this->folder . '/' . $image['post_type'] . '/' . $image['property_unique_id'] . '/' . $filename,
				'SourceFile' => $local_path,
				'ACL' => 'public-read',
			));
		
			if ($result['@metadata']['effectiveUri']) {
				$url = $result['@metadata']['effectiveUri'];
				$json_images[$image['post_id']][$image['image_type']][] = $url;
			}

		}
		return $json_images;
	}


	/**
	 * List all of the images in a folder.
	 *
	 * @param string $property_id The ID Of the property.
	 * @param string $post_type The post type.
	 * @return array|false Returns an array of objects, or false on failure.
	 */
	public function list( $property_id, $post_type ) {

		$exists = $this->s3->listObjects(
			array(
				'Bucket' => $this->options['cpib_bucket_name'],
				'Prefix' => $this->folder . '/' . $post_type . '/' . $property_id,
			)
		);

		$found_keys = array();

		if ( is_array( $exists['Contents'] ) ) {
			foreach ( $exists['Contents'] as $key ) {
				$found_keys[] = $key['Key'];
			}
			return $found_keys;
		} else {
			return false;
		}
	}

	/**
	 * Delete images from bucket.
	 *
	 * @param array $images An array of images for deletion from bucket.
	 * @return bool True on success, False on fail.
	 */
	public function delete( $images ) {

		foreach ( $images as $image ) {

			$this->s3->deleteObjects(
				array(
					'Bucket' => $this->options['cpib_bucket_name'],
					'Delete' => array(
						'Objects' => array(
							array(
								'Key' => $image,
							),
						),
					),
				)
			);
		}
		return true;
	}

}
