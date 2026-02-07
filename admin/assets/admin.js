/* global himooseAjax */
( function( $ ) {
	$( document ).ready( function() {
		var pollTimer = null;
		var pollStartedAt = null;
		var currentJobId = null;
		var currentPreviewUrl = null;
		var sampleAudio = null;

		// Meta box helper text (under the buttons). We'll swap it after a dropdown selection.
		var $metaBoxHelperText = $( '#himoose-meta-box-container p.description' ).not( '.himoose-shortcode-reminder' );
		var metaBoxHelperDefaultHtml = $metaBoxHelperText.length ? $metaBoxHelperText.html() : '';
		var shouldSwapHelperOnSelect =
			$metaBoxHelperText.length &&
			window.himooseAjax &&
			himooseAjax.postType === 'post' &&
			/top of this post/i.test( $metaBoxHelperText.text() );
		var metaBoxHelperAfterSelectText = 'Make sure to save your changes.';

		function clearPoll() {
			if ( pollTimer ) {
				window.clearInterval( pollTimer );
				pollTimer = null;
			}
			pollStartedAt = null;
		}

		function setInlineError( message, upgradeUrl ) {
			var $err = $( '.himoose-generate-error' );
			if ( ! $err.length ) {
				return;
			}

			var html = '';
			if ( message ) {
				html += '<div class="himoose-generate-error-message">' + String( message ) + '</div>';
			}
			if ( upgradeUrl ) {
				html +=
					'<div class="himoose-upgrade-cta">' +
					'<a class="button button-primary" target="_blank" rel="noopener noreferrer" href="' +
					encodeURI( upgradeUrl ) +
					'">Upgrade</a>' +
					'</div>';
			}
			$err.html( html ).show();
		}

		function setStatusText( message ) {
			var $status = $( '.himoose-generate-status' );
			if ( $status.length ) {
				$status.text( message || '' ).toggle( !! message );
			}
		}

		function setHintText( message ) {
			var $hint = $( '.himoose-generate-hint' );
			if ( $hint.length ) {
				$hint.text( message || '' ).toggle( !! message );
			}
		}

		function setPreviewLink( url ) {
			var $preview = $( '.himoose-preview' );
			currentPreviewUrl = url || null;
			if ( ! $preview.length ) {
				return;
			}
			if ( url ) {
				var safeUrl = encodeURI( url );
				$preview
					.html(
						'<audio controls preload="metadata" src="' + safeUrl + '" style="width:100%;"></audio>'
					)
					.show();

				var audioEl = $preview.find( 'audio' ).get( 0 );
				if ( audioEl && typeof audioEl.load === 'function' ) {
					try {
						audioEl.load();
					} catch ( e ) {
						// ignore
					}
				}
			} else {
				$preview.hide().empty();
			}
		}

		function fetchPreviewUrlForJobId( jobId, callback ) {
			if ( ! jobId ) {
				callback( null );
				return;
			}
			$.ajax( {
				url: himooseAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'himoose_get_podcast_status',
					nonce: himooseAjax.nonce,
					jobId: jobId
				},
				success: function( response ) {
					if ( ! response || ! response.success || ! response.data ) {
						callback( null );
						return;
					}
					var data = response.data || {};
					callback( data.previewUrl || data.preview_url || null );
				},
				error: function() {
					callback( null );
				}
			} );
		}

		function looksLikeAudioUrl( url ) {
			if ( ! url ) {
				return false;
			}
			return /\.(mp3|wav|m4a|ogg)(\?|$)/i.test( String( url ) );
		}

		function setProgressDialog( visible, message ) {
			var $dialog = $( '.himoose-progress-dialog' );
			if ( ! $dialog.length ) {
				return;
			}

			if ( message ) {
				$dialog.find( '.himoose-progress-dialog-text' ).text( String( message ) );
			}

			if ( visible ) {
				$dialog.css( 'display', 'flex' );
			} else {
				$dialog.hide();
			}
		}

		function collapseGenerationControls() {
			// Hide the top toggle button and the form fields, but keep status/preview/hint visible.
			$( '#himoose-start-generate' ).hide();
			var $fields = $( '#himoose-generate-fields' );
			if ( $fields.length ) {
				$fields.show();
				$fields
					.children()
					.not( '.himoose-generate-status, .himoose-preview, .himoose-generate-hint, .himoose-generate-error' )
					.hide();
			}
		}

		function getEditorTitleAndContent() {
			// Gutenberg (Block Editor): prefer wp.data selectors when available.
			try {
				if ( window.wp && wp.data && typeof wp.data.select === 'function' ) {
					var editor = wp.data.select( 'core/editor' );
					if ( editor && typeof editor.getEditedPostAttribute === 'function' ) {
						var title = editor.getEditedPostAttribute( 'title' ) || '';
						// Use raw content; server will strip tags.
						var content = editor.getEditedPostAttribute( 'content' ) || '';
						return { title: title, content: content };
					}
				}
			} catch ( e ) {
				// ignore
			}

			// Classic editor fallback
			var titleClassic = $( '#title' ).val() || '';
			var contentClassic = '';
			if ( typeof window.tinyMCE !== 'undefined' && window.tinyMCE.get( 'content' ) && ! window.tinyMCE.get( 'content' ).isHidden() ) {
				contentClassic = window.tinyMCE.get( 'content' ).getContent() || '';
			} else {
				contentClassic = $( '#content' ).val() || '';
			}
			return { title: titleClassic, content: contentClassic };
		}

		function upsertSelectOption( jobId, label ) {
			var $select = $( '#himoose-podcast-select' );
			var $container = $( '#himoose-podcast-selector' );
			if ( ! $select.length ) {
				return;
			}
			$container.show();
			var existing = $select.find( 'option[value="' + jobId + '"]' );
			if ( existing.length ) {
				existing.text( label );
			} else {
				$select.append( $( '<option>', { value: jobId, text: label } ) );
			}
			$select.val( jobId ).trigger( 'change' );
		}

		function mapStatusToMessage( status ) {
			switch ( status ) {
				case 'pending':
					return 'Queued…';
				case 'processing':
					return 'Processing… (this can take a few minutes)';
				case 'ready':
					return 'Ready!';
				case 'failed':
					return 'Failed.';
				default:
					return 'Working…';
			}
		}

		function pollStatus() {
			if ( ! currentJobId ) {
				return;
			}
			if ( pollStartedAt && ( Date.now() - pollStartedAt ) > ( 15 * 60 * 1000 ) ) {
				clearPoll();
				setInlineError( 'Generation is taking longer than expected. Please check again later by loading latest audio.', null );
				setStatusText( '' );
				$( '#himoose-generate-submit' ).prop( 'disabled', false ).show();
				$( '#himoose-generate-close' ).show();
				setProgressDialog( false );
				return;
			}

			$.ajax( {
				url: himooseAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'himoose_get_podcast_status',
					nonce: himooseAjax.nonce,
					jobId: currentJobId
				},
				success: function( response ) {
					if ( ! response || ! response.success ) {
						var msg = response && response.data && response.data.message ? response.data.message : 'Error checking status.';
						setInlineError( msg, response && response.data ? response.data.upgradeUrl : null );
						setProgressDialog( false );
						$( '#himoose-generate-close' ).show();
						return;
					}
					var data = response.data || {};
					var status = data.status || data.rawStatus || '';
					var statusMessage = mapStatusToMessage( status );
					setStatusText( statusMessage );
					if ( status === 'pending' || status === 'processing' ) {
						setProgressDialog( true, statusMessage );
					}
					if ( status === 'ready' ) {
						clearPoll();
						setProgressDialog( false );
						setStatusText( 'Your audio is ready!' );
							collapseGenerationControls();
						if ( data.previewUrl ) {
							setPreviewLink( data.previewUrl );
						}
						if ( data.jobId ) {
							var label = data.label || ( 'Audio Selected (ID: ' + data.jobId + ')' );
							upsertSelectOption( data.jobId, label );
						}
						setHintText( '' );
					} else if ( status === 'failed' ) {
						clearPoll();
						setProgressDialog( false );
						setInlineError( data.error || 'Audio generation failed. Please try again.', null );
						setStatusText( '' );
							$( '#himoose-generate-submit' ).prop( 'disabled', false ).show();
							$( '#himoose-generate-close' ).show();
					}
				}
			} );
		}

		// Initialize WP color picker if present.
		if ( $.fn.wpColorPicker ) {
			$( '.himoose-color-field' ).wpColorPicker();
		}

		var $startGenerate = $( '#himoose-start-generate' );
		var startGenerateDefaultLabel = ( $startGenerate.text() || '' ).trim();
		$( '#himoose-start-generate' ).on( 'click', function( e ) {
			e.preventDefault();
			var $fields = $( '#himoose-generate-fields' );
			var $fetchBtn = $( '#himoose-fetch-podcasts' );
			var $helperText = $( '#himoose-meta-box-container p.description' ).not( '.himoose-shortcode-reminder' );
			$fields.show();
			$startGenerate.hide().text( startGenerateDefaultLabel );
			$fetchBtn.hide();
			$helperText.hide();

			$( '.himoose-generate-error' ).hide().empty();
			setHintText( '' );
		} );

		$( '#himoose-generate-close' ).on( 'click', function( e ) {
			e.preventDefault();
			var $fields = $( '#himoose-generate-fields' );
			var $fetchBtn = $( '#himoose-fetch-podcasts' );
			var $helperText = $( '#himoose-meta-box-container p.description' ).not( '.himoose-shortcode-reminder' );

			$fields.hide();
			$startGenerate.show().text( startGenerateDefaultLabel );
			$( '.himoose-generate-error' ).hide().empty();
			setHintText( '' );
			setStatusText( '' );
			setPreviewLink( null );
			setProgressDialog( false );

			// Restore load + helper only if no job is already selected.
			if ( ! $( '#himoose_job_id' ).val() ) {
				$fetchBtn.show();
				$helperText.show();
				$( '.himoose-generate-link' ).show();
			}
		} );

		$( '.himoose-voice-sample' ).on( 'click', function( e ) {
			e.preventDefault();
			var $btn = $( this );
			var source = $btn.data( 'voice-source' );
			var voice = source === 'guest' ? $( '#himoose-guest-voice' ).val() : $( '#himoose-host-voice' ).val();
			if ( ! voice ) {
				return;
			}
			var voiceFile = String( voice ).toLowerCase();
			var url = ( himooseAjax.sampleBaseUrl || '' ) + voiceFile + ( himooseAjax.sampleExt || '.wav' );
			url += ( url.indexOf( '?' ) === -1 ? '?' : '&' ) + 'dev=1337';

			if ( sampleAudio && ! sampleAudio.paused ) {
				sampleAudio.pause();
				sampleAudio.currentTime = 0;
				$( '.himoose-voice-sample' ).text( 'Play' );
				// If clicking again while playing, treat as stop.
				if ( sampleAudio.src === url ) {
					return;
				}
			}

			sampleAudio = new Audio( url );
			sampleAudio.addEventListener( 'ended', function() {
				$( '.himoose-voice-sample' ).text( 'Play' );
			} );
			$( '.himoose-voice-sample' ).text( 'Play' );
			$btn.text( 'Stop' );
			sampleAudio.play().catch( function() {
				$btn.text( 'Play' );
			} );
		} );

		$( '#himoose-generate-submit' ).on( 'click', function( e ) {
			e.preventDefault();
			$( '.himoose-generate-error' ).hide().empty();
			setPreviewLink( null );
			setHintText( '' );

			// Prevent users from closing the form mid-request.
			$( '#himoose-generate-close' ).hide();
			setProgressDialog( true, 'Starting generation…' );

			var meta = getEditorTitleAndContent();
			var postId = $( '#himoose-meta-box-container' ).data( 'post-id' );
			if ( ! postId ) {
				setInlineError( 'Missing post ID. Please reload and try again.', null );
				setProgressDialog( false );
				$( '#himoose-generate-close' ).show();
				return;
			}

			var payload = {
				action: 'himoose_generate_podcast',
				nonce: himooseAjax.nonce,
				postId: postId,
				title: meta.title || '',
				content: meta.content || '',
				focus: $( '#himoose-focus' ).val() || '',
				length: $( '#himoose-audio-length' ).val() || 'SHORT',
				hostVoiceName: $( '#himoose-host-voice' ).val() || 'Sulafat',
				guestVoiceName: $( '#himoose-guest-voice' ).val() || 'Fenrir',
				primaryColor: $( '#himoose-primary-color' ).val() || '#667eea',
				secondaryColor: $( '#himoose-secondary-color' ).val() || '#764ba2',
				customTitle: $( '#himoose-custom-title' ).val() || ''
			};

			$( '#himoose-generate-submit' ).prop( 'disabled', true );
			$( '#himoose-generate-spinner' ).addClass( 'is-active' ).css( 'display', 'inline-block' );
			setStatusText( 'Starting generation…' );

			$.ajax( {
				url: himooseAjax.ajaxurl,
				type: 'POST',
				data: payload,
				success: function( response ) {
					var $generateBtn = $( '#himoose-generate-submit' );
					$( '#himoose-generate-spinner' ).removeClass( 'is-active' ).hide();

					if ( ! response || ! response.success ) {
						var msg = response && response.data && response.data.message ? response.data.message : 'Generation request failed.';
						setInlineError( msg, response && response.data ? response.data.upgradeUrl : null );
						setStatusText( '' );
							setProgressDialog( false );
						$generateBtn.prop( 'disabled', false ).show();
							$( '#himoose-generate-close' ).show();
						return;
					}

					var data = response.data || {};
					currentJobId = data.jobId || null;
					if ( ! currentJobId ) {
						setInlineError( 'Generation started but no jobId was returned.', null );
						setStatusText( '' );
							setProgressDialog( false );
						$generateBtn.prop( 'disabled', false ).show();
							$( '#himoose-generate-close' ).show();
						return;
					}

					// Job accepted: hide the submit button to prevent duplicate submissions.
					$generateBtn.prop( 'disabled', true ).hide();

						setStatusText( 'Queued…' );
						setProgressDialog( true, 'Queued…' );
					pollStartedAt = Date.now();
					clearPoll();
					pollTimer = window.setInterval( pollStatus, 15000 );
					// Do an immediate poll so the user sees status quickly.
					pollStatus();
				},
				error: function() {
					$( '#himoose-generate-submit' ).prop( 'disabled', false );
					$( '#himoose-generate-spinner' ).removeClass( 'is-active' ).hide();
					setInlineError( 'Network error. Please try again.', null );
					setStatusText( '' );
						setProgressDialog( false );
					$( '#himoose-generate-submit' ).show();
						$( '#himoose-generate-close' ).show();
				}
			} );
		} );

		$( '#himoose-fetch-podcasts' ).on( 'click', function( e ) {
			e.preventDefault();

			var $button = $( this );
			var $spinner = $( '#himoose-spinner' );
			var $select = $( '#himoose-podcast-select' );
			var $container = $( '#himoose-podcast-selector' );
			var $errorContainer = $( '.himoose-error' );

			$button.prop( 'disabled', true );
			$spinner.addClass( 'is-active' ).css( 'display', 'inline-block' );
			$errorContainer.hide().text( '' );

			$.ajax( {
				url: himooseAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'himoose_get_podcasts',
					nonce: himooseAjax.nonce
				},
				success: function( response ) {
					$button.prop( 'disabled', false );
					$spinner.removeClass( 'is-active' ).hide();

					if ( response.success ) {
						var data = response.data;
						var podcasts = [];

						// Handle response structure: { podcasts: [...] } or [...]
						if ( data && Array.isArray( data.podcasts ) ) {
							podcasts = data.podcasts;
						} else if ( Array.isArray( data ) ) {
							podcasts = data;
						}

						$select.empty();
						$select.append( $( '<option>', {
							value: '',
							text: 'Select audio...'
						} ) );

						var currentJobId = $( '#himoose_job_id' ).val();

						if ( podcasts.length > 0 ) {
							$.each( podcasts, function( i, podcast ) {
								// Map API fields: id -> jobId, created -> completedDate
								var id = podcast.id || podcast.jobId;
								var date = podcast.created || podcast.completedDate || '';
								var previewUrl =
									podcast.previewUrl ||
									podcast.preview_url ||
									podcast.audioUrl ||
									podcast.audio_url ||
									podcast.mp3Url ||
									podcast.mp3_url ||
									podcast.audioFileUrl ||
									podcast.audio_file_url ||
									podcast.url ||
									'';

								// Format date if possible (simple substring for YYYY-MM-DD)
								if ( date && date.length >= 10 ) {
									date = date.substring( 0, 10 );
								}

								var text = ( podcast.title || 'Untitled' ) + ' – ' + date;

								// Truncate long titles visually
								if ( text.length > 60 ) {
									text = text.substring( 0, 60 ) + '...';
								}

								var isSelected = ( id === currentJobId );

								if ( isSelected ) {
									// Ensure the label is up to date in the hidden field
									$( '#himoose_podcast_label' ).val( text );
								}

								var $opt = $( '<option>', {
									value: id,
									text: text,
									selected: isSelected
								} );
								if ( previewUrl ) {
									$opt.attr( 'data-preview-url', previewUrl );
								}
								$select.append( $opt );
							} );
							$container.show();

							// Update button visibility based on current selection state
							if ( currentJobId && $select.val() === currentJobId ) {
								$( '#himoose-remove-section' ).show();
								$( '#himoose-remove-podcast' ).show();
								$( '#himoose-fetch-podcasts' ).hide();
								$( '#himoose-start-generate' ).hide();
							} else {
								$( '#himoose-remove-podcast' ).hide();
								$( '#himoose-remove-section' ).hide();
								$( '#himoose-fetch-podcasts' ).show();
								$( '#himoose-start-generate' ).show();
							}
						} else {
							$errorContainer.text( 'No audio was found for this domain,  you should generate some!' ).show();
						}
					} else {
						$errorContainer.text( response.data ).show();
					}
				},
				error: function() {
					$button.prop( 'disabled', false );
					$spinner.removeClass( 'is-active' ).hide();
					$errorContainer.text( 'Network error. Please try again.' ).show();
				}
			} );
		} );

		$( '#himoose-podcast-select' ).on( 'change', function() {
			var jobId = $( this ).val();
			var $selected = $( this ).find( 'option:selected' );
			var label = $selected.text();
			var previewUrl = $selected.attr( 'data-preview-url' ) || '';
			$( '#himoose_job_id' ).val( jobId );

			if ( jobId ) {
				$( '#himoose_podcast_label' ).val( label );
				$( '#himoose-remove-section' ).show();
				$( '#himoose-remove-podcast' ).show();
				$( '#himoose-fetch-podcasts' ).hide();
				$( '#himoose-start-generate' ).hide();

				// If we have a preview URL from the API response, load it into the preview player.
				if ( previewUrl && looksLikeAudioUrl( previewUrl ) ) {
					setPreviewLink( previewUrl );
				} else {
					fetchPreviewUrlForJobId( jobId, function( fetchedUrl ) {
						// Ensure the selection hasn't changed.
						if ( $( '#himoose-podcast-select' ).val() !== jobId ) {
							return;
						}
						if ( fetchedUrl ) {
							$selected.attr( 'data-preview-url', fetchedUrl );
							setPreviewLink( fetchedUrl );
						} else if ( previewUrl ) {
							// Fall back to the list-provided URL even if it doesn't look like a direct audio file.
							setPreviewLink( previewUrl );
						}
					} );
				}

				if ( shouldSwapHelperOnSelect && $metaBoxHelperText.length ) {
					$metaBoxHelperText.text( metaBoxHelperAfterSelectText ).show();
				}
			} else {
				$( '#himoose_podcast_label' ).val( '' );
				$( '#himoose-remove-podcast' ).hide();
				$( '#himoose-remove-section' ).hide();
				$( '#himoose-fetch-podcasts' ).show();
				$( '#himoose-start-generate' ).show();
				setPreviewLink( null );

				if ( shouldSwapHelperOnSelect && $metaBoxHelperText.length && metaBoxHelperDefaultHtml ) {
					$metaBoxHelperText.html( metaBoxHelperDefaultHtml );
				}
			}
		} );

		// If the meta box loads with a selected jobId (e.g. existing selection), try to load its preview.
		var $podcastSelect = $( '#himoose-podcast-select' );
		if ( $podcastSelect.length && $podcastSelect.val() ) {
			var $opt = $podcastSelect.find( 'option:selected' );
			var initialPreviewUrl = $opt.attr( 'data-preview-url' ) || '';
			var initialJobId = $podcastSelect.val();
			if ( initialPreviewUrl ) {
				setPreviewLink( initialPreviewUrl );
			} else {
				fetchPreviewUrlForJobId( initialJobId, function( fetchedUrl ) {
					if ( $podcastSelect.val() !== initialJobId ) {
						return;
					}
					if ( fetchedUrl ) {
						$podcastSelect.find( 'option:selected' ).attr( 'data-preview-url', fetchedUrl );
						setPreviewLink( fetchedUrl );
					}
				} );
			}
		}

		$( '#himoose-remove-podcast' ).on( 'click', function( e ) {
			e.preventDefault();
			clearPoll();
			setProgressDialog( false );
			setStatusText( '' );
			setHintText( '' );
			setPreviewLink( null );
			$( '.himoose-generate-error' ).hide().empty();

			$( '#himoose_job_id' ).val( '' );
			$( '#himoose_podcast_label' ).val( '' );
			$( '#himoose-podcast-select' ).val( '' ).empty();
			$( '#himoose-podcast-selector' ).hide();
			$( '#himoose-remove-podcast' ).hide();
			$( '#himoose-remove-section' ).hide();
			$( '#himoose-generate-fields' ).hide();
			$( '#himoose-start-generate' ).show();
			$( '#himoose-fetch-podcasts' ).show();
			$( '#himoose-meta-box-container p.description' ).not( '.himoose-shortcode-reminder' ).show();
			$( '.himoose-generate-link' ).show();
			$( '.himoose-shortcode-reminder' ).hide();
		} );

		$( document ).on( 'click', '.himoose-review-dismiss', function( e ) {
			e.preventDefault();
			var $prompt = $( this ).closest( '.himoose-review-prompt' );
			$prompt.hide();
			$.ajax( {
				url: himooseAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'himoose_dismiss_review_prompt',
					nonce: himooseAjax.nonce
				}
			} );
		} );
	} );
}( jQuery ) );
