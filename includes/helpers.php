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

/**
 * Get the Hi, Moose API base URL.
 *
 * Defaults to production, but can be overridden via:
 * - wp-config.php constant HIMOOSE_API_BASE
 * - Filter 'himoose_api_base'
 *
 * @return string API base URL without trailing slash.
 */
function himoose_get_api_base() {
	$base = defined( 'HIMOOSE_API_BASE' ) ? HIMOOSE_API_BASE : 'https://wp-api.himoose.com/v1';
	$base = rtrim( trim( (string) $base ), '/' );
	/**
	 * Filters the Hi, Moose API base URL.
	 *
	 * @param string $base API base URL without trailing slash.
	 */
	return rtrim( trim( (string) apply_filters( 'himoose_api_base', $base ) ), '/' );
}

/**
 * Get the Hi, Moose app (dashboard) base URL.
 *
 * Defaults to production, but can be overridden via:
 * - wp-config.php constant HIMOOSE_APP_BASE
 * - Filter 'himoose_app_base'
 *
 * @return string App base URL without trailing slash.
 */
function himoose_get_app_base() {
	$base = defined( 'HIMOOSE_APP_BASE' ) ? HIMOOSE_APP_BASE : 'https://app.himoose.com';
	$base = rtrim( trim( (string) $base ), '/' );
	/**
	 * Filters the Hi, Moose app base URL.
	 *
	 * @param string $base App base URL without trailing slash.
	 */
	return rtrim( trim( (string) apply_filters( 'himoose_app_base', $base ) ), '/' );
}
