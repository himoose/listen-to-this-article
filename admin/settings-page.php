<?php
/**
 * Render the settings page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add options page.
 */
function himoose_options_page() {
	add_submenu_page(
		'options-general.php',
		__( 'Welcome to Hi, Moose', 'listen-to-this-article' ),
		__( 'Welcome to Hi, Moose', 'listen-to-this-article' ),
		'manage_options',
		'himoose-onboarding',
		'himoose_onboarding_page_html'
	);

	add_options_page(
		'Podcast-Style Text to Speech - Hi, Moose',
		'Hi, Moose Audio Generator',
		'manage_options',
		'himoose-settings',
		'himoose_options_page_html'
	);
}
add_action( 'admin_menu', 'himoose_options_page' );

/**
 * Keep the onboarding page accessible by URL without showing it as a submenu item.
 */
function himoose_hide_onboarding_submenu() {
	remove_submenu_page( 'options-general.php', 'himoose-onboarding' );
}
add_action( 'admin_head', 'himoose_hide_onboarding_submenu' );

/**
 * Options page HTML.
 */
function himoose_options_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'himoose_options_group' );
			do_settings_sections( 'himoose-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Render the onboarding page.
 */
function himoose_onboarding_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$api_key = himoose_get_api_key();
	$has_api_key = ! empty( $api_key );
	$current_user = wp_get_current_user();
	$user_email = $current_user instanceof WP_User ? $current_user->user_email : '';
	?>
	<div class="wrap himoose-onboarding-wrap">
		<div class="himoose-onboarding-shell">
			<div class="himoose-onboarding-card">
				<p class="himoose-onboarding-kicker"><?php esc_html_e( 'Start in 30 seconds', 'listen-to-this-article' ); ?></p>
				<h1 class="himoose-onboarding-title"><?php esc_html_e( 'Turn your posts into podcast-style audio', 'listen-to-this-article' ); ?></h1>

				<?php if ( $has_api_key ) : ?>
					<div class="notice notice-success inline">
						<p><strong><?php esc_html_e( 'Hi, Moose is already connected.', 'listen-to-this-article' ); ?></strong> <?php esc_html_e( 'You can go straight to the plugin settings or start generating audio from your posts.', 'listen-to-this-article' ); ?></p>
					</div>
					<p class="himoose-onboarding-actions">
						<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'options-general.php?page=himoose-settings' ) ); ?>"><?php esc_html_e( 'Open Plugin Settings', 'listen-to-this-article' ); ?></a>
					</p>
				<?php else : ?>
					<p class="himoose-onboarding-copy"><?php esc_html_e( 'Connect your site in one step. If you would rather skip the email flow, you can still use the original manual API key setup.', 'listen-to-this-article' ); ?></p>

					<div class="himoose-onboarding-connect">
						<div id="himoose-quick-connect-email-wrap-onboarding" class="himoose-onboarding-email-wrap" hidden>
							<label class="himoose-onboarding-label" for="himoose-quick-connect-email-onboarding"><?php esc_html_e( 'Email address', 'listen-to-this-article' ); ?></label>
							<input type="email" id="himoose-quick-connect-email-onboarding" value="<?php echo esc_attr( $user_email ); ?>" placeholder="email@example.com" class="regular-text" />
							<p class="description"><?php echo wp_kses_post( __( 'We will use this only to connect your WordPress site to your Hi, Moose account. By continuing, you agree to our <a href="https://himoose.com/terms" target="_blank" rel="noopener noreferrer">terms</a> and <a href="https://himoose.com/privacy-policy" target="_blank" rel="noopener noreferrer">privacy policy</a>.', 'listen-to-this-article' ) ); ?></p>
						</div>

						<button
							type="button"
							id="himoose-quick-connect-btn-onboarding"
							class="button button-primary button-hero"
							data-idle-label="<?php echo esc_attr__( 'Connect Hi, Moose', 'listen-to-this-article' ); ?>"
							data-loading-label="<?php echo esc_attr__( 'Connecting...', 'listen-to-this-article' ); ?>"
							data-success-label="<?php echo esc_attr__( 'Finish connection in new tab', 'listen-to-this-article' ); ?>"
						>
							<?php esc_html_e( 'Connect Hi, Moose', 'listen-to-this-article' ); ?>
						</button>
						<p class="himoose-onboarding-microcopy"><?php esc_html_e( 'No API key needed', 'listen-to-this-article' ); ?></p>
						<p id="himoose-quick-connect-error-onboarding" class="himoose-quick-connect-error"></p>
					</div>

					<details class="himoose-onboarding-advanced">
						<summary><?php esc_html_e( 'Already have an account?', 'listen-to-this-article' ); ?></summary>
						<p><?php esc_html_e( 'Prefer the original manual connection option? Paste your API key here instead.', 'listen-to-this-article' ); ?></p>
						<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post" class="himoose-onboarding-manual-form">
							<?php settings_fields( 'himoose_options_group' ); ?>
							<input type="hidden" name="himoose_onboarding_manual_save" value="1" />
							<label class="himoose-onboarding-label" for="himoose-onboarding-api-key"><?php esc_html_e( 'Hi, Moose API Key', 'listen-to-this-article' ); ?></label>
							<input type="password" name="himoose_api_key" id="himoose-onboarding-api-key" value="" class="regular-text" />
							<p class="description"><?php esc_html_e( 'You can manage your domain and other plugin settings after connecting.', 'listen-to-this-article' ); ?></p>
							<div class="himoose-onboarding-manual-actions">
								<?php submit_button( __( 'Save API Key', 'listen-to-this-article' ), 'secondary', 'submit', false ); ?>
								<a class="button button-link" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( himoose_get_app_base() . '/register?source=wordpress&domain=' . rawurlencode( himoose_get_domain() ) ); ?>"><?php esc_html_e( 'Get an API key manually', 'listen-to-this-article' ); ?></a>
							</div>
						</form>
					</details>

					<p class="himoose-onboarding-footer">
						<a href="<?php echo esc_url( admin_url( 'options-general.php?page=himoose-settings' ) ); ?>"><?php esc_html_e( 'Skip for now and use the full settings page', 'listen-to-this-article' ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}
