<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

global $wpdb;
$wpdb_collate = $wpdb->collate;

$table_name = $wpdb->prefix . 'custom_post_images';
$sql =
    "CREATE TABLE {$table_name} (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `image_id` text NOT NULL,
        `post_id` int(10) NULL,
        `image_modtime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `image_url` text NOT NULL,
        `bucket_url` text NOT NULL,
        `property_unique_id` text NOT NULL,
        `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `status` enum('cancel', 'fail', 'pending','done') DEFAULT NULL,
        `post_type` text NULL,
        `import_id` text NOT NULL,
        PRIMARY KEY  (id)
    )
    COLLATE {$wpdb_collate}";

dbDelta( $sql );