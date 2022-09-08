<?php
namespace cpib\sync;

class queue {

    const TYPE_MANUAL = 'manual';
    const TYPE_AUTO = 'auto';
    const STATUS_PENDING = 'pending';
    const STATUS_CANCEL = 'cancel';
    const STATUS_FAIL = 'fail';
    const STATUS_DONE = 'done';

    static $table_name = 'custom_post_images';

    static function insert( $post_id, $image_id, $property_unique_id, $image_url, $image_modtime, $post_type, $status = Queue::STATUS_PENDING ) {
        global $wpdb;

        $wpdb->insert($wpdb->prefix.self::$table_name, [
            'post_id' => $post_id,
            'image_id' => $image_id,
            'property_unique_id' => $property_unique_id,
            'image_url' => $image_url,
            'image_modtime' => $image_modtime,
            'post_type' => $post_type,
            'status' => $status
        ]);

        return $wpdb->insert_id;
    }

    static function update($row_id, $data){
        global $wpdb;

        $wpdb->update($wpdb->prefix.self::$table_name, $data, ['status' => $status]);
    }

    static function get_pending($status){
        global $wpdb;

        $table_name = $wpdb->prefix.self::$table_name;

        $sql = $wpdb->prepare("SELECT * FROM {$table_name} WHERE `status` = %s", $status );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    static function delete($row_id) {
        global $wpdb;

        $table_name = $wpdb->prefix.self::$table_name;

        $wpdb->delete($table_name, ['id' => $row_id]);
    }
}