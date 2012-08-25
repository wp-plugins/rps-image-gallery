<?php
/*
Plugin Name: RPS Image Gallery
Plugin URI: http://redpixel.com/rps-image-gallery-plugin
Description: An image gallery with caption support and ability to link to a slideshow or alternate URL. 
Version: 1.2.6
Author: Red Pixel Studios
Author URI: http://redpixel.com/
License: GPL3
*/

/* 	Copyright (C) 2011 - 2012  Red Pixel Studios  (email : support@redpixel.com)

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
 * @version 1.2.6
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
		
		add_filter( 'attachment_fields_to_edit', array( &$this, 'f_media_edit_gallery_link_target' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( &$this, 'f_media_save_gallery_link_target' ), 10, 2 );
	}

	/*
	 * Add the gallery_link field to the page for editing.
	 *
	 * @since version 1.2
	 */
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

	/*
	 * Add the gallery_link_target field to the page for editing
	 *
	 * @since version 1.2.6
	 */
	public function f_media_edit_gallery_link_target( $fields, $post ) {
		if ( stristr( $post->post_mime_type, 'image' ) === false ) return $fields;
		
		$target = get_post_meta( $post->ID, '_rps_attachment_post_gallery_link_target', true );
		$options_inner_html = '';
		
		$options = array(
			'_self',
			'_blank',
			'_parent',
			'_top'
		);
		
		foreach ( $options as $option ) :
			$selected = ( $target == $option ) ? 'selected="selected"' : '';
			$default = ( $option == '_self' ) ? ' (default)' : '';
			$options_inner_html .= '<option value="' . $option . '"' . $selected . '>' . $option . $default . '</option>';
		endforeach;
		
		$fields['post_gallery_link_target'] = array(
			'label' => 'Gallery Link Target',
			'value' => $target,
			'input' => 'html',
			'html' => '<select name="attachments[' . $post->ID . '][post_gallery_link_target]" id="attachments[' . $post->ID . '][post_gallery_link_target]">' . $options_inner_html . '</select>',
			'helps' => 'Select the target for the Gallery Link URL.'
		);
	
		return $fields;
	}
	
	/*
	 * Save the gallery_link field.
	 *
	 * @since version 1.2
	 */
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

	/*
	 * Save the gallery_link_target field.
	 *
	 * @since version 1.2.6
	 */
	public function f_media_save_gallery_link_target( $post, $fields ) {
		if ( !isset( $fields['post_gallery_link_target'] ) ) return $post;
		
		if ( empty( $fields['post_gallery_link_target'] ) ) {
			if ( get_post_meta( $post['ID'], '_rps_attachment_post_gallery_link_target', true ) ) {
				delete_post_meta( $post['ID'], '_rps_attachment_post_gallery_link_target' );
			}
			return $post;
		}
		
		update_post_meta( $post['ID'], '_rps_attachment_post_gallery_link_target', $fields['post_gallery_link_target'] );
		
		return $post;
	}

	/*
	 * Return the gallery_link field.
	 *
	 * @since version 1.2
	 */
	public function get_gallery_link( $attachment_id ) {
		return get_post_meta( $attachment_id, '_rps_attachment_post_gallery_link', true );
	}

	/*
	 * Return the gallery_link_target field.
	 *
	 * @since version 1.2
	 */
	public function get_gallery_link_target( $attachment_id ) {
		return get_post_meta( $attachment_id, '_rps_attachment_post_gallery_link_target', true );
	}

	public function cb_init() {
		add_shortcode( 'rps-image-gallery', array( &$this, 'cb_gallery_shortcode' ) );
		add_shortcode( 'gallery', array( &$this, 'cb_gallery_shortcode' ) );
		
		wp_register_style( 'rps-image-gallery-fancybox', plugins_url( 'dependencies/fancybox/jquery.fancybox-1.3.4.css', __FILE__ ), false, '1.0.0' );
		wp_register_style( 'rps-image-gallery', plugins_url( 'rps-image-gallery.css', __FILE__ ), array( 'rps-image-gallery-fancybox' ), '1.0.0' );
		
		wp_register_script( 'rps-image-gallery-easing', plugins_url( 'dependencies/fancybox/jquery.easing-1.3.pack.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
		wp_register_script( 'rps-image-gallery-fancybox', plugins_url( 'dependencies/fancybox/jquery.fancybox-1.3.4.pack.js', __FILE__ ), array( 'rps-image-gallery-easing' ), '1.0.0', true );
	}
	
	public function cb_enqueue_styles_scripts() {
		wp_enqueue_style( 'rps-image-gallery' );
	}
	
	public function cb_gallery_shortcode( $atts, $content = null ) {
		$str_output = '';

		/*
		 * Specify allowed values for shortcode attributes.
		 */
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
			'file',
			'none'
		);
		
		$allowed_container = array(
			'div',
			'p',
			'span'
		);
		
		/*
		 * Allowed values for fancybox shortcode attributes.
		 */
		$allowed_fb_transition_in = array(
			'elastic',
			'fade',
			'none'
		);
		
		$allowed_fb_transition_out = array(
			'elastic',
			'fade',
			'none'
		);
		
		$allowed_fb_title_position = array(
			'outside',
			'inside',
			'over'
		);
		
		$allowed_fb_speed_in_min = 100;
		$allowed_fb_speed_in_max = 1000;
		
		$allowed_fb_speed_out_min = 100;
		$allowed_fb_speed_out_max = 1000;

		/*
		 * Specify defaults for shortcode attributes.
		 */
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
			'exclude' => '',
			
			// fancybox attributes
			'fb_title_show' => 'true',
			'fb_transition_in' => 'none',
			'fb_transition_out' => 'none',
			'fb_title_position' => 'over',
			'fb_speed_in' => 300,
			'fb_speed_out' => 300,
			'fb_show_close_button' => 'true'
		);
		
		$shortcode_atts = shortcode_atts( $defaults, $atts );
		extract( $shortcode_atts, EXTR_SKIP );
		
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
		$fb_title_show = trim( strtolower( $fb_title_show ) );
		$fb_transition_in = trim( strtolower( $fb_transition_in ) );
		$fb_transition_out = trim( strtolower( $fb_transition_out ) );
		$fb_title_position = trim( strtolower( $fb_title_position ) );
		$fb_show_close_button = trim( strtolower( $fb_show_close_button ) );
		
		// type cast strings as necessary
		$id = absint( $id );
		$columns = absint( $columns );
		$caption = ( $caption == 'true' ) ? true : false;
		$slideshow = ( $slideshow == 'true' ) ? true : false;
		$fb_title_show = ( $fb_title_show == 'true' ) ? true : false;
		$fb_speed_in = absint( $fb_speed_in );
		$fb_speed_out = absint( $fb_speed_out );
		$fb_show_close_button = ( $fb_show_close_button == 'true' ) ? true : false;

		// test for allowed values
		$columns = max( $allowed_columns_min, min( $allowed_columns_max, $columns ) );
		if ( !in_array( $orderby, $allowed_orderby ) ) $orderby = $defaults['orderby'];
		if ( !in_array( $order, $allowed_order ) ) $order = $defaults['order'];
		if ( !in_array( $itemtag, $allowed_itemtag ) ) $itemtag = $defaults['itemtag'];
		if ( !in_array( $icontag, $allowed_icontag ) ) $icontag = $defaults['icontag'];
		if ( !in_array( $captiontag, $allowed_captiontag ) ) $captiontag = $defaults['captiontag'];
		if ( !in_array( $link, $allowed_link ) ) $link = $defaults['link'];
		if ( !in_array( $container, $allowed_container ) ) $container = $defaults['container'];
		if ( !in_array( $fb_transition_in, $allowed_fb_transition_in ) ) $fb_transition_in = $defaults['fb_transition_in'];
		if ( !in_array( $fb_transition_out, $allowed_fb_transition_out ) ) $fb_transition_out = $defaults['fb_transition_out'];
		if ( !in_array( $fb_title_position, $allowed_fb_title_position ) ) $fb_title_position = $defaults['fb_title_position'];
		$fb_speed_in = max( $allowed_fb_speed_in_min, min( $allowed_fb_speed_in_max, $fb_speed_in ) );
		$fb_speed_out = max( $allowed_fb_speed_out_min, min( $allowed_fb_speed_out_max, $fb_speed_out ) );
		
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
		
		/*
		 * Determine if fancybox should be loaded if the user wants a slideshow
		 * if so, store shortcode information for later use when outputting dynamic javascript
		 */
		if ( $slideshow ) {
			$this->slideshows[] = compact(
				'group_name',
				'fb_title_show',
				'fb_transition_in',
				'fb_transition_out',
				'fb_title_position',
				'fb_speed_in',
				'fb_speed_out',
				'fb_show_close_button'
			);
		}
		
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
			$str_caption = $attachment->post_excerpt;
			$arr_image_small = wp_get_attachment_image_src( $attachment->ID, $size );
			$arr_image_large = wp_get_attachment_image_src( $attachment->ID, $size_large );
			$str_class = '';
			$gallery_caption = '';
			$str_target = get_post_meta( $attachment->ID, '_rps_attachment_post_gallery_link_target', true );
			
			/*
			 * Fall back to using the title if the alt text is not provided for accessibility requirements.
			 */
			if ( $str_alt_text == '' ) $str_alt_text = $str_title;
			
			/*
			 * Flag the last image with a class for the slideshow just in case the slideshow option is set to true.
			 */
			if ( $i == $quantity ) $str_class = ' class="last"';
			
			/*
			 * The gallery-item class is default for WordPress so we preserve it.
			 */
			if ( $itemtag == 'dl' ) $str_output .= '<' . $itemtag . ' class="gallery-item">';
			
			/*
			 * The gallery-icon class is default for WordPress so we preserve it.
			 */
			$str_output .= '<' . $icontag . ' class="gallery-icon">';
			
			/*
			 * Get the value of the gallery link field of the attachment.
			 */
			if ( $this->get_gallery_link( $attachment->ID ) != '' ) $str_href = $this->get_gallery_link( $attachment->ID );
						
			/*
			 * Check if the slideshow shortcode attribute is true and the href is empty.
			 * If so, link to the larger version of the image and group it with the other images.
			 * This, in effect, makes the image part of the slideshow through the rel tag.
			 */
			if ( $slideshow and $str_href == '' ) :

				$str_href = $arr_image_large[0];
				$str_rel = ' rel="' . $group_name . '"';

			/* 
			 * If no slideshow, then check to see if the href is set and if not then use the "Link thumbnails to" setting.
			 * A value of "file" links to the full size version of the image, while a value of "permalink" links to the attachment template.
			 */
			else:
				if ( $str_href == '' ) :
					if($link == 'file'):
						$str_href = $arr_image_large[0];
					elseif ($link == 'permalink'):
						$str_href = get_attachment_link($attachment->ID);
					endif;
				else:
					/*
					 * If the gallery link is defined check and set the gallery link target.
					 * If the target is _self or empty then no need to output since it is the default behavior.
					 */
					$str_target = ( $str_target == '_self' or $str_target == '' ) ? '' : ' target="' . $str_target . '"';
				endif;
			endif;

			/*
			 * Determine what strings need to be used for the title attribute of the HREF and the caption.
			 * Each image has the possibility of having a Title, Alternate Text and Caption.
			 * The attachment title is already set to $str_title and is replaced if any of the
			 * other values are populated in order of precedence Caption, Alt Text then Title.
			 */
			if ( $str_caption != '' ) :
				$str_title = strip_tags( $str_caption );
			elseif ( $str_alt_text != '' ) :
				$str_title = $str_alt_text;
			endif;
			
			/* 
			 * If the slideshow is set to false, and the link value is set to none and the href is empty,
			 * just output the gallery image and don't link it to anything, otherwise output the image link.
			 */
			if ( !$slideshow && $link == 'none' && $str_href == '' ) :
				$str_output .= '<img' . $str_class . ' alt="' . $str_alt_text . '" src="' . $arr_image_small[0] . '" />';
			else :
				$str_output .= '<a' . $str_rel . ' href="' . $str_href . '" title="' . $str_title . '"' . $str_target . '><img' . $str_class . ' alt="' . $str_alt_text . '" src="' . $arr_image_small[0] . '" /></a>';
			endif;
			
			/*
			 * Define the gallery caption tag if caption is set to true.
			 * Note that the wp-caption-text and gallery-caption classes are default for WordPress.
			 */
			if ( $caption != false ) $gallery_caption = '<' . $captiontag . ' class="wp-caption-text gallery-caption">' . $str_caption . '</' . $captiontag . '>';
			
			/*
			 * Check to see if ul or dl is being used and output the caption in the correct order
			 * since ul and dl lists are structured differently.
			 */
			if( $itemtag == 'ul' ):
				$str_output .= $gallery_caption . '</' . $icontag . '>';
			elseif( $itemtag == 'dl' ):
				$str_output .= '</' . $icontag . '>' . $gallery_caption;
			else:
				$str_output .= '</' . $icontag . '>';
			endif;
			
			/*
			 * If a dl is being used, such as is the default for the WordPress gallery,
			 * check to see if a new row is needed based on the number of columns specified by the user.
			 */
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
	
	/*
	 * Output the necessary styles and scripts in the footer.
	 *
	 * @since version 1.2
	 */
	public function cb_footer_styles_scripts () {
		if ( empty( $this->slideshows ) ) return;
		wp_print_scripts( 'rps-image-gallery-fancybox' );
		
		?>
		<script type="text/javascript">
			;jQuery.noConflict();
	
			( function( $, window ) {
			var document = window.document;
			$( document ).ready( function() {
				<?php foreach ( $this->slideshows as $slideshow ) { ?>
				    $('a[rel="<?php echo $slideshow['group_name']; ?>"]').fancybox({
				    	'titleShow' : <?php echo ( $slideshow['fb_title_show'] ) ? 'true' : 'false'; ?>,
						'transitionIn' : '<?php echo $slideshow['fb_transition_in']; ?>',
						'transitionOut' : '<?php echo $slideshow['fb_transition_out']; ?>',
						'titlePosition' : '<?php echo $slideshow['fb_title_position']; ?>',
						'speedIn' : <?php echo $slideshow['fb_speed_in']; ?>,
						'speedOut' : <?php echo $slideshow['fb_speed_out']; ?>,
						'showCloseButton' : <?php echo ( $slideshow['fb_show_close_button'] ) ? 'true' : 'false'; ?>,
						// not editable via shortcode
						'titleFormat' : function(title, currentArray, currentIndex, currentOpts) {
							return '<span id="fancybox-title-over">Image ' + (currentIndex + 1) + ' / ' + currentArray.length + (title.length ? ' &nbsp; ' + title : '') + '</span>';
						},
						'cyclic' : true,
						'centerOnScroll' : true
					});
				<?php } ?>
			});
			
			} )( jQuery, window );
		</script>
	<?php }
	
	private $slideshows = array();
}

if ( ! isset( $rps_image_gallery ) ) $rps_image_gallery = new RPS_Image_Gallery;

endif;

?>