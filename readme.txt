=== WP Featured Video ===
Contributors: tormorten
Donate link: http://tormorten.no/
Tags: featured image, featured video, thumbnail, video thumbnail, video image, video, thumb, vimeo, youtube
Requires at least: 3.8
Tested up to: 3.9.2
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add a YouTube or Vimeo video as the featured image for your post or page.

== Description ==

A really simple and awesome way to add a video as your featured image.

It automatically recognizes URLs to Vimeo or YouTube and overrides the default WordPress featured image on singular posts. 

Since it hooks in to already existing WordPress functions it will work out of the box with most themes that use the `the_post_thumbnail()` or `get_the_post_thumbnail()` function.

Will work perfectly with, for instance, Twenty Fourteen. No configuration, no hassle.

For developers there are even functions to integrate it in to your theme. The `the_post_video_thumbnail()` and `get_the_post_video_thumbnail()` functions work just like WordPress' own featured image functions.

The plugins keeps the video at a nice 16:9 ratio (which is the same ratio as YouTube's own player), and even works for fluid/responsive themes.

To make access easy, the featured video administration is located in the same metabox as the featured image. It even looks the same. How neat, right?

By default, the featured video feature is availiable for singular pages only, but this can be overriden by applying `false` to the `wp_featured_video_singular_only` filter.

== Installation ==


1. Upload `wp-featured-video` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Select your featured video in a post, page or any custom post type that support featured images.

== Frequently Asked Questions ==

= Must do this soon =

Any questions would have to go in to the support forums for now.

== Screenshots ==

1. The "Featured Image"-metabox after the plugin has been installed.
2. The modal after clicking "Set featured image".
3. The modal after inserting a video and data has been fetched.
4. The "Featured Image"-metabox after a video has been selected.
5. The outcome after selecting a video as featured image.

== Changelog ==

= 1.0 =
* The plugin has been released