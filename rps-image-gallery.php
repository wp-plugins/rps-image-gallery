<?php
/*
Plugin Name: RPS Image Gallery
Plugin URI: http://redpixel.com/
Description: An image gallery with caption support and ability to link to a slideshow or alternate URL. 
Version: 1.1.1
Author: Red Pixel Studios
Author URI: http://redpixel.com/
License: GPL3
*/

/* 	Copyright (C) 2011  Red Pixel Studios  (email : support@redpixel.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful, HI hi
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * An image gallery with caption support and ability to link to a slideshow or alternate URL.
 *
 * @package rps-image-gallery
 * @author Red Pixel Studios
 * @version 1.1.1
 */

/**
 * @todo Documentation.
 */
if ( ! class_exists( 'RPS_Image_Gallery', false ) ) :

class RPS_Image_Gallery {

	public function __construct() {
		add_action( 'init', array( &$this, 'cb_init' ) );
		add_action( 'wp_footer', array( &$this, 'cb_footer_styles_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'cb_enqueue_styles_scripts' ) );
		
		add_filter( 'attachment_fields_to_edit', array( &$this, 'f_media_edit_gallery_link' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( &$this, 'f_media_save_gallery_link' ), 10, 2 );
	}

	/* Add the gallery_link field to the page for editing */
	public function f_media_edit_gallery_link( $fields, $post ) {
		if ( stristr( $post->post_mime_type, 'image' ) === false ) return $fields;
		
		$fields['post_gallery_link'] = array(
			'label' => 'Gallery Link URL',
			'value' => esc_attr( get_post_meta( $post->ID, '_rps_attachment_post_gallery_link', true ) ),
			'input' => 'text',
			'helps' => 'Enter a relative or absolute link that should be followed when the image is clicked<br />from within an image gallery.'
		);
	
		return $fields;
	}

	/* Save the gallery_link field */
	public function f_media_save_gallery_link( $post, $fields ) {
		if ( !isset( $fields['post_gallery_link'] ) ) return $post;

		$safe_url = trim( $fields['post_gallery_link'] );
		if ( empty( $safe_url ) ) {
			if ( get_post_meta( $post['ID'], '_rps_attachment_post_gallery_link', true ) ) {
				delete_post_meta( $post['ID'], '_rps_attachment_post_gallery_link' );
			}
			return $post;
		}
		
		$safe_url = esc_url( $safe_url );
		if ( empty( $safe_url ) ) return $post;
		
		update_post_meta( $post['ID'], '_rps_attachment_post_gallery_link', $safe_url );
		
		return $post;
	}

	/* Return the gallery_link field */
	public function get_gallery_link( $attachment_id ) {
		return get_post_meta( $attachment_id, '_rps_attachment_post_gallery_link', true );
	}

	public function cb_init() {
		add_shortcode( 'rps-image-gallery', array( &$this, 'cb_gallery_shortcode' ) );
		add_shortcode( 'gallery', array( &$this, 'cb_gallery_shortcode' ) );
		
		wp_register_style( 'rps-image-gallery-fancybox', plugins_url( 'dependencies/fancybox/jquery.fancybox-1.3.4.css', __FILE__ ), false, '1.0.0' );
		wp_register_style( 'rps-image-gallery', plugins_url( 'rps-image-gallery.css', __FILE__ ), array( 'rps-image-gallery-fancybox' ), '1.0.0' );
		
		wp_register_script( 'rps-image-gallery-easing', plugins_url( 'dependencies/fancybox/jquery.easing-1.3.pack.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
		wp_register_script( 'rps-image-gallery-fancybox', plugins_url( 'dependencies/fancybox/jquery.fancybox-1.3.4.pack.js', __FILE__ ), array( 'rps-image-gallery-easing' ), '1.0.0', true );
		wp_register_script( 'rps-image-gallery', plugins_url( 'rps-image-gallery.js', __FILE__ ), array( 'rps-image-gallery-fancybox' ), '1.0.2', true );
	}
	
	public function cb_enqueue_styles_scripts() {
		wp_enqueue_style( 'rps-image-gallery' );
	}
	
	public function cb_gallery_shortcode( $atts, $content = null ) {
		$str_output = '';

		// specify allowed values for shortcode attributes
		$allowed_columns_min = 1;
		$allowed_columns_max = 9;
		
		$allowed_orderby = array( 
			'menu_order',
			'title',
			'post_date',
			'random'
		);
		
		$allowed_order = array(
			'asc',
			'desc'
		);
		
		$allowed_itemtag = array(
			'ul',
			'dl'
		);
		
		$allowed_icontag = array(
			'li',
			'dt'
		);
		
		$allowed_captiontag = array(
			'span',
			'dd'
		);
		
		$allowed_link = array(
			'permalink',
			'file'
		);
		
		$allowed_container = array(
			'div',
			'p',
			'span'
		);

		// specify defaults for shortcode attributes
		$defaults = array(
			'columns' => '3',
			'id' => get_the_id(),
			'size' => 'thumbnail',
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'itemtag' => 'ul', // dl = itemtag default
			'icontag' => 'li', // dt = icontag default
			'captiontag' => 'span', // dd = captiontag default
			'link' => 'permalink',
			'size_large' => 'large',
			'group_name' => 'rps-image-group',
			'container' => 'div',
			'caption' => 'false', // false = default
			'slideshow' => 'true', // false causes link within 'description' or 'gallery link' to be used
			'include' => '',
			'exclude' => ''
		);
		
		extract( shortcode_atts( $defaults, $atts ), EXTR_SKIP );
		
		// convert string values to lowercase and trim
		$size = trim( strtolower( $size ) );
		$orderby = trim( strtolower( $orderby ) );
		$order = trim( strtolower( $order ) );
		$itemtag = trim( strtolower( $itemtag ) );
		$icontag = trim( strtolower( $icontag ) );
		$captiontag = trim( strtolower( $captiontag ) );
		$link = trim( strtolower( $link ) );
		$size_large = trim( strtolower( $size_large ) );
		$group_name = trim( strtolower( $group_name ) );
		$container = trim( strtolower( $container ) );
		$caption = trim( strtolower( $caption ) );
		$slideshow = trim( strtolower( $slideshow ) );
		
		// type cast strings as necessary
		$id = absint( $id );
		$columns = absint( $columns );
		$caption = ( $caption == 'true' ) ? true : false;
		$slideshow = ( $slideshow == 'true' ) ? true : false;

		// test for allowed values
		$columns = max( $allowed_columns_min, min( $allowed_columns_max, $columns ) );
		if ( !in_array( $orderby, $allowed_orderby ) ) $orderby = $defaults['orderby'];
		if ( !in_array( $order, $allowed_order ) ) $order = $defaults['order'];
		if ( !in_array( $itemtag, $allowed_itemtag ) ) $itemtag = $defaults['itemtag'];
		if ( !in_array( $icontag, $allowed_icontag ) ) $icontag = $defaults['icontag'];
		if ( !in_array( $captiontag, $allowed_captiontag ) ) $captiontag = $defaults['captiontag'];
		if ( !in_array( $link, $allowed_link ) ) $captiontag = $defaults['link'];
		if ( !in_array( $container, $allowed_container ) ) $captiontag = $defaults['container'];
		
		// Safely parse include and exclude for use with get_posts().
		$include = trim( $include );
		$exclude = trim( $exclude );
		
		$include_arr = ( ! empty( $include ) ) ? explode( ',', $include ) : array();
		$exclude_arr = ( ! empty( $exclude ) ) ? explode( ',', $exclude ) : array();
		
		$include_arr = array_map( 'trim', $include_arr );
		$include_arr = array_map( 'absint', $include_arr );
		
		$exclude_arr = array_map( 'trim', $exclude_arr );
		$exclude_arr = array_map( 'absint', $exclude_arr );
		
		// You can't use include and exclude at the same time, so we'll let exclude trump include.
		if ( ! empty( $exclude_arr ) && ! empty( $include_arr ) ) $include_arr = array();
		
		// determine if fancybox should be loaded if the user wants a slideshow
		if ( $slideshow ) {
			$this->print_scripts = true;
			$str_output .= '<script type="text/javascript">var rps_img_gallery_rel = "' . $group_name . '";</script>';
		}
		 
		$attachments = get_posts( array(
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => $id,
			'order' => $order,
			'orderby' => $orderby,
			'include' => $include_arr,
			'exclude' => $exclude_arr
		) );
		
		if ( empty( $attachments ) ) return '';
		
		$i = 0;
		$quantity = count( $attachments );
		$str_aux = '';
		$str_output .= '<' . $container . ' class="rps-image-gallery gallery-columns-' . $columns . ' gallery-size-' . $size . '">';
		
		if ( $itemtag == 'ul' ) $str_output .= '<' . $itemtag . '>';
		
		foreach ( $attachments as $attachment ) {
			$i++;
			$str_href = '';
			$str_rel = '';
			$str_title = strip_tags( $attachment->post_title );
			$str_alt_text = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
			$str_caption = strip_tags( $attachment->post_excerpt );
			$str_description = $attachment->post_content;
			$bool_description_not_link = true;
			$arr_image_small = wp_get_attachment_image_src( $attachment->ID, $size );
			$arr_image_large = wp_get_attachment_image_src( $attachment->ID, $size_large );
			
			// fall back to using the title if the alt text is not provided for accessibility requirements
			if ( $str_alt_text == '' ) $str_alt_text = $str_title;
			
			// flag the last image with a class for the slideshow just in case the slideshow option is set to true
			if ( $i == $quantity ) $str_class = ' class="last"';
			
			// the gallery-item class is default for WordPress so we preserve it
			if ( $itemtag == 'dl' ) $str_output .= '<' . $itemtag . ' class="gallery-item">';
			
			// the gallery-icon class is default for WordPress so we preserve it
			$str_output .= '<' . $icontag . ' class="gallery-icon">';
			
			// determine if the description field begins with http(s):// or / and assume that it is a url
			// this check will not be necessary as users move away from adding links in the description field
			if ( preg_match( '/^\/|https?/', $str_description ) ) :
				$bool_description_not_link = false;
				$str_href = $str_description;
			elseif ( $this->get_gallery_link( $attachment->ID ) != '' ) :
				$str_href = $this->get_gallery_link( $attachment->ID );
			endif;
						
			// check to see if the slideshow shortcode attribute is true and the href is empty
			// if so link to the larger version of the image and group it with the other images
			// this in effect makes the image part of the slideshow through the rel tag
			if ( $slideshow and $str_href == '' ) :

				$str_href = $arr_image_large[0];
				$str_rel = ' rel="' . $group_name . '"';

			// if not check to see if the href is set and if not then use the gallery setting
			// "Link thumbnails to" setting. A value of file link to a bigger version of the image
			// whereas any other value links to the attachment page
			else:
				if ( $str_href == '' ) :
					if($link == 'file'):
						$str_href = $arr_image_large[0];
					else:
						$str_href = get_attachment_link($attachment->ID);
					endif;
				endif;
			endif;

			// determine what strings need to be used for the title attribute of the HREF and the caption
			// each image has the possiblity of having a Title, Alternate Text, Caption, Description
			// the attachment title is already defined as the $str_title and is replaced if any of the
			// other values are populated in order of preference Caption, Description then Alt Text
			if ( $str_caption != '' ) :
				$str_title = $str_caption;
			elseif ( $str_description != '' && $bool_description_not_link ) :
				$str_title = $str_description;
			elseif ( $str_alt_text != '' ) :
				$str_title = $str_alt_text;
			endif;

			
			$str_output .= '<a' . $str_rel . ' href="' . $str_href . '" title="' . $str_title . '"><img' . $str_class . ' alt="' . $str_alt_text . '" src="' . $arr_image_small[0] . '" /></a>';
			
			
			// define the gallery caption tag if caption is set to true
			// the wp-caption-text and gallery-caption classes are default for WordPress
			if ( $caption != false ) $gallery_caption = '<' . $captiontag . ' class="wp-caption-text gallery-caption">' . $str_caption . '</' . $captiontag . '>';
			
			// check to see if ul or dl is being used and output the caption in the correct order
			// since ul and dl lists are structured differently
			if( $itemtag == 'ul' ):
				$str_output .= $gallery_caption . '</' . $icontag . '>';
			elseif( $itemtag == 'dl' ):
				$str_output .= '</' . $icontag . '>' . $gallery_caption;
			else:
				$str_output .= '</' . $icontag . '>';
			endif;
			
			// if a dl is being used, such as is the default for the WordPress gallery,
			// check to see if a new row is needed based on the number of columns specified by the user
			if( $itemtag == 'dl' ):
				$str_output .= '</' . $itemtag . '>';
				if( $i % $columns == 0 && $i != $quantity ):
					$str_output .= '<br style="clear: both;" />';
				endif;
			endif;
		}
		
		if ( $itemtag == 'ul' ) $str_output .= '</' . $itemtag . '>';
		
		$str_output .= '</' . $container . '>';
		
		return $str_output;
	}
	
	public function cb_footer_styles_scripts () {
		if ( ! $this->print_scripts ) return;
		
		//wp_print_styles( 'rps-image-gallery' );
		wp_print_scripts( 'rps-image-gallery' );
	}
	
	private $print_scripts = false;
}

if ( ! isset( $rps_image_gallery ) ) $rps_image_gallery = new RPS_Image_Gallery;

endif;

?>