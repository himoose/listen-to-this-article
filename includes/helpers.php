<?php
/**
 * Helper functions for the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the current site's domain.
 *
 * @return string The domain name (e.g., example.com).
 */
function himoose_get_domain() {
	$saved_domain = get_option( 'himoose_domain' );
	if ( ! empty( $saved_domain ) ) {
		// Sanitize: remove protocol and trailing slashes, trim whitespace.
		$domain = trim( $saved_domain );
		$domain = preg_replace( '#^https?://#', '', $domain );
		$domain = rtrim( $domain, '/' );
		return $domain;
	}

	$url = home_url();
	$parsed_url = wp_parse_url( $url );
	return isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
}

/**
 * Get the API Key.
 * Checks wp-config.php constant first, then database option.
 *
 * @return string The API Key.
 */
function himoose_get_api_key() {
	if ( defined( 'HIMOOSE_API_KEY' ) && HIMOOSE_API_KEY ) {
		return HIMOOSE_API_KEY;
	}
	return get_option( 'himoose_api_key' );
}
