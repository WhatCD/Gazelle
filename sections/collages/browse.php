<?
define('COLLAGES_PER_PAGE', 25);

include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

list($Page,$Limit) = page_limit(COLLAGES_PER_PAGE);


$OrderVals = array('Time', 'Name', 'Torrents');
$WayVals = array('Ascending', 'Descending');
$OrderTable = array('Time'=>'ID', 'Name'=>'c.Name', 'Torrents'=>'NumTorrents');
$WayTable = array('Ascending'=>'ASC', 'Descending'=>'DESC');

// Are we searching in bodies, or just names?
if(!empty($_GET['type'])) {
	$Type = $_GET['type'];
	if(!in_array($Type, array('c.name', 'description'))) {
		$Type = 'c.name';
	}
} else {
	$Type = 'c.name';
}

if(!empty($_GET['search'])) {
	// What are we looking for? Let's make sure it isn't dangerous.
	$Search = strtr(db_string(trim($_GET['search'])),$SpecialChars);
	// Break search string down into individual words
	$Words = explode(' ', $Search);
}

if(!empty($_GET['tags'])) {
	$Tags = explode(',',db_string(trim($_GET['tags'])));
	foreach($Tags as $ID=>$Tag) {
		$Tags[$ID] = sanitize_tag($Tag);
	}
}

if(!empty($_GET['cats'])) {
	$Categories = $_GET['cats'];
	foreach($Categories as $Cat=>$Accept) {
		if(empty($CollageCats[$Cat]) || !$Accept) { unset($Categories[$Cat]); }
	}
	$Categories = array_keys($Categories);
} else {
	$Categories = array(1,2,3,4,5,6);
}

// Ordering
if(!empty($_GET['order']) && !empty($OrderTable[$_GET['order']])) {
	$Order = $OrderTable[$_GET['order']];
} else {
	$Order = 'ID';
}

if(!empty($_GET['way']) && !empty($WayTable[$_GET['way']])) {
	$Way = $WayTable[$_GET['way']];
} else {
	$Way = 'DESC';
}

$BookmarkView = !empty($_GET['bookmarks']);

if ($BookmarkView) {
	$BookmarkJoin = 'INNER JOIN bookmarks_collages AS bc ON c.ID = bc.CollageID';
} else {
	$BookmarkJoin = '';
}

$BaseSQL = $SQL = "SELECT SQL_CALC_FOUND_ROWS 
	c.ID, 
	c.Name, 
	c.NumTorrents,
	c.TagList,
	c.CategoryID,
	c.UserID,
	um.Username 
	FROM collages AS c 
	$BookmarkJoin
	LEFT JOIN users_main AS um ON um.ID=c.UserID 
	WHERE Deleted = '0'";

if ($BookmarkView) {
	$SQL .= " AND bc.UserID = '" . $LoggedUser['ID'] . "'";
}



if(!empty($Search)) {
	$SQL .= " AND $Type LIKE '%";
	$SQL .= implode("%' AND $Type LIKE '%", $Words);
	$SQL .= "%'";
}

if(!empty($Tags)) {
	$SQL.= " AND TagList LIKE '%";
	$SQL .= implode("%' AND TagList LIKE '%", $Tags);
	$SQL .= "%'";
}

if(!empty($_GET['userid'])) {
	$UserID = $_GET['userid'];
	if(!is_number($UserID)) {
		error(404);
	}
	$User = user_info($UserID);
	$Perms = get_permissions($User['PermissionID']);
	$UserClass = $Perms['Class'];

	$UserLink = '<a href="user.php?id='.$UserID.'">'.$User['Username'].'</a>';
	if(!empty($_GET['contrib'])) {
		if (!check_paranoia('collagecontribs', $User['Paranoia'], $UserClass, $UserID)) { error(403); }
		$DB->query("SELECT DISTINCT CollageID FROM collages_torrents WHERE UserID = $UserID");
		$CollageIDs = $DB->collect('CollageID');
		if(empty($CollageIDs)) {
			$SQL .= " AND 0";
		} else {
			$SQL .= " AND c.ID IN(".db_string(implode(',', $CollageIDs)).")";
		}
	} else {
		if (!check_paranoia('collages', $User['Paranoia'], $UserClass, $UserID)) { error(403); }
		$SQL .= " AND UserID='".$_GET['userid']."'";
	}
	$Categories[] = 0;
}

if(!empty($Categories)) {
	$SQL.=" AND CategoryID IN(".db_string(implode(',',$Categories)).")";
}

if ($_GET['action'] == 'mine') {
	$SQL = $BaseSQL;
	$SQL .= " AND c.UserID='".$LoggedUser['ID']."' AND c.CategoryID=0";
}

$SQL.=" ORDER BY $Order $Way LIMIT $Limit ";
$DB->query($SQL);
$Collages = $DB->to_array();
$DB->query("SELECT FOUND_ROWS()");
list($NumResults) = $DB->next_record();

show_header(($BookmarkView)?'Your bookmarked collages':'Browse collages');
?>
<div class="thin">
<? if ($BookmarkView) { ?>
	<h2>Your bookmarked collages</h2>
<? } else { ?>
	<h2>Browse collages<?=(!empty($UserLink) ? (isset($CollageIDs) ? ' with contributions by '.$UserLink : ' started by '.$UserLink) : '')?></h2>
<? } ?>
<? if (!$BookmarkView) { ?>
	<div>
		<form action="" method="get">
			<div><input type="hidden" name="action" value="search" /></div>
			<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
				<tr>
					<td class="label"><strong>Search for:</strong></td>
					<td colspan="3">
						<input type="text" name="search" size="70" value="<?=(!empty($_GET['search']) ? display_str($_GET['search']) : '')?>" />
					</td>
				</tr>
				<tr>
					<td class="label"><strong>Tags:</strong></td>
					<td colspan="3">
						<input type="text" name="tags" size="70" value="<?=(!empty($_GET['tags']) ? display_str($_GET['tags']) : '')?>" />
					</td>
				</tr>
				<tr>
					<td class="label"><strong>Categories:</strong></td>
					<td colspan="3">
<? foreach($CollageCats as $ID=>$Cat) { ?>
						<input type="checkbox" value="1" name="cats[<?=$ID?>]" id="cats_<?=$ID?>" <?if(in_array($ID, $Categories)) { echo ' checked="checked"'; }?>>
						<label for="cats_<?=$ID?>"><?=$Cat?></label>
<? } ?>
					</td>
				</tr>
				<tr>
					<td class="label"><strong>Search in:</strong></td>
					<td>
						<input type="radio" name="type" value="c.name" <? if($Type == 'c.name') { echo 'checked="checked" '; }?>/> Names
						<input type="radio" name="type" value="description" <? if($Type == 'description') { echo 'checked="checked" '; }?>/> Descriptions
					</td>
					<td class="label"><strong>Order by:</strong></td>
					<td>
						<select name="order">
						<?
							foreach($OrderVals as $Cur){ ?>
							<option value="<?=$Cur?>"<? if(isset($_GET['order']) && $_GET['order'] == $Cur || (!isset($_GET['order']) && $Cur == 'Time')) { echo ' selected="selected"'; } ?>><?=$Cur?></option>
						<?	}?>
						</select>
						<select name="way">
						<?	foreach($WayVals as $Cur){ ?>
							<option value="<?=$Cur?>"<? if(isset($_GET['way']) && $_GET['way'] == $Cur || (!isset($_GET['way']) && $Cur == 'Descending')) { echo ' selected="selected"'; } ?>><?=$Cur?></option>
						<?	}?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="4" class="center">
						<input type="submit" value="Search" />
					</td>
				</tr>
			</table>	
		</form>
	</div>
<? } // if (!$BookmarkView) ?>
	<div class="linkbox">
<? if (!$BookmarkView) {
if (check_perms('site_collages_create')) { ?>
		<a href="collages.php?action=new">[New collage]</a>
<? } 
if (check_perms('site_collages_personal')) {
	
 	$DB->query("SELECT ID FROM collages WHERE UserID='$LoggedUser[ID]' AND CategoryID='0' AND Deleted='0'");
	$CollageCount = $DB->record_count();
	
	if ($CollageCount == 1) {
		list($CollageID) = $DB->next_record();
?>
		<a href="collages.php?id=<?=$CollageID?>">[My personal collage]</a>
<?	} elseif ($CollageCount > 1) { ?>
		<a href="collages.php?action=mine">[My personal collages]</a>
<?	}
} 
if (check_perms('site_collages_subscribe')) { ?>
		<a href="userhistory.php?action=subscribed_collages">[My Subscribed Collages]</a>
<? }
if (check_perms('site_collages_recover')) { ?>
		<a href="collages.php?action=recover">[Recover collage]</a>
<?
}
if (check_perms('site_collages_create') || check_perms('site_collages_personal') || check_perms('site_collages_recover')) {
?>
		<br />
<?
}
?>
		<a href="collages.php?userid=<?=$LoggedUser['ID']?>">[Collages you started]</a>
		<a href="collages.php?userid=<?=$LoggedUser['ID']?>&amp;contrib=1">[Collages you've contributed to]</a>
<? } else { ?>
		<a href="bookmarks.php?type=torrents">[Torrents]</a>
		<a href="bookmarks.php?type=artists">[Artists]</a>
		<a href="bookmarks.php?type=collages">[Collages]</a>
		<a href="bookmarks.php?type=requests">[Requests]</a>
<? } ?>
<br /><br />
<?
$Pages=get_pages($Page,$NumResults,COLLAGES_PER_PAGE,9);
echo $Pages;
?>
	</div>
<? if (count($Collages) == 0) { ?>
<div class="box pad" align="center">
<?	if ($BookmarkView) { ?>
	<h2>You have not bookmarked any collages.</h2>
<?	} else { ?>
	<h2>Your search did not match anything.</h2>
	<p>Make sure all names are spelled correctly, or try making your search less specific.</p>
<?	} ?>
</div><!--box-->
</div><!--content-->
<? show_footer(); die();
} ?>
<table width="100%">
	<tr class="colhead">
		<td>Category</td>
		<td>Collage</td>
		<td>Torrents</td>
		<td>Author</td>
	</tr>
<?
$Row = 'a'; // For the pretty colours
foreach ($Collages as $Collage) {
	list($ID, $Name, $NumTorrents, $TagList, $CategoryID, $UserID, $Username) = $Collage;
	$Row = ($Row == 'a') ? 'b' : 'a';
	$TagList = explode(' ', $TagList);
	$Tags = array();
	foreach($TagList as $Tag) {
		$Tags[]='<a href="collages.php?action=search&amp;tags='.$Tag.'">'.$Tag.'</a>';
	}
	$Tags = implode(', ', $Tags);
	
	//Print results
?>
	<tr class="row<?=$Row?> <?=($BookmarkView)?'bookmark_'.$ID:''?>">
		<td>
			<a href="collages.php?action=search&amp;cats[<?=(int)$CategoryID?>]=1"><?=$CollageCats[(int)$CategoryID]?></a>
		</td>
		<td>
			<a href="collages.php?id=<?=$ID?>"><?=$Name?></a>
<?	if ($BookmarkView) { ?>
			<span style="float:right">
				<a href="#" onclick="Unbookmark('collage', <?=$ID?>,'');return false;">[Remove bookmark]</a>
			</span>
<?	} ?>
			<div class="tags">
				<?=$Tags?>
			</div>
		</td>
		<td><?=(int)$NumTorrents?></td>
		<td><?=format_username($UserID, $Username)?></td>
	</tr>
<? } ?>
</table>
	<div class="linkbox"><?=$Pages?></div>
</div>
<?
show_footer();
?>
