<?
//~~~~~~~~~~~ Main artist page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//

//For sorting tags
function compare($X, $Y){
	return($Y['count'] - $X['count']);
}

include(SERVER_ROOT.'/sections/bookmarks/functions.php'); // has_bookmarked()
include(SERVER_ROOT.'/sections/requests/functions.php');
include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;


// Similar artist map
include(SERVER_ROOT.'/classes/class_artist.php');
include(SERVER_ROOT.'/classes/class_artists_similar.php');

$ArtistID = $_GET['id'];
if(!is_number($ArtistID)) { error(0); }


if(!empty($_GET['revisionid'])) { // if they're viewing an old revision
	$RevisionID=$_GET['revisionid'];
	if(!is_number($RevisionID)){ error(0); }
	$Data = $Cache->get_value("artist_$ArtistID"."_revision_$RevisionID");
} else { // viewing the live version
	$Data = $Cache->get_value('artist_'.$ArtistID);
	$RevisionID = false;
}
if($Data) {
	$Data = unserialize($Data);
	list($K, list($Name, $Image, $Body, $NumSimilar, $SimilarArray, $TorrentList, $GroupMeta)) = each($Data);
	
} else {
	$sql = "SELECT
		a.Name,
		wiki.Image,
		wiki.body,
		a.VanityHouse
		FROM artists_group AS a
		LEFT JOIN wiki_artists AS wiki ON wiki.RevisionID=a.RevisionID
		WHERE ";
	if($RevisionID){
		$sql.=" wiki.RevisionID='$RevisionID' ";
	} else {
		$sql.=" a.ArtistID='$ArtistID' ";
	}
	$sql .= " GROUP BY a.ArtistID";
	$DB->query($sql, MYSQLI_NUM, true);
	
	if($DB->record_count()==0) { error(404); }
	
	list($Name, $Image, $Body, $VanityHouseArtist) = $DB->next_record();
}

//----------------- Build list and get stats

ob_start();


// Requests
$Requests = $Cache->get_value('artists_requests_'.$ArtistID);
if(!is_array($Requests)) {
	$DB->query("SELECT
			r.ID,
			r.CategoryID,
			r.Title,
			r.Year,
			r.TimeAdded,
			COUNT(rv.UserID) AS Votes,
			SUM(rv.Bounty) AS Bounty
		FROM requests AS r
			LEFT JOIN requests_votes AS rv ON rv.RequestID=r.ID
			LEFT JOIN requests_artists AS ra ON r.ID=ra.RequestID 
		WHERE ra.ArtistID = ".$ArtistID."
			AND r.TorrentID = 0
		GROUP BY r.ID
		ORDER BY Votes DESC");
	
	if($DB->record_count() > 0) {
		$Requests = $DB->to_array();
	} else {
		$Requests = array();
	}
	$Cache->cache_value('artists_requests_'.$ArtistID, $Requests);
}
$NumRequests = count($Requests);

$LastReleaseType = 0;
if(empty($GroupMeta) || empty($TorrentList)) {
	$DB->query("SELECT 
			DISTINCT ta.GroupID, ta.Importance, tg.VanityHouse
			FROM torrents_artists AS ta
			JOIN torrents_group AS tg ON tg.ID=ta.GroupID
			WHERE ta.ArtistID='$ArtistID'
			ORDER BY ta.Importance, tg.ReleaseType ASC, tg.Year DESC");
	
	$GroupIDs = $DB->collect('GroupID');
	$GroupMeta = $DB->to_array('GroupID', MYSQLI_BOTH, false);
	if(count($GroupIDs)>0) {
		$TorrentList = get_groups($GroupIDs, true, true);
		$TorrentList = $TorrentList['matches'];
	} else {
		$TorrentList = array();
	}
}
$NumGroups = count($TorrentList);

if(!empty($TorrentList)) {
?>
<div id="discog_table">
<?
}

// Get list of used release types
$UsedReleases = array();
$TorrentListByReleaseType = array();
// Check if user has preferences for ordering/showing
if (isset($LoggedUser['ArtistOptions']) && is_array($LoggedUser['ArtistOptions'])) {
	$ReleaseTypesOrder = $LoggedUser['ArtistOptions'];
	$HasShown = in_array(1, $ReleaseTypesOrder);
	// Add releasetypes from default order, useful when new types are added
	foreach($ReleaseTypesDefaultOrder as $Key => $ReleaseType) {
		if(!array_key_exists($ReleaseType, $ReleaseTypesOrder)) {
			$ReleaseTypesOrder[$ReleaseType] = $HasShown ? 1 : 0; // If no releasetype is open by default assume (s)he wants them all closed
		}
	}
} else {
	// Load defaults
	$ReleaseTypesOrder = array_flip($ReleaseTypesDefaultOrder);
}

foreach($ReleaseTypesOrder as $ReleaseType => $ReleaseTypeName) {
	$TorrentListByReleaseType[$ReleaseType] = array();
}

//Names for releasetypes that are not uploadable but exist in the defaultordering
$ReleaseTypes[1023] = "Remixed By";
$ReleaseTypes[1024] = "Guest Appearance";

foreach($TorrentList as $GroupID=>$Group) {
	if($GroupMeta[$GroupID]['Importance'] == '2') {
		$TorrentList[$GroupID]['ReleaseType'] = 1024;
	}
	if($GroupMeta[$GroupID]['Importance'] == '3') {
		$TorrentList[$GroupID]['ReleaseType'] = 1023;
	}
	if(!in_array($TorrentList[$GroupID]['ReleaseType'], $UsedReleases)) {
		$UsedReleases[] = $TorrentList[$GroupID]['ReleaseType'];
	}
	$TorrentListByReleaseType[$TorrentList[$GroupID]['ReleaseType']][$GroupID] = $Group;
}

reset($TorrentList);
if(!empty($UsedReleases)) { ?>
	<div id="releasetypes"class="box center">
	<span id="releasetype_links">
<?
	foreach($ReleaseTypesOrder as $ReleaseID => $Torrents) {
		if(!in_array($ReleaseID, $UsedReleases)) {
			continue;
		}
		switch($ReleaseTypes[$ReleaseID]) {
			case "Remix" :
				$DisplayName = "Remixes";
				break;
			case "Anthology" :
				$DisplayName = "Anthologies";
				break;
			default :
				$DisplayName = $ReleaseTypes[$ReleaseID]."s";
				break;
		}

?>
		<a href="#torrents_<?=str_replace(" ", "_", strtolower($ReleaseTypes[$ReleaseID]))?>" id="torrents_<?=$ReleaseID?>_anchorlink">[<?=$DisplayName?>]</a>
<?
	}
	if ($NumRequests > 0) {
?>
	<a href="#requests" id="requests_anchorlink">[Requests]</a>
	</span>
	<span style="position:right" id="torrents_allopenclose" class="hidden">
		<a href="#" onclick="setDefaultShow('all', true);return false;">[All default open]</a>
		<a href="#" onclick="setDefaultShow('all', false);return false;">[All default closed]</a>
	</span>
<? } ?>
	</div>
<? }

$NumTorrents = 0;
$NumSeeders = 0;
$NumLeechers = 0;
$NumSnatches = 0;

function display_name($ReleaseType) {
	global $ReleaseTypes;
	return $DisplayName;
}
?>
<div id="torrents_tables">
<?
$OpenTable = false;
foreach ($TorrentListByReleaseType as $ReleaseType => $TorrentListForReleaseType) {
	$NumTorrentsReleaseType = count($TorrentListForReleaseType);
	if($OpenTable) { 
		?></table><?
		$OpenTable = false;
	}
	switch($ReleaseTypes[$ReleaseType]) {
		case "Remix" :
			$DisplayName = "Remixes";
			break;
		case "Anthology" :
			$DisplayName = "Anthologies";
			break;
		default :
			$DisplayName = $ReleaseTypes[$ReleaseType]."s";
			break;
	}
	$ReleaseTypeLabel = strtolower(str_replace(' ','_',$ReleaseTypes[$ReleaseType]));

	if ((isset($LoggedUser['ArtistOptions']) && array_key_exists($ReleaseType, $LoggedUser['ArtistOptions'])) && $LoggedUser['ArtistOptions'][$ReleaseType] == 0) {
		$HideDiscog = " hidden";
		$HideDiscogDefault = true;		
	}
	else {
		$HideDiscog = "";
		$HideDiscogDefault = false;	
	}	
	?>
	<table class="torrent_table releases_<?=$ReleaseType?>_table<?=$NumTorrentsReleaseType==0?" empty hidden":""?>" id="torrents_<?=$ReleaseTypeLabel?>">
		<tr class="colhead_dark">
			<td class="small" style="width: 28px;"></td>
			<td width="70%">
				<span class="artist_normalcol">
				<? if($NumTorrentsReleaseType > 0) { ?>
					<a href="#">&uarr;</a>
				<? } ?>
				</span>&nbsp;
				<strong><?=$DisplayName?></strong>&nbsp;
				<span class="artist_normalcol">
				<? if($NumTorrentsReleaseType > 0) { ?>
					(<a href="#"<?=!$HideDiscogDefault?'class="hidden"':''?> id="releases_<?=$ReleaseType?>_viewlink" onclick="setShow('<?=$ReleaseType?>',true);return false;">View</a><a href="#"<?=$HideDiscogDefault?'class="hidden"':''?> id="releases_<?=$ReleaseType?>_hidelink" onclick="setShow('<?=$ReleaseType?>',false);return false;">Hide</a>)
				<? } ?>
				</span>
			</td>
			<td class="artist_normalcol">Size</td>
			<td class="artist_normalcol sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/snatched.png" alt="Snatches" title="Snatches" /></td>
			<td class="artist_normalcol sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/seeders.png" alt="Seeders" title="Seeders" /></td>
			<td class="artist_normalcol sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/leechers.png" alt="Leechers" title="Leechers" /></td>
			<td class="artist_editcol hidden" align="right">
				<a href="#"<?=!$HideDiscogDefault?'class="hidden"':''?> id="releases_<?=$ReleaseType?>_openlink" onclick="setDefaultShow('<?=$ReleaseType?>',true);return false;">[default closed]</a>
				<a href="#"<?=$HideDiscogDefault?'class="hidden"':''?> id="releases_<?=$ReleaseType?>_closedlink" onclick="setDefaultShow('<?=$ReleaseType?>',false);return false;">[default open]</a>
				<input class="hidden" id="releases_<?=$ReleaseType?>_defaultopen"<?=!$HideDiscogDefault?' checked="checked"':''?> type="checkbox" title="Show" />
				&nbsp;
				<span style="cursor:move;" id="torrents_<?=$ReleaseTypeLabel?>_handle">&#9776;</span>
			</td>
		</tr>
		<?
		$OpenTable = true;

	foreach($TorrentListForReleaseType as $GroupID => $Group) {
		list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TagList, $NewReleaseType, $GroupVanityHouse, $Torrents, $GroupArtists) = array_values($Group);
		$GroupVanityHouse = $GroupMeta[$GroupID]['VanityHouse'];
		$TagList = explode(' ',str_replace('_','.',$TagList));

		$TorrentTags = array();
	
		// $Tags array is for the sidebar on the right
		foreach($TagList as $Tag) {
			if(!isset($Tags[$Tag])) {
				$Tags[$Tag] = array('name'=>$Tag, 'count'=>1);
			} else {
				$Tags[$Tag]['count']++;
			}
			$TorrentTags[]='<a href="torrents.php?taglist='.$Tag.'">'.$Tag.'</a>';
		}
		$TorrentTags = implode(', ', $TorrentTags);
		$TorrentTags = '<br /><div class="tags">'.$TorrentTags.'</div>';

		if (($ReleaseType == 1023) || ($ReleaseType == 1024)) {
			$ArtistPrefix = display_artists(array(1 => $GroupArtists));
		} else {
			$ArtistPrefix = '';
		}
	
		$DisplayName = $ArtistPrefix . '<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
		if($GroupYear>0) { $DisplayName = $GroupYear. ' - '.$DisplayName; }

		if($GroupVanityHouse) { $DisplayName .= ' [<abbr title="This is a vanity house release">VH</abbr>]'; }

?>
			<tr class="releases_<?=$ReleaseType?> group discog<?=$HideDiscog?>">
				<td class="center">
					<div class="show_torrents"><a href="#" class="show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event)" title="Collapse this group"></a></div>
				</td>
				<td class="artist_normalcol" colspan="6">
					<strong><?=$DisplayName?></strong>
					<?=$TorrentTags?>
				</td>
			</tr>
<?
		$LastRemasterYear = '-';
		$LastRemasterTitle = '';
		$LastRemasterRecordLabel = '';
		$LastRemasterCatalogueNumber = '';
		$LastMedia = '';
	
		$EditionID = 0;
	
		foreach ($Torrents as $TorrentID => $Torrent) {
			$NumTorrents++;
		
			$Torrent['Seeders'] = (int)$Torrent['Seeders'];
			$Torrent['Leechers'] = (int)$Torrent['Leechers'];
			$Torrent['Snatched'] = (int)$Torrent['Snatched'];
		
			$NumSeeders+=$Torrent['Seeders'];
			$NumLeechers+=$Torrent['Leechers'];
			$NumSnatches+=$Torrent['Snatched'];
		
			if($Torrent['RemasterTitle'] != $LastRemasterTitle || $Torrent['RemasterYear'] != $LastRemasterYear ||
			$Torrent['RemasterRecordLabel'] != $LastRemasterRecordLabel || $Torrent['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber || $Torrent['Media'] != $LastMedia) {
		
				$EditionID++;
			
				if($Torrent['RemasterTitle']  || $Torrent['RemasterYear'] || $Torrent['RemasterRecordLabel'] || $Torrent['RemasterCatalogueNumber']) {				
					$RemasterName = $Torrent['RemasterYear'];
					$AddExtra = " - ";
					if($Torrent['RemasterRecordLabel']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterRecordLabel']); $AddExtra=' / '; }
					if($Torrent['RemasterCatalogueNumber']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterCatalogueNumber']); $AddExtra=' / '; }
					if($Torrent['RemasterTitle']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterTitle']); $AddExtra=' / '; }
					$RemasterName .= $AddExtra.display_str($Torrent['Media']);
				
?>
	<tr class="releases_<?=$ReleaseType?> groupid_<?=$GroupID?> edition group_torrent discog <?=$HideDiscog?>">
		<td colspan="7" class="artist_normalcol edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" title="Collapse this edition">&minus;</a> <?=$RemasterName?></strong></a></td>
	</tr>
<?
				} else {
					$MasterName = "Original Release";
					$AddExtra = " / ";
					if($GroupRecordLabel) { $MasterName .= $AddExtra.$GroupRecordLabel; $AddExtra=' / '; }
					if($GroupCatalogueNumber) { $MasterName .= $AddExtra.$GroupCatalogueNumber; $AddExtra=' / '; }
					$MasterName .= $AddExtra.display_str($Torrent['Media']);
?>
	<tr class="releases_<?=$ReleaseType?> groupid_<?=$GroupID?> edition group_torrent <?=$HideDiscog?>">
		<td colspan="7" class="artist_normalcol edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" title="Collapse this edition">&minus;</a> <?=$MasterName?></strong></a></td>
	</tr>
<?
				}
			}
			$LastRemasterTitle = $Torrent['RemasterTitle'];
			$LastRemasterYear = $Torrent['RemasterYear'];
			$LastRemasterRecordLabel = $Torrent['RemasterRecordLabel'];
			$LastRemasterCatalogueNumber = $Torrent['RemasterCatalogueNumber'];
			$LastMedia = $Torrent['Media'];
?>
	<tr class="releases_<?=$ReleaseType?> groupid_<?=$GroupID?> edition_<?=$EditionID?> group_torrent discog <?=$HideDiscog?>">
		<td class="artist_normalcol" colspan="2">
			<span>
				[<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>]
			</span>
			&nbsp;&nbsp;&raquo;&nbsp; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=torrent_info($Torrent)?></a>
		</td>
		<td class="artist_normalcol nobr"><?=get_size($Torrent['Size'])?></td>
		<td class="artist_normalcol"><?=number_format($Torrent['Snatched'])?></td>
		<td class="artist_normalcol<?=($Torrent['Seeders']==0)?' r00':''?>"><?=number_format($Torrent['Seeders'])?></td>
		<td class="artist_normalcol"><?=number_format($Torrent['Leechers'])?></td>
		<td class="artist_editcol hidden"></td>
	</tr>
<?
		}
	}
}
if(!empty($TorrentList)) { ?>
			</table>
		</div>
<?}
?>
</div>
<?

$TorrentDisplayList = ob_get_clean();

//----------------- End building list and getting stats

show_header($Name, 'browse,requests,artists,bbcode');
?>
<div class="thin">
	<h2><?=$Name?><? if ($RevisionID) { ?> (Revision #<?=$RevisionID?>)<? } if ($VanityHouseArtist) { ?> [Vanity House] <? } ?></h2>
		<span style="font-size: 0.7em;float:right;">
			<a href="#" onclick="editOrdering();return false;" class="" id="editlayout">[Edit ordering]</a>
			<span class="hidden" id="savinglayout">Saving ordering...</span>
			<a href="#" onclick="saveOrdering();return false;" class="hidden" id="savelayout">[Save ordering]</a>
		</span>
	</h2>
	<div id="emptylinkbox" class="linkbox hidden"></div>
	<div id="linkbox" class="linkbox">
<? if (check_perms('site_submit_requests')) { ?>
		<a href="requests.php?action=new&amp;artistid=<?=$ArtistID?>">[Add Request]</a>
<? }

if (check_perms('site_torrents_notify')) {
	if (($Notify = $Cache->get_value('notify_artists_'.$LoggedUser['ID'])) === FALSE) {
		$DB->query("SELECT ID, Artists FROM users_notify_filters WHERE UserID='$LoggedUser[ID]' AND Label='Artist notifications' LIMIT 1");
		$Notify = $DB->next_record(MYSQLI_ASSOC);
		$Cache->cache_value('notify_artists_'.$LoggedUser['ID'], $Notify, 0);
	}
	if (stripos($Notify['Artists'], '|'.$Name.'|') === FALSE) {
?>
		<a href="artist.php?action=notify&amp;artistid=<?=$ArtistID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">[Notify of new uploads]</a>
<?
	} else {
?>
		<a href="artist.php?action=notifyremove&amp;artistid=<?=$ArtistID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">[Do not notify of new uploads]</a>
<?
	}
}

if (has_bookmarked('artist', $ArtistID)) {
?>
		<a href="#" id="bookmarklink_artist_<?=$ArtistID?>" onclick="Unbookmark('artist', <?=$ArtistID?>,'[Bookmark]');return false;">[Remove bookmark]</a>

<?
	} else { 
?>
		<a href="#" id="bookmarklink_artist_<?=$ArtistID?>" onclick="Bookmark('artist', <?=$ArtistID?>,'[Remove bookmark]');return false;">[Bookmark]</a>
<?
}

if (check_perms('site_edit_wiki')) {
?>
		<a href="artist.php?action=edit&amp;artistid=<?=$ArtistID?>">[Edit]</a>
<? } ?>
		<a href="artist.php?action=history&amp;artistid=<?=$ArtistID?>">[View history]</a>
<? if (check_perms('site_delete_artist') && check_perms('torrents_delete')) { ?>
		<a href="artist.php?action=delete&amp;artistid=<?=$ArtistID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">[Delete]</a>
<? }

if ($RevisionID && check_perms('site_edit_wiki')) {
?>
		<a href="artist.php?action=revert&amp;artistid=<?=$ArtistID?>&amp;revisionid=<?=$RevisionID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">
			[Revert to this revision]
		</a>
<? } ?>
	</div>
	<div class="sidebar">
<? if($Image) { ?>
		<div class="box">
			<div class="head"><strong><?=$Name?></strong></div>
			<div style="text-align:center;padding:10px 0px;">
				<img style="max-width: 220px;" src="<?=$Image?>" alt="<?=$Name?>" onclick="lightbox.init(this,220);" />
			</div>
		</div>
<?	}
//<strip>
if(check_perms('zip_downloader')){
	if(isset($LoggedUser['Collector'])) {
		list($ZIPList,$ZIPPrefs) = $LoggedUser['Collector'];
		$ZIPList = explode(':',$ZIPList);
	} else {
		$ZIPList = array('00','11');
		$ZIPPrefs = 1;
	}
?>
		<div class="box">
			<div class="head colhead_dark"><strong>Collector</strong></div>
			<div class="pad">
				<form action="artist.php" method="post">
					<input type="hidden" name="action" value="download" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="artistid" value="<?=$ArtistID?>" /> 
				<ul id="list" class="nobullet">
<? foreach ($ZIPList as $ListItem) { ?>
					<li id="list<?=$ListItem?>">
						<input type="hidden" name="list[]" value="<?=$ListItem?>" /> 
						<span style="float:left;"><?=$ZIPOptions[$ListItem]['2']?></span>
						<a href="#" onclick="remove_selection('<?=$ListItem?>');return false;" style="float:right;">[X]</a>
						<br style="clear:all;" />
					</li>
<? } ?>
				</ul>
				<select id="formats" style="width:180px">
<?
$OpenGroup = false;
$LastGroupID=-1;

foreach ($ZIPOptions as $Option) {
	list($GroupID,$OptionID,$OptName) = $Option;

	if($GroupID!=$LastGroupID) {
		$LastGroupID=$GroupID;
		if($OpenGroup) { ?>
					</optgroup>
<?		} ?>
					<optgroup label="<?=$ZIPGroups[$GroupID]?>">
<?		$OpenGroup = true;
	}
?>
						<option id="opt<?=$GroupID.$OptionID?>" value="<?=$GroupID.$OptionID?>"<? if(in_array($GroupID.$OptionID,$ZIPList)){ echo ' disabled="disabled"'; }?>><?=$OptName?></option>
<?
}
?>
					</optgroup>
				</select>
				<button type="button" onclick="add_selection()">+</button>
				<select name="preference" style="width:210px">
					<option value="0"<? if($ZIPPrefs==0){ echo ' selected="selected"'; } ?>>Prefer Original</option>
					<option value="1"<? if($ZIPPrefs==1){ echo ' selected="selected"'; } ?>>Prefer Best Seeded</option>
					<option value="2"<? if($ZIPPrefs==2){ echo ' selected="selected"'; } ?>>Prefer Bonus Tracks</option>
				</select>
				<input type="submit" style="width:210px" value="Download" /> 
				</form>
			</div>
		</div>
<? } //<strip> 
?>
		<div class="box">
			<div class="head"><strong>Tags</strong></div>
			<ul class="stats nobullet">
<?
uasort($Tags, 'compare');
foreach ($Tags as $TagName => $Tag) {
?>
					<li><a href="torrents.php?taglist=<?=$TagName?>"><?=$TagName?></a> (<?=$Tag['count']?>)</li>
<?
}
?>
			</ul>
		</div>
<?

// Stats
?>
			<div class="box">
			<div class="head"><strong>Statistics</strong></div>
			<ul class="stats nobullet">
				<li>Number of groups: <?=$NumGroups?></li>
				<li>Number of torrents: <?=$NumTorrents?></li>
				<li>Number of seeders: <?=$NumSeeders?></li>
				<li>Number of leechers: <?=$NumLeechers?></li>
				<li>Number of snatches: <?=$NumSnatches?></li>
			</ul>
		</div>
<?


if(empty($SimilarArray)) {
	$DB->query("
		SELECT
		s2.ArtistID,
		a.Name,
		ass.Score,
		ass.SimilarID
		FROM artists_similar AS s1
		JOIN artists_similar AS s2 ON s1.SimilarID=s2.SimilarID AND s1.ArtistID!=s2.ArtistID
		JOIN artists_similar_scores AS ass ON ass.SimilarID=s1.SimilarID
		JOIN artists_group AS a ON a.ArtistID=s2.ArtistID
		WHERE s1.ArtistID='$ArtistID'
		ORDER BY ass.Score DESC
		LIMIT 30
	");
	$SimilarArray = $DB->to_array();
	$NumSimilar = count($SimilarArray);
}
?>
		<div class="box">
			<div class="head"><strong>Similar artists</strong></div>
			<ul class="stats nobullet">
<?
	if($NumSimilar == 0) { ?>
				<li><i>None found</i></li> 
<?	}
	$First = true;
	foreach ($SimilarArray as $SimilarArtist) {	
		list($Artist2ID, $Artist2Name, $Score, $SimilarID) = $SimilarArtist;
		$Score = $Score/100;
		if($First) {
			$Max = $Score + 1	;
			$First = false;
		}
		
		$FontSize = (ceil((((($Score - 2)/$Max - 2) * 4)))) + 8;
		
?>
				<li>
					<span title=<?=$Score?>><a href="artist.php?id=<?=$Artist2ID?>" style="float:left; display:block;"><?=$Artist2Name?></a></span>										<div style="float:right; display:block; letter-spacing: -1px;">
					<a href="artist.php?action=vote_similar&amp;artistid=<?=$ArtistID?>&amp;similarid=<?=$SimilarID?>&amp;way=down" style="font-family: monospace;">[&minus;]</a>
					<a href="artist.php?action=vote_similar&amp;artistid=<?=$ArtistID?>&amp;similarid=<?=$SimilarID?>&amp;way=up" style="font-family: monospace;">[+]</a>
<?		if(check_perms('site_delete_tag')) { ?> 
					<a href="artist.php?action=delete_similar&amp;similarid=<?=$SimilarID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">[X]</a>
<?		} ?> 
					</div>
					<br style="clear:both" />
				</li>
<?		} ?>
			</ul>
		</div>
		<div class="box">
			<div class="head"><strong>Add similar artist</strong></div>
			<ul class="nobullet">
				<li>
					<form action="artist.php" method="post">
						<input type="hidden" name="action" value="add_similar" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" name="artistid" value="<?=$ArtistID?>" />
						<input type="text" autocomplete="off" id="artistsimilar" name="artistname" size="20" />
						<input type="submit" value="+" />
					</form>
				</li>
			</ul>
		</div>
	</div>
	<div class="main_column">
<?

echo $TorrentDisplayList;

if($NumRequests > 0) {
	
?>
	<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%" id="requests">
		<tr class="colhead_dark">
			<td style="width:48%;">
				<a href="#">&uarr;</a>&nbsp;
				<strong>Request Name</strong>
			</td>
			<td>
				<strong>Vote</strong>
			</td>
			<td>
				<strong>Bounty</strong>
			</td>
			<td>
				<strong>Added</strong>
			</td>
		</tr>
<?
	foreach($Requests as $Request) {
		list($RequestID, $CategoryID, $Title, $Year, $TimeAdded, $Votes, $Bounty) = $Request;
		
			$CategoryName = $Categories[$CategoryID - 1];
			
			if($CategoryName == "Music") {
				$ArtistForm = get_request_artists($RequestID);
				$ArtistLink = display_artists($ArtistForm, true, true);
				$FullName = $ArtistLink."<a href='requests.php?action=view&amp;id=".$RequestID."'>".$Title." [".$Year."]</a>";
			} else if($CategoryName == "Audiobooks" || $CategoryName == "Comedy") {
				$FullName = "<a href='requests.php?action=view&amp;id=".$RequestID."'>".$Title." [".$Year."]</a>";
			} else {
				$FullName ="<a href='requests.php?action=view&amp;id=".$RequestID."'>".$Title."</a>";
			}
			
			$Row = ($Row == 'a') ? 'b' : 'a';
			
			$Tags = get_request_tags($RequestID);
?>
		<tr class="row<?=$Row?>">
			<td>
				<?=$FullName?>
				<div class="tags">
<?			
		$TagList = array();
		foreach($Tags as $TagID => $TagName) {
			$TagList[] = "<a href='requests.php?tag=".$TagID."'>".display_str($TagName)."</a>";
		}
		$TagList = implode(', ', $TagList);
?>
					<?=$TagList?>
				</div>
			</td>
			<td>
				<?=$Votes?> 
<?  	if(check_perms('site_vote')){ ?>
				<input type="hidden" id="auth" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				&nbsp;&nbsp; <a href="javascript:Vote(20971520)"><strong>(+)</strong></a>
<?		} ?> 
			</td>
			<td>
				<?=get_size($Bounty)?>
			</td>
			<td>
				<?=time_diff($TimeAdded)?>
			</td>
		</tr>
<?	} ?>
	</table>
<?
}

// Similar artist map

if($NumSimilar>0) {
	$SimilarData = $Cache->get_value('similar_positions_'.$ArtistID);
	
	if(!$Data) {
		include(SERVER_ROOT.'/classes/class_image.php');
		$Img = new IMAGE;
		$Img->create(WIDTH, HEIGHT);
		$Img->color(255,255,255, 127);
		
		$Similar = new ARTISTS_SIMILAR($ArtistID, $Name);
		$Similar->set_up();
		$Similar->set_positions();
		$Similar->background_image();
		
		
		$SimilarData = $Similar->dump_data();
		
		$Cache->cache_value('similar_positions_'.$ArtistID, $SimilarData, 3600*24);
	} else {
		$Similar = new ARTISTS_SIMILAR($ArtistID);
		$Similar->load_data($SimilarData);
	}
?>

		<div id="similar_artist_map" class="box">
			<div class="head"><strong>Similar artist map</strong></div>
			<div style="width:<?=WIDTH?>px;height:<?=HEIGHT?>px;position:relative;background-image:url(static/similar/<?=$ArtistID?>.png?t=<?=time()?>)">
<?
	$Similar->write_artists();
?>
			</div>
		</div>
<? } // if $NumSimilar>0 ?> 
		<div class="box">
			<div class="head"><strong>Artist info</strong></div>
			<div class="body"><?=$Text->full_format($Body)?></div>
		</div>
	</div>
</div>
<?
show_footer();


// Cache page for later use

if($RevisionID) { 
	$Key = "artist_$ArtistID"."_revision_$RevisionID";
} else { 
	$Key = 'artist_'.$ArtistID;
}

$Data = serialize(array(array($Name, $Image, $Body, $NumSimilar, $SimilarArray, $TorrentList, $GroupMeta)));

$Cache->cache_value($Key, $Data, 3600);
?>
