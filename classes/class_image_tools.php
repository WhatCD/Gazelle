<?
/**
 * This class determines the thumbnail equivalent of an image's url after being passed the original
 *
 **/

 $blacklist = array("tinypic", "dsimg");
 
 /**
  * Checks if image host is good, otherwise displays an error.
  */
 function check_imagehost($url) {
    global $blacklist;
    
    foreach ($blacklist as &$value) { 
        if(strpos(strtolower($url), $value)) {
            $parsed_url = parse_url($url);
            error($parsed_url['host'] . " is not an allowed imagehost. Please use a different imagehost.");
            break;
        }
    }
 }
 
/**
 * The main function, called to get the thumbnail url.
 */
function to_thumbnail($url) {
    $thumb = $url;
    $extension = pathinfo($url, PATHINFO_EXTENSION);
    if (contains('whatimg', $url)) {
        if (hasWhatImgThumb($url)) {
            if ($extension == 'jpeg') {
                $thumb = replace_extension($url, '_thumb.jpeg');
            }
            if ($extension == 'jpg') {
                $thumb = replace_extension($url, '_thumb.jpg');
            }
            if ($extension == 'png') {
                $thumb = replace_extension($url, '_thumb.png');
            }
            if ($extension == 'gif') {
                $thumb = replace_extension($url, '_thumb.gif');
            }
        }
    } elseif (contains('imgur', $url)) {
        $url = cleanImgurUrl($url);
        if ($extension == 'jpeg') {
            $thumb = replace_extension($url, 'm.jpeg');
        }
        if ($extension == 'jpg') {
            $thumb = replace_extension($url, 'm.jpg');
        }
        if ($extension == 'png') {
            $thumb = replace_extension($url, 'm.png');
        }
        if ($extension == 'gif') {
            $thumb = replace_extension($url, 'm.gif');
        }
    }
    return $thumb;
}

/**
 * Replaces the extension.
 */
function replace_extension($string, $extension) {
    $string = preg_replace('/\.[^.]*$/', '', $string);
    $string = $string . $extension;
    return $string;
}

function contains($substring, $string) {
    return $pos = strpos($string, $substring);

}

/**
 * Checks if url points to a whatimg thumbnail.
 */
function hasWhatImgThumb($url) {
    return !contains("_thumb", $url);
}

/**
 * Cleans up imgur url if it already has a modifier attached to the end of it.
 */
function cleanImgurUrl($url) {
    $extension = pathinfo($url, PATHINFO_EXTENSION);
    $full = preg_replace('/\.[^.]*$/', '', $url);
    $base = substr($full, 0, strrpos($full, '/'));
    $path = substr($full, strrpos($full, '/') + 1);
    if (strlen($path) == 6) {
        $last = $path[strlen($path) - 1];
        if ($last == 'm' || $last == 'l' || $last == 's' || $last == 'h' || $last == 'b') {
            $path = substr($path, 0, -1);
        }
    }
    return $base . "/" . $path . "." . $extension;
}
?>
