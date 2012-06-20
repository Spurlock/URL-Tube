URL-Tube
========

Using just the URL from a YouTube video, this plugin can create video embeds and thumbnails, of whatever size you like, for use in your templates.

///////////////
EMBEDDING VIDEO
///////////////

Just use the following tag, and pass the URL of the Youtube video as "src". You can use the shortened Share URL or a full YouTube video page URL:
		
{exp:url_tube src="http://youtu.be/nU_cOAutCcs"}
	
OR...		
		
{exp:url_tube src="http://www.youtube.com/watch?v=nU_cOAutCcs&feature=youtu.be"}
		
And of course you can set up a custom field to contain either of these URLs, and pull the requested video from that:
		
{exp:url_tube src="{video_field}"}
		
You can optionally pass in width and/or height. If neither is passed, the plugin uses the Youtube default size of 560 x 315. If only one is passed, the plugin calculates the other so as to make the aspect ratio 16/9.		
		
{exp:url_tube src="http://youtu.be/nU_cOAutCcs" width="585" height="329"}
		
////////////////////////
THUMBNAIL PREVIEW IMAGES
////////////////////////
		
If you don't want to embed the video itself, but only output a thumbnail image for it, you can use the thumbnail tag:
		
{exp:url_tube:thumbnail src="http://youtu.be/nU_cOAutCcs" width="585" height="329"}
		
////////
VIDEO ID
////////
		
If all you want is to extract the Video's ID, just use the ID tag (height and width are ignored):
		
{exp:url_tube:id src="http://youtu.be/nU_cOAutCcs"}

The above code will simply output "nU_cOAutCcs"