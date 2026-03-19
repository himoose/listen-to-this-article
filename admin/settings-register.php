<?php
/**
 * Register settings for the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display success notice after automated connection.
 */
function himoose_connect_success_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['page'] ) && 'himoose-settings' === $_GET['page'] && isset( $_GET['himoose_connect'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'success' === $_GET['himoose_connect'] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php esc_html_e( 'Success!', 'listen-to-this-article' ); ?></strong> <?php esc_html_e( 'Your account has been successfully connected and your API Key is securely saved.', 'listen-to-this-article' ); ?></p>
			</div>
			<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( 'error' === $_GET['himoose_connect'] ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$error_msg = isset( $_GET['himoose_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['himoose_msg'] ) ) : __( 'We successfully caught the callback, but the backend did not return a valid API Key. Please try mapping it manually.', 'listen-to-this-article' );
			?>
			<div class="notice notice-error is-dismissible">
				<p><strong><?php esc_html_e( 'Connection Failed:', 'listen-to-this-article' ); ?></strong> <?php echo esc_html( $error_msg ); ?></p>
			</div>
			<?php
		}
	}
}
add_action( 'admin_notices', 'himoose_connect_success_notice' );

/**
 * Initialize settings.
 */
function himoose_settings_init() {
	$user_id = get_current_user_id();
	$successful_assignments = $user_id ? (int) get_user_meta( $user_id, 'himoose_successful_assignments_count', true ) : 0;
	$review_prompt_dismissed = $user_id ? (bool) get_user_meta( $user_id, 'himoose_review_prompt_dismissed', true ) : false;

	register_setting(
		'himoose_options_group',
		'himoose_api_key',
		array(
			'sanitize_callback' => 'himoose_sanitize_api_key',
		)
	);

	register_setting(
		'himoose_options_group',
		'himoose_domain',
		array(
			'sanitize_callback' => 'himoose_sanitize_domain',
		)
	);

	register_setting(
		'himoose_options_group',
		'himoose_auto_insert',
		array(
			'sanitize_callback' => 'himoose_sanitize_checkbox',
		)
	);

	add_settings_section(
		'himoose_section_developers',
		__( 'Configuration', 'listen-to-this-article' ),
		'himoose_section_developers_callback',
		'himoose-settings'
	);

	add_settings_field(
		'himoose_api_key',
		__( 'Hi, Moose API Key', 'listen-to-this-article' ),
		'himoose_field_api_key_callback',
		'himoose-settings',
		'himoose_section_developers'
	);

	add_settings_field(
		'himoose_domain',
		__( 'Website Domain', 'listen-to-this-article' ),
		'himoose_field_domain_callback',
		'himoose-settings',
		'himoose_section_developers'
	);

	add_settings_field(
		'himoose_auto_insert',
		__( 'Auto Insert Player', 'listen-to-this-article' ),
		'himoose_field_auto_insert_callback',
		'himoose-settings',
		'himoose_section_developers'
	);

	// Gentle review ask once the user has had success a couple of times.
	if ( $user_id && $successful_assignments >= 2 && ! $review_prompt_dismissed ) {
		add_settings_section(
			'himoose_section_review',
			__( 'Support This Plugin 👋🫎', 'listen-to-this-article' ),
			'himoose_section_review_callback',
			'himoose-settings'
		);

		add_settings_field(
			'himoose_review_prompt',
			__( 'Leave a review', 'listen-to-this-article' ),
			'himoose_field_review_prompt_callback',
			'himoose-settings',
			'himoose_section_review'
		);
	}
}
add_action( 'admin_init', 'himoose_settings_init' );

/**
 * Enqueue scripts for settings page.
 *
 * @param string $hook The current admin page.
 */
function himoose_settings_enqueue_scripts( $hook ) {
	if ( 'settings_page_himoose-settings' !== $hook ) {
		return;
	}

	wp_enqueue_script( 'himoose-admin-js', HIMOOSE_PLUGIN_URL . 'admin/assets/admin.js', array( 'jquery' ), HIMOOSE_VERSION, true );
	wp_localize_script(
		'himoose-admin-js',
		'himooseAjax',
		array(
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'quickConnectNonce'  => wp_create_nonce( 'himoose_quick_connect_nonce' ),
			'appBase'            => himoose_get_app_base(),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'himoose_settings_enqueue_scripts' );

/**
 * Sanitize checkbox.
 *
 * @param mixed $input The input value.
 * @return string '1' or ''.
 */
function himoose_sanitize_checkbox( $input ) {
	return ( '1' === $input ) ? '1' : '';
}

/**
 * Sanitize API Key.
 *
 * @param string $new_value The new API key.
 * @return string The sanitized key or the old key if new is empty.
 */
function himoose_sanitize_api_key( $new_value ) {
	// Check if delete checkbox is checked.
	if ( isset( $_POST['himoose_delete_api_key'] ) ) {
		// Verify nonce before processing the delete action.
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'himoose_options_group-options' ) ) {
			if ( '1' === $_POST['himoose_delete_api_key'] ) {
				return '';
			}
		}
	}

	$old_value = get_option( 'himoose_api_key' );
	if ( empty( $new_value ) && ! empty( $old_value ) ) {
		return $old_value;
	}
	return sanitize_text_field( $new_value );
}

/**
 * Sanitize Domain.
 *
 * @param string $input The input domain.
 * @return string The sanitized domain.
 */
function himoose_sanitize_domain( $input ) {
	// Remove protocol and trailing slashes.
	$domain = trim( $input );
	$domain = preg_replace( '#^https?://#', '', $domain );
	$domain = rtrim( $domain, '/' );
	
	// Basic text sanitization.
	return sanitize_text_field( $domain );
}

/**
 * Section callback.
 */
function himoose_section_developers_callback() {
	echo '<p>' . esc_html__( 'Connect your site to Hi, Moose to start embedding audio versions of your content.', 'listen-to-this-article' ) . '</p>';
}

/**
 * Review section callback.
 */
function himoose_section_review_callback() {
	echo '<p>' . esc_html__( 'If you’ve found this plugin useful, a review is a huge help for us. We greatly appreciate it.', 'listen-to-this-article' ) . '</p>';
}

/**
 * Review prompt field callback.
 */
function himoose_field_review_prompt_callback() {
	$review_url = 'https://wordpress.org/support/plugin/listen-to-this-article/reviews/';
	?>
	<p style="margin:0 0 8px;">
		<?php echo esc_html__( '⭐⭐⭐⭐⭐ Please consider leaving a 5-star review; it really helps!', 'listen-to-this-article' ); ?>
	</p>
	<p style="margin:0;">
		<a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $review_url ); ?>">
			<?php esc_html_e( 'Leave a review', 'listen-to-this-article' ); ?>
		</a>
	</p>
	<?php
}

/**
 * API Key field callback.
 */
function himoose_field_api_key_callback() {
	$api_key = himoose_get_api_key();
	$is_constant = defined( 'HIMOOSE_API_KEY' ) && HIMOOSE_API_KEY;

	if ( $is_constant ) {
		?>
		<input type="password" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" disabled />
		<p class="description">
			<?php esc_html_e( 'Your API key is defined in wp-config.php.', 'listen-to-this-article' ); ?>
		</p>
		<?php
		return;
	}

	$placeholder = ! empty( $api_key ) ? __( 'API Key is set. Enter a new key to update.', 'listen-to-this-article' ) : '';

	// Calculate domain for the registration link.
	$saved_domain = get_option( 'himoose_domain' );
	$url = home_url();
	$parsed_url = wp_parse_url( $url );
	$detected_domain = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
	$domain_value = ! empty( $saved_domain ) ? $saved_domain : $detected_domain;
	
	$register_url = himoose_get_app_base() . '/register?source=wordpress&domain=' . urlencode( $domain_value );
	
	if ( empty( $api_key ) ) {
		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email;
		?>
		<div style="background:#f0f0f1; padding: 15px; border-left: 4px solid #764ba2; margin-bottom:20px; max-width: 600px;">
			<h3 style="margin-top:0;"><?php esc_html_e( 'Complete Plugin Setup', 'listen-to-this-article' ); ?></h3>
			<p style="margin-bottom: 15px;"><?php esc_html_e( 'Connect your site to Hi, Moose to start generating and embedding audio.', 'listen-to-this-article' ); ?></p>
			
			<div id="himoose-quick-connect-email-wrap" style="display:none; margin-bottom:15px;">
				<p style="margin-top:0; margin-bottom: 8px; font-weight: 500; font-size: 13px;"><?php esc_html_e( 'Confirm your email to sync your site:', 'listen-to-this-article' ); ?></p>
				<input type="email" id="himoose-quick-connect-email" value="<?php echo esc_attr( $user_email ); ?>" placeholder="email@example.com" class="regular-text" style="margin:0; display:block; width:100%; max-width: 350px;" />
				<p style="font-size: 11px; margin-top: 5px; margin-bottom: 0; color: #666; line-height: 1.3;"><em><?php echo wp_kses_post( __( 'We do not spam. By continuing, you agree to our <a href="https://himoose.com/terms" target="_blank">terms</a> and <a href="https://himoose.com/privacy-policy" target="_blank">privacy policy</a>.', 'listen-to-this-article' ) ); ?></em></p>
			</div>

			<button type="button" id="himoose-quick-connect-btn" class="button button-primary">
				<?php esc_html_e( 'Connect Site', 'listen-to-this-article' ); ?>
			</button>
			
			<p id="himoose-quick-connect-error" style="color:#d63638; display:none; margin: 10px 0 0 0;"></p>
		</div>

		<h4 style="margin-bottom: 10px;"><?php esc_html_e( 'Already have an account?', 'listen-to-this-article' ); ?></h4>
		<p class="description" style="margin-bottom: 10px;"><?php esc_html_e( 'Paste your API key here to connect your existing account.', 'listen-to-this-article' ); ?></p>
		<?php
	} else {
		// Just output normal label if they're updating an existing attached key.
		?>
		<h4 style="margin-bottom: 10px; margin-top:0;"><?php esc_html_e( 'API Key', 'listen-to-this-article' ); ?></h4>
		<?php
	}
	?>
	<input type="password" name="himoose_api_key" value="" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="regular-text" />
	
	<?php if ( empty( $api_key ) ) : ?>
		<p style="margin-top: 10px;">
			<a href="<?php echo esc_url( $register_url ); ?>" target="_blank" class="button button-secondary">
				<?php esc_html_e( 'Get an API Key manually', 'listen-to-this-article' ); ?>
			</a>
		</p>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'Your API key is saved securely. To update it, enter a new key above.', 'listen-to-this-article' ); ?></p>
		<p>
			<label>
				<input type="checkbox" name="himoose_delete_api_key" value="1" />
				<span style="color: #d63638;"><?php esc_html_e( 'Disconnect / Remove API Key', 'listen-to-this-article' ); ?></span>
			</label>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Domain field callback (Editable).
 */
function himoose_field_domain_callback() {
	$saved_domain = get_option( 'himoose_domain' );
	
	// Calculate detected domain for display purposes.
	$url = home_url();
	$parsed_url = wp_parse_url( $url );
	$detected_domain = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';

	// Use saved domain if exists, otherwise default to detected.
	$value = ! empty( $saved_domain ) ? $saved_domain : $detected_domain;

	?>
	<input type="text" name="himoose_domain" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description">
		<?php esc_html_e( 'The domain used to fetch audio. You can edit this if your audio is hosted on a different domain.', 'listen-to-this-article' ); ?>
	</p>
	<?php
}

/**
 * Auto Insert field callback.
 */
function himoose_field_auto_insert_callback() {
	$auto_insert = get_option( 'himoose_auto_insert' );
	?>
	<label>
		<input type="checkbox" name="himoose_auto_insert" value="1" <?php checked( '1', $auto_insert ); ?> />
		<?php esc_html_e( 'Automatically insert the audio player at the top of post content (posts only) when the post has audio. Pages always require the shortcode [himoose_podcast].', 'listen-to-this-article' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'If unchecked (or if you are editing a page), manually insert the shortcode [himoose_podcast] where you want the player to appear.', 'listen-to-this-article' ); ?>
	</p>
	<?php
}
