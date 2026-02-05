<?php
/**
 * Privacy Policy content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Privacy Policy content.
 */
function himoose_add_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content = sprintf(
		'<h3>%s</h3>
		<p>%s</p>
		<p>%s</p>
		<ul>
			<li>%s</li>
			<li>%s</li>
		</ul>
		<p>%s <a href="https://himoose.com/privacy-policy" target="_blank">%s</a>.</p>',
		__( 'Listen to This Article as a Podcast (Hi, Moose AEO)', 'listen-to-this-article' ),
		__( 'This site uses the "Listen to This Article" plugin to provide audio versions of articles. This service is powered by Hi, Moose.', 'listen-to-this-article' ),
		__( 'When you load a page with an audio player, or when the site administrator fetches audio data:', 'listen-to-this-article' ),
		__( 'The website domain is sent to the Hi, Moose API to retrieve the correct audio content.', 'listen-to-this-article' ),
		__( 'The embedded audio player may collect anonymous usage data (plays, pauses) to provide analytics to the site owner. IP addresses are anonymized.', 'listen-to-this-article' ),
		__( 'For more details, please see the Hi, Moose Privacy Policy:', 'listen-to-this-article' ),
		__( 'https://himoose.com/privacy-policy', 'listen-to-this-article' )
	);

	wp_add_privacy_policy_content(
		__( 'Listen to This Article', 'listen-to-this-article' ),
		wp_kses_post( $content )
	);
}
add_action( 'admin_init', 'himoose_add_privacy_policy_content' );
