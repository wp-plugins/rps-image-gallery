<?php
/*
Plugin Name: RPS Image Gallery
Plugin URI: http://redpixel.com/rps-image-gallery-plugin
Description: An image gallery with caption support and ability to link to a slideshow or alternate URL. 
Version: 1.2.28
Author: Red Pixel Studios
Author URI: http://redpixel.com/
License: GPL3
*/

/* 	Copyright (C) 2011 - 2014  Red Pixel Studios  (email : support@redpixel.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
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
 * @version 1.2.28
 */

if ( ! class_exists( 'RPS_Image_Gallery', false ) ) :

class RPS_Image_Gallery {

	/**
	 * The current version of the plugin for internal use.
	 * Be sure to keep this updated as the plugin is updated.
	 *
	 * @since 1.2.24
	 */
	const PLUGIN_VERSION = '1.2.27';
	
	/**
	 * The plugin's name for use in printing to the user.
	 *
	 * @since 1.2.24
	 */
	const PLUGIN_NAME = 'RPS Image Gallery';
		
	/**
	 * A unique identifier for the plugin. Used for CSS classes
	 * and the like. Uses hyphens instead of spaces.
	 *
	 * @since 1.2.24
	 */
	const PLUGIN_SLUG = 'rps-image-gallery';
	
	/**
	 * A unique prefix that identifies the plugin. Used for storing
	 * database options, naming interface elements, and so on.
	 * Uses underscores instead of spaces.
	 *
	 * @since 1.2.24
	 */
	const PLUGIN_PREFIX = 'rps_image_gallery';
	
	/**
	 * A private instance of the plugin for internal use.
	 *
	 * @since 1.2.24
	 */
	private static $plugin_instance;
		
	/**
	 * Default key for checkbox arrays.
	 *
	 * @since 1.2.24
	 */
	const CHECKBOX_DEFAULT_KEY = 'display';
	
	/**
	 * Parent page for the plugin settings page.
	 *
	 * @since 1.2.24
	 */
	const OPTIONS_PAGE_PARENT = 'options-general.php';
	
	/**
	 * Slug for the plugin settings page.
	 *
	 * @since 1.2.24
	 */
	const OPTIONS_PAGE_SLUG = 'rps_image_gallery_options';
	
	/**
	 * Initialize redux framework class variables.
	 *
	 * @since 1.2.24
	 */
	private $plugin_active_redux_framework = false;
	public $ReduxFramework;
	public $args = array();
	public $sections = array();
	public $fields = array();

	/**
	 * An entry point wrapper to ensure that the plugin is only invoked once.
	 *
	 * @since 1.2.24
	 */
	public static function invoke() {
		if ( ! isset( self::$plugin_instance ) )
			self::$plugin_instance = new self;
	}

	public function __construct() {
		add_action( 'init', array( &$this, 'cb_init' ) );
		add_action( 'wp_footer', array( &$this, 'cb_footer_styles_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'cb_enqueue_styles_scripts' ) );
		
		add_filter( 'attachment_fields_to_edit', array( &$this, 'f_media_edit_gallery_link' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( &$this, 'f_media_save_gallery_link' ), 10, 2 );
		
		add_filter( 'attachment_fields_to_edit', array( &$this, 'f_media_edit_gallery_link_target' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( &$this, 'f_media_save_gallery_link_target' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, '_settings_link' ) );
		add_action( 'plugins_loaded', array( &$this, '_plugins_loaded' ) );

		add_action( 'admin_notices', array( &$this, '_display_redux_dependency_notice' ) );
		add_action( 'admin_init', array( &$this, '_dismiss_redux_dependency_notice' ) );
		
		self::_init_redux_arguments();
		self::_init_sections();
		self::_set_fields();
	}
	
	/**
	 * Displays an admin notice on plugin activation.
	 *
	 * @since 1.2.24
	 */
	public function _display_redux_dependency_notice() {
		global $pagenow;
		global $current_user;
		
		if ( ! get_user_meta( $current_user->ID, 'setting_error_' . self::PLUGIN_PREFIX . '_redux_framework_dismiss' ) and 'plugins.php' == $pagenow and current_user_can( 'install_plugins' ) ) :
		
			if ( ! self::_is_plugin_active( 'redux-framework', 'redux-framework.php' ) ) :
				echo '<div id="setting-error-' . self::PLUGIN_SLUG . '-redux-framework" class="error settings-error"><p>';
				echo sprintf( __( '%1$s uses the %2$s plugin to change default settings.', 'rps-image-gallery' ), '<strong>' . self::PLUGIN_NAME . '</strong>','<a href="http://wordpress.org/plugins/redux-framework/" target="_blank">Redux Framework</a>' );
				echo '<a href="?setting_error_' . self::PLUGIN_PREFIX . '_redux_framework_dismiss=0" style="float:right;">' . __( 'Dismiss', 'rps-image-gallery' ) . '</a>';
				echo '</p></div>';
			endif;
			
		endif;
	}
	
	/**
	 * Allows user to dismiss the admin notice.
	 *
	 * @since 1.2.24
	 */
	public function _dismiss_redux_dependency_notice() {
		global $current_user;
		
		if ( isset( $_GET['setting_error_' . self::PLUGIN_PREFIX . '_redux_framework_dismiss'] ) and '0' == $_GET['setting_error_' . self::PLUGIN_PREFIX . '_redux_framework_dismiss'] ) :
			add_user_meta( $current_user->ID, 'setting_error_' . self::PLUGIN_PREFIX . '_redux_framework_dismiss', 'true', true );
		endif;
	}

	/**
	 * Initialize arguments for Redux Framework.
	 *
	 * @see https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments
	 * @since 1.2.24
	 */
	public function _init_redux_arguments() {
		
		$this->args = array(
            
			'opt_name'          	=> '_' . self::PLUGIN_PREFIX, // This is where your data is stored in the database and also becomes your global variable name.
			'display_name'			=> self::PLUGIN_NAME, // Name that appears at the top of your panel
			'display_version'		=> self::PLUGIN_VERSION, // Version that appears at the top of your panel
			'allow_sub_menu'     	=> true, // Show the sections below the admin menu item or not
			'menu_title'			=> __( 'Image Gallery', 'rps-image-gallery' ),
            'page'		 	 		=> self::PLUGIN_NAME,
            'google_api_key'   	 	=> '', // Must be defined to add google fonts to the typography module
            'dev_mode'           	=> false, // Show the time the page took to load, etc
            'customizer'         	=> true, // Enable basic customizer support
            'page_priority'      	=> null, // Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
            'page_type'             => 'submenu', // Must be set to 'submenu' if page_parent is to be used. Options are 'menu' and 'submenu'.
            'page_parent'        	=> self::OPTIONS_PAGE_PARENT, // Admin page where the interface should appear
            'page_permissions'   	=> 'manage_options', // Permissions needed to access the options panel.
            'menu_icon'          	=> '', // Specify a custom URL to an icon
            'last_tab'           	=> '', // Force your panel to always open to a specific tab (by id)
            'page_icon'          	=> 'icon-themes', // Icon displayed in the admin panel next to your menu_title
            'page_slug'          	=> self::OPTIONS_PAGE_SLUG, // Page slug used to denote the panel
            'save_defaults'      	=> true, // On load save the defaults to DB before user clicks save or not
            'admin_bar'          	=> false, // Show the panel pages on the admin bar
            'default_show'       	=> false, // If true, shows the default value next to each field that is not the default value.
            'default_mark'       	=> '*', // What to print by the field's title if the value shown is default. Suggested: *	            
            'show_import_export' 	=> true, // Whether to display the Import/Export tab
            
            'help_tabs'          	=> array(),
            'help_sidebar'       	=> '', // __( '', $this->args['domain'] );            
			);
	}

	/**
	 * Initialize sections and fields for settings form.
	 *
	 * @since 1.2.24
	 */
	public function _init_sections() {

		$this->sections[] = array(
			'title' => __('Gallery', 'rps-image-gallery'),
			'desc' => __('Specify the layout and attributes of the gallery grid of images.', 'rps-image-gallery'),
			'icon' => 'el-icon-book',
		    'submenu' => true,
			'fields' => array(	
				array(
					'id'=>'container',
					'type' => 'radio',
					'title' => __( 'Container', 'rps-image-gallery' ),
					'subtitle' => 'container="true"',
					'desc' => __( 'The HTML tag containing the gallery grid.', 'rps-image-gallery' ),
					'options' => array( 'div' => __( 'Division (default)', 'rps-image-gallery' ), 'span' => __( 'Span', 'rps-image-gallery' ) ),
					'default' => 'div',
					),
				array(
					'id'=>'columns',
					'type' => 'slider', 
					'title' => __( 'Columns', 'rps-image-gallery' ),
					'subtitle' => 'columns="3"',
					'desc' => __( 'Number of columns making up the grid of images.', 'rps-image-gallery' ),
					'min' => '1',
					'step' => '1',
					'max' => '9',
					'default' => '3',
					'validate' => 'numeric',
					),	
				array(
					'id'=>'heading',
					'type' => 'checkbox',
					'title' => __( 'Image Title', 'rps-image-gallery' ), 
					'subtitle' => 'heading="false"',
					'desc' => __( 'The title appears just below the image in the gallery grid and after the image counter in the slideshow.', 'rps-image-gallery' ),
					'options' => array( self::CHECKBOX_DEFAULT_KEY => __( 'Display the image title', 'rps-image-gallery' ) ),
					'default' => array( self::CHECKBOX_DEFAULT_KEY => '0' ),
					),
				array(
					'id'=>'headingtag',
					'type' => 'select',
					'title' => __( 'Image Title Tag', 'rps-image-gallery' ), 
					'subtitle' => 'headingtag="h2"',
					'desc' => __( 'The HTML tag used to wrap the image title.', 'rps-image-gallery' ),
					'options' => array( 'h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'h5' => 'h5', 'h6' => 'h6' ),
					'default' => 'h2',
					),
				array(
					'id'=>'caption',
					'type' => 'checkbox',
					'title' => __( 'Caption', 'rps-image-gallery' ), 
					'subtitle' => 'caption="false"',
					'desc' => __( 'The caption appears just below the image in the gallery grid and after the image counter and image title in the slideshow.', 'rps-image-gallery' ),
					'options' => array( self::CHECKBOX_DEFAULT_KEY => __( 'Display the caption', 'rps-image-gallery' ) ),
					'default' => array( self::CHECKBOX_DEFAULT_KEY => '0' ),
					),
				array(
					'id'=>'caption_source',
					'type' => 'radio',
					'title' => __( 'Caption Source', 'rps-image-gallery' ), 
					'subtitle' => 'caption_source="caption"',
					'desc' => __( 'Specify the source of the text displayed as the caption.', 'rps-image-gallery' ),
					'options' => array( 'caption' => __( 'Caption (default)', 'rps-image-gallery' ), 'description' => __( 'Description', 'rps-image-gallery' ) ),
					'default' => 'caption',
					),
				array(
					'id'=>'caption_align',
					'type' => 'radio',
					'title' => __( 'Caption Alignment', 'rps-image-gallery' ), 
					'subtitle' => 'caption_align="left"',
					'desc' => __( 'Specify the alignment of the caption text for the gallery.', 'rps-image-gallery' ),
					'options' => array( 'left' => __( 'Left (default)', 'rps-image-gallery' ), 'center' => __( 'Center', 'rps-image-gallery' ), 'right' => __( 'Right', 'rps-image-gallery' ) ),
					'default' => 'left',
					),
				array(
					'id'=>'align',
					'type' => 'radio',
					'title' => __( 'Image Alignment', 'rps-image-gallery' ), 
					'subtitle' => 'align="left"',
					'desc' => __( 'Specify the alignment of the last row of images if there are not enough to complete the row.', 'rps-image-gallery' ),
					'options' => array( 'left' => __( 'Left (default)', 'rps-image-gallery' ), 'center' => __( 'Center', 'rps-image-gallery' ), 'right' => __( 'Right', 'rps-image-gallery' ) ),
					'default' => 'left',
					),
				array(
					'id'=>'background_thumbnails',
					'type' => 'checkbox',
					'title' => __( 'Background Thumbnails', 'rps-image-gallery' ), 
					'subtitle' => 'background_thumbnails="false"',
					'desc' => __( 'Images displayed as backgrounds have greater flexibility when styled with CSS.', 'rps-image-gallery' ),
					'options' => array( self::CHECKBOX_DEFAULT_KEY => __( 'Display gallery images as backgrounds', 'rps-image-gallery' ) ),
					'default' => array( self::CHECKBOX_DEFAULT_KEY => '0' ),
					),
				array(
					'id'=>'link',
					'type' => 'radio',
					'title' => __( 'Link', 'rps-image-gallery' ), 
					'subtitle' => 'link="permalink"',
					'desc' => __( 'Default behavior when clicking a gallery image if a Gallery Link URL is not set or slideshow mode is disabled.', 'rps-image-gallery' ),
					'options' => array( 'permalink' => 'Attachment Page', 'file' => 'Uploaded Image', 'none' => 'None' ),
					'default' => 'permalink',
					),
				)
			);

		$this->sections[] = array(
			'title' => __('Slideshow', 'rps-image-gallery'),
			'desc' => __('Define the slideshow behavior.', 'rps-image-gallery'),
			'icon' => 'el-icon-paper-clip',
		    'submenu' => true,
			'fields' => array(
				array(
					'id'=>'slideshow',
					'type' => 'checkbox',
					'title' => __( 'Slideshow', 'rps-image-gallery' ), 
					'subtitle' => 'slideshow="true"', 
					'desc' => __( 'Causes the slideshow window to display when a gallery image is clicked.', 'rps-image-gallery' ),
					'options' => array( self::CHECKBOX_DEFAULT_KEY => __( 'Activate the slideshow', 'rps-image-gallery' ) ),
					'default' => array( self::CHECKBOX_DEFAULT_KEY => '1' ),
					),
				array(
					'id'=>'fb_title_show',
					'type' => 'checkbox',
					'title' => __( 'Title', 'rps-image-gallery' ), 
					'subtitle' => 'fb_title_show="true"', 
					'desc' => __( 'The title area may include the image counter, image title, caption text and EXIF data.', 'rps-image-gallery' ),
					'options' => array( self::CHECKBOX_DEFAULT_KEY => __( 'Show the slideshow title area', 'rps-image-gallery' ) ),
					'default' => array( self::CHECKBOX_DEFAULT_KEY => '1' ),
					),
				array(
					'id'=>'fb_title_position',
					'type' => 'radio',
					'title' => __( 'Title Position', 'rps-image-gallery' ), 
					'subtitle' => 'fb_title_position="over"', 
					'desc' => __( 'Where the title area should appear.', 'rps-image-gallery' ),
					'options' => array( 'over' => __( 'Over the image (default)', 'rps-image-gallery' ), 'outside' => __( 'Below the slide', 'rps-image-gallery' ), 'inside' => __( 'Below the image', 'rps-image-gallery' ) ),
					'default' => 'over',
					),
				array(
					'id'=>'fb_title_align',
					'type' => 'radio',
					'title' => __( 'Title Alignment', 'rps-image-gallery' ), 
					'subtitle' => 'fb_title_align="none"', 
					'desc' => __( 'Alignment of text in the title area.', 'rps-image-gallery' ),
					'options' => array( 'none' => __( 'None (default)', 'rps-image-gallery' ), 'left' => __( 'Left', 'rps-image-gallery' ), 'center' => __( 'Center', 'rps-image-gallery' ), 'right' => __( 'Right', 'rps-image-gallery' ) ),
					'default' => 'none',
					),
				array(
					'id'=>'fb_title_counter_show',
					'type' => 'checkbox',
					'title' => __( 'Image Counter', 'rps-image-gallery' ), 
					'subtitle' => 'fb_title_counter_show="true"', 
					'desc' => __( 'The image counter displays the word "Image" followed by the image number and total count.', 'rps-image-gallery' ),
					'options' => array( self::CHECKBOX_DEFAULT_KEY => __( 'Show the image counter', 'rps-image-gallery' ) ),
					'default' => array( self::CHECKBOX_DEFAULT_KEY => '1' ),
					),
				array(
					'id'=>'fb_center_on_scroll',
					'type' => 'checkbox',
					'title' => __( 'Slideshow Position', 'rps-image-gallery' ), 
					'subtitle' => 'fb_center_on_scroll="true"', 
					'desc' => __( 'Specifies the slideshow behavior when a browser window scrolled or resized.', 'rps-image-gallery' ),
					'options' => array( self::CHECKBOX_DEFAULT_KEY => __( 'Keep the slideshow centered', 'rps-image-gallery' ) ),
					'default' => array( self::CHECKBOX_DEFAULT_KEY => '1' ),
					),
				array(
					'id'=>'fb_cyclic',
					'type' => 'checkbox',
					'title' => __( 'Loop', 'rps-image-gallery' ), 
					'subtitle' => 'fb_cyclic="true"', 
					'options' => array( self::CHECKBOX_DEFAULT_KEY => __( 'Start over when reaching the end of the slideshow', 'rps-image-gallery' ) ),
					'default' => array( self::CHECKBOX_DEFAULT_KEY => '1' ),
					),
				array(
					'id'=>'fb_transition_in',
					'type' => 'radio',
					'title' => __( 'Transition In', 'rps-image-gallery' ), 
					'subtitle' => 'fb_transition_in="true"', 
					'desc' => __( 'Effect when slideshow is opened.', 'rps-image-gallery' ),
					'options' => array( 'none' => __( 'None (default)', 'rps-image-gallery' ), 'elastic' => __( 'Elastic', 'rps-image-gallery' ), 'fade' => __( 'Fade', 'rps-image-gallery' ) ),
					'default' => 'none',
					),
				array(
					'id'=>'fb_speed_in',
					'type' => 'slider', 
					'title' => __( 'Transition Speed In', 'rps-image-gallery' ),
					'subtitle' => 'fb_speed_in="300"', 
					'min' => '100',
					'step' => '100',
					'max' => '1000',
					'default' => '300',
					'validate' => 'numeric',
					),	
				array(
					'id'=>'fb_transition_out',
					'type' => 'radio',
					'title' => __( 'Transition Out', 'rps-image-gallery' ), 
					'subtitle' => 'fb_transition_out="true"', 
					'desc' => __( 'Effect when slideshow is closed.', 'rps-image-gallery' ),
					'options' => array( 'none' => __( 'None (default)', 'rps-image-gallery' ), 'elastic' => __( 'Elastic', 'rps-image-gallery' ), 'fade' => __( 'Fade', 'rps-image-gallery' ) ),
					'default' => 'none',
					),
				array(
					'id'=>'fb_speed_out',
					'type' => 'slider', 
					'title' => __( 'Transition Speed Out', 'rps-image-gallery' ),
					'subtitle' => 'fb_speed_out="300"', 
					'min' => '100',
					'step' => '100',
					'max' => '1000',
					'default' => '300',
					'validate' => 'numeric',
					),	
				array(
					'id'=>'fb_show_close_button',
					'type' => 'checkbox',
					'title' => __( 'Close Button', 'rps-image-gallery' ), 
					'subtitle' => 'fb_show_close_button="true"', 
					'options' => array( self::CHECKBOX_DEFAULT_KEY => __( 'Show the close button on the slideshow window', 'rps-image-gallery' ) ),
					'default' => array( self::CHECKBOX_DEFAULT_KEY => '1' ),
					),
				),
			);

		$this->sections[] = array(
			'title' => __('Image Sizes', 'rps-image-gallery'),
			'desc' => __('Specify the image sizes to use in the gallery and slideshow views.', 'rps-image-gallery'),
			'icon' => 'el-icon-eye-open',
		    'submenu' => true,
			'fields' => array(	
				array(
					'id'=>'size',
					'type' => 'callback',
					'title' => __( 'Gallery Image Size', 'rps-image-gallery' ), 
					'subtitle' => 'size="thumbnail"',
					'desc' => __( 'Specify the image size for the gallery view.', 'rps-image-gallery' ),
					'default' => 'thumbnail',
					'callback' => '_rps_image_gallery_custom_field_image_sizes',
					),
				array(
					'id'=>'size_large',
					'type' => 'callback',
					'title' => __( 'Slideshow Image Size', 'rps-image-gallery' ), 
					'subtitle' => 'size="large"',
					'desc' => __( 'Specify the image size for the slideshow view.', 'rps-image-gallery' ),
					'default' => 'large',
					'callback' => '_rps_image_gallery_custom_field_image_sizes',
					),
				)
			);

		$this->sections[] = array(
			'title' => __('EXIF Data', 'rps-image-gallery'),
			'desc' => __('Define if and where EXIF data is displayed.', 'rps-image-gallery'),
			'icon' => 'el-icon-info-sign',
		    'submenu' => true,
			'fields' => array(	
				array(
					'id'=>'exif',
					'type' => 'checkbox',
					'title' => __( 'EXIF', 'rps-image-gallery' ), 
					'subtitle' => 'exif="false"',
					'options' => array( self::CHECKBOX_DEFAULT_KEY => __( 'Display EXIF data', 'rps-image-gallery' ) ),
					'default' => array( self::CHECKBOX_DEFAULT_KEY => '0' ),
					),
				array(
					'id'=>'exif_locations',
					'type' => 'radio',
					'title' => __( 'EXIF Location', 'rps-image-gallery' ), 
					'subtitle' => __( 'Define where the EXIF data is displayed.', 'rps-image-gallery' ),
					'options' => array( 'slideshow' => __( 'Slideshow (default)', 'rps-image-gallery' ), 'gallery' => __( 'Gallery', 'rps-image-gallery' ) ),
					'default' => 'slideshow',
					),
				array(
		            'id' => 'exif_fields',
			        'type' => 'sortable',
			        'mode' => 'checkbox', // checkbox or text
		    	    'title' => __('Sortable Text Option', 'rps-image-gallery'),
		        	'subtitle' => __('Define and reorder these however you want.', 'rps-image-gallery'),
					'desc' => __('This is the description field, again good for additional info.', 'rps-image-gallery'),
		            'options' => array(
		            	'camera' => __( 'Camera', 'rps-image-gallery' ),
		            	'aperture' => __( 'Aperture', 'rps-image-gallery' ),
		            	'focal_length' => __( 'Focal Length', 'rps-image-gallery' ),
		            	'iso' => __( 'ISO', 'rps-image-gallery' ),
		            	'shutter_speed' => __( 'Shutter Speed', 'rps-image-gallery' ),
		            	'title' => __( 'Title', 'rps-image-gallery' ),
		            	'caption' => __( 'Caption', 'rps-image-gallery' ),
		            	'credit' => __( 'Credit', 'rps-image-gallery' ),
		            	'copyright' => __( 'Copyright', 'rps-image-gallery' ),
		            	'created_timestamp' => __( 'Created Timestamp', 'rps-image-gallery' ),
		    	    	),
		        	'default' => array(
		        		'camera' => '1',
		        		'aperture' => '1',
		        		'focal_length' => '1',
		        		'iso' => '1',
		        		'shutter_speed' => '1',
		        		'title' => '1',
		        		'caption' => '1',
		        		'credit' => '1',
		        		'copyright' => '1',
		        		'created_timestamp' => '1'
		        		),
					),
				)
			);
			
		$this->sections[] = array(
			'title' => __('Sorting', 'rps-image-gallery'),
			'desc' => __('The sorting settings only work if the ids shortcode attribute has not been set. Changing the order in the WordPress Gallery editor will add the ids attribute.', 'rps-image-gallery'),
			'icon' => 'el-icon-cogs',
		    'submenu' => true,
			'fields' => array(
				array(
					'id'=>'order',
					'type' => 'radio',
					'title' => __( 'Order', 'rps-image-gallery' ), 
					'subtitle' => 'order="asc"',
					'options' => array( 'asc' => __( 'Ascending (default)', 'rps-image-gallery' ), 'desc' => __( 'Descending', 'rps-image-gallery' ) ),
					'default' => 'asc',
					),
				array(
					'id'=>'orderby',
					'type' => 'radio',
					'title' => __( 'Order By', 'rps-image-gallery' ), 
					'subtitle' => 'orderby="menu_order"',
					'options' => array( 'menu_order' => __( 'Menu Order (default)', 'rps-image-gallery' ), 'title' => __( 'Title', 'rps-image-gallery' ), 'post_date' => __( 'Date', 'rps-image-gallery' ), 'rand' => __( 'Random', 'rps-image-gallery' ), 'ID' => __( 'ID', 'rps-image-gallery' ) ),
					'default' => 'menu_order',
					),
				)
			);
	}
	
	/**
	 * Sets array of field defaults.
	 *
	 * @return array Array of fields and settings.
	 * @since 1.2.24
	 */
    public function _set_fields() {
        foreach( $this->sections as $section ) :
			
			if( isset( $section['fields'] ) ) :
			
				foreach( $section['fields'] as $field ) :
			
					$this->fields[$field['id']] = $field;
			
				endforeach;
			
			endif;
			
        endforeach;
    }

	/**
	 * Builds an array of default values for the shortcode attributes.
	 *
	 * @return array Array of defaults.
	 * @since 1.2.24
	 */
	private function _get_defaults() {
		static $defaults_array;
	
		global $_rps_image_gallery;
		$setting = '';

		if ( isset( $this->fields ) and empty( $defaults_array ) ) :
			
			$defaults_array = array();
			
			foreach( $this->fields as $field ) :
			
				// the redux framework is active so get the settings from the rps_image_gallery global
				if ( $this->plugin_active_redux_framework ) :
				
					$setting = $_rps_image_gallery[$field['id']];
				
				// the redux framework is not active so get the default value from the fields array
				elseif ( isset( $field['default'] ) ) :
				
					$setting = $field['default'];
				
				endif;
				
				// process the setting to get the value based on the field type
				if( is_array( $setting ) ) :
					
					switch ( true ) :
						
						// a checkbox which has one possible value
						case ( 'checkbox' == $field['type'] ) :
							$defaults_array[$field['id']] = $setting[self::CHECKBOX_DEFAULT_KEY];
							break;
						
						// a sortable list of checkboxes with key as the value and value as the switch
						case ( 'sortable' == $field['type'] and 'checkbox' == $field['mode'] ) :
							$setting_values = array();
							
							foreach ( $setting as $key => $value ) :
								if ( ! empty( $value ) ) $setting_values[] = $key;
							endforeach;
														
							$defaults_array[$field['id']] = $setting_values;
							break;
							
					endswitch;
					
				else :
				
					$defaults_array[$field['id']] = $setting;
				
				endif;					
			
			endforeach;
			
		endif;
		
		return $defaults_array;
	}
	
	/**
	 * Retrieves the default value for a specific shortcode attribute.
	 *
	 * @since 1.2.24
	 */
	private function _shortcode_default( $id = '' ) {
		$defaults = self::_get_defaults();
		return $defaults[$id];
	}
		
	/**
	 * Load the text domain for l10n and i18n and activate features based on availability of supporting plugins.
	 *
	 * @since 1.2.22
	 */
	public function _plugins_loaded() {
		load_plugin_textdomain( 'rps-image-gallery', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . '/lang/' );
		
		$wordpress_plugin_path = str_replace( dirname( plugin_basename( __FILE__ ) ), '', dirname( plugin_dir_path( __FILE__ ) ) );
		
		$redux_plugin_directory = 'redux-framework';
		$redux_plugin_filename = 'redux-framework.php';
		$redux_plugin_path = $wordpress_plugin_path . '/' . $redux_plugin_directory;
		
		$this->plugin_active_redux_framework = self::_is_plugin_active( $redux_plugin_directory, $redux_plugin_filename );
		
		if ( $this->plugin_active_redux_framework and file_exists( $redux_plugin_path . '/ReduxCore/framework.php' ) ) :
			require_once( $redux_plugin_path . '/' . $redux_plugin_filename );
			$this->ReduxFramework = new ReduxFramework( $this->sections, $this->args );			
		endif;
	}
	
	/**
	 * Generates a link to the Settings page on the plugin entry on the plugins page.
	 *
	 * @since 1.2.24
	 */
	public function _settings_link( $links ) {
	    if ( $this->plugin_active_redux_framework ) :
		    $settings_link = '<a href="' . self::OPTIONS_PAGE_PARENT . '?page=' . self::OPTIONS_PAGE_SLUG . '">' . __( 'Settings', 'rps-image-gallery' ) . '</a>';
		  	array_push( $links, $settings_link );
		endif;
		
	  	return $links;
	}

	/**
	 * Helper method to check if a specified plugin is active.
	 * Should be called at plugins_loaded hook.
	 *
	 * @return boolean True if plugin is active.
	 * @since 1.2.24
	 */
	private function _is_plugin_active( $plugin_directory = '', $plugin_filename = '' ) {
		$wordpress_active_plugins = get_option( 'active_plugins' );
		return in_array( trailingslashit( $plugin_directory ) . $plugin_filename, $wordpress_active_plugins );
	}
		
	/**
	 * Sanitizes and validates the values passed through shortcode attributes.
	 * If not within tolerance then fallback to the shortcode default.
	 *
	 * @since 1.2.24
	 */
	private function _filter_shortcode_attribute( $field = '', $value = '' ) {
		$filtered_value = '';
		
		if ( '' != $field and '' != $value ) :
		
			if( array_key_exists( $field, $this->fields ) ) :
		
				$field_props = $this->fields[$field];
				$filtered_value = '';
				
				switch ( true ) :
					
					// radio and select menus - 'options' is an array and 'default' is a string identifying the default key in the options array
					case ( 'radio' == $field_props['type'] or 'select' == $field_props['type'] ) :
						$value = sanitize_text_field( $value );
						$filtered_value = ( array_key_exists( $value, $field_props['options'] ) ) ? $value : self::_shortcode_default( $field );
						break;
						
					// sortable checkbox - list of checkbox items that may be sorted but provided as comma separated values via shortcode
					case ( 'sortable' == $field_props['type'] and 'checkbox' == $field_props['mode'] ) :
						$value = array_map( 'trim', explode( ',', trim( sanitize_text_field( $value ) ) ) );
						
						foreach ( $value as $element ) :
							if ( array_key_exists( $element, $field_props['options'] ) )
								$filtered_value[] = $element;
						endforeach;
						
						if ( empty( $filtered_value ) and ! is_array( $filtered_value ) )
							$filtered_value = self::_shortcode_default( $field );
						break;
					
					// slider - value is a string number with a 'min' and 'max' defined
					case ( 'slider' == $field_props['type'] ) :
						$value = intval( sanitize_text_field( $value ) );
						$filtered_value = ( $value >= intval( $field_props['min'] ) and $value <= intval( $field_props['max'] ) ) ? $value : self::_shortcode_default( $field );
						break;
						
					// checkbox - value is either '1', on or '0' off
					case ( 'checkbox' == $field_props['type'] ) :
						$value = sanitize_text_field( $value );
						$filtered_value = ( 'true' == $value ) ? '1' : '0';
						break;
						
					// just sanitize the text field
					default :
						$value = sanitize_text_field( $value );
	
				endswitch;
			
			// handle attributes for fields not set in $this->fields
			else :
			
				$value = sanitize_text_field( $value );
				$value = explode( ',', $value );
				$value = array_map( 'trim', $value );
						
			endif;
			
		endif;
		
		return ( '' != $filtered_value ) ? $filtered_value : $value;
	}

	/*
	 * Add the gallery_link field to the page for editing.
	 *
	 * @since 1.2
	 */
	public function f_media_edit_gallery_link( $fields, $post ) {
		if ( stristr( $post->post_mime_type, 'image' ) === false ) return $fields;
		
		$fields['post_gallery_link'] = array(
			'label' => __( 'Gallery Link URL', 'rps-image-gallery' ),
			'value' => esc_attr( get_post_meta( $post->ID, '_rps_attachment_post_gallery_link', true ) ),
			'input' => 'text',
			'helps' => __( 'Enter a relative or absolute link that should be followed when the image is clicked<br />from within an image gallery.', 'rps-image-gallery' )
		);
	
		return $fields;
	}

	/*
	 * Add the gallery_link_target field to the page for editing
	 *
	 * @since 1.2.6
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
			'label' => __( 'Gallery Link Target', 'rps-image-gallery' ),
			'value' => $target,
			'input' => 'html',
			'html' => '<select name="attachments[' . $post->ID . '][post_gallery_link_target]" id="attachments[' . $post->ID . '][post_gallery_link_target]">' . $options_inner_html . '</select>',
			'helps' => __( 'Select the target for the Gallery Link URL.', 'rps-image-gallery' )
		);
	
		return $fields;
	}
	
	/*
	 * Save the gallery_link field.
	 *
	 * @since 1.2
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
	 * @since 1.2.6
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
	 * @since 1.2
	 */
	public function get_gallery_link( $attachment_id ) {
		return get_post_meta( $attachment_id, '_rps_attachment_post_gallery_link', true );
	}

	/*
	 * Return the gallery_link_target field.
	 *
	 * @since 1.2
	 */
	public function get_gallery_link_target( $attachment_id ) {
		return get_post_meta( $attachment_id, '_rps_attachment_post_gallery_link_target', true );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.2
	 */
	public function cb_init() {
		add_shortcode( 'rps-image-gallery', array( &$this, 'cb_gallery_shortcode' ) );
		add_shortcode( 'gallery', array( &$this, 'cb_gallery_shortcode' ) );
		
		wp_register_style( 'rps-image-gallery-fancybox', plugins_url( 'dependencies/fancybox/jquery.fancybox-1.3.4.css', __FILE__ ), false, '1.0.0' );
		wp_register_style( 'rps-image-gallery', plugins_url( 'rps-image-gallery.css', __FILE__ ), array( 'rps-image-gallery-fancybox' ), self::PLUGIN_VERSION );
		
		wp_register_script( 'rps-image-gallery-easing', plugins_url( 'dependencies/fancybox/jquery.easing-1.3.pack.2.js', __FILE__ ), array( 'jquery' ), '1.3', true );
		wp_register_script( 'rps-image-gallery-fancybox', plugins_url( 'dependencies/fancybox/jquery.fancybox-1.3.4.pack.js', __FILE__ ), array( 'rps-image-gallery-easing' ), '1.3.4', true );
	}
	
	/**
	 * Enqueue styles and scripts.
	 *
	 * @since 1.2
	 */
	public function cb_enqueue_styles_scripts() {
		wp_enqueue_style( 'rps-image-gallery' );
	}
		
	/**
	 * The gallery shortcode.
	 *
	 * @since 1.0
	 * @todo Setup array of statuses to exclude in options framework.
	 */					
	public function cb_gallery_shortcode( $atts, $content = null ) {
	
		global $post;
		global $_rps_image_gallery;
				
		$str_output = '';
		$gallery_ids = array();
		$attachments = array();
		$excluded_statuses = array( 'trash', 'future' );
		
		/*
		 * Specify defaults for shortcode attributes.
		 */
		$defaults = array(
			'id' => get_the_id(),
			'ids' => '',
			'group_name' => 'rps-image-group-' . $post->ID,
			'include' => '',
			'exclude' => '',
			'container' => self::_shortcode_default( 'container' ),
			'columns' => self::_shortcode_default( 'columns' ),
			'align' => self::_shortcode_default( 'align' ),
			'size' => self::_shortcode_default( 'size' ),
			'size_large' => self::_shortcode_default( 'size_large' ),
			'orderby' => self::_shortcode_default( 'orderby' ),
			'order' => self::_shortcode_default( 'order' ),
			'heading' => self::_shortcode_default( 'heading' ),
			'headingtag' => self::_shortcode_default( 'headingtag' ),
			'caption' => self::_shortcode_default( 'caption' ),
			'caption_source' => self::_shortcode_default( 'caption_source' ),
			'caption_align' => self::_shortcode_default( 'caption_align' ),
			'link' => self::_shortcode_default( 'link' ),
			'slideshow' => self::_shortcode_default( 'slideshow' ),
			'background_thumbnails' => self::_shortcode_default( 'background_thumbnails' ),
			'exif' => self::_shortcode_default( 'exif' ),
			'exif_locations' => self::_shortcode_default( 'exif_locations' ),
			'exif_fields' => self::_shortcode_default( 'exif_fields' ),
			'fb_title_show' => self::_shortcode_default( 'fb_title_show' ),
			'fb_title_position' => self::_shortcode_default( 'fb_title_position' ),
			'fb_title_align' => self::_shortcode_default( 'fb_title_align' ),
			'fb_title_counter_show' => self::_shortcode_default( 'fb_title_counter_show' ),
			'fb_center_on_scroll' => self::_shortcode_default( 'fb_center_on_scroll' ),
			'fb_cyclic' => self::_shortcode_default( 'fb_cyclic' ),
			'fb_transition_in' => self::_shortcode_default( 'fb_transition_in' ),
			'fb_transition_out' => self::_shortcode_default( 'fb_transition_out' ),
			'fb_speed_in' => self::_shortcode_default( 'fb_speed_in' ),
			'fb_speed_out' => self::_shortcode_default( 'fb_speed_out' ),
			'fb_show_close_button' => self::_shortcode_default( 'fb_show_close_button' ),
		);
		
		// filter provided attributes defined in $this->fields.
		if ( is_array( $atts ) ) :
			foreach ( $atts as $field => $value ) :
				$atts[$field] = self::_filter_shortcode_attribute( $field, $value );
			endforeach;
		endif;
						
		if ( ! empty( $atts['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $atts['orderby'] ) )
				$atts['orderby'] = 'post__in';
			$atts['include'] = $atts['ids'];
		}
		
		$shortcode_atts = shortcode_atts( $defaults, $atts );
		extract( $shortcode_atts, EXTR_SKIP );
				
		// an array of posts containing galleries which should be combined
		$gallery_ids = (array)$id;

		/*
		 * Make sure that the attachment ids are not being provided alongside gallery ids
		 * since this will cause the gallery to be output more than once.
		 */
		if ( ! empty( $ids ) ) : // attachment ids were specified and should be used (WordPress 3.5)

			$attachments = get_posts( array(
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'numberposts' => -1,
				'post_status' => null,
				'post_parent' => ( ( empty( $include ) ) ? $id : '' ),
				'order' => $order,
				'orderby' => $orderby,
				'include' => $include,
				'exclude' => ( empty( $include ) ? $exclude : array() )
			) );
		
		else : // process the galleries as normal using post attachments
		
			foreach ( $gallery_ids as $id ) {
			
				//exclude ids of posts that have been set to specified statuses but expose the gallery items if the user is viewing their parent
				//allowing users with the proper permissions to see galleries on posts that would normally be hidden from view
				if ( ! in_array( get_post_status( $id ), $excluded_statuses ) or $post->ID === $id ) {
				
					$post_attachments = get_posts( array(
						'post_type' => 'attachment',
						'post_mime_type' => 'image',
						'numberposts' => -1,
						'post_status' => null,
						'post_parent' => ( ( empty( $include ) ) ? $id : '' ),
						'order' => $order,
						'orderby' => $orderby,
						'include' => $include,
						'exclude' => ( empty( $include ) ? $exclude : array() )
					) );
					$attachments = array_merge( $attachments, $post_attachments );
					
				}
				
			}

			if ( empty( $attachments ) ) return '';
			$attachments = $this->reorder_merged_attachments( $attachments, $orderby, $order );
			
		endif;
				
		/**
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
				'fb_show_close_button',
				'fb_title_counter_show',
				'fb_cyclic',
				'fb_center_on_scroll'
			);
		}
		
		$quantity = count( $attachments );
		
		/**
		 * The outer wrapper for the gallery.
		 */
		$str_output .= '<' . $container . ' class="rps-image-gallery gallery-columns-' . $columns . ' gallery-size-' . $size . '" style="text-align:' . $align . '">';
		
		/*
		 * If the gallery_thumbnails option is set to true then we assign a class in order to transform the gallery.
		 */
		$str_class_background_thumbnails = ( $background_thumbnails ) ? ' class="background-thumbnails"' : '';
			
		$str_output .= '<ul'. $str_class_background_thumbnails .'>';
		
		/**
		 * Initialize the counter that is used while looping through the attachments
		 * to set classes on the list items and images.
		 */
		$i = 0;
		
		foreach ( $attachments as $attachment ) {
			$i++;
			$str_href = '';
			$str_rel = '';
			$str_title = $str_heading = strip_tags( $attachment->post_title );
			$str_alt_text = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
			$str_caption = '';
			if( $caption_source == 'caption' ) :
				$str_caption = $attachment->post_excerpt;
			elseif( $caption_source == 'description' ) :
				$str_caption = $attachment->post_content;
			endif;
			$arr_image_small = wp_get_attachment_image_src( $attachment->ID, $size );
			$arr_image_large = wp_get_attachment_image_src( $attachment->ID, $size_large );
			$str_class = '';
			$gallery_heading = '';
			$gallery_caption = '';
			$gallery_target = '';
			$str_target = get_post_meta( $attachment->ID, '_rps_attachment_post_gallery_link_target', true );
			$str_image = '';
						
			/*
			 * Fall back to using the title if the alt text is not provided for accessibility requirements.
			 */
			if ( $str_alt_text == '' ) $str_alt_text = $str_title;
						
			/*
			 * Flag the last image with a class for the slideshow just in case the slideshow option is set to true.
			 */
			if ( $i == $quantity ) $str_class = ' class="last"';
			
			/*
			 * The gallery-icon class is default for WordPress so we preserve it.
			 * Add gallery-icon-end-row and gallery-icon-begin-row classes to assist with override styling.
			 * If the counter modulus the number of columns equals zero then append the end-row class.
			 * However if there is only one column required then there is no need to evaluate modulus.
			 */
			if ( $i % $columns == 0 and $columns > 1 ) :
				$str_output .= '<li class="gallery-icon gallery-icon-end-row">';
			/*
			 * If the counter modulus the number of columns equals one then append the begin-row class.
			 */
			elseif ( $i % $columns == 1 and $columns > 1 ) :
				$str_output .= '<li class="gallery-icon gallery-icon-begin-row">';
			/*
			 * Otherwise just output the default gallery-icon class.
			 */
			else :
				$str_output .= '<li class="gallery-icon">';
			endif;
			
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
			 * If no slideshow, then check to see if the href is set and if not then use
			 * the "Link thumbnails to" setting. A value of "file" links to the full size
			 * version of the image, while a value of "permalink" links to the attachment template.
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
					$gallery_target = ( $str_target == '_self' or $str_target == '' ) ? '' : ' target="' . $str_target . '"';
				endif;
			endif;

			/**
			 * Define the exif data for display.
			 */
			$gallery_exif = ( $exif and 'gallery' == $exif_locations ) ? self::generate_gallery_exif( $attachment->ID, $exif_fields ) : '';
			$slideshow_exif = ( $exif and 'slideshow' == $exif_locations ) ? self::generate_slideshow_exif ( $attachment->ID, $exif_fields ) : '';

			/*
			 * Determine what strings need to be used for the title attribute of the HREF and the caption.
			 * Each image has the possibility of having a Title, Alternate Text and Caption.
			 * The attachment title is already set to $str_title and is replaced if any of the
			 * other values are populated in order of precedence Caption, Alt Text then Title.
			 */
			if ( $str_caption != '' ) :
				$str_title = htmlspecialchars( strip_tags( $str_caption ), ENT_QUOTES, get_bloginfo( 'charset' ) );
			elseif ( $str_alt_text != '' ) :
				$str_title = $str_alt_text;
			endif;
			
			$str_title = $str_title . $slideshow_exif;
			
			/*
			 * If the heading is set to show then add it to the str_title so that it can also show in the slideshow
			 */
			if ( $heading ) $str_title = $str_heading . ' &mdash; ' . $str_title;
			
			/*
			 * Define the string to be used for the image. It will be wrapped in nested divs if $background_thumbnails is set to true.
			 */
			if ( $background_thumbnails ) :
			
				$str_image = '<div><div style="background-image:url(' . $arr_image_small[0] . ');"><img' . $str_class . ' alt="' . $str_alt_text . '" src="' . $arr_image_small[0] . '" title="' . $str_title . '" style="visibility:hidden;" /></div></div>';
				
			else :
			
				$str_image = '<img' . $str_class . ' alt="' . $str_alt_text . '" src="' . $arr_image_small[0] . '" title="' . $str_title . '" />';
				
			endif;
			
			/* 
			 * If the slideshow is set to false, and the link value is set to none and the href is empty,
			 * just output the gallery image and don't link it to anything, otherwise output the image link.
			 * @todo setup handler to force the padding in the li gallery-icon so that the thumbnail image used as a background looks just like the image (padding:5%)
			 */
			if ( !$slideshow && $link == 'none' && $str_href == '' ) :
				$str_output .= $str_image;
			else :
				$str_output .= '<a' . $str_rel . ' href="' . $str_href . '" title="' . $str_title . '"' . $gallery_target . '>' . $str_image . '</a>';
			endif;
			
			/*
			 * Define the gallery heading tag if the heading is set to true.
			 */
			if ( $heading ) $gallery_heading = '<' . $headingtag . ' class="wp-heading-text gallery-heading">' . $str_heading . '</' . $headingtag . '>';
			
			/*
			 * Define the gallery caption tag if caption is set to true.
			 * Note that the wp-caption-text and gallery-caption classes are default for WordPress.
			 */
			if ( $caption ) $gallery_caption = '<span class="wp-caption-text gallery-caption" style="text-align:' . $_rps_image_gallery['caption_align'] . '">' . $str_caption . '</span>';
						
			$str_output .= $gallery_heading . $gallery_caption . $gallery_exif . '</li>';
			
		}
		
		$str_output .= '</ul>';
		
		$str_output .= '</' . $container . '>';
				
		return $str_output;
	}
	
	/**
	 * Process the EXIF data.
	 * @since 1.2.22
	 * @return array Array of image meta data with empty values omitted and specific values converted in the specified or default order.
	 * @see http://codex.wordpress.org/Function_Reference/wp_read_image_metadata
	 * @see http://www.media.mit.edu/pia/Research/deepview/exif.html
	 * aperture, credit, camera, caption, created_timestamp, copyright, focal_length, iso, shutter_speed, title
	 * @todo use sprintf function for localizing strings
	 */
	private function get_exif_data( $attachment_id = '', $image_metadata_requested = array() ) {
		$output = array();
		$metadata = wp_get_attachment_metadata( $attachment_id );
		
		$image_metadata_available = $metadata['image_meta'];
		$image_metadata_selected = array();
						
		if( ! empty( $image_metadata_available ) ) :
		
				// Get the fields in the order that the user requested
				foreach ( $image_metadata_requested as $key ) :
					
					// Check to see if the field that the user requested is available
					if ( array_key_exists( $key, $image_metadata_available ) )
						$image_metadata_selected[$key] = $image_metadata_available[$key];
					
				endforeach;
				
				// Verify that there are some fields selected after processing
				if ( ! empty( $image_metadata_selected ) ) :

					foreach ( $image_metadata_selected as $meta_key => $meta_value ) :
	
						if ( ! empty( $meta_value ) ) :
						
							$meta_value = ( $meta_key == 'aperture' ) ? __( 'f/', 'rps-image-gallery' ) . $meta_value : $meta_value;
							$meta_value = ( $meta_key == 'created_timestamp' ) ? date_i18n( get_option( 'date_format' ), $meta_value ) : $meta_value;
							$meta_value = ( $meta_key == 'focal_length' ) ? $meta_value . __( 'mm', 'rps-image-gallery' ) : $meta_value;
							$meta_value = ( $meta_key == 'iso' ) ? __( 'ISO', 'rps-image-gallery' ) . $meta_value : $meta_value;
							$meta_value = ( $meta_key == 'shutter_speed' ) ? round( $meta_value, 4 ) . __( 's', 'rps-image-gallery' ) : $meta_value;
								
							$output[$meta_key] = $meta_value;
																			
						endif;
	
					endforeach;
				
				endif;
				
		endif;
		
		return $output;
	}
	
	/**
	 * Generate the EXIF string that appears with the image caption in the gallery.
	 * @since 1.2.22
	 * @return string
	 */
	private function generate_gallery_exif( $attachment_id = '', $image_metadata_requested = array() ) {
		$output = '';
		$exif = self::get_exif_data( $attachment_id, $image_metadata_requested );

		if ( ! empty( $exif ) ) :

			$output .= '<ul class="gallery-meta">';
							
			foreach ( $exif as $meta_key => $meta_value ) :
	
					$output .= '<li class="meta-' . $meta_key . '">' . $meta_value . '</li>';
	
			endforeach;
							
			$output .= '</ul>';
			
		endif;
		
		return $output;
	}
	
	/**
	 * Generate the EXIF string that appears with the image caption in the slideshow.
	 * @since 1.2.22
	 * @return string
	 */
	private function generate_slideshow_exif( $attachment_id = '', $image_metadata_requested = array() ) {
		$output = '';
		$exif = self::get_exif_data( $attachment_id, $image_metadata_requested );
		
		if ( ! empty( $exif ) ) :
			
			$output .= ' &mdash; ';
			$output .= implode( '&nbsp; ', $exif );
			
		endif;
		
		return $output;
	}
	
	/**
	 * @since 1.2.9
	 * @return array IDs of the posts from which to source the attachments
	 *
	 * The gallery id can contain a string including a single integer or can be a
	 * series of integers that is delimited by a comma. We remove duplicate IDs to
	 * prevent a single gallery from appearing more than once. We also check to see
	 * if the integer returned is zero and if so assume that a non integer was provided.
	 */
	private function process_gallery_id( $id ) {
		$ids_sanitized = array();
		
		$ids = explode( ',', $id );
		
		foreach ( $ids as $id ) :
			$id = absint( trim( $id ) );
			if ( $id > 0 ) $ids_sanitized[] = $id;
		endforeach;
		
		return array_unique( $ids_sanitized );
		
	}
	
	/**
	 * @since 1.2.9
	 * @return array Resorted array of attachments as objects.
	 *
	 * Possible orderby values are 'menu_order', 'title', 'post_date' or 'random'.
	 * If 'random' is used then we just need to shuffle the array of attachments.
	 */
	private function reorder_merged_attachments( $attachments, $orderby, $order ) {
		$menu_order = $title = $post_date = array();
		if ( $orderby == 'rand' ) :
			shuffle( $attachments );
		else :
			foreach ( $attachments as $key => $row ) :
				$menu_order[$key] = $row->menu_order;
				$title[$key] = $row->post_title;
				$post_date[$key] = $row->post_modified_gmt;
			endforeach;
		
			switch ( $orderby ) {
				case 'menu_order' :
					$resort_orderby = $menu_order;
					break;
				case 'title' :
					$resort_orderby = $title;
					break;
				case 'post_date' :
					$resort_orderby = $post_date;
					break;
			}
			
			array_multisort( $resort_orderby, ( ( $order == 'asc' ) ? SORT_ASC : SORT_DESC ), $attachments );
		endif;
		
		return $attachments;
	}
	
	/*
	 * Output the necessary styles and scripts in the footer.
	 *
	 * @since 1.2
	 */
	public function cb_footer_styles_scripts () {
		if ( empty( $this->slideshows ) ) return;
		global $_rps_image_gallery;
		wp_print_scripts( 'rps-image-gallery-fancybox' );
		
		?>
		<script type="text/javascript">
			;( function( jQuery, undefined ) {
			var $ = jQuery.noConflict();
			
			$( document ).ready( function() {
				<?php foreach ( $this->slideshows as $slideshow ) { ?>
				    $('a[rel="<?php echo $slideshow['group_name']; ?>"]').fancybox({
						'transitionIn' : '<?php echo $slideshow['fb_transition_in']; ?>',
						'transitionOut' : '<?php echo $slideshow['fb_transition_out']; ?>',
						'titlePosition' : '<?php echo $slideshow['fb_title_position']; ?>',
						'speedIn' : <?php echo $slideshow['fb_speed_in']; ?>,
						'speedOut' : <?php echo $slideshow['fb_speed_out']; ?>,
						'showCloseButton' : <?php echo ( $slideshow['fb_show_close_button'] ) ? 'true' : 'false'; ?>,
						'cyclic' : <?php echo ( $slideshow['fb_cyclic'] ) ? 'true' : 'false'; ?>,
						'centerOnScroll' : <?php echo ( $slideshow['fb_center_on_scroll'] ) ? 'true' : 'false'; ?>,
					<?php if ( $slideshow['fb_title_show'] and $slideshow['fb_title_counter_show'] ) : ?>
				    	'titleShow' : true,
						'titleFormat' : function(title, currentArray, currentIndex, currentOpts) { return '<span id="fancybox-title-<?php echo $slideshow['fb_title_position']; ?>">Image ' + (currentIndex + 1) + ' / ' + currentArray.length + (title.length ? ' &nbsp; ' + title : '') + '</span>'; },
					<?php elseif ( $slideshow['fb_title_show'] and ! $slideshow['fb_title_counter_show'] ) : ?>
						'titleShow' : true,
						'titleFormat' : function(title, currentArray, currentIndex, currentOpts) { return '<span id="fancybox-title-<?php echo $slideshow['fb_title_position']; ?>">' + (title.length ? title : '') + '</span>'; },
					<?php elseif ( ! $slideshow['fb_title_show'] and $slideshow['fb_title_counter_show'] ) : ?>
						'titleShow' : true,
						'titleFormat' : function(title, currentArray, currentIndex, currentOpts) { return '<span id="fancybox-title-<?php echo $slideshow['fb_title_position']; ?>">Image ' + (currentIndex + 1) + ' / ' + currentArray.length + '</span>'; },
					<?php else : ?>
						'titleShow' : false,
					<?php endif; ?>
					});
				<?php } ?>
			});
			
			} )( jQuery );
		</script>
		<?php
		
		$fancybox_elements_path = wp_make_link_relative( plugins_url( 'dependencies/fancybox/', __FILE__ ) );
		if ( 'none' !== $_rps_image_gallery['fb_title_align'] ) :
			echo '<style type="text/css">#fancybox-title{text-align:' . $_rps_image_gallery['fb_title_align'] . ' !important;}</style>';		
		endif;
		
		?>
		<!--[if lt IE 7]>
		<style type="text/css">
			/* IE6 */
			
			.fancybox-ie6 #fancybox-close { background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_close.png', sizingMethod='scale'); }
			
			.fancybox-ie6 #fancybox-left-ico { background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_nav_left.png', sizingMethod='scale'); }
			.fancybox-ie6 #fancybox-right-ico { background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_nav_right.png', sizingMethod='scale'); }
			
			.fancybox-ie6 #fancybox-title-over { background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_title_over.png', sizingMethod='scale'); zoom: 1; }
			.fancybox-ie6 #fancybox-title-float-left { background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_title_left.png', sizingMethod='scale'); }
			.fancybox-ie6 #fancybox-title-float-main { background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_title_main.png', sizingMethod='scale'); }
			.fancybox-ie6 #fancybox-title-float-right { background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_title_right.png', sizingMethod='scale'); }
			
			.fancybox-ie6 #fancybox-bg-w, .fancybox-ie6 #fancybox-bg-e, .fancybox-ie6 #fancybox-left, .fancybox-ie6 #fancybox-right, #fancybox-hide-sel-frame {
				height: expression(this.parentNode.clientHeight + "px");
			}
			
			#fancybox-loading.fancybox-ie6 {
				position: absolute; margin-top: 0;
				top: expression( (-20 + (document.documentElement.clientHeight ? document.documentElement.clientHeight/2 : document.body.clientHeight/2 ) + ( ignoreMe = document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop )) + 'px');
			}
			
			#fancybox-loading.fancybox-ie6 div	{ background: transparent; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_loading.png', sizingMethod='scale'); }
		</style>
		<![endif]-->
		<!--[if lte IE 8]>
		<style type="text/css">
			/* IE6, IE7, IE8 */
			
			.fancybox-ie .fancybox-bg { background: transparent !important; }
			
			.fancybox-ie #fancybox-bg-n { filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_shadow_n.png', sizingMethod='scale'); }
			.fancybox-ie #fancybox-bg-ne { filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_shadow_ne.png', sizingMethod='scale'); }
			.fancybox-ie #fancybox-bg-e { filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_shadow_e.png', sizingMethod='scale'); }
			.fancybox-ie #fancybox-bg-se { filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_shadow_se.png', sizingMethod='scale'); }
			.fancybox-ie #fancybox-bg-s { filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_shadow_s.png', sizingMethod='scale'); }
			.fancybox-ie #fancybox-bg-sw { filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_shadow_sw.png', sizingMethod='scale'); }
			.fancybox-ie #fancybox-bg-w { filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_shadow_w.png', sizingMethod='scale'); }
			.fancybox-ie #fancybox-bg-nw { filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $fancybox_elements_path; ?>fancy_shadow_nw.png', sizingMethod='scale'); }
		</style>
		<![endif]-->
	<?php }
			
	private $slideshows = array();
	
}

	if ( ! isset( $rps_image_gallery ) ) $rps_image_gallery = new RPS_Image_Gallery;
	
	/**
	 * Callback that generates the 'size' and 'size_large' select menus.
	 * Compiles all registered image slugs and dimensions.
	 *
	 * @return string Select menu and optional description div.
	 * @since 1.2.24
	 */
	function _rps_image_gallery_custom_field_image_sizes( $field, $value ) {
		global $_wp_additional_image_sizes;
		$image_size_slugs = get_intermediate_image_sizes(); // array of image size slugs
		$image_sizes = array();

		foreach ( $image_size_slugs as $image_size ) :
					
			if ( in_array( $image_size, array( 'thumbnail', 'medium', 'large' ) ) ) :
				
				$width = get_option( $image_size . '_size_w' );
				$height = get_option( $image_size . '_size_h' );
				
			else :
			
				$width = $_wp_additional_image_sizes[$image_size]['width'];
				$height = $_wp_additional_image_sizes[$image_size]['height'];
			
			endif;
			
			$image_sizes[$image_size] = ucwords( str_replace( '-', ' ', $image_size ) ) . ' (' . $width . '&times;' . $height . ')';
		
		endforeach;

		$image_sizes['full'] = __( 'Original File', 'rps-image-gallery' );
		
		echo '<select  id="' . $field['id'] . '-select" data-placeholder="' . _x( 'Select an item', 'select menu data placeholder text for image size fields', 'rps-image-gallery' ) . '" name="_rps_image_gallery['. $field['id'] .']" class="redux-select-item " style="width: 40%;" rows="6">';
		echo '<option></option>';
		
		foreach ( $image_sizes as $option_value => $option_text ) :
		
			$selected = ( $option_value == $value ) ? ' selected="selected"' : '';
			echo '<option value="' . $option_value . '"' . $selected . '>' . $option_text . '</option>';
		
		endforeach;
		
		echo '</select>';
		echo ( ! empty( $field['desc'] ) ) ? '<div class="description field-desc">' . $field['desc'] . '</div>' : '';
	}

endif;

?>