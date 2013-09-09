<?
// error out on invalid requests (before caching)
if (isset($_GET['details'])) {
	if (in_array($_GET['details'],array('ul','dl','numul','uls','dls'))) {
		$Details = $_GET['details'];
	} else {
		error(404);
	}
} else {
	$Details = 'all';
}

View::show_header('Top 10 Users');
?>
<div class="thin">
	<div class="header">
		<h2>Top 10 Users</h2>
		<? Top10View::render_linkbox("users"); ?>

	</div>
<?

// defaults to 10 (duh)
$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$Limit = in_array($Limit, array(10,100,250)) ? $Limit : 10;

$BaseQuery = "
	SELECT
		u.ID,
		ui.JoinDate,
		u.Uploaded,
		u.Downloaded,
		ABS(u.Uploaded-524288000) / (".time()." - UNIX_TIMESTAMP(ui.JoinDate)) AS UpSpeed,
		u.Downloaded / (".time()." - UNIX_TIMESTAMP(ui.JoinDate)) AS DownSpeed,
		COUNT(t.ID) AS NumUploads
	FROM users_main AS u
		JOIN users_info AS ui ON ui.UserID = u.ID
		LEFT JOIN torrents AS t ON t.UserID=u.ID
	WHERE u.Enabled='1'
		AND Uploaded>'". 5 * 1024 * 1024 * 1024 ."'
		AND Downloaded>'". 5 * 1024 * 1024 * 1024 ."'
		AND (Paranoia IS NULL OR (Paranoia NOT LIKE '%\"uploaded\"%' AND Paranoia NOT LIKE '%\"downloaded\"%'))
	GROUP BY u.ID";

	if ($Details == 'all' || $Details == 'ul') {
		if (!$TopUserUploads = $Cache->get_value('topuser_ul_'.$Limit)) {
			$DB->query("$BaseQuery ORDER BY u.Uploaded DESC LIMIT $Limit;");
			$TopUserUploads = $DB->to_array();
			$Cache->cache_value('topuser_ul_'.$Limit,$TopUserUploads, 3600 * 12);
		}
		generate_user_table('Uploaders', 'ul', $TopUserUploads, $Limit);
	}

	if ($Details == 'all' || $Details == 'dl') {
		if (!$TopUserDownloads = $Cache->get_value('topuser_dl_'.$Limit)) {
			$DB->query("$BaseQuery ORDER BY u.Downloaded DESC LIMIT $Limit;");
			$TopUserDownloads = $DB->to_array();
			$Cache->cache_value('topuser_dl_'.$Limit,$TopUserDownloads, 3600 * 12);
		}
		generate_user_table('Downloaders', 'dl', $TopUserDownloads, $Limit);
	}

	if ($Details == 'all' || $Details == 'numul') {
		if (!$TopUserNumUploads = $Cache->get_value('topuser_numul_'.$Limit)) {
			$DB->query("$BaseQuery ORDER BY NumUploads DESC LIMIT $Limit;");
			$TopUserNumUploads = $DB->to_array();
			$Cache->cache_value('topuser_numul_'.$Limit,$TopUserNumUploads, 3600 * 12);
		}
		generate_user_table('Torrents Uploaded', 'numul', $TopUserNumUploads, $Limit);
	}

	if ($Details == 'all' || $Details == 'uls') {
		if (!$TopUserUploadSpeed = $Cache->get_value('topuser_ulspeed_'.$Limit)) {
			$DB->query("$BaseQuery ORDER BY UpSpeed DESC LIMIT $Limit;");
			$TopUserUploadSpeed = $DB->to_array();
			$Cache->cache_value('topuser_ulspeed_'.$Limit,$TopUserUploadSpeed, 3600 * 12);
		}
		generate_user_table('Fastest Uploaders', 'uls', $TopUserUploadSpeed, $Limit);
	}

	if ($Details == 'all' || $Details == 'dls') {
		if (!$TopUserDownloadSpeed = $Cache->get_value('topuser_dlspeed_'.$Limit)) {
			$DB->query("$BaseQuery ORDER BY DownSpeed DESC LIMIT $Limit;");
			$TopUserDownloadSpeed = $DB->to_array();
			$Cache->cache_value('topuser_dlspeed_'.$Limit,$TopUserDownloadSpeed, 3600 * 12);
		}
		generate_user_table('Fastest Downloaders', 'dls', $TopUserDownloadSpeed, $Limit);
	}



echo '</div>';
View::show_footer();
exit;

// generate a table based on data from most recent query to $DB
function generate_user_table($Caption, $Tag, $Details, $Limit) {
	global $Time;
?>
	<h3>Top <?=$Limit.' '.$Caption;?>
		<small class="top10_quantity_links">
<?
	switch ($Limit) {
		case 100: ?>
			- <a href="top10.php?type=users&amp;details=<?=$Tag?>" class="brackets">Top 10</a>
			- <span class="brackets">Top 100</span>
			- <a href="top10.php?type=users&amp;limit=250&amp;details=<?=$Tag?>" class="brackets">Top 250</a>
		<?	break;
		case 250: ?>
			- <a href="top10.php?type=users&amp;details=<?=$Tag?>" class="brackets">Top 10</a>
			- <a href="top10.php?type=users&amp;limit=100&amp;details=<?=$Tag?>" class="brackets">Top 100</a>
			- <span class="brackets">Top 250</span>
		<?	break;
		default: ?>
			- <span class="brackets">Top 10</span>
			- <a href="top10.php?type=users&amp;limit=100&amp;details=<?=$Tag?>" class="brackets">Top 100</a>
			- <a href="top10.php?type=users&amp;limit=250&amp;details=<?=$Tag?>" class="brackets">Top 250</a>
<?	} ?>
		</small>
	</h3>
	<table class="border">
	<tr class="colhead">
		<td class="center">Rank</td>
		<td>User</td>
		<td style="text-align: right;">Uploaded</td>
		<td style="text-align: right;">UL speed</td>
		<td style="text-align: right;">Downloaded</td>
		<td style="text-align: right;">DL speed</td>
		<td style="text-align: right;">Uploads</td>
		<td style="text-align: right;">Ratio</td>
		<td style="text-align: right;">Joined</td>
	</tr>
<?
	// in the unlikely event that query finds 0 rows...
	if (empty($Details)) {
		echo '
		<tr class="rowb">
			<td colspan="9" class="center">
				Found no users matching the criteria
			</td>
		</tr>
		</table><br />';
		return;
	}
	$Rank = 0;
	foreach ($Details as $Detail) {
		$Rank++;
		$Highlight = ($Rank % 2 ? 'a' : 'b');
?>
	<tr class="row<?=$Highlight?>">
		<td class="center"><?=$Rank?></td>
		<td><?=Users::format_username($Detail['ID'], false, false, false)?></td>
		<td class="number_column"><?=Format::get_size($Detail['Uploaded'])?></td>
		<td class="number_column tooltip" title="Upload speed is reported in base 2 in bytes per second, not bits per second."><?=Format::get_size($Detail['UpSpeed'])?>/s</td>
		<td class="number_column"><?=Format::get_size($Detail['Downloaded'])?></td>
		<td class="number_column tooltip" title="Download speed is reported in base 2 in bytes per second, not bits per second."><?=Format::get_size($Detail['DownSpeed'])?>/s</td>
		<td class="number_column"><?=number_format($Detail['NumUploads'])?></td>
		<td class="number_column"><?=Format::get_ratio_html($Detail['Uploaded'], $Detail['Downloaded'])?></td>
		<td class="number_column"><?=time_diff($Detail['JoinDate'])?></td>
	</tr>
<?	} ?>
</table><br />
<?
}
?>
