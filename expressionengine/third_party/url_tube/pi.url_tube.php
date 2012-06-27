<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


$plugin_info = array(
						'pi_name'			=> 'URL Tube',
						'pi_version'		=> '1.0',
						'pi_author'			=> 'Mark Spurlock',
						'pi_author_url'		=> 'https://github.com/Spurlock',
						'pi_description'	=> 'Using just the URL from a YouTube video, this plugin can create video embeds and thumbnails, of whatever size you like, for use in your templates.',
						'pi_usage'			=> URL_tube::usage()
					);

/**
 * URL_tube Class
 */

class URL_tube {

	var $return_data;
	
	/**
	 * Constructor
	 */
	function URL_tube()
	{
		$this->EE =& get_instance();
		$src = $this->EE->TMPL->fetch_param('src');
		
		if($v = $this->getVideoID($src))
		{
			//Set video dimensions
			$dims = $this->getDimensions($this->EE->TMPL->fetch_param('width'),$this->EE->TMPL->fetch_param('height'));
			$h = $dims['height'];
			$w = $dims['width'];		
			
			//Validate and add class and id attributes
			$class = $this->EE->TMPL->fetch_param('class');
			$id = $this->EE->TMPL->fetch_param('id');
			$selectors = "";
			if($id && preg_match("/-?[_a-zA-Z]+[_a-zA-Z0-9-]*/",$id))
				$selectors .= "id='$id' ";
			if($class && preg_match("/-?[_a-zA-Z]+[_a-zA-Z0-9-]*/",$class))
				$selectors .= "class='$class' ";
			
			$this->return_data = "<iframe width='$w' height='$h' $selectors src='http://www.youtube.com/embed/$v' frameborder='0' allowfullscreen></iframe>";
		}
	}
	
	function getDimensions($w,$h)
	{
		if($h && $w) //Height and width both set
		{
			$h = intval($h);
			$w = intval($w);
		}
		else if($h) //Height set, calculate width
		{
			$h = intval($h);
			$w = ceil($h * 16/9);
		}
		else if($w) //Width set, calculate height
		{
			$w = intval($w);
			$h = ceil($w * 9/16);
		}
		else //Fall back on defaults
		{
			$w = 560;
			$h = 315;
		}
		return array("width"=>$w,"height"=>$h);
	}
	
	//Conveneince function for getting Video ID from template
	function id()
	{
		$s = $this->EE->TMPL->fetch_param('src');
		return $this->getVideoID($s);
	}
	
	//Generates a thumbnail image for the video
	function thumbnail()
	{
		$s = $this->EE->TMPL->fetch_param('src');
		$vid = $this->getVideoID($s);
		$dims = $this->getDimensions($this->EE->TMPL->fetch_param('width'),$this->EE->TMPL->fetch_param('height'));
		$h = $dims['height'];
		$w = $dims['width'];
		
		return "<img src='http://img.youtube.com/vi/$vid/0.jpg' alt='Video Thumbnail' height='$h' width='$w'/>";
	}
	
	//Fetch the Video ID from any Youtube URL
	function getVideoID($str)
	{
		$segs = parse_url($str);		
		if(!isset($segs['host'])) //Die if there is no host in passed URL.
			return false;
			
		$host = $segs['host'];					
		$vid = NULL;
		
		if($host=='youtu.be' && isset($segs['path'])) //Extract from share URL
		{
			$vid = substr($segs['path'],1);
		}
		else if(($host=='youtube.com' || $host=='www.youtube.com') && isset($segs['query'])) //Extract from full URL
		{
			parse_str($segs['query'], $query);
			$vid = $query['v'];
		}
		//Validate and return Video ID
		if($vid && preg_match('/^[a-zA-Z0-9_\-]{11}$/',$vid))
			return $vid;
		return false;
	}
	
	/**
	 * Usage
	 *
	 * Plugin Usage
	 *
	 * @access	public
	 * @return	string
	 */
	function usage()
	{
		ob_start(); 
		?>
		
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

		<?php
		$buffer = ob_get_contents();
	
		ob_end_clean(); 

		return $buffer;
	}

}