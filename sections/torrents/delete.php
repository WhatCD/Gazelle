<?
$TorrentID = $_GET['torrentid'];
if (!$TorrentID || !is_number($TorrentID)) { error(404); }

$DB->query("SELECT t.UserID, t.Time, COUNT(x.uid) FROM torrents AS t LEFT JOIN xbt_snatched AS x ON x.fid=t.ID WHERE t.ID=".$TorrentID." GROUP BY t.UserID");
if($DB->record_count() < 1) {
	error('Torrent already deleted.');
}
list($UserID, $Time, $Snatches) = $DB->next_record();

if ($LoggedUser['ID']!=$UserID && !check_perms('torrents_delete')) {
	error(403);
}

if(isset($_SESSION['logged_user']['multi_delete']) && $_SESSION['logged_user']['multi_delete']>=3 && !check_perms('torrents_delete_fast')) {
	error('You have recently deleted 3 torrents, please contact a staff member if you need to delete more.');
}

if(time_ago($Time) > 3600*24*7 && !check_perms('torrents_delete')) { // Should this be torrents_delete or torrents_delete_fast?
	error('You can no longer delete this torrent as it has been uploaded for over a week with no problems. If you now think there is a problem, please report it instead.');
}

if($Snatches > 4 && !check_perms('torrents_delete')) { // Should this be torrents_delete or torrents_delete_fast?
	error('You can no longer delete this torrent as it has been snatched by 5 or more users. If you believe there is a problem with the torrent please report it instead.');
}

show_header('Delete torrent', 'reportsv2');
?>
<div class="thin center">
	<div class="box" style="width:600px; margin:0px auto;">
		<div class="head colhead">
			Delete torrent
		</div>
		<div class="pad">
			<form action="torrents.php" method="post">
				<input type="hidden" name="action" value="takedelete" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="torrentid" value="<?=$TorrentID?>" />
				<strong>Reason: </strong>
				<select name="reason">
					<option value="Dead">Dead</option>
					<option value="Dupe">Dupe</option>
					<option value="Trumped">Trumped</option>
					<option value="Rules Broken">Rules broken</option>
					<option value="" selected="selected">Other</option>
				</select>
				&nbsp;
				<strong>Extra info: </strong>
				<input type="text" name="extra" size="30" />
				<input value="Delete" type="submit" />
			</form>
		</div>
	</div>
</div>
<?
if(check_perms('admin_reports')) {
?>
<div id="all_reports" style="width: 80%; margin-left: auto; margin-right: auto">
<?
	require(SERVER_ROOT.'/sections/reportsv2/array.php');
	require(SERVER_ROOT.'/classes/class_text.php');
	$Text = NEW TEXT;
	$ReportID = 0;
	$DB->query("SELECT
			tg.Name,
			tg.ID,
			CASE COUNT(ta.GroupID)
				WHEN 1 THEN aa.ArtistID
				WHEN 0 THEN '0'
				ELSE '0'
			END AS ArtistID,
			CASE COUNT(ta.GroupID)
				WHEN 1 THEN aa.Name
				WHEN 0 THEN ''
				ELSE 'Various Artists'
			END AS ArtistName,
			tg.Year,
			tg.CategoryID,
			t.Time,
			t.Remastered,
			t.RemasterTitle,
			t.RemasterYear,
			t.Media,
			t.Format,
			t.Encoding,
			t.Size,
			t.HasLog,
			t.LogScore,
			t.UserID AS UploaderID,
			uploader.Username
			FROM torrents AS t
			LEFT JOIN torrents_group AS tg ON tg.ID=t.GroupID
			LEFT JOIN torrents_artists AS ta ON ta.GroupID=tg.ID AND ta.Importance='1'
			LEFT JOIN artists_alias AS aa ON aa.AliasID=ta.AliasID
			LEFT JOIN users_main AS uploader ON uploader.ID=t.UserID
			WHERE t.ID=".$TorrentID);

	if($DB->record_count() < 1) {
		die();
	}
	list($GroupName, $GroupID, $ArtistID, $ArtistName, $Year, $CategoryID, $Time, $Remastered, $RemasterTitle, 
		$RemasterYear, $Media, $Format, $Encoding, $Size, $HasLog, $LogScore, $UploaderID, $UploaderName) = $DB->next_record();
	
	$Type = 'dupe'; //hardcoded default
	
	if (array_key_exists($Type, $Types[$CategoryID])) {
		$ReportType = $Types[$CategoryID][$Type];
	} else if(array_key_exists($Type,$Types['master'])) {
		$ReportType = $Types['master'][$Type];
	} else {
		//There was a type but it wasn't an option!
		$Type = 'other';
		$ReportType = $Types['master']['other'];
	}
	if ($ArtistID == 0 && empty($ArtistName)) {
		$RawName = $GroupName.($Year ? " ($Year)" : "")." [$Format/$Encoding/$Media]".($Remastered ? " <$RemasterTitle - $RemasterYear>" : "").($HasLog ? " ($LogScore %)" : "")." (".number_format($Size/(1024*1024), 2)." MB)";
		$LinkName = "<a href='torrents.php?id=$GroupID'>$GroupName".($Year ? " ($Year)" : "")."</a> <a href='torrents.php?torrentid=$TorrentID'> [$Format/$Encoding/$Media]".($Remastered ? " &lt;$RemasterTitle - $RemasterYear&gt;" : "")."</a> ".($HasLog ? " <a href='torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID'>(Log: $LogScore %)</a>" : "")." (".number_format($Size/(1024*1024), 2)." MB)";
		$BBName = "[url=torrents.php?id=$GroupID]$GroupName".($Year ? " ($Year)" : "")."[/url] [url=torrents.php?torrentid=$TorrentID][$Format/$Encoding/$Media]".($Remastered ? " <$RemasterTitle - $RemasterYear>" : "")."[/url] ".($HasLog ? " [url=torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID'](Log: $LogScore %)[/url]" : "")." (".number_format($Size/(1024*1024), 2)." MB)";
	} elseif ($ArtistID == 0 && $ArtistName == 'Various Artists') {
		$RawName = "Various Artists - $GroupName".($Year ? " ($Year)" : "")." [$Format/$Encoding/$Media]".($Remastered ? " <$RemasterTitle - $RemasterYear>" : "").($HasLog ? " ($LogScore %)" : "")." (".number_format($Size/(1024*1024), 2)." MB)";
		$LinkName = "Various Artists - <a href='torrents.php?id=$GroupID'>$GroupName".($Year ? " ($Year)" : "")."</a> <a href='torrents.php?torrentid=$TorrentID'> [$Format/$Encoding/$Media]".($Remastered ? " &lt;$RemasterTitle - $RemasterYear&gt;" : "")."</a> ".($HasLog ? " <a href='torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID'>(Log: $LogScore %)</a>" : "")." (".number_format($Size/(1024*1024), 2)." MB)";
		$BBName = "Various Artists - [url=torrents.php?id=$GroupID]$GroupName".($Year ? " ($Year)" : "")."[/url] [url=torrents.php?torrentid=$TorrentID][$Format/$Encoding/$Media]".($Remastered ? " <$RemasterTitle - $RemasterYear>" : "")."[/url] ".($HasLog ? " [url=torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID'](Log: $LogScore %)[/url]" : "")." (".number_format($Size/(1024*1024), 2)." MB)";
	} else {
		$RawName = "$ArtistName - $GroupName".($Year ? " ($Year)" : "")." [$Format/$Encoding/$Media]".($Remastered ? " <$RemasterTitle - $RemasterYear>" : "").($HasLog ? " ($LogScore %)" : "")." (".number_format($Size/(1024*1024), 2)." MB)";
		$LinkName = "<a href='artist.php?id=$ArtistID'>$ArtistName</a> - <a href='torrents.php?id=$GroupID'>$GroupName".($Year ? " ($Year)" : "")."</a> <a href='torrents.php?torrentid=$TorrentID'> [$Format/$Encoding/$Media]".($Remastered ? " &lt;$RemasterTitle - $RemasterYear&gt;" : "")."</a> ".($HasLog ? " <a href='torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID'>(Log: $LogScore %)</a>" : "")." (".number_format($Size/(1024*1024), 2)." MB)";
		$BBName = "[url=artist.php?id=$ArtistID]".$ArtistName."[/url] - [url=torrents.php?id=$GroupID]$GroupName".($Year ? " ($Year)" : "")."[/url] [url=torrents.php?torrentid=$TorrentID][$Format/$Encoding/$Media]".($Remastered ? " <$RemasterTitle - $RemasterYear>" : "")."[/url] ".($HasLog ? " [url=torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID'](Log: $LogScore %)[/url]" : "")." (".number_format($Size/(1024*1024), 2)." MB)";
	}	
?>	
	<div id="report<?=$ReportID?>">
		<form id="report_form<?=$ReportID?>" action="reports.php" method="post">
			<? 
				/*
				* Some of these are for takeresolve, some for the javascript.			
				*/
			?>
			<div>
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" id="newreportid" name="newreportid" value="<?=$ReportID?>" />
				<input type="hidden" id="reportid<?=$ReportID?>" name="reportid" value="<?=$ReportID?>" />
				<input type="hidden" id="torrentid<?=$ReportID?>" name="torrentid" value="<?=$TorrentID?>" />
				<input type="hidden" id="uploader<?=$ReportID?>" name="uploader" value="<?=$UploaderName?>" />
				<input type="hidden" id="uploaderid<?=$ReportID?>" name="uploaderid" value="<?=$UploaderID?>" />
				<input type="hidden" id="reporterid<?=$ReportID?>" name="reporterid" value="<?=$ReporterID?>" />
				<input type="hidden" id="raw_name<?=$ReportID?>" name="raw_name" value="<?=$RawName?>" />
				<input type="hidden" id="type<?=$ReportID?>" name="type" value="<?=$Type?>" />
				<input type="hidden" id="categoryid<?=$ReportID?>" name="categoryid" value="<?=$CategoryID?>" />
				<input type="hidden" id="pm_type<?=$ReportID?>" name="pm_type" value="Uploader" />
				<input type="hidden" id="from_delete<?=$ReportID?>" name="from_delete" value="<?=$GroupID?>" />
			</div>
			<table cellpadding="5">
				<tr>
					<td class="label">Torrent:</td>
					<td colspan="3">
	<?	if(!$GroupID) { ?>
						<a href="log.php?search=Torrent+<?=$TorrentID?>"><?=$TorrentID?></a> (Deleted)
	<?  } else {?>
						<?=$LinkName?>
						<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">[DL]</a>
						uploaded by <a href="user.php?id=<?=$UploaderID?>"><?=$UploaderName?></a> <?=time_diff($Time)?>
						<br />
	<?		$DB->query("SELECT r.ID 
						FROM reportsv2 AS r 
						LEFT JOIN torrents AS t ON t.ID=r.TorrentID 
						WHERE r.Status != 'Resolved'
						AND t.GroupID=$GroupID");
			$GroupOthers = ($DB->record_count());
			
			if($GroupOthers > 0) { ?>
						<div style="text-align: right;">
							<a href="reportsv2.php?view=group&amp;id=<?=$GroupID?>">There <?=(($GroupOthers > 1) ? "are $GroupOthers reports" : "is 1 other report")?> for torrent(s) in this group</a>
						</div>
	<? 		$DB->query("SELECT t.UserID 
						FROM reportsv2 AS r 
						JOIN torrents AS t ON t.ID=r.TorrentID 
						WHERE r.Status != 'Resolved'
						AND t.UserID=$UploaderID");
			$UploaderOthers = ($DB->record_count());

			if($UploaderOthers > 0) { ?>
						<div style="text-align: right;">
							<a href="reportsv2.php?view=uploader&amp;id=<?=$UploaderID?>">There <?=(($UploaderOthers > 1) ? "are $UploaderOthers reports" : "is 1 other report")?> for torrent(s) uploaded by this user</a>
						</div>
	<? 		}
		
			$DB->query("SELECT DISTINCT req.ID,
						req.FillerID,
						um.Username,
						req.TimeFilled
						FROM requests AS req 
						LEFT JOIN torrents AS t ON t.GroupID=req.TorrentID
						LEFT JOIN reportsv2 AS rep ON rep.TorrentID=t.ID
						JOIN users_main AS um ON um.ID=req.FillerID
						WHERE rep.Status != 'Resolved'
						AND req.TorrentID=$TorrentID");
			$Requests = ($DB->record_count());
			if($Requests > 0) { 
				while(list($RequestID, $FillerID, $FillerName, $FilledTime) = $DB->next_record()) {
		?>
							<div style="text-align: right;">
								<a href="user.php?id=<?=$FillerID?>"><?=$FillerName?></a> used a torrent from this group to fill <a href="requests.php?action=viewrequest&amp;id=<?=$RequestID?>">this request</a> <?=time_diff($FilledTime)?>
							</div>
		<?		}
			}
		}
	}
		?>
					</td>
				</tr>
				<? // END REPORTED STUFF :|: BEGIN MOD STUFF ?>
				<tr>
					<td class="label">
						<a href="javascript:Load('<?=$ReportID?>')">Resolve</a>
					</td>
					<td colspan="3">
						<select name="resolve_type" id="resolve_type<?=$ReportID?>" onchange="ChangeResolve(<?=$ReportID?>)">
<?
$TypeList = $Types['master'] + $Types[$CategoryID];
$Priorities = array();
foreach ($TypeList as $Key => $Value) {
	$Priorities[$Key] = $Value['priority'];
}
array_multisort($Priorities, SORT_ASC, $TypeList);

foreach($TypeList as $IType => $Data) {
?>
					<option value="<?=$IType?>"<?=(($Type == $IType)?' selected="selected"':'')?>><?=$Data['title']?></option>
<? } ?>
						</select>
						<span id="options<?=$ReportID?>">
							<span title="Delete Torrent?">	
								<strong>Delete</strong>
								<input type="checkbox" name="delete" id="delete<?=$ReportID?>"<?=($ReportType['resolve_options']['delete']?' checked="checked"':'')?>>
							</span>
							<span title="Warning length in weeks">
								<strong>Warning</strong>
								<select name="warning" id="warning<?=$ReportID?>">
<? for($i = 0; $i < 9; $i++) { ?>
								<option value="<?=$i?>"<?=(($ReportType['resolve_options']['warn'] == $i)?' selected="selected"':'')?>><?=$i?></option>
<? } ?>
								</select>
							</span>
							<span title="Remove upload privileges?">
								<strong>Upload</strong>
								<input type="checkbox" name="upload" id="upload<?=$ReportID?>"<?=($ReportType['resolve_options']['upload']?' checked="checked"':'')?>>
							</span>
						</span>
						</td>
				</tr>
				<tr>
					<td class="label">PM Uploader</td> 
					<td colspan="3">
						<span title="Appended to the regular message unless using send now.">
							<textarea name="uploader_pm" id="uploader_pm<?=$ReportID?>" cols="50" rows="1"></textarea>
						</span>
						<input type="button" value="Send Now" onclick="SendPM(<?=$ReportID?>)" />
					</td>
				</tr>
				<tr>
					<td class="label"><strong>Extra</strong> Log Message:</td> 
					<td>
						<input type="text" name="log_message" id="log_message<?=$ReportID?>" size="40" />
					</td>
					<td class="label"><strong>Extra</strong> Staff Notes:</td> 
					<td>
						<input type="text" name="admin_message" id="admin_message<?=$ReportID?>" size="40" />
					</td>
				</tr>
				<tr>
					<td colspan="4" style="text-align: center;">
						<input type="button" value="Submit" onclick="TakeResolve(<?=$ReportID?>);" />
					</td>
				</tr>
			</table>
		</form>
		<br />
	</div>
</div>
<?
}
show_footer();
?>
