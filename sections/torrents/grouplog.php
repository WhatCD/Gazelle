<?
$GroupID = $_GET['groupid'];
if (!is_number($GroupID)) { error(404); }

show_header("History for Group $GroupID");
?>

<div class="thin">
	<h2>History for Group <?=$GroupID?></h2>

	<table>
		<tr class="colhead">
			<td>Date</td>
			<td>Torrent</td>
			<td>User</td>
			<td>Info</td>
		</tr>
<?
	$Log = $DB->query("SELECT TorrentID, UserID, Info, Time FROM group_log WHERE GroupID = ".$GroupID." ORDER BY Time DESC");

	while (list($TorrentID, $UserID, $Info, $Time) = $DB->next_record())
	{
?>
		<tr class="rowa">
			<td><?=$Time?></td>
<?
			if ($TorrentID != 0) {
				$DB->query("SELECT Media, Format, Encoding FROM torrents WHERE ID=".$TorrentID);
				list($Media, $Format, $Encoding) = $DB->next_record();
				if ($Media == "") { ?>
					<td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a> (Deleted)</td><?
				} else { ?>
					<td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a> (<?=$Format?>/<?=$Encoding?>/<?=$Media?>)</td>
<?				}
			} else { ?>
				<td />
<?			}
			
			$DB->query("SELECT Username FROM users_main WHERE ID = ".$UserID);
			list($Username) = $DB->next_record();
			$DB->set_query_id($Log);
?>
			<td><?=format_username($UserID, $Username)?></td>
			<td><?=$Info?></td>
		</tr>
<?
	}
?>
	</table>
</div>
<?
show_footer();
?>
