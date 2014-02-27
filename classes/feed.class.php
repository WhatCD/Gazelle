<?
class FEED {
	var $UseSSL = true; // If we're using SSL for blog and news links

	function open_feed() {
		header("Content-type: application/xml; charset=UTF-8");
		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n","<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n\t<channel>\n";
		echo '<xhtml:meta xmlns:xhtml="http://www.w3.org/1999/xhtml" name="robots" content="noindex" />'."\n";
		echo '<meta xmlns="http://pipes.yahoo.com" name="pipes" content="noprocess" />'."\n";
	}

	function close_feed() {
		echo "\t</channel>\n</rss>";
	}

	function channel($Title, $Description, $Section = '') {
		$Site = $this->UseSSL ? site_url() : site_url(false);
		echo "\t\t<title>$Title :: ". SITE_NAME. "</title>\n";
		echo "\t\t<link>$Site$Section</link>\n";
		echo "\t\t<description>$Description</description>\n";
		echo "\t\t<language>en-us</language>\n";
		echo "\t\t<lastBuildDate>". date('r'). "</lastBuildDate>\n";
		echo "\t\t<docs>http://blogs.law.harvard.edu/tech/rss</docs>\n";
		echo "\t\t<generator>Gazelle Feed Class</generator>\n\n";
	}

	function item($Title, $Description, $Page, $Creator, $Comments = '', $Category = '', $Date = '') { //Escape with CDATA, otherwise the feed breaks.
		if ($Date == '') {
			$Date = date('r');
		} else {
			$Date = date('r', strtotime($Date));
		}
		$Site = $this->UseSSL ? site_url() : site_url(false);
		$Item = "\t\t<item>\n";
		$Item .= "\t\t\t<title><![CDATA[$Title]]></title>\n";
		$Item .= "\t\t\t<description><![CDATA[$Description]]></description>\n";
		$Item .= "\t\t\t<pubDate>$Date</pubDate>\n";
		$Item .= "\t\t\t<link>$Site$Page</link>\n";
		$Item .= "\t\t\t<guid>$Site$Page</guid>\n";
		if ($Comments != '') {
			$Item .= "\t\t\t<comments>$Site$Comments</comments>\n";
		}
		if ($Category != '') {
			$Item .= "\t\t\t<category><![CDATA[$Category]]></category>\n";
		}
		$Item .= "\t\t\t<dc:creator>$Creator</dc:creator>\n\t\t</item>\n";
		return $Item;
	}

	function retrieve($CacheKey, $AuthKey, $PassKey) {
		global $Cache;
		$Entries = $Cache->get_value($CacheKey);
		if (!$Entries) {
			$Entries = array();
		} else {
			foreach ($Entries as $Item) {
				echo str_replace(array('[[PASSKEY]]', '[[AUTHKEY]]'), array(display_str($PassKey), display_str($AuthKey)), $Item);
			}
		}
	}

	function populate($CacheKey, $Item) {
		global $Cache;
		$Entries = $Cache->get_value($CacheKey, true);
		if (!$Entries) {
			$Entries = array();
		} else {
			if (count($Entries) >= 50) {
				array_pop($Entries);
			}
		}
		array_unshift($Entries, $Item);
		$Cache->cache_value($CacheKey, $Entries, 0); //inf cache
	}
}
