=== RPS Image Gallery ===
Contributors: redpixelstudios
Donate link: http://redpixel.com/donate
Tags: gallery, images, slideshow, fancybox
Requires at least: 3.6
Tested up to: 3.8.1
Stable tag: 1.2.26
License: GPL3

The RPS Image Gallery plugin takes over where the WordPress gallery leaves off by adding slideshow and advanced linking capabilities.

== Description ==

The RPS Image Gallery plugin takes over where the WordPress gallery leaves off by adding slideshow and advanced linking capabilities.

The plugin changes the way the gallery is output by using an unordered list instead of using a definition list for each image. This offers several advantages. There are fewer lines of code per gallery for simplified styling and better efficiency. From an accessibility standpoint, the unordered list is better suited to this type of content than is the definition list. It enables a gallery that will automatically wrap to any given container width.

In addition, any image in the gallery can either invoke a slideshow or link to another page. The link is specified in the Gallery Link field within the image's Edit Media screen as is the link target. When an image that has a Gallery Link is clicked, the user will be directed to that location. Images that link elsewhere are automatically excluded from the slideshow.

There are many other options that allow you to modify the gallery and slideshow output and even a feature that combines attachments from multiple posts or pages into a single gallery.

= Features =
* Set gallery shortcode defaults. (new in version 1.2.24, requires [Redux Framework](http://wordpress.org/plugins/redux-framework/) plugin)
* Combine and sort attachments from multiple posts into a single gallery.
* Specify whether clicking an image will invoke a slideshow or link to a page
* Set the target for the image link
* Support for multiple galleries on a single page
* Optionally display the image title and caption or description in gallery view
* Define sort order of the gallery through the standard familiar interface
* Uses an unordered list instead of a definition list
* Only loads required scripts when shortcode is invoked
* Overrides the default [WordPress Gallery](http://codex.wordpress.org/Gallery_Shortcode "Gallery Shortcode") shortcode but includes all of its options
* Optionally display EXIF data in the gallery and/or slideshow.
* Optionally display gallery thumbnails as background images or standard images.

== Installation ==

1. Upload the <code>rps-image-gallery</code> directory and its containing files to the <code>/wp-content/plugins/</code> directory.
2. Activate the plugin through the "Plugins" menu in WordPress.

== Frequently Asked Questions ==
= Where is the Settings page? =

You must have the [Redux Framework](http://wordpress.org/plugins/redux-framework/) plugin installed in order to edit the default options. Once activated you will see an Image Gallery link show under the WordPress admin Settings tab.

= Can I override default settings per gallery? =

Yes. Any shortcode attribute will override the gallery default settings.

= Where can I find a comprehensive list of shortcode attributes? =

Have a look at the "Other Notes" tab.

= How do I add a gallery? =

You can refer to the [gallery instructions](http://en.support.wordpress.com/images/gallery/#adding-a-gallery "Adding a Gallery") posted at WordPress.com support.

= What happens if I deactivate the plugin after having setup galleries with it active? =

Nothing bad. The default [WordPress Gallery](http://codex.wordpress.org/Gallery_Shortcode "Gallery Shortcode") behavior will take over and any shortcode attributes that are specific to RPS Image Gallery are ignored.

= How do I define the sizes of the images in the gallery and the slideshow? =

You may use any of the standard image sizes including "thumbnail", "medium", "large", "full" and any other additional image size registered by the active theme.

<code>
[gallery size="thumbnail" size_large="large"]
</code>

= Where do I set the link and target for each image? =

The fields "Gallery Link URL" and "Gallery Link Target" on the Edit Media screen allow you to specify the settings for each image (see screenshots).

= What attributes of the WordPress Gallery shortcode have been modified? =

* link - By default the only two options are "file" and "permalink". We have added an option of "none" in order to prevent gallery thumbnail images from linking anywhere if slideshow is also set to "false" (since version 1.2.2). An example of this approach is:

<code>
[gallery link="none" slideshow="false"]
</code>

* id - By default you can use the id to display a gallery that exists on another post/page. We have added the option to pass along a comma delimited list of ids so that a single gallery can be created from multiple galleries. The 'orderby' and 'order' arguments are applied after the attachments are combined. The following example will combine the image attachments from post 321 and 455 into a single gallery sorted alphabetically by title:

<code>
[gallery id="321,455" orderby="title" order="asc"]
</code>

**Notice for WordPress 3.5+ Users:** When the "ids" attribute and "id" attribute are present in the same shortcode, the "ids" attribute will be used to determine which images should be included and what order they will be in.

= What will display if I set caption_source to 'caption' or 'description' but some of my images don't have either? =

The plugin will fall back to the image title if a caption or description is not defined for the image.

= Can I use the caption or the description? =

Yes. You will need to select which one you want to use, but the approach is simple:

<code>
[gallery caption="true" caption_source="caption"]
[gallery caption="true" caption_source="description"]
</code>

= How do I add multiple galleries to the same page? =

Though the WordPress Gallery editor only allows you to manage a single gallery, you can combine galleries from multiple post/pages onto a single page. To do this, create a post/page for each gallery that you want to include. Record the post IDs for the gallery pages, then add a gallery shortcode for each of them on the post/page that will contain them. For example:

<code>
[gallery id="134" group_name="group1"]
[gallery id="159" group_name="group2"]
</code>

This code will pull the gallery from post 134 and 159 and display them one after the other. The group name attribute allows for each gallery to display in a separate slideshow. Excluding the group name or making it the same will cause the slideshow to be contiguous between the galleries.

Alternatively, you can create multiple galleries from the attached images on a post/page. To do so, get a list of the image (attachment) IDs that you want for each gallery, then pass them to the gallery shortcode in the "include" attribute like so:

<code>
[gallery include="10,11,24,87"]
[gallery include="7,16,23,45"]
</code>

Keep in mind that all of the included images must be attached to the post/page to be successfully added to the gallery.

= How do I combine multiple galleries? =

Since version 2.0.9, all you need to do to combine multiple galleries is pass along a comma delimited list of ids like so:

<code>
[gallery id="134,159" orderby="title"]
</code>

This code will take all of the images from the two galleries, merge and order them by the image title.

= What version of fancybox is being used and are there plans to support fancybox2? =

fancybox version 1.3.4 is included with this plugin and there are plans to support fancybox2 in a future release.

= How do I display EXIF data in the gallery and/or slideshow? =

You can make the EXIF data show by adding the exif_locations argument to the shortcode like so.

<code>
[gallery exif="true" exif_locations="slideshow"]
</code>

= How do I control which EXIF fields display? =

The EXIF fields that can be displayed are "camera", "aperture", "focal_length", "iso", "shutter_speed", "title", "caption", "credit", "copyright" and "created_timestamp". The order you enter the fields is reflected in the output.

<code>
[gallery exif="true" exif_locations="slideshow" exif_fields="aperture,focal_length,iso,shutter_speed"]
</code>

== Other Notes ==
What follows is a comprehensive list of attributes for the gallery shortcode when RPS Image Gallery is active.

= id =
The post IDs containing a gallery to include.

* '' - single post ID or comma separated list of post IDs (default)

= ids =
The image IDs to display in the gallery.

* '' - single image ID or comma separated list of image IDs (default)

= container =
The container for the gallery.

* 'div' (default)
* 'span'

= columns =
How many columns to use for the gallery view.

* '3' - range is 1 to 9 (default)

= align =
Affects the heading(title), caption and the last row of images when there are fewer images in the row than number of columns.

* 'left' (default)
* 'center'
* 'right'

= size =
The size of the image that should be displayed in the gallery view. It can be any of the standard image sizes including any registered by the theme.

* 'thumbnail' (default)
* 'medium'
* 'large'
* 'full'

= size_large =
The size of the image that should be displayed in the slideshow view. It can be any of the standard image sizes including any registered by the theme.

* 'thumbnail'
* 'medium'
* 'large' (default)
* 'full'

= orderby =
How to sort the images. It is ignored if a list of image IDs is included in the shortcode.

* 'menu_order' (default)
* 'title'
* 'post_date'
* 'rand'
* 'ID'
* 'post__in'

= order =
How to order the images. It is ignored if a list of image IDs is included in the shortcode.

* 'ASC' (default)
* 'DESC'

= heading =
Display the image title in the gallery and slideshow views.

* 'true'
* 'false' (default)

= headingtag =
The tag that should be used to wrap the image heading (title).

* 'h1'
* 'h2' (default)
* 'h3'
* 'h4'
* 'h5'
* 'h6'

= caption =
Display the image caption or description under the images in the gallery grid view.

* 'true'
* 'false' (default)

= caption_source =
Define where the text presented as the caption should be sourced.

* 'caption' (default)
* 'description'

= caption_align =
Specify alignment of the caption text presented in the gallery grid.

* 'left' (default)
* 'center'
* 'right'

= link =
Where to get the URL to direct a user when clicking/tapping an image. Only has an effect if Slideshow is set to false.
* 'permalink' (default)
* 'file'
* 'none'

= slideshow =
Invoke the slideshow (fancybox) viewer when an image without a Gallery Link URL value is clicked.

* 'true' (default)
* 'false'

= background_thumbnails =
Display the gallery thumbnail images as backgrounds or standard images.

* 'true'
* 'false' (default)

= exif = (since 1.2.24)
Show the EXIF image data.

* 'true'
* 'false' (default)

= exif_locations =
Where to show the EXIF data associated with the image.

* 'gallery'
* 'slideshow' (default)

= exif_fields =
What EXIF fields to display and in what order.

* 'camera,aperture,focal_length,iso,shutter_speed,title,caption,credit,copyright,created_timestamp' (default)

= include =
Comma separated attachment IDs to display. Cannot be used with exclude.

* '' (default)

= exclude =
Comma separated attachment IDs to display. Cannot be used with include.

* '' (default)

= group_name =
The class of the gallery group which determines what images belong to the gallery slideshow.

* 'rps-image-group' (default)

= fb_title_show =
Show the title, caption or description, and EXIF data in the slideshow view.

* 'true' (default)
* 'false'

= fb_title_position =
The position of the title, caption or description, and EXIF data in relation to the image in the slideshow.

* 'over' (default)
* 'outside'
* 'inside'

= fb_title_align =
The alignment of the text in the slideshow title.

* 'none' (default)
* 'left'
* 'center'
* 'right'

= fb_show_close_button =
Show the close button in the upper-right corner of the slideshow (clicking outside the slideshow always closes it).

* 'true' (default)
* 'false'

= fb_transition_in =
The effect that should be used when the slideshow is opened.

* 'none' (default)
* 'elastic'
* 'fade'

= fb_transition_out =
The effect that should be used when the slideshow is closed.

* 'none' (default)
* 'elastic'
* 'fade'

= fb_speed_in =
Time in milliseconds of the fade and transition when the slideshow is opened.

* '300' - minimum of 100 and maximum of 1000 (default)

= fb_speed_out =
Time in milliseconds of the fade and transition when the slideshow is closed.

* '300' - minimum of 100 and maximum of 1000 (default)

= fb_title_counter_show =
Display the image counter in the slideshow (i.e. "Image 1/10).

* 'true' (default)
* 'false'

= fb_cyclic =
Make the slideshow start from the beginning once the end is reached.

* 'true' (default)
* 'false'

= fb_center_on_scroll =
Center the image on the screen while scrolling the page.

* 'true' (default)
* 'false'

== Screenshots ==

1. Fields named "Gallery Link URL" and "Gallery Link Target" appear on the Edit Media screen for images so that an admin can force the image to link to a post or page on their site or a page on another site.
1. The familiar WordPress Gallery object appears within the Visual editor, just as before the installation of the plugin.
1. The default output for the gallery is the flexible unordered list.
1. Clicking a gallery image opens the slideshow(fancybox) viewer or directs the site visitor to a page specified in the Gallery Link field.

== Changelog ==
= 1.2.26 =
* Added 'full' as image size option in settings.
* Updated documentation.

= 1.2.25 =
* Maintenance release.

= 1.2.24 =
* Added settings page to specify gallery defaults.
* Removed 'p' as option for container since it conflicts with the_content filter.
* Added 'caption_source' attribute to set where caption is sourced.
* Added 'caption_align' attribute to set text alignment of the caption in the gallery.
* Added 'fb_title_align' attribute to set text alignment of the caption in the slideshow.

= 1.2.23 =
* Added option to use caption or the description.
* Fixed path issue for fancybox elements called by IE6 through IE8.

= 1.2.22 =
* Added option to display EXIF image data.
* Added option to display gallery thumbnails as backgrounds instead of images.

= 1.2.21 =
* Maintenance release to correct jQuery noConflict mode setting.

= 1.2.20 =
* Uses jQuery version included with WordPress.

= 1.2.19 =
* Includes the necessary version of jQuery for compatibility with higher WordPress versions. Packaged in a no-conflict wrapper to avoid collisions with other jQuery versions.

= 1.2.18 =
* Added option to show fb counter without title.
* Applies proper class on fancybox title based on title position.

= 1.2.17 =
* Fixed a bug that caused exclude to not work properly in certain situations.

= 1.2.16 =
* Fixed a bug introduced by v1.2.15 that messed with the post interface.

= 1.2.15 =
* Fixed a minor bug where the include attribute no longer functioned properly.

= 1.2.14 =
* Unique post gallery images no longer merge into a single slideshow on archive pages.

= 1.2.13 =
* Added pass through arguments for cyclic and centerOnScroll fancybox options.

= 1.2.12 =
* Maintenance release to eliminate warning message being logged when sorting single gallery.

= 1.2.11 =
* Added support for ids attribute in gallery shortcode.
* Reordering merged gallieries is now possible via the default Gallery admin interface.

= 1.2.10 =
* Made column width definitions in CSS more precise for layouts with tight tolerances.

= 1.2.9 =
* Added option to combine attachments from multiple pages into a single gallery while respecting orderby and order arguments.

= 1.2.8 =
* Added classes to indicate beginning and end of gallery rows.
* Added shortcode option to specify gallery alignment.
* Removed definition list styles that were no longer needed.

= 1.2.7 =
* Added title attribute for image in grid view when no link is present.
* Added option to turn image heading (title) on or off in gallery and slideshow views.
* Added option to specify the heading tag from h2-h6.
* Added option to turn the slideshow image counter on or off.
* Removed support for definition list (dl) structure and removed shortcode arguments including itemtag, icontag and captiontag.

= 1.2.6 =
* Added target parameter to gallery link.
* Modified CSS to eliminate extra horizontal space between images in gallery grid due to inline-block styling of list items.

= 1.2.5 =
* Eliminated possibility of HTML markup appearing in title attribute.

= 1.2.4 =
* Added support for HTML markup in the image caption.

= 1.2.3 =
* Modified z-index of fancybox overlay and wrap so that they appear above most theme elements.

= 1.2.2 =
* Corrected an issue with the fancybox CSS that resulted in 404 errors for some supporting graphical elements.
* Added a shortcode attribute option for "link" so that it can now be set to "none".

= 1.2 =
* Added capability to pass fancybox settings through shortcode attributes.
* Changed the default slideshow behavior to be cyclic (loop).
* Corrected an issue preventing slideshow for multiple galleries.

= 1.1.1 =
* First official release version.

== Upgrade Notice ==
= 1.2.26 =
* Added option to use original size images.

= 1.2.25 =
* Fixed setting to control showing and hiding of EXIF data.
* Public release of settings page for users to specify gallery defaults.

= 1.2.24 =
* Added settings page for users to specify gallery defaults.

= 1.2.23 =
* Option to use image caption or description. Fixed 404 errors when browsing with IE6 through IE8.

= 1.2.22 =
* Optionally display EXIF data in gallery and slideshow. Force gallery thumbnails to display as background images for better styling flexibility.

= 1.2.21 =
* Fixes issue that affects certain themes which load javacript in footer. 

= 1.2.20 =
* Now uses jQuery included with WordPress. Relies on jQuery Migrate that also ships with WordPress 3.6.

= 1.2.19 =
* Fixes a jQuery compatibility issue with WordPress 3.6.

= 1.2.18 =
* Better title and counter handling in slideshow.

= 1.2.17 =
* Fixed a bug that caused exclude to not work properly in certain situations.

= 1.2.16 =
* This is an important update that fixes a bug caused by v1.2.15 which interferes with the post interface.

= 1.2.15 =
* This update fixes a minor bug with the include attribute.

= 1.2.14 =
* Improved operation of slideshow when multiple galleries appear on archive pages.

= 1.2.13 =
* Expanded passthrough options for fancybox.

= 1.2.12 =
* Fixed issue that would generate a warning when sorting gallery.

= 1.2.11 =
* Compatibility with WordPress 3.5 ordering and image inclusion standards.

= 1.2.10 =
* Updated default widths for columns.

= 1.2.9 =
* Added option to combine attachments from multiple posts in one gallery.

= 1.2.8 =
* Set default gallery alignment to left with option to override in shortcode.
* Added gallery row classes to allow easier overriding of default margins.

= 1.2.7 =
* Added option to display image title above caption.
* Removed support for definition list (dl) structure.

= 1.2.6 =
* Added support for setting target of gallery link.
* Corrected horizontal image spacing issue in gallery grid view.

= 1.2.5 =
* Fixed bug that allowed HTML markup to appear in title attribute.

= 1.2.4 =
* HTML markup in image captions is now allowed.

= 1.2.3 =
* Fix for users of Twenty Eleven theme and most other themes that display elements overlapping the slideshow.

= 1.2.2 =
* Corrects 404 errors generated by the fancybox CSS when Internet Explorer is the active browser.
* Allow "none" as an option for the link shortcode attribute.

= 1.2 =
* Specify slideshow behavior.
* Corrects an issue whereby only the last gallery on the page could trigger a slideshow.
