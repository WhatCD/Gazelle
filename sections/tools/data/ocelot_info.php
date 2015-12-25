<?
if (!check_perms('users_mod')) {
	error(403);
}

if (isset($_GET['userid']) && is_number($_GET['userid'])) {
	$UserHeavyInfo = Users::user_heavy_info($_GET['userid']);
	if (isset($UserHeavyInfo['torrent_pass'])) {
		$TorrentPass = $UserHeavyInfo['torrent_pass'];
		$UserPeerStats = Tracker::user_peer_count($TorrentPass);
		$UserInfo = Users::user_info($_GET['userid']);
		$UserLevel = $Classes[$UserInfo['PermissionID']]['Level'];
		if (!check_paranoia('leeching+', $UserInfo['Paranoia'], $UserLevel, $_GET['userid'])) {
			$UserPeerStats[0] = false;
		}
		if (!check_paranoia('seeding+', $UserInfo['Paranoia'], $UserLevel, $_GET['userid'])) {
			$UserPeerStats[1] = false;
		}
	} else {
		$UserPeerStats = false;
	}
} else {
	$MainStats = Tracker::info();
}

View::show_header('Tracker info');
?>
<div class="thin">
	<div class="header">
		<h2>Tracker info</h2>
	</div>
	<div class="linkbox">
		<a href="?action=<?=$_REQUEST['action']?>" class="brackets" />Main stats</a>
	</div>
	<div class="sidebar">
		<div class="box box2">
			<div class="head"><strong>User stats</strong></div>
			<div class="pad">
				<form method="get" action="">
					<input type="hidden" name="action" value="ocelot_info" />
					<span class="label">Get stats for user</span><br />
					<input type="text" name="userid" placeholder="User ID" value="<?Format::form('userid')?>" />
					<input type="submit" value="Go" />
				</form>
			</div>
		</div>
	</div>
	<div class="main_column">
		<div class="box box2">
			<div class="head"><strong>Numbers and such</strong></div>
			<div class="pad">
<?
if (!empty($UserPeerStats)) {
?>
				User ID: <?=$_GET['userid']?><br />
				Leeching: <?=$UserPeerStats[0] === false ? "hidden" : number_format($UserPeerStats[0])?><br />
				Seeding: <?=$UserPeerStats[1] === false ? "hidden" : number_format($UserPeerStats[1])?><br />
<?
} elseif (!empty($MainStats)) {
	foreach ($MainStats as $Key => $Value) {
		if (is_numeric($Value)) {
			if (substr($Key, 0, 6) === "bytes ") {
				$Value = Format::get_size($Value);
				$Key = substr($Key, 6);
			} else {
				$Value = number_format($Value);
			}
		}
?>
				<?="$Value $Key<br />\n"?>
<?
	}
} elseif (isset($TorrentPass)) {
?>
				Failed to get stats for user <?=$_GET['userid']?>
<?
} elseif (isset($_GET['userid'])) {
?>
				User <?=display_str($_GET['userid'])?> doesn't exist
<?
} else {
?>
				Failed to get tracker info
<?
}
?>
			</div>
		</div>
	</div>
</div>
<?
View::show_footer();
