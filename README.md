URL-Tube
========

Using just the URL from a YouTube/Vimeo video, this plugin can create video embeds and thumbnails, of whatever size you like, for use in your templates.

***************
EMBEDDING VIDEO
***************
		
Just use the following tag, and pass the URL of the Youtube/Vimeo video as "src". Any valid URL format should work:
		
{exp:url_tube src="http://youtu.be/nU_cOAutCcs"}
		
OR...		
		
{exp:url_tube src="http://www.youtube.com/watch?v=nU_cOAutCcs&feature=youtu.be"}
		
And of course you can set up a custom field to contain either of these URLs, and pull the requested video from that:
		
{exp:url_tube src="{video_field}"}
		
You can optionally pass in width and/or height. If neither is passed, the plugin uses 560 x 315 as default. If only one is passed, the plugin calculates the other so as to make the aspect ratio 16/9.		
		
{exp:url_tube src="http://youtu.be/nU_cOAutCcs" width="585" height="329"}
		
************************
THUMBNAIL PREVIEW IMAGES
************************
		
If you don't want to embed the video itself, but only output a thumbnail image for it, you can use the thumbnail tag:

{exp:url_tube:thumbnail src="http://youtu.be/nU_cOAutCcs" width="585" height="329"}

PERFORMANCE NOTE: This tag works best with YouTube videos. With Vimeo content, using too many of these tags can hurt your page's load time.
		
********
VIDEO ID
********
		
If all you want is to extract the Video's ID, just use the ID tag (height and width are ignored):
		
{exp:url_tube:id src="http://youtu.be/nU_cOAutCcs"}
		
The above code will simply output "nU_cOAutCcs"

*******
STYLING
*******
		
You can assign class and id attributes to the iframe (when embedding video) or image (when creating thumbnails) using the "class" and/or "id" parameters:
		
{exp:url_tube src="http://youtu.be/nU_cOAutCcs" class="small" id="main_vid"}

*************
HTTPS SUPPORT
*************
        
URL Tube will work with http and https protocols. By default, it will use whichever protocol is passed in the "src" parameter (and default to http 
if it doesn't find one). If you want to force URL tube to always use one or the other, you can do so using the "ssl" parameter. For example, the 
following tag will embed the video using https, even though http was given in the URL:
        
{exp:url_tube src="http://youtu.be/nU_cOAutCcs" ssl="yes"}

*************
EMBED OPTIONS
*************

YouTube and Vimeo each support a set of options that can be added to the embedded video. For example, YouTube videos typically display "related videos" at 
the end of your clip, but this behavior can be turned off by adding "?rel=0" to the "src" attribute. To use these options with URL Tube, simply pass them 
in as parameters to your url_tube tag:

{exp:url_tube src="http://youtu.be/nU_cOAutCcs" rel="0" theme="light"}

Note that Vimeo and YouTube support different sets of options, with only a small number that will work for both providers (also, both providers have a "color" 
option, but they use it differently from one another). URL Tube will safely ignore any parameters that don't apply for the video's provider.

List of YouTube parameters: https://developers.google.com/youtube/player_parameters
List of Vimeo parameters: http://developer.vimeo.com/player/embedding