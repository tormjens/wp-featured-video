<?php  
/**
 * WP Featured Video WordPress Plugin
 * 
 * @package WordPress
 **/

/*
 * Plugin Name: WP Featured Video
 * Description: Integrates with the existing featured image-functions, so it works with all themes.
 * Plugin URI: http://tormorten.no
 * Author: Tor Morten Jensen
 * Author URI: http://tormorten.no
 * Version: 1.0
 * License: GPL2
 * Text Domain: wp-featured-video
 * Domain Path: languages/
 * 
 */

/*

    Copyright (C) 2014  Tor Morten Jensen  tormorten@tormorten.no

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if( !defined( 'ABSPATH' ) )
	die( 'Hold on there cowboy! You can not load this directly. Load it in to WordPress and see what happens!' );

if( !class_exists( 'WP_Featured_Video' ) ) {

	/**
	 * Featured Video Main class
	 *
	 * @author Tor Morten Jensen <tormorten@tormorten.no>
	 **/
	class WP_Featured_Video {

		/**
		 * Adds the necessary actions and filters for the plugin to do its magic
		 *
		 * @param bool $load To load or not to load actions. That is the question.
		 * @return void
		 **/
		public function __construct( $load = true ) {

			if( $load ) {

				if( !is_admin() ) {

					// replace the exisiting thumbnail
					add_filter( 'post_thumbnail_html', array( $this, 'replace_thumbnail' ), 99, 5 );

					// add scripts
					add_action( 'wp_enqueue_scripts', array( $this, 'public_scripts' ) );

					// check if a thumbnail has been set
					add_filter( 'get_post_metadata', array( $this, 'has_video_check' ), 99, 4 );

				}

				// these hooks only apply to the admin
				if( is_admin() ) {

					// add the link to featured image meta box
					add_filter( 'admin_post_thumbnail_html', array( $this, 'add_button' ), 99, 2 );

					// create a modal
					add_action( 'admin_footer', array( $this, 'render_modal_container' ) );

					// add scripts
					add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

					// do ajax to get video information
					add_action( 'wp_ajax_featured_video_get_data', array( $this, 'ajax_render_video_data' ) );
					add_action( 'wp_ajax_featured_video_modal', array( $this, 'render_modal' ) );

					// save the video url
					add_action( 'save_post', array( $this, 'save' ) );

				}
			}

		}

		/**
		 * Registers and enqueus scripts needed for the featured video feature to work
		 *
		 * @return void
		 **/
		public function public_scripts() {

			wp_enqueue_script( 'jquery' );

			wp_enqueue_script( 'wp-featured-video-js', plugins_url( 'js/featured.video.public.js', __FILE__ ), array( 'jquery' ), '1.0' );
			
			wp_enqueue_style( 'wp-featured-video-css', plugins_url( 'css/featured.video.public.css', __FILE__ ), array(), '1.0' );

		}

		/**
		 * Checks if a post has video
		 *
		 * @param null|array|string $value     The value get_metadata() should
	 	 *                                     return - a single metadata value,
		 *                                     or an array of values.
		 * @param int               $object_id Object ID.
		 * @param string            $meta_key  Meta key.
		 * @param string|array      $single    Meta value, or an array of values.
		 **/
		public function has_video_check( $value, $object_id, $meta_key, $single ) {

			if( $meta_key == '_thumbnail_id' ) {

				$only_single = apply_filters( 'wp_featured_video_singular_only', true );

				if( $only_single ) {

					if( !is_singular() )
						return $value;

				}

				if( $this->has_featured_video( $object_id ) ) 
					$value = true;

			}

			return $value;

		}

		/**
		 * Replaces the post thumbnail with the video thumbnail
		 *
		 * @param string $html              The post thumbnail HTML.
		 * @param string $post_id           The post ID.
		 * @param string $post_thumbnail_id The post thumbnail ID.
		 * @param string $size              The post thumbnail size.
		 * @param string $attr              Query string of attributes.
		 * @return string The new featured image
		 **/
		public function replace_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {

			$only_single = apply_filters( 'wp_featured_video_singular_only', true );

			if( $only_single ) {

				if( !is_singular() )
					return $html;

			}

			if( $this->has_featured_video( $post_id ) ) {

				if( $size == 'post-thumbnail' )
					$size = 'thumbnail';

				$size_array = $this->get_image_sizes( $size );

				$width = $size_array['width'];
				$height = $size_array['height'];
				$crop = $size_array['crop'];

				$height = round( ( $width / 16 ) * 9 ); // always keeps the video at a 16:9 aspect ratio, no matter what

				$ratio = $width / $height;

				$video = $this->get_video_id( $post_id );
				$video_type = $this->get_video_type( $post_id );

				$attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $post_thumbnail_id );

				if( !isset( $attr['class'] ) )
					$attr['class'] = '';

				$attr['class'] .= ' featured-video featured-video-type-'. $video_type .' featured-video-'. ( $crop ? 'crop' : 'normal' );
				
				$attr['id'] = 'featured-video-'. $post_id;
				
				$attr['style'] = 'width:'.$width.'px;';
				
				$attrs = '';
				foreach ( $attr as $name => $value ) {
					$attrs .= " $name=" . '"' . $value . '"';
				}

				$html = '<div' . $attrs .'>';

				if( $video_type == 'youtube' ) {

					$youtube_query = apply_filters( 'wp_featured_video_youtube_query', array(
						'autoplay' => 0,
						'origin' => get_permalink( $post_id ),
						'controls' => 2,
						'showinfo' => 0
					) );

					$youtube_query = http_build_query( $youtube_query );

					$html .= '<iframe class="featured-video-iframe" type="text/html" width="'.$width.'" height="'.$height.'"';
  					$html .= 'src="http://www.youtube.com/embed/'.$video.'?'.$youtube_query.'"';
  					$html .= 'frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';

				}
				elseif( $video_type == 'vimeo' ) {

					$vimeo_query = apply_filters( 'wp_featured_video_vimeo_query', array(
						'autoplay' => 0,
						'byline' => 0,
						'portrait' => 0,
						'title' => 0,
						'badge' => 0
					) );

					$vimeo_query = http_build_query( $vimeo_query );

					$html .= '<iframe class="featured-video-iframe" width="'.$width.'" height="'.$height.'"';
					$html .= 'src="//player.vimeo.com/video/'.$video.'?'.$vimeo_query.'"';
					$html .= 'frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';

				}

				$html .= '</div>';

			}

			return $html;

		}

		/**
		 * Gets image sizes
		 *
		 * @param string $size (optional) A single size
		 * @return array An array of sizes or just the one requested size
		 **/
		private function get_image_sizes( $size = '' ) {

	        global $_wp_additional_image_sizes;

	        $sizes = array();
	        $get_intermediate_image_sizes = get_intermediate_image_sizes();

	        // Create the full array with sizes and crop info
	        foreach( $get_intermediate_image_sizes as $_size ) {

	                if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {

	                        $sizes[ $_size ]['width'] = get_option( $_size . '_size_w' );
	                        $sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
	                        $sizes[ $_size ]['crop'] = (bool) get_option( $_size . '_crop' );

	                } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

	                        $sizes[ $_size ] = array( 
	                                'width' => $_wp_additional_image_sizes[ $_size ]['width'],
	                                'height' => $_wp_additional_image_sizes[ $_size ]['height'],
	                                'crop' =>  $_wp_additional_image_sizes[ $_size ]['crop']
	                        );

	                }

	        }

	        // Get only 1 size if found
	        if ( $size ) {

	                if( isset( $sizes[ $size ] ) ) {
	                        return $sizes[ $size ];
	                } else {
	                        return false;
	                }

	        }

	        return $sizes;
		}

		/**
		 * Saves the data
		 *
		 * @param int $post_id ID of the saved post
		 * @return void
		 **/
		public function save($post_id) {

			if( $_POST ) {

				if( isset( $_POST['featured_video_meta'] ) && wp_verify_nonce( $_POST['featured_video_meta'], 'save_featured_video' ) ) {

					if( isset( $_POST['_featured_video_url'] ) )
						update_post_meta( $post_id, '_featured_video_url', $_POST['_featured_video_url'] );

				}

			}

		}

		/**
		 * Registers and enqueus scripts needed for the featured video feature to work
		 *
		 * @return void
		 **/
		public function scripts() {

			if( get_current_screen()->base !== 'post' )
				return;

			wp_enqueue_script( 'jquery' );

			wp_enqueue_script( 'wp-featured-video-js', plugins_url( 'js/featured.video.js', __FILE__ ), array( 'jquery' ), '1.0' );
			wp_localize_script( 'wp-featured-video-js', 'Featured_Video', array( 'SetVideo' => __( 'Set featured video', 'featured-video' ), 'RemoveVideo' => __( 'Remove featured video', 'featured-video' ) ) );

			wp_enqueue_style( 'wp-featured-video-css', plugins_url( 'css/featured.video.css', __FILE__ ), array(), '1.0' );

		}

		/**
		 * Creates a new instance of the plugin
		 *
		 * @return object An instance of our plugin
		 **/
		public function instance() {

			return new self();

		}

		/**
		 * Add a new link to the featured image box.
		 *
		 * @param string $html The HTML for the featured image box
		 * @param int $post_id The current post ID
		 * @return void
		 **/
		public function add_button($html, $post_id) {

			$html .= '<hr>';

			$html .= '<div class="featured-video-metabox-container">';

			$html .= '<div class="featured_video" id="featured_video">';
			if( $this->has_featured_video( $post_id ) ) {

				$html .= '<img src="'. $this->get_video_thumbnail( $post_id ) .'">';

			}
			$html .= '</div>';

			$html .= '<div id="thumbnail-change-toggle">';

			if( ! $this->has_featured_video( $post_id ) )
				$html .= '<p class="hide-if-no-js"><a href="#" id="set-featured-video">'. __( 'Set featured video', 'featured-video' ) .'</a></p>';
			else
				$html .= '<p class="hide-if-no-js"><a href="#" id="remove-featured-video">'. __( 'Remove featured video', 'featured-video' ) .'</a></p>';

			$html .= '</div>';

			$html .= '<input type="hidden" value="'. $this->get_video_url( $post_id ) .'" name="_featured_video_url" id="_featured_video_url">';

			$html .= '</div>';

			$html .= wp_nonce_field( 'save_featured_video', 'featured_video_meta', null, false );

			return $html;

		}

		/**
		 * Gets the URL for a video
		 *
		 * @param integer $post_id The post ID
		 * @return string Video URL
		 **/
		private function get_video_url($post_id) {

			$video = get_post_meta( $post_id, '_featured_video_url', true );

			return $video;

		}

		/**
		 * Gets the ID for a video
		 *
		 * @param integer $post_id The post ID
		 * @return string Video ID
		 **/
		private function get_video_id($post_id) {

			$video = $this->get_video_url($post_id);

			if( empty($video) || !$video ) {
				return false;
			}

			$data = $this->get_video_data_parsed($video);

			return $data['id'];
		}

		/**
		 * Gets the type for a video
		 *
		 * @param integer $post_id The post ID
		 * @return string Video tyoe
		 **/
		private function get_video_type($post_id) {

			$video = $this->get_video_url($post_id);

			if( empty($video) || !$video ) {
				return false;
			}

			$data = $this->get_video_data_parsed($video);

			return $data['type'];
		}

		/**
		 * Gets the thumbnail for a video
		 *
		 * @param integer $post_id The post ID
		 * @return string Video thumbnail source
		 **/
		private function get_video_thumbnail($post_id) {

			$video = $this->get_video_url($post_id);

			if( empty($video) || !$video ) {
				return false;
			}

			$data = $this->get_video_data_parsed($video);

			return $data['thumbnail'];

		}

		/**
		 * Checks if a post has featured video.
		 *
		 * @param int $post_id The current post ID
		 * @return boolean
		 **/
		private function has_featured_video($post_id) {

			$video = get_post_meta( $post_id, '_featured_video_url', true );

			if( empty($video) || !$video ) {
				return false;
			}

			return true;

		}

		/**
		 * Gets the parsed data from the URL
		 *
		 * @param string $url URL to a YouTube or Vimeo video
		 * @return array Array with the parsed data for the requested URL
		 **/

		private function get_video_data_parsed( $url ) {

			$data = get_transient( 'wp_featured_video_data_'. sanitize_title( $url ) );
			$data = false;
			if( $data === false ) {

				$video_data = $this->get_video_data( $url );

				if( $video_data ) {

					$data = $video_data['data'];

					if( $video_data['type'] == 'youtube' ) {

						$title = array_pop($data['title']); // the title
						$duration = array_pop($data['media$group']['media$content']); // the duration of the video
						$duration = $duration['duration']; // the duration in seconds
						$thumbnails = $data['media$group']['media$thumbnail'];

						foreach($thumbnails as $thumbnail) {
							if( $thumbnail['yt$name'] == 'mqdefault' ) {
								$thumb = $thumbnail['url'];
							}
						}
					}
					elseif( $video_data['type'] == 'vimeo' ) {

						$title = $data['title'];
						$duration = $data['duration'];
						$thumb = $data['thumbnail_large'];

					}

					$data = array( 
						'id' => $video_data['id'], 
						'title' => $title, 
						'duration' => $duration, 
						'thumbnail' => $thumb,
						'type' => $video_data['type']
					);

					set_transient( 'wp_featured_video_data_'. sanitize_title( $url ), $data );

				}
			}

			return $data;

		}

		/**
		 * Gets the data from the video URL
		 *
		 * @param string $url URL to a YouTube or Vimeo video
		 * @return array Array with the data for the requested URL
		 **/
		private function get_video_data( $url ) {

			$youtube = 'https://gdata.youtube.com/feeds/api/videos/%s?v=2&alt=json';
			$vimeo = 'http://vimeo.com/api/v2/video/%s.json';

			if( strpos( $url, 'youtube.com' ) !== false || stripos( $url, 'youtu.be' ) !== false ) 
				$service = 'youtube';

			elseif( strpos( $url, 'vimeo.com' ) !== false ) 
				$service = 'vimeo';

			else
				return false;

			if( $service == 'youtube' ) {

				parse_str( parse_url( $url, PHP_URL_QUERY ), $query );

				if( !isset( $query['v'] ) )
					return false;

				$data_url = sprintf( $youtube, $query['v'] );

				$data = @json_decode( @file_get_contents( $data_url ), true );

				if( !isset($data['entry']) )
					return false;

				$return = array( 'data' => $data['entry'], 'id' => $query['v'], 'type' => 'youtube' );

			}

			if( $service == 'vimeo' ) {

				$id = substr( parse_url( $url, PHP_URL_PATH ), 1 );
				if( ! is_numeric( $id ) ) 
					return false;

				$data_url = sprintf( $vimeo, $id );

				$data = @json_decode( @file_get_contents( $data_url ), true );

				$return = array( 'data' => $data[0], 'id' => $id, 'type' => 'vimeo' );

			}

			return $return;

		}

		/**
		 * Render a modal container for inserting videos
		 *
		 * @return void
		 **/
		public function render_modal_container() {

			echo '<div id="featured-video-modal-container"></div>';

		}

		/**
		 * Render a modal for inserting videos
		 *
		 * @return void
		 **/
		public function render_modal() {

			?>
			
			<div tabindex="0" id="_featured_video_modal" class="featured-video-modal" style="position: relative; display:none;">
				<div class="media-modal wp-core-ui">
					<a class="media-modal-close" href="#" title="<?php _e( 'Close' ); ?>"><span class="media-modal-icon"></span></a>
					<div class="media-modal-content">
						<div class="media-frame wp-core-ui hide-menu" id="_featured_video_modal_0">
							<div class="media-frame-menu">
								<div class="media-menu">
									<a href="#" class="media-menu-item active"><?php _e( 'Set Featured Video', 'featured-video' ); ?></a>
								</div>
							</div>
							<div class="media-frame-title">
								<h1><?php _e( 'Set Featured Video', 'featured-video' ); ?></h1>
							</div>
							<div class="media-frame-content">

								<div class="attachments-browser">
									
									<div class="attachments" style="top: 0; left: 16px;" id="insert-video-url">
										
										<h2><?php _e( 'Insert URL to a video', 'featured-video' ); ?></h2>	

										<input type="text" size="40" id="_featured_video">

										<button id="_get_video_data" class="button-primary"><?php _e( 'Get Video Information', 'featured-video' ); ?></button>
										
										<div class="video-data">
											<?php 

											global $post;

											if( isset($post->ID) ) {

												if( $this->has_featured_video( $post->ID ) ) :

													$video = $this->get_video_url( $post->ID );

													$this->ajax_render_video_data( false, $video );

												endif;
											}

											?>
										</div>
									</div>
									
									<div class="media-sidebar">
										<p>
											<?php _e( 'You can type any Vimeo or YouTube URL to get the metadata for the video and insert as a featured video.', 'featured-video' ); ?>
										</p>
									</div>

								</div>

							</div>
				
						</div>
					</div>
				</div>
				<div class="media-modal-backdrop"></div>
			</div>

			<?php

			die;

		}

		/**
		 * Renders video data 
		 *
		 * @param int $ajax (optional) If this is an ajax request or not
		 * @param string $url (optional) The URL to fetch
		 * @return void Echoes video data
		 **/
		public function ajax_render_video_data( $ajax = true, $url = null ) {

			if( $ajax || $ajax == '' )
				$url = $_POST['url'];

			$data = $this->get_video_data_parsed( $url );

			if( $data ) {

				$title = $data['title']; // the title
				$duration = $data['duration']; // the duration in seconds
				$thumb = $data['thumbnail'];

				?>
				<div class="video-data-item" data-video="<?php echo $url; ?>" data-thumb="<?php echo $thumb; ?>">

					<div class="video-thumbnail">
						<img src="<?php echo $thumb; ?>" alt="">
					</div>

					<div class="video-information">

						<h3><?php echo $title; ?></h3>
						<h4><?php echo $this->duration($duration); ?></h4>
						<div class="video-type"><?php echo $data['type']; ?></div>

						<button class="button-primary" id="insert-video"><?php _e( 'Set this video as featured video', 'featured-video' ); ?></button>
					
					</div>
				</div>
				<?php

			}
			else {

				_e( 'This is not a valid video URL. Please try another URL.', 'featured-video' );

			}

			if( $ajax || $ajax == '' )
				die();

		}

		/**
		 * Formats seconds as video duration
		 *
		 * @param int $seconds_count The seconds to format
		 * @return string Video duration
		 * @author aktagon <http://aktagon.com>
		 **/
		private function duration($seconds_count) {
			$delimiter  = ':';
			$seconds = $seconds_count % 60;
			$minutes = floor($seconds_count/60);
			$hours   = floor($seconds_count/3600);

			$seconds = str_pad($seconds, 2, "0", STR_PAD_LEFT);
			$minutes = str_pad($minutes, 2, "0", STR_PAD_LEFT).$delimiter;

			if($hours > 0)
			{
				$hours = str_pad($hours, 2, "0", STR_PAD_LEFT).$delimiter;
			}
			else
			{
				$hours = '';
			}

			return "$hours$minutes$seconds";
		}

	}

	/**
	 * Includes the template tags
	 **/
	require_once plugin_dir_path( __FILE__ ) . '/lib/template.php';

	/**
	 * Load the plugin into action. Since it really never gets called from anywhere else, this is the only place we'll need it.
	 **/
	add_action( 'plugins_loaded', array( 'WP_Featured_Video', 'instance' ) );

}

?>