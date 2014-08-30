<?php  
/**
 * Template tags that can be used by developers to manually insert a video.
 * 
 * Made to act, feel and look just like the standard WP functions.
 * 
 * @package WordPress
 * @author Tor Morten Jensen <tormorten@tormorten.no>
 **/

if( !function_exists('the_post_video_thumbnail') ) {

	/**
	 * Display Post Thumbnail.
	 *
	 * @since 2.9.0
	 *
	 * @param string|array $size Optional. Image size. Defaults to 'post-thumbnail', which theme sets using set_post_thumbnail_size( $width, $height, $crop_flag );.
	 * @param string|array $attr Optional. Query string or array of attributes.
	 */
	function the_post_video_thumbnail( $size = 'post-thumbnail', $attr = '' ) {

		echo get_the_post_video_thumbnail( null, $size, $attr );
		
	}

}

if( !function_exists('get_the_post_video_thumbnail') ) {

	/**
	 * Retrieve Post Thumbnail.
	 *
	 * @since 2.9.0
	 *
	 * @param int $post_id Optional. Post ID.
	 * @param string $size Optional. Image size. Defaults to 'post-thumbnail'.
	 * @param string|array $attr Optional. Query string or array of attributes.
	 */
	function get_the_post_video_thumbnail( $post_id = null, $size = 'post-thumbnail', $attr = '' ) {
	
		$post_id = ( null === $post_id ) ? get_the_ID() : $post_id;

		$video = new WP_Featured_Video(false);

		return $video->replace_thumbnail('', $post_id, null, $size, $attr);

	}

}

?>