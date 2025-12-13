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
		__( 'Add a Podcast', 'listen-to-this-article' ),
		'himoose_render_meta_box',
		'post',
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
	
	?>
	<div id="himoose-meta-box-container">
		<input type="hidden" name="himoose_job_id" id="himoose_job_id" value="<?php echo esc_attr( $job_id ); ?>" />
		<input type="hidden" name="himoose_podcast_label" id="himoose_podcast_label" value="<?php echo esc_attr( $label ); ?>" />

		<div id="himoose-podcast-selector" style="<?php echo $has_job ? '' : 'display:none;'; ?>">
			<select id="himoose-podcast-select" class="widefat">
				<?php if ( $has_job ) : ?>
					<?php 
					$display_label = ! empty( $label ) ? $label : sprintf( 
						/* translators: %s: Podcast Job ID */
						__( 'Podcast Selected (ID: %s)', 'listen-to-this-article' ), 
						$job_id 
					); 
					?>
				<option value="<?php echo esc_attr( $job_id ); ?>" selected><?php echo esc_html( $display_label ); ?></option>
			<?php else : ?>
				<option value=""><?php esc_html_e( 'Select a podcast...', 'listen-to-this-article' ); ?></option>
			<?php endif; ?>
			</select>
		</div>

		<?php if ( empty( $api_key ) ) : ?>
			<p>
				<?php esc_html_e( 'Please connect to Hi, Moose to load podcasts.', 'listen-to-this-article' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=himoose-settings' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Go to Settings', 'listen-to-this-article' ); ?>
				</a>
			</p>
		<?php else : ?>
			<button type="button" id="himoose-fetch-podcasts" class="button button-secondary" style="<?php echo $has_job ? 'display:none;' : ''; ?>">
				<?php esc_html_e( 'Load latest podcasts', 'listen-to-this-article' ); ?>
			</button>

			<button type="button" id="himoose-remove-podcast" class="button-link button-link-delete" style="<?php echo $has_job ? '' : 'display:none;'; ?>">
				<?php esc_html_e( 'Remove podcast', 'listen-to-this-article' ); ?>
			</button>

			<span class="spinner" id="himoose-spinner" style="display:none; float:none; margin-left: 5px;"></span>

			<p class="himoose-error" style="display:none;"></p>
			
			<p class="description">
				<?php 
				$auto_insert = get_option( 'himoose_auto_insert' );
				if ( '1' === $auto_insert ) {
					esc_html_e( 'Selecting a podcast will automatically display the player at the top of this post.', 'listen-to-this-article' );
				} else {
					echo wp_kses_post( __( 'Selecting a podcast saves the player data. <strong>You must insert the shortcode <code>[himoose_podcast]</code> in your content to display it.</strong>', 'listen-to-this-article' ) );
				}
				?>
			</p>

			<p class="himoose-generate-link">
				<a href="https://app.himoose.com/podcast-generator" target="_blank">
					<?php esc_html_e( 'Generate a new podcast', 'listen-to-this-article' ); ?> &rarr;
				</a>
			</p>
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

	// Handle clearing the podcast.
	if ( empty( $new_job_id ) ) {
		update_post_meta( $post_id, '_himoose_job_id', '' );
		delete_post_meta( $post_id, '_himoose_podcast_label' );
		delete_post_meta( $post_id, '_himoose_embed_html' );
		return;
	}

	// Check if we need to fetch new embed data.
	$existing_embed = get_post_meta( $post_id, '_himoose_embed_html', true );
	$needs_fetch    = ( $new_job_id !== $old_job_id || empty( $existing_embed ) );

	if ( $needs_fetch ) {
		$embed_html = himoose_remote_get_embed( $new_job_id );

		if ( ! is_wp_error( $embed_html ) ) {
			// Success! Update ID, Embed, and Label.
			update_post_meta( $post_id, '_himoose_job_id', $new_job_id );
			update_post_meta( $post_id, '_himoose_embed_html', $embed_html );

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
		if ( isset( $_POST['himoose_podcast_label'] ) ) {
			update_post_meta( $post_id, '_himoose_podcast_label', sanitize_text_field( wp_unslash( $_POST['himoose_podcast_label'] ) ) );
		}
	}
}
add_action( 'save_post', 'himoose_save_meta_box_data' );

/**
 * Enqueue Admin Scripts.
 */
function himoose_enqueue_admin_scripts( $hook ) {
	global $post;

	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}

	// Only enqueue if post type supports our meta box (which is 'post' by default).
	if ( 'post' !== get_post_type( $post ) ) {
		return;
	}

	wp_enqueue_script( 'himoose-admin-js', HIMOOSE_PLUGIN_URL . 'admin/assets/admin.js', array( 'jquery' ), HIMOOSE_VERSION, true );
	wp_enqueue_style( 'himoose-admin-css', HIMOOSE_PLUGIN_URL . 'admin/assets/admin.css', array(), HIMOOSE_VERSION );

	wp_localize_script( 'himoose-admin-js', 'himooseAjax', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'himoose_ajax_nonce' ),
	) );
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
