<?php

$Queries = array();

$OrderWays = array('year', 'votes', 'bounty', 'created', 'lastvote', 'filled');
list($Page, $Limit) = Format::page_limit(REQUESTS_PER_PAGE);
$Submitted = !empty($_GET['submit']);

//Paranoia
$UserInfo = Users::user_info((int)$_GET['userid']);
$Perms = Permissions::get_permissions($UserInfo['PermissionID']);
$UserClass = $Perms['Class'];

$BookmarkView = false;

if (empty($_GET['type'])) {
	$Title = 'Requests';
	if (!check_perms('site_see_old_requests') || empty($_GET['showall'])) {
		$SS->set_filter('visible', array(1));
	}
} else {
	switch ($_GET['type']) {
		case 'created':
			if (!empty($_GET['userid'])) {
				if (is_number($_GET['userid'])) {
					if (!check_paranoia('requestsvoted_list', $UserInfo['Paranoia'], $Perms['Class'], $_GET['userid'])) {
						error(403);
					}
					$Title = 'Requests created by ' . $UserInfo['Username'];
					$SS->set_filter('userid', array($_GET['userid']));
				} else {
					error(404);
				}
			} else {
				$Title = 'My requests';
				$SS->set_filter('userid', array($LoggedUser['ID']));
			}
			break;
		case 'voted':
			if (!empty($_GET['userid'])) {
				if (is_number($_GET['userid'])) {
					if (!check_paranoia('requestsvoted_list', $UserInfo['Paranoia'], $Perms['Class'], $_GET['userid'])) {
						error(403);
					}
					$Title = "Requests voted for by ".$UserInfo['Username'];
					$SS->set_filter('voter', array($_GET['userid']));
				} else {
					error(404);
				}
			} else {
				$Title = "Requests I've voted on";
				$SS->set_filter('voter', array($LoggedUser['ID']));
			}
			break;
		case 'filled':
			if (empty($_GET['userid']) || !is_number($_GET['userid'])) {
				error(404);
			} else {
				if (!check_paranoia('requestsfilled_list', $UserInfo['Paranoia'], $Perms['Class'], $_GET['userid'])) {
					error(403);
				}
				$Title = "Requests filled by ".$UserInfo['Username'];
				$SS->set_filter('fillerid', array($_GET['userid']));
			}
			break;
		case 'bookmarks':
			$Title = 'Your bookmarked requests';
			$BookmarkView = true;
			$SS->set_filter('bookmarker', array($LoggedUser['ID']));
			break;
		default:
			error(404);
	}
}

if ($Submitted && empty($_GET['show_filled'])) {
	$SS->set_filter('torrentid', array(0));
}

$EnableNegation = false; // Sphinx needs at least one positive search condition to support the NOT operator

if (!empty($_GET['formats'])) {
	$FormatArray = $_GET['formats'];
	if (count($FormatArray) != count($Formats)) {
		$FormatNameArray = array();
		foreach ($FormatArray as $Index => $MasterIndex) {
			if (isset($Formats[$MasterIndex])) {
				$FormatNameArray[$Index] = '"'.strtr($Formats[$MasterIndex], '-.', '  ').'"';
			}
		}
		if (count($FormatNameArray) >= 1) {
			$EnableNegation = true;
			if (!empty($_GET['formats_strict'])) {
				$Queries[]='@formatlist ('.implode(' | ', $FormatNameArray).')';
			} else {
				$Queries[]='@formatlist (any | '.implode(' | ', $FormatNameArray).')';
			}
		}
	}
}

if (!empty($_GET['media'])) {
	$MediaArray = $_GET['media'];
	if (count($MediaArray) != count($Media)) {
		$MediaNameArray = array();
		foreach ($MediaArray as $Index => $MasterIndex) {
			if (isset($Media[$MasterIndex])) {
				$MediaNameArray[$Index] = '"'.strtr($Media[$MasterIndex], '-.', '  ').'"';
			}
		}

		if (count($MediaNameArray) >= 1) {
			$EnableNegation = true;
			if (!empty($_GET['media_strict'])) {
				$Queries[]='@medialist ('.implode(' | ', $MediaNameArray).')';
			} else {
				$Queries[]='@medialist (any | '.implode(' | ', $MediaNameArray).')';
			}
		}
	}
}

if (!empty($_GET['bitrates'])) {
	$BitrateArray = $_GET['bitrates'];
	if (count($BitrateArray) != count($Bitrates)) {
		$BitrateNameArray = array();
		foreach ($BitrateArray as $Index => $MasterIndex) {
			if (isset($Bitrates[$MasterIndex])) {
				$BitrateNameArray[$Index] = '"'.strtr($SS->EscapeString($Bitrates[$MasterIndex]), '-.', '  ').'"';
			}
		}

		if (count($BitrateNameArray) >= 1) {
			$EnableNegation = true;
			if (!empty($_GET['bitrate_strict'])) {
				$Queries[]='@bitratelist ('.implode(' | ', $BitrateNameArray).')';
			} else {
				$Queries[]='@bitratelist (any | '.implode(' | ', $BitrateNameArray).')';
			}
		}
	}
}

if (!empty($_GET['search'])) {
	$SearchString = trim($_GET['search']);
	if ($SearchString != '') {
		$SearchWords = array('include' => array(), 'exclude' => array());
		$Words = explode(' ', $SearchString);
		foreach ($Words as $Word) {
			$Word = trim($Word);
			// Skip isolated hyphens to enable "Artist - Title" searches
			if ($Word == '-') {
				continue;
			}
			if ($Word[0] == '!' && strlen($Word) >= 2) {
				if (strpos($Word,'!',1) === false) {
					$SearchWords['exclude'][] = $Word;
				} else {
					$SearchWords['include'][] = $Word;
					$EnableNegation = true;
				}
			} elseif ($Word != '') {
				$SearchWords['include'][] = $Word;
				$EnableNegation = true;
			}
		}
		$QueryParts = array();
		if (!$EnableNegation && !empty($SearchWords['exclude'])) {
			$SearchWords['include'] = array_merge($SearchWords['include'], $SearchWords['exclude']);
			unset($SearchWords['exclude']);
		}
		foreach ($SearchWords['include'] as $Word) {
			$QueryParts[] = $SS->EscapeString($Word);
		}
		if (!empty($SearchWords['exclude'])) {
			foreach ($SearchWords['exclude'] as $Word) {
				$QueryParts[] = '!'.$SS->EscapeString(substr($Word,1));
			}
		}
		if (!empty($QueryParts)) {
			$Queries[] = "@* ".implode(' ', $QueryParts);
		}
	}
}

if (!empty($_GET['tags'])) {
	$Tags = explode(',', $_GET['tags']);
	$TagNames = array();
	if (!isset($_GET['tags_type']) || $_GET['tags_type'] == 1) {
		$TagType = 1;
		$_GET['tags_type'] = '1';
	} else {
		$TagType = 0;
		$_GET['tags_type'] = '0';
	}
	foreach ($Tags as $Tag) {
		$Tag = ltrim($Tag);
		$Exclude = ($Tag[0] == '!');
		$Tag = Misc::sanitize_tag($Tag);
		if (!empty($Tag)) {
			$TagNames[] = $Tag;
			$TagsExclude[$Tag] = $Exclude;
		}
	}
	$AllNegative = !in_array(false, $TagsExclude);
	$Tags = Misc::get_tags($TagNames);

	// Replace the ! characters that sanitize_tag removed
	if ($TagType == 1 || $AllNegative) {
		foreach ($TagNames as &$TagName) {
			if ($TagsExclude[$TagName]) {
				$TagName = '!'.$TagName;
			}
		}
		unset($TagName);
	}
} elseif (!isset($_GET['tags_type']) || $_GET['tags_type'] != 0) {
	$_GET['tags_type'] = 1;
} else {
	$_GET['tags_type'] = 0;
}

// 'All' tags
if ($TagType == 1 && !empty($Tags)) {
	foreach ($Tags as $TagID => $TagName) {
		$SS->set_filter('tagid', array($TagID), $TagsExclude[$TagName]);
	}
} elseif (!empty($Tags)) {
	$SS->set_filter('tagid', array_keys($Tags), $AllNegative);
}

if (!empty($_GET['filter_cat'])) {
	$CategoryArray = array_keys($_GET['filter_cat']);
	$Debug->log_var(array($CategoryArray, $Categories));
	if (count($CategoryArray) != count($Categories)) {
		foreach ($CategoryArray as $Key => $Index) {
			if (!isset($Categories[$Index-1])) {
				unset($CategoryArray[$Key]);
			}
		}
		if (count($CategoryArray) >= 1) {
			$SS->set_filter('categoryid', $CategoryArray);
		}
	}
}

if (!empty($_GET['releases'])) {
	$ReleaseArray = $_GET['releases'];
	if (count($ReleaseArray) != count($ReleaseTypes)) {
		foreach ($ReleaseArray as $Index => $Value) {
			if (!isset($ReleaseTypes[$Value])) {
				unset($ReleaseArray[$Index]);
			}
		}
		if (count($ReleaseArray) >= 1) {
			$SS->set_filter('releasetype', $ReleaseArray);
		}
	}
}

if (!empty($_GET['requestor']) && check_perms('site_see_old_requests')) {
	if (is_number($_GET['requestor'])) {
		$SS->set_filter('userid', array($_GET['requestor']));
	} else {
		error(404);
	}
}

if (isset($_GET['year'])) {
	if (is_number($_GET['year']) || $_GET['year'] == 0) {
		$SS->set_filter('year', array($_GET['year']));
	} else {
		error(404);
	}
}

if (!empty($_GET['page']) && is_number($_GET['page']) && $_GET['page'] > 0) {
	$Page = $_GET['page'];
	$SS->limit(($Page - 1) * REQUESTS_PER_PAGE, REQUESTS_PER_PAGE);
} else {
	$Page = 1;
	$SS->limit(0, REQUESTS_PER_PAGE);
}

if (empty($_GET['order'])) {
	$CurrentOrder = 'created';
	$CurrentSort = 'desc';
	$Way = SPH_SORT_ATTR_DESC;
	$NewSort = 'asc';
} else {
	if (in_array($_GET['order'], $OrderWays)) {
		$CurrentOrder = $_GET['order'];
		if ($_GET['sort'] == 'asc' || $_GET['sort'] == 'desc') {
			$CurrentSort = $_GET['sort'];
			$Way = ($CurrentSort == 'asc' ? SPH_SORT_ATTR_ASC : SPH_SORT_ATTR_DESC);
			$NewSort = ($_GET['sort'] == 'asc' ? 'desc' : 'asc');
		} else {
			error(404);
		}
	} else {
		error(404);
	}
}

switch ($CurrentOrder) {
	case 'votes' :
		$OrderBy = 'Votes';
		break;
	case 'bounty' :
		$OrderBy = 'Bounty';
		break;
	case 'created' :
		$OrderBy = 'TimeAdded';
		break;
	case 'lastvote' :
		$OrderBy = 'LastVote';
		break;
	case 'filled' :
		$OrderBy = 'TimeFilled';
		break;
	case 'year' :
		$OrderBy = 'Year';
		break;
	default :
		$OrderBy = 'TimeAdded';
		break;
}
//print($Way); print($OrderBy); die();
$SS->SetSortMode($Way, $OrderBy);

if (count($Queries) > 0) {
	$Query = implode(' ',$Queries);
} else {
	$Query = '';
}

$SS->set_index('requests requests_delta');
$SphinxResults = $SS->search($Query, '', 0, array(), '', '');
$NumResults = $SS->TotalResults;
if ($NumResults && $NumResults < ($Page - 1) * REQUESTS_PER_PAGE + 1) {
	$PageLinks = Format::get_pages(0, $NumResults, REQUESTS_PER_PAGE);
} else {
	$PageLinks = Format::get_pages($Page, $NumResults, REQUESTS_PER_PAGE);
}

$CurrentURL = Format::get_url(array('order', 'sort'));
View::show_header($Title, 'requests');

?>
<div class="thin">
	<div class="header">
		<h2><?=$Title?></h2>
	</div>
	<div class="linkbox">
<?	if (!$BookmarkView) {
		if (check_perms('site_submit_requests')) { ?>
		<a href="requests.php?action=new" class="brackets">New request</a>
		<a href="requests.php?type=created" class="brackets">My requests</a>
<?		}
		if (check_perms('site_vote')) { ?>
		<a href="requests.php?type=voted" class="brackets">Requests I've voted on</a>
<?		} ?>
		<a href="bookmarks.php?type=requests" class="brackets">Bookmarked requests</a>
<?	} else { ?>
		<a href="bookmarks.php?type=torrents" class="brackets">Torrents</a>
		<a href="bookmarks.php?type=artists" class="brackets">Artists</a>
		<a href="bookmarks.php?type=collages" class="brackets">Collages</a>
		<a href="bookmarks.php?type=requests" class="brackets">Requests</a>
<?	} ?>
	</div>
	<form class="search_form" name="requests" action="" method="get">
<?	if ($BookmarkView) { ?>
		<input type="hidden" name="action" value="view" />
		<input type="hidden" name="type" value="requests" />
<?	} else { ?>
		<input type="hidden" name="type" value="<?=$_GET['type']?>" />
<?	} ?>
		<input type="hidden" name="submit" value="true" />
<?	if (!empty($_GET['userid']) && is_number($_GET['userid'])) { ?>
		<input type="hidden" name="userid" value="<?=$_GET['userid']?>" />
<?	} ?>
		<table cellpadding="6" cellspacing="1" border="0" class="layout border" width="100%">
			<tr id="search_terms">
				<td class="label">Search terms:</td>
				<td>
					<input type="text" name="search" size="75" value="<? if (isset($_GET['search'])) { echo display_str($_GET['search']); } ?>" />
				</td>
			</tr>
			<tr id="tagfilter">
				<td class="label">Tags (comma-separated):</td>
				<td>
					<input type="text" name="tags" id="tags" size="60" value="<?= (!empty($TagNames) ? display_str(implode(', ', $TagNames)) : '') ?>" <? Users::has_autocomplete_enabled('other'); ?>/>&nbsp;
					<input type="radio" name="tags_type" id="tags_type0" value="0"<? Format::selected('tags_type',0,'checked')?> /><label for="tags_type0"> Any</label>&nbsp;&nbsp;
					<input type="radio" name="tags_type" id="tags_type1" value="1"<? Format::selected('tags_type',1,'checked')?> /><label for="tags_type1"> All</label>
				</td>
			</tr>
			<tr id="include_filled">
				<td class="label">Include filled:</td>
				<td>
					<input type="checkbox" name="show_filled"<? if (!$Submitted || !empty($_GET['show_filled']) || (!$Submitted && !empty($_GET['type']) && $_GET['type'] == 'filled')) { ?> checked="checked"<? } ?> />
				</td>
			</tr>
<?	if (check_perms('site_see_old_requests')) { ?>
			<tr id="include_old">
				<td class="label">Include old:</td>
				<td>
					<input type="checkbox" name="showall"<? if (!empty($_GET['showall'])) { ?> checked="checked"<? } ?> />
				</td>
			</tr>
<?	/* ?>
			<tr>
				<td class="label">Requested by:</td>
				<td>
					<input type="text" name="requester" size="75" value="<?=display_str($_GET['requester'])?>" />
				</td>
			</tr>
<?	*/} ?>
		</table>
		<table class="layout cat_list">
<?
$x = 1;
reset($Categories);
foreach ($Categories as $CatKey => $CatName) {
	if ($x % 8 == 0 || $x == 1) {
?>
				<tr class="cat_list">
<?	} ?>
					<td>
						<input type="checkbox" name="filter_cat[<?=($CatKey + 1) ?>]" id="cat_<?=($CatKey + 1) ?>" value="1"<? if (isset($_GET['filter_cat'][$CatKey + 1])) { ?> checked="checked"<? } ?> />
						<label for="cat_<?=($CatKey + 1) ?>"><?=$CatName?></label>
					</td>
<?
	if ($x % 7 == 0) {
?>
				</tr>
<?
	}
	$x++;
}
?>
		</table>
		<table class="layout">
			<tr id="release_list">
				<td class="label">Release types</td>
				<td>
					<input type="checkbox" id="toggle_releases" onchange="Toggle('releases', 0)"<?=(!$Submitted || !empty($ReleaseArray) && count($ReleaseArray) == count($ReleaseTypes) ? ' checked="checked"' : '') ?> /> <label for="toggle_releases">All</label>
<?		$i = 0;
		foreach ($ReleaseTypes as $Key => $Val) {
			if ($i % 8 == 0) {
				echo '<br />';
			}	?>
					<input type="checkbox" name="releases[]" value="<?=$Key?>" id="release_<?=$Key?>"
						<?=(((!$Submitted) || !empty($ReleaseArray) && in_array($Key, $ReleaseArray)) ? ' checked="checked" ' : '')?>
					/> <label for="release_<?=$Key?>"><?=$Val?></label>
<?			$i++;
		} ?>
				</td>
			</tr>
			<tr id="format_list">
				<td class="label">Formats</td>
				<td>
					<input type="checkbox" id="toggle_formats" onchange="Toggle('formats', 0);"<?=(!$Submitted || !empty($FormatArray) && count($FormatArray) == count($Formats) ? ' checked="checked"' : '') ?> />
					<label for="toggle_formats">All</label>
					<input type="checkbox" id="formats_strict" name="formats_strict"<?=(!empty($_GET['formats_strict']) ? ' checked="checked"' : '')?> />
					<label for="formats_strict">Only specified</label>
<?		foreach ($Formats as $Key => $Val) {
			if ($Key % 8 == 0) {
				echo '<br />';
			}	?>
					<input type="checkbox" name="formats[]" value="<?=$Key?>" id="format_<?=$Key?>"
						<?=(((!$Submitted) || !empty($FormatArray) && in_array($Key, $FormatArray)) ? ' checked="checked" ' : '')?>
					/> <label for="format_<?=$Key?>"><?=$Val?></label>
<?		} ?>
				</td>
			</tr>
			<tr id="bitrate_list">
				<td class="label">Bitrates</td>
				<td>
					<input type="checkbox" id="toggle_bitrates" onchange="Toggle('bitrates', 0);"<?=(!$Submitted || !empty($BitrateArray) && count($BitrateArray) == count($Bitrates) ? ' checked="checked"' : '')?> />
					<label for="toggle_bitrates">All</label>
					<input type="checkbox" id="bitrate_strict" name="bitrate_strict"<?=(!empty($_GET['bitrate_strict']) ? ' checked="checked"' : '') ?> />
					<label for="bitrate_strict">Only specified</label>
<?		foreach ($Bitrates as $Key => $Val) {
			if ($Key % 8 == 0) {
				echo '<br />';
			}	?>
					<input type="checkbox" name="bitrates[]" value="<?=$Key?>" id="bitrate_<?=$Key?>"
						<?=(((!$Submitted) || !empty($BitrateArray) && in_array($Key, $BitrateArray)) ? ' checked="checked" ' : '')?>
					/> <label for="bitrate_<?=$Key?>"><?=$Val?></label>
<?		} ?>
				</td>
			</tr>
			<tr id="media_list">
				<td class="label">Media</td>
				<td>
					<input type="checkbox" id="toggle_media" onchange="Toggle('media', 0);"<?=(!$Submitted || !empty($MediaArray) && count($MediaArray) == count($Media) ? ' checked="checked"' : '')?> />
					<label for="toggle_media">All</label>
					<input type="checkbox" id="media_strict" name="media_strict"<?=(!empty($_GET['media_strict']) ? ' checked="checked"' : '')?> />
					<label for="media_strict">Only specified</label>
<?		foreach ($Media as $Key => $Val) {
			if ($Key % 8 == 0) {
				echo '<br />';
			}	?>
					<input type="checkbox" name="media[]" value="<?=$Key?>" id="media_<?=$Key?>"
						<?=(((!$Submitted) || !empty($MediaArray) && in_array($Key, $MediaArray)) ? ' checked="checked" ' : '')?>
					/> <label for="media_<?=$Key?>"><?=$Val?></label>
<?		} ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center">
					<input type="submit" value="Search requests" />
				</td>
			</tr>
		</table>
	</form>

<?		if ($NumResults) { ?>
	<div class="linkbox">
		<?=$PageLinks?>
	</div>
<?		} ?>
	<table id="request_table" class="request_table border" cellpadding="6" cellspacing="1" border="0" width="100%">
		<tr class="colhead_dark">
			<td style="width: 38%;" class="nobr">
				<strong>Request name</strong> / <a href="?order=year&amp;sort=<?=(($CurrentOrder == 'year') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>"><strong>Year</strong></a>
			</td>
			<td class="nobr">
				<a href="?order=votes&amp;sort=<?=(($CurrentOrder == 'votes') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>"><strong>Votes</strong></a>
			</td>
			<td class="nobr">
				<a href="?order=bounty&amp;sort=<?=(($CurrentOrder == 'bounty') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>"><strong>Bounty</strong></a>
			</td>
			<td class="nobr">
				<a href="?order=filled&amp;sort=<?=(($CurrentOrder == 'filled') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>"><strong>Filled</strong></a>
			</td>
			<td class="nobr">
				<strong>Filled by</strong>
			</td>
			<td class="nobr">
				<strong>Requested by</strong>
			</td>
			<td class="nobr">
				<a href="?order=created&amp;sort=<?=(($CurrentOrder == 'created') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>"><strong>Created</strong></a>
			</td>
			<td class="nobr">
				<a href="?order=lastvote&amp;sort=<?=(($CurrentOrder == 'lastvote') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>"><strong>Last vote</strong></a>
			</td>
		</tr>
<?	if ($NumResults == 0) { ?>
		<tr class="rowb">
			<td colspan="8">
				Nothing found!
			</td>
		</tr>
<?	} elseif ($NumResults < ($Page - 1) * REQUESTS_PER_PAGE + 1) { ?>
		<tr class="rowb">
			<td colspan="8">
				The requested page contains no matches!
			</td>
		</tr>
<?	} else {

	//We don't use sphinxapi's default cache searcher, we use our own functions
	if (!empty($SphinxResults['notfound'])) {
		$SQLResults = Requests::get_requests($SphinxResults['notfound']);
		if (is_array($SQLResults['notfound'])) {
			//Something wasn't found in the db, remove it from results
			reset($SQLResults['notfound']);
			foreach ($SQLResults['notfound'] as $ID) {
				unset($SQLResults['matches'][$ID]);
				unset($SphinxResults['matches'][$ID]);
			}
		}

		// Merge SQL results with memcached results
		foreach ($SQLResults['matches'] as $ID => $SQLResult) {
			$SphinxResults['matches'][$ID] = $SQLResult;

			//$Requests['matches'][$ID] = array_merge($Requests['matches'][$ID], $SQLResult);
			//We ksort because depending on the filter modes, we're given our data in an unpredictable order
			//ksort($Requests['matches'][$ID]);
		}
	}

	$Requests = $SphinxResults['matches'];

		$Row = 'a';
		$TimeCompare = 1267643718; // Requests v2 was implemented 2010-03-03 20:15:18
		foreach ($Requests as $RequestID => $Request) {

			//list($BitrateList, $CatalogueNumber, $CategoryID, $Description, $FillerID, $FormatList, $RequestID, $Image, $LogCue, $MediaList, $ReleaseType,
			//	$Tags, $TimeAdded, $TimeFilled, $Title, $TorrentID, $RequestorID, $RequestorName, $Year, $RequestID, $Categoryid, $FillerID, $LastVote,
			//	$ReleaseType, $TagIDs, $TimeAdded, $TimeFilled, $TorrentID, $RequestorID, $Voters) = array_values($Request);

			list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Year, $Image, $Description, $CatalogueNumber, $RecordLabel,
				$ReleaseType, $BitrateList, $FormatList, $MediaList, $LogCue, $FillerID, $FillerName, $TorrentID, $TimeFilled) = $Request;

			$RequestVotes = Requests::get_votes_array($RequestID);

			$VoteCount = count($RequestVotes['Voters']);

			if ($CategoryID == 0) {
				$CategoryName = 'Unknown';
			} else {
				$CategoryName = $Categories[$CategoryID - 1];
			}

			$IsFilled = ($TorrentID != 0);

			if ($CategoryName == 'Music') {
				$ArtistForm = Requests::get_artists($RequestID);
				$ArtistLink = Artists::display_artists($ArtistForm, true, true);
				$FullName = $ArtistLink."<a href=\"requests.php?action=view&amp;id=".$RequestID."\">$Title [$Year]</a>";
			} elseif ($CategoryName == 'Audiobooks' || $CategoryName == 'Comedy') {
				$FullName = "<a href=\"requests.php?action=view&amp;id=".$RequestID."\">$Title [$Year]</a>";
			} else {
				$FullName ="<a href=\"requests.php?action=view&amp;id=".$RequestID."\">$Title</a>";
			}

			$Row = ($Row == 'a') ? 'b' : 'a';

			$Tags = $Request['Tags'];
?>
		<tr class="row<?=$Row?>">
			<td>
				<?=$FullName?>
				<div class="tags">
<?
			$TagList = array();
			foreach ($Tags as $TagID => $TagName) {
				$TagList[] = '<a href="?tags='.$TagName.($BookmarkView ? '&amp;type=requests' : '').'\">'.display_str($TagName).'</a>';
			}
			$TagList = implode(', ', $TagList);
?>
					<?=$TagList?>
				</div>
			</td>
			<td class="nobr">
				<span id="vote_count_<?=$RequestID?>"><?=number_format($VoteCount)?></span>
<?		 	if (!$IsFilled && check_perms('site_vote')) { ?>
				&nbsp;&nbsp; <a href="javascript:Vote(0, <?=$RequestID?>)" class="brackets"><strong>+</strong></a>
<?			} ?>
			</td>
			<td class="nobr">
				<?=Format::get_size($RequestVotes['TotalBounty'])?>
			</td>
			<td>
<?			if ($IsFilled) { ?>
				<a href="torrents.php?<?=(strtotime($TimeFilled) < $TimeCompare ? 'id=' : 'torrentid=').$TorrentID?>"><strong><?=time_diff($TimeFilled)?></strong></a>
<?			} else { ?>
				<strong>No</strong>
<?			} ?>
			</td>
			<td>
<?			if ($IsFilled) { ?>
				<a href="user.php?id=<?=$FillerID?>"><?=$FillerName?></a>
<?			} else { ?>
				&mdash;
<?			} ?>
			</td>
			<td>
				<a href="user.php?id=<?=$RequestorID?>"><?=$RequestorName?></a>
			</td>
			<td>
				<?=time_diff($TimeAdded)?>
			</td>
			<td>
				<?=time_diff($LastVote)?>
			</td>
		</tr>
<?
		} // while
	} // else
?>
	</table>
	<div class="linkbox">
		<?=$PageLinks?>
	</div>
</div>
<? View::show_footer(); ?>
