<?
header('Content-type: application/x-suggestions+json');
require('classes/ajax_start.php');

if(empty($_GET['name'])) { die('["",[],[],[]]'); }

$FullName = rawurldecode($_GET['name']);

$MaxKeySize = 4;
if (strtolower(substr($FullName,0,4)) == 'the ') {
	$MaxKeySize += 4;
}
$KeySize = min($MaxKeySize,max(1,strlen($FullName)));

$Letters = strtolower(substr($FullName,0,$KeySize));
$AutoSuggest = $Cache->get('autocomplete_artist_'.$KeySize.'_'.$Letters);
if(!is_array($AutoSuggest)) {
	if(!isset($DB) || !is_object($DB)) {
		require(SERVER_ROOT.'/classes/class_mysql.php'); //Require the database wrapper
		$DB=NEW DB_MYSQL; //Load the database wrapper
	}
	$Limit = (($KeySize === $MaxKeySize)?250:10);
	$DB->query("SELECT 
		a.ArtistID,
		a.Name, 
		SUM(t.Snatched) AS Snatches 
		FROM artists_group AS a 
		INNER JOIN torrents_artists AS ta ON ta.ArtistID=a.ArtistID 
		INNER JOIN torrents AS t ON t.GroupID=ta.GroupID 
		WHERE a.Name LIKE '".db_string($Letters)."%' 
		GROUP BY ta.ArtistID 
		ORDER BY Snatches DESC 
		LIMIT $Limit");
	$AutoSuggest = $DB->to_array(false,MYSQLI_NUM,false);
	$Cache->cache_value('autocomplete_artist_'.$KeySize.'_'.$Letters,$AutoSuggest,1800+7200*($MaxKeySize-$KeySize)); // Can't cache things for too long in case names are edited
}

$Matched = 0;
$Suggestions = array();
$Snatches = array();
$Links = array();
foreach ($AutoSuggest as $Suggestion) {
	list($ID,$Name, $Snatch) = $Suggestion;
	if (stripos($Name,$FullName) === 0) {
		$Suggestions[] = display_str($Name);
		$Snatches[] = number_format($Snatch).' snatches';
		$Links[] = 'http'.($SSL?'s':'').'://'.$_SERVER['HTTP_HOST'].'/artist.php?id='.$ID;
		if (++$Matched > 9) {
			break;
		}
	}
}

echo json_encode(array($FullName,$Suggestions,$Snatches,$Links));
