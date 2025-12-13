<?php
/**
 * Register settings for the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize settings.
 */
function himoose_settings_init() {
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
}
add_action( 'admin_init', 'himoose_settings_init' );

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
	echo '<p>' . esc_html__( 'Connect your site to Hi, Moose to start embedding your articles as podcasts.', 'listen-to-this-article' ) . '</p>';
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
	
	$register_url = 'https://app.himoose.com/register?source=wordpress&domain=' . urlencode( $domain_value );
	?>
	<input type="password" name="himoose_api_key" value="" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="regular-text" />
	
	<?php if ( empty( $api_key ) ) : ?>
		<p style="margin-top: 10px;">
			<a href="<?php echo esc_url( $register_url ); ?>" target="_blank" class="button button-primary">
				<?php esc_html_e( 'Get Your API Key', 'listen-to-this-article' ); ?>
			</a>
		</p>
		<p class="description"><?php esc_html_e( 'Click the button above to create your free account and get your key.', 'listen-to-this-article' ); ?></p>
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
		<?php esc_html_e( 'The domain used to fetch podcasts. You can edit this if your podcasts are hosted on a different domain.', 'listen-to-this-article' ); ?>
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
		<?php esc_html_e( 'Automatically insert the podcast player at the top of the post content when it has a podcast. Alternatively, you can override the placement by using the shortcode [himoose_podcast].', 'listen-to-this-article' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'If unchecked, you must manually insert the shortcode [himoose_podcast] where you want the player to appear.', 'listen-to-this-article' ); ?>
	</p>
	<?php
}
