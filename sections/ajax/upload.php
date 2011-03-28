<?
// echo out the slice of the form needed for the selected upload type ($_GET['section']).


// Include the necessary form class
include(SERVER_ROOT.'/classes/class_torrent_form.php');
$TorrentForm = new TORRENT_FORM();

$GenreTags = $Cache->get_value('genre_tags');
if(!$GenreTags){
	$DB->query('SELECT Name FROM tags WHERE TagType=\'genre\' ORDER BY Name');
	$GenreTags =  $DB->collect('Name');
	$Cache->cache_value('genre_tags', $GenreTags, 3600*24);
}

$UploadForm = $Categories[$_GET['categoryid']];

switch($UploadForm) {
	case 'Music':
		$TorrentForm->music_form($GenreTags);
		break;
		
	case 'Audiobooks':
	case 'Comedy':
		$TorrentForm->audiobook_form();
		break;
	
	case 'Applications':
	case 'Comics':
	case 'E-Books':
	case 'E-Learning Videos':
		$TorrentForm->simple_form($_GET['categoryid']);
		break;
	default:
		echo "Invalid action!";
}



?>
