<?php
/**
 * Plugin Name:       Listen to This Article as a Podcast – AI Text to Speech Audio Player
 * Plugin URI:        https://himoose.com/listen-to-this-article
 * Description:       Turn your articles into podcast-style audio using natural AI narration, and add a clean embedded player to your posts. Great for “listen to this article,” accessibility, and SEO/AEO.
 * Version:           1.1.0
 * Author:            Hi, Moose
 * Author URI:        https://himoose.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       listen-to-this-article
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants.
define( 'HIMOOSE_VERSION', '1.1.0' );
define( 'HIMOOSE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HIMOOSE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Allow clean environment overrides from wp-config.php.
// Define these in wp-config.php for development/testing:
// - HIMOOSE_API_BASE (no trailing slash)
// - HIMOOSE_APP_BASE (no trailing slash)
if ( ! defined( 'HIMOOSE_API_BASE' ) ) {
	define( 'HIMOOSE_API_BASE', 'https://wp-api.himoose.com/v1' );
}

if ( ! defined( 'HIMOOSE_APP_BASE' ) ) {
	define( 'HIMOOSE_APP_BASE', 'https://app.himoose.com' );
}

// Include Helper Functions.
require_once HIMOOSE_PLUGIN_DIR . 'includes/helpers.php';

// Include API Client.
require_once HIMOOSE_PLUGIN_DIR . 'includes/api-client.php';

// Include Privacy Policy Content.
require_once HIMOOSE_PLUGIN_DIR . 'includes/privacy.php';

// Include Admin Settings & Meta Boxes (only in admin).
if ( is_admin() ) {
	require_once HIMOOSE_PLUGIN_DIR . 'admin/settings-register.php';
	require_once HIMOOSE_PLUGIN_DIR . 'admin/settings-page.php';
	require_once HIMOOSE_PLUGIN_DIR . 'includes/meta-box.php';
}

// Include Front-End Rendering.
require_once HIMOOSE_PLUGIN_DIR . 'includes/embed-render.php';

/**
 * Add Settings link to plugin list.
 *
 * @param array $links Existing links.
 * @return array Modified links.
 */
function himoose_add_plugin_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=himoose-settings' ) ) . '">' . esc_html__( 'Settings', 'listen-to-this-article' ) . '</a>';
	$links['settings'] = $settings_link;
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'himoose_add_plugin_action_links' );

// Activation/Deactivation Hooks.
register_activation_hook( __FILE__, 'himoose_activate_plugin' );
register_deactivation_hook( __FILE__, 'himoose_deactivate_plugin' );

/**
 * Activation hook.
 */
function himoose_activate_plugin() {
	// Set default options if needed.
	if ( false === get_option( 'himoose_auto_insert' ) ) {
		add_option( 'himoose_auto_insert', '1' ); // Default to auto-insert.
	}
}

/**
 * Deactivation hook.
 */
function himoose_deactivate_plugin() {
	// Cleanup if necessary.
}
