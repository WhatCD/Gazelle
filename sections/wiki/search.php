<?
if(empty($_GET['nojump'])) {
	$ArticleID = $Alias->to_id($_GET['search']);
	if($ArticleID) { //Found Article
		header('Location: wiki.php?action=article&id='.$ArticleID);
	}
}

define('ARTICLES_PER_PAGE', 25);
list($Page,$Limit) = page_limit(ARTICLES_PER_PAGE);

$OrderVals = array('Title', 'Created', 'Edited');
$WayVals = array('Ascending', 'Descending');
$TypeTable = array('Title'=>'w.Title', 'Body'=>'w.Body');
$OrderTable = array('Title'=>'w.Title', 'Created'=>'w.ID', 'Edited'=>'w.Date');
$WayTable = array('Ascending'=>'ASC', 'Descending'=>'DESC');

// What are we looking for? Let's make sure it isn't dangerous.
$Search = db_string(trim($_GET['search']));

if(!in_array($Type, array('w.Title', 'w.Body'))) { $Type = 'w.Title'; }

// Break search string down into individual words
$Words = explode(' ', $Search);

$Type = $TypeTable[$_GET['type']];
if(!$Type) { $Type = 'w.Title'; }

$Order = $OrderTable[$_GET['order']];
if(!$Order) { $Order = 'ID'; }

$Way = $WayTable[$_GET['way']];
if(!$Way) { $Way = 'DESC'; }

$SQL = "SELECT SQL_CALC_FOUND_ROWS 
	w.ID, 
	w.Title, 
	w.Date,
	w.Author,
	u.Username 
	FROM wiki_articles AS w
	LEFT JOIN users_main AS u ON u.ID=w.Author 
	WHERE w.MinClassRead <= '".$LoggedUser['Class']."'";
if($Search!='') {
	$SQL .= " AND $Type LIKE '%";
	$SQL .= implode("%' AND $Type LIKE '%", $Words);
	$SQL .= "%' ";
}

$SQL.=" ORDER BY $Order $Way LIMIT $Limit ";
$RS = $DB->query($SQL);
$DB->query("SELECT FOUND_ROWS()");
list($NumResults) = $DB->next_record();
$DB->set_query_id($RS);

show_header('Search articles');
?>
<div class="thin">
	<h2>Search articles</h2>
	<div class="linkbox">
		[<a href="wiki.php?action=create&amp;alias=<?=display_str($Alias->convert($_GET['search']))?>">Create an article</a>] [<a  href="wiki.php?action=link&amp;alias=<?=display_str($Alias->convert($_GET['search']))?>">Link this search</a>]
	</div>
	<div>
		<form action="" method="get">
			<div>
				<input type="hidden" name="action" value="search" />
				<input type="hidden" name="nojump" value="1" />
			</div>
			<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
				<tr>
					<td class="label"><strong>Search for:</strong></td>
					<td colspan="3">
						<input type="text" name="search" size="70" value="<?=display_str($_GET['search'])?>" />
					</td>
				</tr>
				<tr>
					<td class="label"><strong>Search in:</strong></td>
					<td>
						<input type="radio" name="type" value="Title" <? if($Type == 'w.Title') { echo 'checked="checked" '; }?>/> Title
						<input type="radio" name="type" value="Body" <? if($Type == 'w.Body') { echo 'checked="checked" '; }?>/> Body
					</td>
					<td class="label"><strong>Order by:</strong></td>
					<td>
						<select name="order">
						<?
							foreach($OrderVals as $Cur){ ?>
							<option value="<?=$Cur?>"<? if($_GET['order'] == $Cur || (!$_GET['order'] && $Cur == 'Time')) { echo ' selected="selected"'; } ?>><?=$Cur?></option>
						<?	}?>
						</select>
						<select name="way">
						<?	foreach($WayVals as $Cur){ ?>
							<option value="<?=$Cur?>"<? if($_GET['way'] == $Cur || (!$_GET['way'] && $Cur == 'Descending')) { echo ' selected="selected"'; } ?>><?=$Cur?></option>
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
	<br />
	<div class="linkbox">
<?
$Pages=get_pages($Page,$NumResults,ARTICLES_PER_PAGE,'action=search&amp;type='.display_str($_GET['type']).'&amp;search='.display_str($_GET['search']));
echo $Pages;
?>
	</div>
<table width="100%">
	<tr class="colhead">
		<td>Article</td>
		<td>Last Updated</td>
		<td>Last edited by</td>
	</tr>
<? while(list($ID, $Title, $Date, $UserID, $Username) = $DB->next_record()) {?>
	<tr>
		<td><a href="wiki.php?action=article&id=<?=$ID?>"><?=$Title?></a></td>
		<td><?=$Date?></td>
		<td><?=format_username($UserID, $Username)?></td>
	</tr>
<? } ?>
</table>
	<div class="linkbox"><?=$Pages?></div>
</div>
<? show_footer(); ?>
