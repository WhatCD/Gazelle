<?
$DB->query("
	SELECT Page, COUNT(1)
	FROM comments
	WHERE AuthorID = $UserID
	GROUP BY Page");
$Comments = $DB->to_array('Page');
$NumComments = $Comments['torrents'][1];
$NumArtistComments = $Comments['artist'][1];
$NumCollageComments = $Comments['collages'][1];
$NumRequestComments = $Comments['requests'][1];

$DB->query("
	SELECT COUNT(ID)
	FROM collages
	WHERE Deleted = '0'
		AND UserID = '$UserID'");
list($NumCollages) = $DB->next_record();

$DB->query("
	SELECT COUNT(DISTINCT CollageID)
	FROM collages_torrents AS ct
		JOIN collages ON CollageID = ID
	WHERE Deleted = '0'
		AND ct.UserID = '$UserID'");
list($NumCollageContribs) = $DB->next_record();

$DB->query("
	SELECT COUNT(DISTINCT GroupID)
	FROM torrents
	WHERE UserID = '$UserID'");
list($UniqueGroups) = $DB->next_record();

$DB->query("
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
			OR (Media = 'DAT' AND Format = 'FLAC'))
		AND UserID = '$UserID'");
list($PerfectFLACs) = $DB->next_record();
?>
		<div class="box box_info box_userinfo_community">
			<div class="head colhead_dark">Community</div>
			<ul class="stats nobullet">
				<li id="comm_posts">Forum posts: <?=number_format($ForumPosts)?> <a href="userhistory.php?action=posts&amp;userid=<?=$UserID?>" class="brackets">View</a></li>
<?	if ($Override = check_paranoia_here('torrentcomments+')) { ?>
				<li id="comm_torrcomm"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Torrent comments: <?=number_format($NumComments)?>
<?				if ($Override = check_paranoia_here('torrentcomments')) { ?>
					<a href="comments.php?id=<?=$UserID?>" class="brackets<?=($Override === 2 ? ' paranoia_override' : '')?>">View</a>
<?				} ?>
				</li>
				<li id="comm_artcomm"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Artist comments: <?=number_format($NumArtistComments)?>
<?				if ($Override = check_paranoia_here('torrentcomments')) { ?>
					<a href="comments.php?id=<?=$UserID?>&amp;action=artist" class="brackets<?=($Override === 2 ? ' paranoia_override' : '')?>">View</a>
<?				} ?>
				</li>
				<li id="comm_collcomm"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Collage comments: <?=number_format($NumCollageComments)?>
<?				if ($Override = check_paranoia_here('torrentcomments')) { ?>
					<a href="comments.php?id=<?=$UserID?>&amp;action=collages" class="brackets<?=($Override === 2 ? ' paranoia_override' : '')?>">View</a>
<?				} ?>
				</li>
				<li id="comm_reqcomm"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Request comments: <?=number_format($NumRequestComments)?>
<?				if ($Override = check_paranoia_here('torrentcomments')) { ?>
					<a href="comments.php?id=<?=$UserID?>&amp;action=requests" class="brackets<?=($Override === 2 ? ' paranoia_override' : '')?>">View</a>
<?				} ?>
				</li>
<?
	}
	if (($Override = check_paranoia_here('collages+'))) { ?>
				<li id="comm_collstart"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Collages started: <?=number_format($NumCollages)?>
<?				if ($Override = check_paranoia_here('collages')) { ?>
					<a href="collages.php?userid=<?=$UserID?>" class="brackets<?=(($Override === 2) ? ' paranoia_override' : '')?>">View</a>
<?				} ?>
				</li>
<?
	}
	if (($Override = check_paranoia_here('collagecontribs+'))) { ?>
				<li id="comm_collcontrib"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Collages contributed to: <? echo number_format($NumCollageContribs); ?>
<?				if ($Override = check_paranoia_here('collagecontribs')) { ?>
					<a href="collages.php?userid=<?=$UserID?>&amp;contrib=1" class="brackets<?=(($Override === 2) ? ' paranoia_override' : '')?>">View</a>
<?				} ?>
				</li>
<?
	}

	//Let's see if we can view requests because of reasons
	$ViewAll    = check_paranoia_here('requestsfilled_list');
	$ViewCount  = check_paranoia_here('requestsfilled_count');
	$ViewBounty = check_paranoia_here('requestsfilled_bounty');

	if ($ViewCount && !$ViewBounty && !$ViewAll) { ?>
				<li>Requests filled: <?=number_format($RequestsFilled)?></li>
<?	} elseif (!$ViewCount && $ViewBounty && !$ViewAll) { ?>
				<li>Requests filled: <?=Format::get_size($TotalBounty)?> collected</li>
<?	} elseif ($ViewCount && $ViewBounty && !$ViewAll) { ?>
				<li>Requests filled: <?=number_format($RequestsFilled)?> for <?=Format::get_size($TotalBounty)?></li>
<?	} elseif ($ViewAll) { ?>
				<li>
					<span<?=($ViewCount === 2 ? ' class="paranoia_override"' : '')?>>Requests filled: <?=number_format($RequestsFilled)?></span>
					<span<?=($ViewBounty === 2 ? ' class="paranoia_override"' : '')?>> for <?=Format::get_size($TotalBounty) ?></span>
					<a href="requests.php?type=filled&amp;userid=<?=$UserID?>" class="brackets<?=(($ViewAll === 2) ? ' paranoia_override' : '')?>">View</a>
				</li>
<?
	}

	//Let's see if we can view requests because of reasons
	$ViewAll    = check_paranoia_here('requestsvoted_list');
	$ViewCount  = check_paranoia_here('requestsvoted_count');
	$ViewBounty = check_paranoia_here('requestsvoted_bounty');

	if ($ViewCount && !$ViewBounty && !$ViewAll) { ?>
				<li>Requests created: <?=number_format($RequestsCreated)?></li>
				<li>Requests voted: <?=number_format($RequestsVoted)?></li>
<?	} elseif (!$ViewCount && $ViewBounty && !$ViewAll) { ?>
				<li>Requests created: <?=Format::get_size($RequestsCreatedSpent)?> spent</li>
				<li>Requests voted: <?=Format::get_size($TotalSpent)?> spent</li>
<?	} elseif ($ViewCount && $ViewBounty && !$ViewAll) { ?>
				<li>Requests created: <?=number_format($RequestsCreated)?> for <?=Format::get_size($RequestsCreatedSpent)?></li>
				<li>Requests voted: <?=number_format($RequestsVoted)?> for <?=Format::get_size($TotalSpent)?></li>
<?	} elseif ($ViewAll) { ?>
				<li>
					<span<?=($ViewCount === 2 ? ' class="paranoia_override"' : '')?>>Requests created: <?=number_format($RequestsCreated)?></span>
					<span<?=($ViewBounty === 2 ? ' class="paranoia_override"' : '')?>> for <?=Format::get_size($RequestsCreatedSpent)?></span>
					<a href="requests.php?type=created&amp;userid=<?=$UserID?>" class="brackets<?=($ViewAll === 2 ? ' paranoia_override' : '')?>">View</a>
				</li>
				<li>
					<span<?=($ViewCount === 2 ? ' class="paranoia_override"' : '')?>>Requests voted: <?=number_format($RequestsVoted)?></span>
					<span<?=($ViewBounty === 2 ? ' class="paranoia_override"' : '')?>> for <?=Format::get_size($TotalSpent)?></span>
					<a href="requests.php?type=voted&amp;userid=<?=$UserID?>" class="brackets<?=($ViewAll === 2 ? ' paranoia_override' : '')?>">View</a>
				</li>
<?
	}
	if ($Override = check_paranoia_here('uploads+')) { ?>
				<li id="comm_upload"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Uploaded: <?=number_format($Uploads)?>
<?					if ($Override = check_paranoia_here('uploads')) { ?>
					<a href="torrents.php?type=uploaded&amp;userid=<?=$UserID?>" class="brackets<?=($Override === 2 ? ' paranoia_override' : '')?>">View</a>
<?						if (check_perms('zip_downloader')) { ?>
					<a href="torrents.php?action=redownload&amp;type=uploads&amp;userid=<?=$UserID?>" onclick="return confirm('If you no longer have the content, your ratio WILL be affected; be sure to check the size of all torrents before redownloading.');" class="brackets<?=($Override === 2 ? ' paranoia_override' : '')?>">Download</a>
<?
						}
					}
?>
				</li>
<?
	}
	if ($Override = check_paranoia_here('uniquegroups+')) { ?>
				<li id="comm_uniquegroup"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Unique groups: <?=number_format($UniqueGroups)?>
<?					if ($Override = check_paranoia_here('uniquegroups')) { ?>
					<a href="torrents.php?type=uploaded&amp;userid=<?=$UserID?>&amp;filter=uniquegroup" class="brackets<?=($Override === 2 ? ' paranoia_override' : '')?>">View</a>
<?					} ?>
				</li>
<?
	}
	if ($Override = check_paranoia_here('perfectflacs+')) { ?>
				<li id="comm_perfectflac"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>"Perfect" FLACs: <?=number_format($PerfectFLACs)?>
<?					if ($Override = check_paranoia_here('perfectflacs')) { ?>
					<a href="torrents.php?type=uploaded&amp;userid=<?=$UserID?>&amp;filter=perfectflac" class="brackets<?=($Override === 2 ? ' paranoia_override' : '')?>">View</a>
<?					} ?>
				</li>
<?
	}
	if ($Override = check_paranoia_here('seeding+')) {
?>
				<li id="comm_seeding"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Seeding:
					<span class="user_commstats" id="user_commstats_seeding"><a href="#" class="brackets" onclick="commStats(<?=$UserID?>); return false;">Show stats</a></span>
<?		if ($Override = check_paranoia_here('snatched+')) { ?>
					<span<?=($Override === 2 ? ' class="paranoia_override"' : '')?> id="user_commstats_seedingperc"></span>
<?
		}
		if ($Override = check_paranoia_here('seeding')) {
?>
					<a href="torrents.php?type=seeding&amp;userid=<?=$UserID?>" class="brackets<?=($Override === 2 ? ' paranoia_override' : '')?>">View</a>
<?			if (check_perms('zip_downloader')) { ?>
					<a href="torrents.php?action=redownload&amp;type=seeding&amp;userid=<?=$UserID?>" onclick="return confirm('If you no longer have the content, your ratio WILL be affected; be sure to check the size of all torrents before redownloading.');" class="brackets">Download</a>
<?
			}
		}
?>
				</li>
<?
	}
	if ($Override = check_paranoia_here('leeching+')) {
?>
				<li id="comm_leeching"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Leeching:
					<span class="user_commstats" id="user_commstats_leeching"><a href="#" class="brackets" onclick="commStats(<?=$UserID?>); return false;">Show stats</a></span>
<?		if ($Override = check_paranoia_here('leeching')) { ?>
					<a href="torrents.php?type=leeching&amp;userid=<?=$UserID?>" class="brackets<?=($Override === 2 ? ' paranoia_override' : '')?>">View</a>
<?
		}
		if ($DisableLeech == 0 && check_perms('users_view_ips')) {
?>
					<strong>(Disabled)</strong>
<?		} ?>
				</li>
<?
	}
	if ($Override = check_paranoia_here('snatched+')) { ?>
				<li id="comm_snatched"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Snatched:
					<span class="user_commstats" id="user_commstats_snatched"><a href="#" class="brackets" onclick="commStats(<?=$UserID?>); return false;">Show stats</a></span>
<?		if ($Override = check_perms('site_view_torrent_snatchlist', $Class)) { ?>
					<span id="user_commstats_usnatched"<?=($Override === 2 ? ' class="paranoia_override"' : '')?>></span>
<?
		}
	}
	if ($Override = check_paranoia_here('snatched')) { ?>
					<a href="torrents.php?type=snatched&amp;userid=<?=$UserID?>" class="brackets<?=($Override === 2 ? ' paranoia_override' : '')?>">View</a>
<?					if (check_perms('zip_downloader')) { ?>
					<a href="torrents.php?action=redownload&amp;type=snatches&amp;userid=<?=$UserID?>" onclick="return confirm('If you no longer have the content, your ratio WILL be affected, be sure to check the size of all torrents before redownloading.');" class="brackets">Download</a>
<?					} ?>
				</li>
<?
	}
	if (check_perms('site_view_torrent_snatchlist', $Class)) {
?>
				<li id="comm_downloaded">Downloaded:
					<span class="user_commstats" id="user_commstats_downloaded"><a href="#" class="brackets" onclick="commStats(<?=$UserID?>); return false;">Show stats</a></span>
					<span id="user_commstats_udownloaded"></span>
					<a href="torrents.php?type=downloaded&amp;userid=<?=$UserID?>" class="brackets">View</a>
				</li>
<?
	}
	if ($Override = check_paranoia_here('invitedcount')) {
	$DB->query("
		SELECT COUNT(UserID)
		FROM users_info
		WHERE Inviter = '$UserID'");
	list($Invited) = $DB->next_record();
?>
				<li id="comm_invited">Invited: <?=number_format($Invited)?></li>
<?
	}
?>
			</ul>
<?	if ($LoggedUser['AutoloadCommStats']) { ?>
			<script type="text/javascript">
				commStats(<?=$UserID?>);
			</script>
<?	} ?>
		</div>
