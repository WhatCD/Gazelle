<?php

/**
 * ImageTools Class
 * Thumbnail aide, mostly
 */
class ImageTools {
	/**
	 * Store processed links to avoid repetition
	 * @var array 'URL' => 'Parsed URL'
	 */
	static private $Storage = array();

	/**
	 * We use true as an extra property to make the domain an array key
	 * @var array $Hosts Array of image hosts
	 */
	static private $Hosts = array(
		'whatimg.com' => true,
		'imgur.com' => true
	);

	/**
	 * Blacklisted sites
	 * @var array $Blacklist Array of blacklisted hosts
	 */
	static private $Blacklist = array(
		'tinypic.com'
	);

	/**
	 * Array of image hosts that provide thumbnailing
	 * @var array $Thumbs
	 */
	static private $Thumbs = array(
		'i.imgur.com' => true,
		'whatimg.com' => true
	);

	/**
	 * Array of extensions
	 * @var array $Extensions
	 */
	static private $Extensions = array(
		'jpg' => true,
		'jpeg' => true,
		'png' => true,
		'gif' => true
	);

	/**
	 * Checks from our list of valid hosts
	 * @param string $Host Domain/host to check
	 * @return boolean
	 */
	public static function valid_host($Host) {
		return !empty(self::$Hosts[$Host]) && self::$Hosts[$Host] === true;
	}

	/**
	 * Checks if a link's host is (not) good, otherwise displays an error.
	 * @param string $Url Link to an image
	 * @return boolean
	 */
	public static function blacklisted($Url) {
		foreach (self::$Blacklist as &$Value) {
			if (stripos($Url, $Value)) {
				$ParsedUrl = parse_url($Url);
				error($ParsedUrl['host'] . ' is not an allowed image host. Please use a different host.');
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks to see if a link has a thumbnail
	 * @param string $Url Link to an image
	 * @return string|false Matched host or false
	 */
	public static function thumbnailable($Url) {
		$ParsedUrl = parse_url($Url);
		return !empty(self::$Thumbs[$ParsedUrl['host']]);
	}

	/**
	 * Checks an extension
	 * @param string $Ext Extension to check
	 * @return boolean
	 */
	public static function valid_extension($Ext) {
//		return @self::$Extensions[$Ext] === true;
		return !empty(self::$Extensions[$Ext]) && self::$Extensions[$Ext] === true;
	}

	/**
	 * Stores a link with a (thumbnail) link
	 * @param type $Link
	 * @param type $Processed
	 */
	public static function store($Link, $Processed) {
		self::$Storage[$Link] = $Processed;
	}

	/**
	 * Retrieves an entry from our storage
	 * @param type $Link
	 * @return boolean|string Returns false if no match
	 */
	public static function get_stored($Link) {
		if (isset(self::$Storage[$Link])) {
			return self::$Storage[$Link];
		}
		return false;
	}

	/**
	 * Turns link into thumbnail (if possible) or default group image (if missing)
	 * Automatically applies proxy when appropriate
	 *
	 * @global array $CategoryIcons
	 * @param string $Link Link to an image
	 * @param int $Groupid The torrent's group ID for a default image
	 * @param boolean $Thumb Thumbnail image
	 * @return string Link to image
	 */
	public static function wiki_image($Link, $GroupID = 0, $Thumb = true) {
		global $CategoryIcons;

		if ($Link && $Thumb) {
			$Thumb = self::thumbnail($Link);
			if (check_perms('site_proxy_images')) {
				return self::proxy_url($Thumb);
			}
			return $Thumb;
		}

		return STATIC_SERVER . 'common/noartwork/' . $CategoryIcons[$GroupID];
	}

	/**
	 * The main function called to get a thumbnail link.
	 * Use wiki_image() instead of this method for more advanced options
	 *
	 * @param string $Link Image link
	 * @return string Image link
	 */
	public static function thumbnail($Link) {
		if (($Found = self::get_stored($Link))) {
			return $Found;
		}
		return self::process_thumbnail($Link);
	}

	/**
	 * Matches a hosts that thumbnails and stores link
	 * @param string $Link Image link
	 * @return string Thumbnail link or Image link
	 */
	static private function process_thumbnail($Link) {
		$Thumb = $Link;
		$Extension = pathinfo($Link, PATHINFO_EXTENSION);

		if (self::thumbnailable($Link) && self::valid_extension($Extension)) {
			if (contains('whatimg', $Link) && !has_whatimg_thumb($Link)) {
				$Thumb = replace_extension($Link, '_thumb.' . $Extension);
			} elseif (contains('imgur', $Link)) {
				$Thumb = replace_extension(clean_imgur_url($Link), 'm.' . $Extension);
			}
		}
		self::store($Link, $Thumb);
		return $Thumb;
	}

	/**
	 * Creates an HTML thumbnail
	 * @param type $Source
	 * @param type $Category
	 * @param type $Size
	 */
	public static function cover_thumb($Source, $Category = 0, $Size = 90, $Title = 'Cover') {
		$Img = self::wiki_image($Source, $Category);
		if (!$Source) {
			$Source = $Img;
		} elseif (check_perms('site_proxy_images')) {
			$Source = self::proxy_url($Source);
		}
?>
		<img src="<?=$Img?>" width="<?=$Size?>" height="<?=$Size?>" alt="<?=$Title?>" onclick="lightbox.init('<?=$Source?>', <?=$Size?>)" />
<?
	}

	/**
	 * Create image proxy URL
	 * @param string $Url image URL
	 * @return image proxy URL
	 */
	public static function proxy_url($Url) {
		global $SSL;
		return ($SSL ? 'https' : 'http') . '://' . SITE_URL
			. '/image.php?i=' . urlencode($Url);
	}
}

/**
 * This non-class determines the thumbnail equivalent of an image's URL after being passed the original
 *
 **/


/**
 * Replaces the extension.
 */
function replace_extension($String, $Extension) {
	return preg_replace('/\.[^.]*$/', $Extension, $String);
}

function contains($Substring, $String) {
	return strpos($String, $Substring) !== false;
}

/**
 * Checks if URL points to a whatimg thumbnail.
 */
function has_whatimg_thumb($Url){
	return contains("_thumb", $Url);
}

/**
 * Cleans up imgur URL if it already has a modifier attached to the end of it.
 */
function clean_imgur_url($Url){
    $Extension = pathinfo($Url, PATHINFO_EXTENSION);
    $Full = preg_replace('/\.[^.]*$/', '', $Url);
    $Base = substr($Full, 0, strrpos($Full, '/'));
    $Path = substr($Full, strrpos($Full, '/') + 1);
    if (strlen($Path) == 6) {
        $Last = $Path[strlen($Path) - 1];
        if ($Last == 'm' || $Last == 'l' || $Last == 's' || $Last == 'h' || $Last == 'b') {
            $Path = substr($Path, 0, -1);
        }
    }
    return $Base . "/" . $Path . "." . $Extension;
}
