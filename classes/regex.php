<?
//resource_type://username:password@domain:port/path?query_string#anchor
define('RESOURCE_REGEX', '(https?|ftps?):\/\/');
define('IP_REGEX', '(\d{1,3}\.){3}\d{1,3}');
define('DOMAIN_REGEX', '([a-z0-9\-\_]+\.)*[a-z0-9\-\_]+');
define('PORT_REGEX', ':\d{1,5}');
define('URL_REGEX', '('.RESOURCE_REGEX.')('.IP_REGEX.'|'.DOMAIN_REGEX.')('.PORT_REGEX.')?(\/\S*)*');
define('USERNAME_REGEX', '/^[a-z0-9_?]{1,20}$/iD');
define('EMAIL_REGEX','[_a-z0-9-]+([.+][_a-z0-9-]+)*@'.DOMAIN_REGEX);
define('IMAGE_REGEX', URL_REGEX.'\/\S+\.(jpg|jpeg|tif|tiff|png|gif|bmp)(\?\S*)?');
define('CSS_REGEX', URL_REGEX.'\/\S+\.css(\?\S*)?');
define('SITELINK_REGEX', RESOURCE_REGEX.'(ssl.)?'.preg_quote(NONSSL_SITE_URL, '/'));
define('TORRENT_REGEX', SITELINK_REGEX.'\/torrents\.php\?(.*&)?torrentid=(\d+)'); // torrentid = group 4
define('TORRENT_GROUP_REGEX', SITELINK_REGEX.'\/torrents\.php\?(.*&)?id=(\d+)'); // id = group 4
define('ARTIST_REGEX', SITELINK_REGEX.'\/artist\.php\?(.*&)?id=(\d+)'); // id = group 4
