<?
/*
 * This is the AJAX page that gets called from the JavaScript
 * function NewReport(), any changes here should probably be
 * replicated on static.php.
 */

if (!check_perms('admin_reports')) {
	error(403);
}


$DB->query("
	SELECT
		r.ID,
		r.ReporterID,
		reporter.Username,
		r.TorrentID,
		r.Type,
		r.UserComment,
		r.ResolverID,
		resolver.Username,
		r.Status,
		r.ReportedTime,
		r.LastChangeTime,
		r.ModComment,
		r.Track,
		r.Image,
		r.ExtraID,
		r.Link,
		r.LogMessage,
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
		t.HasCue,
		t.HasLog,
		t.LogScore,
		t.UserID AS UploaderID,
		uploader.Username
	FROM reportsv2 AS r
		LEFT JOIN torrents AS t ON t.ID = r.TorrentID
		LEFT JOIN torrents_group AS tg ON tg.ID = t.GroupID
		LEFT JOIN torrents_artists AS ta ON ta.GroupID = tg.ID AND ta.Importance = '1'
		LEFT JOIN artists_alias AS aa ON aa.AliasID = ta.AliasID
		LEFT JOIN users_main AS resolver ON resolver.ID = r.ResolverID
		LEFT JOIN users_main AS reporter ON reporter.ID = r.ReporterID
		LEFT JOIN users_main AS uploader ON uploader.ID = t.UserID
	WHERE r.Status = 'New'
	GROUP BY r.ID
	ORDER BY ReportedTime ASC
	LIMIT 1");

		if (!$DB->has_results()) {
			die();
		}

		list($ReportID, $ReporterID, $ReporterName, $TorrentID, $Type, $UserComment, $ResolverID, $ResolverName, $Status, $ReportedTime, $LastChangeTime,
			$ModComment, $Tracks, $Images, $ExtraIDs, $Links, $LogMessage, $GroupName, $GroupID, $ArtistID, $ArtistName, $Year, $CategoryID, $Time, $Remastered, $RemasterTitle,
			$RemasterYear, $Media, $Format, $Encoding, $Size, $HasCue, $HasLog, $LogScore, $UploaderID, $UploaderName) = $DB->next_record(MYSQLI_BOTH, array("ModComment"));

		if (!$GroupID) {
			//Torrent already deleted
			$DB->query("
				UPDATE reportsv2
				SET
					Status = 'Resolved',
					LastChangeTime = '".sqltime()."',
					ModComment = 'Report already dealt with (torrent deleted)'
				WHERE ID = $ReportID");
			$Cache->decrement('num_torrent_reportsv2');
?>
	<div id="report<?=$ReportID?>" class="report box pad center" data-reportid="<?=$ReportID?>">
		<a href="reportsv2.php?view=report&amp;id=<?=$ReportID?>">Report <?=$ReportID?></a> for torrent <?=$TorrentID?> (deleted) has been automatically resolved. <input type="button" value="Clear" onclick="ClearReport(<?=$ReportID?>);" />
	</div>
<?
			die();
		}
		$DB->query("
			UPDATE reportsv2
			SET Status = 'InProgress',
				ResolverID = ".$LoggedUser['ID']."
			WHERE ID = $ReportID");

		if (array_key_exists($Type, $Types[$CategoryID])) {
			$ReportType = $Types[$CategoryID][$Type];
		} elseif (array_key_exists($Type,$Types['master'])) {
			$ReportType = $Types['master'][$Type];
		} else {
			//There was a type but it wasn't an option!
			$Type = 'other';
			$ReportType = $Types['master']['other'];
		}
		$RemasterDisplayString = Reports::format_reports_remaster_info($Remastered, $RemasterTitle, $RemasterYear);

		if ($ArtistID == 0 && empty($ArtistName)) {
			$RawName = $GroupName.($Year ? " ($Year)" : '').($Format || $Encoding || $Media ? " [$Format/$Encoding/$Media]" : '') . $RemasterDisplayString . ($HasCue ? ' (Cue)' : '').($HasLog ? " (Log: {$LogScore}%)" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
			$LinkName = "<a href=\"torrents.php?id=$GroupID\">$GroupName".($Year ? " ($Year)" : '')."</a> <a href=\"torrents.php?torrentid=$TorrentID\">".($Format || $Encoding || $Media ? " [$Format/$Encoding/$Media]" : '') . $RemasterDisplayString . '</a> '.($HasCue ? ' (Cue)' : '').($HasLog ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID\">(Log: {$LogScore}%)</a>" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
			$BBName = "[url=torrents.php?id=$GroupID]$GroupName".($Year ? " ($Year)" : '')."[/url] [url=torrents.php?torrentid=$TorrentID][$Format/$Encoding/$Media]{$RemasterDisplayString}[/url] ".($HasCue ? ' (Cue)' : '').($HasLog ? " [url=torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID](Log: {$LogScore}%)[/url]" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
		} elseif ($ArtistID == 0 && $ArtistName == 'Various Artists') {
			$RawName = "Various Artists - $GroupName".($Year ? " ($Year)" : '')." [$Format/$Encoding/$Media]$RemasterDisplayString" . ($HasCue ? ' (Cue)' : '').($HasLog ? " (Log: {$LogScore}%)" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
			$LinkName = "Various Artists - <a href=\"torrents.php?id=$GroupID\">$GroupName".($Year ? " ($Year)" : '')."</a> <a href=\"torrents.php?torrentid=$TorrentID\"> [$Format/$Encoding/$Media]$RemasterDisplayString</a> ".($HasCue ? ' (Cue)' : '').($HasLog ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID\">(Log: {$LogScore}%)</a>" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
			$BBName = "Various Artists - [url=torrents.php?id=$GroupID]$GroupName".($Year ? " ($Year)" : '')."[/url] [url=torrents.php?torrentid=$TorrentID][$Format/$Encoding/$Media]{$RemasterDisplayString}[/url] ".($HasCue ? ' (Cue)' : '').($HasLog ? " [url=torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID](Log: {$LogScore}%)[/url]" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
		} else {
			$RawName = "$ArtistName - $GroupName".($Year ? " ($Year)" : '')." [$Format/$Encoding/$Media]$RemasterDisplayString" . ($HasCue ? ' (Cue)' : '').($HasLog ? " (Log: {$LogScore}%)" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
			$LinkName = "<a href=\"artist.php?id=$ArtistID\">$ArtistName</a> - <a href=\"torrents.php?id=$GroupID\">$GroupName".($Year ? " ($Year)" : '')."</a> <a href=\"torrents.php?torrentid=$TorrentID\"> [$Format/$Encoding/$Media]$RemasterDisplayString</a> ".($HasCue ? ' (Cue)' : '').($HasLog ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID\">(Log: {$LogScore}%)</a>" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
			$BBName = "[url=artist.php?id=$ArtistID]".$ArtistName."[/url] - [url=torrents.php?id=$GroupID]$GroupName".($Year ? " ($Year)" : '')."[/url] [url=torrents.php?torrentid=$TorrentID][$Format/$Encoding/$Media]{$RemasterDisplayString}[/url] ".($HasCue ? ' (Cue)' : '').($HasLog ? " [url=torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID](Log: {$LogScore}%)[/url]" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
		}
	?>
		<div id="report<?=$ReportID?>" class="report" data-reportid="<?=$ReportID?>">
			<form class="edit_form" name="report" id="reportform_<?=$ReportID?>" action="reports.php" method="post">
<?
					/*
					* Some of these are for takeresolve, some for the JavaScript.
					*/
				?>
				<div>
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" id="reportid<?=$ReportID?>" name="reportid" value="<?=$ReportID?>" />
					<input type="hidden" id="torrentid<?=$ReportID?>" name="torrentid" value="<?=$TorrentID?>" />
					<input type="hidden" id="uploader<?=$ReportID?>" name="uploader" value="<?=$UploaderName?>" />
					<input type="hidden" id="uploaderid<?=$ReportID?>" name="uploaderid" value="<?=$UploaderID?>" />
					<input type="hidden" id="reporterid<?=$ReportID?>" name="reporterid" value="<?=$ReporterID?>" />
					<input type="hidden" id="raw_name<?=$ReportID?>" name="raw_name" value="<?=$RawName?>" />
					<input type="hidden" id="type<?=$ReportID?>" name="type" value="<?=$Type?>" />
					<input type="hidden" id="categoryid<?=$ReportID?>" name="categoryid" value="<?=$CategoryID?>" />
				</div>
				<table class="box layout" cellpadding="5">
					<tr>
						<td class="label"><a href="reportsv2.php?view=report&amp;id=<?=$ReportID?>">Reported</a> torrent:</td>
						<td colspan="3">
<?		if (!$GroupID) { ?>
							<a href="log.php?search=Torrent+<?=$TorrentID?>"><?=$TorrentID?></a> (Deleted)
<?		} else { ?>
							<?=$LinkName?>
							<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download" class="brackets tooltip">DL</a>
							uploaded by <a href="user.php?id=<?=$UploaderID?>"><?=$UploaderName?></a> <?=time_diff($Time)?>
							<br />
							<div style="text-align: right;">was reported by <a href="user.php?id=<?=$ReporterID?>"><?=$ReporterName?></a> <?=time_diff($ReportedTime)?> for the reason: <strong><?=$ReportType['title']?></strong></div>
<?				$DB->query("
						SELECT r.ID
						FROM reportsv2 AS r
							LEFT JOIN torrents AS t ON t.ID = r.TorrentID
						WHERE r.Status != 'Resolved'
							AND t.GroupID = $GroupID");
				$GroupOthers = ($DB->record_count() - 1);

				if ($GroupOthers > 0) { ?>
							<div style="text-align: right;">
								<a href="reportsv2.php?view=group&amp;id=<?=$GroupID?>">There <?=(($GroupOthers > 1) ? "are $GroupOthers other reports" : "is 1 other report")?> for torrents in this group</a>
							</div>
<?				$DB->query("
						SELECT t.UserID
						FROM reportsv2 AS r
							JOIN torrents AS t ON t.ID = r.TorrentID
						WHERE r.Status != 'Resolved'
							AND t.UserID = $UploaderID");
				$UploaderOthers = ($DB->record_count() - 1);

				if ($UploaderOthers > 0) { ?>
							<div style="text-align: right;">
								<a href="reportsv2.php?view=uploader&amp;id=<?=$UploaderID?>">There <?=(($UploaderOthers > 1) ? "are $UploaderOthers other reports" : "is 1 other report")?> for torrents uploaded by this user</a>
							</div>
<?				}

				$DB->query("
						SELECT DISTINCT req.ID,
							req.FillerID,
							um.Username,
							req.TimeFilled
						FROM requests AS req
							LEFT JOIN torrents AS t ON t.ID = req.TorrentID
							LEFT JOIN reportsv2 AS rep ON rep.TorrentID = t.ID
							JOIN users_main AS um ON um.ID = req.FillerID
						WHERE rep.Status != 'Resolved'
							AND req.TimeFilled > '2010-03-04 02:31:49'
							AND req.TorrentID = $TorrentID");
				$Requests = $DB->has_results();
				if ($Requests > 0) {
					while (list($RequestID, $FillerID, $FillerName, $FilledTime) = $DB->next_record()) {
			?>
								<div style="text-align: right;">
									<strong class="important_text"><a href="user.php?id=<?=$FillerID?>"><?=$FillerName?></a> used this torrent to fill <a href="requests.php?action=view&amp;id=<?=$RequestID?>">this request</a> <?=time_diff($FilledTime)?></strong>
								</div>
<?					}
				}
			}
		}
			?>
						</td>
					</tr>
<?			if ($Tracks) { ?>
					<tr>
						<td class="label">Relevant tracks:</td>
						<td colspan="3">
							<?=str_replace(' ', ', ', $Tracks)?>
						</td>
					</tr>
<?			}

			if ($Links) { ?>
					<tr>
						<td class="label">Relevant links:</td>
						<td colspan="3">
<?
				$Links = explode(' ', $Links);
				foreach ($Links as $Link) {

					if ($local_url = Text::local_url($Link)) {
						$Link = $local_url;
					} ?>
							<a href="<?=$Link?>"><?=$Link?></a>
<?
				} ?>
						</td>
					</tr>
<?
			}
			if ($ExtraIDs) { ?>
					<tr>
						<td class="label">Relevant other torrents:</td>
						<td colspan="3">
<?
				$First = true;
				$Extras = explode(' ', $ExtraIDs);
				foreach ($Extras as $ExtraID) {
						$DB->query("
								SELECT
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
									t.Time,
									t.Remastered,
									t.RemasterTitle,
									t.RemasterYear,
									t.Media,
									t.Format,
									t.Encoding,
									t.Size,
									t.HasCue,
									t.HasLog,
									t.LogScore,
									t.UserID AS UploaderID,
									uploader.Username
								FROM torrents AS t
									LEFT JOIN torrents_group AS tg ON tg.ID = t.GroupID
									LEFT JOIN torrents_artists AS ta ON ta.GroupID = tg.ID AND ta.Importance = '1'
									LEFT JOIN artists_alias AS aa ON aa.AliasID = ta.AliasID
									LEFT JOIN users_main AS uploader ON uploader.ID = t.UserID
								WHERE t.ID = '$ExtraID'
								GROUP BY tg.ID");

						list($ExtraGroupName, $ExtraGroupID, $ExtraArtistID, $ExtraArtistName, $ExtraYear, $ExtraTime, $ExtraRemastered, $ExtraRemasterTitle,
							$ExtraRemasterYear, $ExtraMedia, $ExtraFormat, $ExtraEncoding, $ExtraSize, $ExtraHasCue, $ExtraHasLog, $ExtraLogScore, $ExtraUploaderID, $ExtraUploaderName) = Misc::display_array($DB->next_record());


					if ($ExtraGroupName) {
		$ExtraRemasterDisplayString = Reports::format_reports_remaster_info($ExtraRemastered, $ExtraRemasterTitle, $ExtraRemasterYear);

		if ($ArtistID == 0 && empty($ArtistName)) {
			$ExtraLinkName = "<a href=\"torrents.php?id=$ExtraGroupID\">$ExtraGroupName".($ExtraYear ? " ($ExtraYear)" : '')."</a> <a href=\"torrents.php?torrentid=$ExtraID\"> [$ExtraFormat/$ExtraEncoding/$ExtraMedia]$ExtraRemasterDisplayString</a> " . ($ExtraHasLog == '1' ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$ExtraID&amp;groupid=$ExtraGroupID\">(Log: {$ExtraLogScore}%)</a>" : '').' ('.number_format($ExtraSize / (1024 * 1024), 2).' MB)';
		} elseif ($ArtistID == 0 && $ArtistName == 'Various Artists') {
			$ExtraLinkName = "Various Artists - <a href=\"torrents.php?id=$ExtraGroupID\">$ExtraGroupName".($ExtraYear ? " ($ExtraYear)" : '')."</a> <a href=\"torrents.php?torrentid=$ExtraID\"> [$ExtraFormat/$ExtraEncoding/$ExtraMedia]$ExtraRemasterDisplayString</a> " . ($ExtraHasLog == '1' ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$ExtraID&amp;groupid=$ExtraGroupID\">(Log: {$ExtraLogScore}%)</a>" : '').' ('.number_format($ExtraSize / (1024 * 1024), 2).' MB)';
		} else {
			$ExtraLinkName = "<a href=\"artist.php?id=$ExtraArtistID\">$ExtraArtistName</a> - <a href=\"torrents.php?id=$ExtraGroupID\">$ExtraGroupName".($ExtraYear ? " ($ExtraYear)" : '')."</a> <a href=\"torrents.php?torrentid=$ExtraID\"> [$ExtraFormat/$ExtraEncoding/$ExtraMedia]$ExtraRemasterDisplayString</a> " . ($ExtraHasLog == '1' ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$ExtraID&amp;groupid=$ExtraGroupID\">(Log: {$ExtraLogScore}%)</a>" : '').' ('.number_format($ExtraSize / (1024 * 1024), 2).' MB)';
		}
			?>
								<?=($First ? '' : '<br />')?>
								<?=$ExtraLinkName?>
								<a href="torrents.php?action=download&amp;id=<?=$ExtraID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download" class="brackets tooltip">DL</a>
								uploaded by <a href="user.php?id=<?=$ExtraUploaderID?>"><?=$ExtraUploaderName?></a> <?=time_diff($ExtraTime)?> <a href="#" onclick="Switch(<?=$ReportID?>, <?=$TorrentID?>, <?=$ExtraID?>); return false;" class="brackets">Switch</a>
<?
						$First = false;
					}
				} ?>
						</td>
					</tr>
<?
			}
			if ($Images) { ?>
					<tr>
						<td class="label">Relevant images:</td>
						<td colspan="3">
<?
				$Images = explode(' ', $Images);
				foreach ($Images as $Image) {
		?>
							<img style="max-width: 200px;" onclick="lightbox.init(this, 200);" src="<?=ImageTools::process($Image)?>" alt="Relevant image" />
<?
				} ?>
						</td>
					</tr>
<?
			} ?>
					<tr>
						<td class="label">User comment:</td>
						<td colspan="3"><?=Text::full_format($UserComment)?></td>
					</tr>
<?					// END REPORTED STUFF :|: BEGIN MOD STUFF ?>
					<tr>
						<td class="label">Report comment:</td>
						<td colspan="3">
							<input type="text" name="comment" id="comment<?=$ReportID?>" size="70" value="<?=$ModComment?>" />
							<input type="button" value="Update now" onclick="UpdateComment(<?=$ReportID?>);" />
						</td>
					</tr>
					<tr>
						<td class="label">
							<a href="javascript:Load('<?=$ReportID?>')" class="tooltip" title="Click here to reset the resolution options to their default values.">Resolve</a>:
						</td>
						<td colspan="3">
							<select name="resolve_type" id="resolve_type<?=$ReportID?>" onchange="ChangeResolve(<?=$ReportID?>);">
<?
	$TypeList = $Types['master'] + $Types[$CategoryID];
	$Priorities = array();
	foreach ($TypeList as $Key => $Value) {
		$Priorities[$Key] = $Value['priority'];
	}
	array_multisort($Priorities, SORT_ASC, $TypeList);

	foreach ($TypeList as $Type => $Data) {
?>
								<option value="<?=$Type?>"><?=$Data['title']?></option>
<?	} ?>
							</select>
							<span id="options<?=$ReportID?>">
<?	if (check_perms('users_mod')) { ?>
								<span class="tooltip" title="Delete torrent?">
									<label for="delete<?=$ReportID?>"><strong>Delete</strong></label>
									<input type="checkbox" name="delete" id="delete<?=$ReportID?>" />
								</span>
<?	} ?>
								<span class="tooltip" title="Warning length in weeks">
									<label for="warning<?=$ReportID?>"><strong>Warning</strong></label>
									<select name="warning" id="warning<?=$ReportID?>">
<?	for ($i = 0; $i < 9; $i++) { ?>
										<option value="<?=$i?>"><?=$i?></option>
<?	} ?>
									</select>
								</span>
								<span class="tooltip" title="Remove upload privileges?">
									<label for="upload<?=$ReportID?>"><strong>Remove upload privileges</strong></label>
									<input type="checkbox" name="upload" id="upload<?=$ReportID?>" />
								</span>
								&nbsp;&nbsp;
								<span class="tooltip" title="Update resolve type">
									<input type="button" name="update_resolve" id="update_resolve<?=$ReportID?>" value="Update now" onclick="UpdateResolve(<?=$ReportID?>);" />
								</span>
							</span>
						</td>
					</tr>
					<tr>
						<td class="label tooltip" title="Uploader: Appended to the regular message unless using &quot;Send now&quot;. Reporter: Must be used with &quot;Send now&quot;.">
							PM
							<select name="pm_type" id="pm_type<?=$ReportID?>">
								<option value="Uploader">Uploader</option>
								<option value="Reporter">Reporter</option>
							</select>:
						</td>
						<td colspan="3">
							<textarea name="uploader_pm" id="uploader_pm<?=$ReportID?>" cols="50" rows="1"></textarea>
							<input type="button" value="Send now" onclick="SendPM(<?=$ReportID?>);" />
						</td>
					</tr>
					<tr>
						<td class="label"><strong>Extra</strong> log message:</td>
						<td>
							<input type="text" name="log_message" id="log_message<?=$ReportID?>" size="40"<?
									if ($ExtraIDs) {
										$Extras = explode(' ', $ExtraIDs);
										$Value = '';
										foreach ($Extras as $ExtraID) {
											$Value .= site_url()."torrents.php?torrentid=$ExtraID ";
										}
										echo ' value="'.trim($Value).'"';
									} ?> />
						</td>
						<td class="label"><strong>Extra</strong> staff notes:</td>
						<td>
							<input type="text" name="admin_message" id="admin_message<?=$ReportID?>" size="40" />
						</td>
					</tr>
					<tr>
						<td colspan="4" style="text-align: center;">
							<input type="button" value="Invalidate report" onclick="Dismiss(<?=$ReportID?>);" />
							<input type="button" value="Resolve report manually" onclick="ManualResolve(<?=$ReportID?>);" />
							| <input type="button" value="Unclaim" onclick="GiveBack(<?=$ReportID?>);" />
							| <input id="grab<?=$ReportID?>" type="button" value="Claim" onclick="Grab(<?=$ReportID?>);" />
							| Multi-resolve <input type="checkbox" name="multi" id="multi<?=$ReportID?>" checked="checked" />
							| <input type="button" value="Submit" onclick="TakeResolve(<?=$ReportID?>);" />
						</td>
					</tr>
				</table>
			</form>
		</div>
