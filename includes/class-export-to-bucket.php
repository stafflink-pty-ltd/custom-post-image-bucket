<?php

namespace cpib;

/**
 * Undocumented class
 */
class Bucket {

	/**
	 * Undocumented variable
	 *
	 * @var [type]
	 */
	private $s3;
	/**
	 * Undocumented variable
	 *
	 * @var [type]
	 */
	private $options;
	/**
	 * Undocumented variable
	 *
	 * @var [type]
	 */
	private $envtype;
	/**
	 * Undocumented variable
	 *
	 * @var [type]
	 */
	private $folder;

	/**
	 * Constructor!
	 */
	public function __construct() {

		require ABSPATH . 'vendor/autoload.php';

		if ( ! file_exists( ABSPATH . '/tmp' ) ) {
			mkdir( ABSPATH . '/tmp' );
		}
		// check the environment so we don't overwrite images on staging/prod etc.
		$this->envtype = wp_get_environment_type();
		$this->folder  = ( 'production' === $this->envtype ) ? 'images' : 'dev';
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
	 * Upload images to the chosen bucket.
	 *
	 * @param [type] $images
	 * @return void
	 */
	public function upload( $images ) {

		$json_images = array();

		foreach ( $images as $image ) {

			if ( empty( $image ) ) {
				continue;
			}

			$stream_options = array(
				'ssl' => array(
					'verify_peer'      => false,
					'verify_peer_name' => false,
				),
			);

			$filename      = basename( $image['image_url'] );
			$local_path    = ABSPATH . 'tmp/' . $filename;
			$file_contents = file_get_contents( $image['image_url'], false, stream_context_create( $stream_options ) );

			fopen( $local_path, 'w' ) || die( 'Error: Unable to open file.' );
			file_put_contents( $local_path, $file_contents );

			$result = $this->s3->putObject(
				array(
					'Bucket'     => $this->options['cpib_bucket_name'],
					'Key'        => $this->folder . '/' . $image['post_type'] . '/' . $image['property_unique_id'] . '/' . $filename,
					'SourceFile' => $local_path,
					'ACL'        => 'public-read',
				)
			);

			if ( $result['@metadata']['effectiveUri'] ) {
				$url                                = $result['@metadata']['effectiveUri'];
				$json_images[ $image['post_id'] ][] = $url;
			}
		}
		error_log( 'images uploaded.', 0 );
		return $json_images;
	}

	/**
	 * List all of the images in a folder.
	 *
	 * @param [type] $property_id
	 * @param [type] $post_type
	 * @return void
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
	 * Undocumented function
	 *
	 * @param [type] $images
	 * @return void
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
