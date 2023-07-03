<?php
/**
 *  File doc!
 *
 * @package cpib
 */

namespace cpib\sync;

/**
 * Queue!
 */
class Queue {

	const TYPE_MANUAL    = 'manual';
	const TYPE_AUTO      = 'auto';
	const STATUS_PENDING = 'pending';
	const STATUS_CANCEL  = 'cancel';
	const STATUS_FAIL    = 'fail';
	const STATUS_DONE    = 'done';

	static $table_name = 'custom_post_images';

	/**
	 * Undocumented function
	 *
	 * @param [type] $post_id
	 * @param [type] $image_id
	 * @param [type] $property_unique_id
	 * @param [type] $image_url
	 * @param [type] $image_modtime
	 * @param [type] $post_type
	 * @param [type] $image_type = image | floorplan
	 * @param [type] $status
	 * @return void
	 */
	public static function insert( $post_id, $image_id, $property_unique_id, $image_url, $image_modtime, $post_type, $image_type = 'image', $status = self::STATUS_PENDING ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . self::$table_name,
			array(
				'post_id'            => $post_id,
				'image_id'           => $image_id,
				'property_unique_id' => $property_unique_id,
				'image_url'          => $image_url,
				'image_modtime'      => $image_modtime,
				'post_type'          => $post_type,
				'image_type'         => $image_type,
				'status'             => $status,
			)
		);

		return $wpdb->insert_id;
	}

	/**
	 * Update images.
	 *
	 * @param [type] $row_id
	 * @param [type] $data
	 * @return void
	 */
	public static function update( $row_id, $data ) {
		global $wpdb;

		$wpdb->update( $wpdb->prefix . self::$table_name, $data, array( 'status' => $status ) );
	}

	/**
	 * Get pending images
	 *
	 * @param [type] $status
	 * @return void
	 */
	public static function get_pending( $status, $image_type = 'image' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::$table_name;

		$sql = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE `status` = %s AND `image_type` = %s", $status, $image_type);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $row_id
	 * @return void
	 */
	public static function delete( $row_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::$table_name;

		$wpdb->delete( $table_name, array( 'id' => $row_id ) );
	}
}
