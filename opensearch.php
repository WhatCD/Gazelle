<?
header('Content-type: application/opensearchdescription+xml');
require('classes/config.php');

$SSL = ($_SERVER['SERVER_PORT'] === '443');

$Type = ((!empty($_GET['type']) && in_array($_GET['type'],array('torrents','artists','requests','forums','users','wiki','log')))?$_GET['type']:'artists');

/*
$FH = fopen(SERVER_ROOT.'/favicon.ico','r');
$Image = base64_encode(fread($FH,filesize(SERVER_ROOT.'/favicon.ico')));
fclose($FH);
*/

echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/" xmlns:moz="http://www.mozilla.org/2006/browser/search/">
	<ShortName><?=SITE_NAME.' '.ucfirst($Type)?> </ShortName>
	<Description>Search <?=SITE_NAME?> for <?=ucfirst($Type)?></Description>
	<Developer></Developer>
	<Image width="16" height="16" type="image/x-icon">http<?=($SSL?'s':'')?>://<?=SITE_URL?>/favicon.ico</Image>
<?
switch ($Type) {
	case 'artists':
?>
	<Url type="text/html" method="get" template="http<?=($SSL?'s':'')?>://<?=SITE_URL?>/artist.php?artistname={searchTerms}"></Url>
	<moz:SearchForm>http<?=($SSL?'s':'')?>://<?=SITE_URL?>/torrents.php?action=advanced</moz:SearchForm>
<?
		break;
	case 'torrents':
?>
	<Url type="text/html" method="get" template="http<?=($SSL?'s':'')?>://<?=SITE_URL?>/torrents.php?action=basic&amp;searchstr={searchTerms}"></Url>
	<moz:SearchForm>http<?=($SSL?'s':'')?>://<?=SITE_URL?>/torrents.php</moz:SearchForm>
<?
		break;
	case 'requests':
?>
	<Url type="text/html" method="get" template="http<?=($SSL?'s':'')?>://<?=SITE_URL?>/requests.php?search={searchTerms}"></Url>
	<moz:SearchForm>http<?=($SSL?'s':'')?>://<?=SITE_URL?>/requests.php</moz:SearchForm>
<?
		break;
	case 'forums':
?>
	<Url type="text/html" method="get" template="http<?=($SSL?'s':'')?>://<?=SITE_URL?>/forums.php?action=search&amp;search={searchTerms}"></Url>
	<moz:SearchForm>http<?=($SSL?'s':'')?>://<?=SITE_URL?>/forums.php?action=search</moz:SearchForm>
<?
		break;
	case 'users':
?>
	<Url type="text/html" method="get" template="http<?=($SSL?'s':'')?>://<?=SITE_URL?>/user.php?action=search&amp;search={searchTerms}"></Url>
	<moz:SearchForm>http<?=($SSL?'s':'')?>://<?=SITE_URL?>/user.php?action=search</moz:SearchForm>
<?
		break;
	case 'wiki':
?>
	<Url type="text/html" method="get" template="http<?=($SSL?'s':'')?>://<?=SITE_URL?>/wiki.php?action=search&amp;search={searchTerms}"></Url>
	<moz:SearchForm>http<?=($SSL?'s':'')?>://<?=SITE_URL?>/wiki.php?action=search</moz:SearchForm>
<?
		break;
	case 'log':
?>
	<Url type="text/html" method="get" template="http<?=($SSL?'s':'')?>://<?=SITE_URL?>/log.php?search={searchTerms}"></Url>
	<moz:SearchForm>http<?=($SSL?'s':'')?>://<?=SITE_URL?>/log.php</moz:SearchForm>
<?
		break;
}
?>
	<Url type="application/opensearchdescription+xml" rel="self" template="http<?=($SSL?'s':'')?>://<?=SITE_URL?>/opensearch.php?type=<?=$Type?>" />
	<Language>en-us</Language>
	<OutputEncoding>UTF-8</OutputEncoding>
	<InputEncoding>UTF-8</InputEncoding>
</OpenSearchDescription>
