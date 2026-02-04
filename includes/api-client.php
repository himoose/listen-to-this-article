<?php
/**
 * API Client for Hi, Moose.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch list of podcasts from Hi, Moose API.
 *
 * @return array|WP_Error Array of podcasts or WP_Error on failure.
 */
function himoose_remote_get_podcasts() {
	$api_key = himoose_get_api_key();
	$domain  = himoose_get_domain();

	if ( empty( $api_key ) ) {
		return new WP_Error( 'missing_api_key', __( 'API Key is missing.', 'listen-to-this-article' ) );
	}

	if ( empty( $domain ) ) {
		return new WP_Error( 'missing_domain', __( 'Could not detect domain.', 'listen-to-this-article' ) );
	}

	$url = add_query_arg(
		array( 'domain' => $domain ),
		himoose_get_api_base() . '/getWordPressPodcasts'
	);

	$args = array(
		'headers' => array(
			'x-himoose-api-key'    => $api_key,
			'x-himoose-wp-version' => HIMOOSE_VERSION,
		),
		'timeout' => 15,
	);

	$response = wp_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		$body = wp_remote_retrieve_body( $response );
		$error_message = '';

		// Try to parse JSON error body.
		$data = json_decode( $body, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			if ( isset( $data['error']['message'] ) ) {
				$error_message = $data['error']['message'];
			} elseif ( isset( $data['message'] ) ) {
				$error_message = $data['message'];
			}
		}

		// Fallback if no specific message found in body.
		if ( empty( $error_message ) ) {
			if ( 401 === $code ) {
				$error_message = __( 'Invalid API Key. Please check your settings.', 'listen-to-this-article' );
			} elseif ( 403 === $code ) {
				$error_message = __( 'Access denied. Please check your domain settings.', 'listen-to-this-article' );
			} else {
				/* translators: %d: HTTP status code */
				$error_message = sprintf( __( 'HTTP Error %d', 'listen-to-this-article' ), $code );
			}
		}

		return new WP_Error( 'api_error', $error_message );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return new WP_Error( 'json_error', __( 'Invalid JSON response.', 'listen-to-this-article' ) );
	}

	return $data;
}

/**
 * Fetch full embed HTML for a specific job ID.
 *
 * @param string $job_id The job ID of the podcast.
 * @return string|WP_Error The embed HTML or WP_Error on failure.
 */
function himoose_remote_get_embed( $job_id ) {
	$api_key = himoose_get_api_key();
	$domain  = himoose_get_domain();

	if ( empty( $api_key ) ) {
		return new WP_Error( 'missing_api_key', __( 'API Key is missing.', 'listen-to-this-article' ) );
	}

	if ( empty( $job_id ) ) {
		return new WP_Error( 'missing_job_id', __( 'Job ID is missing.', 'listen-to-this-article' ) );
	}

	$url = add_query_arg(
		array(
			'jobId'  => $job_id,
			'domain' => $domain,
		),
		himoose_get_api_base() . '/getWordPressEmbed'
	);

	$args = array(
		'headers' => array(
			'x-himoose-api-key'    => $api_key,
			'x-himoose-wp-version' => HIMOOSE_VERSION,
		),
		'timeout' => 15,
	);

	$response = wp_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		$body = wp_remote_retrieve_body( $response );
		$error_message = '';

		// Try to parse JSON error body.
		$data = json_decode( $body, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			if ( isset( $data['error']['message'] ) ) {
				$error_message = $data['error']['message'];
			} elseif ( isset( $data['message'] ) ) {
				$error_message = $data['message'];
			}
		}

		// Fallback if no specific message found in body.
		if ( empty( $error_message ) ) {
			if ( 401 === $code ) {
				$error_message = __( 'Invalid API Key. Please check your settings.', 'listen-to-this-article' );
			} elseif ( 403 === $code ) {
				$error_message = __( 'Access denied. Please check your domain settings.', 'listen-to-this-article' );
			} else {
				/* translators: %d: HTTP status code */
				$error_message = sprintf( __( 'HTTP Error %d', 'listen-to-this-article' ), $code );
			}
		}

		return new WP_Error( 'api_error', $error_message );
	}

	$body = wp_remote_retrieve_body( $response );
	
	// Check if response is JSON (which contains the HTML in a field)
	$data = json_decode( $body, true );
	if ( json_last_error() === JSON_ERROR_NONE && is_array( $data ) && isset( $data['html'] ) ) {
		return $data['html'];
	}

	// Fallback: if it's not JSON or doesn't have 'html' field, assume body is HTML
	return $body;
}
