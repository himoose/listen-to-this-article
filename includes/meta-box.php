<?php
/**
 * Meta Box logic for the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Meta Box.
 */
function himoose_add_meta_box() {
	add_meta_box(
		'himoose_podcast_meta_box',
		__( 'Audio Content', 'listen-to-this-article' ),
		'himoose_render_meta_box',
		array( 'post', 'page' ),
		'side',
		'low'
	);
}
add_action( 'add_meta_boxes', 'himoose_add_meta_box' );

/**
 * Render Meta Box.
 *
 * @param WP_Post $post The post object.
 */
function himoose_render_meta_box( $post ) {
	wp_nonce_field( 'himoose_save_meta_box_data', 'himoose_meta_box_nonce' );

	$job_id = get_post_meta( $post->ID, '_himoose_job_id', true );
	$label  = get_post_meta( $post->ID, '_himoose_podcast_label', true );
	$api_key = himoose_get_api_key();
	$has_job = ! empty( $job_id );
	$post_type = get_post_type( $post );
	$user_id = get_current_user_id();
	$successful_assignments = $user_id ? (int) get_user_meta( $user_id, 'himoose_successful_assignments_count', true ) : 0;
	$review_prompt_dismissed = $user_id ? (bool) get_user_meta( $user_id, 'himoose_review_prompt_dismissed', true ) : false;

	$defaults = get_option( 'himoose_generation_defaults', array() );
	$default_host_voice = isset( $defaults['hostVoiceName'] ) && is_string( $defaults['hostVoiceName'] ) && $defaults['hostVoiceName'] ? $defaults['hostVoiceName'] : 'Sulafat';
	$default_guest_voice = isset( $defaults['guestVoiceName'] ) && is_string( $defaults['guestVoiceName'] ) && $defaults['guestVoiceName'] ? $defaults['guestVoiceName'] : 'Fenrir';
	$default_primary_color = isset( $defaults['primaryColor'] ) && is_string( $defaults['primaryColor'] ) && $defaults['primaryColor'] ? $defaults['primaryColor'] : '#667eea';
	$default_secondary_color = isset( $defaults['secondaryColor'] ) && is_string( $defaults['secondaryColor'] ) && $defaults['secondaryColor'] ? $defaults['secondaryColor'] : '#764ba2';
	$default_length = isset( $defaults['length'] ) && is_string( $defaults['length'] ) && $defaults['length'] ? $defaults['length'] : 'SHORT';
	$default_focus = isset( $defaults['focus'] ) && is_string( $defaults['focus'] ) ? $defaults['focus'] : '';
	$default_custom_title = isset( $defaults['customTitle'] ) && is_string( $defaults['customTitle'] ) && $defaults['customTitle'] ? $defaults['customTitle'] : __( 'Listen to this article as a podcast', 'listen-to-this-article' );

	$voices = array(
		array( 'value' => 'Zephyr', 'label' => 'Zephyr (Bright)' ),
		array( 'value' => 'Puck', 'label' => 'Puck (Upbeat)' ),
		array( 'value' => 'Charon', 'label' => 'Charon (Informative)' ),
		array( 'value' => 'Kore', 'label' => 'Kore (Firm)' ),
		array( 'value' => 'Fenrir', 'label' => 'Fenrir (Excitable)' ),
		array( 'value' => 'Leda', 'label' => 'Leda (Youthful)' ),
		array( 'value' => 'Orus', 'label' => 'Orus (Firm)' ),
		array( 'value' => 'Aoede', 'label' => 'Aoede (Breezy)' ),
		array( 'value' => 'Callirrhoe', 'label' => 'Callirrhoe (Easy-going)' ),
		array( 'value' => 'Autonoe', 'label' => 'Autonoe (Bright)' ),
		array( 'value' => 'Enceladus', 'label' => 'Enceladus (Breathy)' ),
		array( 'value' => 'Iapetus', 'label' => 'Iapetus (Clear)' ),
		array( 'value' => 'Umbriel', 'label' => 'Umbriel (Easy-going)' ),
		array( 'value' => 'Algieba', 'label' => 'Algieba (Smooth)' ),
		array( 'value' => 'Despina', 'label' => 'Despina (Smooth)' ),
		array( 'value' => 'Erinome', 'label' => 'Erinome (Clear)' ),
		array( 'value' => 'Algenib', 'label' => 'Algenib (Gravelly)' ),
		array( 'value' => 'Rasalgethi', 'label' => 'Rasalgethi (Informative)' ),
		array( 'value' => 'Laomedeia', 'label' => 'Laomedeia (Upbeat)' ),
		array( 'value' => 'Achernar', 'label' => 'Achernar (Soft)' ),
		array( 'value' => 'Alnilam', 'label' => 'Alnilam (Firm)' ),
		array( 'value' => 'Schedar', 'label' => 'Schedar (Even)' ),
		array( 'value' => 'Gacrux', 'label' => 'Gacrux (Mature)' ),
		array( 'value' => 'Pulcherrima', 'label' => 'Pulcherrima (Forward)' ),
		array( 'value' => 'Achird', 'label' => 'Achird (Friendly)' ),
		array( 'value' => 'Zubenelgenubi', 'label' => 'Zubenelgenubi (Casual)' ),
		array( 'value' => 'Vindemiatrix', 'label' => 'Vindemiatrix (Gentle)' ),
		array( 'value' => 'Sadachbia', 'label' => 'Sadachbia (Lively)' ),
		array( 'value' => 'Sadaltager', 'label' => 'Sadaltager (Knowledgeable)' ),
		array( 'value' => 'Sulafat', 'label' => 'Sulafat (Warm)' ),
	);

	$lengths = array(
		array( 'value' => 'SHORT', 'label' => 'Standard (4-5 minutes)', 'description' => 'Quick overview' ),
		array( 'value' => 'STANDARD', 'label' => 'Longer (≈10 minutes)', 'description' => 'Comprehensive coverage' ),
	);
	
	?>
	<div id="himoose-meta-box-container" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
		<input type="hidden" name="himoose_job_id" id="himoose_job_id" value="<?php echo esc_attr( $job_id ); ?>" />
		<input type="hidden" name="himoose_podcast_label" id="himoose_podcast_label" value="<?php echo esc_attr( $label ); ?>" />

		<div id="himoose-podcast-selector" style="<?php echo $has_job ? '' : 'display:none;'; ?>">
			<select id="himoose-podcast-select" class="widefat">
				<?php if ( $has_job ) : ?>
					<?php 
					$display_label = ! empty( $label ) ? $label : sprintf( 
						/* translators: %s: Podcast Job ID */
						__( 'Audio Selected (ID: %s)', 'listen-to-this-article' ), 
						$job_id 
					); 
					?>
				<option value="<?php echo esc_attr( $job_id ); ?>" selected><?php echo esc_html( $display_label ); ?></option>
			<?php else : ?>
				<option value=""><?php esc_html_e( 'Select audio...', 'listen-to-this-article' ); ?></option>
			<?php endif; ?>
			</select>
		</div>

		<?php if ( $has_job && 'page' === $post_type ) : ?>
			<p class="description himoose-shortcode-reminder">
				<?php
				echo wp_kses_post(
					__( 'To display the audio player on this page, insert the shortcode <code>[himoose_podcast]</code> into the page content.', 'listen-to-this-article' )
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( $user_id && ! $review_prompt_dismissed && $successful_assignments >= 2 ) : ?>
			<div class="himoose-review-prompt" role="note">
				<p class="himoose-review-prompt-message">
					<?php
					echo esc_html__( '⭐⭐⭐⭐⭐ If this plugin is helping you, please consider leaving a 5‑star review. It really helps!', 'listen-to-this-article' );
					?>
				</p>
				<p class="himoose-review-prompt-actions">
					<a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/listen-to-this-article/reviews/?filter=5">
						<?php esc_html_e( 'Leave a review', 'listen-to-this-article' ); ?>
					</a>
					<button type="button" class="button-link himoose-review-dismiss">
						<?php esc_html_e( 'Dismiss', 'listen-to-this-article' ); ?>
					</button>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( empty( $api_key ) ) : ?>
			<p>
				<?php esc_html_e( 'Please connect to Hi, Moose to generate audio.', 'listen-to-this-article' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=himoose-settings' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Go to Settings', 'listen-to-this-article' ); ?>
				</a>
			</p>
		<?php else : ?>
			<?php if ( ! $has_job ) : ?>
				<button type="button" id="himoose-start-generate" class="button button-primary">
					<?php
					if ( 'page' === $post_type ) {
						esc_html_e( 'Generate audio for this page', 'listen-to-this-article' );
					} else {
						esc_html_e( 'Generate audio for this post', 'listen-to-this-article' );
					}
					?>
				</button>

				<div id="himoose-generate-fields" style="display:none; margin-top: 12px;">
					<div class="himoose-progress-dialog" style="display:none;" role="status" aria-live="polite">
						<div class="himoose-progress-dialog-inner">
							<span class="spinner is-active himoose-progress-dialog-spinner"></span>
							<span class="himoose-progress-dialog-text">
								<?php esc_html_e( 'Generating audio…', 'listen-to-this-article' ); ?>
							</span>
						</div>
					</div>
				<p class="himoose-field">
					<label for="himoose-custom-title"><strong><?php esc_html_e( 'Player title (leave empty to omit)', 'listen-to-this-article' ); ?></strong></label>
					<input type="text" id="himoose-custom-title" class="widefat" value="<?php echo esc_attr( $default_custom_title ); ?>" />
				</p>

				<p class="himoose-field">
					<label for="himoose-host-voice"><strong><?php esc_html_e( 'Host voice', 'listen-to-this-article' ); ?></strong></label>
					<div class="himoose-inline-row">
						<select id="himoose-host-voice" class="widefat">
							<?php foreach ( $voices as $voice ) : ?>
								<option value="<?php echo esc_attr( $voice['value'] ); ?>" <?php selected( $default_host_voice, $voice['value'] ); ?>><?php echo esc_html( $voice['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="button himoose-voice-sample" data-voice-source="host"><?php esc_html_e( 'Play', 'listen-to-this-article' ); ?></button>
					</div>
				</p>

				<p class="himoose-field">
					<label for="himoose-guest-voice"><strong><?php esc_html_e( 'Guest voice', 'listen-to-this-article' ); ?></strong></label>
					<div class="himoose-inline-row">
						<select id="himoose-guest-voice" class="widefat">
							<?php foreach ( $voices as $voice ) : ?>
								<option value="<?php echo esc_attr( $voice['value'] ); ?>" <?php selected( $default_guest_voice, $voice['value'] ); ?>><?php echo esc_html( $voice['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="button himoose-voice-sample" data-voice-source="guest"><?php esc_html_e( 'Play', 'listen-to-this-article' ); ?></button>
					</div>
				</p>

				<p class="himoose-field">
					<label for="himoose-primary-color"><strong><?php esc_html_e( 'Player primary color', 'listen-to-this-article' ); ?></strong></label>
					<input type="text" id="himoose-primary-color" class="himoose-color-field" value="<?php echo esc_attr( $default_primary_color ); ?>" />
				</p>

				<p class="himoose-field">
					<label for="himoose-secondary-color"><strong><?php esc_html_e( 'Player secondary color', 'listen-to-this-article' ); ?></strong></label>
					<input type="text" id="himoose-secondary-color" class="himoose-color-field" value="<?php echo esc_attr( $default_secondary_color ); ?>" />
				</p>

				<p class="himoose-field">
					<label for="himoose-audio-length"><strong><?php esc_html_e( 'Audio length', 'listen-to-this-article' ); ?></strong></label>
					<select id="himoose-audio-length" class="widefat">
						<?php foreach ( $lengths as $len ) : ?>
							<option value="<?php echo esc_attr( $len['value'] ); ?>" <?php selected( $default_length, $len['value'] ); ?>><?php echo esc_html( $len['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p class="himoose-field">
					<label for="himoose-focus"><strong><?php esc_html_e( 'Basic instructions', 'listen-to-this-article' ); ?></strong></label>
					<textarea id="himoose-focus" class="widefat" rows="3" placeholder="<?php echo esc_attr__( 'E.g., keep it upbeat, focus on key takeaways, avoid jargon...', 'listen-to-this-article' ); ?>"><?php echo esc_textarea( $default_focus ); ?></textarea>
				</p>

				<p class="himoose-field">
					<span class="himoose-generate-actions">
						<button type="button" id="himoose-generate-submit" class="button button-primary">
							<?php esc_html_e( 'Generate Audio', 'listen-to-this-article' ); ?>
						</button>
						<button type="button" id="himoose-generate-close" class="button button-secondary">
							<?php esc_html_e( 'Close', 'listen-to-this-article' ); ?>
						</button>
						<span class="spinner" id="himoose-generate-spinner" style="display:none; float:none; margin-left: 5px;"></span>
					</span>
				</p>

				<p class="himoose-generate-status" style="display:none;"></p>
				<p class="himoose-preview" style="display:none;"></p>
				<p class="himoose-generate-hint" style="display:none;"></p>
				<p class="himoose-generate-error" style="display:none;"></p>
				</div>

				<button type="button" id="himoose-fetch-podcasts" class="button button-secondary">
					<?php esc_html_e( 'Load latest audio', 'listen-to-this-article' ); ?>
				</button>
			<?php endif; ?>

			<?php if ( ! $has_job ) : ?>
				<span class="spinner" id="himoose-spinner" style="display:none; float:none; margin-left: 5px;"></span>

				<p class="himoose-error" style="display:none;"></p>
				
				<p class="description">
					<?php 
					$auto_insert = get_option( 'himoose_auto_insert' );
					if ( 'page' === $post_type ) {
						echo wp_kses_post( __( 'Selecting audio saves the player data. <strong>You must insert the shortcode <code>[himoose_podcast]</code> in your content to display it on pages.</strong>', 'listen-to-this-article' ) );
					} else {
						if ( '1' === $auto_insert ) {
							esc_html_e( 'Selecting audio will automatically display the player at the top of this post.', 'listen-to-this-article' );
						} else {
							echo wp_kses_post( __( 'Selecting audio saves the player data. <strong>You must insert the shortcode <code>[himoose_podcast]</code> in your content to display it.</strong>', 'listen-to-this-article' ) );
						}
					}
					?>
				</p>

				<p class="himoose-generate-link">
					<a href="<?php echo esc_url( himoose_get_app_base() . '/podcast-generator' ); ?>" target="_blank">
						<?php esc_html_e( 'Use advanced mode instead', 'listen-to-this-article' ); ?>
					</a>
				</p>
			<?php endif; ?>

			<div id="himoose-remove-section" class="himoose-remove-section" style="<?php echo $has_job ? '' : 'display:none;'; ?>">
				<button type="button" id="himoose-remove-podcast" class="button-link button-link-delete">
					<?php
					if ( 'page' === $post_type ) {
						esc_html_e( 'Remove audio from this page', 'listen-to-this-article' );
					} else {
						esc_html_e( 'Remove audio from this post', 'listen-to-this-article' );
					}
					?>
				</button>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Save Meta Box Data.
 *
 * @param int $post_id The post ID.
 */
function himoose_save_meta_box_data( $post_id ) {
	// Check nonce.
	if ( ! isset( $_POST['himoose_meta_box_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['himoose_meta_box_nonce'] ) ), 'himoose_save_meta_box_data' ) ) {
		return;
	}

	// Check autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Get the new Job ID.
	$new_job_id = '';
	if ( isset( $_POST['himoose_job_id'] ) ) {
		$new_job_id = sanitize_text_field( wp_unslash( $_POST['himoose_job_id'] ) );
	}

	// Get the old Job ID.
	$old_job_id = get_post_meta( $post_id, '_himoose_job_id', true );

	// Handle clearing the audio.
	if ( empty( $new_job_id ) ) {
		update_post_meta( $post_id, '_himoose_job_id', '' );
		delete_post_meta( $post_id, '_himoose_podcast_label' );
		delete_post_meta( $post_id, '_himoose_embed_html' );
		return;
	}

	// Check if we need to fetch new embed data.
	$existing_embed = get_post_meta( $post_id, '_himoose_embed_html', true );
	$needs_fetch    = ( $new_job_id !== $old_job_id || empty( $existing_embed ) );
	$did_update     = false;

	if ( $needs_fetch ) {
		$embed_html = himoose_remote_get_embed( $new_job_id );

		if ( ! is_wp_error( $embed_html ) ) {
			// Success! Update ID, Embed, and Label.
			update_post_meta( $post_id, '_himoose_job_id', $new_job_id );
			update_post_meta( $post_id, '_himoose_embed_html', $embed_html );
			$did_update = true;

			if ( isset( $_POST['himoose_podcast_label'] ) ) {
				update_post_meta( $post_id, '_himoose_podcast_label', sanitize_text_field( wp_unslash( $_POST['himoose_podcast_label'] ) ) );
			}
		}
		// If API fails, we intentionally do NOT update the Job ID or Label.
		// This prevents the post from being in a state where it has a Job ID but no (or wrong) embed HTML.
		// The user will see the old selection (or empty) upon page reload, indicating the save didn't fully succeed.
	} else {
		// No fetch needed (ID hasn't changed and we have embed).
		// Just ensure ID and Label are up to date (e.g. if label text changed in UI).
		update_post_meta( $post_id, '_himoose_job_id', $new_job_id );
		$did_update = true;
		if ( isset( $_POST['himoose_podcast_label'] ) ) {
			update_post_meta( $post_id, '_himoose_podcast_label', sanitize_text_field( wp_unslash( $_POST['himoose_podcast_label'] ) ) );
		}
	}

	// Count a "successful add" the first time a post/page gets an audio selection saved.
	if ( $did_update && empty( $old_job_id ) && ! empty( $new_job_id ) ) {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$count = (int) get_user_meta( $user_id, 'himoose_successful_assignments_count', true );
			update_user_meta( $user_id, 'himoose_successful_assignments_count', $count + 1 );
		}
	}
}
add_action( 'save_post', 'himoose_save_meta_box_data' );

/**
 * AJAX: Dismiss the review prompt for the current user.
 */
function himoose_ajax_dismiss_review_prompt() {
	check_ajax_referer( 'himoose_ajax_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'listen-to-this-article' ) ) );
	}

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => __( 'Missing user.', 'listen-to-this-article' ) ) );
	}

	update_user_meta( $user_id, 'himoose_review_prompt_dismissed', 1 );
	wp_send_json_success( array( 'dismissed' => true ) );
}
add_action( 'wp_ajax_himoose_dismiss_review_prompt', 'himoose_ajax_dismiss_review_prompt' );

/**
 * Enqueue Admin Scripts.
 */
function himoose_enqueue_admin_scripts( $hook ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$post_type = $screen && isset( $screen->post_type ) ? $screen->post_type : '';

	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}

	// Only enqueue if post type supports our meta box.
	if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'himoose-admin-js', HIMOOSE_PLUGIN_URL . 'admin/assets/admin.js', array( 'jquery', 'wp-color-picker' ), HIMOOSE_VERSION, true );
	wp_enqueue_style( 'himoose-admin-css', HIMOOSE_PLUGIN_URL . 'admin/assets/admin.css', array( 'wp-color-picker' ), HIMOOSE_VERSION );

	wp_localize_script(
		'himoose-admin-js',
		'himooseAjax',
		array(
			'ajaxurl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'himoose_ajax_nonce' ),
			'sampleBaseUrl' => 'https://audio.himoose.com/listen/himoose.com/voice-samples/',
			'sampleExt'     => '.wav',
			'postType'      => $post_type,
		)
	);
}
add_action( 'admin_enqueue_scripts', 'himoose_enqueue_admin_scripts' );

/**
 * AJAX Handler to get podcasts.
 */
function himoose_ajax_get_podcasts() {
	check_ajax_referer( 'himoose_ajax_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( __( 'Insufficient permissions.', 'listen-to-this-article' ) );
	}

	$podcasts = himoose_remote_get_podcasts();

	if ( is_wp_error( $podcasts ) ) {
		wp_send_json_error( $podcasts->get_error_message() );
	}

	wp_send_json_success( $podcasts );
}
add_action( 'wp_ajax_himoose_get_podcasts', 'himoose_ajax_get_podcasts' );

/**
	* AJAX: Start podcast generation.
	*/
function himoose_ajax_generate_podcast() {
	check_ajax_referer( 'himoose_ajax_nonce', 'nonce' );

	$post_id = isset( $_POST['postId'] ) ? absint( $_POST['postId'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'listen-to-this-article' ) ) );
	}

	$api_key = himoose_get_api_key();
	if ( empty( $api_key ) ) {
		wp_send_json_error( array( 'message' => __( 'Missing API Key. Please connect in Settings.', 'listen-to-this-article' ) ) );
	}

	$domain = himoose_get_domain();
	if ( empty( $domain ) ) {
		wp_send_json_error( array( 'message' => __( 'Could not detect domain.', 'listen-to-this-article' ) ) );
	}

	$title_raw = isset( $_POST['title'] ) ? (string) wp_unslash( $_POST['title'] ) : '';
	$content_raw = isset( $_POST['content'] ) ? (string) wp_unslash( $_POST['content'] ) : '';
	$focus_raw = isset( $_POST['focus'] ) ? (string) wp_unslash( $_POST['focus'] ) : '';
	$length_raw = isset( $_POST['length'] ) ? (string) wp_unslash( $_POST['length'] ) : 'SHORT';
	$host_voice_raw = isset( $_POST['hostVoiceName'] ) ? (string) wp_unslash( $_POST['hostVoiceName'] ) : 'Sulafat';
	$guest_voice_raw = isset( $_POST['guestVoiceName'] ) ? (string) wp_unslash( $_POST['guestVoiceName'] ) : 'Fenrir';
	$primary_color_raw = isset( $_POST['primaryColor'] ) ? (string) wp_unslash( $_POST['primaryColor'] ) : '#667eea';
	$secondary_color_raw = isset( $_POST['secondaryColor'] ) ? (string) wp_unslash( $_POST['secondaryColor'] ) : '#764ba2';
	$custom_title_raw = isset( $_POST['customTitle'] ) ? (string) wp_unslash( $_POST['customTitle'] ) : '';

	$title = sanitize_text_field( $title_raw );
	$focus = sanitize_textarea_field( $focus_raw );
	$length = in_array( $length_raw, array( 'SHORT', 'STANDARD' ), true ) ? $length_raw : 'SHORT';
	$host_voice = sanitize_text_field( $host_voice_raw );
	$guest_voice = sanitize_text_field( $guest_voice_raw );

	$sanitize_hex = static function( $value, $fallback ) {
		$v = trim( (string) $value );
		if ( '' === $v ) {
			return $fallback;
		}
		if ( '#' !== $v[0] ) {
			$v = '#' . $v;
		}
		if ( preg_match( '/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $v ) ) {
			return strtolower( $v );
		}
		return $fallback;
	};

	$primary_color = $sanitize_hex( $primary_color_raw, '#667eea' );
	$secondary_color = $sanitize_hex( $secondary_color_raw, '#764ba2' );
	$custom_title = sanitize_text_field( $custom_title_raw );

	// Normalize content to plain text (API-side can still re-process if needed).
	$content_text = html_entity_decode( wp_strip_all_tags( $content_raw, true ), ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) );
	$content_text = trim( $content_text );

	if ( '' === $title ) {
		wp_send_json_error( array( 'message' => __( 'Post/page title is required.', 'listen-to-this-article' ) ) );
	}
	if ( '' === $content_text ) {
		wp_send_json_error( array( 'message' => __( 'Post/page content is empty. Please add content before generating.', 'listen-to-this-article' ) ) );
	}

	// Save site-wide defaults for better UX next time.
	update_option(
		'himoose_generation_defaults',
		array(
			'hostVoiceName' => $host_voice,
			'guestVoiceName' => $guest_voice,
			'primaryColor' => $primary_color,
			'secondaryColor' => $secondary_color,
			'length' => $length,
			'focus' => $focus,
			'customTitle' => $custom_title,
		),
		false
	);

	$payload = array(
		// Include both keys for backwards/forwards compatibility while API evolves.
		'domain'         => $domain,
		'customerDomain' => $domain,
		'title'          => $title,
		'content'        => $content_text,
		'focus'          => $focus,
		'length'         => $length,
		'hostVoiceName'  => $host_voice,
		'guestVoiceName' => $guest_voice,
		'playerConfig'   => array(
			'primaryColor'   => $primary_color,
			'secondaryColor' => $secondary_color,
			'customTitle'    => $custom_title,
		),
	);

	$result = himoose_remote_generate_podcast( $payload );
	if ( is_wp_error( $result ) ) {
		$data = $result->get_error_data();
		wp_send_json_error(
			array(
				'message'    => $result->get_error_message(),
				'upgradeUrl' => is_array( $data ) && isset( $data['upgradeUrl'] ) ? $data['upgradeUrl'] : null,
				'code'       => is_array( $data ) && isset( $data['code'] ) ? $data['code'] : null,
			)
		);
	}

	wp_send_json_success( $result );
}
add_action( 'wp_ajax_himoose_generate_podcast', 'himoose_ajax_generate_podcast' );

/**
	* AJAX: Poll podcast generation status.
	*/
function himoose_ajax_get_podcast_status() {
	check_ajax_referer( 'himoose_ajax_nonce', 'nonce' );

	$job_id = isset( $_POST['jobId'] ) ? sanitize_text_field( wp_unslash( $_POST['jobId'] ) ) : '';
	if ( empty( $job_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Missing jobId.', 'listen-to-this-article' ) ) );
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'listen-to-this-article' ) ) );
	}

	$result = himoose_remote_get_podcast_status( $job_id );
	if ( is_wp_error( $result ) ) {
		$data = $result->get_error_data();
		wp_send_json_error(
			array(
				'message'    => $result->get_error_message(),
				'upgradeUrl' => is_array( $data ) && isset( $data['upgradeUrl'] ) ? $data['upgradeUrl'] : null,
				'code'       => is_array( $data ) && isset( $data['code'] ) ? $data['code'] : null,
			)
		);
	}

	wp_send_json_success( $result );
}
add_action( 'wp_ajax_himoose_get_podcast_status', 'himoose_ajax_get_podcast_status' );
