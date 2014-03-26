<?php
/*
 * This page is used for viewing reports in every viewpoint except auto.
 * It doesn't AJAX grab a new report when you resolve each one, use auto
 * for that (reports.php). If you wanted to add a new view, you'd simply
 * add to the case statement(s) below and add an entry to views.php to
 * explain it.
 * Any changes made to this page within the foreach loop should probably be
 * replicated on the auto page (reports.php).
 */

if (!check_perms('admin_reports')) {
	error(403);
}

include(SERVER_ROOT.'/classes/reports.class.php');

define('REPORTS_PER_PAGE', '10');
list($Page, $Limit) = Format::page_limit(REPORTS_PER_PAGE);


if (isset($_GET['view'])) {
	$View = $_GET['view'];
} else {
	error(404);
}

if (isset($_GET['id'])) {
	if (!is_number($_GET['id']) && $View !== 'type') {
		error(404);
	} else {
		$ID = db_string($_GET['id']);
	}
} else {
	$ID = '';
}

$Order = 'ORDER BY r.ReportedTime ASC';

if (!$ID) {
	switch ($View) {
		case 'resolved':
			$Title = 'All the old smelly reports';
			$Where = "WHERE r.Status = 'Resolved'";
			$Order = 'ORDER BY r.LastChangeTime DESC';
			break;
		case 'unauto':
			$Title = 'New reports, not auto assigned!';
			$Where = "WHERE r.Status = 'New'";
			break;
		default:
			error(404);
			break;
	}
} else {
	switch ($View) {
		case 'staff':
			$DB->query("
				SELECT Username
				FROM users_main
				WHERE ID = $ID");
			list($Username) = $DB->next_record();
			if ($Username) {
				$Title = "$Username's in-progress reports";
			} else {
				$Title = "$ID's in-progress reports";
			}
			$Where = "
				WHERE r.Status = 'InProgress'
					AND r.ResolverID = $ID";
			break;
		case 'resolver':
			$DB->query("
				SELECT Username
				FROM users_main
				WHERE ID = $ID");
			list($Username) = $DB->next_record();
			if ($Username) {
				$Title = "$Username's resolved reports";
			} else {
				$Title = "$ID's resolved reports";
			}
			$Where = "
				WHERE r.Status = 'Resolved'
					AND r.ResolverID = $ID";
			$Order = 'ORDER BY r.LastChangeTime DESC';
			break;
		case 'group':
			$Title = "Unresolved reports for the group $ID";
			$Where = "
				WHERE r.Status != 'Resolved'
					AND tg.ID = $ID";
			break;
		case 'torrent':
			$Title = "All reports for the torrent $ID";
			$Where = "WHERE r.TorrentID = $ID";
			break;
		case 'report':
			$Title = "Viewing resolution of report $ID";
			$Where = "WHERE r.ID = $ID";
			break;
		case 'reporter':
			$DB->query("
				SELECT Username
				FROM users_main
				WHERE ID = $ID");
			list($Username) = $DB->next_record();
			if ($Username) {
				$Title = "All torrents reported by $Username";
			} else {
				$Title = "All torrents reported by user $ID";
			}
			$Where = "WHERE r.ReporterID = $ID";
			$Order = 'ORDER BY r.ReportedTime DESC';
			break;
		case 'uploader':
			$DB->query("
				SELECT Username
				FROM users_main
				WHERE ID = $ID");
			list($Username) = $DB->next_record();
			if ($Username) {
				$Title = "All reports for torrents uploaded by $Username";
			} else {
				$Title = "All reports for torrents uploaded by user $ID";
			}
			$Where = "
				WHERE r.Status != 'Resolved'
					AND t.UserID = $ID";
			break;
		case 'type':
			$Title = 'All new reports for the chosen type';
			$Where = "
				WHERE r.Status = 'New'
					AND r.Type = '$ID'";
			break;
			break;
		default:
			error(404);
			break;
	}
}


$DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
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
	$Where
	GROUP BY r.ID
	$Order
	LIMIT $Limit");

$Reports = $DB->to_array();

$DB->query('SELECT FOUND_ROWS()');
list($Results) = $DB->next_record();
$PageLinks = Format::get_pages($Page, $Results, REPORTS_PER_PAGE, 11);

View::show_header('Reports V2!', 'reportsv2,bbcode');
?>
<div class="header">
	<h2><?=$Title?></h2>
<?	include('header.php'); ?>
</div>
<div class="buttonbox pad center">
<?	if ($View !== 'resolved') { ?>
	<span class="tooltip" title="Resolves *all* checked reports with their respective resolutions"><input type="button" onclick="MultiResolve();" value="Multi-resolve" /></span>
	<span class="tooltip" title="Assigns all of the reports on the page to you!"><input type="button" onclick="Grab();" value="Claim all" /></span>
<?	}
	if ($View === 'staff' && $LoggedUser['ID'] == $ID) { ?>
	| <span class="tooltip" title="Unclaim all of the reports currently displayed"><input type="button" onclick="GiveBack();" value="Unclaim all" /></span>
<?	} ?>
</div>
<?	if ($PageLinks) { ?>
<div class="linkbox">
	<?=$PageLinks?>
</div>
<?	} ?>
<div id="all_reports" style="width: 80%; margin-left: auto; margin-right: auto;">
<?
if (count($Reports) === 0) {
?>
	<div class="box pad center">
		<strong>No new reports! \o/</strong>
	</div>
<?
} else {
	foreach ($Reports as $Report) {

		list($ReportID, $ReporterID, $ReporterName, $TorrentID, $Type, $UserComment, $ResolverID, $ResolverName, $Status, $ReportedTime, $LastChangeTime,
			$ModComment, $Tracks, $Images, $ExtraIDs, $Links, $LogMessage, $GroupName, $GroupID, $ArtistID, $ArtistName, $Year, $CategoryID, $Time, $Remastered, $RemasterTitle,
			$RemasterYear, $Media, $Format, $Encoding, $Size, $HasCue, $HasLog, $LogScore, $UploaderID, $UploaderName) = Misc::display_array($Report, array('ModComment'));

		if (!$GroupID && $Status != 'Resolved') {
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
	<div id="report<?=$ReportID?>" class="report box pad center">
		<a href="reportsv2.php?view=report&amp;id=<?=$ReportID?>">Report <?=$ReportID?></a> for torrent <?=$TorrentID?> (deleted) has been automatically resolved. <input type="button" value="Hide" onclick="ClearReport(<?=$ReportID?>);" />
	</div>
<?
		} else {
			if (!$CategoryID) {
				//Torrent was deleted
			} else {
				if (array_key_exists($Type, $Types[$CategoryID])) {
					$ReportType = $Types[$CategoryID][$Type];
				} elseif (array_key_exists($Type, $Types['master'])) {
					$ReportType = $Types['master'][$Type];
				} else {
					//There was a type but it wasn't an option!
					$Type = 'other';
					$ReportType = $Types['master']['other'];
				}
			}
			$RemasterDisplayString = Reports::format_reports_remaster_info($Remastered, $RemasterTitle, $RemasterYear);

			if ($ArtistID == 0 && empty($ArtistName)) {
				$RawName = $GroupName.($Year ? " ($Year)" : '').($Format || $Encoding || $Media ? " [$Format/$Encoding/$Media]" : '') . $RemasterDisplayString . ($HasCue ? ' (Cue)' : '').($HasLog ? " (Log: {$LogScore}%)" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';

				$LinkName = "<a href=\"torrents.php?id=$GroupID\">$GroupName".($Year ? " ($Year)" : '')."</a> <a href=\"torrents.php?torrentid=$TorrentID\">".($Format || $Encoding || $Media ? " [$Format/$Encoding/$Media]" : '') . $RemasterDisplayString . '</a> '.($HasCue ? ' (Cue)' : '').($HasLog ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID\">(Log: {$LogScore}%)</a>" : '').' ('.number_format($Size / (1024 * 1024), 2)." MB)";

				$BBName = "[url=torrents.php?id=$GroupID]$GroupName".($Year ? " ($Year)" : '')."[/url] [url=torrents.php?torrentid=$TorrentID][$Format/$Encoding/$Media]{$RemasterDisplayString}[/url] ".($HasCue ? ' (Cue)' : '').($HasLog ? " [url=torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID](Log: {$LogScore}%)[/url]" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
			} elseif ($ArtistID == 0 && $ArtistName == 'Various Artists') {
				$RawName = "Various Artists - $GroupName".($Year ? " ($Year)" : '')." [$Format/$Encoding/$Media]{$RemasterDisplayString}" . ($HasCue ? ' (Cue)' : '').($HasLog ? " (Log: {$LogScore}%)" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';

				$LinkName = "Various Artists - <a href=\"torrents.php?id=$GroupID\">$GroupName".($Year ? " ($Year)" : '')."</a> <a href=\"torrents.php?torrentid=$TorrentID\"> [$Format/$Encoding/$Media]$RemasterDisplayString</a> ".($HasCue ? ' (Cue)' : '').($HasLog ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID\">(Log: {$LogScore}%)</a>" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';

				$BBName = "Various Artists - [url=torrents.php?id=$GroupID]$GroupName".($Year ? " ($Year)" : '')."[/url] [url=torrents.php?torrentid=$TorrentID][$Format/$Encoding/$Media]{$RemasterDisplayString}[/url] ".($HasCue ? ' (Cue)' : '').($HasLog ? " [url=torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID](Log: {$LogScore}%)[/url]" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
			} else {
				$RawName = "$ArtistName - $GroupName".($Year ? " ($Year)" : '')." [$Format/$Encoding/$Media]{$RemasterDisplayString}" . ($HasCue ? ' (Cue)' : '').($HasLog ? " (Log: {$LogScore}%)" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';

				$LinkName = "<a href=\"artist.php?id=$ArtistID\">$ArtistName</a> - <a href=\"torrents.php?id=$GroupID\">$GroupName".($Year ? " ($Year)" : '')."</a> <a href=\"torrents.php?torrentid=$TorrentID\"> [$Format/$Encoding/$Media]{$RemasterDisplayString}</a> ".($HasCue ? ' (Cue)' : '').($HasLog ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID\">(Log: {$LogScore}%)</a>" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';

				$BBName = "[url=artist.php?id=$ArtistID]".$ArtistName."[/url] - [url=torrents.php?id=$GroupID]$GroupName".($Year ? " ($Year)" : '')."[/url] [url=torrents.php?torrentid=$TorrentID][$Format/$Encoding/$Media]{$RemasterDisplayString}[/url] ".($HasCue ? ' (Cue)' : '').($HasLog ? " [url=torrents.php?action=viewlog&amp;torrentid=$TorrentID&amp;groupid=$GroupID](Log: {$LogScore}%)[/url]" : '').' ('.number_format($Size / (1024 * 1024), 2).' MB)';
			}
?>
	<div id="report<?=$ReportID?>">
		<form class="manage_form" name="report" id="reportform_<?=$ReportID?>" action="reports.php" method="post">
<?
/*
* Some of these are for takeresolve, namely the ones that aren't inputs, some for the JavaScript.
*/
?>
			<div>
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" id="reportid<?=$ReportID?>" name="reportid" value="<?=$ReportID?>" />
				<input type="hidden" id="torrentid<?=$ReportID?>" name="torrentid" value="<?=$TorrentID?>" />
				<input type="hidden" id="uploader<?=$ReportID?>" name="uploader" value="<?=$UploaderName?>" />
				<input type="hidden" id="uploaderid<?=$ReportID?>" name="uploaderid" value="<?=$UploaderID?>" />
				<input type="hidden" id="reporterid<?=$ReportID?>" name="reporterid" value="<?=$ReporterID?>" />
				<input type="hidden" id="report_reason<?=$ReportID?>" name="report_reason" value="<?=$UserComment?>" />
				<input type="hidden" id="raw_name<?=$ReportID?>" name="raw_name" value="<?=$RawName?>" />
				<input type="hidden" id="type<?=$ReportID?>" name="type" value="<?=$Type?>" />
				<input type="hidden" id="categoryid<?=$ReportID?>" name="categoryid" value="<?=$CategoryID?>" />
			</div>
			<table class="box layout" cellpadding="5">
				<tr>
					<td class="label"><a href="reportsv2.php?view=report&amp;id=<?=$ReportID?>">Reported</a> torrent:</td>
					<td colspan="3">
<?			if (!$GroupID) { ?>
						<a href="log.php?search=Torrent+<?=$TorrentID?>"><?=$TorrentID?></a> (Deleted)
<?			} else { ?>
						<?=$LinkName?>
						<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download" class="brackets tooltip">DL</a>
						uploaded by <a href="user.php?id=<?=$UploaderID?>"><?=$UploaderName?></a> <?=time_diff($Time)?>
						<br />
<?				if ($ReporterName == '') {
					$ReporterName = 'System';
				} ?>
						<div style="text-align: right;">was reported by <a href="user.php?id=<?=$ReporterID?>"><?=$ReporterName?></a> <?=time_diff($ReportedTime)?> for the reason: <strong><?=$ReportType['title']?></strong></div>
<?				if ($Status != 'Resolved') {

					$DB->query("
						SELECT r.ID
						FROM reportsv2 AS r
							LEFT JOIN torrents AS t ON t.ID = r.TorrentID
						WHERE r.Status != 'Resolved'
							AND t.GroupID = $GroupID");
					$GroupOthers = ($DB->record_count() - 1);

					if ($GroupOthers > 0) { ?>
						<div style="text-align: right;">
							<a href="reportsv2.php?view=group&amp;id=<?=$GroupID?>">There <?=(($GroupOthers > 1) ? "are $GroupOthers other reports" : "is 1 other report")?> for torrent(s) in this group</a>
						</div>
<?					}

					$DB->query("
						SELECT t.UserID
						FROM reportsv2 AS r
							JOIN torrents AS t ON t.ID = r.TorrentID
						WHERE r.Status != 'Resolved'
							AND t.UserID = $UploaderID");
					$UploaderOthers = ($DB->record_count() - 1);

					if ($UploaderOthers > 0) { ?>
						<div style="text-align: right;">
							<a href="reportsv2.php?view=uploader&amp;id=<?=$UploaderID?>">There <?=(($UploaderOthers > 1) ? "are $UploaderOthers other reports" : "is 1 other report")?> for torrent(s) uploaded by this user</a>
						</div>
<?					}

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
					$Requests = ($DB->has_results());
					if ($Requests > 0) {
						while (list($RequestID, $FillerID, $FillerName, $FilledTime) = $DB->next_record()) {
?>
						<div style="text-align: right;">
							<strong class="important_text"><a href="user.php?id=<?=$FillerID?>"><?=$FillerName?></a> used this torrent to fill <a href="requests.php?action=view&amp;id=<?=$RequestID?>">this request</a> <?=time_diff($FilledTime)?></strong>
						</div>
<?						}
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
<?
			}

			if ($Links) { ?>
				<tr>
					<td class="label">Relevant links:</td>
					<td colspan="3">
<?
				$Links = explode(' ', $Links);
				foreach ($Links as $Link) {

					if ($local_url = Text::local_url($Link)) {
						$Link = $local_url;
					}
?>
						<a href="<?=$Link?>"><?=$Link?></a>
<?				} ?>
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
							$ExtraLinkName = "<a href=\"torrents.php?id=$ExtraGroupID\">$ExtraGroupName".($ExtraYear ? " ($ExtraYear)" : '')."</a> <a href=\"torrents.php?torrentid=$ExtraID\"> [$ExtraFormat/$ExtraEncoding/$ExtraMedia]$ExtraRemasterDisplayString</a> " . ($ExtraHasCue == '1' ? ' (Cue)' : '').($ExtraHasLog == '1' ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$ExtraID&amp;groupid=$ExtraGroupID\">(Log: $ExtraLogScore %)</a>" : '').' ('.number_format($ExtraSize / (1024 * 1024), 2).' MB)';
						} elseif ($ArtistID == 0 && $ArtistName == 'Various Artists') {
							$ExtraLinkName = "Various Artists - <a href=\"torrents.php?id=$ExtraGroupID\">$ExtraGroupName".($ExtraYear ? " ($ExtraYear)" : '')."</a> <a href=\"torrents.php?torrentid=$ExtraID\"> [$ExtraFormat/$ExtraEncoding/$ExtraMedia]$ExtraRemasterDisplayString</a> " . ($ExtraHasCue == '1' ? ' (Cue)' : '').($ExtraHasLog == '1' ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$ExtraID&amp;groupid=$ExtraGroupID\">(Log: $ExtraLogScore %)</a>" : '').' ('.number_format($ExtraSize / (1024 * 1024), 2).' MB)';
						} else {
							$ExtraLinkName = "<a href=\"artist.php?id=$ExtraArtistID\">$ExtraArtistName</a> - <a href=\"torrents.php?id=$ExtraGroupID\">$ExtraGroupName".($ExtraYear ? " ($ExtraYear)" : '')."</a> <a href=\"torrents.php?torrentid=$ExtraID\"> [$ExtraFormat/$ExtraEncoding/$ExtraMedia]$ExtraRemasterDisplayString</a> " . ($ExtraHasCue == '1' ? ' (Cue)' : '').($ExtraHasLog == '1' ? " <a href=\"torrents.php?action=viewlog&amp;torrentid=$ExtraID&amp;groupid=$ExtraGroupID\">(Log: $ExtraLogScore %)</a>" : '').' ('.number_format($ExtraSize / (1024 * 1024), 2).' MB)';
						}
?>
						<?=($First ? '' : '<br />')?>
						<?=$ExtraLinkName?>
						<a href="torrents.php?action=download&amp;id=<?=$ExtraID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download" class="brackets tooltip">DL</a>
						uploaded by <a href="user.php?id=<?=$ExtraUploaderID?>"><?=$ExtraUploaderName?></a> <?=time_diff($ExtraTime)?> <a href="#" onclick="Switch(<?=$ReportID?>, <?=$TorrentID?>, <?=$ExtraID?>); return false;" class="brackets">Switch</a>
<?
						$First = false;
					}
				}
?>
					</td>
				</tr>
<?
			}

			if ($Images) {
?>
				<tr>
					<td class="label">Relevant images:</td>
					<td colspan="3">
<?
				$Images = explode(' ', $Images);
				foreach ($Images as $Image) {
?>
						<img style="max-width: 200px;" onclick="lightbox.init(this, 200);" src="<?=ImageTools::process($Image)?>" alt="Relevant image" />
<?				} ?>
					</td>
				</tr>
<?
			} ?>
				<tr>
					<td class="label">User comment:</td>
					<td colspan="3" class="wrap_overflow"><?=Text::full_format($UserComment)?></td>
				</tr>
<?						// END REPORTED STUFF :|: BEGIN MOD STUFF
			if ($Status == 'InProgress') { ?>
				<tr>
					<td class="label">In progress by:</td>
					<td colspan="3">
						<a href="user.php?id=<?=$ResolverID?>"><?=$ResolverName?></a>
					</td>
				</tr>
<?			}
			if ($Status != 'Resolved') { ?>
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

				foreach ($TypeList as $Type => $Data) { ?>
							<option value="<?=$Type?>"><?=$Data['title']?></option>
<?				} ?>
						</select>
						<span id="options<?=$ReportID?>">
<?				if (check_perms('users_mod')) { ?>
							<span class="tooltip" title="Delete torrent?">
								<label for="delete<?=$ReportID?>"><strong>Delete</strong></label>
								<input type="checkbox" name="delete" id="delete<?=$ReportID?>" />
							</span>
<?				} ?>
							<span class="tooltip" title="Warning length in weeks">
								<label for="warning<?=$ReportID?>"><strong>Warning</strong></label>
								<select name="warning" id="warning<?=$ReportID?>">
<?				for ($i = 0; $i < 9; $i++) { ?>
									<option value="<?=$i?>"><?=$i?></option>
<?				} ?>
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
					} ?>
						/>
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
<?					if ($Status == 'InProgress' && $LoggedUser['ID'] == $ResolverID) { ?>
						| <input type="button" value="Unclaim" onclick="GiveBack(<?=$ReportID?>);" />
<?					} else { ?>
						| <input id="grab<?=$ReportID?>" type="button" value="Claim" onclick="Grab(<?=$ReportID?>);" />
<?					}	?>
						| Multi-resolve <input type="checkbox" name="multi" id="multi<?=$ReportID?>" checked="checked" />
						| <input type="button" id="submit_<?=$ReportID?>" value="Submit" onclick="TakeResolve(<?=$ReportID?>);" />
					</td>
				</tr>
<?				} else { ?>
				<tr>
					<td class="label">Resolver:</td>
					<td colspan="3">
						<a href="user.php?id=<?=$ResolverID?>"><?=$ResolverName?></a>
					</td>
				</tr>
				<tr>
					<td class="label">Resolve time:</td>
					<td colspan="3">
						<?=time_diff($LastChangeTime); echo "\n"; ?>
					</td>
				</tr>
				<tr>
					<td class="label">Report comments:</td>
					<td colspan="3">
						<?=$ModComment; echo "\n"; ?>
					</td>
				</tr>
				<tr>
					<td class="label">Log message:</td>
					<td colspan="3">
						<?=$LogMessage; echo "\n"; ?>
					</td>
				</tr>
<?					if ($GroupID) { ?>
				<tr>
					<td	colspan="4" style="text-align: center;">
						<input id="grab<?=$ReportID?>" type="button" value="Claim" onclick="Grab(<?=$ReportID?>);" />
					</td>
				</tr>
<?					}
				} ?>
			</table>
		</form>
	</div>
	<script type="text/javascript">//<![CDATA[
		Load(<?=$ReportID?>);
	//]]>
	</script>
<?
		}
	}
}
?>
</div>
<? if ($PageLinks) { ?>
<div class="linkbox pager"><?=$PageLinks?></div>
<? } ?>
<? View::show_footer(); ?>
