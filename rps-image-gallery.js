;jQuery.noConflict();

( function( $, window, rps_img_gallery_rel ) {
var document = window.document;
$( document ).ready( function() {
    $("a[rel="+rps_img_gallery_rel+"]").fancybox({
		'transitionIn' : 'none',
		'transitionOut' : 'none',
		'titlePosition' : 'over',
		'titleFormat' : function(title, currentArray, currentIndex, currentOpts) {
			return '<span id="fancybox-title-over">Image ' + (currentIndex + 1) + ' / ' + currentArray.length + (title.length ? ' &nbsp; ' + title : '') + '</span>';
		}
	});
});

} )( jQuery, window, rps_img_gallery_rel );