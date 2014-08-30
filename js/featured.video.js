// Featured Video Backend JavaScript

(function(Featured_Video, $, undefined) {

	Featured_Video.openModal = function() {
		
		$.post( ajaxurl, { action: 'featured_video_modal' }, function( response ) {

			$('#featured-video-modal-container').html(response);
			$('#_featured_video_modal').show();
		} );

	};

	Featured_Video.closeModal = function() {
		$('#_featured_video_modal').hide();
		$( '#featured-video-modal-container' ).html( '' );
	};

	Featured_Video.setVideo = function(element) {
		
		var url = element.data( 'video' ),
			thumb = element.data( 'thumb' );

		$( '#_featured_video_url' ).val( url );
		$( '#featured_video' ).html( '<img src="'+ thumb +'">' );

		$( '#thumbnail-change-toggle' ).html( '<p class="hide-if-no-js"><a href="#" id="remove-featured-video">'+ Featured_Video.RemoveVideo +'</a></p>' )

		Featured_Video.closeModal();

	};

	Featured_Video.removeVideo = function(element) {
		
		$('#_featured_video_modal').find( '.video-data' ).html( '' );

		$( '#_featured_video_url' ).val( '' );
		$( '#featured_video' ).html( '' );

		$( '#thumbnail-change-toggle' ).html( '<p class="hide-if-no-js"><a href="#" id="set-featured-video">'+ Featured_Video.SetVideo +'</a></p>' )

	};

	Featured_Video.getVideoData = function() {
		
		var video_url = $( '#_featured_video' ).val(),
			modal = $('#_featured_video_modal'),
			video_data = modal.find( '.video-data' ),
			data = {
				action: 'featured_video_get_data',
				url: video_url
			};

		video_data.html('<span class="spinner"></span>');

		$.post( ajaxurl, data, function( response ) {

			video_data.html( response );

		} );

	};

	Featured_Video.bindButtons = function() {
		
		$( '.featured-video-metabox-container' ).on( 'click', '#set-featured-video', function(e) {

			e.preventDefault();

			Featured_Video.openModal();

		} );
		
		$( '.featured-video-metabox-container #featured_video' ).on( 'click', 'img', function(e) {

			e.preventDefault();

			Featured_Video.openModal();

		} );
		
		$( '.featured-video-metabox-container' ).on( 'click', '#remove-featured-video', function(e) {

			e.preventDefault();

			Featured_Video.removeVideo();

		} );

	};

	Featured_Video.bindModal = function() {
		
		$( '#featured-video-modal-container' ).on( 'click', '.featured-video-modal #_get_video_data', function(e) {

			e.preventDefault();

			Featured_Video.getVideoData();

		} );

		$( '#featured-video-modal-container' ).on( 'click', '#_featured_video_modal .video-data .video-data-item #insert-video', function() {

			Featured_Video.setVideo( $(this).parent().parent() );

		} );
		
		$( '#featured-video-modal-container' ).on( 'click', '#_featured_video_modal 	.media-modal-close', function(e) {

			e.preventDefault();

			Featured_Video.closeModal();

		} );

	};

	$(function() { //wait for ready

		Featured_Video.bindButtons();
		Featured_Video.bindModal();

	});

}(window.Featured_Video = window.Featured_Video || {}, jQuery));