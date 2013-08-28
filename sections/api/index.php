<?
$Available = array(
	'access_request',
	'access_state',
	'user_stats_ratio',
	'user_stats_torrent',
	'user_stats_comumnity',
);

if (
	empty($_GET['req'])
	|| empty($_GET['uid'])
	|| empty($_GET['aid'])
	|| empty($_GET['key'])
	|| !is_number($_GET['uid'])
	|| !is_number($_GET['aid'])
	|| !in_array($_GET['req'], $Available, true)
) {
	error('invalid');
}


$AppID = $_GET['aid'];
$UserID = $_GET['uid'];

$App = $Cache->get_value("api_apps_$AppID");
if (!is_array($App)) {
	if (!isset($DB)) {
		require(SERVER_ROOT.'/classes/mysql.class.php');
		$DB = new DB_MYSQL;
	}
	$DB->query("
		SELECT Token, Name
		FROM api_applications
		WHERE ID = '$AppID'
		LIMIT 1");
	$App = $DB->to_array(false, MYSQLI_ASSOC);
	$Cache->cache_value("api_apps_$AppID", $App, 0);
}
$App = $App[0];

//Handle our request auths
if ($_GET['req'] === 'access_request') {
	if (md5($App['Token']) !== $_GET['key']) {
		error('invalid');
	}
} else {
	$User = $Cache->get_value("api_users_$UserID");
	if (!is_array($User)) {
		if (!isset($DB)) {
			require(SERVER_ROOT.'/classes/mysql.class.php');
			$DB = new DB_MYSQL;
		}
		$DB->query("
			SELECT AppID, Token, State, Time, Access
			FROM api_users
			WHERE UserID = '$UserID'
			LIMIT 1"); //int, no db_string
		$User = $DB->to_array('AppID', MYSQLI_ASSOC);
		$Cache->cache_value("api_users_$UserID", $User, 0);
	}
	$User = $User[$AppID];

	if (md5($User['Token'] . $App['Token']) !== $_GET['key']) {
		error('invalid');
	}
}

die('API put on hold');
require(SERVER_ROOT.'/sections/api/'.$_GET['req'].'.php');
echo '</payload>';
$Debug->profile();
