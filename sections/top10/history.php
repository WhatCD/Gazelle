<?
if(!check_perms('users_mod')) { error(404); }
//if(!check_perms('site_top10_history')) { error(403); }
show_header('Top 10 Torrents history!');
?>
<div class="thin">
	<h2> Top 10 Torrents </h2>
	<div class="linkbox">
		<a href="top10.php?type=torrents"><strong>[Torrents]</strong></a>
		<a href="top10.php?type=users">[Users]</a>
		<a href="top10.php?type=tags">[Tags]</a>
		<a href="top10.php?type=history">[History]</a>
	</div>

	<div class="pad box">
		<form method="get" action="">
			<input type="hidden" name="type" value="history" />
			<h3>Search for a date! (After 2010-09-05)</h3>
			<table>
				<tr>
					<td class="label">Date:</td>
					<td><input type="text" id="date" name="date" value="<?=!empty($_GET['date']) ? display_str($_GET['date']) : 'YYYY-MM-DD'?>" onfocus="if($('#date').raw().value == 'YYYY-MM-DD') $('#date').raw().value = ''" /></td>
				</tr>
				<tr>
					<td class="label">Type:</td>
					<td>
						<input type="radio" name="datetype" value="day" checked="checked"> Day
						<input type="radio" name="datetype" value="week"> Week
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<input type="submit" value="Submit" />
					</td>
				</tr>
			</table>
		</form>
	</div>
<?
if(!empty($_GET['date'])) {
	$Date = $_GET['date'];
	$SQLTime = $Date." 00:00:00";
	if(!validDate($SQLTime)) {
		error('Something is wrong with the date you provided');
	}

	if(empty($_GET['datetype']) || $_GET['datetype'] == "day") {
		$Type = 'day';
		$Where = "WHERE th.Date BETWEEN '".$SQLTime."' AND '".$SQLTime."' + INTERVAL 24 HOUR AND Type='Daily'";
	} else {
		$Type = 'week';
		$Where = "WHERE th.Date BETWEEN '".$SQLTime."' - AND '".$SQLTime."' + INTERVAL 7 DAY' AND Type='Weekly'";
	}

	$Details = $Cache->get_value('top10_history_'.$SQLTime);
	if($Details === false) {
		$DB->query("SELECT
				tht.Rank,
				tht.TitleString,
				tht.TagString,
				tht.TorrentID,
				g.ID,
				g.Name,
				g.CategoryID,
				g.TagList,
				t.Format,
				t.Encoding,
				t.Media,
				t.Scene,
				t.HasLog,
				t.HasCue,
				t.LogScore,
				t.RemasterYear,
				g.Year,
				t.RemasterTitle
			FROM top10_history AS th
				LEFT JOIN top10_history_torrents AS tht ON tht.HistoryID = th.ID
				LEFT JOIN torrents AS t ON t.ID = tht.TorrentID
				LEFT JOIN torrents_group AS g ON g.ID = t.GroupID
			".$Where."
			ORDER BY tht.Rank ASC");
			
		$Details = $DB->to_array();

		$Cache->cache_value('top10_history_'.$SQLTime, $Details, 3600*24);
	}
?>

	<br />
	<div class="pad box">
		<h3>Top 10 for <?=($Type == 'day' ? $Date : 'the first week after '.$Date)?></h3>
	<table class="border">
	<tr class="colhead">
		<td class="center" style="width:15px;"></td>
		<td class="center"></td>
		<td><strong>Name</strong></td>
	</tr>
<?
	foreach ($Details as $Detail) {
		list($Rank, $TitleString, $TagString, $TorrentID, $GroupID,$GroupName,$GroupCategoryID,$TorrentTags,
			$Format,$Encoding,$Media,$Scene,$HasLog,$HasCue,$LogScore,$Year,$GroupYear,
			$RemasterTitle,$Snatched,$Seeders,$Leechers,$Data) = $Detail;

		// highlight every other row
		$Highlight = ($Rank % 2 ? 'a' : 'b');

		if($GroupID) {
			//Group still exists
			$DisplayName='';
			
			$Artists = get_artist($GroupID);
			
			if(!empty($Artists)) {
				$DisplayName = display_artists($Artists, true, true);
			}
			
			$DisplayName .= "<a href='torrents.php?id=$GroupID&amp;torrentid=$TorrentID'  title='View Torrent'>$GroupName</a>";

			if($GroupCategoryID==1 && $GroupYear>0) {
				$DisplayName.= " [$GroupYear]";
			}

			// append extra info to torrent title
			$ExtraInfo='';
			$AddExtra='';
			if($Format) { $ExtraInfo.=$Format; $AddExtra=' / '; }
			if($Encoding) { $ExtraInfo.=$AddExtra.$Encoding; $AddExtra=' / '; }
			"FLAC / Lossless / Log (100%) / Cue / CD";
			if($HasLog) { $ExtraInfo.=$AddExtra."Log (".$LogScore."%)"; $AddExtra=' / '; }
			if($HasCue) { $ExtraInfo.=$AddExtra."Cue"; $AddExtra=' / '; }
			if($Media) { $ExtraInfo.=$AddExtra.$Media; $AddExtra=' / '; }
			if($Scene) { $ExtraInfo.=$AddExtra.'Scene'; $AddExtra=' / '; }
			if($Year>0) { $ExtraInfo.=$AddExtra.$Year; $AddExtra=' '; }
			if($RemasterTitle) { $ExtraInfo.=$AddExtra.$RemasterTitle; }
			if($ExtraInfo!='') {
				$ExtraInfo = "- [$ExtraInfo]";
			}

			$DisplayName .= $ExtraInfo;
			$TorrentTags = explode(' ',$TorrentTags);
		} else {
			$DisplayName = $TitleString." (Deleted)";
			$TorrentTags = $TagString;
		}
		$TagList=array();
		
		$PrimaryTag = '';
		if($TorrentTags!='') {
			foreach ($TorrentTags as $TagKey => $TagName) {
				$TagName = str_replace('_','.',$TagName);
				$TagList[]='<a href="torrents.php?taglist='.$TagName.'">'.$TagName.'</a>';
			}
			$PrimaryTag = $TorrentTags[0];
			$TagList = implode(', ', $TagList);
			$TorrentTags='<br /><div class="tags">'.$TagList.'</div>';
		}
		$TorrentTags = '<br /><div class="tags">'.$TagList.'</div>';
?>
	<tr class="group_torrent row<?=$Highlight?>">
		<td style="padding:8px;text-align:center;"><strong><?=$Rank?></strong></td>
<?
		//fix array offset php error
		if ($GroupCategoryID > 0) {
			$GroupCatOffset = $GroupCategoryID - 1;
		}
?>
		<td class="center cats_col"><div title="<?=ucfirst(str_replace('_',' ',$PrimaryTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCatOffset]))?> tags_<?=str_replace('.','_',$PrimaryTag)?>"></div></td>
		<td>
		<span><?=($GroupID ? '[<a href="torrents.php?action=download&amp;id='.$TorrentID.'&amp;authkey='.$LoggedUser['AuthKey'].'&amp;torrent_pass='.$LoggedUser['torrent_pass'].' title="Download">DL</a>]' : '(Deleted)')?></span>
			<?=$DisplayName?>
			<?=$TorrentTags?>
		</td>
	</tr>
<?
	}
?>
	</table><br />
</div>
</div>
<?
}
show_footer();
?>
