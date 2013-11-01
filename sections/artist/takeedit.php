<?
/*********************************************************************\
The page that handles the backend of the 'edit artist' function.
\*********************************************************************/

authorize();

if (!$_REQUEST['artistid'] || !is_number($_REQUEST['artistid'])) {
	error(404);
}

if (!check_perms('site_edit_wiki')) {
	error(403);
}

// Variables for database input
$UserID = $LoggedUser['ID'];
$ArtistID = $_REQUEST['artistid'];
if (check_perms('artist_edit_vanityhouse')) {
	$VanityHouse = isset($_POST['vanity_house']) ? 1 : 0 ;
}


if ($_GET['action'] === 'revert') { // if we're reverting to a previous revision
	authorize();
	$RevisionID = $_GET['revisionid'];
	if (!is_number($RevisionID)) {
		error(0);
	}
} else { // with edit, the variables are passed with POST
	$Body = db_string($_POST['body']);
	$Summary = db_string($_POST['summary']);
	$Image = db_string($_POST['image']);
	ImageTools::blacklisted($Image);
	// Trickery
	if (!preg_match("/^".IMAGE_REGEX."$/i", $Image)) {
		$Image = '';
	}
}

// Insert revision
if (!$RevisionID) { // edit
	$DB->query("
		INSERT INTO wiki_artists
			(PageID, Body, Image, UserID, Summary, Time)
		VALUES
			('$ArtistID', '$Body', '$Image', '$UserID', '$Summary', '".sqltime()."')");
} else { // revert
	$DB->query("
		INSERT INTO wiki_artists (PageID, Body, Image, UserID, Summary, Time)
		SELECT '$ArtistID', Body, Image, '$UserID', 'Reverted to revision $RevisionID', '".sqltime()."'
		FROM wiki_artists
		WHERE RevisionID = '$RevisionID'");
}

$RevisionID = $DB->inserted_id();

// Update artists table (technically, we don't need the RevisionID column, but we can use it for a join which is nice and fast)
$DB->query("
	UPDATE artists_group
	SET
		". (isset($VanityHouse) ? "VanityHouse = '$VanityHouse'," : '') ."
		RevisionID = '$RevisionID'
	WHERE ArtistID = '$ArtistID'");

// There we go, all done!
$Cache->delete_value("artist_$ArtistID"); // Delete artist cache
header("Location: artist.php?id=$ArtistID");
?>
