<?php
/**
 * @version 1.1
 * @author Josh Hundley
 * @author Jorge Orpinel <jop@levogiro.net> (changes)
 */
class ImageHelper extends Helper {
    public $helpers = array('Html');
    public $cacheDir = 'resized'; // relative to 'img'.DS

    /**
     * Automatically resizes an image and returns formatted IMG tag
     *
     * @param string $path Path to the image file, relative to the webroot/img/ directory.
     * @param integer $width Image of returned image
     * @param integer $height Height of returned image
     * @param boolean $aspect Maintain aspect ratio (default: true)
     * @param array    $htmlAttributes Array of HTML attributes.
     * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
     * @return mixed    Either string or echos the value, depends on AUTO_OUTPUT and $return.
     * @access public
     */
    public function resize($path, $width = null, $height = null, $options = array()) {
    
        $_options = array(
          'aspect' => true,
          'htmlAttributes' => array(),
          'return' => false,
          'crop' => false,
          'fit' => false,
          'bgColor' => array('255','255','255')
        );
        $options = array_merge($_options,$options);
    
        $types = array(1 => "gif", "jpeg", "png", "swf", "psd", "wbmp"); // used to determine image type

        $uploadsDir = 'uploads';

        $fullpath = ROOT.DS.APP_DIR.DS.WEBROOT_DIR.DS.$uploadsDir.DS;
        $url = ROOT.DS.APP_DIR.DS.WEBROOT_DIR.DS.$path;
        

        if (!($size = getimagesize($url)))
            return; // image doesn't exist
            
        $newWidth = $width;
        $newHeight = $height;
            
        if(empty($height))
        {
          $newHeight = ceil($width / ($size[0]/$size[1]));
        }
        elseif(empty($width))
        {
          $newWidth = ceil(($size[0]/$size[1]) * $height);
        }
        elseif($options['aspect'] === true) { // adjust to aspect.
            if(($size[1]/$height) > ($size[0]/$width))
            {
              $newWidth = ceil(($size[0]/$size[1]) * $height);
            }
            else
            {
              $newHeight = ceil($width / ($size[0]/$size[1]));
            }
        }

        $relfile = $this->webroot.$uploadsDir.'/'.$this->cacheDir.'/'.$newWidth.'x'.$newHeight.'_'.basename($path); // relative file
        $cachefile = $fullpath.$this->cacheDir.DS.$newWidth.'x'.$newHeight.'_'.basename($path);  // location on server

        if (file_exists($cachefile)) {
            $csize = getimagesize($cachefile);
            $cached = ($csize[0] == $newWidth && $csize[1] == $newHeight); // image is cached
            if (@filemtime($cachefile) < @filemtime($url)) // check if up to date
                $cached = false;
        } else {
            $cached = false;
        }
        
        $cached = false;

        if (!$cached) {
            $resize = ($size[0] > $newWidth || $size[1] > $newHeight) || ($size[0] < $newWidth || $size[1] < $newHeight);
        } else {
            $resize = false;
        }

        if ($resize) {
            $image = call_user_func('imagecreatefrom'.$types[$size[2]], $url);
            
            $dst_x = 0;
            $dst_y = 0;
            $src_x = 0;
            $src_y = 0;
            
            $canvasWidth = $newWidth;
            $canvasHeight = $newHeight;
            
            //Fix the image width and height and align the image in the middle
            if($options['fit'])
            {
              $canvasWidth = $width;
              $canvasHeight = $height;
              
              $dst_x = ($canvasWidth / 2) - ($newWidth / 2);
            }
            
            
            if (function_exists("imagecreatetruecolor") && ($temp = imagecreatetruecolor($canvasWidth, $canvasHeight)))
            {
              $bgColour = imagecolorallocate($temp, $options['bgColor'][0], $options['bgColor'][1], $options['bgColor'][2]);
              imagefilledrectangle($temp, 0, 0, $canvasWidth, $canvasHeight, $bgColour);
              imagecopyresampled ($temp, $image, $dst_x, $dst_y, $src_x, $src_y, $newWidth, $newHeight, $size[0], $size[1]);
            }
            else
            {
              $temp = imagecreate ($canvasWidth, $canvasHeight);
              $bgColour = imagecolorallocate($temp, $options['bgColor'][0], $options['bgColor'][1], $options['bgColor'][2]);
              imagefilledrectangle($temp, 0, 0, $canvasWidth, $canvasHeight, $bgColour);
              imagecopyresized ($temp, $image, $dst_x, $dst_y, $src_x, $src_y, $newWidth, $newHeight, $size[0], $size[1]);
            }
            call_user_func("image".$types[$size[2]], $temp, $cachefile);
            imagedestroy ($image);
            imagedestroy ($temp);
        } else {
            //copy($url, $cachefile);
        }

        if($options['return'] == 'path')
        {
          return $relfile;
        }
        else
        {
          return $this->output(sprintf($this->Html->tags['image'], $relfile, $this->Html->_parseAttributes($options['htmlAttributes'], null, '', ' ')), $options['return']);
        }
    }
}
?>