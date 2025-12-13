<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options.
delete_option( 'himoose_api_key' );
delete_option( 'himoose_domain' );
delete_option( 'himoose_auto_insert' );

// Delete post meta (clean up database).
delete_post_meta_by_key( '_himoose_job_id' );
delete_post_meta_by_key( '_himoose_podcast_label' );
delete_post_meta_by_key( '_himoose_embed_html' );
