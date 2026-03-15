<?php
/**
 * Meta Box logic for the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trim a UTF-8 string to a safe maximum length.
 *
 * @param string $value Raw value.
 * @param int    $max_length Maximum length.
 * @return string
 */
function himoose_trim_generation_value( $value, $max_length ) {
	$value      = trim( wp_check_invalid_utf8( (string) $value ) );
	$max_length = max( 0, (int) $max_length );

	if ( 0 === $max_length || '' === $value ) {
		return $value;
	}

	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $value, 0, $max_length );
	}

	return substr( $value, 0, $max_length );
}

/**
 * Sanitize a short generation field.
 *
 * @param string $value Raw value.
 * @param int    $max_length Maximum length.
 * @return string
 */
function himoose_sanitize_generation_text_field( $value, $max_length ) {
	return himoose_trim_generation_value( sanitize_text_field( $value ), $max_length );
}

/**
 * Sanitize a textarea generation field.
 *
 * @param string $value Raw value.
 * @param int    $max_length Maximum length.
 * @return string
 */
function himoose_sanitize_generation_textarea_field( $value, $max_length ) {
	return himoose_trim_generation_value( sanitize_textarea_field( $value ), $max_length );
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
	$default_use_advanced = ! empty( $defaults['useAdvancedCustomization'] );
	$default_advanced = isset( $defaults['advancedCustomization'] ) && is_array( $defaults['advancedCustomization'] ) ? $defaults['advancedCustomization'] : array();
	$default_context = isset( $default_advanced['context'] ) && is_string( $default_advanced['context'] ) ? $default_advanced['context'] : '';
	$default_director_accent = isset( $default_advanced['directorAccent'] ) && is_string( $default_advanced['directorAccent'] ) ? $default_advanced['directorAccent'] : '';
	$default_director_pace = isset( $default_advanced['directorPace'] ) && is_string( $default_advanced['directorPace'] ) ? $default_advanced['directorPace'] : '';
	$default_director_style = isset( $default_advanced['directorStyle'] ) && is_string( $default_advanced['directorStyle'] ) ? $default_advanced['directorStyle'] : '';
	$default_guest_direction = isset( $default_advanced['guestDirection'] ) && is_string( $default_advanced['guestDirection'] ) ? $default_advanced['guestDirection'] : '';
	$default_host_direction = isset( $default_advanced['hostDirection'] ) && is_string( $default_advanced['hostDirection'] ) ? $default_advanced['hostDirection'] : '';
	$default_scene = isset( $default_advanced['scene'] ) && is_string( $default_advanced['scene'] ) ? $default_advanced['scene'] : '';

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
		array( 'value' => 'Lapetus', 'label' => 'Lapetus (Clear)' ),
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

		<p class="himoose-preview" style="display:none;"></p>

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
					<a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/listen-to-this-article/reviews/">
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
				<button type="button" id="himoose-fetch-podcasts" class="button button-secondary" style="<?php echo $has_job ? 'display:none;' : ''; ?>">
					<?php esc_html_e( 'Load available audio', 'listen-to-this-article' ); ?>
				</button>

				<span class="spinner" id="himoose-spinner" style="<?php echo $has_job ? 'display:none;' : 'display:none;'; ?>"></span>

				<button type="button" id="himoose-start-generate" class="button button-primary" style="<?php echo $has_job ? 'display:none;' : ''; ?>">
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
				<p class="himoose-field himoose-customization-mode" role="group" aria-label="<?php esc_attr_e( 'Customization mode', 'listen-to-this-article' ); ?>">
					<button
						type="button"
						class="button himoose-mode-toggle <?php echo $default_use_advanced ? '' : 'is-active'; ?>"
						data-mode="basic"
						aria-pressed="<?php echo $default_use_advanced ? 'false' : 'true'; ?>"
					>
						<?php esc_html_e( 'Basic Customization', 'listen-to-this-article' ); ?>
					</button>
					<button
						type="button"
						class="button himoose-mode-toggle <?php echo $default_use_advanced ? 'is-active' : ''; ?>"
						data-mode="advanced"
						aria-pressed="<?php echo $default_use_advanced ? 'true' : 'false'; ?>"
					>
						<?php esc_html_e( 'Advanced Customization', 'listen-to-this-article' ); ?>
					</button>
					<input type="hidden" id="himoose-use-advanced-customization" value="<?php echo $default_use_advanced ? '1' : '0'; ?>" />
				</p>
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

				<div class="himoose-advanced-fields" style="<?php echo $default_use_advanced ? '' : 'display:none;'; ?>">
					<p class="himoose-field himoose-advanced-note">
						<?php esc_html_e( 'Advanced guidance is optional. Keep it concise but specific so the model has clear direction.', 'listen-to-this-article' ); ?>
					</p>

					<p class="himoose-field">
						<label for="himoose-host-direction"><strong><?php esc_html_e( 'Host direction', 'listen-to-this-article' ); ?></strong></label>
						<textarea id="himoose-host-direction" class="widefat" rows="3" maxlength="500" placeholder="<?php echo esc_attr__( 'Optional host-specific delivery notes...', 'listen-to-this-article' ); ?>"><?php echo esc_textarea( $default_host_direction ); ?></textarea>
					</p>

					<p class="himoose-field">
						<label for="himoose-guest-direction"><strong><?php esc_html_e( 'Guest direction', 'listen-to-this-article' ); ?></strong></label>
						<textarea id="himoose-guest-direction" class="widefat" rows="3" maxlength="500" placeholder="<?php echo esc_attr__( 'Optional guest-specific delivery notes...', 'listen-to-this-article' ); ?>"><?php echo esc_textarea( $default_guest_direction ); ?></textarea>
					</p>

					<p class="himoose-field">
						<label for="himoose-scene"><strong><?php esc_html_e( 'The scene', 'listen-to-this-article' ); ?></strong></label>
						<textarea id="himoose-scene" class="widefat" rows="3" maxlength="500" placeholder="<?php echo esc_attr__( 'Optional atmosphere, setting, or vibe...', 'listen-to-this-article' ); ?>"><?php echo esc_textarea( $default_scene ); ?></textarea>
					</p>

					<p class="himoose-field">
						<label for="himoose-director-style"><strong><?php esc_html_e( 'Conversation style', 'listen-to-this-article' ); ?></strong></label>
						<textarea id="himoose-director-style" class="widefat" rows="2" maxlength="300" placeholder="<?php echo esc_attr__( 'Optional style guidance...', 'listen-to-this-article' ); ?>"><?php echo esc_textarea( $default_director_style ); ?></textarea>
					</p>

					<p class="himoose-field">
						<label for="himoose-director-pace"><strong><?php esc_html_e( 'Conversation pace', 'listen-to-this-article' ); ?></strong></label>
						<textarea id="himoose-director-pace" class="widefat" rows="2" maxlength="300" placeholder="<?php echo esc_attr__( 'Optional pacing guidance...', 'listen-to-this-article' ); ?>"><?php echo esc_textarea( $default_director_pace ); ?></textarea>
					</p>

					<p class="himoose-field">
						<label for="himoose-director-accent"><strong><?php esc_html_e( 'Accent', 'listen-to-this-article' ); ?></strong></label>
						<textarea id="himoose-director-accent" class="widefat" rows="2" maxlength="220" placeholder="<?php echo esc_attr__( 'Optional accent guidance...', 'listen-to-this-article' ); ?>"><?php echo esc_textarea( $default_director_accent ); ?></textarea>
					</p>

					<p class="himoose-field">
						<label for="himoose-context"><strong><?php esc_html_e( 'More context', 'listen-to-this-article' ); ?></strong></label>
						<textarea id="himoose-context" class="widefat" rows="4" maxlength="700" placeholder="<?php echo esc_attr__( 'Optional audience or brand context...', 'listen-to-this-article' ); ?>"><?php echo esc_textarea( $default_context ); ?></textarea>
					</p>
				</div>

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
				<p class="himoose-generate-hint" style="display:none;"></p>
				<p class="himoose-generate-error" style="display:none;"></p>
				</div>

				<p class="himoose-error" style="<?php echo $has_job ? 'display:none;' : 'display:none;'; ?>"></p>
				
				<p class="description" style="<?php echo $has_job ? 'display:none;' : ''; ?>">
					<?php 
					$auto_insert = get_option( 'himoose_auto_insert' );
					if ( 'page' === $post_type ) {
						echo wp_kses_post( __( 'Selecting audio saves the player data. <strong>You must insert the shortcode <code>[himoose_podcast]</code> in your content to display it on pages.</strong>', 'listen-to-this-article' ) );
					} else {
						if ( '1' === $auto_insert ) {
							esc_html_e( 'After selecting audio, click Update/Publish to save your changes and show the player at the top of this post.', 'listen-to-this-article' );
						} else {
							echo wp_kses_post( __( 'Selecting audio saves the player data. <strong>You must insert the shortcode <code>[himoose_podcast]</code> in your content to display it.</strong>', 'listen-to-this-article' ) );
						}
					}
					?>
				</p>

				<p class="himoose-generate-link" style="<?php echo $has_job ? 'display:none;' : ''; ?>">
					<a href="<?php echo esc_url( himoose_get_app_base() . '/podcast-generator' ); ?>" target="_blank">
						<?php esc_html_e( '📈 Open Analytics', 'listen-to-this-article' ); ?>
					</a>
				</p>

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

	$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
	$content = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';
	$focus = isset( $_POST['focus'] ) ? himoose_trim_generation_value( sanitize_textarea_field( wp_unslash( $_POST['focus'] ) ), 500 ) : '';
	$length = isset( $_POST['length'] ) ? sanitize_text_field( wp_unslash( $_POST['length'] ) ) : 'SHORT';
	$host_voice = isset( $_POST['hostVoiceName'] ) ? sanitize_text_field( wp_unslash( $_POST['hostVoiceName'] ) ) : 'Sulafat';
	$guest_voice = isset( $_POST['guestVoiceName'] ) ? sanitize_text_field( wp_unslash( $_POST['guestVoiceName'] ) ) : 'Fenrir';
	$primary_color = isset( $_POST['primaryColor'] ) ? sanitize_text_field( wp_unslash( $_POST['primaryColor'] ) ) : '#667eea';
	$secondary_color = isset( $_POST['secondaryColor'] ) ? sanitize_text_field( wp_unslash( $_POST['secondaryColor'] ) ) : '#764ba2';
	$custom_title = isset( $_POST['customTitle'] ) ? himoose_trim_generation_value( sanitize_text_field( wp_unslash( $_POST['customTitle'] ) ), 140 ) : '';
	$use_advanced_customization = ! empty( $_POST['useAdvancedCustomization'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['useAdvancedCustomization'] ) );
	$advanced_customization = array(
		'context'         => isset( $_POST['context'] ) ? himoose_trim_generation_value( sanitize_textarea_field( wp_unslash( $_POST['context'] ) ), 700 ) : '',
		'directorAccent'  => isset( $_POST['directorAccent'] ) ? himoose_trim_generation_value( sanitize_textarea_field( wp_unslash( $_POST['directorAccent'] ) ), 220 ) : '',
		'directorPace'    => isset( $_POST['directorPace'] ) ? himoose_trim_generation_value( sanitize_textarea_field( wp_unslash( $_POST['directorPace'] ) ), 300 ) : '',
		'directorStyle'   => isset( $_POST['directorStyle'] ) ? himoose_trim_generation_value( sanitize_textarea_field( wp_unslash( $_POST['directorStyle'] ) ), 300 ) : '',
		'guestDirection'  => isset( $_POST['guestDirection'] ) ? himoose_trim_generation_value( sanitize_textarea_field( wp_unslash( $_POST['guestDirection'] ) ), 500 ) : '',
		'hostDirection'   => isset( $_POST['hostDirection'] ) ? himoose_trim_generation_value( sanitize_textarea_field( wp_unslash( $_POST['hostDirection'] ) ), 500 ) : '',
		'scene'           => isset( $_POST['scene'] ) ? himoose_trim_generation_value( sanitize_textarea_field( wp_unslash( $_POST['scene'] ) ), 500 ) : '',
	);

	$length = in_array( $length, array( 'SHORT', 'STANDARD' ), true ) ? $length : 'SHORT';

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

	$primary_color = $sanitize_hex( $primary_color, '#667eea' );
	$secondary_color = $sanitize_hex( $secondary_color, '#764ba2' );

	// Normalize content to plain text (API-side can still re-process if needed).
	$content_text = html_entity_decode( wp_strip_all_tags( $content, true ), ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) );
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
			'useAdvancedCustomization' => $use_advanced_customization,
			'advancedCustomization' => $advanced_customization,
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
		'useAdvancedCustomization' => $use_advanced_customization,
		'advancedCustomization' => $advanced_customization,
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
