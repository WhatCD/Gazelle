<?
enforce_login();

if (!defined('LOG_ENTRIES_PER_PAGE')) {
	define('LOG_ENTRIES_PER_PAGE', 25);
}
list($Page,$Limit) = page_limit(LOG_ENTRIES_PER_PAGE);

if(!empty($_GET['search'])) {
	$Search = db_string($_GET['search']);
} else {
	$Search = false;
}
$Words = explode(' ', $Search);
$sql = "SELECT
	SQL_CALC_FOUND_ROWS 
	Message,
	Time
	FROM log ";
if($Search) {
	$sql .= "WHERE Message LIKE '%";
	$sql .= implode("%' AND Message LIKE '%", $Words);
	$sql .= "%' ";
}
if(!check_perms('site_view_full_log')) {
	if($Search) {
		$sql.=" AND "; 
	} else {
		$sql.=" WHERE ";
	}
	$sql .= " Time>'".time_minus(3600*24*28)."' ";
}

$sql .= "ORDER BY ID DESC LIMIT $Limit";

show_header("Site log");

$Log = $DB->query($sql);
$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();
$DB->set_query_id($Log);
?>
<div class="thin">
	<h2>Site log</h2>
	<div>
		<form action="" method="get">
			<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
				<tr>
					<td class="label"><strong>Search for:</strong></td>
					<td>
						<input type="text" name="search" size="60"<? if (!empty($_GET['search'])) { echo ' value="'.display_str($_GET['search']).'"'; } ?> />
						&nbsp;
						<input type="submit" value="Search log" />
					</td>
				</tr>
			</table>	
		</form>
	</div>
	
	
	
	<div class="linkbox">
<?
$Pages=get_pages($Page,$Results,LOG_ENTRIES_PER_PAGE,9);
echo $Pages;
?>
	</div>
	
	<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
		<tr class="colhead">
			<td style="width: 180px;"><strong>Time</strong></td>
			<td><strong>Message</strong></td>
		</tr>

<?
if($DB->record_count() == 0) {
	echo '<tr class="nobr"><td colspan="2">Nothing found!</td></tr>';
}
$Row = 'a';
$Usernames = array();
while(list($Message, $LogTime) = $DB->next_record()) {
	$MessageParts = explode(" ", $Message);
	$Message = "";
	$Color = $Colon = false;
	for ($i = 0, $PartCount = sizeof($MessageParts); $i < $PartCount; $i++) {
		if ((strpos($MessageParts[$i], 'https://'.SSL_SITE_URL) === 0 && $Offset = strlen('https://'.SSL_SITE_URL.'/')) ||
				(strpos($MessageParts[$i], 'http://'.NONSSL_SITE_URL) === 0 && $Offset = strlen('http://'.NONSSL_SITE_URL.'/'))) {
			$MessageParts[$i] = '<a href="'.substr($MessageParts[$i], $Offset).'">'.substr($MessageParts[$i], $Offset).'</a>';
		}
		switch ($MessageParts[$i]) {
			case "Torrent":
			case "torrent":
				$TorrentID = $MessageParts[$i + 1];
				if (is_numeric($TorrentID)) {
					$Message = $Message.' '.$MessageParts[$i].' <a href="torrents.php?torrentid='.$TorrentID.'"> '.$TorrentID.'</a>';
					$i++;
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				break;
			case "Request":
				$RequestID = $MessageParts[$i + 1];
				if (is_numeric($RequestID)) {
					$Message = $Message.' '.$MessageParts[$i].' <a href="requests.php?action=view&id='.$RequestID.'"> '.$RequestID.'</a>';
					$i++;
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				break;
			case "Artist":
			case "artist":
				$ArtistID = $MessageParts[$i + 1];
				if (is_numeric($ArtistID)) {
					$Message = $Message.' '.$MessageParts[$i].' <a href="artist.php?id='.$ArtistID.'"> '.$ArtistID.'</a>';
					$i++;
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				break;
			case "group":
			case "Group":
				$GroupID = $MessageParts[$i + 1];
				if (is_numeric($GroupID)) {
					$Message = $Message.' '.$MessageParts[$i].' <a href="torrents.php?id='.$GroupID.'"> '.$GroupID.'</a>';
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				$i++;
				break;
			case "by":
				$UserID = 0;
				$User = "";
				$URL = "";
				if ($MessageParts[$i + 1] == "user") {
					$i++;
					if (is_numeric($MessageParts[$i + 1])) {
						$UserID = $MessageParts[++$i];
					}
					$URL = "user ".$UserID." ".'<a href="user.php?id='.$UserID.'">'.$MessageParts[++$i]."</a>";
				} else {
					$User = $MessageParts[++$i];
					if(substr($User,-1) == ':') {
						$User = substr($User, 0, -1);
						$Colon = true;
					}
					if(!isset($Usernames[$User])) {
						$DB->query("SELECT ID FROM users_main WHERE Username = '".$User."'");
						list($UserID) = $DB->next_record();
						$Usernames[$User] = $UserID ? $UserID : '';
					} else {
						$UserID = $Usernames[$User];
					}
					$DB->set_query_id($Log);
					$URL = $Usernames[$User] ? '<a href="user.php?id='.$UserID.'">'.$User."</a>".($Colon?':':'') : $User;
				}
				$Message = $Message." by ".$URL;
				break;
			case "uploaded":
				if ($Color === false) {
					$Color = 'green';
				}
				$Message = $Message." ".$MessageParts[$i];
				break;
			case "deleted":
				if ($Color === false || $Color === 'green') {
					$Color = 'red';
				}
				$Message = $Message." ".$MessageParts[$i];
				break;
			case "edited":
				if ($Color === false) {
					$Color = 'blue';
				}
				$Message = $Message." ".$MessageParts[$i];
				break;
			case "un-filled":
				if ($Color === false) {
					$Color = '';
				}
				$Message = $Message." ".$MessageParts[$i];
				break;
			case "marked":
				if ($i == 1) {
					$User = $MessageParts[$i - 1];
					if(!isset($Usernames[$User])) {
						$DB->query("SELECT ID FROM users_main WHERE Username = '".$User."'");
						list($UserID) = $DB->next_record();
						$Usernames[$User] = $UserID ? $UserID : '';
						$DB->set_query_id($Log);
					} else {
						$UserID = $Usernames[$User];
					}
					$URL = $Usernames[$User] ? '<a href="user.php?id='.$UserID.'">'.$User."</a>" : $User;
					$Message = $URL." ".$MessageParts[$i];
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				break;
			case "Collage":
				$CollageID = $MessageParts[$i + 1];
				if (is_numeric($CollageID)) {
					$Message = $Message.' '.$MessageParts[$i].' <a href="collages.php?id='.$CollageID.'"> '.$CollageID.'</a>';
					$i++;
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				break;
			default:
				$Message = $Message." ".$MessageParts[$i];
		}
	}
	$Row = ($Row == 'a') ? 'b' : 'a';
?>
		<tr class="row<?=$Row?>">
			<td class="nobr">
				<?=time_diff($LogTime)?>
			</td>
			<td>
				<span<? if($Color) { ?> style="color: <?=$Color ?>;"<? } ?>><?=$Message?></span>
			</td>
		</tr>
<?
}
?>
	</table>
	<div class="linkbox">
		<?=$Pages?>
	</div>
</div>
<?
show_footer() ?>
