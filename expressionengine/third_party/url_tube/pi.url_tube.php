<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');


$plugin_info = array(
    'pi_name' => 'URL Tube',
    'pi_version' => '1.3',
    'pi_author' => 'Mark Spurlock',
    'pi_author_url' => 'https://github.com/Spurlock',
    'pi_description' => 'Using just the URL from a YouTube or Vimeo video, this plugin can create 
        video embeds and thumbnails, of whatever size you like, for use in your templates.',
    'pi_usage' => URL_tube::usage()
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
        $this->EE = & get_instance();
        $src = $this->EE->TMPL->fetch_param('src');

        if ($video_id = $this->getVideoID($src)) {

            //Set video dimensions and selector attributes
            list($w, $h) = $this->getDimensions($this->EE->TMPL->fetch_param('width'), $this->EE->TMPL->fetch_param('height'));
            $sel = $this->makeSelectorString();
            $site = $this->getVideoSite($src);
            $query_string = $this->getQueryString($site);
            $protocol = $this->getProtocol($this->EE->TMPL->fetch_param('ssl'), $src);

            //output markup to the template
            if ($site == 'youtube') {
                
                $this->return_data = "<iframe width='$w' height='$h' $sel src='$protocol://www.youtube.com/embed/$video_id$query_string' 
                    frameborder='0' allowfullscreen></iframe>";
                
            } else if ($site == 'vimeo') {
                
                $this->return_data = "<iframe src='$protocol://player.vimeo.com/video/$video_id$query_string' width='$w' height='$h' 
                    frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>";
            }
        }
    }

    /**
     * Conveneince function for getting Video ID from template
     */
    function id() 
    {
        $s = $this->EE->TMPL->fetch_param('src');
        return $this->getVideoID($s);
    }

    /**
     * Generates a thumbnail image for the video
     */
    function thumbnail() 
    {
        $src = $this->EE->TMPL->fetch_param('src');

        $vid = $this->getVideoID($src);
        list($width, $height) = $this->getDimensions($this->EE->TMPL->fetch_param('width'), $this->EE->TMPL->fetch_param('height'));
        $sel = $this->makeSelectorString();
     
        $site = $this->getVideoSite($src);
        $url = ($site=='youtube') ? "http://img.youtube.com/vi/$vid/0.jpg" : $this->getVimeoThumbnailUrl($vid, $width);
        
        return $url ? "<img src='$url' alt='Video Thumbnail' $sel height='$height' width='$width'/>" : null;
    }
    
    /**
     * Returns the correct thumbnail URL from the Vimeo API, or false on failure
     */
    private function getVimeoThumbnailUrl($video_id, $width)
    {
        $api_response = @file_get_contents("http://vimeo.com/api/v2/video/$video_id.php");
        $video_data = @unserialize(trim($api_response));

        //if the response looks right, decide which size thumbnail to use
        if (isset($video_data[0])) {
            if ( $width<=100 ) {
                return $video_data[0]['thumbnail_small'];
            } elseif ( $width<=200 ) {
                return $video_data[0]['thumbnail_medium'];
            } 
            return $video_data[0]['thumbnail_large'];
        }
        return false;
    }
    
    /**
     * Decides what protocol to use (http or https) and returns the protocol portion of the URL
     */
    private function getProtocol($ssl, $src) 
    {
        //if the ssl flag is set, go with that
        if ($ssl=="yes" || $ssl=="on") {
            return "https";
        } else if ($ssl=="no" || $ssl=="off") {
            return "http";
        }
        
        //otherwise look for protocol in the url, falling back on http if it's not found.
        $segs = parse_url($src);
        if($segs['scheme']=="http" || $segs['scheme']=="https"){
            return $segs['scheme'];
        }
        return "http";
    }

    /**
     * Retrieves the host from the video URL
     */
    private function getVideoHost($src) 
    {
        $segs = parse_url($src);
        if (!isset($segs['host'])) //Die if there is no host in passed URL.
            return false;

        return $segs['host'];
    }

    /**
     * Gets video site from video URL
     */
    private function getVideoSite($src) 
    {
        $youtube_hosts = array('youtube.com', 'www.youtube.com', 'youtu.be');
        $vimeo_hosts = array('vimeo.com', 'www.vimeo.com');

        $host = $this->getVideoHost($src);

        if (in_array($host, $youtube_hosts)) {
            return 'youtube';
        } else if (in_array($host, $vimeo_hosts)) {
            return 'vimeo';
        } else {
            return false;
        }
    }

    /**
     * Fetch the Video ID from any Youtube/Vimeo URL
     */
    private function getVideoID($src) 
    {
        $site = $this->getVideoSite($src);
        $segs = parse_url($src);
        if (empty($segs['host'])) //Die if there is no host in passed URL.
            return false;

        $host = $segs['host'];
        $path = $segs['path'];
        $vid = NULL;

        if ($site == 'youtube') {
            if ($host == 'youtu.be' && $path) {
                //Extract from share URL
                $vid = substr($path, 1);
            } else if (($host == 'youtube.com' || $host == 'www.youtube.com')) {

                if (isset($segs['query'])) {
                    //Extract from full URL
                    parse_str($segs['query'], $query);
                    $vid = $query['v'];
                } else {
                    //Extract from embed URL
                    $embedloc = strpos($path, "embed/");
                    $vid = substr($path, $embedloc + 6);
                }
            }

            //Validate and return Video ID
            if ($vid && preg_match('/^[a-zA-Z0-9_\-]{11}$/', $vid)) {
                return $vid;
            } else {
                return false;
            }
        } else if ($site == 'vimeo') {
            $chars = str_split($path);
            $vid = '';
            $id_started = false; //flag is set when we start finding numeric characters

            foreach ($chars as $char) {
                if (preg_match('/^[0-9]{1}$/', $char)) {
                    if ($id_started) {
                        $vid .= $char;
                    } else {
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

    /**
     * Returns a query string of all embed params that were passed through template, to be appended to embed src
     */
    private function getQueryString($site) 
    {
        //whitelist all supported attributes
        $valid_attrs = array();
        if ($site == 'youtube') {

            $valid_attrs = array('autohide', 'autoplay', 'cc_load_policy', 'color', 'controls', 'disablekb', 'enablejsapi', 'end', 'fs',
                'iv_load_policy', 'list', 'listType', 'loop', 'modestbranding', 'origin', 'playerapiid', 'playlist', 'rel', 'showinfo', 'start', 'theme');
        } elseif ($site == 'vimeo') {

            $valid_attrs = array('title', 'byline', 'portrait', 'color', 'autoplay', 'loop', 'api', 'player_id');
        }

        //loop through supported attributes, appending all the ones actually used to a query string
        $query_string = '?';
        foreach ($valid_attrs as $attr) {
            $value = $this->EE->TMPL->fetch_param($attr);
            if (strlen($value)) {
                $query_string.= $attr . '=' . $value . '&';
            }
        }
        $query_string = substr($query_string, 0, -1); //remove last character (which will be either '?' or '&')

        return $query_string;
    }

    /**
     * Validate class and id attributes, return them in a string to be used on an html element
     */
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

    /**
     * Given some combination of set or unset height and width, determine the output dimensions
     */
    private function getDimensions($w, $h) 
    {
        if ($h && $w) {
            //Height and width both set
            $h = intval($h);
            $w = intval($w);
        } else if ($h) {
            //Height set, calculate width
            $h = intval($h);
            $w = ceil($h * 16 / 9);
        } else if ($w) {
            //Width set, calculate height
            $w = intval($w);
            $h = ceil($w * 9 / 16);
        } else {
            //Fall back on defaults
            $w = 560;
            $h = 315;
        }
        return array($w, $h);
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

        <?php
        $buffer = ob_get_contents();

        ob_end_clean();

        return $buffer;
    }

}