<?php
/**
 * API Client for Hi, Moose.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch list of audio from Hi, Moose API.
 *
 * @return array|WP_Error Array of audio or WP_Error on failure.
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

/**
	* Start a WordPress audio generation job.
	*
	* @param array $payload Generation payload.
	* @return array|WP_Error Response array (expects at least jobId/status) or WP_Error on failure.
	*/
function himoose_remote_generate_podcast( $payload ) {
	$api_key = himoose_get_api_key();
	$domain  = himoose_get_domain();

	if ( empty( $api_key ) ) {
		return new WP_Error( 'missing_api_key', __( 'API Key is missing.', 'listen-to-this-article' ) );
	}

	if ( empty( $domain ) ) {
		return new WP_Error( 'missing_domain', __( 'Could not detect domain.', 'listen-to-this-article' ) );
	}

	$url = himoose_get_api_base() . '/generateWordPressPodcast';

	$args = array(
		'headers' => array(
			'x-himoose-api-key'    => $api_key,
			'x-himoose-wp-version' => HIMOOSE_VERSION,
			'content-type'         => 'application/json; charset=utf-8',
		),
		'timeout' => 20,
		'body'    => wp_json_encode( $payload ),
	);

	$response = wp_remote_post( $url, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( 200 !== $code ) {
		$error_message = '';
		$error_data    = array();

		if ( json_last_error() === JSON_ERROR_NONE && is_array( $data ) ) {
			if ( isset( $data['error']['message'] ) && is_string( $data['error']['message'] ) ) {
				$error_message = $data['error']['message'];
			} elseif ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
				$error_message = $data['error'];
			} elseif ( isset( $data['message'] ) && is_string( $data['message'] ) ) {
				$error_message = $data['message'];
			}

			// Pass through optional structured fields (e.g. upgradeUrl).
			foreach ( array( 'code', 'upgradeUrl', 'limit' ) as $key ) {
				if ( isset( $data[ $key ] ) ) {
					$error_data[ $key ] = $data[ $key ];
				}
			}
		}

		if ( empty( $error_message ) ) {
			/* translators: %d: HTTP status code */
			$error_message = sprintf( __( 'HTTP Error %d', 'listen-to-this-article' ), $code );
		}

		$err = new WP_Error( 'api_error', $error_message );
		if ( ! empty( $error_data ) ) {
			$err->add_data( $error_data );
		}
		return $err;
	}

	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
		return new WP_Error( 'json_error', __( 'Invalid JSON response.', 'listen-to-this-article' ) );
	}

	return $data;
}

/**
	* Get the status of a WordPress podcast generation job.
	*
	* @param string $job_id Job ID.
	* @return array|WP_Error Response array or WP_Error on failure.
	*/
function himoose_remote_get_podcast_status( $job_id ) {
	$api_key = himoose_get_api_key();
	$domain  = himoose_get_domain();

	if ( empty( $api_key ) ) {
		return new WP_Error( 'missing_api_key', __( 'API Key is missing.', 'listen-to-this-article' ) );
	}

	if ( empty( $domain ) ) {
		return new WP_Error( 'missing_domain', __( 'Could not detect domain.', 'listen-to-this-article' ) );
	}

	if ( empty( $job_id ) ) {
		return new WP_Error( 'missing_job_id', __( 'Job ID is missing.', 'listen-to-this-article' ) );
	}

	$url = add_query_arg(
		array(
			'jobId'  => $job_id,
			'domain' => $domain,
		),
		himoose_get_api_base() . '/getWordPressPodcastStatus'
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
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( 200 !== $code ) {
		$error_message = '';
		$error_data    = array();

		if ( json_last_error() === JSON_ERROR_NONE && is_array( $data ) ) {
			if ( isset( $data['error']['message'] ) && is_string( $data['error']['message'] ) ) {
				$error_message = $data['error']['message'];
			} elseif ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
				$error_message = $data['error'];
			} elseif ( isset( $data['message'] ) && is_string( $data['message'] ) ) {
				$error_message = $data['message'];
			}
			foreach ( array( 'code', 'upgradeUrl', 'limit' ) as $key ) {
				if ( isset( $data[ $key ] ) ) {
					$error_data[ $key ] = $data[ $key ];
				}
			}
		}

		if ( empty( $error_message ) ) {
			/* translators: %d: HTTP status code */
			$error_message = sprintf( __( 'HTTP Error %d', 'listen-to-this-article' ), $code );
		}

		$err = new WP_Error( 'api_error', $error_message );
		if ( ! empty( $error_data ) ) {
			$err->add_data( $error_data );
		}
		return $err;
	}

	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
		return new WP_Error( 'json_error', __( 'Invalid JSON response.', 'listen-to-this-article' ) );
	}

	return $data;
}

/**
 * Initialize Quick Connect.
 *
 * @param string $email The user's email address.
 * @param string $install_id The WP installation ID.
 * @param string $domain The normalized domain.
 * @param string $redirect_page The admin page to return to after connection.
 * @return array|WP_Error Response array or WP_Error on failure.
 */
function himoose_remote_init_quick_connect( $email, $install_id, $domain, $redirect_page = 'himoose-settings' ) {
	$url = himoose_get_api_base() . '/initWordPressConnect';

	if ( ! in_array( $redirect_page, array( 'himoose-settings', 'himoose-onboarding' ), true ) ) {
		$redirect_page = 'himoose-settings';
	}

	$state = wp_generate_password( 24, false );
	set_transient(
		'himoose_quick_connect_state_' . get_current_user_id(),
		array(
			'state'         => $state,
			'redirect_page' => $redirect_page,
		),
		HOUR_IN_SECONDS
	);
	$return_url = admin_url( 'options-general.php?page=' . $redirect_page . '&himoose_connect=success&state=' . urlencode( $state ) );

	$payload = array(
		'email'             => $email,
		'install_id'        => $install_id,
		'normalized_domain' => $domain,
		'return_url'        => $return_url,
	);

	$args = array(
		'headers' => array(
			'content-type'         => 'application/json',
			'x-himoose-wp-version' => HIMOOSE_VERSION,
		),
		'timeout' => 20,
		'body'    => wp_json_encode( $payload ),
	);

	$response = wp_remote_post( $url, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( 200 !== $code ) {
		return new WP_Error( 'api_error', __( 'HTTP Error ', 'listen-to-this-article' ) . $code );
	}

	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
		return new WP_Error( 'json_error', __( 'Invalid JSON response.', 'listen-to-this-article' ) );
	}

	return $data;
}

/**
 * AJAX handler for Quick Connect.
 */
function himoose_ajax_quick_connect() {
check_ajax_referer( 'himoose_quick_connect_nonce', 'nonce' );

if ( ! current_user_can( 'manage_options' ) ) {
wp_send_json_error( array( 'message' => __( 'Permission denied.', 'listen-to-this-article' ) ) );
}

$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
if ( empty( $email ) ) {
wp_send_json_error( array( 'message' => __( 'Invalid email.', 'listen-to-this-article' ) ) );
}

$install_id = himoose_get_install_id();
$domain     = himoose_get_domain();
	$redirect_page = isset( $_POST['redirect_page'] ) ? sanitize_key( wp_unslash( $_POST['redirect_page'] ) ) : 'himoose-settings';

	$response = himoose_remote_init_quick_connect( $email, $install_id, $domain, $redirect_page );

if ( is_wp_error( $response ) ) {
wp_send_json_error( array( 'message' => $response->get_error_message() ) );
}

if ( isset( $response['status'] ) && 'success' === $response['status'] && ! empty( $response['connect_token'] ) ) {
wp_send_json_success( array( 'connect_token' => $response['connect_token'] ) );
}

wp_send_json_error( array( 'message' => __( 'Unable to start Quick Connect. Please try our standard setup method.', 'listen-to-this-article' ) ) );
}
add_action( 'wp_ajax_himoose_quick_connect', 'himoose_ajax_quick_connect' );

/**
 * Exchange temporary token for a permanent API key.
 *
 * @param string $token The temporary authorization token.
 * @return array|WP_Error Response array or WP_Error on failure.
 */
function himoose_remote_exchange_token( $token ) {
	$url = himoose_get_api_base() . '/exchangeWordPressToken';

	$payload = array(
		'token' => $token,
	);

	$args = array(
		'headers' => array(
			'content-type'         => 'application/json',
			'x-himoose-wp-version' => HIMOOSE_VERSION,
		),
		'timeout' => 20,
		'body'    => wp_json_encode( $payload ),
	);

	$response = wp_remote_post( $url, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( 200 !== $code ) {
		$error_message = isset( $data['message'] ) ? $data['message'] : __( 'HTTP Error ', 'listen-to-this-article' ) . $code;
		return new WP_Error( 'api_error', $error_message );
	}

	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
		return new WP_Error( 'json_error', __( 'Invalid JSON response.', 'listen-to-this-article' ) );
	}

	return $data;
}

/**
 * Listen for OAuth-style callback token from Quick Connect.
 */
function himoose_catch_auth_token_redirect() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['himoose_auth_token'] ) && ! empty( $_GET['himoose_auth_token'] ) ) {
		// Validate CSRF state token
		$transient_key = 'himoose_quick_connect_state_' . get_current_user_id();
		$transient_data = get_transient( $transient_key );
		$saved_state    = is_array( $transient_data ) && isset( $transient_data['state'] ) ? $transient_data['state'] : $transient_data;
		$redirect_page  = is_array( $transient_data ) && isset( $transient_data['redirect_page'] ) ? $transient_data['redirect_page'] : 'himoose-settings';

		if ( ! in_array( $redirect_page, array( 'himoose-settings', 'himoose-onboarding' ), true ) ) {
			$redirect_page = 'himoose-settings';
		}
		
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['state'] ) || sanitize_text_field( wp_unslash( $_GET['state'] ) ) !== $saved_state ) {
			wp_die( esc_html__( 'Invalid security state token. Please try connecting again so we can securely verify the request.', 'listen-to-this-article' ) );
		}
		delete_transient( $transient_key );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = sanitize_text_field( wp_unslash( $_GET['himoose_auth_token'] ) );
		$response = himoose_remote_exchange_token( $token );
		
		$actual_api_key = '';
		if ( ! is_wp_error( $response ) && isset( $response['api_key'] ) ) {
			$actual_api_key = sanitize_text_field( $response['api_key'] );
		}

		if ( ! empty( $actual_api_key ) ) {
			update_option( 'himoose_api_key', $actual_api_key );
			$redirect_url = admin_url( 'options-general.php?page=' . $redirect_page . '&himoose_connect=success' );
		} else {
			$error_msg = is_wp_error( $response ) ? $response->get_error_message() : __( 'Empty API key returned.', 'listen-to-this-article' );
			$redirect_url = admin_url( 'options-general.php?page=' . $redirect_page . '&himoose_connect=error&himoose_msg=' . urlencode( $error_msg ) );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}
}
add_action( 'admin_init', 'himoose_catch_auth_token_redirect' );

