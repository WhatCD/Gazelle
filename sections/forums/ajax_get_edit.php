<?
if(!check_perms('site_moderate_forums')) {
	error(403);
}

if(empty($_GET['postid']) || !is_number($_GET['postid'])) {
	die();
}

$PostID = $_GET['postid'];

if(!isset($_GET['depth']) || !is_number($_GET['depth'])) {
	die();
}

$Depth = $_GET['depth'];

if(empty($_GET['type']) || !in_array($_GET['type'], array('forums', 'collages', 'requests', 'torrents'))) {
	die();
}
$Type = $_GET['type'];

include(SERVER_ROOT.'/classes/class_text.php');
$Text = new TEXT;

$Edits = $Cache->get_value($Type.'_edits_'.$PostID);
if(!is_array($Edits)) {
	$DB->query("SELECT ce.EditUser, um.Username, ce.EditTime, ce.Body
			FROM comments_edits AS ce 
				JOIN users_main AS um ON um.ID=ce.EditUser
			WHERE Page = '".$Type."' AND PostID = ".$PostID."
			ORDER BY ce.EditTime DESC");
	$Edits = $DB->to_array();
	$Cache->cache_value($Type.'_edits_'.$PostID, $Edits, 0);
}
	
if($Depth != 0) {
	list($UserID, $Username, $Time, $Body) = $Edits[$Depth - 1];
} else {
	//Not an edit, have to get from the original
	switch($Type) {
		case 'forums' :
			//Get from normal forum stuffs
			$DB->query("SELECT fp.AuthorID, um.Username, fp.AddedTime, fp.Body
					FROM forums_posts AS fp
						JOIN users_main AS um ON um.ID=fp.AuthorID
					WHERE fp.ID = ".$PostID);
			list($UserID, $Username, $Time, $Body) = $DB->next_record();
			break;
		case 'collages' :
		case 'requests' :
		case 'torrents' :
			$DB->query("SELECT c.AuthorID, um.Username, c.AddedTime, c.Body
					FROM ".$Type."_comments AS c
						JOIN users_main AS um ON um.ID=c.AuthorID
					WHERE c.ID = ".$PostID);
			list($UserID, $Username, $Time, $Body) = $DB->next_record();
			break;
	}
}
?>

				<?=$Text->full_format($Body)?>
				<br />
				<br />

<? if($Depth < count($Edits)) { ?>
					<a href="#edit_info_<?=$PostID?>" onclick="LoadEdit('<?=$Type?>', <?=$PostID?>, <?=($Depth + 1)?>); return false;">&laquo;</a>
					<?=(($Depth == 0) ? 'Last edited by' : 'Edited by')?>
					<?=format_username($UserID, $Username) ?> <?=strtolower(time_diff($Time))?>
<? } else { ?>
					<em>Original Post</em>
<? }

if($Depth > 0) { ?>
					<a href="#edit_info_<?=$PostID?>" onclick="LoadEdit('<?=$Type?>', <?=$PostID?>, <?=($Depth - 1)?>); return false;">&raquo;</a>
<? } ?>

