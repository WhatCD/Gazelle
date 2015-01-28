<?php
set_time_limit(50000);
ob_end_flush();
gc_enable();

/*
 * Use this if your version of pgrep does not support the '-c' option.
 * The '-c' option requires procps-ng.
 *
 * $PCount = chop(shell_exec("/usr/bin/pgrep -f schedule.php | wc -l"));
 */
$PCount = chop(shell_exec("/usr/bin/pgrep -cf schedule.php"));
if ($PCount > 3) {
	// 3 because the cron job starts two processes and pgrep finds itself
	die("schedule.php is already running. Exiting ($PCount)\n");
}

/*TODO: make it awesome, make it flexible!
INSERT INTO users_geodistribution
	(Code, Users)
SELECT g.Code, COUNT(u.ID) AS Users
FROM geoip_country AS g
	JOIN users_main AS u ON INET_ATON(u.IP) BETWEEN g.StartIP AND g.EndIP
WHERE u.Enabled = '1'
GROUP BY g.Code
ORDER BY Users DESC
*/

/*************************************************************************\
//--------------Schedule page -------------------------------------------//

This page is run every 15 minutes, by cron.

\*************************************************************************/

function next_biweek() {
	$Date = date('d');
	if ($Date < 22 && $Date >= 8) {
		$Return = 22;
	} else {
		$Return = 8;
	}
	return $Return;
}

function next_day() {
	$Tomorrow = time(0, 0, 0, date('m'), date('d') + 1, date('Y'));
	return date('d', $Tomorrow);
}

function next_hour() {
	$Hour = time(date('H') + 1, 0, 0, date('m'), date('d'), date('Y'));
	return date('H', $Hour);
}

if ((!isset($argv[1]) || $argv[1] != SCHEDULE_KEY) && !check_perms('admin_schedule')) { // authorization, Fix to allow people with perms hit this page.
	error(403);
}

if (check_perms('admin_schedule')) {
	authorize();
	View::show_header();
	echo '<pre>';
}

$DB->query("
	SELECT NextHour, NextDay, NextBiWeekly
	FROM schedule");
list($Hour, $Day, $BiWeek) = $DB->next_record();
$NextHour = next_hour();
$NextDay = next_day();
$NextBiWeek = next_biweek();

$DB->query("
	UPDATE schedule
	SET
		NextHour = $NextHour,
		NextDay = $NextDay,
		NextBiWeekly = $NextBiWeek");

$NoDaily = isset($argv[2]) && $argv[2] == 'nodaily';

$sqltime = sqltime();

echo "$sqltime\n";

/*************************************************************************\
//--------------Run every time ------------------------------------------//

These functions are run every time the script is executed (every 15
minutes).

\*************************************************************************/


echo "Ran every-time functions\n";

//------------- Freeleech -----------------------------------------------//

//We use this to control 6 hour freeleeches. They're actually 7 hours, but don't tell anyone.
/*
$TimeMinus = time_minus(3600 * 7);

$DB->query("
	SELECT DISTINCT GroupID
	FROM torrents
	WHERE FreeTorrent = '1'
		AND FreeLeechType = '3'
		AND Time < '$TimeMinus'");
while (list($GroupID) = $DB->next_record()) {
	$Cache->delete_value("torrents_details_$GroupID");
	$Cache->delete_value("torrent_group_$GroupID");
}
$DB->query("
	UPDATE torrents
	SET FreeTorrent = '0',
		FreeLeechType = '0'
	WHERE FreeTorrent = '1'
		AND FreeLeechType = '3'
		AND Time < '$TimeMinus'");
*/
sleep(5);
//------------- Delete unpopular tags -----------------------------------//
$DB->query("
	DELETE FROM torrents_tags
	WHERE NegativeVotes > 1
		AND NegativeVotes > PositiveVotes");

//------------- Expire old FL Tokens and clear cache where needed ------//
$sqltime = sqltime();
$DB->query("
	SELECT DISTINCT UserID
	FROM users_freeleeches
	WHERE Expired = FALSE
		AND Time < '$sqltime' - INTERVAL 4 DAY");
if ($DB->has_results()) {
	while (list($UserID) = $DB->next_record()) {
		$Cache->delete_value('users_tokens_'.$UserID[0]);
	}

	$DB->query("
		SELECT uf.UserID, t.info_hash
		FROM users_freeleeches AS uf
			JOIN torrents AS t ON uf.TorrentID = t.ID
		WHERE uf.Expired = FALSE
			AND uf.Time < '$sqltime' - INTERVAL 4 DAY");
	while (list($UserID, $InfoHash) = $DB->next_record(MYSQLI_NUM, false)) {
		Tracker::update_tracker('remove_token', array('info_hash' => rawurlencode($InfoHash), 'userid' => $UserID));
	}
	$DB->query("
		UPDATE users_freeleeches
		SET Expired = TRUE
		WHERE Time < '$sqltime' - INTERVAL 4 DAY
			AND Expired = FALSE");
}




/*************************************************************************\
//--------------Run every hour ------------------------------------------//

These functions are run every hour.

\*************************************************************************/


if ($Hour != $NextHour || $_GET['runhour'] || isset($argv[2])) {
	echo "Ran hourly functions\n";

	//------------- Front page stats ----------------------------------------//

	//Love or hate, this makes things a hell of a lot faster

	if ($Hour % 2 == 0) {
		$DB->query("
			SELECT COUNT(uid) AS Snatches
			FROM xbt_snatched");
		list($SnatchStats) = $DB->next_record();
		$Cache->cache_value('stats_snatches', $SnatchStats, 0);
	}

	$DB->query("
		SELECT IF(remaining = 0, 'Seeding', 'Leeching') AS Type,
			COUNT(uid)
		FROM xbt_files_users
		WHERE active = 1
		GROUP BY Type");
	$PeerCount = $DB->to_array(0, MYSQLI_NUM, false);
	$SeederCount = isset($PeerCount['Seeding'][1]) ? $PeerCount['Seeding'][1] : 0;
	$LeecherCount = isset($PeerCount['Leeching'][1]) ? $PeerCount['Leeching'][1] : 0;
	$Cache->cache_value('stats_peers', array($LeecherCount, $SeederCount), 0);

	$DB->query("
		SELECT COUNT(ID)
		FROM users_main
		WHERE Enabled = '1'
			AND LastAccess > '".time_minus(3600 * 24)."'");
	list($UserStats['Day']) = $DB->next_record();

	$DB->query("
		SELECT COUNT(ID)
		FROM users_main
		WHERE Enabled = '1'
			AND LastAccess > '".time_minus(3600 * 24 * 7)."'");
	list($UserStats['Week']) = $DB->next_record();

	$DB->query("
		SELECT COUNT(ID)
		FROM users_main
		WHERE Enabled = '1'
			AND LastAccess > '".time_minus(3600 * 24 * 30)."'");
	list($UserStats['Month']) = $DB->next_record();

	$Cache->cache_value('stats_users', $UserStats, 0);

	//------------- Record who's seeding how much, used for ratio watch

	$DB->query("TRUNCATE TABLE users_torrent_history_temp");

	// Find seeders that have announced within the last hour
	$DB->query("
		INSERT INTO users_torrent_history_temp
			(UserID, NumTorrents)
		SELECT uid, COUNT(DISTINCT fid)
		FROM xbt_files_users
		WHERE mtime > unix_timestamp(NOW() - INTERVAL 1 HOUR)
			AND Remaining = 0
		GROUP BY uid");

	// Mark new records as "checked" and set the current time as the time
	// the user started seeding <NumTorrents> seeded.
	// Finished = 1 means that the user hasn't been seeding exactly <NumTorrents> earlier today.
	// This query will only do something if the next one inserted new rows last hour.
	$DB->query("
		UPDATE users_torrent_history AS h
			JOIN users_torrent_history_temp AS t ON t.UserID = h.UserID
					AND t.NumTorrents = h.NumTorrents
		SET h.Finished = '0',
			h.LastTime = UNIX_TIMESTAMP(NOW())
		WHERE h.Finished = '1'
			AND h.Date = UTC_DATE() + 0");

	// Insert new rows for users who haven't been seeding exactly <NumTorrents> torrents earlier today
	// and update the time spent seeding <NumTorrents> torrents for the others.
	// Primary table index: (UserID, NumTorrents, Date).
	$DB->query("
		INSERT INTO users_torrent_history
			(UserID, NumTorrents, Date)
		SELECT UserID, NumTorrents, UTC_DATE() + 0
		FROM users_torrent_history_temp
		ON DUPLICATE KEY UPDATE
			Time = Time + UNIX_TIMESTAMP(NOW()) - LastTime,
			LastTime = UNIX_TIMESTAMP(NOW())");

	//------------- Promote users -------------------------------------------//
	sleep(5);
	$Criteria = array();
	$Criteria[] = array('From' => USER, 'To' => MEMBER,  'MinUpload' => 10 * 1024 * 1024 * 1024,  'MinRatio' => 0.7,  'MinUploads' => 0,  'MaxTime' => time_minus(3600 * 24 * 7));
	$Criteria[] = array('From' => MEMBER, 'To' => POWER, 'MinUpload' => 25 * 1024 * 1024 * 1024,  'MinRatio' => 1.05, 'MinUploads' => 5,  'MaxTime' => time_minus(3600 * 24 * 7 * 2));
	$Criteria[] = array('From' => POWER, 'To' => ELITE,  'MinUpload' => 100 * 1024 * 1024 * 1024, 'MinRatio' => 1.05, 'MinUploads' => 50, 'MaxTime' => time_minus(3600 * 24 * 7 * 4));
	$Criteria[] = array('From' => ELITE, 'To' => TORRENT_MASTER, 'MinUpload' => 500 * 1024 * 1024 * 1024, 'MinRatio' => 1.05, 'MinUploads' => 500, 'MaxTime' => time_minus(3600 * 24 * 7 * 8));
	$Criteria[] = array(
		'From' => TORRENT_MASTER,
		'To' => POWER_TM,
		'MinUpload' => 500 * 1024 * 1024 * 1024,
		'MinRatio' => 1.05,
		'MinUploads' => 500,
		'MaxTime' => time_minus(3600 * 24 * 7 * 8),
		'Extra' => '
				(
					SELECT COUNT(DISTINCT GroupID)
					FROM torrents
					WHERE UserID = users_main.ID
				) >= 500');
	$Criteria[] = array(
		'From' => POWER_TM,
		'To' => ELITE_TM,
		'MinUpload' => 500 * 1024 * 1024 * 1024,
		'MinRatio' => 1.05,
		'MinUploads' => 500,
		'MaxTime' => time_minus(3600 * 24 * 7 * 8),
		'Extra' => "
				(
					SELECT COUNT(ID)
					FROM torrents
					WHERE ((LogScore = 100 AND Format = 'FLAC')
						OR (Media = 'Vinyl' AND Format = 'FLAC')
						OR (Media = 'WEB' AND Format = 'FLAC')
						OR (Media = 'DVD' AND Format = 'FLAC')
						OR (Media = 'Soundboard' AND Format = 'FLAC')
						OR (Media = 'Cassette' AND Format = 'FLAC')
						OR (Media = 'SACD' AND Format = 'FLAC')
						OR (Media = 'Blu-ray' AND Format = 'FLAC')
						OR (Media = 'DAT' AND Format = 'FLAC')
						)
						AND UserID = users_main.ID
				) >= 500");

	 foreach ($Criteria as $L) { // $L = Level
		$Query = "
				SELECT ID
				FROM users_main
					JOIN users_info ON users_main.ID = users_info.UserID
				WHERE PermissionID = ".$L['From']."
					AND Warned = '0000-00-00 00:00:00'
					AND Uploaded >= '$L[MinUpload]'
					AND (Uploaded / Downloaded >= '$L[MinRatio]' OR (Uploaded / Downloaded IS NULL))
					AND JoinDate < '$L[MaxTime]'
					AND (
						SELECT COUNT(ID)
						FROM torrents
						WHERE UserID = users_main.ID
						) >= '$L[MinUploads]'
					AND Enabled = '1'";
		if (!empty($L['Extra'])) {
			$Query .= ' AND '.$L['Extra'];
		}

		$DB->query($Query);

		$UserIDs = $DB->collect('ID');

		if (count($UserIDs) > 0) {
			$DB->query("
				UPDATE users_main
				SET PermissionID = ".$L['To']."
				WHERE ID IN(".implode(',', $UserIDs).')');
			foreach ($UserIDs as $UserID) {
				/*$Cache->begin_transaction("user_info_$UserID");
				$Cache->update_row(false, array('PermissionID' => $L['To']));
				$Cache->commit_transaction(0);*/
				$Cache->delete_value("user_info_$UserID");
				$Cache->delete_value("user_info_heavy_$UserID");
				$Cache->delete_value("user_stats_$UserID");
				$Cache->delete_value("enabled_$UserID");
				$DB->query("
					UPDATE users_info
					SET AdminComment = CONCAT('".sqltime()." - Class changed to ".Users::make_class_string($L['To'])." by System\n\n', AdminComment)
					WHERE UserID = $UserID");
				Misc::send_pm($UserID, 0, 'You have been promoted to '.Users::make_class_string($L['To']), 'Congratulations on your promotion to '.Users::make_class_string($L['To'])."!\n\nTo read more about ".SITE_NAME."'s user classes, read [url=".site_url()."wiki.php?action=article&amp;name=userclasses]this wiki article[/url].");
			}
		}

		// Demote users with less than the required uploads

		$Query = "
			SELECT ID
			FROM users_main
				JOIN users_info ON users_main.ID = users_info.UserID
			WHERE PermissionID = '$L[To]'
				AND ( Uploaded < '$L[MinUpload]'
					OR (
						SELECT COUNT(ID)
						FROM torrents
						WHERE UserID = users_main.ID
						) < '$L[MinUploads]'";
			if (!empty($L['Extra'])) {
				$Query .= ' OR NOT '.$L['Extra'];
			}
			$Query .= "
					)
				AND Enabled = '1'";

		$DB->query($Query);
		$UserIDs = $DB->collect('ID');

		if (count($UserIDs) > 0) {
			$DB->query("
				UPDATE users_main
				SET PermissionID = ".$L['From']."
				WHERE ID IN(".implode(',', $UserIDs).')');
			foreach ($UserIDs as $UserID) {
				/*$Cache->begin_transaction("user_info_$UserID");
				$Cache->update_row(false, array('PermissionID' => $L['From']));
				$Cache->commit_transaction(0);*/
				$Cache->delete_value("user_info_$UserID");
				$Cache->delete_value("user_info_heavy_$UserID");
				$Cache->delete_value("user_stats_$UserID");
				$Cache->delete_value("enabled_$UserID");
				$DB->query("
					UPDATE users_info
					SET AdminComment = CONCAT('".sqltime()." - Class changed to ".Users::make_class_string($L['From'])." by System\n\n', AdminComment)
					WHERE UserID = $UserID");
				Misc::send_pm($UserID, 0, 'You have been demoted to '.Users::make_class_string($L['From']), "You now only qualify for the \"".Users::make_class_string($L['From'])."\" user class.\n\nTo read more about ".SITE_NAME."'s user classes, read [url=".site_url()."wiki.php?action=article&amp;name=userclasses]this wiki article[/url].");
			}
		}
	}


	//------------- Expire invites ------------------------------------------//
	sleep(3);
	$DB->query("
		SELECT InviterID
		FROM invites
		WHERE Expires < '$sqltime'");
	$Users = $DB->to_array();
	foreach ($Users as $UserID) {
		list($UserID) = $UserID;
		$DB->query("
			SELECT Invites, PermissionID
			FROM users_main
			WHERE ID = $UserID");
		list($Invites, $PermID) = $DB->next_record();
		if (($Invites < 2 && $Classes[$PermID]['Level'] <= $Classes[POWER]['Level']) || ($Invites < 4 && $PermID == ELITE)) {
			$DB->query("
				UPDATE users_main
				SET Invites = Invites + 1
				WHERE ID = $UserID");
			$Cache->begin_transaction("user_info_heavy_$UserID");
			$Cache->update_row(false, array('Invites' => '+1'));
			$Cache->commit_transaction(0);
		}
	}
	$DB->query("
		DELETE FROM invites
		WHERE Expires < '$sqltime'");


	//------------- Hide old requests ---------------------------------------//
	sleep(3);
	$DB->query("
		UPDATE requests
		SET Visible = 0
		WHERE TimeFilled < (NOW() - INTERVAL 7 DAY)
			AND TimeFilled != '0000-00-00 00:00:00'");

	//------------- Remove dead peers ---------------------------------------//
	sleep(3);
	$DB->query("
		DELETE FROM xbt_files_users
		WHERE mtime < unix_timestamp(NOW() - INTERVAL 6 HOUR)");

	//------------- Remove dead sessions ---------------------------------------//
	sleep(3);

	$AgoMins = time_minus(60 * 30);
	$AgoDays = time_minus(3600 * 24 * 30);

	$SessionQuery = $DB->query("
			SELECT UserID, SessionID
			FROM users_sessions
			WHERE (LastUpdate < '$AgoDays' AND KeepLogged = '1')
				OR (LastUpdate < '$AgoMins' AND KeepLogged = '0')");
	$DB->query("
		DELETE FROM users_sessions
		WHERE (LastUpdate < '$AgoDays' AND KeepLogged = '1')
			OR (LastUpdate < '$AgoMins' AND KeepLogged = '0')");

	$DB->set_query_id($SessionQuery);
	while (list($UserID, $SessionID) = $DB->next_record()) {
		$Cache->begin_transaction("users_sessions_$UserID");
		$Cache->delete_row($SessionID);
		$Cache->commit_transaction(0);
	}


	//------------- Lower Login Attempts ------------------------------------//
	$DB->query("
		UPDATE login_attempts
		SET Attempts = Attempts - 1
		WHERE Attempts > 0");
	$DB->query("
		DELETE FROM login_attempts
		WHERE LastAttempt < '".time_minus(3600 * 24 * 90)."'");

	//------------- Remove expired warnings ---------------------------------//
	$DB->query("
		SELECT UserID
		FROM users_info
		WHERE Warned < '$sqltime'");
	while (list($UserID) = $DB->next_record()) {
		$Cache->begin_transaction("user_info_$UserID");
		$Cache->update_row(false, array('Warned' => '0000-00-00 00:00:00'));
		$Cache->commit_transaction(2592000);
	}

	$DB->query("
		UPDATE users_info
		SET Warned = '0000-00-00 00:00:00'
		WHERE Warned < '$sqltime'");

	// If a user has downloaded more than 10 GiBs while on ratio watch, disable leeching privileges, and send the user a message

	$DB->query("
		SELECT ID, torrent_pass
		FROM users_info AS i
			JOIN users_main AS m ON m.ID = i.UserID
		WHERE i.RatioWatchEnds != '0000-00-00 00:00:00'
			AND i.RatioWatchDownload + 10 * 1024 * 1024 * 1024 < m.Downloaded
			AND m.Enabled = '1'
			AND m.can_leech = '1'");
	$Users = $DB->to_pair('torrent_pass', 'ID');

	if (count($Users) > 0) {
		$Subject = 'Leeching Disabled';
		$Message = 'You have downloaded more than 10 GB while on Ratio Watch. Your leeching privileges have been disabled. Please reread the rules and refer to this guide on how to improve your ratio ' . site_url() . 'wiki.php?action=article&amp;id=110';
		foreach ($Users as $TorrentPass => $UserID) {
			Misc::send_pm($UserID, 0, $Subject, $Message);
			Tracker::update_tracker('update_user', array('passkey' => $TorrentPass, 'can_leech' => '0'));
		}

		$DB->query("
			UPDATE users_info AS i
				JOIN users_main AS m ON m.ID = i.UserID
			SET m.can_leech = '0',
				i.AdminComment = CONCAT('$sqltime - Leeching privileges disabled by ratio watch system for downloading more than 10 GBs on ratio watch. - required ratio: ', m.RequiredRatio, '\n\n', i.AdminComment)
			WHERE m.ID IN(" . implode(',', $Users) . ')');
	}

}
/*************************************************************************\
//--------------Run every day -------------------------------------------//

These functions are run in the first 15 minutes of every day.

\*************************************************************************/

if (!$NoDaily && $Day != $NextDay || $_GET['runday']) {
	echo "Ran daily functions\n";
	if ($Day % 2 == 0) { // If we should generate the drive database (at the end)
		$GenerateDriveDB = true;
	}

	//------------- Ratio requirements

	// Clear old seed time history
	$DB->query("
		DELETE FROM users_torrent_history
		WHERE Date < DATE('".sqltime()."' - INTERVAL 7 DAY) + 0");

	// Store total seeded time for each user in a temp table
	$DB->query("TRUNCATE TABLE users_torrent_history_temp");
	$DB->query("
		INSERT INTO users_torrent_history_temp
			(UserID, SumTime)
		SELECT UserID, SUM(Time)
		FROM users_torrent_history
		GROUP BY UserID");

	// Insert new row with <NumTorrents> = 0 with <Time> being number of seconds short of 72 hours.
	// This is where we penalize torrents seeded for less than 72 hours
	$DB->query("
		INSERT INTO users_torrent_history
			(UserID, NumTorrents, Date, Time)
		SELECT UserID, 0, UTC_DATE() + 0, 259200 - SumTime
		FROM users_torrent_history_temp
		WHERE SumTime < 259200");

	// Set <Weight> to the time seeding <NumTorrents> torrents
	$DB->query("
		UPDATE users_torrent_history
		SET Weight = NumTorrents * Time");

	// Calculate average time spent seeding each of the currently active torrents.
	// This rounds the results to the nearest integer because SeedingAvg is an int column.
	$DB->query("TRUNCATE TABLE users_torrent_history_temp");
	$DB->query("
		INSERT INTO users_torrent_history_temp
			(UserID, SeedingAvg)
		SELECT UserID, SUM(Weight) / SUM(Time)
		FROM users_torrent_history
		GROUP BY UserID");

	// Remove dummy entry for torrents seeded less than 72 hours
	$DB->query("
		DELETE FROM users_torrent_history
		WHERE NumTorrents = '0'");

	// Get each user's amount of snatches of existing torrents
	$DB->query("TRUNCATE TABLE users_torrent_history_snatch");
	$DB->query("
		INSERT INTO users_torrent_history_snatch (UserID, NumSnatches)
		SELECT xs.uid, COUNT(DISTINCT xs.fid)
		FROM xbt_snatched AS xs
			JOIN torrents AS t ON t.ID = xs.fid
		GROUP BY xs.uid");

	// Get the fraction of snatched torrents seeded for at least 72 hours this week
	// Essentially take the total number of hours seeded this week and divide that by 72 hours * <NumSnatches>
	$DB->query("
		UPDATE users_main AS um
			JOIN users_torrent_history_temp AS t ON t.UserID = um.ID
			JOIN users_torrent_history_snatch AS s ON s.UserID = um.ID
		SET um.RequiredRatioWork = (1 - (t.SeedingAvg / s.NumSnatches))
		WHERE s.NumSnatches > 0");

	$RatioRequirements = array(
		array(80 * 1024 * 1024 * 1024, 0.60, 0.50),
		array(60 * 1024 * 1024 * 1024, 0.60, 0.40),
		array(50 * 1024 * 1024 * 1024, 0.60, 0.30),
		array(40 * 1024 * 1024 * 1024, 0.50, 0.20),
		array(30 * 1024 * 1024 * 1024, 0.40, 0.10),
		array(20 * 1024 * 1024 * 1024, 0.30, 0.05),
		array(10 * 1024 * 1024 * 1024, 0.20, 0.0),
		array(5 * 1024 * 1024 * 1024, 0.15, 0.0)
	);

	$DownloadBarrier = 100 * 1024 * 1024 * 1024;
	$DB->query("
		UPDATE users_main
		SET RequiredRatio = 0.60
		WHERE Downloaded > $DownloadBarrier");


	foreach ($RatioRequirements as $Requirement) {
		list($Download, $Ratio, $MinRatio) = $Requirement;

		$DB->query("
			UPDATE users_main
			SET RequiredRatio = RequiredRatioWork * $Ratio
			WHERE Downloaded >= '$Download'
				AND Downloaded < '$DownloadBarrier'");

		$DB->query("
			UPDATE users_main
			SET RequiredRatio = $MinRatio
			WHERE Downloaded >= '$Download'
				AND Downloaded < '$DownloadBarrier'
				AND RequiredRatio < $MinRatio");

		/*$DB->query("
			UPDATE users_main
			SET RequiredRatio = $Ratio
			WHERE Downloaded >= '$Download'
				AND Downloaded < '$DownloadBarrier'
				AND can_leech = '0'
				AND Enabled = '1'");
		*/
		$DownloadBarrier = $Download;
	}

	$DB->query("
		UPDATE users_main
		SET RequiredRatio = 0.00
		WHERE Downloaded < 5 * 1024 * 1024 * 1024");

	// Here is where we manage ratio watch

	$OffRatioWatch = array();
	$OnRatioWatch = array();

	// Take users off ratio watch and enable leeching
	$UserQuery = $DB->query("
			SELECT
				m.ID,
				torrent_pass
			FROM users_info AS i
				JOIN users_main AS m ON m.ID = i.UserID
			WHERE m.Uploaded/m.Downloaded >= m.RequiredRatio
				AND i.RatioWatchEnds != '0000-00-00 00:00:00'
				AND m.can_leech = '0'
				AND m.Enabled = '1'");
	$OffRatioWatch = $DB->collect('ID');
	if (count($OffRatioWatch) > 0) {
		$DB->query("
			UPDATE users_info AS ui
				JOIN users_main AS um ON um.ID = ui.UserID
			SET ui.RatioWatchEnds = '0000-00-00 00:00:00',
				ui.RatioWatchDownload = '0',
				um.can_leech = '1',
				ui.AdminComment = CONCAT('$sqltime - Leeching re-enabled by adequate ratio.\n\n', ui.AdminComment)
			WHERE ui.UserID IN(".implode(',', $OffRatioWatch).')');
	}

	foreach ($OffRatioWatch as $UserID) {
		$Cache->begin_transaction("user_info_heavy_$UserID");
		$Cache->update_row(false, array('RatioWatchEnds' => '0000-00-00 00:00:00', 'RatioWatchDownload' => '0', 'CanLeech' => 1));
		$Cache->commit_transaction(0);
		Misc::send_pm($UserID, 0, 'You have been taken off Ratio Watch', "Congratulations! Feel free to begin downloading again.\n To ensure that you do not get put on ratio watch again, please read the rules located [url=".site_url()."rules.php?p=ratio]here[/url].\n");
		echo "Ratio watch off: $UserID\n";
	}
	$DB->set_query_id($UserQuery);
	$Passkeys = $DB->collect('torrent_pass');
	foreach ($Passkeys as $Passkey) {
		Tracker::update_tracker('update_user', array('passkey' => $Passkey, 'can_leech' => '1'));
	}

	// Take users off ratio watch
	$UserQuery = $DB->query("
				SELECT m.ID, torrent_pass
				FROM users_info AS i
					JOIN users_main AS m ON m.ID = i.UserID
				WHERE m.Uploaded / m.Downloaded >= m.RequiredRatio
					AND i.RatioWatchEnds != '0000-00-00 00:00:00'
					AND m.Enabled = '1'");
	$OffRatioWatch = $DB->collect('ID');
	if (count($OffRatioWatch) > 0) {
		$DB->query("
			UPDATE users_info AS ui
				JOIN users_main AS um ON um.ID = ui.UserID
			SET ui.RatioWatchEnds = '0000-00-00 00:00:00',
				ui.RatioWatchDownload = '0',
				um.can_leech = '1'
			WHERE ui.UserID IN(".implode(',', $OffRatioWatch).')');
	}

	foreach ($OffRatioWatch as $UserID) {
		$Cache->begin_transaction("user_info_heavy_$UserID");
		$Cache->update_row(false, array('RatioWatchEnds' => '0000-00-00 00:00:00', 'RatioWatchDownload' => '0', 'CanLeech' => 1));
		$Cache->commit_transaction(0);
		Misc::send_pm($UserID, 0, "You have been taken off Ratio Watch", "Congratulations! Feel free to begin downloading again.\n To ensure that you do not get put on ratio watch again, please read the rules located [url=".site_url()."rules.php?p=ratio]here[/url].\n");
		echo "Ratio watch off: $UserID\n";
	}
	$DB->set_query_id($UserQuery);
	$Passkeys = $DB->collect('torrent_pass');
	foreach ($Passkeys as $Passkey) {
		Tracker::update_tracker('update_user', array('passkey' => $Passkey, 'can_leech' => '1'));
	}

	// Put user on ratio watch if he doesn't meet the standards
	sleep(10);
	$DB->query("
		SELECT m.ID, m.Downloaded
		FROM users_info AS i
			JOIN users_main AS m ON m.ID = i.UserID
		WHERE m.Uploaded / m.Downloaded < m.RequiredRatio
			AND i.RatioWatchEnds = '0000-00-00 00:00:00'
			AND m.Enabled = '1'
			AND m.can_leech = '1'");
	$OnRatioWatch = $DB->collect('ID');

	if (count($OnRatioWatch) > 0) {
		$DB->query("
			UPDATE users_info AS i
				JOIN users_main AS m ON m.ID = i.UserID
			SET i.RatioWatchEnds = '".time_plus(60 * 60 * 24 * 14)."',
				i.RatioWatchTimes = i.RatioWatchTimes + 1,
				i.RatioWatchDownload = m.Downloaded
			WHERE m.ID IN(".implode(',', $OnRatioWatch).')');
	}

	foreach ($OnRatioWatch as $UserID) {
		$Cache->begin_transaction("user_info_heavy_$UserID");
		$Cache->update_row(false, array('RatioWatchEnds' => time_plus(60 * 60 * 24 * 14), 'RatioWatchDownload' => 0));
		$Cache->commit_transaction(0);
		Misc::send_pm($UserID, 0, 'You have been put on Ratio Watch', "This happens when your ratio falls below the requirements we have outlined in the rules located [url=".site_url()."rules.php?p=ratio]here[/url].\n For information about ratio watch, click the link above.");
		echo "Ratio watch on: $UserID\n";
	}

	sleep(5);

	//------------- Rescore 0.95 logs of disabled users

	$LogQuery = $DB->query("
			SELECT DISTINCT t.ID
			FROM torrents AS t
				JOIN users_main AS um ON t.UserID = um.ID
				JOIN torrents_logs_new AS tl ON tl.TorrentID = t.ID
			WHERE um.Enabled = '2'
				AND t.HasLog = '1'
				AND LogScore = 100
				AND Log LIKE 'EAC extraction logfile from%'");
	$Details = array();
	$Details[] = "Ripped with EAC v0.95, -1 point [1]";
	$Details = serialize($Details);
	while (list($TorrentID) = $DB->next_record()) {
		$DB->query("
			UPDATE torrents
			SET LogScore = 99
			WHERE ID = $TorrentID");
		$DB->query("
			UPDATE torrents_logs_new
			SET Score = 99, Details = '$Details'
			WHERE TorrentID = $TorrentID");
	}

	sleep(5);

	//------------- Disable downloading ability of users on ratio watch
	$UserQuery = $DB->query("
			SELECT ID, torrent_pass
			FROM users_info AS i
				JOIN users_main AS m ON m.ID = i.UserID
			WHERE i.RatioWatchEnds != '0000-00-00 00:00:00'
				AND i.RatioWatchEnds < '$sqltime'
				AND m.Enabled = '1'
				AND m.can_leech != '0'");

	$UserIDs = $DB->collect('ID');
	if (count($UserIDs) > 0) {
		$DB->query("
			UPDATE users_info AS i
				JOIN users_main AS m ON m.ID = i.UserID
			SET	m.can_leech = '0',
				i.AdminComment = CONCAT('$sqltime - Leeching ability disabled by ratio watch system - required ratio: ', m.RequiredRatio, '\n\n', i.AdminComment)
			WHERE m.ID IN(".implode(',', $UserIDs).')');



		$DB->query("
			DELETE FROM users_torrent_history
			WHERE UserID IN (".implode(',', $UserIDs).')');
	}

	foreach ($UserIDs as $UserID) {
		$Cache->begin_transaction("user_info_heavy_$UserID");
		$Cache->update_row(false, array('RatioWatchDownload' => 0, 'CanLeech' => 0));
		$Cache->commit_transaction(0);
		Misc::send_pm($UserID, 0, 'Your downloading privileges have been disabled', "As you did not raise your ratio in time, your downloading privileges have been revoked. You will not be able to download any torrents until your ratio is above your new required ratio.");
		echo "Ratio watch disabled: $UserID\n";
	}

	$DB->set_query_id($UserQuery);
	$Passkeys = $DB->collect('torrent_pass');
	foreach ($Passkeys as $Passkey) {
		Tracker::update_tracker('update_user', array('passkey' => $Passkey, 'can_leech' => '0'));
	}

	//------------- Disable inactive user accounts --------------------------//
	sleep(5);
	// Send email
	$DB->query("
		SELECT um.Username, um.Email
		FROM users_info AS ui
			JOIN users_main AS um ON um.ID = ui.UserID
			LEFT JOIN users_levels AS ul ON ul.UserID = um.ID AND ul.PermissionID = '".CELEB."'
		WHERE um.PermissionID IN ('".USER."', '".MEMBER	."')
			AND um.LastAccess < '".time_minus(3600 * 24 * 110, true)."'
			AND um.LastAccess > '".time_minus(3600 * 24 * 111, true)."'
			AND um.LastAccess != '0000-00-00 00:00:00'
			AND ui.Donor = '0'
			AND um.Enabled != '2'
			AND ul.UserID IS NULL
		GROUP BY um.ID");
	while (list($Username, $Email) = $DB->next_record()) {
		$Body = "Hi $Username,\n\nIt has been almost 4 months since you used your account at ".site_url().". This is an automated email to inform you that your account will be disabled in 10 days if you do not sign in.";
		Misc::send_email($Email, 'Your '.SITE_NAME.' account is about to be disabled', $Body, 'noreply');
	}

	$DB->query("
		SELECT um.ID
		FROM users_info AS ui
			JOIN users_main AS um ON um.ID = ui.UserID
			LEFT JOIN users_levels AS ul ON ul.UserID = um.ID AND ul.PermissionID = '".CELEB."'
		WHERE um.PermissionID IN ('".USER."', '".MEMBER	."')
			AND um.LastAccess < '".time_minus(3600 * 24 * 30 * 4)."'
			AND um.LastAccess != '0000-00-00 00:00:00'
			AND ui.Donor = '0'
			AND um.Enabled != '2'
			AND ul.UserID IS NULL
		GROUP BY um.ID");
	if ($DB->has_results()) {
		Tools::disable_users($DB->collect('ID'), 'Disabled for inactivity.', 3);
	}

	//------------- Disable unconfirmed users ------------------------------//
	sleep(10);
	// get a list of user IDs for clearing cache keys
	$DB->query("
		SELECT UserID
		FROM users_info AS ui
			JOIN users_main AS um ON um.ID = ui.UserID
		WHERE um.LastAccess = '0000-00-00 00:00:00'
			AND ui.JoinDate < '".time_minus(60 * 60 * 24 * 7)."'
			AND um.Enabled != '2'");
	$UserIDs = $DB->collect('UserID');

	// disable the users
	$DB->query("
		UPDATE users_info AS ui
			JOIN users_main AS um ON um.ID = ui.UserID
		SET um.Enabled = '2',
			ui.BanDate = '$sqltime',
			ui.BanReason = '3',
			ui.AdminComment = CONCAT('$sqltime - Disabled for inactivity (never logged in)\n\n', ui.AdminComment)
		WHERE um.LastAccess = '0000-00-00 00:00:00'
			AND ui.JoinDate < '".time_minus(60 * 60 * 24 * 7)."'
			AND um.Enabled != '2'");
	$Cache->decrement('stats_user_count', $DB->affected_rows());

	// clear the appropriate cache keys
	foreach ($UserIDs as $UserID) {
		$Cache->delete_value("user_info_$UserID");
	}

	echo "disabled unconfirmed\n";

	//------------- Demote users --------------------------------------------//
	sleep(10);
	// Demote to Member when the ratio falls below 0.95 or they have less than 25 GB upload
	$DemoteClasses = [POWER, ELITE, TORRENT_MASTER, POWER_TM, ELITE_TM];
	$Query = $DB->query('
		SELECT ID
		FROM users_main
		WHERE PermissionID IN(' . implode(', ', $DemoteClasses) . ')
			AND (
				Uploaded / Downloaded < 0.95
				OR Uploaded < 25 * 1024 * 1024 * 1024
			)');
	echo "demoted 1\n";

	$DB->query('
		UPDATE users_info AS ui
			JOIN users_main AS um ON um.ID = ui.UserID
		SET
			um.PermissionID = ' . MEMBER . ",
			ui.AdminComment = CONCAT('" . sqltime() . ' - Class changed to ' . Users::make_class_string(MEMBER) . " by System\n\n', ui.AdminComment)
		WHERE um.PermissionID IN (" . implode(', ', $DemoteClasses) . ')
			AND (
				um.Uploaded / um.Downloaded < 0.95
				OR um.Uploaded < 25 * 1024 * 1024 * 1024
			)');
	$DB->set_query_id($Query);
	while (list($UserID) = $DB->next_record()) {
		/*$Cache->begin_transaction("user_info_$UserID");
		$Cache->update_row(false, array('PermissionID' => MEMBER));
		$Cache->commit_transaction(2592000);*/
		$Cache->delete_value("user_info_$UserID");
		$Cache->delete_value("user_info_heavy_$UserID");
		Misc::send_pm($UserID, 0, 'You have been demoted to '.Users::make_class_string(MEMBER), "You now only meet the requirements for the \"".Users::make_class_string(MEMBER)."\" user class.\n\nTo read more about ".SITE_NAME."'s user classes, read [url=".site_url()."wiki.php?action=article&amp;name=userclasses]this wiki article[/url].");
	}
	echo "demoted 2\n";

	// Demote to User when the ratio drops below 0.65
	$DemoteClasses = [MEMBER, POWER, ELITE, TORRENT_MASTER, POWER_TM, ELITE_TM];
	$Query = $DB->query('
		SELECT ID
		FROM users_main
		WHERE PermissionID IN(' . implode(', ', $DemoteClasses) . ')
			AND Uploaded / Downloaded < 0.65');
	echo "demoted 3\n";
	$DB->query('
		UPDATE users_info AS ui
			JOIN users_main AS um ON um.ID = ui.UserID
		SET
			um.PermissionID = ' . USER . ",
			ui.AdminComment = CONCAT('" . sqltime() . ' - Class changed to ' . Users::make_class_string(USER) . " by System\n\n', ui.AdminComment)
		WHERE um.PermissionID IN (" . implode(', ', $DemoteClasses) . ')
			AND um.Uploaded / um.Downloaded < 0.65');
	$DB->set_query_id($Query);
	while (list($UserID) = $DB->next_record()) {
		/*$Cache->begin_transaction("user_info_$UserID");
		$Cache->update_row(false, array('PermissionID' => USER));
		$Cache->commit_transaction(2592000);*/
		$Cache->delete_value("user_info_$UserID");
		$Cache->delete_value("user_info_heavy_$UserID");
		Misc::send_pm($UserID, 0, 'You have been demoted to '.Users::make_class_string(USER), "You now only meet the requirements for the \"".Users::make_class_string(USER)."\" user class.\n\nTo read more about ".SITE_NAME."'s user classes, read [url=".site_url()."wiki.php?action=article&amp;name=userclasses]this wiki article[/url].");
	}
	echo "demoted 4\n";

	//------------- Lock old threads ----------------------------------------//
	sleep(10);
	$DB->query("
		SELECT t.ID, t.ForumID
		FROM forums_topics AS t
			JOIN forums AS f ON t.ForumID = f.ID
		WHERE t.IsLocked = '0'
			AND t.IsSticky = '0'
			AND DATEDIFF(CURDATE(), DATE(t.LastPostTime)) / 7 > f.AutoLockWeeks
			AND f.AutoLock = '1'");
	$IDs = $DB->collect('ID');
	$ForumIDs = $DB->collect('ForumID');

	if (count($IDs) > 0) {
		$LockIDs = implode(',', $IDs);
		$DB->query("
			UPDATE forums_topics
			SET IsLocked = '1'
			WHERE ID IN($LockIDs)");
		sleep(2);
		$DB->query("
			DELETE FROM forums_last_read_topics
			WHERE TopicID IN($LockIDs)");

		foreach ($IDs as $ID) {
			$Cache->begin_transaction("thread_$ID".'_info');
			$Cache->update_row(false, array('IsLocked' => '1'));
			$Cache->commit_transaction(3600 * 24 * 30);
			$Cache->expire_value("thread_$ID".'_catalogue_0', 3600 * 24 * 30);
			$Cache->expire_value("thread_$ID".'_info', 3600 * 24 * 30);
			Forums::add_topic_note($ID, 'Locked automatically by schedule', 0);
		}

		$ForumIDs = array_flip(array_flip($ForumIDs));
		foreach ($ForumIDs as $ForumID) {
			$Cache->delete_value("forums_$ForumID");
		}
	}
	echo "Old threads locked\n";

	//------------- Delete dead torrents ------------------------------------//

	sleep(10);

	$DB->query("
		SELECT
			t.ID,
			t.GroupID,
			tg.Name,
			t.Format,
			t.Encoding,
			t.UserID,
			t.Media,
			HEX(t.info_hash) AS InfoHash
		FROM torrents AS t
			JOIN torrents_group AS tg ON tg.ID = t.GroupID
		WHERE
			(t.last_action < '".time_minus(3600 * 24 * 28)."' AND t.last_action != 0)
			OR
			(t.Time < '".time_minus(3600 * 24 * 2)."' AND t.last_action = 0)");
	$Torrents = $DB->to_array(false, MYSQLI_NUM, false);
	echo 'Found '.count($Torrents)." inactive torrents to be deleted.\n";

	$LogEntries = $DeleteNotes = array();

	// Exceptions for inactivity deletion
	$InactivityExceptionsMade = array(//UserID => expiry time of exception
	);
	$i = 0;
	foreach ($Torrents as $Torrent) {
		list($ID, $GroupID, $Name, $Format, $Encoding, $UserID, $Media, $InfoHash) = $Torrent;
		if (array_key_exists($UserID, $InactivityExceptionsMade) && (time() < $InactivityExceptionsMade[$UserID])) {
			// don't delete the torrent!
			continue;
		}
		$ArtistName = Artists::display_artists(Artists::get_artist($GroupID), false, false, false);
		if ($ArtistName) {
			$Name = "$ArtistName - $Name";
		}
		if ($Format && $Encoding) {
			$Name .= ' ['.(empty($Media) ? '' : "$Media / ") . "$Format / $Encoding]";
		}
		Torrents::delete_torrent($ID, $GroupID);
		$LogEntries[] = db_string("Torrent $ID ($Name) (".strtoupper($InfoHash).") was deleted for inactivity (unseeded)");

		if (!array_key_exists($UserID, $DeleteNotes)) {
			$DeleteNotes[$UserID] = array('Count' => 0, 'Msg' => '');
		}

		$DeleteNotes[$UserID]['Msg'] .= "\n$Name";
		$DeleteNotes[$UserID]['Count']++;

		++$i;
		if ($i % 500 == 0) {
			echo "$i inactive torrents removed.\n";
		}
	}
	echo "$i torrents deleted for inactivity.\n";

	foreach ($DeleteNotes as $UserID => $MessageInfo) {
		$Singular = (($MessageInfo['Count'] == 1) ? true : false);
		Misc::send_pm($UserID, 0, $MessageInfo['Count'].' of your torrents '.($Singular ? 'has' : 'have').' been deleted for inactivity', ($Singular ? 'One' : 'Some').' of your uploads '.($Singular ? 'has' : 'have').' been deleted for being unseeded. Since '.($Singular ? 'it' : 'they').' didn\'t break any rules (we hope), please feel free to re-upload '.($Singular ? 'it' : 'them').".\n\nThe following torrent".($Singular ? ' was' : 's were').' deleted:'.$MessageInfo['Msg']);
	}
	unset($DeleteNotes);

	if (count($LogEntries) > 0) {
		$Values = "('".implode("', '$sqltime'), ('", $LogEntries) . "', '$sqltime')";
		$DB->query("
			INSERT INTO log (Message, Time)
			VALUES $Values");
		echo "\nDeleted $i torrents for inactivity\n";
	}

	$DB->query("
		SELECT SimilarID
		FROM artists_similar_scores
		WHERE Score <= 0");
	$SimilarIDs = implode(',', $DB->collect('SimilarID'));

	if ($SimilarIDs) {
		$DB->query("
			DELETE FROM artists_similar
			WHERE SimilarID IN($SimilarIDs)");
		$DB->query("
			DELETE FROM artists_similar_scores
			WHERE SimilarID IN($SimilarIDs)");
		$DB->query("
			DELETE FROM artists_similar_votes
			WHERE SimilarID IN($SimilarIDs)");
	}


	// Daily top 10 history.
	$DB->query("
		INSERT INTO top10_history (Date, Type)
		VALUES ('$sqltime', 'Daily')");
	$HistoryID = $DB->inserted_id();

	$Top10 = $Cache->get_value('top10tor_day_10');
	if ($Top10 === false) {
		$DB->query("
			SELECT
				t.ID,
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
				t.RemasterTitle,
				t.Snatched,
				t.Seeders,
				t.Leechers,
				((t.Size * t.Snatched) + (t.Size * 0.5 * t.Leechers)) AS Data
			FROM torrents AS t
				LEFT JOIN torrents_group AS g ON g.ID = t.GroupID
			WHERE t.Seeders > 0
				AND t.Time > ('$sqltime' - INTERVAL 1 DAY)
			ORDER BY (t.Seeders + t.Leechers) DESC
			LIMIT 10;");

		$Top10 = $DB->to_array();
	}

	$i = 1;
	foreach ($Top10 as $Torrent) {
		list($TorrentID, $GroupID, $GroupName, $GroupCategoryID, $TorrentTags,
			$Format, $Encoding, $Media, $Scene, $HasLog, $HasCue, $LogScore, $Year, $GroupYear,
			$RemasterTitle, $Snatched, $Seeders, $Leechers, $Data) = $Torrent;

		$DisplayName = '';

		$Artists = Artists::get_artist($GroupID);

		if (!empty($Artists)) {
			$DisplayName = Artists::display_artists($Artists, false, true);
		}

		$DisplayName .= $GroupName;

		if ($GroupCategoryID == 1 && $GroupYear > 0) {
			$DisplayName .= " [$GroupYear]";
		}

		// append extra info to torrent title
		$ExtraInfo = '';
		$AddExtra = '';
		if ($Format) {
			$ExtraInfo .= $Format;
			$AddExtra = ' / ';
		}
		if ($Encoding) {
			$ExtraInfo .= $AddExtra.$Encoding;
			$AddExtra = ' / ';
		}
		// "FLAC / Lossless / Log (100%) / Cue / CD";
		if ($HasLog) {
			$ExtraInfo .= "{$AddExtra}Log ($LogScore%)";
			$AddExtra = ' / ';
		}
		if ($HasCue) {
			$ExtraInfo .= "{$AddExtra}Cue";
			$AddExtra = ' / ';
		}
		if ($Media) {
			$ExtraInfo .= $AddExtra.$Media;
			$AddExtra = ' / ';
		}
		if ($Scene) {
			$ExtraInfo .= "{$AddExtra}Scene";
			$AddExtra = ' / ';
		}
		if ($Year > 0) {
			$ExtraInfo .= $AddExtra.$Year;
			$AddExtra = ' ';
		}
		if ($RemasterTitle) {
			$ExtraInfo .= $AddExtra.$RemasterTitle;
		}
		if ($ExtraInfo != '') {
			$ExtraInfo = "- [$ExtraInfo]";
		}

		$TitleString = "$DisplayName $ExtraInfo";

		$TagString = str_replace('|', ' ', $TorrentTags);

		$DB->query("
			INSERT INTO top10_history_torrents
				(HistoryID, Rank, TorrentID, TitleString, TagString)
			VALUES
				($HistoryID, $i, $TorrentID, '".db_string($TitleString)."', '".db_string($TagString)."')");
		$i++;
	}

	// Weekly top 10 history.
	// We need to haxxor it to work on a Sunday as we don't have a weekly schedule
	if (date('w') == 0) {
		$DB->query("
			INSERT INTO top10_history (Date, Type)
			VALUES ('$sqltime', 'Weekly')");
		$HistoryID = $DB->inserted_id();

		$Top10 = $Cache->get_value('top10tor_week_10');
		if ($Top10 === false) {
			$DB->query("
				SELECT
					t.ID,
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
					t.RemasterTitle,
					t.Snatched,
					t.Seeders,
					t.Leechers,
					((t.Size * t.Snatched) + (t.Size * 0.5 * t.Leechers)) AS Data
				FROM torrents AS t
					LEFT JOIN torrents_group AS g ON g.ID = t.GroupID
				WHERE t.Seeders > 0
					AND t.Time > ('$sqltime' - INTERVAL 1 WEEK)
				ORDER BY (t.Seeders + t.Leechers) DESC
				LIMIT 10;");

			$Top10 = $DB->to_array();
		}

		$i = 1;
		foreach ($Top10 as $Torrent) {
			list($TorrentID, $GroupID, $GroupName, $GroupCategoryID, $TorrentTags,
				$Format, $Encoding, $Media, $Scene, $HasLog, $HasCue, $LogScore, $Year, $GroupYear,
				$RemasterTitle, $Snatched, $Seeders, $Leechers, $Data) = $Torrent;

			$DisplayName = '';

			$Artists = Artists::get_artist($GroupID);

			if (!empty($Artists)) {
				$DisplayName = Artists::display_artists($Artists, false, true);
			}

			$DisplayName .= $GroupName;

			if ($GroupCategoryID == 1 && $GroupYear > 0) {
				$DisplayName .= " [$GroupYear]";
			}

			// append extra info to torrent title
			$ExtraInfo = '';
			$AddExtra = '';
			if ($Format) {
				$ExtraInfo .= $Format;
				$AddExtra = ' / ';
			}
			if ($Encoding) {
				$ExtraInfo .= $AddExtra.$Encoding;
				$AddExtra = ' / ';
			}
			// "FLAC / Lossless / Log (100%) / Cue / CD";
			if ($HasLog) {
				$ExtraInfo .= "{$AddExtra}Log ($LogScore%)";
				$AddExtra = ' / ';
			}
			if ($HasCue) {
				$ExtraInfo .= "{$AddExtra}Cue";
				$AddExtra = ' / ';
			}
			if ($Media) {
				$ExtraInfo .= $AddExtra.$Media;
				$AddExtra = ' / ';
			}
			if ($Scene) {
				$ExtraInfo .= "{$AddExtra}Scene";
				$AddExtra = ' / ';
			}
			if ($Year > 0) {
				$ExtraInfo .= $AddExtra.$Year;
				$AddExtra = ' ';
			}
			if ($RemasterTitle) {
				$ExtraInfo .= $AddExtra.$RemasterTitle;
			}
			if ($ExtraInfo != '') {
				$ExtraInfo = "- [$ExtraInfo]";
			}

			$TitleString = "$DisplayName $ExtraInfo";

			$TagString = str_replace('|', ' ', $TorrentTags);

			$DB->query("
				INSERT INTO top10_history_torrents
					(HistoryID, Rank, TorrentID, TitleString, TagString)
				VALUES
					($HistoryID, $i, $TorrentID, '" . db_string($TitleString) . "', '" . db_string($TagString) . "')");
			$i++;
		} //foreach ($Top10 as $Torrent)

		// Send warnings to uploaders of torrents that will be deleted this week
		$DB->query("
			SELECT
				t.ID,
				t.GroupID,
				tg.Name,
				t.Format,
				t.Encoding,
				t.UserID
			FROM torrents AS t
				JOIN torrents_group AS tg ON tg.ID = t.GroupID
				JOIN users_info AS u ON u.UserID = t.UserID
			WHERE t.last_action < NOW() - INTERVAL 20 DAY
				AND t.last_action != 0
				AND u.UnseededAlerts = '1'
			ORDER BY t.last_action ASC");
		$TorrentIDs = $DB->to_array();
		$TorrentAlerts = array();
		foreach ($TorrentIDs as $TorrentID) {
			list($ID, $GroupID, $Name, $Format, $Encoding, $UserID) = $TorrentID;

			if (array_key_exists($UserID, $InactivityExceptionsMade) && (time() < $InactivityExceptionsMade[$UserID])) {
				// don't notify exceptions
				continue;
			}

			if (!array_key_exists($UserID, $TorrentAlerts))
				$TorrentAlerts[$UserID] = array('Count' => 0, 'Msg' => '');
			$ArtistName = Artists::display_artists(Artists::get_artist($GroupID), false, false, false);
			if ($ArtistName) {
				$Name = "$ArtistName - $Name";
			}
			if ($Format && $Encoding) {
				$Name .= " [$Format / $Encoding]";
			}
			$TorrentAlerts[$UserID]['Msg'] .= "\n[url=".site_url()."torrents.php?torrentid=$ID]".$Name."[/url]";
			$TorrentAlerts[$UserID]['Count']++;
		}
		foreach ($TorrentAlerts as $UserID => $MessageInfo) {
			Misc::send_pm($UserID, 0, 'Unseeded torrent notification', $MessageInfo['Count']." of your uploads will be deleted for inactivity soon. Unseeded torrents are deleted after 4 weeks. If you still have the files, you can seed your uploads by ensuring the torrents are in your client and that they aren't stopped. You can view the time that a torrent has been unseeded by clicking on the torrent description line and looking for the \"Last active\" time. For more information, please go [url=".site_url()."wiki.php?action=article&amp;id=663]here[/url].\n\nThe following torrent".($MessageInfo['Count'] > 1 ? 's' : '').' will be removed for inactivity:'.$MessageInfo['Msg']."\n\nIf you no longer wish to receive these notifications, please disable them in your profile settings.");
		}
	}

	$DB->query("
		UPDATE staff_pm_conversations
		SET Status = 'Resolved', ResolverID = '0'
		WHERE Date < NOW() - INTERVAL 1 MONTH
			AND Status = 'Open'
			AND AssignedToUser IS NULL");

	Donations::schedule();

}
/*************************************************************************\
//--------------Run twice per month -------------------------------------//

These functions are twice per month, on the 8th and the 22nd.

\*************************************************************************/

if ($BiWeek != $NextBiWeek || $_GET['runbiweek']) {
	echo "Ran bi-weekly functions\n";

	//------------- Cycle auth keys -----------------------------------------//

	$DB->query("
		UPDATE users_info
		SET AuthKey =
			MD5(
				CONCAT(
					AuthKey, RAND(), '".db_string(Users::make_secret())."',
					SHA1(
						CONCAT(
							RAND(), RAND(), '".db_string(Users::make_secret())."'
						)
					)
				)
			);"
	);

	//------------- Give out invites! ---------------------------------------//

	/*
	Power Users have a cap of 2 invites. Elites have a cap of 4.
	Every month, on the 8th and the 22nd, each PU/Elite user gets one invite up to their max.

	Then, every month, on the 8th and the 22nd, we give out bonus invites like this:

	Every Power User or Elite whose total invitee ratio is above 0.75 and total invitee upload is over 2 GBs gets one invite.
	Every Elite whose total invitee ratio is above 2.0 and total invitee upload is over 10 GBs gets one more invite.
	Every Elite whose total invitee ratio is above 3.0 and total invitee upload is over 20 GBs gets yet one more invite.

	This cascades, so if you qualify for the last bonus group, you also qualify for the first two and will receive three bonus invites.

	The bonus invites cannot put a user over their cap.

	*/

	$DB->query("
		SELECT ID
		FROM users_main AS um
			JOIN users_info AS ui ON ui.UserID = um.ID
		WHERE um.Enabled = '1'
			AND ui.DisableInvites = '0'
			AND ((um.PermissionID = ".POWER."
					AND um.Invites < 2
				 ) OR (um.PermissionID = ".ELITE."
					AND um.Invites < 4)
				)");
	$UserIDs = $DB->collect('ID');
	if (count($UserIDs) > 0) {
		foreach ($UserIDs as $UserID) {
				$Cache->begin_transaction("user_info_heavy_$UserID");
				$Cache->update_row(false, array('Invites' => '+1'));
				$Cache->commit_transaction(0);
		}
		$DB->query('
			UPDATE users_main
			SET Invites = Invites + 1
			WHERE ID IN ('.implode(',', $UserIDs).')');
	}

	$BonusReqs = array(
		array(0.75, 2 * 1024 * 1024 * 1024),
		array(2.0, 10 * 1024 * 1024 * 1024),
		array(3.0, 20 * 1024 * 1024 * 1024));

	// Since MySQL doesn't like subselecting from the target table during an update, we must create a temporary table.

	$DB->query("
		CREATE TEMPORARY TABLE temp_sections_schedule_index
		SELECT SUM(Uploaded) AS Upload, SUM(Downloaded) AS Download, Inviter
		FROM users_main AS um
			JOIN users_info AS ui ON ui.UserID = um.ID
		GROUP BY Inviter");

	foreach ($BonusReqs as $BonusReq) {
		list($Ratio, $Upload) = $BonusReq;
		$DB->query("
			SELECT ID
			FROM users_main AS um
				JOIN users_info AS ui ON ui.UserID = um.ID
				JOIN temp_sections_schedule_index AS u ON u.Inviter = um.ID
			WHERE u.Upload > $Upload
				AND u.Upload / u.Download > $Ratio
				AND um.Enabled = '1'
				AND ui.DisableInvites = '0'
				AND ((um.PermissionID = ".POWER.'
						AND um.Invites < 2
					 ) OR (um.PermissionID = '.ELITE.'
						AND um.Invites < 4)
					)');
		$UserIDs = $DB->collect('ID');
		if (count($UserIDs) > 0) {
			foreach ($UserIDs as $UserID) {
					$Cache->begin_transaction("user_info_heavy_$UserID");
					$Cache->update_row(false, array('Invites' => '+1'));
					$Cache->commit_transaction(0);
			}
			$DB->query('
				UPDATE users_main
				SET Invites = Invites + 1
				WHERE ID IN ('.implode(',', $UserIDs).')');
		}
	}

	if ($BiWeek == 8) {
		$DB->query('TRUNCATE TABLE top_snatchers;');
		$DB->query("
			INSERT INTO top_snatchers (UserID)
			SELECT uid
			FROM xbt_snatched
			GROUP BY uid
			ORDER BY COUNT(uid) DESC
			LIMIT 100;");

	}
}


echo "-------------------------\n\n";
if (check_perms('admin_schedule')) {
	echo '<pre>';
	View::show_footer();
}
?>
