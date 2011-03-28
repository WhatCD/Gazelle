<?
function make_utf8($Str) {
	if ($Str!="") {
		if (is_utf8($Str)) { $Encoding="UTF-8"; }
		if (empty($Encoding)) { $Encoding=mb_detect_encoding($Str,'UTF-8, ISO-8859-1'); }
		if (empty($Encoding)) { $Encoding="ISO-8859-1"; }
		if ($Encoding=="UTF-8") { return $Str; }
		else { return @mb_convert_encoding($Str,"UTF-8",$Encoding); }
	}
}

function is_utf8($Str) {
	return preg_match('%^(?:
		[\x09\x0A\x0D\x20-\x7E]			 // ASCII
		| [\xC2-\xDF][\x80-\xBF]			// non-overlong 2-byte
		| \xE0[\xA0-\xBF][\x80-\xBF]		// excluding overlongs
		| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} // straight 3-byte
		| \xED[\x80-\x9F][\x80-\xBF]		// excluding surrogates
		| \xF0[\x90-\xBF][\x80-\xBF]{2}	 // planes 1-3
		| [\xF1-\xF3][\x80-\xBF]{3}		 // planes 4-15
		| \xF4[\x80-\x8F][\x80-\xBF]{2}	 // plane 16
		)*$%xs', $Str
	);
}

function is_number($Str) {
	$Return = true;
	if ($Str < 0) { $Return = false; }
	// We're converting input to a int, then string and comparing to original
	$Return = ($Str == strval(intval($Str)) ? true : false);
	return $Return;
}

function display_str($Str) {
	if (empty($Str)) {
		return '';
	}
	if ($Str!='' && !is_number($Str)) {
		$Str=make_utf8($Str);
		$Str=mb_convert_encoding($Str,"HTML-ENTITIES","UTF-8");
		$Str=preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/m","&amp;",$Str);

		$Replace = array(
			"'",'"',"<",">",
			'&#128;','&#130;','&#131;','&#132;','&#133;','&#134;','&#135;','&#136;','&#137;','&#138;','&#139;','&#140;','&#142;','&#145;','&#146;','&#147;','&#148;','&#149;','&#150;','&#151;','&#152;','&#153;','&#154;','&#155;','&#156;','&#158;','&#159;'
		);

		$With=array(
			'&#39;','&quot;','&lt;','&gt;',
			'&#8364;','&#8218;','&#402;','&#8222;','&#8230;','&#8224;','&#8225;','&#710;','&#8240;','&#352;','&#8249;','&#338;','&#381;','&#8216;','&#8217;','&#8220;','&#8221;','&#8226;','&#8211;','&#8212;','&#732;','&#8482;','&#353;','&#8250;','&#339;','&#382;','&#376;'
		);

		$Str=str_replace($Replace,$With,$Str);
	}
	return $Str;
}

require('classes/class_useragent.php'); //Require the useragent class
$UA = new USER_AGENT;
$Browser = $UA->browser($_SERVER['HTTP_USER_AGENT']);
$OperatingSystem = $UA->operating_system($_SERVER['HTTP_USER_AGENT']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 
<head> 
	<title>&lt;script&gt; Defer Bug</title>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.0/jquery.min.js" type="text/javascript" defer="defer"></script> 
</head> 
<body>
	<h2>Defer bug test (Gecko 1.9.2+)</h2>
	<p>This page showcases issues with browsers and defer loading with the preservation of content order.</p>
	<p>As one can see in this demo, the implementation of the HTML5 changes to &lt;script defer&gt; (<a href="https://bugzilla.mozilla.org/show_bug.cgi?id=518104">1</a>) has resulted in several small issues, originating from the way this has been used historically. Up until the past year, javascript execution has been painfully slow, to an extent that things like Google Analytics advised the placement of their javascript in file footers (a contradiction to another part of the spec). The defer attribute has been used in such cases to allowe ECMAScript to remain in the correct inclusion location without blocking the download of content or the rendering of the webpage. This lowered the percieved load time, the <em>most</em> important number in web development (notably for encouraging users to return). Because of this change however, not only has this percieved performance gain been sacrificed, but a relatively serious backwords compatibility issue for sites using defer in their natural code execution pattern.</p>
	<p>Because of this combination of breaking various script loading patterns, a decrease in user percieved performance, the fact that all other browsers thus far have opted out of this adaptation of the spec, and the resulting encouragement this would give web developers to include scripts outside the page header; I am humbly noting that this additional 'clarification' contained in the HTML5 spec is flawed in nature and should be reverted to it's prior method.</p>
	<strong>Browser Info</strong>
	<ul>
		<li>Detected platform: <?=$OperatingSystem?></li>
		<li>Detected browser: <?=$Browser?></li>
		<li>User-Agent: <?=display_str($_SERVER['HTTP_USER_AGENT'])?></li>
	</ul>
	<p>To clarify for the confused: if you get the alert, your browser has this bug, if you do not get the alert, your browser is working fine.</p>
<script type="text/javascript" defer="defer">
	if (typeof($) === 'undefined') {
		alert('jQuery was not loaded prior to inline code execution');
	}
</script>
</body> 
</html> 
