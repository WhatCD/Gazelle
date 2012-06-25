<?
/**
 * This class determines the thumbnail equivalent of an image's url after being passed the original 
 *
**/

function to_thumbnail($url) {
        $thumb = $url;
        $extension = pathinfo($url, PATHINFO_EXTENSION);
		if(contains('whatimg', $url)) {
			if(hasWhatImgThumb($url)) { 
				if($extension == 'jpeg') {
					$thumb = replace_extension($url, '_thumb.jpeg');
				}
				if($extension == 'jpg') {
                                        $thumb = replace_extension($url, '_thumb.jpg');
                                }
				if($extension == 'png') {
					$thumb = replace_extension($url, '_thumb.png');
				}
				if($extension == 'gif') {
					$thumb = replace_extension($url, '_thumb.gif');
				}
			}
		}
		elseif(contains('imgur', $url)) {
			$url = cleanImgurUrl($url);
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
       return $pos = strpos($string, $substring);       
}

function hasWhatImgThumb($url) { 
	return !contains("_thumb", $url);
}

function cleanImgurUrl($url) {
	$extension = pathinfo($url, PATHINFO_EXTENSION);
	$path = preg_replace('/\.[^.]*$/', '', $url);
	$last = $path[strlen($path)-1];
	if($last == 'm' || $last == 's' || $last == 'b') {
		$path = substr($path, 0, -1);
	} 
	return $path . "." . $extension;  
}
?>
