<?
/************************************************************************
||------------------|| User token history page ||-----------------------||
This page lists the torrents a user has spent his tokens on. It
gets called if $_GET['action'] == 'token_history'.

Using $_GET['userid'] allows a mod to see any user's token history.
Nonmods and empty userid show $LoggedUser['ID']'s history
************************************************************************/

if (isset($_GET['userid'])) {
	if (($_GET['userid'] == $LoggedUser['ID']) || check_perms('users_mod')) {
		$UserID = $_GET['userid'];
	} else {
		error(403);
	}
} else {
	$UserID = $LoggedUser['ID'];
}

if (!is_number($UserID)) { error(404); }

$UserInfo = user_info($UserID);

if (isset($_GET['expire'])) {
	if (!check_perms('users_mod')) { error(403); }
	$UserID = $_GET['userid'];
	$TorrentID = $_GET['torrentid'];
	
	if (!is_number($UserID) || !is_number($TorrentID)) { error(403); }
	$DB->query("SELECT info_hash FROM torrents where ID = $TorrentID");
	if (list($InfoHash) = $DB->next_record(MYSQLI_NUM, FALSE)) {
		$DB->query("UPDATE users_freeleeches SET Expired=TRUE WHERE UserID=$UserID AND TorrentID=$TorrentID");
		$Cache->delete_value('users_tokens_'.$UserID);
		update_tracker('remove_token', array('info_hash' => rawurlencode($InfoHash), 'userid' => $UserID));
	}
	header("Location: userhistory.php?action=token_history&userid=$UserID");
}

show_header('Freeleech token history');

list($Page,$Limit) = page_limit(25);

$DB->query("SELECT SQL_CALC_FOUND_ROWS
			   f.TorrentID,
			   t.GroupID,
			   f.Time,
			   f.Expired,			
			   f.Downloaded,
			   g.Name,
			   t.Format,
			   t.Encoding
			FROM users_freeleeches AS f
			JOIN torrents AS t ON t.ID = f.TorrentID
			JOIN torrents_group AS g ON g.ID = t.GroupID
			WHERE f.UserID = $UserID
			ORDER BY f.Time DESC
			LIMIT $Limit");
$Tokens = $DB->to_array();

$DB->query("SELECT FOUND_ROWS()");
list($NumResults) = $DB->next_record();
$Pages=get_pages($Page, $NumResults, 25);

?>
<h2>Freeleech token history for <?=format_username($UserID, $UserInfo['Username'], $UserInfo['Donor'], $UserInfo['Warned'], $UserInfo['Enabled'])?></h2>

<div class="linkbox"><?=$Pages?></div>
<table>
	<tr class="colhead_dark">
		<td>Torrent</td>
		<td>Time</td>
		<td>Expired</td>
<? if (check_perms('users_mod')) { ?>
		<td>Downloaded</td>
<? } ?>
	</tr>
<?
$i = true;
foreach ($Tokens as $Token) {
	$i = !$i;
	list($TorrentID, $GroupID, $Time, $Expired, $Downloaded, $Name, $Format, $Encoding) = $Token; 
	$ArtistName = display_artists(get_artist($GroupID));
	if($ArtistName) {
		$Name = $ArtistName."<a href=\"torrents.php?torrentid=$TorrentID\">$Name</a>";
	}
	if($Format && $Encoding) {
		$Name.=' ['.$Format.' / '.$Encoding.']';
	}
?>
	<tr class="<?=($i?'rowa':'rowb')?>">
		<td><?=$Name?></td>
		<td><?=time_diff($Time)?></td>
		<td><?=($Expired ? 'Yes' : 'No')?><?=(check_perms('users_mod') && !$Expired)?" <a href=\"userhistory.php?action=token_history&expire=1&userid=$UserID&torrentid=$TorrentID\">(expire)</a>":''?>
		</td>
<?	if (check_perms('users_mod')) { ?>
		<td><?=get_size($Downloaded)?></td>
<?	} ?>
	</tr>
<? }
?>
</table>
<div class="linkbox"><?=$Pages?></div>
<?
show_footer();
?>