<?
define('FOOTER_FILE', SERVER_ROOT.'/design/privatefooter.php');
$HTTPS = ($_SERVER['SERVER_PORT'] == 443) ? 'ssl_' : '';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title><?=display_str($PageTitle)?></title>
<meta http-equiv="X-UA-Compatible" content="chrome=1;IE=edge" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<link rel="apple-touch-icon" href="/apple-touch-icon.png" />
<link rel="search" type="application/opensearchdescription+xml"
	title="<?=SITE_NAME?> Torrents" href="opensearch.php?type=torrents" />
<link rel="search" type="application/opensearchdescription+xml"
	title="<?=SITE_NAME?> Artists" href="opensearch.php?type=artists" />
<link rel="search" type="application/opensearchdescription+xml"
	title="<?=SITE_NAME?> Requests" href="opensearch.php?type=requests" />
<link rel="search" type="application/opensearchdescription+xml"
	title="<?=SITE_NAME?> Forums" href="opensearch.php?type=forums" />
<link rel="search" type="application/opensearchdescription+xml"
	title="<?=SITE_NAME?> Log" href="opensearch.php?type=log" />
<link rel="search" type="application/opensearchdescription+xml"
	title="<?=SITE_NAME?> Users" href="opensearch.php?type=users" />
<link rel="search" type="application/opensearchdescription+xml"
	title="<?=SITE_NAME?> Wiki" href="opensearch.php?type=wiki" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=feed_news&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - News" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=feed_blog&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - Blog" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=feed_changelog&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - Gazelle Change Log" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_notify_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - P.T.N." />
<?
if (isset($LoggedUser['Notify'])) {
	foreach ($LoggedUser['Notify'] as $Filter) {
		list($FilterID, $FilterName) = $Filter;
?>
	<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_notify_<?=$FilterID?>_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;name=<?=urlencode($FilterName)?>"
	title="<?=SITE_NAME?> - <?=display_str($FilterName)?>" />
<?
	}
}
?>
	<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_all&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - All Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_music&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - Music Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_apps&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - Application Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_ebooks&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - E-Book Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_abooks&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - Audiobooks Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_evids&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - E-Learning Video Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_comedy&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - Comedy Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_comics&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - Comic Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_mp3&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - MP3 Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_flac&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - FLAC Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_vinyl&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - Vinyl Sourced Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_lossless&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - Lossless Torrents" />
<link rel="alternate" type="application/rss+xml"
	href="feeds.php?feed=torrents_lossless24&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>"
	title="<?=SITE_NAME?> - 24bit Lossless Torrents" />

<link
	href="<?=STATIC_SERVER?>styles/global.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/global.css')?>"
	rel="stylesheet" type="text/css" />
<? if ($Mobile) { ?>
	<meta name="viewport"
	content="width=device-width; initial-scale=1.0; maximum-scale=1.0, user-scalable=no;" />
<link href="<?=STATIC_SERVER ?>styles/mobile/style.css" rel="stylesheet"
	type="text/css" />
<?
} else {
	if (empty($LoggedUser['StyleURL'])) {
?>
	<link
	href="<?=STATIC_SERVER?>styles/<?=$LoggedUser['StyleName']?>/style.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/'.$LoggedUser['StyleName'].'/style.css')?>"
	title="<?=$LoggedUser['StyleName']?>" rel="stylesheet" type="text/css"
	media="screen" />
<?	} else { ?>
	<link href="<?=$LoggedUser['StyleURL']?>" title="External CSS"
	rel="stylesheet" type="text/css" media="screen" />
<?
	}
	if ($LoggedUser['UseOpenDyslexic']) {
		// load the OpenDyslexic font ?>
	<link rel="stylesheet"
	href="<?=STATIC_SERVER?>styles/opendyslexic/style.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/opendyslexic/style.css')?>"
	type="text/css" charset="utf-8" />

<!--<link href="<?=STATIC_SERVER?>styles/opendyslexic/style.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/opendyslexic/style.css')?>" title="OpenDyslexic" rel="stylesheet" type="text/css" media="screen" />-->
<?
	}
}
?>

	<script src="<?=STATIC_SERVER?>functions/jquery.js"
	type="text/javascript"></script>
<script
	src="<?=STATIC_SERVER?>functions/script_start.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/script_start.js')?>"
	type="text/javascript"></script>
<script
	src="<?=STATIC_SERVER?>functions/ajax.class.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/ajax.class.js')?>"
	type="text/javascript"></script>
<script type="text/javascript">//<![CDATA[
		var authkey = "<?=$LoggedUser['AuthKey']?>";
		var userid = <?=$LoggedUser['ID']?>;
	//]]></script>
<script
	src="<?=STATIC_SERVER?>functions/global.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/global.js')?>"
	type="text/javascript"></script>
<script src="<?=STATIC_SERVER?>functions/jquery.autocomplete.js"
	type="text/javascript"></script>
<script src="<?=STATIC_SERVER?>functions/autocomplete.js"
	type="text/javascript"></script>

<?

$Scripts = explode(',', $JSIncludes);
foreach ($Scripts as $Script) {
	if (empty($Script)) {
		continue;
	}
?>
	<script
	src="<?=STATIC_SERVER?>functions/<?=$Script?>.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/'.$Script.'.js')?>"
	type="text/javascript"></script>
<?
}
if ($Mobile) { ?>
	<script src="<?=STATIC_SERVER?>styles/mobile/style.js"
	type="text/javascript"></script>
<?
}
?>
</head>
<body id="<?=$Document == 'collages' ? 'collage' : $Document?>">
	<div id="wrapper">
		<h1 class="hidden"><?=SITE_NAME?></h1>

		<div id="header">
			<div id="logo">
				<a href="index.php"></a>
			</div>
			<div id="userinfo">
				<ul id="userinfo_username">
					<li id="nav_userinfo"
						<?=Format::add_class($PageID, array('user',false,false), 'active', true, 'id')?>><a
						href="user.php?id=<?=$LoggedUser['ID']?>" class="username"><?=$LoggedUser['Username']?></a></li>
					<li id="nav_useredit"
						class="brackets<?=Format::add_class($PageID, array('user','edit'), 'active', false)?>"><a
						href="user.php?action=edit&amp;userid=<?=$LoggedUser['ID']?>">Edit</a></li>
					<li id="nav_logout" class="brackets"><a
						href="logout.php?auth=<?=$LoggedUser['AuthKey']?>">Logout</a></li>
				</ul>
				<ul id="userinfo_major">
					<li id="nav_upload"
						class="brackets<?=Format::add_class($PageID, array('upload'), 'active', false)?>"><a
						href="upload.php">Upload</a></li>
<?
if (check_perms('site_send_unlimited_invites')) {
	$Invites = ' (∞)';
} elseif ($LoggedUser['Invites'] > 0) {
	$Invites = ' ('.$LoggedUser['Invites'].')';
} else {
	$Invites = '';
}
?>
			<li id="nav_invite"
						class="brackets<?=Format::add_class($PageID, array('user','invite'), 'active', false)?>"><a
						href="user.php?action=invite">Invite<?=$Invites?></a></li>
					<li id="nav_donate"
						class="brackets<?=Format::add_class($PageID, array('donate'), 'active', false)?>"><a
						href="donate.php">Donate</a></li>
					
				</ul>
				<ul id="userinfo_stats">
					<li id="stats_seeding"><a
						href="torrents.php?type=seeding&amp;userid=<?=$LoggedUser['ID']?>">Up</a>:
						<span class="stat"
						title="<?=Format::get_size($LoggedUser['BytesUploaded'], 5)?>"><?=Format::get_size($LoggedUser['BytesUploaded'])?></span></li>
					<li id="stats_leeching"><a
						href="torrents.php?type=leeching&amp;userid=<?=$LoggedUser['ID']?>">Down</a>:
						<span class="stat"
						title="<?=Format::get_size($LoggedUser['BytesDownloaded'], 5)?>"><?=Format::get_size($LoggedUser['BytesDownloaded'])?></span></li>
					<li id="stats_ratio">Ratio: <span class="stat"><?=Format::get_ratio_html($LoggedUser['BytesUploaded'], $LoggedUser['BytesDownloaded'])?></span></li>
<?	if (!empty($LoggedUser['RequiredRatio'])) { ?>
			<li id="stats_required"><a href="rules.php?p=ratio">Required</a>: <span
						class="stat"
						title="<?=number_format($LoggedUser['RequiredRatio'], 5)?>"><?=number_format($LoggedUser['RequiredRatio'], 2)?></span></li>
<?	}
	if ($LoggedUser['FLTokens'] > 0) { ?>
			<li id="fl_tokens"><a href="wiki.php?action=article&amp;id=754">Tokens</a>:
						<span class="stat"><a
							href="userhistory.php?action=token_history&amp;userid=<?=$LoggedUser['ID']?>"><?=$LoggedUser['FLTokens']?></a></span></li>
<?	} ?>
		</ul>
<?
$NewSubscriptions = $Cache->get_value('subscriptions_user_new_'.$LoggedUser['ID']);
if ($NewSubscriptions === false) {
	if ($LoggedUser['CustomForums']) {
		unset($LoggedUser['CustomForums']['']);
		$RestrictedForums = implode("','", array_keys($LoggedUser['CustomForums'], 0));
		$PermittedForums = implode("','", array_keys($LoggedUser['CustomForums'], 1));
	}
	$DB->query("
		SELECT COUNT(s.TopicID)
		FROM users_subscriptions AS s
			JOIN forums_last_read_topics AS l ON s.UserID = l.UserID AND s.TopicID = l.TopicID
			JOIN forums_topics AS t ON l.TopicID = t.ID
			JOIN forums AS f ON t.ForumID = f.ID
		WHERE (f.MinClassRead <= ".$LoggedUser['Class']." OR f.ID IN ('$PermittedForums'))
			AND l.PostID < t.LastPostID
			AND s.UserID = ".$LoggedUser['ID'].
		(!empty($RestrictedForums) ? "
			AND f.ID NOT IN ('$RestrictedForums')" : ''));
	list($NewSubscriptions) = $DB->next_record();
	$Cache->cache_value('subscriptions_user_new_'.$LoggedUser['ID'], $NewSubscriptions, 0);
} ?>
		<ul id="userinfo_minor"
					<?=($NewSubscriptions ? ' class="highlite"' : '')?>>
					<li id="nav_inbox"
						<?=Format::add_class($PageID, array('inbox'), 'active', true)?>><a
						onmousedown="Stats('inbox');"
						href="<?=Inbox::get_inbox_link(); ?>">Inbox</a></li>
					<li id="nav_staffinbox"
						<?=Format::add_class($PageID, array('staffpm'), 'active', true)?>><a
						onmousedown="Stats('staffpm');" href="staffpm.php">Staff Inbox</a></li>
					<li id="nav_uploaded"
						<?=Format::add_class($PageID, array('torrents',false,'uploaded'), 'active', true, 'userid')?>><a
						onmousedown="Stats('uploads');"
						href="torrents.php?type=uploaded&amp;userid=<?=$LoggedUser['ID']?>">Uploads</a></li>
					<li id="nav_bookmarks"
						<?=Format::add_class($PageID, array('bookmarks'), 'active', true)?>><a
						onmousedown="Stats('bookmarks');"
						href="bookmarks.php?type=torrents">Bookmarks</a></li>
<? if (check_perms('site_torrents_notify')) { ?>
			<li id="nav_notifications"
						<?=Format::add_class($PageID, array(array('torrents','notify'),array('user','notify')), 'active', true, 'userid')?>><a
						onmousedown="Stats('notifications');"
						href="user.php?action=notify">Notifications</a></li>
<? } ?>
			<li id="nav_subscriptions"
						<?=Format::add_class($PageID, array('userhistory','subscriptions'), 'active', true)?>><a
						onmousedown="Stats('subscriptions');"
						href="userhistory.php?action=subscriptions"
						<?=($NewSubscriptions ? ' class="new-subscriptions"' : '')?>>Subscriptions</a></li>
					<li id="nav_comments"
						<?=Format::add_class($PageID, array('comments'), 'active', true, 'userid')?>><a
						onmousedown="Stats('comments');" href="comments.php">Comments</a></li>
					<li id="nav_friends"
						<?=Format::add_class($PageID, array('friends'), 'active', true)?>><a
						onmousedown="Stats('friends');" href="friends.php">Friends</a></li>
				</ul>
			</div>
			<div id="menu">
				<h4 class="hidden">Site Menu</h4>
				<ul>
					<li id="nav_index"
						<?=Format::add_class($PageID, array('index'), 'active', true)?>><a
						href="index.php">Home</a></li>
					<li id="nav_torrents"
						<?=Format::add_class($PageID, array('torrents',false,false), 'active', true)?>><a
						href="torrents.php">Torrents</a></li>
					<li id="nav_collages"
						<?=Format::add_class($PageID, array('collages'), 'active', true)?>><a
						href="collages.php">Collages</a></li>
					<li id="nav_requests"
						<?=Format::add_class($PageID, array('requests'), 'active', true)?>><a
						href="requests.php">Requests</a></li>
					<li id="nav_forums"
						<?=Format::add_class($PageID, array('forums'), 'active', true)?>><a
						href="forums.php">Forums</a></li>
					<li id="nav_irc"
						<?=Format::add_class($PageID, array('chat'), 'active', true)?>><a
						href="chat.php">IRC</a></li>
					<li id="nav_top10"
						<?=Format::add_class($PageID, array('top10'), 'active', true)?>><a
						href="top10.php">Top 10</a></li>
					<li id="nav_rules"
						<?=Format::add_class($PageID, array('rules'), 'active', true)?>><a
						href="rules.php">Rules</a></li>
					<li id="nav_wiki"
						<?=Format::add_class($PageID, array('wiki'), 'active', true)?>><a
						href="wiki.php">Wiki</a></li>
					<li id="nav_staff"
						<?=Format::add_class($PageID, array('staff'), 'active', true)?>><a
						href="staff.php">Staff</a></li>
				</ul>
			</div>
<?
//Start handling alert bars
$Alerts = array();
$ModBar = array();

//Quotes
if ($LoggedUser['NotifyOnQuote']) {
	$QuoteNotificationsCount = $Cache->get_value('notify_quoted_'.$LoggedUser['ID']);
	if ($QuoteNotificationsCount === false) {
		if ($LoggedUser['CustomForums']) {
			unset($LoggedUser['CustomForums']['']);
			$RestrictedForums = implode("','", array_keys($LoggedUser['CustomForums'], 0));
			$PermittedForums = implode("','", array_keys($LoggedUser['CustomForums'], 1));
		}
		$sql = "
			SELECT COUNT(q.UnRead)
			FROM users_notify_quoted AS q
				LEFT JOIN forums_topics AS t ON t.ID = q.PageID
				LEFT JOIN forums AS f ON f.ID = t.ForumID
			WHERE q.UserID = $LoggedUser[ID]
				AND q.UnRead = 1
				AND q.Page = 'forums'
				AND ((f.MinClassRead <= '$LoggedUser[Class]'";
		if (!empty($RestrictedForums)) {
			$sql .= " AND f.ID NOT IN ('$RestrictedForums')";
		}
		$sql .= ')';
		if (!empty($PermittedForums)) {
			$sql .= " OR f.ID IN ('$PermittedForums')";
		}
		$sql .= ')';
		$DB->query($sql);
		list($QuoteNotificationsCount) = $DB->next_record();
		$Cache->cache_value('notify_quoted_'.$LoggedUser['ID'], $QuoteNotificationsCount, 0);
	}
	if ($QuoteNotificationsCount > 0) {
		$Alerts[] = '<a href="userhistory.php?action=quote_notifications">'. 'New quote'. ($QuoteNotificationsCount > 1 ? 's' : '') . '</a>';
	}
}

// News
$MyNews = $LoggedUser['LastReadNews'];
$CurrentNews = $Cache->get_value('news_latest_id');
if ($CurrentNews === false) {
	$DB->query("
		SELECT ID
		FROM news
		ORDER BY Time DESC
		LIMIT 1");
	if ($DB->record_count() == 1) {
		list($CurrentNews) = $DB->next_record();
	} else {
		$CurrentNews = -1;
	}
	$Cache->cache_value('news_latest_id', $CurrentNews, 0);
}
if ($MyNews < $CurrentNews) {
	$Alerts[] = '<a href="index.php">New announcement!</a>';
}

// Blog
$MyBlog = $LoggedUser['LastReadBlog'];
$CurrentBlog = $Cache->get_value('blog_latest_id');
if ($CurrentBlog === false) {
	$DB->query("
		SELECT ID
		FROM blog
		WHERE Important = 1
		ORDER BY Time DESC
		LIMIT 1");
	if ($DB->record_count() == 1) {
		list($CurrentBlog) = $DB->next_record();
	} else {
		$CurrentBlog = -1;
	}
	$Cache->cache_value('blog_latest_id', $CurrentBlog, 0);
}
if ($MyBlog < $CurrentBlog) {
	$Alerts[] = '<a href="blog.php">New blog post!</a>';
}

// Staff blog
if (check_perms('users_mod')) {
	global $SBlogReadTime, $LatestSBlogTime;
	if (!$SBlogReadTime && ($SBlogReadTime = $Cache->get_value('staff_blog_read_'.$LoggedUser['ID'])) === false) {
		$DB->query("
			SELECT Time
			FROM staff_blog_visits
			WHERE UserID = ".$LoggedUser['ID']);
		if (list($SBlogReadTime) = $DB->next_record()) {
			$SBlogReadTime = strtotime($SBlogReadTime);
		} else {
			$SBlogReadTime = 0;
		}
		$Cache->cache_value('staff_blog_read_'.$LoggedUser['ID'], $SBlogReadTime, 1209600);
	}
	if (!$LatestSBlogTime && ($LatestSBlogTime = $Cache->get_value('staff_blog_latest_time')) === false) {
		$DB->query("
			SELECT MAX(Time)
			FROM staff_blog");
		if (list($LatestSBlogTime) = $DB->next_record()) {
			$LatestSBlogTime = strtotime($LatestSBlogTime);
		} else {
			$LatestSBlogTime = 0;
		}
		$Cache->cache_value('staff_blog_latest_time', $LatestSBlogTime, 1209600);
	}
	if ($SBlogReadTime < $LatestSBlogTime) {
		$Alerts[] = '<a href="staffblog.php">New staff blog post!</a>';
	}
}

//Staff PM
$NewStaffPMs = $Cache->get_value('staff_pm_new_'.$LoggedUser['ID']);
if ($NewStaffPMs === false) {
	$DB->query("
		SELECT COUNT(ID)
		FROM staff_pm_conversations
		WHERE UserID = '".$LoggedUser['ID']."'
			AND Unread = '1'");
	list($NewStaffPMs) = $DB->next_record();
	$Cache->cache_value('staff_pm_new_'.$LoggedUser['ID'], $NewStaffPMs, 0);
}

if ($NewStaffPMs > 0) {
	$Alerts[] = '<a href="staffpm.php">You have '.$NewStaffPMs.(($NewStaffPMs > 1) ? ' new staff messages' : ' new staff message').'</a>';
}

//Inbox
$NewMessages = $Cache->get_value('inbox_new_'.$LoggedUser['ID']);
if ($NewMessages === false) {
	$DB->query("
		SELECT COUNT(UnRead)
		FROM pm_conversations_users
		WHERE UserID = '".$LoggedUser['ID']."'
			AND UnRead = '1'
			AND InInbox = '1'");
	list($NewMessages) = $DB->next_record();
	$Cache->cache_value('inbox_new_'.$LoggedUser['ID'], $NewMessages, 0);
}

if ($NewMessages > 0) {
	$Alerts[] = '<a href="' . Inbox::get_inbox_link() . "\">You have $NewMessages".(($NewMessages > 1) ? ' new messages' : ' new message').'</a>';
}

if ($LoggedUser['RatioWatch']) {
	$Alerts[] = '<a href="rules.php?p=ratio">Ratio Watch</a>: You have '.time_diff($LoggedUser['RatioWatchEnds'], 3).' to get your ratio over your required ratio or your leeching abilities will be disabled.';
} elseif ($LoggedUser['CanLeech'] != 1) {
	$Alerts[] = '<a href="rules.php?p=ratio">Ratio Watch</a>: Your downloading privileges are disabled until you meet your required ratio.';
}

if (check_perms('site_torrents_notify')) {
	$NewNotifications = $Cache->get_value('notifications_new_'.$LoggedUser['ID']);
	if ($NewNotifications === false) {
		$DB->query("
			SELECT COUNT(UserID)
			FROM users_notify_torrents
			WHERE UserID = '$LoggedUser[ID]'
				AND UnRead = '1'");
		list($NewNotifications) = $DB->next_record();
		/* if ($NewNotifications && !check_perms('site_torrents_notify')) {
			$DB->query("
				DELETE FROM users_notify_torrents
				WHERE UserID = '$LoggedUser[ID]'");
			$DB->query("
				DELETE FROM users_notify_filters
				WHERE UserID = '$LoggedUser[ID]'");
		} */
		$Cache->cache_value('notifications_new_'.$LoggedUser['ID'], $NewNotifications, 0);
	}
	if ($NewNotifications > 0) {
		$Alerts[] = '<a href="torrents.php?action=notify">You have '.$NewNotifications.(($NewNotifications > 1) ? ' new torrent notifications' : ' new torrent notification').'</a>';
	}
}

// Collage subscriptions
if (check_perms('site_collages_subscribe')) {
	$NewCollages = $Cache->get_value('collage_subs_user_new_'.$LoggedUser['ID']);
	if ($NewCollages === false) {
			$DB->query("
				SELECT COUNT(DISTINCT s.CollageID)
				FROM users_collage_subs as s
					JOIN collages as c ON s.CollageID = c.ID
					JOIN collages_torrents as ct on ct.CollageID = c.ID
				WHERE s.UserID = $LoggedUser[ID]
					AND ct.AddedOn > s.LastVisit
					AND c.Deleted = '0'");
			list($NewCollages) = $DB->next_record();
			$Cache->cache_value('collage_subs_user_new_'.$LoggedUser['ID'], $NewCollages, 0);
	}
	if ($NewCollages > 0) {
		$Alerts[] = '<a href="userhistory.php?action=subscribed_collages">You have '.$NewCollages.(($NewCollages > 1) ? ' new collage updates' : ' new collage update').'</a>';
	}
}
if (check_perms('users_mod')) {
	$ModBar[] = '<a href="tools.php">Toolbox</a>';
}
if (check_perms('users_mod') || $LoggedUser['PermissionID'] == FORUM_MOD) {
	$NumStaffPMs = $Cache->get_value('num_staff_pms_'.$LoggedUser['ID']);
	if ($NumStaffPMs === false) {
		if (check_perms('users_mod')) {
			$DB->query("
				SELECT COUNT(ID)
				FROM staff_pm_conversations
				WHERE Status = 'Unanswered'
					AND (AssignedToUser = ".$LoggedUser['ID']."
						OR (Level >= ".max(700, $Classes[MOD]['Level'])."
							AND Level <= ".$LoggedUser['Class']."))");
		}
		if ($LoggedUser['PermissionID'] == FORUM_MOD) {
			$DB->query("
				SELECT COUNT(ID)
				FROM staff_pm_conversations
				WHERE Status='Unanswered'
					AND (AssignedToUser = ".$LoggedUser['ID']."
						OR Level = '". $Classes[FORUM_MOD]['Level'] . "')");
		}
		list($NumStaffPMs) = $DB->next_record();
		$Cache->cache_value('num_staff_pms_'.$LoggedUser['ID'], $NumStaffPMs , 1000);
	}

	if ($NumStaffPMs > 0) {
		$ModBar[] = '<a href="staffpm.php">'.$NumStaffPMs.' Staff PMs</a>';
	}
}
if (check_perms('admin_reports')) {
// Torrent reports code
	$NumTorrentReports = $Cache->get_value('num_torrent_reportsv2');
	if ($NumTorrentReports === false) {
		$DB->query("
			SELECT COUNT(ID)
			FROM reportsv2
			WHERE Status = 'New'");
		list($NumTorrentReports) = $DB->next_record();
		$Cache->cache_value('num_torrent_reportsv2', $NumTorrentReports, 0);
	}

	$ModBar[] = '<a href="reportsv2.php">'.$NumTorrentReports.(($NumTorrentReports == 1) ? ' Report' : ' Reports').'</a>';

// Other reports code
	$NumOtherReports = $Cache->get_value('num_other_reports');
	if ($NumOtherReports === false) {
		$DB->query("
			SELECT COUNT(ID)
			FROM reports
			WHERE Status = 'New'");
		list($NumOtherReports) = $DB->next_record();
		$Cache->cache_value('num_other_reports', $NumOtherReports, 0);
	}

	if ($NumOtherReports > 0) {
		$ModBar[] = '<a href="reports.php">'.$NumOtherReports.(($NumTorrentReports == 1) ? ' Other report' : ' Other reports').'</a>';
	}
} elseif (check_perms('project_team')) {
	$NumUpdateReports = $Cache->get_value('num_update_reports');
	if ($NumUpdateReports === false) {
		$DB->query("
			SELECT COUNT(ID)
			FROM reports
			WHERE Status = 'New'
				AND Type = 'request_update'");
		list($NumUpdateReports) = $DB->next_record();
		$Cache->cache_value('num_update_reports', $NumUpdateReports, 0);
	}

	if ($NumUpdateReports > 0) {
		$ModBar[] = '<a href="reports.php">Request update reports</a>';
	}
} elseif (check_perms('site_moderate_forums')) {
	$NumForumReports = $Cache->get_value('num_forum_reports');
	if ($NumForumReports === false) {
		$DB->query("
			SELECT COUNT(ID)
			FROM reports
			WHERE Status = 'New'
				AND Type IN('artist_comment', 'collages_comment', 'post', 'requests_comment', 'thread', 'torrents_comment')");
		list($NumForumReports) = $DB->next_record();
		$Cache->cache_value('num_forum_reports', $NumForumReports, 0);
	}

	if ($NumForumReports > 0) {
		$ModBar[] = '<a href="reports.php">'.$NumForumReports.(($NumForumReports == 1) ? ' Forum report' : ' Forum reports').'</a>';
	}
}



if (!empty($Alerts) || !empty($ModBar)) {
?>
	<div id="alerts">
<?		foreach ($Alerts as $Alert) { ?>
		<div class="alertbar"><?=$Alert?></div>
<?		}
		if (!empty($ModBar)) { ?>
		<div class="alertbar blend"><?=implode(' | ', $ModBar)?></div>
<?		} ?>
	</div>
<?
}
//Done handling alertbars


?>
	<div id="searchbars">
				<ul>
					<li id="searchbar_torrents"><span class="hidden">Torrents: </span>
						<form class="search_form" name="torrents" action="torrents.php"
							method="get">
<? if (isset($LoggedUser['SearchType']) && $LoggedUser['SearchType']) { // Advanced search ?>
					<input type="hidden" name="action" value="advanced" />
<? } ?>
					<input id="torrentssearch" accesskey="t" spellcheck="false"
								onfocus="if (this.value == 'Torrents') this.value='';"
								onblur="if (this.value == '') this.value='Torrents';"
								<? if (isset($LoggedUser['SearchType']) && $LoggedUser['SearchType']) { // Advanced search ?>
								value="Torrents" type="text" name="groupname" size="17"
								<? } else { ?> value="Torrents" type="text" name="searchstr"
								size="17" <? } ?> />
						</form></li>
					<li id="searchbar_artists"><span class="hidden">Artist: </span>
						<form class="search_form" name="artists" action="artist.php"
							method="get">
							<input id="artistsearch" <? Users::has_autocomplete_enabled('search'); ?>
								accesskey="a"
								spellcheck="false" autocomplete="off"
								onfocus="if (this.value == 'Artists') this.value='';"
								onblur="if (this.value == '') this.value='Artists';"
								value="Artists" type="text" name="artistname" size="17" />
						</form>
						</li>
					<li id="searchbar_requests"><span class="hidden">Requests: </span>
						<form class="search_form" name="requests" action="requests.php"
							method="get">
							<input id="requestssearch" spellcheck="false"
								onfocus="if (this.value == 'Requests') this.value='';"
								onblur="if (this.value == '') this.value='Requests';"
								value="Requests" type="text" name="search" size="17" />
						</form></li>
					<li id="searchbar_forums"><span class="hidden">Forums: </span>
						<form class="search_form" name="forums" action="forums.php"
							method="get">
							<input value="search" type="hidden" name="action" /> <input
								id="forumssearch"
								onfocus="if (this.value == 'Forums') this.value='';"
								onblur="if (this.value == '') this.value='Forums';"
								value="Forums" type="text" name="search" size="17" />
						</form></li>
					<!--
			<li id="searchbar_wiki">
				<span class="hidden">Wiki: </span>
				<form class="search_form" name="wiki" action="wiki.php" method="get">
					<input type="hidden" name="action" value="search" />
					<input
						onfocus="if (this.value == 'Wiki') this.value='';"
						onblur="if (this.value == '') this.value='Wiki';"
						value="Wiki" type="text" name="search" size="17"
					/>
				</form>
			</li>
-->
					<li id="searchbar_log"><span class="hidden">Log: </span>
						<form class="search_form" name="log" action="log.php" method="get">
							<input id="logsearch"
								onfocus="if (this.value == 'Log') this.value='';"
								onblur="if (this.value == '') this.value='Log';" value="Log"
								type="text" name="search" size="17" />
						</form></li>
					<li id="searchbar_users"><span class="hidden">Users: </span>
						<form class="search_form" name="users" action="user.php"
							method="get">
							<input type="hidden" name="action" value="search" /> <input
								id="userssearch"
								onfocus="if (this.value == 'Users') this.value='';"
								onblur="if (this.value == '') this.value='Users';" value="Users"
								type="text" name="search" size="20" />
						</form></li>
				</ul>
			</div>
		</div>
		<div id="content">
