<?
if (!check_perms('users_mod')) { error(403); }

if (isset($_REQUEST['addtokens'])) {
	authorize();
	$Tokens = $_REQUEST['numtokens'];
	
	if (!is_number($Tokens) || ($Tokens < 0)) {	error("Please enter a valid number of tokens."); }
	$sql = "UPDATE users_main SET FLTokens = FLTokens + $Tokens WHERE Enabled = '1'";
	if (!isset($_REQUEST['leechdisabled'])) {
		$sql .= " AND can_leech = 1";
	}
	$DB->query($sql);
	$sql = "SELECT ID FROM users_main WHERE Enabled = '1'";
	if (!isset($_REQUEST['leechdisabled'])) {
		$sql .= " AND can_leech = 1";
	}
	$DB->query($sql);
	while (list($UserID) = $DB->next_record()) {
		$Cache->delete_value('user_info_heavy_'.$UserID);
	}
	$message = "<strong>$Tokens freeleech tokens added to all enabled users" . (!isset($_REQUEST['leechdisabled'])?' with enabled leeching privs':'') . '.</strong><br /><br />';
} elseif (isset($_REQUEST['cleartokens'])) {
	authorize();
	$Tokens = $_REQUEST['numtokens'];
	
	if (!is_number($Tokens) || ($Tokens < 0)) {	error("Please enter a valid number of tokens."); }
	
	if (isset($_REQUEST['onlydrop'])) {
		$Where = "WHERE FLTokens > $Tokens";
	} elseif (!isset($_REQUEST['leechdisabled'])) {
		$Where = "WHERE (Enabled = '1' AND can_leech = 1) OR FLTokens > $Tokens";
	} else {
		$Where = "WHERE Enabled = '1' OR FLTokens > $Tokens";
	}
	$DB->query("SELECT ID FROM users_main $Where");
	$Users = $DB->to_array();
	$DB->query("UPDATE users_main SET FLTokens = $Tokens $Where");
	
	foreach ($Users as $UserID) {
		list($UserID) = $UserID;
		$Cache->delete_value('user_info_heavy_'.$UserID);
	}
	
	$where = "";
} elseif (isset($_REQUEST['expire'])) {
	$Tokens = $_REQUEST['tokens'];
	foreach ($Tokens as $Token) {
		list($UserID, $TorrentID) = explode(',', $Token);
		
		if (empty($UserID) || empty($TorrentID) || !is_number($UserID)) { continue; }
		$DB->query("SELECT info_hash FROM torrents where ID = $TorrentID");
		
		if (list($InfoHash) = $DB->next_record()) {
			$DB->query("UPDATE users_freeleeches SET Expired=TRUE WHERE UserID=$UserID AND TorrentID=$TorrentID");
			$Cache->delete_value('users_tokens_'.$UserID);
			update_tracker('remove_token', array('info_hash' => rawurlencode($InfoHash), 'userid' => $UserID));
		}
	}
}

if (empty($_GET['showabusers'])) {
	show_header('Add tokens sitewide');

?>
<h2>Add freeleech tokens to all enabled users</h2>

<div class="linkbox"><a href="tools.php?action=tokens&showabusers=1">[Show Abusers]</a></div>
<div class="box pad" style="margin-left: auto; margin-right: auto; text-align:center; max-width: 40%">
	<?=$message?>
	<form action="" method="post">
		<input type="hidden" name="action" value="tokens" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		Tokens to add: <input type="text" name="numtokens" size="5" style="text-align: right" value="0"><br /><br />
		<label for="leechdisabled">Grant tokens to leech disabled users: </label><input type="checkbox" id="leechdisabled" name="leechdisabled" value="1"><br /><br />
		<input type="submit" name="addtokens" value="Add tokens">
	</form>
</div>
<br />
<div class="box pad" style="margin-left: auto; margin-right: auto; text-align:center; max-width: 40%">
	<?=$message?>
	<form action="" method="post">
		<input type="hidden" name="action" value="tokens" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		Tokens to set: <input type="text" name="numtokens" size="5" style="text-align: right" value="0"><br /><br />
		<span id="droptokens" class=""><label for="onlydrop">Only affect users with at least this many tokens: </label><input type="checkbox" id="onlydrop" name="onlydrop" value="1" onChange="$('#disabled').toggle();return true;"></span><br />
		<span id="disabled" class=""><label for="leechdisabled">Also add tokens (as needed) to leech disabled users: </label><input type="checkbox" id="leechdisabled" name="leechdisabled" value="1" onChange="$('#droptokens').toggle();return true;"></span><br /><br />
		<input type="submit" name="cleartokens" value="Set token total">
	</form>
</div>
<?
} else {
	show_header('FL Token Abusers');
	$Expired = $_GET['expired'] ? 1 : 0;
	$Ratio = $_REQUEST['ratio'] ? db_string($_REQUEST['ratio']) : '1.25';
	if (!preg_match('/^\d+\.?\d*$/',$Ratio)) { $Ratio = '1.25'; }
		
	list($Page,$Limit) = page_limit(50);
	
	$SQL = "SELECT SQL_CALC_FOUND_ROWS
			   m.ID, m.Username, i.Donor, i.Warned, m.Enabled,
			   t.ID, t.GroupID, t.Size, g.Name,
			   fl.Downloaded, fl.Expired
			FROM users_freeleeches AS fl
			JOIN users_main AS m ON m.ID = fl.UserID
			JOIN torrents   AS t ON t.ID = fl.TorrentID
			JOIN users_info AS i ON m.ID = i.UserID
			JOIN torrents_group AS g ON g.ID = t.GroupID
			WHERE fl.Downloaded/t.Size >= $Ratio ";
	if (!$Expired) {
		$SQL .= "AND fl.Expired = 0 ";
	}
	$SQL .=	"ORDER BY fl.Downloaded/t.Size
			 LIMIT $Limit";
	$DB->query($SQL);
	$Pages = get_pages($Page, $DB->record_count(), 50);
	$Abuses = $DB->to_array();
?>
<h2>Freeleech token abusers</h2>

<div class="linkbox">
	<a href="tools.php?action=tokens&showabusers=1&ratio=<?=$Ratio?>&expired=<?=($Expired ? '0' : '1')?>">[<?=($Expired ? 'Hide' : 'Show')?> Expired Tokens]</a>&nbsp;&nbsp;&nbsp;
	<a href="tools.php?action=tokens&showabusers=0">[Add Tokens]</a>
</div>

<div class="linkbox pager"><?=$Pages?></div>
<form action="tools.php?action=tokens&showabusers=1&expired=<?=$Expired?>&page=<?=$Page?>" method="post">
	<input type="hidden" name="ratio" value="<?=$Ratio?>" />
	<table>
		<tr class="colhead_dark">
<?	if ($Expired != '1') { ?>		
			<td><!--Checkbox--></td>
<?  } ?>
			<td>User</td>
			<td>Torrent</td>
			<td>Size</td>
			<td>Transferred</td>
			<td>Ratio</td>
<?	if ($Expired) { ?>
			<td>Expired</td>
<?	} ?>
		</tr>
<?
	$i = true;
	foreach ($Abuses as $TokenInfo) {
		$i = !$i;
		list($UserID, $Username, $Donor, $Warned, $Enabled, $TorrentID, $GroupID, $Size, $Name, $Downloaded, $IsExpired) = $TokenInfo;
		$ArtistName = display_artists(get_artist($GroupID));
		if($ArtistName) {
			$Name = $ArtistName."<a href=\"torrents.php?torrentid=$TorrentID\">$Name</a>";
		}
		if($Format && $Encoding) {
			$Name.=' ['.$Format.' / '.$Encoding.']';
		}
?>
		<tr class="<?=($i?'rowa':'rowb')?>">
<?		if ($Expired != '1') { ?>	
			<td><input type="checkbox" name="tokens[]" value="<?=$UserID?>,<?=$TorrentID?>" /></td>
<?  	} ?>
			<td><?=format_username($UserID, $Username, $Donor, $Warned, $Enabled)?></td>
			<td><?=$Name?></td>
			<td><?=get_size($Size)?></td>
			<td><?=get_size($Downloaded)?></td>
			<td><?=number_format($Downloaded/$Size, 2)?></td>
<?		if ($Expired) { ?>
			<td><?=($IsExpired ? 'Yes' : 'No')?></td>
<?		} ?>
		</tr>
<?	}  ?>
<?	if ($Expired != '1') { ?>	
		<tr>
			<td colspan="<?=($Expired?'7':'6')?>"><input type="submit" name="expire" value="Expire Selected"></td>
		</tr>
<?	} ?>
	</table>
</form>
<div class="linkbox pager"><?=$Pages?></div>
<div class="box pad">
	<form action="tools.php" method="get">
		<input type="hidden" name="action" value="tokens" />
		<input type="hidden" name="showabusers" value="1" />
		<input type="hidden" name="expired" value="<?=$Expired?>" />
		<label for="ratiobox">Abuse ratio: </label><input type="text" size="5" name="ratio" id="ratiobox" value="<?=$Ratio?>" />
	</form>
</div>
<? } 
show_footer()
?>