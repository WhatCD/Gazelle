<?
$Letter = strtoupper(substr($_GET['letter'],0,1));

$sql = "SELECT SQL_CALC_FOUND_ROWS 
	w.ID, 
	w.Title, 
	w.Date,
	w.Author,
	u.Username 
	FROM wiki_articles AS w
	LEFT JOIN users_main AS u ON u.ID=w.Author 
	WHERE w.MinClassRead <= '".$LoggedUser['Class']."'";
if($Letter!=='1') {
	$sql .= " AND UPPER(LEFT(w.Title,1)) = '".db_string($Letter)."'";
} else {
	$Letter = 'All';
}
$sql .= " ORDER BY Title";

$DB->query($sql);

$Title = 'Browse articles';
if($Letter) { $Title.= ' ('.$Letter.')'; }
show_header($Title);
?>
<div class="thin">
<? if($Letter) { ?>
	<h2>Browse articles (<?=$Letter?>)</h2>
	<table width="100%" style="margin-bottom:10px;">
		<tr class="colhead">
			<td>Article</td>
			<td>Last Updated</td>
			<td>Last edited by</td>
		</tr>
<? 	while(list($ID, $Title, $Date, $UserID, $Username) = $DB->next_record()) {?>
		<tr>
			<td><a href="wiki.php?action=article&id=<?=$ID?>"><?=$Title?></a></td>
			<td><?=$Date?></td>
			<td><?=format_username($UserID, $Username)?></td>
		</tr>
<? 	} ?>
	</table>
<? } ?>
	<div class="box pad center">
		<p>Search the wiki for user created tutorials and information.</p>
		<form action="wiki.php" method="get">
			<input type="hidden" name="action" value="search">
			<input type="hidden" name="nojump" value="1" />
			<input type="text" name="search" size="80" />
			<input value="Search" type="submit" class="hidden" />
		</form>
		<br />
		<p>Additionally you can manually browse through the articles by their first letter.</p>
		<span>
			<a href="wiki.php?action=browse&amp;letter=a">A</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=b">B</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=c">C</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=d">D</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=e">E</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=f">F</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=g">G</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=h">H</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=i">I</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=j">J</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=k">K</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=l">L</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=m">M</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=n">N</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=o">O</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=p">P</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=q">Q</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=r">R</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=s">S</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=t">T</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=u">U</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=v">V</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=w">W</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=x">X</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=y">Y</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=z">Z</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=1">All</a>&nbsp;&nbsp;
		</span>
	</div>
</div>
<? show_footer(); ?>
