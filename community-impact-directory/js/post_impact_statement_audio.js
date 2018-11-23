jQuery('#select_audio_button').click( function( event ){
	// Create the media frame.
	var file_frame = wp.media.frames.file_frame = wp.media({
		title: 'Select audio track',
		button: {
			text: 'Use this audio track',
		},
		multiple: false	// Only allow one audio file to be selected
	});
	
	// When an audio file is selected, run a callback.
	file_frame.on( 'select', function() {
		var attachment = file_frame.state().get('selection').first().toJSON();
		var player = jQuery('#audio_track_player');
		
		if(player.children('source').length) {
			player.children('source[type="audio/mpeg"]').attr('src', attachment.url);
		} else {
			player.append( '<source type="audio/mpeg" src="' + attachment.url + '">' );
		}
		jQuery('#impact_statement_audio').val( attachment.url );
	});
	
	// Open the modal
	file_frame.open();
});