// Featured Video Public JavaScript

(function(Featured_Video, $, undefined) {

	Featured_Video.setAspectRatio = function() {
		
		var $allVideos = $(".featured-video iframe");

		// Figure out and save aspect ratio for each video
		$allVideos.each(function() {

		  $(this)
		    .data('aspectRatio', this.height / this.width)

		    // and remove the hard coded width/height
		    .removeAttr('height')
		    .removeAttr('width');

		});

	};

	Featured_Video.keepAsceptRatio = function() {
		
		var $fluidEl = $( '.featured-video' ),
			$allVideos = $(".featured-video iframe");

		var newWidth = $fluidEl.width();

		  // Resize all videos according to their own aspect ratio
		  $allVideos.each(function() {

		    var $el = $(this);

		    $el
		      .width(newWidth)
		      .height(newWidth * $el.data('aspectRatio'));

		  });

	};

	Featured_Video.bindActions = function() {
		
		$(window).on( 'resize', function() {

			Featured_Video.keepAsceptRatio();

		} ).resize();

	};

	$(function() { //wait for ready

		Featured_Video.setAspectRatio();
		Featured_Video.bindActions();

	});

}(window.Featured_Video = window.Featured_Video || {}, jQuery));