<?php
if (!check_perms('site_view_flow')) {
	error(403);
}
View::show_header('Upscale Pool');
define('USERS_PER_PAGE', 50);
list($Page, $Limit) = Format::page_limit(USERS_PER_PAGE);

$RS = $DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		m.ID,
		m.Username,
		m.Uploaded,
		m.Downloaded,
		m.PermissionID,
		m.Enabled,
		i.Donor,
		i.Warned,
		i.JoinDate,
		i.RatioWatchEnds,
		i.RatioWatchDownload,
		m.RequiredRatio
	FROM users_main AS m
		LEFT JOIN users_info AS i ON i.UserID = m.ID
	WHERE i.RatioWatchEnds != '0000-00-00 00:00:00'
		AND m.Enabled = '1'
	ORDER BY i.RatioWatchEnds ASC
	LIMIT $Limit");
$DB->query('SELECT FOUND_ROWS()');
list($Results) = $DB->next_record();
$DB->query("
	SELECT COUNT(UserID)
	FROM users_info
	WHERE BanDate != '0000-00-00 00:00:00'
		AND BanReason = '2'");
list($TotalDisabled) = $DB->next_record();
$DB->set_query_id($RS);
?>
	<div class="header">
		<h2>Upscale Pool</h2>
	</div>
<?
if ($DB->has_results()) {
?>
	<div class="box pad thin">
		<p>There are currently <?=number_format($Results)?> enabled users on Ratio Watch and <?=number_format($TotalDisabled)?> already disabled.</p>
	</div>
	<div class="linkbox">
<?
	$Pages = Format::get_pages($Page, $Results, USERS_PER_PAGE, 11);
	echo $Pages;
?>
	</div>
	<table width="100%">
		<tr class="colhead">
			<td>User</td>
			<td class="number_column">Uploaded</td>
			<td class="number_column">Downloaded</td>
			<td class="number_column">Ratio</td>
			<td class="number_column">Required Ratio</td>
			<td class="number_column tooltip" title="How much the user needs to upload to meet his or her required ratio">Deficit</td>
			<td class="number_column tooltip" title="How much the user has downloaded on Ratio Watch">Gamble</td>
			<td>Registration Date</td>
			<td class="tooltip" title="If the time shown here ends in &quot;ago&quot;, then this is how long the user has been on ratio watch and/or below his or her required ratio. If the time shown here does not end in &quot;ago&quot;, then this is the time until the two week Ratio Watch period expires.">Ratio Watch Ended/Ends</td>
			<td>Lifespan</td>
		</tr>
<?
	while (list($UserID, $Username, $Uploaded, $Downloaded, $PermissionID, $Enabled, $Donor, $Warned, $Joined, $RatioWatchEnds, $RatioWatchDownload, $RequiredRatio) = $DB->next_record()) {
	$Row = $Row === 'b' ? 'a' : 'b';

?>
		<tr class="row<?=$Row?>">
			<td><?=Users::format_username($UserID, true, true, true, true)?></td>
			<td class="number_column"><?=Format::get_size($Uploaded)?></td>
			<td class="number_column"><?=Format::get_size($Downloaded)?></td>
			<td class="number_column"><?=Format::get_ratio_html($Uploaded, $Downloaded)?></td>
			<td class="number_column"><?=number_format($RequiredRatio, 2)?></td>
			<td class="number_column"><? if (($Downloaded * $RequiredRatio) > $Uploaded) { echo Format::get_size(($Downloaded * $RequiredRatio) - $Uploaded);} ?></td>
			<td class="number_column"><?=Format::get_size($Downloaded - $RatioWatchDownload)?></td>
			<td><?=time_diff($Joined, 2)?></td>
			<td><?=time_diff($RatioWatchEnds)?></td>
			<td><?//time_diff(strtotime($Joined), strtotime($RatioWatchEnds))?></td>
		</tr>
<?	} ?>
	</table>
	<div class="linkbox">
<?	echo $Pages; ?>
	</div>
<?
} else { ?>
	<h2 align="center">There are currently no users on ratio watch.</h2>
<?
}

View::show_footer();
?>
