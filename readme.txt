=== Plugin Name ===
Contributors: Red Pixel Studios
Donate link: http://redpixel.com/
Tags: gallery, images, slideshow, fancybox
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.1.1

The RPS Image Gallery plugin takes over where the WordPress gallery leaves off by adding slideshow and advanced linking capabilities.

== Description ==

The RPS Image Gallery plugin takes over where the WordPress gallery leaves off by adding slideshow and advanced linking capabilities.

The plugin changes the way the gallery is output by using an unordered list instead of using a definition list for each image. This offers several advantages. There are fewer lines of code per gallery for simplified styling and better efficiency. From an accessibility standpoint, the unordered list is better suited to this type of content than is the definition list. It enables a gallery that will automatically wrap to any given container width.

In addition, any image in the gallery can either invoke a slideshow or link to another page. The link is specified in the Gallery Link field within the image’s Edit Media screen. When an image that has a Gallery Link is clicked, the user will be directed to that location. Images that link elsewhere are automatically excluded from the slideshow.

= Features =
* Specify whether clicking an image will invoke a slideshow or link to a page</li>
* Support for multiple galleries on a single page</li>
* Optionally display the image captions</li>
* Define sort order of the gallery through the standard familiar interface</li>
* Uses an unordered list instead of a definition list</li>
* Only loads required scripts when shortcode is invoked</li>
* Overrides the default WordPress gallery shortcode but includes all of its options</li>

== Installation ==

1. Upload the <code>rps-image-gallery</code> directory and its containing files to the <code>/wp-content/plugins/</code> directory.</li>
2. Activate the plugin through the "Plugins" menu in WordPress.</li>

== Frequently Asked Questions ==

= What happens if I deactivate the plugin after having setup galleries with it active? =

Nothing bad. The default WordPress gallery behavior will take over and any shortcode attributes that are specific to the plugin are ignored.

= What attributes are added to the gallery shortcode by the plugin? =

* size_large (default='large') - the size of the image that should be displayed in the slideshow view such as 'medium' or 'large'
* group_name (default='rps-image-group') - the class of the gallery group that is used to determine which images belong to the gallery slideshow
* container (default='div', allowed='div','p','span') - the overall container for the gallery.
* caption (default='false') - whether or not to show the caption under the images in the gallery grid view.
* slideshow (default='true') - whether or not to invoke the fancybox slideshow viewer when an image without a Gallery Link value is clicked.

= Why is the default output for the gallery grid set to use an unordered list rather than a definition list?

The unordered list output is more flexible when used with variable-width layouts since it does not include a break at the end of each row. The default WordPress Gallery output can be achieved by adding the following attributes and values to the shortcode:
* itemtag='dl'
* icontag='dt'

= What will display if I set the caption attribute to 'true' but some of my images don't have captions? =

The plugin will fallback to the image title if a caption is not defined for the image.

= Is the image description needed? =

No. We took the approach that the description field should be used to store information about the image that likely would not be seen by the site visitors but could be useful for admins when searching for the image.

== Screenshots ==

1. A field named "Gallery Link" is added to the Edit Media screen for images so that an admin can force the image to link to a post or page on their site or a page on another site.
2. The familiar WordPress gallery object appears within the Visual editor, just as before the installation of the plugin.
3. The default output for the gallery is the flexible unordered list.
4. Clicking a gallery image opens the fancybox slideshow viewer or directs the site visitor to a page specified in the Gallery Link field.

== Changelog ==

= 1.1.1 =
* First official release version.
