<?
/**
 * This class determines the thumbnail equivalent of an image's url after being passed the original 
 *
**/


function to_thumbnail($url) {
        $thumb = $url;
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        if(contains('whatimg', $url)) {
                if($extension == 'jpeg' || $extension == 'jpg') {
                        $thumb = replace_extension($url, '_thumb.jpg');
                }
                if($extension == 'png') {
                        $thumb = replace_extension($url, '_thumb.png');
                }
                if($extension == 'gif') {
                        $thumb = replace_extension($url, '_thumb.gif');
                }
        }
        elseif(contains('imgur', $url)) {
                if($extension == 'jpeg') {
                        $thumb = replace_extension($url, 'm.jpeg');
                }
                if($extension == 'jpg') {
                        $thumb = replace_extension($url, 'm.jpg');
                }
                if($extension == 'png') {
                        $thumb = replace_extension($url, 'm.png');
                }
                if($extension == 'gif') {
                        $thumb = replace_extension($url, 'm.gif');
                }
        }
        return $thumb;
}


function replace_extension($string, $extension) {
        $string =  preg_replace('/\.[^.]*$/', '', $string);
        $string = $string . $extension;
        return $string;
}

function contains($substring, $string) {
        $pos = strpos($string, $substring);
        if($pos === false) {
                return false;
        }
        else {
                return true;
        }

}
?>
