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
	add_options_page(
		'Hi, Moose Audio Generator',
		'Hi, Moose Audio Generator',
		'manage_options',
		'himoose-settings',
		'himoose_options_page_html'
	);
}
add_action( 'admin_menu', 'himoose_options_page' );

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
