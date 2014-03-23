<?
enforce_login();
if (!defined('LOG_ENTRIES_PER_PAGE')) {
	define('LOG_ENTRIES_PER_PAGE', 100);
}
View::show_header("Site log");

include(SERVER_ROOT.'/sections/log/sphinx.php');
?>
<div class="thin">
	<div class="header">
		<h2>Site log</h2>
	</div>
	<div class="box pad">
		<form class="search_form" name="log" action="" method="get">
			<table cellpadding="6" cellspacing="1" border="0" class="layout border" width="100%">
				<tr>
					<td class="label"><strong>Search for:</strong></td>
					<td>
						<input type="search" name="search" size="60"<?=(!empty($_GET['search']) ? ' value="'.display_str($_GET['search']).'"' : '')?> />
						&nbsp;
						<input type="submit" value="Search log" />
					</td>
				</tr>
			</table>
		</form>
	</div>

<?	if ($TotalMatches > LOG_ENTRIES_PER_PAGE) { ?>
	<div class="linkbox">
<?
	$Pages = Format::get_pages($Page, $TotalMatches, LOG_ENTRIES_PER_PAGE, 9);
	echo $Pages;?>
	</div>
<?	} ?>
	<table cellpadding="6" cellspacing="1" border="0" class="log_table border" id="log_table" width="100%">
		<tr class="colhead">
			<td style="width: 180px;"><strong>Time</strong></td>
			<td><strong>Message</strong></td>
		</tr>
<?	if ($QueryStatus) { ?>
	<tr class="nobr"><td colspan="2">Search request failed (<?=$QueryError?>).</td></tr>
<?	} elseif (!$DB->has_results()) { ?>
	<tr class="nobr"><td colspan="2">Nothing found!</td></tr>
<?
	}
$Row = 'a';
$Usernames = array();
while (list($ID, $Message, $LogTime) = $DB->next_record()) {
	$MessageParts = explode(' ', $Message);
	$Message = '';
	$Color = $Colon = false;
	for ($i = 0, $PartCount = sizeof($MessageParts); $i < $PartCount; $i++) {
		if ((strpos($MessageParts[$i], 'https://'.SSL_SITE_URL) === 0
				&& $Offset = strlen('https://'.SSL_SITE_URL.'/'))
			|| (strpos($MessageParts[$i], 'http://'.NONSSL_SITE_URL) === 0
				&& $Offset = strlen('http://'.NONSSL_SITE_URL.'/'))
			) {
				$MessageParts[$i] = '<a href="'.substr($MessageParts[$i], $Offset).'">'.substr($MessageParts[$i], $Offset).'</a>';
		}
		switch ($MessageParts[$i]) {
			case 'Torrent':
			case 'torrent':
				$TorrentID = $MessageParts[$i + 1];
				if (is_numeric($TorrentID)) {
					$Message = $Message.' '.$MessageParts[$i]." <a href=\"torrents.php?torrentid=$TorrentID\">$TorrentID</a>";
					$i++;
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				break;
			case 'Request':
				$RequestID = $MessageParts[$i + 1];
				if (is_numeric($RequestID)) {
					$Message = $Message.' '.$MessageParts[$i]." <a href=\"requests.php?action=view&amp;id=$RequestID\">$RequestID</a>";
					$i++;
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				break;
			case 'Artist':
			case 'artist':
				$ArtistID = $MessageParts[$i + 1];
				if (is_numeric($ArtistID)) {
					$Message = $Message.' '.$MessageParts[$i]." <a href=\"artist.php?id=$ArtistID\">$ArtistID</a>";
					$i++;
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				break;
			case 'group':
			case 'Group':
				$GroupID = $MessageParts[$i + 1];
				if (is_numeric($GroupID)) {
					$Message = $Message.' '.$MessageParts[$i]." <a href=\"torrents.php?id=$GroupID\">$GroupID</a>";
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				$i++;
				break;
			case 'by':
				$UserID = 0;
				$User = '';
				$URL = '';
				if ($MessageParts[$i + 1] == 'user') {
					$i++;
					if (is_numeric($MessageParts[$i + 1])) {
						$UserID = $MessageParts[++$i];
					}
					$URL = "user $UserID (<a href=\"user.php?id=$UserID\">".substr($MessageParts[++$i], 1, -1).'</a>)';
				} elseif (in_array($MessageParts[$i - 1], array('deleted', 'uploaded', 'edited', 'created', 'recovered'))) {
					$User = $MessageParts[++$i];
					if (substr($User, -1) == ':') {
						$User = substr($User, 0, -1);
						$Colon = true;
					}
					if (!isset($Usernames[$User])) {
						$DB->query("
							SELECT ID
							FROM users_main
							WHERE Username = _utf8 '" . db_string($User) . "'
							COLLATE utf8_bin");
						list($UserID) = $DB->next_record();
						$Usernames[$User] = $UserID ? $UserID : '';
					} else {
						$UserID = $Usernames[$User];
					}
					$DB->set_query_id($Log);
					$URL = $Usernames[$User] ? "<a href=\"user.php?id=$UserID\">$User</a>".($Colon ? ':' : '') : $User;
				}
				$Message = "$Message by $URL";
				break;
			case 'uploaded':
				if ($Color === false) {
					$Color = 'green';
				}
				$Message = $Message.' '.$MessageParts[$i];
				break;
			case 'deleted':
				if ($Color === false || $Color === 'green') {
					$Color = 'red';
				}
				$Message = $Message.' '.$MessageParts[$i];
				break;
			case 'edited':
				if ($Color === false) {
					$Color = 'blue';
				}
				$Message = $Message.' '.$MessageParts[$i];
				break;
			case 'un-filled':
				if ($Color === false) {
					$Color = '';
				}
				$Message = $Message.' '.$MessageParts[$i];
				break;
			case 'marked':
				if ($i == 1) {
					$User = $MessageParts[$i - 1];
					if (!isset($Usernames[$User])) {
						$DB->query("
							SELECT ID
							FROM users_main
							WHERE Username = _utf8 '" . db_string($User) . "'
							COLLATE utf8_bin");
						list($UserID) = $DB->next_record();
						$Usernames[$User] = $UserID ? $UserID : '';
						$DB->set_query_id($Log);
					} else {
						$UserID = $Usernames[$User];
					}
					$URL = $Usernames[$User] ? "<a href=\"user.php?id=$UserID\">$User</a>" : $User;
					$Message = $URL." ".$MessageParts[$i];
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				break;
			case 'Collage':
				$CollageID = $MessageParts[$i + 1];
				if (is_numeric($CollageID)) {
					$Message = $Message.' '.$MessageParts[$i]." <a href=\"collages.php?id=$CollageID\">$CollageID</a>";
					$i++;
				} else {
					$Message = $Message.' '.$MessageParts[$i];
				}
				break;
			default:
				$Message = $Message.' '.$MessageParts[$i];
		}
	}
	$Row = $Row === 'a' ? 'b' : 'a';
?>
		<tr class="row<?=$Row?>" id="log_<?=$ID?>">
			<td class="nobr">
				<?=time_diff($LogTime)?>
			</td>
			<td>
				<span<? if ($Color) { ?> style="color: <?=$Color?>;"<? } ?>><?=$Message?></span>
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
View::show_footer(); ?>
