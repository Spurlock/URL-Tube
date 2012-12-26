<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


$plugin_info = array(
						'pi_name'			=> 'URL Tube',
						'pi_version'		=> '1.1',
						'pi_author'			=> 'Mark Spurlock',
						'pi_author_url'		=> 'https://github.com/Spurlock',
						'pi_description'	=> 'Using just the URL from a YouTube or Vimeo video, this plugin can create video embeds and thumbnails, of whatever size you like, for use in your templates.',
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
		
		if ($v = $this->getVideoID($src)) {
			
			//Set video dimensions and selector attributes
			$dims = $this->getDimensions($this->EE->TMPL->fetch_param('width'),$this->EE->TMPL->fetch_param('height'));
			$h = $dims['height'];
			$w = $dims['width'];
			$sel = $this->makeSelectorString();
			$site = $this->getVideoSite($src);
			
			if ($site=='youtube')
				$this->return_data = "<iframe width='$w' height='$h' $sel src='http://www.youtube.com/embed/$v' frameborder='0' allowfullscreen></iframe>";
			else if ($site=='vimeo')
				$this->return_data = "<iframe src='http://player.vimeo.com/video/$v' width='$w' height='$h' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>";
		}
	}
	
	//Conveneince function for getting Video ID from template
	function id()
	{
		$s = $this->EE->TMPL->fetch_param('src');
		return $this->getVideoID($s);
	}
	
	//Generates a thumbnail image for the video (YouTube only)
	function thumbnail()
	{
		$s = $this->EE->TMPL->fetch_param('src');
		if($this->getVideoSite($s)!='youtube')
			return;
			
		$vid = $this->getVideoID($s);
		$dims = $this->getDimensions($this->EE->TMPL->fetch_param('width'), $this->EE->TMPL->fetch_param('height'));
		$h = $dims['height'];
		$w = $dims['width'];
		$sel = $this->makeSelectorString();
		
		return "<img src='http://img.youtube.com/vi/$vid/0.jpg' alt='Video Thumbnail' $sel height='$h' width='$w'/>";
	}
	
	private function getVideoHost($src)
	{
		$segs = parse_url($src);		
		if (!isset($segs['host'])) //Die if there is no host in passed URL.
			return false;
			
		return $segs['host'];	
	}
	
	private function getVideoSite($src)
	{
		$youtube_hosts	= array('youtube.com', 'www.youtube.com', 'youtu.be');
		$vimeo_hosts 	= array('vimeo.com', 'www.vimeo.com');
		
		$host = $this->getVideoHost($src);
		
		if (in_array($host, $youtube_hosts)) {
			return 'youtube';
		} else if (in_array($host, $vimeo_hosts)) {
			return 'vimeo';
		} else {
			return false;
		}
	}
	
	//Fetch the Video ID from any Youtube/Vimeo URL
	private function getVideoID($src)
	{
		$site = $this->getVideoSite($src);
		$segs = parse_url($src);		
		if(!isset($segs['host'])) //Die if there is no host in passed URL.
			return false;
			
		$host = $segs['host'];
		$path = $segs['path'];
		$vid = NULL;
		
		if ($site=='youtube') {
			if ($host=='youtu.be' && $path) {
				//Extract from share URL
				$vid = substr($path,1);
			} else if (($host=='youtube.com' || $host=='www.youtube.com')) {
				
				
				if (isset($segs['query'])) {
					//Extract from full URL
					parse_str($segs['query'], $query);
					$vid = $query['v'];
				} else {
					//Extract from embed URL
					$embedloc = strpos($path,"embed/");
					$vid = substr($path,$embedloc+6);
				}
			}
			
			//Validate and return Video ID
			if($vid && preg_match('/^[a-zA-Z0-9_\-]{11}$/',$vid)) {
				return $vid;
			} else {
				return false;
			}
			
		} else if ($site=='vimeo') {
			$chars = str_split($path);
			$vid = '';
			$id_started = false; //flag is set when we start finding numeric characters
			
			foreach($chars as $char) {
				if(preg_match('/^[0-9]{1}$/',$char)) {
					if($id_started) {
						$vid .= $char;
					}
					else {
						$vid = $char;
						$id_started = true;
					}
				} else {
					$id_started = false;
				}
			}
			
			if ($vid) {
				return $vid;
			} else {
				return false;
			}
		}
		
		
	}
	
	//Validate class and id attributes, return them in a string to be used on an html element
	private function makeSelectorString()
	{
		$class = $this->EE->TMPL->fetch_param('class');
		$id = $this->EE->TMPL->fetch_param('id');
		
		$selectors = "";
		if ($id && preg_match("/-?[_a-zA-Z]+[_a-zA-Z0-9-]*/", $id))
			$selectors .= "id='$id' ";
		if ($class && preg_match("/-?[_a-zA-Z]+[_a-zA-Z0-9-]*/", $class))
			$selectors .= "class='$class' ";
			
		return $selectors;
	}
	
	//Given some combination of set or unset height and width, determine the output dimensions
	private function getDimensions($w,$h)
	{
		if($h && $w) {
			//Height and width both set
			$h = intval($h);
			$w = intval($w);
		} else if($h) {
			//Height set, calculate width
			$h = intval($h);
			$w = ceil($h * 16/9);
		}
		else if($w) {
			//Width set, calculate height
			$w = intval($w);
			$h = ceil($w * 9/16);
		} else {
			//Fall back on defaults
			$w = 560;
			$h = 315;
		}
		return array("width"=>$w,"height"=>$h);
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
		
		Note that this feature ONLY SUPPORTS YOUTUBE VIDEOS, and cannot at this time be used with Vimeo.
		
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
		
		<?php
		$buffer = ob_get_contents();
	
		ob_end_clean(); 

		return $buffer;
	}

}