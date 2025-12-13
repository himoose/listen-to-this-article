/* global himooseAjax */
( function( $ ) {
	$( document ).ready( function() {
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
							text: 'Select a podcast...'
						} ) );

						var currentJobId = $( '#himoose_job_id' ).val();

						if ( podcasts.length > 0 ) {
							$.each( podcasts, function( i, podcast ) {
								// Map API fields: id -> jobId, created -> completedDate
								var id = podcast.id || podcast.jobId;
								var date = podcast.created || podcast.completedDate || '';

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

								$select.append( $( '<option>', {
									value: id,
									text: text,
									selected: isSelected
								} ) );
							} );
							$container.show();

							// Update button visibility based on current selection state
							if ( currentJobId && $select.val() === currentJobId ) {
								$( '#himoose-remove-podcast' ).show();
								$( '#himoose-fetch-podcasts' ).hide();
							} else {
								$( '#himoose-remove-podcast' ).hide();
								$( '#himoose-fetch-podcasts' ).show();
							}
						} else {
							$errorContainer.text( 'No podcasts found for this domain.' ).show();
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
			var label = $( this ).find( 'option:selected' ).text();
			$( '#himoose_job_id' ).val( jobId );

			if ( jobId ) {
				$( '#himoose_podcast_label' ).val( label );
				$( '#himoose-remove-podcast' ).show();
				$( '#himoose-fetch-podcasts' ).hide();
			} else {
				$( '#himoose_podcast_label' ).val( '' );
				$( '#himoose-remove-podcast' ).hide();
				$( '#himoose-fetch-podcasts' ).show();
			}
		} );

		$( '#himoose-remove-podcast' ).on( 'click', function( e ) {
			e.preventDefault();
			$( '#himoose_job_id' ).val( '' );
			$( '#himoose_podcast_label' ).val( '' );
			$( '#himoose-podcast-select' ).val( '' ).empty();
			$( '#himoose-podcast-selector' ).hide();
			$( '#himoose-remove-podcast' ).hide();
			$( '#himoose-fetch-podcasts' ).show();
		} );
	} );
}( jQuery ) );
