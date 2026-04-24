<?php
/**
 * Fired when the plugin is uninstalled (deleted via the WP admin).
 *
 * Removes ALL FieldForge data from the database:
 *   - All fieldforge_group CPT posts and their postmeta.
 *   - All _fieldforge_* and fieldforge_* postmeta on every post.
 *   - All wp_options entries written by FieldForge (options pages + settings).
 *
 * This file is executed by WordPress directly; it must not load the plugin itself.
 *
 * @package FieldForge
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Delete all fieldforge_group CPT posts (force-delete, bypasses trash).
$group_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'fieldforge_group'"
);
foreach ( $group_ids as $post_id ) {
	wp_delete_post( (int) $post_id, true );
}

// 2. Remove all FieldForge postmeta keys from every post.
//    Covers: field values (fieldforge_*) and ACF-compat back-refs (_fieldforge_*).
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"DELETE FROM {$wpdb->postmeta}
	 WHERE meta_key LIKE 'fieldforge\_%' ESCAPE '\\'
	    OR meta_key LIKE '\_fieldforge\_%' ESCAPE '\\'"
);

// 3. Remove all wp_options entries created by FieldForge.
//    Covers: options-page field values (fieldforge_{slug}_{field}) and settings.
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE 'fieldforge\_%' ESCAPE '\\'"
);

// 4. Remove individual known option keys that don't follow the prefix pattern.
delete_option( 'fieldforge_version' );
delete_option( 'fieldforge_local_json_sync' );
