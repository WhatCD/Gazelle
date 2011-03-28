<?
//resource_type://username:password@domain:port/path?query_string#anchor
define('RESOURCE_REGEX','(https?|ftps?):\/\/');
define('IP_REGEX','(\d{1,3}\.){3}\d{1,3}');
define('DOMAIN_REGEX','(ssl.)?(www.)?[a-z0-9-\.]{1,255}\.[a-zA-Z]{2,6}');
define('PORT_REGEX', '\d{1,5}');
define('URL_REGEX','('.RESOURCE_REGEX.')('.IP_REGEX.'|'.DOMAIN_REGEX.')(:'.PORT_REGEX.')?(\/\S*)*');
define('EMAIL_REGEX','[_a-z0-9-]+([.+][_a-z0-9-]+)*@'.DOMAIN_REGEX);
define('IMAGE_REGEX', URL_REGEX.'\/\S+\.(jpg|jpeg|tif|tiff|png|gif|bmp)(\?\S*)?');
define('SITELINK_REGEX', RESOURCE_REGEX.'(ssl.)?'.preg_quote(NONSSL_SITE_URL, '/').'');
define('TORRENT_REGEX', SITELINK_REGEX.'\/torrents.php\?(id=\d{1,10}\&)?torrentid=\d{1,10}');
define('TORRENT_GROUP_REGEX', SITELINK_REGEX.'\/torrents.php\?id=\d{1,10}\&(torrentid=\d{1,10})?');
?>
