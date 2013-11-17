<?
if (!check_perms('site_torrents_notify')) {
	error(403);
}
authorize();

$FormID = '';
$ArtistList = '';
$TagList = '';
$NotTagList = '';
$ReleaseTypeList = '';
$CategoryList = '';
$FormatList = '';
$EncodingList = '';
$MediaList = '';
$FromYear = 0;
$ToYear = 0;
$Users = '';
$HasFilter = false;

if ($_POST['formid'] && is_number($_POST['formid'])) {
	$FormID = $_POST['formid'];
}

if ($_POST['artists'.$FormID]) {
	$Artists = explode(',', $_POST['artists'.$FormID]);
	$ParsedArtists = array();
	foreach ($Artists as $Artist) {
		if (trim($Artist) != '') {
			$ParsedArtists[] = db_string(trim($Artist));
		}
	}
	if (count($ParsedArtists) > 0) {
		$ArtistList = '|'.implode('|', $ParsedArtists).'|';
		$HasFilter = true;
	}
}

if ($_POST['excludeva'.$FormID]) {
	$ExcludeVA = '1';
	$HasFilter = true;
} else {
	$ExcludeVA = '0';
}

if ($_POST['newgroupsonly'.$FormID]) {
	$NewGroupsOnly = '1';
	$HasFilter = true;
} else {
	$NewGroupsOnly = '0';
}

if ($_POST['tags'.$FormID]) {
	$TagList = '|';
	$Tags = explode(',', $_POST['tags'.$FormID]);
	foreach ($Tags as $Tag) {
		$TagList.=db_string(trim($Tag)).'|';
	}
	$HasFilter = true;
}

if ($_POST['nottags'.$FormID]) {
	$NotTagList = '|';
	$Tags = explode(',', $_POST['nottags'.$FormID]);
	foreach ($Tags as $Tag) {
		$NotTagList.=db_string(trim($Tag)).'|';
	}
	$HasFilter = true;
}

if ($_POST['categories'.$FormID]) {
	$CategoryList = '|';
	foreach ($_POST['categories'.$FormID] as $Category) {
		$CategoryList.=db_string(trim($Category)).'|';
	}
	$HasFilter = true;
}

if ($_POST['releasetypes'.$FormID]) {
	$ReleaseTypeList = '|';
	foreach ($_POST['releasetypes'.$FormID] as $ReleaseType) {
		$ReleaseTypeList.=db_string(trim($ReleaseType)).'|';
	}
	$HasFilter = true;
}

if ($_POST['formats'.$FormID]) {
	$FormatList = '|';
	foreach ($_POST['formats'.$FormID] as $Format) {
		$FormatList.=db_string(trim($Format)).'|';
	}
	$HasFilter = true;
}


if ($_POST['bitrates'.$FormID]) {
	$EncodingList = '|';
	foreach ($_POST['bitrates'.$FormID] as $Bitrate) {
		$EncodingList.=db_string(trim($Bitrate)).'|';
	}
	$HasFilter = true;
}

if ($_POST['media'.$FormID]) {
	$MediaList = '|';
	foreach ($_POST['media'.$FormID] as $Medium) {
		$MediaList.=db_string(trim($Medium)).'|';
	}
	$HasFilter = true;
}

if ($_POST['fromyear'.$FormID] && is_number($_POST['fromyear'.$FormID])) {
	$FromYear = trim($_POST['fromyear'.$FormID]);
	$HasFilter = true;
	if ($_POST['toyear'.$FormID] && is_number($_POST['toyear'.$FormID])) {
		$ToYear = trim($_POST['toyear'.$FormID]);
	} else {
		$ToYear = date('Y') + 3;
	}
}


if ($_POST['users'.$FormID]) {
	$Usernames = explode(',', $_POST['users'.$FormID]);
	$EscapedUsernames = array();
	foreach ($Usernames as $Username) {
		$EscapedUsernames[] = db_string(trim($Username));;
	}

	$DB->query("
		SELECT ID, Paranoia
		FROM users_main
		WHERE Username IN ('" . implode("', '", $EscapedUsernames) . "')
			AND ID != $LoggedUser[ID]");
	while (list($UserID, $Paranoia) = $DB->next_record()) {
		$Paranoia = unserialize($Paranoia);
		if (!in_array('notifications', $Paranoia)) {
			$Users .= '|' . $UserID . '|';
			$HasFilter = true;
		}
	}
}

if (!$HasFilter) {
	$Err = 'You must add at least one criterion to filter by';
} elseif (!$_POST['label'.$FormID] && !$_POST['id'.$FormID]) {
	$Err = 'You must add a label for the filter set';
}

if ($Err) {
	error($Err);
	header('Location: user.php?action=notify');
	die();
}

$ArtistList = str_replace('||', '|', $ArtistList);
$TagList = str_replace('||', '|', $TagList);
$NotTagList = str_replace('||', '|', $NotTagList);
$Users = str_replace('||', '|', $Users);

if ($_POST['id'.$FormID] && is_number($_POST['id'.$FormID])) {
	$DB->query("
		UPDATE users_notify_filters
		SET
			Artists='$ArtistList',
			ExcludeVA='$ExcludeVA',
			NewGroupsOnly='$NewGroupsOnly',
			Tags='$TagList',
			NotTags='$NotTagList',
			ReleaseTypes='$ReleaseTypeList',
			Categories='$CategoryList',
			Formats='$FormatList',
			Encodings='$EncodingList',
			Media='$MediaList',
			FromYear='$FromYear',
			ToYear='$ToYear',
			Users ='$Users'
		WHERE ID='".$_POST['id'.$FormID]."'
			AND UserID='$LoggedUser[ID]'");
} else {
	$DB->query("
		INSERT INTO users_notify_filters
			(UserID, Label, Artists, ExcludeVA, NewGroupsOnly, Tags, NotTags, ReleaseTypes, Categories, Formats, Encodings, Media, FromYear, ToYear, Users)
		VALUES
			('$LoggedUser[ID]','".db_string($_POST['label'.$FormID])."','$ArtistList','$ExcludeVA','$NewGroupsOnly','$TagList', '$NotTagList', '$ReleaseTypeList','$CategoryList','$FormatList','$EncodingList','$MediaList', '$FromYear', '$ToYear', '$Users')");
}

$Cache->delete_value('notify_filters_'.$LoggedUser['ID']);
if (($Notify = $Cache->get_value('notify_artists_'.$LoggedUser['ID'])) !== false && $Notify['ID'] == $_POST['id'.$FormID]) {
	$Cache->delete_value('notify_artists_'.$LoggedUser['ID']);
}
header('Location: user.php?action=notify');
?>
